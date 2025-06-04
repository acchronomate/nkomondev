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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('booking_id')->constrained()->onDelete('restrict');
            $table->string('description'); // "Réservation #BK-2024-000001 du 15/01 au 18/01"
            $table->decimal('amount', 10, 2); // Montant total de la réservation
            $table->decimal('commission_amount', 10, 2); // Commission sur cette réservation
            $table->timestamp('created_at');

            $table->index('invoice_id');
            $table->index('booking_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
