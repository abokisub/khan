<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sel', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique();
            $table->boolean('data')->default(0);
            $table->boolean('airtime')->default(0);
            $table->boolean('result')->default(0);
            $table->boolean('bill')->default(0);
            $table->boolean('bulksms')->default(0);
            $table->boolean('cable')->default(0);
            $table->boolean('data_card')->default(0);
            $table->boolean('recharge_card')->default(0);
            $table->boolean('cash')->default(0);
            $table->boolean('bank_transfer')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sel');
    }
};
