<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Al borrar la cuenta se eliminan también sus datos, para no dejar
        // registros huérfanos: años, movimientos (con sus facturas), vencimientos
        // y recurrencias.
        static::deleting(function (User $user): void {
            $user->fiscalYears()->with('movements')->get()->each(function (FiscalYear $year): void {
                $year->movements->each(function (Movement $movement): void {
                    if ($movement->invoice_path) {
                        Storage::disk('local')->delete($movement->invoice_path);
                    }
                });

                $year->movements()->delete();
                $year->filings()->delete();
                $year->delete();
            });

            $user->recurringMovements()->delete();

            Invitation::where('invited_by', $user->id)->delete();
        });
    }

    public function fiscalYears(): HasMany
    {
        return $this->hasMany(FiscalYear::class);
    }

    public function recurringMovements(): HasMany
    {
        return $this->hasMany(RecurringMovement::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'invited_by');
    }

    /**
     * El dueño de la instancia: puede invitar a más usuarios.
     */
    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    /**
     * Una cuenta desactivada no puede iniciar sesión, pero conserva sus datos.
     */
    public function isActive(): bool
    {
        return $this->deactivated_at === null;
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deactivated_at');
    }
}
