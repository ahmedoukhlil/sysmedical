<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('patient_otp_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('telephone', 30)->index();
            $table->unsignedInteger('fkidcabinet')->index();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('patient_otp_attempts');
    }
};
