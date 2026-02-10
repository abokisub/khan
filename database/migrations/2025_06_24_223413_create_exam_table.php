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
        Schema::create('exam', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username', 20);
            $table->string('transid', 50)->unique();
            $table->string('exam_type', 50);
            $table->string('exam_name', 100)->nullable();
            $table->string('exam_number', 20);
            $table->integer('quantity')->default(1);
            $table->decimal('amount', 10, 2);
            $table->decimal('discount', 10, 2)->default(0.00);
            $table->decimal('oldbal', 10, 2)->default(0.00);
            $table->decimal('newbal', 10, 2);
            $table->enum('plan_status', ['0', '1', '2'])->default(0);
            $table->timestamp('date');
            $table->timestamp('plan_date')->nullable();
            $table->text('result')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('exam');
    }
};
