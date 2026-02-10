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
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'paymentpoint_charge')) {
                $table->decimal('paymentpoint_charge', 10, 2)->default(50.00)->after('xixapay_charge');
            }
        });

        Schema::table('virtual_account_locks', function (Blueprint $table) {
            if (!Schema::hasColumn('virtual_account_locks', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('is_locked');
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
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('paymentpoint_charge');
        });

        Schema::table('virtual_account_locks', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
