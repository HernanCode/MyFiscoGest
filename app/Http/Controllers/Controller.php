<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;

abstract class Controller
{
    /**
     * Corta con 404 si el registro no pertenece al usuario autenticado. Evita
     * que un usuario abra o modifique datos de otro pasando un ID por la URL.
     */
    protected function authorizeOwner(?Model $model): void
    {
        abort_unless($model !== null && (int) $model->user_id === (int) auth()->id(), 404);
    }
}
