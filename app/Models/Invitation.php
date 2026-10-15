<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Invitación para crear una cuenta. El dueño genera el enlace y se lo pasa a
 * quien quiera; no depende del correo saliente (que puede no estar configurado).
 */
class Invitation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public static function newToken(): string
    {
        return bin2hex(random_bytes(24));
    }

    /**
     * Una invitación sirve mientras no se haya usado y no haya caducado.
     */
    public function isValid(): bool
    {
        return $this->accepted_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
