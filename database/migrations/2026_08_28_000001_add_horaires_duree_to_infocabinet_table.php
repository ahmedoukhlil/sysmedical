<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('infocabinet', function (Blueprint $table) {
            $table->time('heure_ouverture')->default('08:00:00');
            $table->time('heure_fermeture')->default('18:00:00');
            $table->unsignedSmallInteger('duree_rdv_minutes')->default(10);
        });
    }

    public function down()
    {
        Schema::table('infocabinet', function (Blueprint $table) {
            $table->dropColumn(['heure_ouverture', 'heure_fermeture', 'duree_rdv_minutes']);
        });
    }
};
