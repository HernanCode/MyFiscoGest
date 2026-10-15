<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use App\Services\AmountCalculator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Plantilla de un movimiento que se repite cada mes (sección 8 de docs/spec.md).
 *
 * No es un gasto en sí: guarda la receta (concepto, importe, impuestos, día del
 * mes) y el puntero `next_date` a la próxima ocurrencia pendiente de generar.
 * Los movimientos reales se crean con \App\Services\RecurringMovementGenerator.
 */
class RecurringMovement extends Model
{
    use BelongsToUser, HasFactory;

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'ends_on' => 'date',
        'next_date' => 'date',
        'active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }

    /**
     * Importe total de cada ocurrencia (base + IVA − retención), mismo
     * cálculo que aplica el hook de Movement al guardar.
     */
    public function getTotalAmountAttribute(): float
    {
        return AmountCalculator::totals($this->base_amount, $this->vat_rate, $this->irpf_rate)['total'];
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * ¿Quedan ocurrencias pendientes de generar antes de hoy?
     */
    public function hasDueOccurrences(): bool
    {
        return $this->active
            && $this->next_date !== null
            && $this->next_date->lte(today())
            && ($this->ends_on === null || $this->next_date->lte($this->ends_on));
    }

    /**
     * La recurrencia tiene fecha de fin y esa fecha ya se superó.
     */
    public function isFinished(): bool
    {
        return $this->ends_on !== null
            && $this->next_date !== null
            && $this->next_date->gt($this->ends_on);
    }
}
