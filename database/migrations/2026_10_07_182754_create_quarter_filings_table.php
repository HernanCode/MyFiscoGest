<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quarter_filings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('quarter');
            $table->enum('model', ['303', '130']);
            $table->date('due_date');
            $table->enum('status', ['pending', 'filed'])->default('pending');
            $table->date('filed_at')->nullable();
            $table->decimal('amount_paid', 12, 2)->nullable();
            $table->timestamps();

            $table->unique(['fiscal_year_id', 'quarter', 'model']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quarter_filings');
    }
};
