<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entrada del registro de auditoría de acciones del administrador sobre las
 * cuentas. Es de solo escritura (append-only): no tiene `updated_at`.
 *
 * Si se elimina el usuario afectado, `target_user_id` pasa a `null` (clave
 * foránea `nullOnDelete`) pero se conserva `target_label` con el correo.
 */
class AdminAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    /**
     * Deja constancia de una acción del administrador sobre una cuenta.
     *
     * @param  array<string, mixed>  $meta
     */
    public static function record(User $actor, ?User $target, string $action, array $meta = []): self
    {
        return static::create([
            'actor_id' => $actor->id,
            'target_user_id' => $target?->id,
            'target_label' => $target?->email,
            'action' => $action,
            'meta' => $meta === [] ? null : $meta,
            'ip' => request()->ip(),
        ]);
    }
}
