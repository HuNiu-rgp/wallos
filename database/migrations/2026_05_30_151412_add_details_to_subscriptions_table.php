<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('name');
            $table->unsignedSmallInteger('billing_interval')->default(1)->after('currency');
            $table->date('start_on')->nullable()->after('billing_cycle');
            $table->string('payment_method')->nullable()->after('last_charged_on');
            $table->string('payer_name')->nullable()->after('payment_method');
            $table->boolean('auto_renew')->default(true)->after('payer_name');
            $table->boolean('notification_enabled')->default(false)->after('auto_create_transactions');
            $table->unsignedSmallInteger('notification_days_before')->nullable()->after('notification_enabled');
            $table->date('cancellation_notice_on')->nullable()->after('notification_days_before');
            $table->string('link_url')->nullable()->after('cancellation_notice_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'logo_path',
                'billing_interval',
                'start_on',
                'payment_method',
                'payer_name',
                'auto_renew',
                'notification_enabled',
                'notification_days_before',
                'cancellation_notice_on',
                'link_url',
            ]);
        });
    }
};
