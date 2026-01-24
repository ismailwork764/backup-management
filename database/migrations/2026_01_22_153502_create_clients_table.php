<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('storage_server_id')->constrained();
            $table->string('registration_key')->unique();

            $table->string('hetzner_subaccount_id')->nullable();
            $table->string('hetzner_username')->nullable();
            $table->string('hetzner_password')->nullable();
            $table->integer('quota_gb')->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
