<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cabinet_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('idEntete')->unique();
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans');
            $table->string('statut', 20)->default('essai');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('grace_ends_at')->nullable();
            $table->timestamp('current_period_ends_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cabinet_subscriptions');
    }
};
