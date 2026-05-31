<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::query()->firstOrNew([
            'email' => 'admin@qq.com',
        ]);

        $admin->name = $admin->name ?: 'Admin';
        $admin->role = 'admin';
        $admin->email_verified_at = $admin->email_verified_at ?: now();

        if (! $admin->exists) {
            $admin->password = Hash::make('123456');
        }

        $admin->save();

        foreach (SystemSetting::defaults() as $key => $value) {
            SystemSetting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
