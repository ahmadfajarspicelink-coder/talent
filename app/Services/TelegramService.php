<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class TelegramService
{
    private string $botToken;
    private string $chatId;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token', '');
        $this->chatId = config('services.telegram.chat_id', '');
    }

    public function sendAlert(string $message): bool
    {
        if (empty($this->botToken) || empty($this->chatId)) {
            Log::warning('Telegram not configured. Set TELEGRAM_BOT_TOKEN and TELEGRAM_CHAT_ID in .env');
            return false;
        }

        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";

        try {
            $response = Http::timeout(10)->post($url, [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);

            if ($response->successful()) {
                Log::info("Telegram alert sent: " . substr($message, 0, 50));
                return true;
            }

            Log::error("Telegram API error: " . $response->body());
            return false;
        } catch (\Throwable $e) {
            Log::error("Telegram send failed: " . $e->getMessage());
            return false;
        }
    }

    // ── Port status alerts ──

    public function alertPortDown(string $deviceName, string $ip, string $ifName, string $ifAlias = ''): bool
    {
        $alias = $ifAlias ? " ({$ifAlias})" : '';

        $message = "🔴 <b>PORT DOWN</b>\n\n"
            . "📦 Device: {$deviceName}\n"
            . "🌐 IP: {$ip}\n"
            . "🔌 Port: {$ifName}{$alias}\n"
            . "🕐 " . now()->format('Y-m-d H:i:s');

        return $this->sendAlert($message);
    }

    public function alertPortUp(string $deviceName, string $ip, string $ifName, string $ifAlias = ''): bool
    {
        $alias = $ifAlias ? " ({$ifAlias})" : '';

        $message = "🟢 <b>PORT UP</b>\n\n"
            . "📦 Device: {$deviceName}\n"
            . "🌐 IP: {$ip}\n"
            . "🔌 Port: {$ifName}{$alias}\n"
            . "🕐 " . now()->format('Y-m-d H:i:s');

        return $this->sendAlert($message);
    }

    // ── Device status alerts ──

    public function alertDeviceOffline(string $deviceName, string $ip): bool
    {
        $message = "⚠️ <b>DEVICE OFFLINE</b>\n\n"
            . "📦 Device: {$deviceName}\n"
            . "🌐 IP: {$ip}\n"
            . "🕐 " . now()->format('Y-m-d H:i:s');

        return $this->sendAlert($message);
    }

    public function alertDeviceOnline(string $deviceName, string $ip): bool
    {
        $message = "✅ <b>DEVICE ONLINE</b>\n\n"
            . "📦 Device: {$deviceName}\n"
            . "🌐 IP: {$ip}\n"
            . "🕐 " . now()->format('Y-m-d H:i:s');

        return $this->sendAlert($message);
    }

    // ── CPU/Memory alerts ──

    public function alertHighCpu(string $deviceName, string $ip, int $cpuPercent): bool
    {
        $message = "🔥 <b>HIGH CPU</b>\n\n"
            . "📦 Device: {$deviceName}\n"
            . "🌐 IP: {$ip}\n"
            . "📊 CPU: {$cpuPercent}%\n"
            . "🕐 " . now()->format('Y-m-d H:i:s');

        return $this->sendAlert($message);
    }

    public function alertHighMemory(string $deviceName, string $ip, float $memPercent): bool
    {
        $message = "🧠 <b>HIGH MEMORY</b>\n\n"
            . "📦 Device: {$deviceName}\n"
            . "🌐 IP: {$ip}\n"
            . "📊 Memory: {$memPercent}%\n"
            . "🕐 " . now()->format('Y-m-d H:i:s');

        return $this->sendAlert($message);
    }
}
