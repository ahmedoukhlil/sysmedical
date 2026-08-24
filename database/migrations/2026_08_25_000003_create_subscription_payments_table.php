<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabinet_subscription_id')->constrained('cabinet_subscriptions');
            $table->unsignedInteger('montant');
            $table->string('devise', 10)->default('MRU');
            $table->string('moyen', 50);
            $table->date('date_paiement');
            $table->unsignedInteger('mois_couverts')->default(1);
            $table->text('note')->nullable();
            $table->foreignId('platform_admin_id')->constrained('platform_admins');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('subscription_payments');
    }
};
