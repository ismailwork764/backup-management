<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('storage_servers', function (Blueprint $table) {
            // Change enum to string
            $table->string('status', 50)->default('active')->change();
        });
    }

    public function down(): void
    {
        Schema::table('storage_servers', function (Blueprint $table) {
            // Revert back to enum
            $table->enum('status', ['active', 'inactive'])
                  ->default('active')
                  ->change();
        });
    }
};
