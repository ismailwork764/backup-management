<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('storage_servers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('server_address')->nullable();
            $table->string('region', 50);
            $table->text('api_token');
            $table->unsignedInteger('total_capacity_gb')->nullable();
            $table->unsignedInteger('used_capacity_gb')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_servers');
    }
};
