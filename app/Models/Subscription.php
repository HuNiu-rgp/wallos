<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'logo_path',
        'amount_cents',
        'currency',
        'billing_interval',
        'billing_cycle',
        'start_on',
        'next_due_on',
        'last_charged_on',
        'payment_method',
        'payer_name',
        'auto_renew',
        'reminder_days_before',
        'notification_enabled',
        'notification_days_before',
        'cancellation_notice_on',
        'link_url',
        'is_active',
        'notes',
    ];

    protected $appends = [
        'logo_input',
        'logo_url',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'billing_interval' => 'integer',
            'start_on' => 'date:Y-m-d',
            'next_due_on' => 'date:Y-m-d',
            'last_charged_on' => 'date:Y-m-d',
            'auto_renew' => 'boolean',
            'reminder_days_before' => 'integer',
            'notification_enabled' => 'boolean',
            'notification_days_before' => 'integer',
            'cancellation_notice_on' => 'date:Y-m-d',
            'is_active' => 'boolean',
        ];
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        if (str_starts_with($this->logo_path, '<svg')) {
            return 'data:image/svg+xml;base64,'.base64_encode($this->logo_path);
        }

        return str_starts_with($this->logo_path, 'http://') || str_starts_with($this->logo_path, 'https://')
            ? $this->logo_path
            : asset('storage/'.$this->logo_path);
    }

    public function getLogoInputAttribute(): ?string
    {
        return $this->logo_path;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
