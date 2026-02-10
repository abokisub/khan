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
        Schema::create('habukhan_key', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('account_number', 20);
            $table->string('account_name', 100);
            $table->string('bank_name', 100);
            $table->decimal('min', 10, 2)->default(0.00);
            $table->decimal('max', 10, 2)->default(0.00);
            $table->integer('default_limit')->default(0);
            $table->string('mon_app_key', 255)->nullable();
            $table->string('mon_sk_key', 255)->nullable();
            $table->string('mon_con_num', 255)->nullable();
            $table->string('mon_bvn', 255)->nullable();
            $table->string('psk', 255)->nullable();
            $table->string('psk_bvn', 255)->nullable();
            $table->boolean('plive')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('habukhan_key');
    }
};
