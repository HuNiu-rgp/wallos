<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public string $reminderMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subscription->name.' 订阅到期提醒',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.subscription-reminder',
        );
    }
}
