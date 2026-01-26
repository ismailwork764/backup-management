<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('storage_servers', function (Blueprint $table) {
            $table->string('password')->nullable()->after('api_token');
        });
    }

    public function down()
    {
        Schema::table('storage_servers', function (Blueprint $table) {
            $table->dropColumn('password');
        });
    }
};
