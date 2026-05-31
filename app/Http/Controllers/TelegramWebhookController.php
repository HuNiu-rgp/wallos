<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UserNotificationSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $settings = SystemSetting::values();
        $secret = $settings['telegram_webhook_secret'];

        abort_unless($secret && hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token')), 403);

        $chatId = data_get($request->all(), 'message.chat.id');
        $text = trim((string) data_get($request->all(), 'message.text'));

        if (! $chatId || $text === '') {
            return response()->json(['ok' => true]);
        }

        $command = strtolower(strtok($text, " \n"));

        if (in_array($command, ['/start', '/id'], true)) {
            return $this->reply($settings, $chatId, "你的 Chat ID：{$chatId}\n请在 Wallos 个人资料的通知设置中填写此 Chat ID 完成绑定。\n\n发送 /help 查看可用命令。");
        }

        if ($command === '/help') {
            return $this->reply($settings, $chatId, $this->help());
        }

        $user = $this->userForChatId((string) $chatId);

        if (! $user) {
            return $this->reply($settings, $chatId, "当前 Chat ID 尚未绑定 Wallos 用户。\n发送 /id 获取 Chat ID，然后在个人资料中完成绑定。");
        }

        return match ($command) {
            '/subscriptions', '订阅' => $this->reply($settings, $chatId, $this->subscriptions($user)),
            '/upcoming', '即将到期' => $this->reply($settings, $chatId, $this->subscriptions($user, 30)),
            default => $this->reply($settings, $chatId, $this->help()),
        };
    }

    private function subscriptions(User $user, ?int $days = null): string
    {
        $query = Subscription::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('next_due_on');

        if ($days !== null) {
            $query
                ->whereDate('next_due_on', '>=', today()->toDateString())
                ->whereDate('next_due_on', '<=', today()->addDays($days)->toDateString());
        }

        $subscriptions = $query->limit(30)->get();

        if ($subscriptions->isEmpty()) {
            return $days === null ? '当前没有有效订阅。' : "未来 {$days} 天没有即将到期的订阅。";
        }

        $title = $days === null ? '有效订阅' : "未来 {$days} 天即将到期";
        $lines = [$title.'：'];

        foreach ($subscriptions as $index => $subscription) {
            $lines[] = ($index + 1).'. '.$subscription->name
                .' | '.number_format($subscription->amount_cents / 100, 2).' '.$subscription->currency
                .' | '.$subscription->next_due_on->format('Y-m-d');
        }

        return mb_strimwidth(implode("\n", $lines), 0, 3900, "\n...");
    }

    private function userForChatId(string $chatId): ?User
    {
        $setting = UserNotificationSetting::query()
            ->where('key', 'telegram_chat_id')
            ->where('value', $chatId)
            ->first();

        return $setting?->user;
    }

    private function help(): string
    {
        return implode("\n", [
            'Wallos 订阅查询命令：',
            '/id - 获取当前 Chat ID',
            '/subscriptions - 查询全部有效订阅',
            '/upcoming - 查询未来 30 天到期订阅',
            '/help - 查看帮助',
        ]);
    }

    private function reply(array $settings, string|int $chatId, string $text): JsonResponse
    {
        if ($settings['telegram_enabled'] === '1' && $settings['telegram_bot_token']) {
            Http::timeout(10)
                ->post('https://api.telegram.org/bot'.$settings['telegram_bot_token'].'/sendMessage', [
                    'chat_id' => $chatId,
                    'text' => $text,
                ])
                ->throw();
        }

        return response()->json(['ok' => true]);
    }
}
