<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature test Modul_User_Management: keunikan email (R8.2) & guard self-delete (R8.5).
 *
 * - Email duplikat: pembuatan Pengguna baru dengan email yang sudah terdaftar
 *   ditolak dengan error validasi 'email' (pesan "email sudah dipakai"), dan
 *   tidak ada Pengguna baru yang tersimpan.
 * - Guard self-delete: Admin yang menghapus akunnya sendiri ditolak; akun tetap
 *   ada di database dan flash error session berisi pesan yang sesuai.
 *
 * Catatan: route users.store / users.destroy dilindungi
 * ['auth','module:user_management'] sehingga hanya Admin yang boleh mengakses
 * Modul_User_Management (R2.4).
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /** R8.2: email duplikat ditolak dengan error 'email', tidak ada user baru. */
    public function test_membuat_user_dengan_email_duplikat_ditolak(): void
    {
        $admin = $this->adminUser();

        $existing = User::factory()->create([
            'role' => 'staff',
            'email' => 'dupe@example.com',
        ]);

        $countBefore = User::count();

        $response = $this->actingAs($admin)
            ->from('/users')
            ->post('/users', [
                'name' => 'Pengguna Baru',
                'email' => 'dupe@example.com', // email yang sudah dipakai
                'password' => 'password123',
                'role' => 'staff',
            ]);

        $response->assertStatus(302);
        $response->assertRedirect('/users');
        $response->assertSessionHasErrors(['email']);

        // Tidak ada Pengguna baru yang tersimpan.
        $this->assertSame($countBefore, User::count());
    }

    /** Pembanding R8.1: email unik baru berhasil membuat Pengguna. */
    public function test_membuat_user_dengan_email_unik_berhasil(): void
    {
        $admin = $this->adminUser();

        $countBefore = User::count();

        $response = $this->actingAs($admin)
            ->from('/users')
            ->post('/users', [
                'name' => 'Pengguna Segar',
                'email' => 'fresh@example.com',
                'password' => 'password123',
                'role' => 'staff',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame($countBefore + 1, User::count());
        $this->assertDatabaseHas('users', [
            'email' => 'fresh@example.com',
            'role' => 'staff',
        ]);
    }

    /** R8.5: Admin menghapus akun sendiri ditolak; akun tetap ada + flash error. */
    public function test_admin_tidak_bisa_menghapus_akun_sendiri(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)
            ->from('/users')
            ->delete('/users/'.$admin->id);

        $response->assertStatus(302);
        $response->assertRedirect('/users');

        // Akun admin tetap ada di database.
        $this->assertDatabaseHas('users', ['id' => $admin->id]);

        // Flash error berisi pesan yang sesuai.
        $response->assertSessionHas('error', 'akun sendiri tidak bisa dihapus');
    }

    /** Pembanding R8.5: menghapus Pengguna lain berhasil. */
    public function test_admin_bisa_menghapus_user_lain(): void
    {
        $admin = $this->adminUser();
        $other = User::factory()->create(['role' => 'staff']);

        $response = $this->actingAs($admin)
            ->from('/users')
            ->delete('/users/'.$other->id);

        $response->assertStatus(302);
        $response->assertRedirect('/users');
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('users', ['id' => $other->id]);
    }
}
