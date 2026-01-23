<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->foreignId('storage_server_id')
                  ->constrained('storage_servers')
                  ->restrictOnDelete();
            $table->string('hetzner_subaccount_id', 100)->nullable();
            $table->string('registration_key', 11)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('storage_server_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
