<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public static function defaults(): array
    {
        return [
            'site_name' => 'Wallos',
            'site_logo_url' => '',
            'registration_enabled' => '1',
            'default_currency' => 'CNY',
            'default_notification_days' => '3',
            'timezone' => 'Asia/Shanghai',
        ];
    }

    public static function values(): array
    {
        if (! Schema::hasTable('system_settings')) {
            return static::defaults();
        }

        return [
            ...static::defaults(),
            ...static::query()->pluck('value', 'key')->all(),
        ];
    }

    public static function value(string $key): ?string
    {
        return static::values()[$key] ?? null;
    }

    public static function enabled(string $key): bool
    {
        return static::value($key) === '1';
    }

    public static function smtpScheme(?string $encryption): ?string
    {
        return match ($encryption) {
            'ssl' => 'smtps',
            'tls' => 'smtp',
            default => null,
        };
    }
}
