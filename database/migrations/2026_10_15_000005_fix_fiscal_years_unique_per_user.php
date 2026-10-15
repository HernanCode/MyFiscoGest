<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Con el multiusuario, dos usuarios distintos deben poder tener el mismo año
 * fiscal. El índice único original era de una sola columna (`year`), pensado
 * para cuando la app era de un único usuario; pasa a ser compuesto
 * (`year`, `user_id`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fiscal_years', function (Blueprint $table): void {
            $table->dropUnique('fiscal_years_year_unique');
        });

        Schema::table('fiscal_years', function (Blueprint $table): void {
            $table->unique(['year', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('fiscal_years', function (Blueprint $table): void {
            $table->dropUnique(['year', 'user_id']);
        });

        Schema::table('fiscal_years', function (Blueprint $table): void {
            $table->unique('year');
        });
    }
};
