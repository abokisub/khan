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
        Schema::create('message', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username', 12);
            $table->string('transid', 50)->unique();
            $table->text('message');
            $table->decimal('amount', 15, 2)->default(0.00);
            $table->decimal('oldbal', 15, 2)->default(0.00);
            $table->decimal('newbal', 15, 2)->default(0.00);
            $table->string('role')->nullable();
            $table->enum('plan_status', ['0', '1', '2'])->default(0);
            $table->timestamp('habukhan_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('message');
    }
};
