<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('quarter');              // 1..4, se calcula desde `date`
            $table->enum('type', ['income', 'expense']);
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();

            $table->date('date');                                // fecha de la factura
            $table->string('concept');
            $table->string('counterparty')->nullable();          // cliente o proveedor
            $table->string('invoice_number')->nullable();

            // Importes
            $table->decimal('base_amount', 12, 2);               // base imponible
            $table->decimal('vat_rate', 5, 2)->default(21);      // % IVA
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('irpf_rate', 5, 2)->default(0);      // % retención (solo ingresos)
            $table->decimal('irpf_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);              // base + IVA - retención

            // Solo gastos
            $table->unsignedTinyInteger('deductible_pct')->default(100);

            // Estado de cobro/pago
            $table->enum('payment_status', ['pending', 'paid'])->default('pending');
            $table->date('paid_at')->nullable();

            // Factura adjunta
            $table->string('invoice_path')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['fiscal_year_id', 'quarter', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movements');
    }
};
