<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations - Add missing columns to existing tables WITHOUT deleting data
     *
     * @return void
     */
    public function up()
    {
        // Add missing columns to general table if they don't exist
        Schema::table('general', function (Blueprint $table) {
            if (!Schema::hasColumn('general', 'facebook')) {
                $table->string('facebook', 255)->nullable();
            }
            if (!Schema::hasColumn('general', 'instagram')) {
                $table->string('instagram', 255)->nullable();
            }
            if (!Schema::hasColumn('general', 'tiktok')) {
                $table->string('tiktok', 255)->nullable();
            }
            if (!Schema::hasColumn('general', 'play_store_url')) {
                $table->string('play_store_url', 255)->nullable();
            }
            if (!Schema::hasColumn('general', 'app_store_url')) {
                $table->string('app_store_url', 255)->nullable();
            }
        });

        // Add missing columns to settings table if they don't exist
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'app_notif_show')) {
                $table->boolean('app_notif_show')->default(true);
            }
            if (!Schema::hasColumn('settings', 'notif_message')) {
                $table->text('notif_message')->nullable();
            }
            if (!Schema::hasColumn('settings', 'notif_show')) {
                $table->boolean('notif_show')->default(true);
            }
            if (!Schema::hasColumn('settings', 'ads_show')) {
                $table->boolean('ads_show')->default(true);
            }
            if (!Schema::hasColumn('settings', 'ads_message')) {
                $table->text('ads_message')->nullable();
            }
            if (!Schema::hasColumn('settings', 'referral_price')) {
                $table->decimal('referral_price', 10, 2)->default(100.00);
            }
        });

        // Add missing columns to habukhan_key table if they don't exist
        Schema::table('habukhan_key', function (Blueprint $table) {
            if (!Schema::hasColumn('habukhan_key', 'mon_app_key')) {
                $table->string('mon_app_key', 255)->nullable();
            }
            if (!Schema::hasColumn('habukhan_key', 'mon_sk_key')) {
                $table->string('mon_sk_key', 255)->nullable();
            }
            if (!Schema::hasColumn('habukhan_key', 'mon_con_num')) {
                $table->string('mon_con_num', 255)->nullable();
            }
            if (!Schema::hasColumn('habukhan_key', 'mon_bvn')) {
                $table->string('mon_bvn', 255)->nullable();
            }
            if (!Schema::hasColumn('habukhan_key', 'psk')) {
                $table->string('psk', 255)->nullable();
            }
            if (!Schema::hasColumn('habukhan_key', 'psk_bvn')) {
                $table->string('psk_bvn', 255)->nullable();
            }
            if (!Schema::hasColumn('habukhan_key', 'plive')) {
                $table->boolean('plive')->default(false);
            }
        });

        // Add missing columns to user table if they don't exist
        Schema::table('user', function (Blueprint $table) {
            if (!Schema::hasColumn('user', 'otp')) {
                $table->string('otp', 6)->nullable();
            }
        });

        // Add missing columns to transaction tables
        $transactionTables = ['airtime', 'cable', 'bill', 'exam', 'bulksms', 'cash'];

        foreach ($transactionTables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'plan_date')) {
                        $table->timestamp('plan_date')->nullable();
                    }
                    if (!Schema::hasColumn($tableName, 'oldbal')) {
                        $table->decimal('oldbal', 10, 2)->default(0.00);
                    }
                });
            }
        }

        // Add specific columns to specific tables
        if (Schema::hasTable('airtime')) {
            Schema::table('airtime', function (Blueprint $table) {
                if (!Schema::hasColumn('airtime', 'network_type')) {
                    $table->string('network_type', 50)->nullable();
                }
            });
        }

        if (Schema::hasTable('cable')) {
            Schema::table('cable', function (Blueprint $table) {
                if (!Schema::hasColumn('cable', 'charges')) {
                    $table->decimal('charges', 10, 2)->default(0.00);
                }
            });
        }

        if (Schema::hasTable('exam')) {
            Schema::table('exam', function (Blueprint $table) {
                if (!Schema::hasColumn('exam', 'exam_name')) {
                    $table->string('exam_name', 100)->nullable();
                }
                if (!Schema::hasColumn('exam', 'quantity')) {
                    $table->integer('quantity')->default(1);
                }
            });
        }

        if (Schema::hasTable('cash')) {
            Schema::table('cash', function (Blueprint $table) {
                if (!Schema::hasColumn('cash', 'amount_credit')) {
                    $table->decimal('amount_credit', 10, 2)->default(0.00);
                }
            });
        }

        if (Schema::hasTable('deposit')) {
            Schema::table('deposit', function (Blueprint $table) {
                if (!Schema::hasColumn('deposit', 'credit_by')) {
                    $table->string('credit_by', 100)->nullable();
                }
                if (!Schema::hasColumn('deposit', 'oldbal')) {
                    $table->decimal('oldbal', 10, 2)->default(0.00);
                }
                if (!Schema::hasColumn('deposit', 'newbal')) {
                    $table->decimal('newbal', 10, 2)->default(0.00);
                }
                if (!Schema::hasColumn('deposit', 'wallet_type')) {
                    $table->string('wallet_type', 50)->nullable();
                }
                if (!Schema::hasColumn('deposit', 'type')) {
                    $table->string('type', 50)->nullable();
                }
                if (!Schema::hasColumn('deposit', 'charges')) {
                    $table->decimal('charges', 10, 2)->default(0.00);
                }
                if (!Schema::hasColumn('deposit', 'monify_ref')) {
                    $table->string('monify_ref', 255)->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // We won't remove columns in down() to preserve data
        // If you need to rollback, do it manually
    }
};
