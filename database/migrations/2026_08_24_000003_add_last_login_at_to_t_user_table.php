<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('t_user', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('t_user', function (Blueprint $table) {
            $table->dropColumn('last_login_at');
        });
    }
};
