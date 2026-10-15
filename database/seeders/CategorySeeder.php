<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Categorías de gasto habituales de un freelance de software, con su % deducible
     * por defecto (ajustable después en cada movimiento).
     *
     * La cuota de autónomos y el resto de gastos son 100 % deducibles.
     * `Suministros` (luz, agua, gas de la vivienda habitual) usa el 30 % típico
     * de la estimación directa simplificada cuando se trabaja desde casa.
     */
    public function run(): void
    {
        $gastos = [
            ['name' => 'Hosting', 'deductible' => 100],
            ['name' => 'Software y suscripciones', 'deductible' => 100],
            ['name' => 'Hardware', 'deductible' => 100],
            ['name' => 'Cuota de autónomos', 'deductible' => 100],
            ['name' => 'Formación', 'deductible' => 100],
            ['name' => 'Asesoría', 'deductible' => 100],
            ['name' => 'Suministros', 'deductible' => 30],
            ['name' => 'Transporte', 'deductible' => 100],
            ['name' => 'Comisiones bancarias', 'deductible' => 100],
            ['name' => 'Otros', 'deductible' => 100],
        ];

        $ingresos = [
            ['name' => 'Servicios', 'deductible' => 100],
            ['name' => 'Otros', 'deductible' => 100],
        ];

        foreach ($gastos as $categoria) {
            Category::updateOrCreate(
                ['name' => $categoria['name'], 'type' => 'expense'],
                ['default_deductible_pct' => $categoria['deductible']],
            );
        }

        foreach ($ingresos as $categoria) {
            Category::updateOrCreate(
                ['name' => $categoria['name'], 'type' => 'income'],
                ['default_deductible_pct' => $categoria['deductible']],
            );
        }
    }
}
