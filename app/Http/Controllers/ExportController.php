<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\FiscalYear;
use App\Models\Movement;
use App\Models\QuarterFiling;
use App\Models\RecurringMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Exportación de datos:
 * - `movements()`: CSV de movimientos (para el gestor), respetando los filtros
 *   del listado (año, trimestre, tipo, estado y búsqueda).
 * - `all()`: copia de seguridad completa en ZIP (CSV de todo + facturas).
 *
 * Formato CSV apto para Excel en español: separador `;` y BOM UTF-8; los
 * importes van con coma decimal y sin separador de miles.
 */
class ExportController extends Controller
{
    public function movements(Request $request): StreamedResponse
    {
        $movements = $this->movementsQuery($request)->get();

        return $this->streamCsv(
            'movimientos-'.$this->periodLabel($request).'.csv',
            fn ($out) => $this->writeMovements($out, $movements),
        );
    }

    public function all(): BinaryFileResponse
    {
        $userId = auth()->id();

        $path = tempnam(sys_get_temp_dir(), 'fiscal-backup-');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);

        $zip->addFromString('movimientos.csv', $this->csvString(
            fn ($out) => $this->writeMovements($out, Movement::forUser($userId)->with(['category', 'fiscalYear'])->orderBy('date')->orderBy('id')->get()),
        ));
        $zip->addFromString('categorias.csv', $this->csvString(
            fn ($out) => $this->writeCategories($out, Category::orderBy('name')->get()),
        ));
        $zip->addFromString('recurrentes.csv', $this->csvString(
            fn ($out) => $this->writeRecurring($out, RecurringMovement::forUser($userId)->with('category')->orderBy('concept')->get()),
        ));
        $zip->addFromString('presentaciones.csv', $this->csvString(
            fn ($out) => $this->writeFilings($out, QuarterFiling::forUser($userId)->with('fiscalYear')->orderBy('due_date')->get()),
        ));
        $zip->addFromString('LEEME.txt', $this->readme());

        // Facturas adjuntas, clasificadas por año, trimestre y tipo (ingresos/gastos).
        Movement::forUser($userId)->with('fiscalYear')->whereNotNull('invoice_path')->get()->each(function (Movement $movement) use ($zip) {
            if (! Storage::disk('local')->exists($movement->invoice_path)) {
                return;
            }

            $folder = 'facturas/'.($movement->fiscalYear?->year ?? 'sin-ano').'/T'.$movement->quarter.'/'
                .($movement->type === 'income' ? 'ingresos' : 'gastos');

            $zip->addFromString($folder.'/'.basename($movement->invoice_path), Storage::disk('local')->get($movement->invoice_path));
        });

        $zip->close();

