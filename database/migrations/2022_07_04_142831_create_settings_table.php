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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->integer('is_verify_email')->default(0);
            $table->integer('is_feature')->default(0);
            $table->integer('flutterwave')->default(0);
            $table->integer('monnify_atm')->default(0);
            $table->integer('monnify')->default(1);
            $table->integer('wema')->default(0);
            $table->integer('fed')->default(0);
            $table->integer('str')->default(0);
            $table->integer('earning')->default(0);
            $table->integer('referral')->default(0);
            $table->integer('kolomoni_mfb')->default(0);
            $table->integer('bulksms')->default(1);
            $table->integer('allow_pin')->default(1);
            $table->integer('bill')->default(1);
            $table->integer('bank_transfer')->default(1);
            $table->integer('paystack')->default(0);
            $table->integer('allow_limit')->default(0);
            $table->integer('stock')->default(0);
            $table->integer('card_ngn_lock')->default(0);
            $table->integer('card_usd_lock')->default(0);

            // Charges & Fees
            $table->decimal('monnify_charge', 10, 2)->default(0.00);
            $table->decimal('xixapay_charge', 10, 2)->default(0.00);
            $table->decimal('paystack_charge', 10, 2)->default(0.00);
            $table->string('transfer_charge_type', 20)->default('FLAT');
            $table->decimal('transfer_charge_value', 10, 2)->default(0.00);
            $table->decimal('transfer_charge_cap', 10, 2)->default(0.00);

            // Controls
            $table->integer('transfer_lock_all')->default(0);
            $table->string('primary_transfer_provider', 50)->nullable();
            $table->string('default_virtual_account', 20)->default('palmpay');
            $table->boolean('palmpay_enabled')->default(true);
            $table->boolean('monnify_enabled')->default(true);
            $table->boolean('wema_enabled')->default(true);
            $table->boolean('xixapay_enabled')->default(true);

            // New Columns (Technical & Branding)
            $table->decimal('referral_price', 10, 2)->default(1.00);
            $table->string('version', 20)->default('1.0.0');
            $table->string('update_url', 255)->nullable();
            $table->string('playstore_url', 255)->nullable();
            $table->string('appstore_url', 255)->nullable();
            $table->string('app_update_title', 255)->nullable();
            $table->text('app_update_desc')->nullable();
            $table->boolean('maintenance')->default(false);
            $table->text('notif_message')->nullable();
            $table->boolean('notif_show')->default(false);
            $table->text('ads_message')->nullable();
            $table->boolean('ads_show')->default(false);
            $table->boolean('app_notif_show')->default(true);

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
        Schema::dropIfExists('settings');
    }
};
