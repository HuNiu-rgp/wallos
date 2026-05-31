<?php

use App\Models\SystemSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('system_settings') || ! Schema::hasTable('user_notification_settings')) {
            return;
        }

        $keys = [
            'smtp_enabled',
            'smtp_host',
            'smtp_port',
            'smtp_username',
            'smtp_password',
            'smtp_encryption',
            'smtp_from_address',
            'smtp_from_name',
            'telegram_enabled',
            'telegram_bot_token',
            'telegram_bot_name',
        ];
        $adminId = DB::table('users')->where('role', 'admin')->value('id');

        foreach ($keys as $key) {
            $existing = DB::table('system_settings')->where('key', $key)->value('value');
            $userValue = $adminId
                ? DB::table('user_notification_settings')->where('user_id', $adminId)->where('key', $key)->value('value')
                : null;
            $value = $existing ?? $userValue ?? SystemSetting::defaults()[$key];

            DB::table('system_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now(), 'created_at' => now()],
            );
        }

        DB::table('user_notification_settings')->whereIn('key', $keys)->delete();
    }

    public function down(): void
    {
        //
    }
};
