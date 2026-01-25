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
        Schema::table('storage_servers', function (Blueprint $table) {
            $table->decimal('total_capacity_gb', 10, 2)->nullable()->change();
            $table->decimal('used_capacity_gb', 10, 2)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('storage_servers', function (Blueprint $table) {
            $table->unsignedInteger('total_capacity_gb')->nullable()->change();
            $table->unsignedInteger('used_capacity_gb')->default(0)->change();
        });
    }
};
