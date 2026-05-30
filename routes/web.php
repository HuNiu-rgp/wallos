<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\UserController;
use App\Models\SystemSetting;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register') && SystemSetting::enabled('registration_enabled'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/calendar', CalendarController::class)->middleware('auth')->name('calendar');

Route::middleware('auth')->group(function () {
    Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('/subscriptions/import', [SubscriptionController::class, 'import'])->name('subscriptions.import');
    Route::get('/subscriptions/export', [SubscriptionController::class, 'export'])->name('subscriptions.export');
    Route::resource('subscriptions', SubscriptionController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/settings', [SystemSettingController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings', [SystemSettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/test-email', [SystemSettingController::class, 'testEmail'])->name('settings.test-email');
    Route::post('/settings/test-telegram', [SystemSettingController::class, 'testTelegram'])->name('settings.test-telegram');
    Route::post('/settings/test-webhook', [SystemSettingController::class, 'testWebhook'])->name('settings.test-webhook');

    Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
});

require __DIR__.'/auth.php';
