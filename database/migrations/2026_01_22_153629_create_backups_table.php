<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')
                  ->constrained('agents')
                  ->cascadeOnDelete();
            $table->enum('status', ['success', 'failed']);
            $table->text('message')->nullable();
            $table->decimal('size_gb', 10, 2)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('agent_id');
            $table->index('created_at');
            $table->index(['agent_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
