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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accommodation_id')->constrained()->onDelete('cascade');
            $table->json('name'); // {fr: "Chambre Double", en: "Double Room"}
            $table->json('description'); // Multilingue
            $table->enum('room_type', ['single', 'double', 'twin', 'triple', 'suite', 'family', 'dormitory']);
            $table->integer('capacity_adults');
            $table->integer('capacity_children')->default(0);
            $table->decimal('base_price_per_night', 10, 2);
            $table->integer('size_sqm')->nullable();
            $table->string('bed_type')->nullable(); // "1 lit double", "2 lits simples"
            $table->json('amenities')->nullable(); // ["tv", "air_conditioning", "minibar"]
            $table->integer('total_quantity')->default(1);
            $table->timestamps();

            $table->index(['accommodation_id', 'room_type']);
            $table->index('base_price_per_night');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
