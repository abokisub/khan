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
            if (!Schema::hasColumn('settings', 'ads_message')) {
                $table->text('ads_message')->nullable();
            }
            if (!Schema::hasColumn('settings', 'ads_show')) {
                $table->boolean('ads_show')->default(0);
            }
            if (!Schema::hasColumn('settings', 'app_notif_show')) {
                $table->boolean('app_notif_show')->default(1);
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
            $table->dropColumn(['ads_message', 'ads_show', 'app_notif_show']);
        });
    }
};
