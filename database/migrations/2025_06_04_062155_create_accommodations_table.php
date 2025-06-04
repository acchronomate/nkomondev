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
        Schema::create('accommodations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // hébergeur
            $table->enum('type', ['hotel', 'motel', 'appart_hotel', 'village_vacances', 'bungalow', 'maison_hotes']);
            $table->string('name');
            $table->string('slug')->unique();
            $table->json('description'); // {fr: "", en: ""}
            $table->string('address');
            $table->foreignId('country_id')->constrained();
            $table->foreignId('city_id')->constrained();
            $table->foreignId('district_id')->nullable()->constrained();
            $table->string('state')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->json('amenities')->nullable(); // ["wifi", "parking", "pool"]
            $table->time('check_in_time')->default('14:00');
            $table->time('check_out_time')->default('12:00');
            $table->integer('min_stay_days')->default(1);
            $table->integer('max_stay_days')->nullable();
            $table->foreignId('currency_id')->constrained();
            $table->decimal('rating_average', 2, 1)->default(0);
            $table->integer('total_reviews')->default(0);
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['city_id', 'status']);
            $table->index('slug');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accommodations');
    }
};
