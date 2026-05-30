<?php

namespace App\Console\Commands;

use App\Services\SubscriptionNotificationService;
use Illuminate\Console\Command;

class SendSubscriptionNotifications extends Command
{
    protected $signature = 'subscriptions:notify';

    protected $description = 'Send reminders for subscriptions that are nearing their next payment date';

    public function handle(SubscriptionNotificationService $notifications): int
    {
        $result = $notifications->sendDueReminders();

        $this->info("Subscription reminders: {$result['sent']} sent, {$result['failed']} failed, {$result['skipped']} skipped.");

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
