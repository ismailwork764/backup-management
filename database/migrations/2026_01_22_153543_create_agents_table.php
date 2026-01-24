<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')
                  ->constrained('clients')
                  ->cascadeOnDelete();
            $table->string('hostname', 255);
            $table->char('api_token', 64)->unique();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_backup_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('client_id');
            $table->index('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
