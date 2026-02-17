<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('notification_email')->nullable()->after('name');
            $table->boolean('daily_backup_notifications_enabled')->default(false)->after('notification_email');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['notification_email', 'daily_backup_notifications_enabled']);
        });
    }
};
