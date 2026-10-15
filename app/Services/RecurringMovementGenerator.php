<?php

namespace App\Services;

use App\Models\Movement;
use App\Models\RecurringMovement;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Generación de movimientos a partir de plantillas recurrentes (sección 7 de
 * docs/spec.md).
 *
 * Toda la lógica de recurrencia vive aquí (AGENTS.MD: la lógica va en servicios,
 * los controladores se quedan finos). El desencadenado es manual: el botón
 * "Generar ahora" de la pantalla de recurrentes.
 */
class RecurringMovementGenerator
{
    /**
     * Crea la plantilla de un movimiento recién guardado y la liga a él.
     *
     * La primera ocurrencia pendiente es siempre posterior al mes de la fecha
     * del movimiento original: ese mes ya está cubierto por el propio movimiento.
     */
    public function createFromMovement(Movement $movement, int $dayOfMonth): RecurringMovement
    {
        $recurring = new RecurringMovement([
            'type' => $movement->type,
            'category_id' => $movement->category_id,
            'concept' => $movement->concept,
            'counterparty' => $movement->counterparty,
            'base_amount' => $movement->base_amount,
            'vat_rate' => $movement->vat_rate,
            'irpf_rate' => $movement->irpf_rate,
            'deductible_pct' => $movement->deductible_pct,
            'start_date' => $movement->date->toDateString(),
            'day_of_month' => $dayOfMonth,
        ]);

        // Primer día indicado posterior al mes completo del movimiento original:
        // ese mes ya está cubierto por el propio movimiento.
        $recurring->next_date = $this->firstOccurrenceAfter(
            $recurring,
            $movement->date->copy()->endOfMonth()
        )->toDateString();

        $recurring->save();

        // Quietly: el hook `saving` de Movement ya calculó importes y trimestre.
        $movement->updateQuietly(['recurring_movement_id' => $recurring->id]);

        return $recurring;
    }

    /**
     * Genera todas las ocurrencias vencidas de una plantilla y devuelve
     * cuántos movimientos ha creado.
     *
     * Nunca genera meses futuros ni posteriores a `ends_on`, y no duplica una
     * ocurrencia ya generada (índice único como última red de seguridad).
     */
    public function generateDue(RecurringMovement $recurring): int
    {
        if (! $recurring->active) {
            return 0;
        }

        $today = today();
        $created = 0;

        DB::transaction(function () use ($recurring, $today, &$created): void {
            while ($recurring->next_date !== null
                && $recurring->next_date->lte($today)
                && ($recurring->ends_on === null || $recurring->next_date->lte($recurring->ends_on))
            ) {
                $occurrence = $recurring->next_date->toDateString();

                $alreadyGenerated = Movement::where('recurring_movement_id', $recurring->id)
                    ->whereDate('date', $occurrence)
                    ->exists();

                if (! $alreadyGenerated) {
                    Movement::create($this->movementAttributes($recurring, $occurrence));
                    $created++;
                }

                $recurring->next_date = $this->nextOccurrence($recurring);
            }

            $recurring->save();
        });

        return $created;
    }

    /**
     * Genera lo que haya vencido en todas las plantillas activas.
     */
    public function generateDueForAll(?int $userId = null): int
    {
        $total = 0;

        RecurringMovement::query()->forUser($userId)->active()->get()
            ->filter(fn (RecurringMovement $recurring) => $recurring->hasDueOccurrences())
            ->each(function (RecurringMovement $recurring) use (&$total): void {
                $total += $this->generateDue($recurring);
            });

        return $total;
    }

    /**
     * Reactiva una plantilla pausada sin arrastrar los meses no generados:
     * si la próxima ocurrencia ya pasó, se recoloca en el primer día indicado
     * que aún no haya pasado.
     */
    public function reactivate(RecurringMovement $recurring): void
    {
        $recurring->active = true;

        if ($recurring->next_date !== null && $recurring->next_date->lte(today())) {
            $recurring->next_date = $this->firstOccurrenceAfter($recurring, today())->toDateString();
        }

        $recurring->save();
    }

    /**
     * Primera ocurrencia estrictamente posterior a la fecha indicada,
     * respetando el `day_of_month` de la plantilla (recortado a los días
     * del mes si no existe ese día).
     */
    public function firstOccurrenceAfter(RecurringMovement $recurring, CarbonInterface $after): CarbonInterface
    {
        $month = $after->copy()->startOfMonth();
        $occurrence = $this->occurrenceInMonth($month, $recurring->day_of_month);

        if (! $occurrence->gt($after)) {
            $occurrence = $this->occurrenceInMonth($month->addMonth(), $recurring->day_of_month);
        }

        return $occurrence;
    }

    /**
     * Atributos del movimiento generado: una copia de la plantilla en estado
     * "pendiente" y sin factura (cada recibo se adjunta a su propio movimiento).
     *
     * @return array<string, mixed>
     */
    private function movementAttributes(RecurringMovement $recurring, string $date): array
    {
        return [
            'user_id' => $recurring->user_id,
            'type' => $recurring->type,
            'category_id' => $recurring->category_id,
            'concept' => $recurring->concept,
            'counterparty' => $recurring->counterparty,
            'base_amount' => $recurring->base_amount,
            'vat_rate' => $recurring->vat_rate,
            'irpf_rate' => $recurring->irpf_rate,
            'deductible_pct' => $recurring->deductible_pct,
            'date' => $date,
            'payment_status' => 'pending',
            'paid_at' => null,
            'invoice_number' => null,
            'invoice_path' => null,
            'notes' => $recurring->notes,
            'recurring_movement_id' => $recurring->id,
        ];
    }

    /**
     * Ocurrencia del mes siguiente a la actual.
     *
     * Se recalcula desde `day_of_month` en cada paso para no perder el día 31
     * cuando el mes siguiente tiene menos días (31 ene → 28 fe → 31 mar).
     */
    private function nextOccurrence(RecurringMovement $recurring): CarbonInterface
    {
        return $this->occurrenceInMonth(
            $recurring->next_date->copy()->startOfMonth()->addMonth(),
            $recurring->day_of_month
        );
    }

    /**
     * Día `day` del mes indicado, recortado a los días que tenga ese mes.
     */
    private function occurrenceInMonth(CarbonInterface $month, int $day): CarbonInterface
    {
        return $month->copy()->day(min($day, $month->daysInMonth));
    }
}
