<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nota: los recordatorios de vencimientos por correo se retiraron por ahora.
// Se volverán a añadir más adelante (comando + Schedule::command(...)).
