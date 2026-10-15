<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un modelo con dueño: se filtra por usuario y, al crearse, hereda el usuario
 * autenticado si no se le indica otro (los servicios y las relaciones anidadas
 * lo aprovechan).
 */
trait BelongsToUser
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, ?int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    protected static function bootBelongsToUser(): void
    {
        static::saving(function ($model): void {
            if (! $model->exists && $model->user_id === null && auth()->check()) {
                $model->user_id = auth()->id();
            }
        });
    }
}
