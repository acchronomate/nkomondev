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
        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->integer('available_quantity');
            $table->decimal('price_override', 10, 2)->nullable(); // Prix spécial pour cette date
            $table->boolean('is_blocked')->default(false);
            $table->timestamp('updated_at');

            $table->unique(['room_id', 'date']);
            $table->index(['date', 'available_quantity']);
            $table->index('is_blocked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('availabilities');
    }
};
