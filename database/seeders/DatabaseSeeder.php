<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ── Production guard ───────────────────────────────────────────
        // Refuse to seed default users in production. Default password
        // lemah ('password') + akun publik (admin@example.com) = critical
        // risk jika accidentally dijalankan di prod via `php artisan db:seed`.
        if (App::environment('production')) {
            $message = 'Refusing to seed default users in production. Create users via the UI or a dedicated seeder.';
            // Saat dipanggil via artisan, $this->command tersedia. Saat dipanggil
            // langsung dari test (new DatabaseSeeder()->run()), $this->command
            // null, fallback ke fwrite STDERR.
            if (isset($this->command) && $this->command !== null) {
                $this->command->error($message);
            } else {
                fwrite(STDERR, "[ERROR] {$message}\n");
            }
            return;
        }

        // Password bisa di-override via env SEED_ADMIN_PASSWORD. Jika tidak
        // diset, generate random 32 char agar tidak ada default password
        // yang gampang ditebak. Hash::make() pake bcrypt (cost 12 dari .env
        // atau default Laravel).
        $adminPassword = Hash::make(env('SEED_ADMIN_PASSWORD') ?: Str::random(32));
        $staffPassword = Hash::make(env('SEED_STAFF_PASSWORD') ?: Str::random(32));

        // Akun Admin: akses penuh ke seluruh modul (partner, order, client, finance, user_management).
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => $adminPassword,
                'role' => 'admin',
            ]
        );

        // Akun Staff: akses terbatas ke modul order & partner.
        User::updateOrCreate(
            ['email' => 'staff@example.com'],
            [
                'name' => 'Staff',
                'password' => $staffPassword,
                'role' => 'staff',
            ]
        );
    }
}
