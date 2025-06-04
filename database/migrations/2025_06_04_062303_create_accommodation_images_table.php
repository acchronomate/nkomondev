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
        Schema::create('accommodation_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accommodation_id')->constrained()->onDelete('cascade');
            $table->string('path');
            $table->json('caption')->nullable(); // {fr: "", en: ""}
            $table->boolean('is_primary')->default(false);
            $table->integer('order')->default(0);
            $table->timestamp('created_at');

            $table->index(['accommodation_id', 'is_primary']);
            $table->index(['accommodation_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accommodation_images');
    }
};
