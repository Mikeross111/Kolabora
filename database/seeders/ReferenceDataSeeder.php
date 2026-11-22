<?php

namespace Database\Seeders;

use App\Models\University;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Data referensi untuk production: provinsi, kabupaten/kota, universitas, kategori lowongan.
 * Wilayah dibaca dari berkas lokal (database/data/wilayah.json, 34 provinsi dan 514 kabupaten/kota),
 * bukan dari API eksternal. Tidak membuat akun dummy dan aman dijalankan berulang.
 *
 * jalankan: php artisan db:seed --class=ReferenceDataSeeder --force
 */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->syncWilayah();
        $this->alignProvinces();

        if (University::count() === 0) {
            (new DummyDataSeeder())->seedUniversities();
        }

        $this->call(JobCategorySeeder::class);
    }

    /** upsert: baris wilayah yang salah (mis. sisa data API lama) dikoreksi, yang belum ada ditambahkan */
    private function syncWilayah(): void
    {
        $data = json_decode(file_get_contents(database_path('data/wilayah.json')), true);

        foreach (array_chunk($data['provinces'], 50) as $chunk) {
            DB::table('provinces')->upsert($chunk, ['id'], ['code', 'name']);
        }
        foreach (array_chunk($data['regencies'], 100) as $chunk) {
            DB::table('regencies')->upsert($chunk, ['id'], ['province_id', 'code', 'name']);
        }
    }

    /** provinsi selalu mengikuti kabupaten/kota-nya, supaya alamat tidak bertentangan */
    private function alignProvinces(): void
    {
        foreach (['institutions', 'problems', 'universities', 'documents'] as $table) {
            DB::statement(
                "UPDATE {$table} SET province_id = (SELECT r.province_id FROM regencies r WHERE r.id = {$table}.regency_id)
                 WHERE regency_id IS NOT NULL
                 AND EXISTS (SELECT 1 FROM regencies r WHERE r.id = {$table}.regency_id AND r.province_id <> {$table}.province_id)"
            );
        }
    }
}
