<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('monthly_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')
                  ->constrained('clients')
                  ->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month'); // 1-12
            $table->decimal('max_used_gb', 10, 2);
            $table->timestamps();

            $table->unique(['client_id', 'year', 'month']);
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_usage');
    }
};
