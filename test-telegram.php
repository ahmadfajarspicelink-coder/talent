<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$telegram = new \App\Services\TelegramService();
$result = $telegram->sendAlert('🧪 <b>TEST NOTIFIKASI</b>

📦 Test dari Kilo
🕐 ' . now()->format('Y-m-d H:i:s'));

echo $result ? "✅ Notifikasi terkirim ke topic!\n" : "❌ Gagal kirim notifikasi\n";
