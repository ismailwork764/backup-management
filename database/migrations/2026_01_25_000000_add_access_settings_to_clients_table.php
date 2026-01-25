<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('reachable_externally')->default(true)->after('hetzner_password');
            $table->boolean('samba_enabled')->default(true)->after('reachable_externally');
            $table->boolean('ssh_enabled')->default(false)->after('samba_enabled');
            $table->boolean('webdav_enabled')->default(false)->after('ssh_enabled');
            $table->boolean('readonly')->default(false)->after('webdav_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['ftp_enabled', 'sftp_enabled', 'scp_enabled', 'webdav_enabled', 'samba_enabled']);
        });
    }
};
