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
        Schema::create('user', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 199);
            $table->string('username', 20)->unique();
            $table->string('email', 255)->unique();
            $table->string('phone', 11)->unique();
            $table->string('password', 255);
            $table->string('apikey', 60)->unique();
            $table->text('app_token')->nullable();
            $table->decimal('bal', 10, 2)->default(0.00);
            $table->decimal('refbal', 10, 2)->default(0.00);
            $table->string('ref', 12)->nullable();
            $table->enum('type', ['SMART', 'AGENT', 'AWUF', 'API', 'ADMIN'])->default('SMART');
            $table->timestamp('date');
            $table->boolean('kyc')->default(false);
            $table->boolean('status')->default(false);
            $table->integer('user_limit')->default(0);
            $table->string('pin', 4)->nullable();
            $table->string('profile_image', 255)->nullable();
            $table->string('sterlen', 255)->nullable();
            $table->string('vdf', 255)->nullable();
            $table->string('fed', 255)->nullable();
            $table->string('wema', 255)->nullable();
            $table->string('kolomoni_mfb', 255)->nullable();
            $table->text('address')->nullable();
            $table->string('webhook', 255)->nullable();
            $table->text('about')->nullable();
            $table->string('bvn', 11)->nullable();
            $table->string('monify_ref', 255)->nullable();
            $table->string('palmpay', 255)->nullable();
            $table->string('habukhan_key', 255)->nullable();
            $table->string('app_key', 255)->nullable();
            $table->string('paystack_account', 255)->nullable();
            $table->string('paystack_bank', 255)->nullable();
            $table->text('opay')->nullable();
            $table->text('dob')->nullable();
            $table->text('nin')->nullable();
            $table->text('occupation')->nullable();
            $table->text('marital_status')->nullable();
            $table->text('religion')->nullable();
            $table->text('city')->nullable();
            $table->text('state')->nullable();
            $table->text('reason')->nullable();
            $table->text('id_card_path')->nullable();
            $table->text('utility_bill_path')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user');
    }
};
