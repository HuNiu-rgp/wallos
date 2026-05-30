<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionNotificationDelivery extends Model
{
    protected $fillable = [
        'subscription_id',
        'channel',
        'next_due_on',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'next_due_on' => 'date',
            'sent_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
