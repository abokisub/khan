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
        Schema::create('deposit', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username', 20);
            $table->decimal('amount', 10, 2);
            $table->decimal('oldbal', 10, 2)->default(0.00);
            $table->decimal('newbal', 10, 2)->default(0.00);
            $table->string('transid', 100)->unique();
            $table->string('payment_method', 50)->nullable();
            $table->string('reference', 100)->nullable();
            $table->string('wallet_type', 50)->nullable();
            $table->string('type', 50)->nullable();
            $table->string('credit_by', 100)->nullable();
            $table->decimal('charges', 10, 2)->default(0.00);
            $table->string('monify_ref', 255)->nullable();
            $table->integer('status')->default(0);
            $table->timestamp('date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deposit');
    }
};
