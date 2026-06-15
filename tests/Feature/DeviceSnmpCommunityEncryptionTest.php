<?php

namespace Tests\Feature;

use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

/**
 * Verifikasi Device.snmp_community di-encrypt at-rest (M-04 — QW #9).
 *
 * Tujuan: SNMP community string tidak boleh plain-text di database. Pakai
 * Laravel Crypt::encryptString (AES-256-CBC). SnmpService baca via property
 * accessor → transparan decrypt.
 */
class DeviceSnmpCommunityEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_device_encrypts_snmp_community_in_database(): void
    {
        $plaintext = 'my-secret-community-123';

        $device = Device::create([
            'name' => 'Mikrotik-1',
            'ip_address' => '192.168.1.1',
            'snmp_community' => $plaintext,
            'snmp_version' => '2c',
            'vendor' => 'mikrotik',
            'status' => 'unknown',
        ]);

        // Reload dari DB untuk dapat raw value
        $raw = \DB::table('devices')->where('id', $device->id)->value('snmp_community');

        $this->assertNotEquals(
            $plaintext,
            $raw,
            'snmp_community di DB harus encrypted, BUKAN plaintext.'
        );
        $this->assertStringStartsWith('eyJ', $raw, 'Encrypted value harus base64 JSON (Laravel Crypt format).');
    }

    public function test_accessing_snmp_community_property_decrypts_value(): void
    {
        $plaintext = 'my-secret-community-456';

        $device = Device::create([
            'name' => 'Cisco-1',
            'ip_address' => '10.0.0.1',
            'snmp_community' => $plaintext,
            'snmp_version' => '2c',
            'vendor' => 'cisco',
            'status' => 'unknown',
        ]);

        // Reload dari DB (bukan pakai $device yang di-create di memory)
        $fresh = Device::find($device->id);

        $this->assertEquals(
            $plaintext,
            $fresh->snmp_community,
            'Property accessor harus decrypt transparan.'
        );
    }

    public function test_double_encryption_is_prevented(): void
    {
        $plaintext = 'community-789';

        $device = Device::create([
            'name' => 'Test-1',
            'ip_address' => '172.16.0.1',
            'snmp_community' => $plaintext,
            'snmp_version' => '2c',
            'vendor' => 'generic',
            'status' => 'unknown',
        ]);

        // Update device dengan nilai encrypted yang sudah ada (idempotent)
        $device->snmp_community = $device->getAttributes()['snmp_community'];
        $device->save();

        $fresh = Device::find($device->id);
        $this->assertEquals($plaintext, $fresh->snmp_community, 'Update tidak boleh double-encrypt.');
    }

    public function test_legacy_plaintext_value_is_returned_as_is(): void
    {
        // Simulasi data lama (pre-QW #9) yang masih plaintext
        $legacyValue = 'legacy-plaintext';

        \DB::table('devices')->insert([
            'name' => 'Legacy-1',
            'ip_address' => '10.10.10.1',
            'snmp_community' => $legacyValue,
            'snmp_version' => '2c',
            'vendor' => 'generic',
            'status' => 'unknown',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $device = Device::where('name', 'Legacy-1')->firstOrFail();
        $this->assertEquals(
            $legacyValue,
            $device->snmp_community,
            'Legacy plaintext value harus di-return apa adanya (graceful degradation).'
        );
    }

    public function test_snmp_community_is_hidden_from_json_serialization(): void
    {
        $device = Device::create([
            'name' => 'Hidden-Test',
            'ip_address' => '192.168.99.1',
            'snmp_community' => 'super-secret',
            'snmp_version' => '2c',
            'vendor' => 'generic',
            'status' => 'unknown',
        ]);

        $json = $device->toArray();
        $this->assertArrayNotHasKey('snmp_community', $json, 'snmp_community harus di-hide dari array/JSON serialization.');
    }
}