        return response()
            ->download($path, 'fiscal-backup-'.now()->format('Y-m-d').'.zip')
            ->deleteFileAfterSend(true);
    }

    // ---- Consulta ----

    /**
     * Mismos filtros que el listado de movimientos. Orden cronológico (el
     * gestor espera las filas por fecha, no por el criterio de la tabla).
     */
    private function movementsQuery(Request $request): Builder
    {
        $query = Movement::query()->forUser($request->user()->id)->with(['category', 'fiscalYear'])
            ->when($request->filled('year'), fn (Builder $q) => $q->where('fiscal_year_id', $request->integer('year')))
            ->when($request->filled('quarter'), fn (Builder $q) => $q->where('quarter', $request->integer('quarter')))
            ->when($request->filled('type'), fn (Builder $q) => $q->where('type', $request->string('type')->toString()))
            ->when($request->filled('payment_status'), fn (Builder $q) => $q->where('payment_status', $request->string('payment_status')->toString()));

        if ($request->filled('q')) {
            $term = $request->string('q')->trim()->toString();
            $query->where(fn (Builder $q) => $q->where('concept', 'like', "%{$term}%")->orWhere('counterparty', 'like', "%{$term}%"));
        }

        return $query->orderBy('date')->orderBy('id');
    }

    private function periodLabel(Request $request): string
    {
        $parts = [];

        if ($request->filled('year')) {
            $year = FiscalYear::forUser($request->user()->id)->find($request->integer('year'));
            $parts[] = (string) ($year?->year ?? $request->integer('year'));
        }
        if ($request->filled('quarter')) {
            $parts[] = 'T'.$request->integer('quarter');
        }
        if ($request->filled('type')) {
            $parts[] = $request->string('type')->toString() === 'income' ? 'ingresos' : 'gastos';
        }
        if ($request->filled('payment_status')) {
            $parts[] = $request->string('payment_status')->toString() === 'paid' ? 'cobrados' : 'pendientes';
        }

        return $parts === [] ? 'todos' : implode('-', $parts);
    }

    // ---- Contenido ----

    private function writeMovements($out, iterable $movements): void
    {
        $this->putCsv($out, ['Fecha', 'Tipo', 'Concepto', 'Cliente/Proveedor', 'Categoría', 'Nº factura', 'Base', '% IVA', 'Cuota IVA', '% IRPF', 'Cuota IRPF', '% Deducible', 'Total', 'Estado', 'Trimestre', 'Año', 'Factura']);

        foreach ($movements as $movement) {
            $this->putCsv($out, [
                $movement->date->format('d/m/Y'),
                $movement->type === 'income' ? 'Ingreso' : 'Gasto',
                $this->safe($movement->concept),
                $this->safe($movement->counterparty),
                $this->safe($movement->category?->name),
                $this->safe($movement->invoice_number),
                $this->number($movement->base_amount),
                $this->number($movement->vat_rate),
                $this->number($movement->vat_amount),
                $this->number($movement->irpf_rate),
                $this->number($movement->irpf_amount),
                $this->number($movement->deductible_pct),
                $this->number($movement->total_amount),
                $movement->payment_status === 'paid' ? 'Cobrado/Pagado' : 'Pendiente',
                'T'.$movement->quarter,
                (string) ($movement->fiscalYear?->year ?? ''),
                $movement->invoice_path ? 'Sí' : 'No',
            ]);
        }
    }

    private function writeCategories($out, iterable $categories): void
    {
        $this->putCsv($out, ['ID', 'Nombre', 'Tipo', '% deducible por defecto']);

        foreach ($categories as $category) {
            $this->putCsv($out, [
                (string) $category->id,
                $this->safe($category->name),
                $category->type === 'income' ? 'Ingreso' : 'Gasto',
                (string) $category->default_deductible_pct,
            ]);
        }
    }

    private function writeRecurring($out, iterable $recurringMovements): void
    {
        $this->putCsv($out, ['Concepto', 'Tipo', 'Cliente/Proveedor', 'Categoría', 'Base', '% IVA', '% IRPF', '% Deducible', 'Día del mes', 'Inicio', 'Fin', 'Próxima', 'Activa']);

        foreach ($recurringMovements as $recurring) {
            $this->putCsv($out, [
                $this->safe($recurring->concept),
                $recurring->type === 'income' ? 'Ingreso' : 'Gasto',
                $this->safe($recurring->counterparty),
                $this->safe($recurring->category?->name),
                $this->number($recurring->base_amount),
                $this->number($recurring->vat_rate),
                $this->number($recurring->irpf_rate),
                $this->number($recurring->deductible_pct),
                (string) $recurring->day_of_month,
                $recurring->start_date?->format('d/m/Y') ?? '',
                $recurring->ends_on?->format('d/m/Y') ?? '',
                $recurring->next_date?->format('d/m/Y') ?? '',
                $recurring->active ? 'Sí' : 'No',
            ]);
        }
    }

    private function writeFilings($out, iterable $filings): void
    {
        $this->putCsv($out, ['Año', 'Trimestre', 'Modelo', 'Fecha límite', 'Estado', 'Fecha presentación', 'Importe pagado']);

        foreach ($filings as $filing) {
            $this->putCsv($out, [
                (string) ($filing->fiscalYear?->year ?? ''),
                'T'.$filing->quarter,
                $filing->model,
                $filing->due_date?->format('d/m/Y') ?? '',
                $filing->status === 'filed' ? 'Presentado' : 'Pendiente',
                $filing->filed_at?->format('d/m/Y') ?? '',
                $filing->amount_paid !== null ? $this->number($filing->amount_paid) : '',
            ]);
        }
    }

    private function readme(): string
    {
        return implode("\n", [
            'Copia de seguridad de Fiscal',
            'Generada el '.now()->format('d/m/Y H:i'),
            '',
            'Contenido:',
            '- movimientos.csv      Movimientos de todos los años fiscales',
            '- categorias.csv       Categorías de ingreso y gasto',
            '- recurrentes.csv      Plantillas de movimientos recurrentes',
            '- presentaciones.csv   Vencimientos 303/130 y su estado',
            '- facturas/{año}/T{n}/{ingresos|gastos}/   Facturas adjuntas',
            '                        (clasificadas por año, trimestre y tipo)',
            '',
            'Formato CSV: separador ";" y coma decimal, listo para Excel.',
        ])."\n";
    }

    // ---- Utilidades ----

    private function putCsv($out, array $row): void
    {
        // Escape vacío: evita el mecanismo propietario de barras invertidas y la
        // deprecación de PHP 8.4; el entrecomillado estándar sigue funcionando.
        fputcsv($out, $row, ';', '"', '');
    }

    private function number(mixed $value): string
    {
        return number_format((float) $value, 2, ',', '');
    }

    /**
     * Evita inyección de fórmulas en Excel cuando el texto empieza por = + - @
     * o un carácter de control.
     */
    private function safe(?string $value): string
    {
        $value = (string) $value;

        if ($value !== '' && str_contains("=+-@\t\r", $value[0])) {
            return "'".$value;
        }

        return $value;
    }

    private function csvString(callable $write): string
    {
        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");
        $write($out);
        rewind($out);
        $content = stream_get_contents($out);
        fclose($out);

        return $content;
    }

    private function streamCsv(string $filename, callable $write): StreamedResponse
    {
        return response()->streamDownload(function () use ($write) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            $write($out);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
