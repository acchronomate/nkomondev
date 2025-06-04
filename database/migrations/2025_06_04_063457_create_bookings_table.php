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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number')->unique(); // Format: BK-2024-000001
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            $table->foreignId('room_id')->constrained()->onDelete('restrict');
            $table->date('check_in');
            $table->date('check_out');
            $table->integer('guests_adults');
            $table->integer('guests_children')->default(0);
            $table->decimal('room_price', 10, 2); // Prix original par nuit
            $table->integer('total_nights');
            $table->decimal('subtotal', 10, 2); // room_price * total_nights
            $table->decimal('commission_rate', 5, 2)->default(5.00); // 5%
            $table->decimal('commission_amount', 10, 2);
            $table->decimal('total_amount', 10, 2); // Montant total pour le client
            $table->foreignId('currency_id')->constrained();
            $table->decimal('exchange_rate_used', 10, 6); // Taux utilisé lors de la réservation
            $table->string('guest_name');
            $table->string('guest_email');
            $table->string('guest_phone');
            $table->text('special_requests')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['room_id', 'check_in', 'check_out']);
            $table->index(['status', 'created_at']);
            $table->index('booking_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
