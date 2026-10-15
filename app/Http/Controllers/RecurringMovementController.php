<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateRecurringMovementRequest;
use App\Models\Category;
use App\Models\RecurringMovement;
use App\Services\RecurringMovementGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Pantalla de gastos e ingresos recurrentes: listado, edición de la plantilla,
 * pausa y generación manual de los movimientos vencidos.
 */
class RecurringMovementController extends Controller
{
    public function __construct(private RecurringMovementGenerator $generator) {}

    public function index(): View
    {
        $recurringMovements = RecurringMovement::forUser(auth()->id())->with('category')->orderBy('concept')->get();

        return view('movements.recurring', [
            'recurringMovements' => $recurringMovements,
            'dueCount' => $recurringMovements->filter(fn (RecurringMovement $recurring) => $recurring->hasDueOccurrences())->count(),
        ]);
    }

    public function edit(RecurringMovement $recurringMovement): View
    {
        $this->authorizeOwner($recurringMovement);

        return view('movements.recurring-edit', [
            'recurringMovement' => $recurringMovement,
            'categories' => Category::orderBy('name')->get(),
            'counterparties' => \App\Models\Movement::counterpartySuggestions(auth()->id()),
        ]);
    }

    public function update(UpdateRecurringMovementRequest $request, RecurringMovement $recurringMovement): RedirectResponse
    {
        $this->authorizeOwner($recurringMovement);

        $data = $request->validated();
        $dayChanged = (int) $data['day_of_month'] !== $recurringMovement->day_of_month;

        $recurringMovement->update($data);

        // Si cambió el día, la próxima generación se recoloca en el primer día
        // indicado que aún no haya pasado (los meses ya generados no se tocan).
        if ($dayChanged && $recurringMovement->active) {
            $recurringMovement->update([
                'next_date' => $this->generator->firstOccurrenceAfter($recurringMovement, today())->toDateString(),
            ]);
        }

        return redirect()
            ->route('movements.recurring.index')
            ->with('status', 'Recurrencia actualizada correctamente.');
    }

    public function toggle(RecurringMovement $recurringMovement): RedirectResponse
    {
        $this->authorizeOwner($recurringMovement);

        if ($recurringMovement->active) {
            $recurringMovement->update(['active' => false]);
            $status = 'Recurrencia pausada: no se generarán nuevos movimientos.';
        } else {
            $this->generator->reactivate($recurringMovement);
            $status = 'Recurrencia reactivada.';
        }

        return redirect()->route('movements.recurring.index')->with('status', $status);
    }

    public function destroy(RecurringMovement $recurringMovement): RedirectResponse
    {
        $this->authorizeOwner($recurringMovement);

        $recurringMovement->delete();

        return redirect()
            ->route('movements.recurring.index')
            ->with('status', 'Recurrencia eliminada. Los movimientos ya generados se mantienen.');
    }

    /**
     * Botón "Generar ahora": crea todos los movimientos vencidos pendientes.
     */
    public function generate(): RedirectResponse
    {
        $created = $this->generator->generateDueForAll(auth()->id());

        $status = $created > 0
            ? "Se han generado {$created} movimientos."
            : 'No hay ningún movimiento pendiente de generar.';

        return redirect()->route('movements.recurring.index')->with('status', $status);
    }
}
