<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('habukhan_api', function (Blueprint $table) {
            $table->id();
            $table->string('habukhan1_username')->nullable();
            $table->string('habukhan1_password')->nullable();
            $table->string('habukhan2_username')->nullable();
            $table->string('habukhan2_password')->nullable();
            $table->string('habukhan3_username')->nullable();
            $table->string('habukhan3_password')->nullable();
            $table->string('habukhan4_username')->nullable();
            $table->string('habukhan4_password')->nullable();
            $table->string('habukhan5_username')->nullable();
            $table->string('habukhan5_password')->nullable();
        });

        // Initial seed
        DB::table('habukhan_api')->insert(['id' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('habukhan_api');
    }
};
