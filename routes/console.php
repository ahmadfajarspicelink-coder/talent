<?php

use App\Console\Commands\PruneMonitoringStats;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes & Scheduled Tasks
|--------------------------------------------------------------------------
|
| Setiap entry Schedule::command() mendaftarkan task ke scheduler Laravel.
| Scheduler di-trigger via `php artisan schedule:work` (foreground, dev) atau
| `php artisan schedule:run` (cron, prod). Lihat README.MONITORING.md untuk
| cara setup di Windows.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// ─────────────────────────────────────────────────────────────────────────
//  SNMP monitoring — auto-poll devices
// ─────────────────────────────────────────────────────────────────────────
// Poll semua device tiap N menit (default 5, override via env).
// Pakai cron('*/N * * * *') karena setiapMinutes(N) dihapus di Laravel 12+.
// TanpaOverlap supaya poll sebelumnya selesai dulu sebelum mulai lagi.
// RunInBackground agar log/output tidak block scheduler.
$pollInterval = (int) config('monitoring.poll_interval_minutes', 5);
Schedule::command('snmp:poll')
    ->cron("*/{$pollInterval} * * * *")
    ->name('snmp:poll')
    ->withoutOverlapping(10)
    ->runInBackground()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/scheduler.log'));


// ─────────────────────────────────────────────────────────────────────────
//  Monitoring — data retention (prune lama)
// ─────────────────────────────────────────────────────────────────────────
// Hapus InterfaceStat >7 hari dan DeviceStat >30 hari.
// Daily cukup — prune adalah maintenance, bukan real-time task.
Schedule::command(PruneMonitoringStats::class)
    ->dailyAt('03:00')
    ->name('monitoring:prune')
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/scheduler.log'));
