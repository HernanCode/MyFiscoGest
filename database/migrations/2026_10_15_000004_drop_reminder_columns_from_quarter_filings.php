<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Se retiraron los recordatorios por correo, así que sus columnas sobran.
 *
 * En instalaciones nuevas la tabla ya se crea sin ellas (hemos editado la
 * migración original); esto limpia las que ya existían en bases de datos
 * anteriores sin tocar el resto de la información.
 */
return new class extends Migration
{
    public function up(): void
    {
        $columns = array_values(array_filter(
            ['remind_days_before', 'reminded_at'],
            fn (string $column) => Schema::hasColumn('quarter_filings', $column),
        ));

        if ($columns !== []) {
            Schema::table('quarter_filings', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('quarter_filings', 'remind_days_before')) {
            Schema::table('quarter_filings', function (Blueprint $table): void {
                $table->unsignedTinyInteger('remind_days_before')->default(7);
            });
        }

        if (! Schema::hasColumn('quarter_filings', 'reminded_at')) {
            Schema::table('quarter_filings', function (Blueprint $table): void {
                $table->timestamp('reminded_at')->nullable();
            });
        }
    }
};
