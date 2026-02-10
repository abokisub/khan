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
        Schema::create('notif', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username', 12);
            $table->text('message');
            $table->string('title', 100);
            $table->boolean('status')->default(false);
            $table->string('image_url')->nullable();
            $table->string('broadcast_id')->nullable();
            $table->timestamp('date');

            $table->index('broadcast_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notif');
    }
};
