<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVirtualAccountLocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('virtual_account_locks', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // xixapay, monnify, paystack, paymentpoint
            $table->string('account_type'); // palmpay, kolomonie, moniepoint, wema
            $table->boolean('is_locked')->default(false);
            $table->timestamps();

            // Ensure unique combination of provider and account_type
            $table->unique(['provider', 'account_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('virtual_account_locks');
    }
}
