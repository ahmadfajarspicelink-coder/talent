<?php

namespace Tests\Unit;

use App\Http\Requests\StorePartnerRequest;
use Eris\Generator;
use Eris\TestTrait;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Property-based test untuk pembatasan tipe Partner.
 *
 * Menguji aturan validasi StorePartnerRequest secara langsung lewat Validator
 * facade. Karena Validator membutuhkan container aplikasi (translator), test ini
 * mewarisi Tests\TestCase agar aplikasi Laravel ter-boot.
 */
class PartnerTypePropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Feature: talent-secreet-isp-broker, Property 3: Tipe Partner dibatasi pada provider atau vendor
     *
     * For any nilai tipe yang dikirimkan saat membuat/memperbarui Partner,
     * penyimpanan diterima jika dan hanya jika nilainya termasuk dalam
     * {provider, vendor}.
     *
     * Validates: Requirements 3.3
     */
    public function test_partner_type_accepted_iff_provider_or_vendor(): void
    {
        $rules = (new StorePartnerRequest())->rules();

        $this->limitTo(100)
            ->forAll(
                // Campuran nilai valid ('provider'/'vendor') dengan beragam nilai
                // lain (variasi huruf besar, kata acak, kosong, string acak) agar
                // sifat "if and only if" benar-benar teruji di kedua arah.
                Generator\oneOf(
                    Generator\elements(
                        'provider',
                        'vendor',
                        'PROVIDER',
                        'Vendor',
                        'reseller',
                        'distributor',
                        'isp',
                        ''
                    ),
                    Generator\string()
                )
            )
            ->then(function (string $type) use ($rules): void {
                // Sediakan name valid supaya hanya aturan 'type' yang menentukan hasil.
                $validator = Validator::make(
                    ['name' => 'Mitra Uji', 'type' => $type],
                    $rules
                );

                $passes = $validator->passes();
                $shouldPass = in_array($type, ['provider', 'vendor'], true);

                $this->assertSame(
                    $shouldPass,
                    $passes,
                    sprintf(
                        'Tipe "%s" seharusnya %s validasi, tapi hasilnya %s.',
                        $type,
                        $shouldPass ? 'lolos' : 'ditolak',
                        $passes ? 'lolos' : 'ditolak'
                    )
                );

                // Saat tipe tidak valid, error harus berada pada field 'type'.
                if (!$shouldPass) {
                    $this->assertTrue(
                        $validator->errors()->has('type'),
                        sprintf('Tipe tidak valid "%s" harus memunculkan error pada field type.', $type)
                    );
                }
            });
    }
}
