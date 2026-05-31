<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class UserNotificationSetting extends Model
{
    protected $fillable = [
        'user_id',
        'key',
        'value',
    ];

    public static function defaults(): array
    {
        return [
            'smtp_enabled' => '0',
            'smtp_host' => '',
            'smtp_port' => '587',
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_encryption' => 'tls',
            'smtp_from_address' => '',
            'smtp_from_name' => '',
            'smtp_notification_email' => '',
            'telegram_enabled' => '0',
            'telegram_bot_token' => '',
            'telegram_chat_id' => '',
            'webhook_enabled' => '0',
            'webhook_method' => 'POST',
            'webhook_url' => '',
            'webhook_headers' => '',
            'webhook_payload' => <<<'JSON'
{
  "event": "subscription.reminder",
  "name": "{{subscription_name}}",
  "price": "{{subscription_price}}",
  "currency": "{{subscription_currency}}",
  "category": "{{subscription_category}}",
  "date": "{{subscription_date}}",
  "payer": "{{subscription_payer}}",
  "days": "{{subscription_days_until_payment}}",
  "notes": "{{subscription_notes}}",
  "url": "{{subscription_url}}"
}
JSON,
            'webhook_cancellation_payload' => '',
            'webhook_ignore_ssl_errors' => '0',
            'webhook_secret' => '',
        ];
    }

    public static function valuesFor(User $user): array
    {
        if (! Schema::hasTable('user_notification_settings')) {
            return static::defaults();
        }

        $settings = $user->relationLoaded('notificationSettings')
            ? $user->notificationSettings->pluck('value', 'key')->all()
            : static::query()->where('user_id', $user->id)->pluck('value', 'key')->all();

        return [
            ...static::defaults(),
            ...$settings,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
