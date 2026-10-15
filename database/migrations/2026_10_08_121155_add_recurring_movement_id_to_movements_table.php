<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movements', function (Blueprint $table) {
            // nullOnDelete: al borrar la plantilla, los movimientos ya generados
            // se quedan (son gastos reales con su factura) y dejan de estar ligados.
            $table->foreignId('recurring_movement_id')->nullable()->after('category_id')
                ->constrained()->nullOnDelete();

            // Red de seguridad contra generaciones duplicadas (p. ej. doble clic).
            // En SQLite y MySQL las filas con NULL no colisionan, así que los
            // movimientos manuales no se ven afectados.
            $table->unique(['recurring_movement_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('movements', function (Blueprint $table) {
            $table->dropUnique(['recurring_movement_id', 'date']);
            $table->dropConstrainedForeignId('recurring_movement_id');
        });
    }
};
