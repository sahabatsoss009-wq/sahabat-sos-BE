<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityProductionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test akses dokumen KYC tanpa token autentikasi harus ditolak (401).
     */
    public function test_unauthenticated_kyc_document_access_is_rejected(): void
    {
        $response = $this->get('/api/storage-file/kyc_documents/ktp_sample.jpg');

        $response->assertStatus(401);
    }

    /**
     * Test pengguna biasa tidak berhak melihat dokumen KYC orang lain (403).
     */
    public function test_regular_user_cannot_view_other_kyc_document(): void
    {
        $user = User::factory()->create([
            'role'        => 'pengguna',
            'is_active'   => true,
            'dokumen_kyc' => 'kyc_documents/my_ktp.jpg',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->get('/api/storage-file/kyc_documents/other_person_ktp.jpg');

        $response->assertStatus(403);
    }

    /**
     * Test admin yang dinonaktifkan tidak dapat mengakses dokumen KYC (401).
     */
    public function test_inactive_admin_cannot_access_kyc_document(): void
    {
        $admin = User::factory()->create([
            'role'        => 'admin',
            'is_active'   => false,
            'permissions' => ['verifikasi_relawan'],
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->get('/api/storage-file/kyc_documents/sample.jpg');

        $response->assertStatus(401);
    }

    /**
     * Test superadmin berhak mengakses dokumen KYC (mengembalikan 404 jika file fisik belum ada di disk).
     */
    public function test_superadmin_passes_kyc_authorization(): void
    {
        $superadmin = User::factory()->create([
            'role'      => 'superadmin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($superadmin, 'sanctum')
            ->get('/api/storage-file/kyc_documents/non_existent.jpg');

        // Lolos dari 401 dan 403, mengembalikan 404 karena berkas fisik belum ada di storage
        $response->assertStatus(404);
    }

    /**
     * Test kontak darurat WhatsApp tidak dapat diakses tanpa otorisasi (403/404).
     */
    public function test_unauthorized_user_cannot_access_whatsapp_contact(): void
    {
        $user = User::factory()->create([
            'role'      => 'pengguna',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->get('/api/tugas-aktif/sos/9999/kontak-darurat');

        $response->assertStatus(404);
    }

    /**
     * Test middleware CheckIsActive memblokir pengguna non-aktif dengan 403 tanpa fatal error token.
     */
    public function test_inactive_user_is_blocked_by_check_is_active(): void
    {
        $user = User::factory()->create([
            'role'      => 'pengguna',
            'is_active' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/update-location', [
                'latitude'  => -6.200000,
                'longitude' => 106.816666,
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test nomor kasus auto-generation pada model SOS dan Laporan.
     */
    public function test_sos_and_laporan_case_number_generation(): void
    {
        $user = User::factory()->create([
            'role'      => 'pengguna',
            'is_active' => true,
        ]);

        $sos = \App\Models\SOS::create([
            'id_pengguna' => $user->id,
            'latitude'    => -6.2088,
            'longitude'   => 106.8456,
            'status_sos'  => 'belum ditangani',
            'waktu_sos'   => now(),
        ]);

        $this->assertNotEmpty($sos->nomor_kasus);
        $this->assertStringStartsWith('SOS-', $sos->nomor_kasus);

        $laporan = \App\Models\Laporan::create([
            'id_pengguna'      => $user->id,
            'lokasi_laporan'   => 'Jl. Sudirman No. 1',
            'latitude'         => -6.2088,
            'longitude'        => 106.8456,
            'kategori_laporan' => 'Bencana Alam',
            'deskripsi'        => 'Pohon tumbang menutup jalan.',
            'status'           => 'belum ditangani',
            'waktu_laporan'    => now(),
        ]);

        $this->assertNotEmpty($laporan->nomor_kasus);
        $this->assertStringStartsWith('LP-', $laporan->nomor_kasus);
    }
}
