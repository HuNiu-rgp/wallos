<?php

use App\Models\UserNotificationSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'key']);
        });

        if (! Schema::hasTable('system_settings')) {
            return;
        }

        $legacySettings = DB::table('system_settings')
            ->whereIn('key', array_keys(UserNotificationSetting::defaults()))
            ->pluck('value', 'key')
            ->all();

        if ($legacySettings === []) {
            return;
        }

        $now = now();

        foreach (DB::table('users')->pluck('id') as $userId) {
            foreach ($legacySettings as $key => $value) {
                DB::table('user_notification_settings')->insert([
                    'user_id' => $userId,
                    'key' => $key,
                    'value' => $value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_settings');
    }
};
