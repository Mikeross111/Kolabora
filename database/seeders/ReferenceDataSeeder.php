<?php

namespace Database\Seeders;

use App\Models\Regency;
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
        if (Regency::count() === 0) {
            $this->seedWilayah();
        }

        if (University::count() === 0) {
            (new DummyDataSeeder())->seedUniversities();
        }

        $this->call(JobCategorySeeder::class);
    }

    private function seedWilayah(): void
    {
        $data = json_decode(file_get_contents(database_path('data/wilayah.json')), true);

        foreach (array_chunk($data['provinces'], 50) as $chunk) {
            DB::table('provinces')->insertOrIgnore($chunk);
        }
        foreach (array_chunk($data['regencies'], 100) as $chunk) {
            DB::table('regencies')->insertOrIgnore($chunk);
        }
    }
}
