<?php

namespace App\Console\Commands;

use App\Models\DeviceStat;
use App\Models\InterfaceStat;
use Illuminate\Console\Command;

/**
 * Hapus data polling lama sesuai retensi hari di config/monitoring.php.
 *
 * Tujuan:
 * - InterfaceStat: 288 baris/hari/interface @ poll 5min. Retensi 7 hari sudah
 *   cukup untuk TopTraffic rate calculation (butuh ≥2 baris).
 * - DeviceStat:    288 baris/hari/device. Retensi 30 hari untuk trend analysis.
 *
 * Scheduled harian via routes/console.php. Aman dijalankan kapan saja
 * (idempotent — baris yang akan dihapus tidak akan di-reinsert sampai poll
 * berikutnya).
 */
class PruneMonitoringStats extends Command
{
    protected $signature = 'monitoring:prune
                            {--interface-days= : Override interface stats retention days}
                            {--device-days= : Override device stats retention days}
                            {--dry-run : Show count without deleting}';

    protected $description = 'Hapus SNMP polling stats lama sesuai retensi hari';

    public function handle(): int
    {
        $ifaceDays = (int) ($this->option('interface-days') ?? config('monitoring.retention.interface_stats_days', 7));
        $devDays   = (int) ($this->option('device-days')    ?? config('monitoring.retention.device_stats_days', 30));
        $isDryRun  = (bool) $this->option('dry-run');

        $ifaceCutoff = now()->subDays($ifaceDays);
        $devCutoff   = now()->subDays($devDays);

        $this->info("Retention: interface_stats={$ifaceDays}d, device_stats={$devDays}d");
        $this->info("Cutoff:    interface_stats < {$ifaceCutoff->toDateTimeString()}, device_stats < {$devCutoff->toDateTimeString()}");

        // Count first (cheap query, dapat feedback sebelum delete)
        $ifaceCount = InterfaceStat::where('polled_at', '<', $ifaceCutoff)->count();
        $devCount   = DeviceStat::where('polled_at',   '<', $devCutoff)->count();

        $this->line("Found:    {$ifaceCount} interface_stats, {$devCount} device_stats to delete");

        if ($isDryRun) {
            $this->warn('Dry-run mode: nothing deleted.');
            return self::SUCCESS;
        }

        $ifaceDeleted = 0;
        $devDeleted   = 0;

        if ($ifaceCount > 0) {
            // Delete in chunks untuk hindari lock table pada dataset besar
            $ifaceDeleted = InterfaceStat::where('polled_at', '<', $ifaceCutoff)
                ->delete();
        }

        if ($devCount > 0) {
            $devDeleted = DeviceStat::where('polled_at', '<', $devCutoff)
                ->delete();
        }

        $this->info("Deleted:  {$ifaceDeleted} interface_stats, {$devDeleted} device_stats");

        return self::SUCCESS;
    }
}
