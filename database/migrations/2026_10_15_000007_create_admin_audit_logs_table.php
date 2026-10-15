<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de auditoría de las acciones del administrador sobre las cuentas.
 *
 * Las claves foráneas usan `nullOnDelete` y guardamos `target_label` para que
 * el rastro sobreviva aunque se elimine el usuario afectado. Es de solo
 * escritura (append-only): por eso no tiene `updated_at`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('target_label')->nullable();
            $table->string('action');
            $table->json('meta')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['target_user_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');
    }
};
