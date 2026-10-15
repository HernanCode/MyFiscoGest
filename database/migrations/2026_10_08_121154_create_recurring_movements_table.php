<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plantillas de movimientos que se repiten cada mes (sección 8 de docs/spec.md).
 *
 * Cada plantilla no es un gasto: es la receta para generarlos. El importe, los
 * impuestos y la categoría viven aquí; los movimientos reales se crean desde
 * la pantalla de recurrentes (botón "Generar ahora").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_movements', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['income', 'expense']);
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();

            $table->string('concept');
            $table->string('counterparty')->nullable();

            // Importes (mismos tipos que movements: nada de floats para dinero).
            $table->decimal('base_amount', 12, 2);
            $table->decimal('vat_rate', 5, 2)->default(21);
            $table->decimal('irpf_rate', 5, 2)->default(0);
            $table->unsignedTinyInteger('deductible_pct')->default(100);

            // Calendario: se repite cada mes en `day_of_month`, empezando en
            // `start_date` y sin generar más allá de `ends_on` (si existe).
            $table->date('start_date');
            $table->unsignedTinyInteger('day_of_month');     // 1..31
            $table->date('ends_on')->nullable();

            // Próxima ocurrencia pendiente de generar; avanza tras cada generación.
            $table->date('next_date');
            $table->boolean('active')->default(true);

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_movements');
    }
};
