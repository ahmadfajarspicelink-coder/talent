<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private readonly string $botToken;
    private readonly string $chatId;

    public function __construct(
        string $botToken = '',
        string $chatId = '',
    ) {
        $this->botToken = $botToken ?: config('services.telegram.bot_token', '');
        $this->chatId = $chatId ?: config('services.telegram.chat_id', '');
    }

    public function sendAlert(string $message): bool
    {
        if ($this->botToken === '' || $this->chatId === '') {
            Log::warning('Telegram not configured. Set TELEGRAM_BOT_TOKEN and TELEGRAM_CHAT_ID in .env');

            return false;
        }

        try {
            $payload = [
                'chat_id'    => $this->chatId,
                'text'       => $message,
                'parse_mode' => 'HTML',
            ];

            $topicId = config('services.telegram.topic_id');
            if ($topicId) {
                $payload['message_thread_id'] = (int) $topicId;
            }

            $response = Http::timeout(10)->post(
                "https://api.telegram.org/bot{$this->botToken}/sendMessage",
                $payload,
            );

            if ($response->successful()) {
                Log::info('Telegram alert sent: ' . mb_substr($message, 0, 50));

                return true;
            }

            Log::error('Telegram API error: ' . $response->body());

            return false;
        } catch (\Throwable $e) {
            Log::error('Telegram send failed: ' . $e->getMessage());

            return false;
        }
    }

    // ── Port status alerts ──

    public function alertPortDown(string $deviceName, string $ip, string $ifName, string $ifAlias = ''): bool
    {
        return $this->sendAlert($this->buildMessage('🔴', 'PORT DOWN', $deviceName, $ip, [
            'Port' => $ifName . ($ifAlias ? " ({$ifAlias})" : ''),
        ]));
    }

    public function alertPortUp(string $deviceName, string $ip, string $ifName, string $ifAlias = ''): bool
    {
        return $this->sendAlert($this->buildMessage('🟢', 'PORT UP', $deviceName, $ip, [
            'Port' => $ifName . ($ifAlias ? " ({$ifAlias})" : ''),
        ]));
    }

    // ── Device status alerts ──

    public function alertDeviceOffline(string $deviceName, string $ip): bool
    {
        return $this->sendAlert($this->buildMessage('⚠️', 'DEVICE OFFLINE', $deviceName, $ip));
    }

    public function alertDeviceOnline(string $deviceName, string $ip): bool
    {
        return $this->sendAlert($this->buildMessage('✅', 'DEVICE ONLINE', $deviceName, $ip));
    }

    // ── CPU/Memory alerts ──

    public function alertHighCpu(string $deviceName, string $ip, int $cpuPercent): bool
    {
        return $this->sendAlert($this->buildMessage('🔥', 'HIGH CPU', $deviceName, $ip, [
            'CPU' => $cpuPercent . '%',
        ]));
    }

    public function alertHighMemory(string $deviceName, string $ip, float $memPercent): bool
    {
        return $this->sendAlert($this->buildMessage('🧠', 'HIGH MEMORY', $deviceName, $ip, [
            'Memory' => $memPercent . '%',
        ]));
    }

    /**
     * Build formatted Telegram alert message.
     *
     * @param  array<string, string>  $extra  label → value pairs
     */
    private function buildMessage(string $emoji, string $title, string $deviceName, string $ip, array $extra = []): string
    {
        $lines = [
            "{$emoji} <b>{$title}</b>\n",
            "📦 Device: {$deviceName}",
            "🌐 IP: {$ip}",
        ];

        foreach ($extra as $label => $value) {
            $lines[] = "📊 {$label}: {$value}";
        }

        $lines[] = '🕐 ' . now()->format('Y-m-d H:i:s');

        return implode("\n", $lines);
    }
}