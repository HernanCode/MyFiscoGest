<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Aislamiento por usuario: cada año fiscal (y todo lo que cuelga de él) pasa a
 * pertenecer a un usuario. Las categorías siguen siendo un catálogo compartido.
 *
 * Los datos existentes se asignan al primer usuario (el dueño/administrador).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['fiscal_years', 'movements', 'recurring_movements', 'quarter_filings'] as $table) {
            if (! Schema::hasColumn($table, 'user_id')) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->unsignedBigInteger('user_id')->nullable()->index();
                });
            }
        }

        // Backfill: lo que ya existe pertenece al primer usuario.
        $ownerId = DB::table('users')->min('id');

        if ($ownerId !== null) {
            foreach (['fiscal_years', 'movements', 'recurring_movements', 'quarter_filings'] as $table) {
                DB::table($table)->whereNull('user_id')->update(['user_id' => $ownerId]);
            }
        }
    }

    public function down(): void
    {
        foreach (['fiscal_years', 'movements', 'recurring_movements', 'quarter_filings'] as $table) {
            if (Schema::hasColumn($table, 'user_id')) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->dropColumn('user_id');
                });
            }
        }
    }
};
