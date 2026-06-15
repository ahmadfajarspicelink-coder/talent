<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Tests\TestCase;

/**
 * Regression test: schedule di routes/console.php harus terdaftar dengan benar.
 *
 * Tanpa test ini, developer bisa saja hapus Schedule::command() di console.php
 * tanpa sadar dan auto-poll SNMP tidak akan jalan di production.
 */
class ScheduleRegistrationTest extends TestCase
{
    public function test_snmp_poll_is_scheduled(): void
    {
        $events = Schedule::events();

        $snmpPoll = collect($events)->first(function ($event) {
            return str_contains($event->command, 'snmp:poll');
        });

        $this->assertNotNull($snmpPoll, 'snmp:poll harus terdaftar di schedule');

        // Cron expression "*/5 * * * *" = 5 menit
        $this->assertSame('*/5 * * * *', $snmpPoll->expression);
    }

    public function test_monitoring_prune_is_scheduled(): void
    {
        $events = Schedule::events();

        $prune = collect($events)->first(function ($event) {
            return str_contains($event->command, 'monitoring:prune');
        });

        $this->assertNotNull($prune, 'monitoring:prune harus terdaftar di schedule');

        // Cron expression "0 3 * * *" = 03:00 daily
        $this->assertSame('0 3 * * *', $prune->expression);
    }

    public function test_snmp_poll_runs_without_overlap_and_in_background(): void
    {
        $events = Schedule::events();

        $snmpPoll = collect($events)->first(function ($event) {
            return str_contains($event->command, 'snmp:poll');
        });

        $this->assertNotNull($snmpPoll);

        // Tanpa overlap — penting supaya poll tidak stack kalau poll sebelumnya lambat
        $this->assertTrue($snmpPoll->withoutOverlapping, 'snmp:poll harus pakai withoutOverlapping');

        // Background — supaya log output tidak block scheduler tick berikutnya
        $this->assertTrue($snmpPoll->runInBackground, 'snmp:poll harus pakai runInBackground');
    }

    public function test_schedule_can_be_listed_via_artisan(): void
    {
        // Sanity check: `artisan schedule:list` exit code 0
        $exitCode = Artisan::call('schedule:list');
        $this->assertSame(0, $exitCode);

        $output = Artisan::output();
        $this->assertStringContainsString('snmp:poll', $output);
        $this->assertStringContainsString('monitoring:prune', $output);
    }

    public function test_monitoring_prune_command_works_in_dry_run(): void
    {
        Artisan::call('monitoring:prune', ['--dry-run' => true]);
        $output = Artisan::output();

        $this->assertStringContainsString('Dry-run mode', $output);
        $this->assertStringContainsString('interface_stats=', $output);
        $this->assertStringContainsString('device_stats=', $output);
    }
}
