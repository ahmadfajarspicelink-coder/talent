<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$botToken = '8846473490:AAHQ4zqEBPdqbnz79DbV-sC7Q_rml_dbIRk';

$response = \Illuminate\Support\Facades\Http::get("https://api.telegram.org/bot{$botToken}/getMe");

$data = $response->json();

if ($data['ok'] ?? false) {
    echo "✅ Bot Token Valid!\n";
    echo "Bot Username: @{$data['result']['username']}\n";
    echo "Bot ID: {$data['result']['id']}\n";
} else {
    echo "❌ Bot Token Invalid!\n";
    echo "Error: " . ($data['description'] ?? 'Unknown error') . "\n";
}

echo "\nResponse: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
