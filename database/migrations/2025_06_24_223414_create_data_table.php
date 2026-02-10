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
        Schema::create('data', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username', 20);
            $table->string('network_type', 50)->nullable();
            $table->string('network', 50)->nullable();
            $table->string('plan_name', 100)->nullable();
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->integer('plan_status')->default(0);
            $table->string('transid', 60)->unique();
            $table->string('plan_phone', 15)->nullable();
            $table->timestamp('plan_date')->nullable();
            $table->decimal('oldbal', 10, 2)->default(0.00);
            $table->decimal('newbal', 10, 2)->default(0.00);
            $table->string('system', 50)->nullable();
            $table->string('wallet', 50)->nullable();
            $table->text('api_response')->nullable();
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
        Schema::dropIfExists('data');
    }
};
