<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Marca de administrador. El dueño de la instancia (primer usuario) es admin y
 * es quien puede invitar a los demás.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_admin')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('is_admin')->default(false)->after('email_verified_at');
            });
        }

        // El primer usuario (creado por el seeder) pasa a ser administrador.
        $firstId = DB::table('users')->min('id');

        if ($firstId !== null) {
            DB::table('users')->where('id', $firstId)->update(['is_admin' => true]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'is_admin')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('is_admin');
            });
        }
    }
};
