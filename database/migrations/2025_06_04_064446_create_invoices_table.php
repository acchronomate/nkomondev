<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique(); // Format: INV-2024-01-0001
            $table->foreignId('user_id')->constrained()->onDelete('restrict'); // hébergeur
            $table->integer('month');
            $table->integer('year');
            $table->integer('total_bookings');
            $table->decimal('total_revenue', 12, 2); // Revenus totaux de l'hébergeur
            $table->decimal('commission_rate', 5, 2)->default(5.00);
            $table->decimal('commission_amount', 10, 2); // Montant commission à payer
            $table->foreignId('currency_id')->constrained();
            $table->decimal('exchange_rate_used', 10, 6);
            $table->enum('status', ['draft', 'sent', 'paid'])->default('draft');
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['month', 'year']);
            $table->index('invoice_number');
            $table->unique(['user_id', 'month', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
