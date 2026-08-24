<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->unsignedInteger('max_storage_mb')->nullable()->after('max_users');
        });
    }

    public function down()
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn('max_storage_mb');
        });
    }
};
