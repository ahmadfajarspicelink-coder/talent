<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Order;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Verifikasi file upload validasi pakai BOTH `mimes` (extension) + `mimetypes`
 * (content-based magic byte sniff). H-06 (audit) — QW #8.
 *
 * Tujuan: pastikan file .php/.exe yang di-rename jadi .docx ditolak.
 * `mimes:` saja bisa di-bypass dengan rename; `mimetypes:` inspect actual content.
 */
class FileUploadMimetypeValidationTest extends TestCase
{
    use RefreshDatabase;

    private function order(): Order
    {
        return Order::factory()->create([
            'status' => 'Penawaran',
            'provider_id' => Partner::factory()->provider(),
            'vendor_id' => Partner::factory()->vendor(),
        ]);
    }

    private function userWithOrderAccess(): User
    {
        return User::factory()->create(['role' => 'staff']);
    }

    /** File .php yang di-rename jadi .docx harus ditolak (content-based check). */
    public function test_php_file_renamed_as_docx_is_rejected_by_mimetype_check(): void
    {
        $user = $this->userWithOrderAccess();
        $order = $this->order();

        // Laravel UploadedFile::fake() secara default kirim mimetype sesuai
        // extension. Kita harus simulasi real request dimana PHP finfo
        // akan deteksi konten aslinya.
        $evil = UploadedFile::fake()->create('evil.docx', 10, 'text/x-php');

        $response = $this->actingAs($user)
            ->from(route('orders.show', $order))
            ->post(route('orders.advanceStatus', $order), [
                'status' => 'Penawaran',
                'document' => $evil,
                'offer_number' => 'OFF-123',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('document');
    }

    /** File dengan content text plain + ext .pdf harus ditolak (mimetype mismatch). */
    public function test_text_file_with_pdf_extension_is_rejected_by_mimetype_check(): void
    {
        $user = $this->userWithOrderAccess();
        $order = $this->order();

        $fake = UploadedFile::fake()->create('doc.pdf', 10, 'text/plain');

        $response = $this->actingAs($user)
            ->from(route('orders.show', $order))
            ->post(route('orders.advanceStatus', $order), [
                'status' => 'Penawaran',
                'document' => $fake,
                'offer_number' => 'OFF-456',
            ]);

        $response->assertSessionHasErrors('document');
    }

    /** Real PDF content harus diterima (regression — tidak reject valid). */
    public function test_valid_pdf_file_is_accepted(): void
    {
        $user = $this->userWithOrderAccess();
        $order = $this->order();

        // fake() create file dengan content type yg benar (application/pdf)
        $valid = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)
            ->from(route('orders.show', $order))
            ->post(route('orders.advanceStatus', $order), [
                'status' => 'Penawaran',
                'document' => $valid,
                'offer_number' => 'OFF-789',
            ]);

        $response->assertSessionDoesntHaveErrors('document');
    }
}
