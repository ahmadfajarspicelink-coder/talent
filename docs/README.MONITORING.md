# WEB2 — Monitoring Scheduler

Scheduled tasks untuk module Network Monitoring (SNMP polling & data retention).

## Daftar Task

| Task         | Command             | Interval       | Tujuan |
|--------------|---------------------|----------------|--------|
| SNMP poll    | `snmp:poll`         | Tiap 5 menit   | Poll semua device via SNMP, simpan InterfaceStat & DeviceStat, kirim alert Telegram |
| Prune stats  | `monitoring:prune`  | Daily jam 03:00 | Hapus InterfaceStat >7 hari & DeviceStat >30 hari |

Override interval & retensi via `.env`:

```env
MONITORING_POLL_INTERVAL=5
MONITORING_RETENTION_INTERFACE_DAYS=7
MONITORING_RETENTION_DEVICE_DAYS=30
MONITORING_ALERT_CPU=80
MONITORING_ALERT_MEMORY=80
```

## Cara Menjalankan Scheduler

Scheduler Laravel perlu proses yang trigger `schedule:run` terus-menerus (atau di-
trigger tiap menit oleh cron / Task Scheduler). Ada 2 mode:

### Mode 1: Foreground (dev only)

```bash
cd D:\Document\laragon\www\WEB2
php artisan schedule:work
```

Proses ini jalan terus di foreground, polling scheduler tiap menit. Berguna untuk dev.
Tutup terminal = scheduler mati.

### Mode 2: Windows Task Scheduler (production / background)

Setup agar `php artisan schedule:run` dipanggil tiap menit oleh Windows Task Scheduler.

**Langkah:**

1. Buka **Task Scheduler** (Win + R → `taskschd.msc`)
2. **Create Task** (bukan "Create Basic Task")
3. Tab **General**:
   - Name: `WEB2 Scheduler`
   - Run whether user is logged on or not: ✓
   - Run with highest privileges: ✓
4. Tab **Triggers** → New:
   - Begin the task: **On a schedule**
   - Settings: **Daily**, Start: hari ini, Recur every: **1 days**
   - **Repeat task every: 1 minute**, for a duration of: **Indefinitely**
   - Enabled: ✓
5. Tab **Actions** → New:
   - Action: **Start a program**
   - Program/script:
     ```
     D:\Document\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe
     ```
   - Add arguments:
     ```
     artisan schedule:run
     ```
   - Start in:
     ```
     D:\Document\laragon\www\WEB2
     ```
6. Tab **Conditions**:
   - Stop if the computer switches to battery power: ✗ (uncek untuk laptop)
   - Start the task only if the computer is on AC power: ✗ (uncek)
7. Tab **Settings**:
   - Allow task to be run on demand: ✓
   - If the task fails, restart every: **1 minute**
   - Attempt to restart up to: **3 times**
   - Stop the task if it runs longer than: **1 minute** (karena `schedule:run` harus selesai <1 menit)

Klik OK, masukkan password user.

**Verifikasi:**

Buka Command Prompt:

```cmd
schtasks /Run /TN "WEB2 Scheduler"
type D:\Document\laragon\www\WEB2\storage\logs\scheduler.log
```

Log akan muncul tiap menit. Setelah 5 menit, `snmp:poll` akan muncul pertama kali.

### Mode 3: NSSM (alternatif production-grade)

[NSSM](https://nssm.cc/) bisa wrap `php artisan schedule:work` jadi Windows service:

```cmd
nssm install "WEB2 Scheduler" "D:\Document\laragon\bin\php\php-8.3.31-Win32-vs16-x64\php.exe" "artisan schedule:work"
nssm set "WEB2 Scheduler" AppDirectory "D:\Document\laragon\www\WEB2"
nssm start "WEB2 Scheduler"
```

Service akan auto-start saat Windows boot. Lebih reliable dari Task Scheduler
(tidak ada delay drift).

## Troubleshooting

### Cek apakah task terdaftar

```bash
php artisan schedule:list
```

Output:
```
*/5 * * * *   php artisan snmp:poll               Next Due: 3 minutes from now
  0 3 * * *   php artisan monitoring:prune        Next Due: 14 hours from now
```

### Cek log scheduler

```bash
type storage\logs\scheduler.log
```

### Trigger manual satu task

```bash
php artisan snmp:poll
php artisan monitoring:prune --dry-run
```

### Cek apakah `php artisan schedule:work` jalan

Buka terminal lain:
```bash
php artisan schedule:list
```

Kalau task "Next Due" sudah lewat dan tidak tereksekusi, scheduler tidak jalan.

## Tests

```bash
php artisan test tests/Feature/ScheduleRegistrationTest.php
```

5 tests memastikan schedule + command terdaftar dan berjalan.
