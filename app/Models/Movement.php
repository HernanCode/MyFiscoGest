<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use App\Services\AmountCalculator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movement extends Model
{
    use BelongsToUser, HasFactory;

    protected $guarded = [];

    protected $casts = ['date' => 'date', 'paid_at' => 'date'];

    protected static function booted(): void
    {
        static::saving(function (Movement $movement) {
            // El usuario se hereda del contexto (o se indica explícitamente).
            if ($movement->user_id === null) {
                $movement->user_id = auth()->id();
            }

            // Trimestre y año fiscal (de este mismo usuario) desde la fecha.
            $movement->quarter = (int) ceil($movement->date->month / 3);
            $movement->fiscal_year_id = FiscalYear::firstOrCreate([
                'year' => $movement->date->year,
                'user_id' => $movement->user_id,
            ])->id;

            // Importes calculados.
            $amounts = AmountCalculator::totals($movement->base_amount, $movement->vat_rate, $movement->irpf_rate);
            $movement->vat_amount = $amounts['vat'];
            $movement->irpf_amount = $amounts['irpf'];
            $movement->total_amount = $amounts['total'];
        });
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Plantilla que generó este movimiento (null si se creó a mano).
     */
    public function recurringMovement(): BelongsTo
    {
        return $this->belongsTo(RecurringMovement::class);
    }

    /**
     * Nombres de cliente/proveedor ya usados por el usuario, agrupados por tipo,
     * para el autocompletado del formulario de movimiento.
     *
     * @return array{income: array<int, string>, expense: array<int, string>}
     */
    public static function counterpartySuggestions(int $userId): array
    {
        $group = fn (string $type) => static::query()
            ->forUser($userId)
            ->where('type', $type)
            ->whereNotNull('counterparty')
            ->where('counterparty', '!=', '')
            ->distinct()
            ->orderBy('counterparty')
            ->limit(50)
            ->pluck('counterparty')
            ->values()
            ->all();

        return ['income' => $group('income'), 'expense' => $group('expense')];
    }

    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }
}
