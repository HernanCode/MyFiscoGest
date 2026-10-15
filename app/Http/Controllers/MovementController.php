<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMovementRequest;
use App\Http\Requests\UpdateMovementRequest;
use App\Models\Category;
use App\Models\FiscalYear;
use App\Models\Movement;
use App\Services\RecurringMovementGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MovementController extends Controller
{
    public function index(Request $request): View
    {
        $filters = collect($request->only(['year', 'quarter', 'type', 'payment_status']))
            ->reject(fn ($value) => $value === null || $value === '')
            ->all();

        $sort = in_array($request->string('sort')->toString(), ['date', 'concept', 'total_amount'], true)
            ? $request->string('sort')->toString()
            : 'date';
        $dir = strtolower($request->string('dir')->toString()) === 'asc' ? 'asc' : 'desc';

        $query = Movement::query()
            ->forUser($request->user()->id)
            ->with(['fiscalYear', 'category'])
            ->when(isset($filters['year']), fn ($q) => $q->where('fiscal_year_id', (int) $filters['year']))
            ->when(isset($filters['quarter']), fn ($q) => $q->where('quarter', (int) $filters['quarter']))
            ->when(isset($filters['type']), fn ($q) => $q->where('type', $filters['type']))
            ->when(isset($filters['payment_status']), fn ($q) => $q->where('payment_status', $filters['payment_status']));

        if ($request->filled('q')) {
            $term = $request->string('q')->trim()->toString();
            $query->where(fn ($q) => $q->where('concept', 'like', "%{$term}%")->orWhere('counterparty', 'like', "%{$term}%"));
        }

        $query->orderBy($sort, $dir)->orderBy('id', $dir);

        $movements = $query->paginate(15)->withQueryString();

        // Resumen del periodo filtrado (año + trimestre): Ingresos, Gastos e IVA.
        $period = Movement::query()
            ->forUser($request->user()->id)
            ->when(isset($filters['year']), fn ($q) => $q->where('fiscal_year_id', (int) $filters['year']))
            ->when(isset($filters['quarter']), fn ($q) => $q->where('quarter', (int) $filters['quarter']));

        $summary = [
            'income' => (float) (clone $period)->income()->sum('base_amount'),
            'expense' => (float) (clone $period)->expense()->sum('base_amount'),
            'vat' => (float) (clone $period)->income()->sum('vat_amount')
                - (float) (clone $period)->expense()->sum('vat_amount'),
        ];

        return view('movements.index', [
            'movements' => $movements,
            'fiscalYears' => FiscalYear::forUser($request->user()->id)->orderBy('year')->get(),
            'filters' => $filters,
            'q' => $request->string('q')->trim()->toString(),
            'sort' => $sort,
            'dir' => $dir,
            'summary' => $summary,
        ]);
    }

    public function create(Request $request): View
    {
        return view('movements.form', [
            'movement' => null,
            'categories' => Category::orderBy('name')->get(),
            'defaultType' => $request->string('tipo')->toString() === 'gasto' ? 'expense' : 'income',
            'recurringMovement' => null,
            'counterparties' => Movement::counterpartySuggestions($request->user()->id),
        ]);
    }

    public function store(StoreMovementRequest $request, RecurringMovementGenerator $generator): RedirectResponse
    {
        $data = $request->validated();
        unset($data['invoice']);
        $data['user_id'] = $request->user()->id;

        $isRecurring = $data['recurring'] ?? false;
        $recurringDay = (int) ($data['recurring_day'] ?? 0);
        unset($data['recurring'], $data['recurring_day']);

        $data['irpf_rate'] = $data['type'] === 'income' ? (float) $data['irpf_rate'] : 0;
        $data['deductible_pct'] = $data['type'] === 'expense' ? (int) $data['deductible_pct'] : 100;
        $data['paid_at'] = $data['payment_status'] === 'paid' ? now()->toDateString() : null;

        $movement = Movement::create($data);

        if ($request->hasFile('invoice')) {
            $movement->update(['invoice_path' => $this->storeInvoice($movement, $request->file('invoice'))]);
        }

        if ($isRecurring) {
            $generator->createFromMovement($movement, $recurringDay);
        }

        return redirect()
            ->route('movements.index')
            ->with('status', $isRecurring
                ? 'Movimiento creado y recurrencia programada correctamente.'
                : 'Movimiento creado correctamente.');
    }

    public function edit(Movement $movement): View
    {
        $this->authorizeOwner($movement);

        return view('movements.form', [
            'movement' => $movement,
            'categories' => Category::orderBy('name')->get(),
            'defaultType' => $movement->type,
            'recurringMovement' => $movement->recurringMovement,
            'counterparties' => Movement::counterpartySuggestions(auth()->id()),
        ]);
    }

    public function update(UpdateMovementRequest $request, Movement $movement, RecurringMovementGenerator $generator): RedirectResponse
    {
        $this->authorizeOwner($movement);

        $data = $request->validated();
        unset($data['invoice']);

        $isRecurring = $data['recurring'] ?? false;
        $recurringDay = (int) ($data['recurring_day'] ?? 0);
        unset($data['recurring'], $data['recurring_day']);

        $data['irpf_rate'] = $movement->type === 'income' ? (float) ($data['irpf_rate'] ?? 0) : 0;
        $data['deductible_pct'] = $movement->type === 'expense' ? (int) ($data['deductible_pct'] ?? 100) : 100;
        $data['paid_at'] = $data['payment_status'] === 'paid'
            ? ($movement->paid_at?->toDateString() ?? now()->toDateString())
            : null;

        $oldInvoicePath = $movement->invoice_path;

        $movement->update($data);

        if ($request->hasFile('invoice')) {
            $this->deleteInvoiceFile($oldInvoicePath);
            $movement->update(['invoice_path' => $this->storeInvoice($movement, $request->file('invoice'))]);
        } else {
            $this->moveInvoiceIfNeeded($movement, $oldInvoicePath);
        }

        // Convertir en recurrente un movimiento que aún no lo era.
        if ($isRecurring && $movement->recurring_movement_id === null) {
            $generator->createFromMovement($movement, $recurringDay);

            return redirect()
                ->route('movements.index')
                ->with('status', 'Movimiento actualizado y recurrencia programada correctamente.');
        }

        return redirect()
            ->route('movements.index')
            ->with('status', 'Movimiento actualizado correctamente.');
    }

    public function destroy(Movement $movement): RedirectResponse
    {
        $this->authorizeOwner($movement);

        $this->deleteInvoiceFile($movement->invoice_path);
        $movement->delete();

        return redirect()
            ->route('movements.index')
            ->with('status', 'Movimiento eliminado correctamente.');
    }

    /**
     * Ver la factura adjunta (inline: PDF e imágenes se muestran en el navegador).
     */
    public function invoice(Movement $movement): StreamedResponse
    {
        $this->authorizeOwner($movement);

        abort_unless($movement->invoice_path && Storage::disk('local')->exists($movement->invoice_path), 404);

        return Storage::disk('local')->response($movement->invoice_path, basename($movement->invoice_path));
    }

    /**
     * Guarda la factura en invoices/{año}/T{trimestre}/ y devuelve su ruta relativa.
     */
    private function storeInvoice(Movement $movement, UploadedFile $file): string
    {
        $directory = "invoices/{$movement->fiscalYear->year}/T{$movement->quarter}";
        $name = $movement->id.'_'.$this->sanitizeFileName($file->getClientOriginalName());

        return $file->storeAs($directory, $name, 'local');
    }

    /**
     * Si la fecha cambió de año o trimestre, mueve la factura a su nueva carpeta.
     */
    private function moveInvoiceIfNeeded(Movement $movement, ?string $oldPath): void
    {
        if (! $oldPath || ! Storage::disk('local')->exists($oldPath)) {
            return;
        }

        $newPath = "invoices/{$movement->fiscalYear->year}/T{$movement->quarter}/".basename($oldPath);

        if ($newPath !== $oldPath) {
            Storage::disk('local')->move($oldPath, $newPath);
            $movement->updateQuietly(['invoice_path' => $newPath]);
        }
    }

    private function deleteInvoiceFile(?string $path): void
    {
        if ($path && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }

    private function sanitizeFileName(string $name): string
    {
        $name = basename($name);
        $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $name) ?: 'factura';

        return $name;
    }
}
