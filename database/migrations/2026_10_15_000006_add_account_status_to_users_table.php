<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estado de la cuenta: permite al administrador suspender el acceso de un
 * usuario sin borrar sus datos (útil para respetar la conservación fiscal).
 * `null` significa cuenta activa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('deactivated_at')->nullable()->after('is_admin')->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['deactivated_at']);
            $table->dropColumn('deactivated_at');
        });
    }
};
