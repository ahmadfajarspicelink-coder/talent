<?php

/*
 * Monitoring module configuration. Dipakai oleh SnmpService untuk alert
 * threshold dan oleh PruneMonitoringStats untuk retensi data polling.
 *
 * Override nilai ini via .env, mis: MONITORING_ALERT_CPU=85
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Alert thresholds (persentase 0-100)
    |--------------------------------------------------------------------------
    | CPU dan memory usage yang melampaui threshold ini akan memicu notifikasi
    | Telegram. Nilai default 80% sesuai best practice ISP NOC.
    */

    'alert_cpu'    => (int) env('MONITORING_ALERT_CPU', 80),
    'alert_memory' => (int) env('MONITORING_ALERT_MEMORY', 80),

    /*
    |--------------------------------------------------------------------------
    | Data retention (hari)
    |--------------------------------------------------------------------------
    | InterfaceStat: polled tiap 5 menit → 288 baris/hari/interface.
    |   TopTraffic butuh minimal 2 baris (current + previous) untuk hitung rate,
    |   jadi retensi 7 hari sudah lebih dari cukup dan tidak membebani storage.
    |
    | DeviceStat: polled tiap 5 menit → 288 baris/hari/device.
    |   Untuk trend analysis (CPU/memory chart) butuh retensi lebih panjang.
    |
    | Ubah via env: MONITORING_RETENTION_INTERFACE_DAYS=14
    */

    'retention' => [
        'interface_stats_days' => (int) env('MONITORING_RETENTION_INTERFACE_DAYS', 7),
        'device_stats_days'    => (int) env('MONITORING_RETENTION_DEVICE_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Polling interval (menit)
    |--------------------------------------------------------------------------
    | Berapa menit sekali SnmpService mem-poll semua device. Default 5 menit
    | (sesuai best practice SNMP polling rate). Interval lebih pendek membebani
    | device target; interval lebih panjang membuat data rate kurang akurat.
    */

    'poll_interval_minutes' => (int) env('MONITORING_POLL_INTERVAL', 5),

];
