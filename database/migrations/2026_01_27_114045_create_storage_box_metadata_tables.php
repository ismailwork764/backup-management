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
        Schema::create('storage_box_types', function (Blueprint $table) {
            $table->id();
            $table->integer('hetzner_id')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('size_bytes');
            $table->json('prices')->nullable();
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., fsn1
            $table->string('description')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
        Schema::dropIfExists('storage_box_types');
    }
};
