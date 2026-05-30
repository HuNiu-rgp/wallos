<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->string('channel');
            $table->date('next_due_on');
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['subscription_id', 'channel', 'next_due_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_notification_deliveries');
    }
};
