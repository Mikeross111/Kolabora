<?php

namespace Database\Seeders;

use App\Models\Province;
use App\Models\University;
use Illuminate\Database\Seeder;

/**
 * Data referensi untuk production: provinsi, kabupaten/kota, universitas, kategori lowongan.
 * Tidak membuat akun atau data dummy, dan aman dijalankan berulang.
 *
 * jalankan: php artisan db:seed --class=ReferenceDataSeeder --force
 */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        if (Province::count() === 0) {
            $this->call(ProvincesRegenciesSeeder::class);
        }

        if (University::count() === 0) {
            (new DummyDataSeeder())->seedUniversities();
        }

        $this->call(JobCategorySeeder::class);
    }
}
