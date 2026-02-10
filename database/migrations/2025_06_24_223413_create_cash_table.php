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
        Schema::create('cash', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username', 20);
            $table->string('transid', 50)->unique();
            $table->string('sender_number', 11);
            $table->decimal('amount', 10, 2);
            $table->decimal('amount_credit', 10, 2)->default(0.00);
            $table->decimal('discount', 10, 2)->default(0.00);
            $table->decimal('oldbal', 10, 2)->default(0.00);
            $table->decimal('newbal', 10, 2);
            $table->enum('plan_status', ['0', '1', '2'])->default(0);
            $table->timestamp('date');
            $table->timestamp('plan_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cash');
    }
};
