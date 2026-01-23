<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications_log', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);
            $table->unsignedBigInteger('reference_id');
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamps();

            $table->index(['type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications_log');
    }
};
