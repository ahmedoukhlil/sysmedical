<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('rendezvous', function (Blueprint $table) {
            $table->timestamp('date_dernier_rappel')->nullable();
        });
    }

    public function down()
    {
        Schema::table('rendezvous', function (Blueprint $table) {
            $table->dropColumn('date_dernier_rappel');
        });
    }
};
