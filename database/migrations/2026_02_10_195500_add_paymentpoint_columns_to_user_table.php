<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentpointColumnsToUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user', function (Blueprint $table) {
            if (!Schema::hasColumn('user', 'paymentpoint_account_number')) {
                $table->string('paymentpoint_account_number', 255)->nullable();
            }
            if (!Schema::hasColumn('user', 'paymentpoint_account_name')) {
                $table->string('paymentpoint_account_name', 255)->nullable();
            }
            if (!Schema::hasColumn('user', 'paymentpoint_bank_name')) {
                $table->string('paymentpoint_bank_name', 255)->nullable();
            }
            if (!Schema::hasColumn('user', 'paymentpoint_customer_id')) {
                $table->string('paymentpoint_customer_id', 255)->nullable();
            }
            if (!Schema::hasColumn('user', 'paymentpoint_reserved_id')) {
                $table->string('paymentpoint_reserved_id', 255)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user', function (Blueprint $table) {
            $table->dropColumn([
                'paymentpoint_account_number',
                'paymentpoint_account_name',
                'paymentpoint_bank_name',
                'paymentpoint_customer_id',
                'paymentpoint_reserved_id',
            ]);
        });
    }
}
