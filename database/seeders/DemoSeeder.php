<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Data demo untuk juri: platform yang terlihat sudah berjalan sekitar satu bulan.
 * Semua orang, instansi, dan perusahaan di sini fiktif (dibuat oleh seeder), bukan pengguna asli.
 * Butuh data referensi (ReferenceDataSeeder) lebih dulu. Aman dijalankan berulang.
 *
 * jalankan: php artisan db:seed --class=DemoSeeder --force
 */
class DemoSeeder extends Seeder
{
    private const PASSWORD = 'Kolabora#2026';

    private Carbon $now;

    /** @var array<string, string[]> foto per folder tema */
    private array $library = [];

    /** @var array<string, bool> foto yang sudah dipakai sebagai sampul, supaya tidak berulang */
    private array $usedPhotos = [];

    public function run(): void
    {
        mt_srand(2026);
        $this->now = now();

        if (DB::table('users')->where('email', 'admin@kolabora.test')->exists()) {
            if (DB::table('problems')->count() >= 30) {
                $this->refreshPhotos();
                $this->refreshJobText();
                $this->refreshDemoState();
                return;
            }
            $this->purge();
        }

        $univs = DB::table('universities')->orderBy('id')->get();
        if ($univs->isEmpty() || DB::table('regencies')->count() === 0) {
            return;
        }

        DB::transaction(function () use ($univs) {
            $users = $this->seedUsers($univs);
            $this->seedProblemsAndActivity($users);
            $this->seedJobs($users);
            $this->seedSocial($users);
        });

        $this->refreshDemoState();
    }

    // ───────────────────────── helpers ─────────────────────────

    private function ago(int $maxDays, int $minDays = 0): Carbon
    {
        return $this->now->copy()->subDays(mt_rand($minDays, $maxDays))->subMinutes(mt_rand(0, 1300));
    }

    private function pick(array $a)
    {
        return $a[array_rand($a)];
    }

    private function purge(): void
    {
        foreach (['reviews', 'project_reports', 'project_milestones', 'documents', 'projects', 'job_applications', 'saved_jobs',
            'saved_talents', 'job_alerts', 'job_postings', 'wishlists', 'applications', 'problem_images', 'problems', 'friends',
            'notifications', 'companies', 'institutions', 'students'] as $t) {
            DB::table($t)->delete();
        }
        DB::table('users')->where('email', 'like', '%@kolabora.test')->delete();
    }

    private function userRow(string $name, string $email, string $username, string $type, string $hash, Carbon $at): int
    {
        return DB::table('users')->insertGetId([
            'name' => $name, 'email' => $email, 'username' => $username, 'password' => $hash, 'user_type' => $type,
            'is_active' => true, 'email_verified_at' => $at, 'created_at' => $at, 'updated_at' => $at,
        ]);
    }

    /**
     * akun demo juri harus selalu punya sesuatu untuk diproses: pelamar yang menunggu (perusahaan),
     * lamaran dan laporan yang menunggu tinjauan (instansi). hanya memulihkan bila jumlahnya terlalu sedikit,
     * dan hanya menyentuh data milik akun demo.
     */
    private function refreshDemoState(): void
    {
        $co = DB::table('companies')->where('email', 'perusahaan@kolabora.test')->value('id');
        if ($co) {
            $jobIds = DB::table('job_postings')->where('company_id', $co)->pluck('id');
            $apps = DB::table('job_applications')->whereIn('job_posting_id', $jobIds)->orderBy('id')->pluck('id');
            $waiting = DB::table('job_applications')->whereIn('job_posting_id', $jobIds)->whereIn('status', ['pending', 'new'])->count();
            if ($apps->count() > 0 && $waiting < 4) {
                $cycle = ['pending', 'pending', 'reviewed', 'shortlisted', 'pending', 'rejected', 'reviewed', 'pending'];
                foreach ($apps as $i => $id) {
                    DB::table('job_applications')->where('id', $id)->update(['status' => $cycle[$i % count($cycle)], 'updated_at' => now()]);
                }
            }
        }

        $inst = DB::table('institutions')->where('email', 'instansi@kolabora.test')->value('id');
        if ($inst) {
            $problemIds = DB::table('problems')->where('institution_id', $inst)->pluck('id');
            $pending = DB::table('applications')->whereIn('problem_id', $problemIds)->where('status', 'pending')->count();
            if ($pending < 4) {
                $ids = DB::table('applications')->whereIn('problem_id', $problemIds)->whereIn('status', ['reviewed', 'rejected'])
                    ->orderBy('id')->limit(4 - $pending)->pluck('id');
                DB::table('applications')->whereIn('id', $ids)->update(['status' => 'pending', 'reviewed_at' => null, 'rejected_at' => null, 'feedback' => null]);
            }

            $activeIds = DB::table('projects')->where('institution_id', $inst)->where('status', 'active')->pluck('id');
            $waitingReports = DB::table('project_reports')->whereIn('project_id', $activeIds)->where('status', 'pending')->count();
            if ($waitingReports < 2) {
                $ids = DB::table('project_reports')->whereIn('project_id', $activeIds)->where('status', 'approved')
                    ->orderByDesc('id')->limit(2 - $waitingReports)->pluck('id');
                DB::table('project_reports')->whereIn('id', $ids)->update(['status' => 'pending', 'institution_feedback' => null, 'reviewed_at' => null]);
            }
        }
    }

    // ───────────────────────── foto ─────────────────────────

    private function themeFromSdg(array $sdg): string
    {
        $first = (int) ($sdg[0] ?? 0);

        return match (true) {
            in_array($first, [1, 2, 15], true) => 'pertanian',
            $first === 14 => 'pesisir',
            in_array($first, [4, 5, 10], true) => 'pendidikan',
            in_array($first, [8, 12, 17], true) => 'pasar',
            in_array($first, [9, 16], true) => 'desa',
            default => 'komunitas',
        };
    }

    private function photosIn(string $dir): array
    {
        if (! isset($this->library[$dir])) {
            $sub = in_array($dir, ['field', 'about', 'homepage'], true) ? $dir : "library/$dir";
            $files = glob(public_path("assets/photography/$sub/*.webp")) ?: [];
            sort($files);
            if ($dir === 'field') {
                $files = array_values(array_filter($files, fn ($f) => str_ends_with($f, '-800.webp')));
            }
            $this->library[$dir] = array_map(fn ($f) => "assets/photography/$sub/" . basename($f), $files);
        }

        return $this->library[$dir];
    }

    /** foto sampul yang belum terpakai, dimulai dari tema yang cocok; jika semua terpakai, pilihan berputar */
    private function pickPhoto(string $theme, bool $cover): string
    {
        $order = array_values(array_unique(array_merge([$theme], match ($theme) {
            'komunitas' => ['field', 'desa', 'pendidikan'],
            'desa' => ['komunitas', 'field', 'homepage', 'about'],
            'pertanian' => ['about', 'homepage', 'desa'],
            'pesisir' => ['homepage', 'desa'],
            'pasar' => ['komunitas', 'desa'],
            default => ['komunitas', 'desa'],
        }, ['field', 'komunitas', 'desa', 'pendidikan', 'pertanian', 'pesisir', 'pasar', 'homepage', 'about'])));

        foreach ($order as $dir) {
            foreach ($this->photosIn($dir) as $path) {
                if (! isset($this->usedPhotos[$path])) {
                    if ($cover) {
                        $this->usedPhotos[$path] = true;
                    }

                    return $path;
                }
            }
        }

        $all = $this->photosIn($theme) ?: $this->photosIn('komunitas');

        return $all[count($this->usedPhotos) % max(1, count($all))] ?? 'assets/photography/field/kkn-itb-2025-mahasiswa-jalan-desa-800.webp';
    }

    /** lowongan demo lama menyimpan daftar sebagai JSON; aplikasi memakai teks biasa satu baris per butir */
    private function refreshJobText(): void
    {
        DB::table('job_postings')->where('job_type', 'full-time')->update(['job_type' => 'full_time']);
        DB::table('job_postings')->where('job_type', 'part-time')->update(['job_type' => 'part_time']);

        foreach (DB::table('job_postings')->get(['id', 'responsibilities', 'qualifications', 'benefits']) as $row) {
            $update = [];
            foreach (['responsibilities', 'qualifications', 'benefits'] as $col) {
                $v = $row->{$col};
                if (is_string($v) && str_starts_with(ltrim($v), '[')) {
                    $list = json_decode($v, true);
                    if (is_array($list)) {
                        $update[$col] = implode("
", array_map('strval', $list));
                    }
                }
            }
            if ($update) {
                DB::table('job_postings')->where('id', $row->id)->update($update);
            }
        }
    }

    /** data demo yang sudah ada: bagikan ulang foto supaya tiap masalah punya sampul berbeda. tidak menyentuh data lain */
    private function refreshPhotos(): void
    {
        $covers = DB::table('problem_images')->where('is_cover', true)->where('image_path', 'like', 'assets/photography/%')->distinct()->count('image_path');
        if ($covers >= 30) {
            return;
        }
        foreach (DB::table('problems')->orderBy('id')->get(['id', 'sdg_categories']) as $p) {
            $rows = DB::table('problem_images')->where('problem_id', $p->id)->where('image_path', 'like', 'assets/photography/%')->orderBy('order')->get(['id']);
            $theme = $this->themeFromSdg((array) json_decode((string) $p->sdg_categories, true));
            foreach ($rows as $i => $row) {
                DB::table('problem_images')->where('id', $row->id)->update(['image_path' => $this->pickPhoto($theme, $i === 0)]);
            }
        }
    }

    // ───────────────────────── pengguna ─────────────────────────

    private function seedUsers($univs): array
    {
        $hash = Hash::make(self::PASSWORD);
        $out = ['students' => [], 'institutions' => [], 'companies' => []];

        $this->userRow('Admin Kolabora', 'admin@kolabora.test', 'admin', 'admin', $hash, $this->ago(30, 29));

        // instansi: [nama, tipe, id kabupaten, PIC, jabatan, deskripsi]
        $insts = [
            ['Pemerintah Desa Cihanjuang', 'pemerintah_desa', 3217, 'Dedi Kurniawan', 'Kepala Desa', 'instansi', 'instansi@kolabora.test'],
            ['Dinas Lingkungan Hidup Kabupaten Bandung', 'dinas', 3204, 'Ratna Wulandari', 'Kepala Bidang Persampahan', 'dlh-bandung', null],
            ['Pemerintah Desa Karangsari', 'pemerintah_desa', 3209, 'Sutrisno', 'Kepala Desa', 'desa-karangsari', null],
            ['Pemerintah Desa Sukamaju', 'pemerintah_desa', 3205, 'Yayan Hidayat', 'Sekretaris Desa', 'desa-sukamaju', null],
            ['Puskesmas Mekarwangi', 'puskesmas', 3206, 'dr. Ika Permatasari', 'Kepala Puskesmas', 'pkm-mekarwangi', null],
            ['Pengelola Desa Wisata Penglipuran Timur', 'ngo', 5106, 'I Wayan Sudiarta', 'Ketua Pengelola', 'wisata-penglipuran', null],
            ['Pemerintah Desa Tegalrejo', 'pemerintah_desa', 3310, 'Slamet Riyadi', 'Kepala Desa', 'desa-tegalrejo', null],
            ['SDN 2 Sindangsari', 'sekolah', 3202, 'Hj. Neneng Rosmiati', 'Kepala Sekolah', 'sdn-sindangsari', null],
            ['Kelurahan Cipamokolan', 'pemerintah_desa', 3273, 'Asep Saepudin', 'Lurah', 'kel-cipamokolan', null],
            ['Pemerintah Desa Karanganyar', 'pemerintah_desa', 3302, 'Bambang Supriyanto', 'Kepala Desa', 'desa-karanganyar', null],
            ['Dinas Perikanan Kabupaten Bekasi', 'dinas', 3216, 'Hendra Gunawan', 'Kepala Bidang Budidaya', 'dkp-bekasi', null],
            ['Puskesmas Sumberjaya', 'puskesmas', 3210, 'dr. Rahmat Hidayat', 'Kepala Puskesmas', 'pkm-sumberjaya', null],
        ];
        foreach ($insts as $i => [$name, $type, $regId, $pic, $picPos, $uname, $email]) {
            $reg = DB::table('regencies')->where('id', $regId)->first() ?? DB::table('regencies')->first();
            $at = $this->ago(30, 24);
            $email ??= $uname . '@instansi.kolabora.test';
            $uid = $this->userRow($name, $email, $uname, 'institution', $hash, $at);
            $id = DB::table('institutions')->insertGetId([
                'user_id' => $uid, 'name' => $name, 'type' => $type, 'address' => 'Jl. Raya ' . Str::before($name, ' ') . ' No. ' . (10 + $i),
                'province_id' => $reg->province_id, 'regency_id' => $reg->id, 'email' => $email, 'phone' => '02270010' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'pic_name' => $pic, 'pic_position' => $picPos,
                'description' => $name . ' membuka program KKN untuk menjawab kebutuhan nyata warga: dari layanan dasar, lingkungan, sampai pemberdayaan ekonomi. Mahasiswa mendapat pendamping lapangan dan pengakuan resmi di akhir program.',
                'is_verified' => true, 'verified_at' => $at, 'verification_status' => 'verified', 'created_at' => $at, 'updated_at' => $at,
            ]);
            $out['institutions'][] = ['id' => $id, 'user_id' => $uid, 'province_id' => $reg->province_id, 'regency_id' => $reg->id, 'name' => $name, 'village' => Str::after($name, ' ')];
        }

        // mahasiswa
        $female = ['Alya', 'Citra', 'Dewi', 'Eka', 'Fitri', 'Gita', 'Hana', 'Indah', 'Jihan', 'Kirana', 'Laras', 'Maya', 'Nadia', 'Putri', 'Rani', 'Salsa', 'Tiara', 'Wulan'];
        $male = ['Bima', 'Dika', 'Eko', 'Fajar', 'Galih', 'Hafiz', 'Irfan', 'Joko', 'Kevin', 'Lutfi', 'Naufal', 'Raka', 'Satria', 'Tegar', 'Yoga', 'Zaki', 'Arief', 'Bagas'];
        $last = ['Pratama', 'Lestari', 'Ramadhan', 'Wibowo', 'Safitri', 'Hidayat', 'Nugraha', 'Kusuma', 'Maharani', 'Saputra', 'Permana', 'Anggraini', 'Firmansyah', 'Utami', 'Setiawan', 'Rahayu', 'Gunawan', 'Puspita', 'Hakim', 'Wardana'];
        $majors = [
            ['Teknik Informatika', ['Laravel', 'UI/UX', 'Basis Data', 'Analisis Data']],
            ['Teknik Lingkungan', ['GIS', 'Pengelolaan Sampah', 'Survei Lapangan', 'Edukasi']],
            ['Manajemen', ['Pemasaran Digital', 'Akuntansi Dasar', 'Penulisan', 'Pendampingan']],
            ['Pendidikan Matematika', ['Mengajar', 'Desain Modul', 'Public Speaking', 'Edukasi']],
            ['Kesehatan Masyarakat', ['Edukasi', 'Survei Lapangan', 'Analisis Data', 'Pendampingan']],
            ['Agribisnis', ['Pendampingan', 'Akuntansi Dasar', 'Pemasaran Digital', 'Survei Lapangan']],
            ['Desain Komunikasi Visual', ['UI/UX', 'Fotografi', 'Desain Modul', 'Pemasaran Digital']],
            ['Teknik Sipil', ['GIS', 'Survei Lapangan', 'Analisis Data', 'Pendampingan']],
            ['Ilmu Kelautan', ['GIS', 'Survei Lapangan', 'Edukasi', 'Fotografi']],
            ['Sistem Informasi', ['Laravel', 'Basis Data', 'UI/UX', 'Analisis Data']],
        ];
        $demo = [
            ['Alya', 'Putri', 'mahasiswa', 'mahasiswa@kolabora.test', 0, 7],
            ['Bima', 'Pratama', 'bima', 'bima@kolabora.test', 1, 6],
            ['Citra', 'Lestari', 'citra', 'citra@kolabora.test', 2, 6],
            ['Dika', 'Ramadhan', 'dika', 'dika@kolabora.test', 3, 5],
        ];
        $interests = ['KKN', 'Pengabdian Masyarakat', 'Teknologi Tepat Guna', 'Lingkungan', 'Pendidikan', 'UMKM', 'Kesehatan Desa', 'Fotografi Dokumenter'];
        $seen = [];
        for ($i = 0; $i < 40; $i++) {
            if ($i < 4) {
                [$first, $lastName, $uname, $email, $mi, $sem] = $demo[$i];
            } else {
                $first = $i % 2 ? $this->pick($male) : $this->pick($female);
                $lastName = $last[($i * 7 + 3) % count($last)];
                $uname = strtolower($first . '.' . $lastName . $i);
                $email = $uname . '@mhs.kolabora.test';
                $mi = $i % count($majors);
                $sem = 5 + $i % 4;
            }
            $at = $this->ago(30, 20);
            $uid = $this->userRow("$first $lastName", $email, $uname, 'student', $hash, $at);
            $univ = $i < 4 ? ($univs->firstWhere('code', 'ITB') ?? $univs[0]) : $univs[($i * 5) % $univs->count()];
            [$major, $skills] = $majors[$mi];
            $sid = DB::table('students')->insertGetId([
                'user_id' => $uid, 'first_name' => $first, 'last_name' => $lastName, 'university_id' => $univ->id, 'major' => $major,
                'nim' => (string) (13522000 + $i + 1), 'semester' => $sem, 'phone' => '+62812' . str_pad((string) (30000 + $i * 37), 7, '0', STR_PAD_LEFT),
                'bio' => "Mahasiswa $major semester $sem. Ingin menerapkan ilmu di kampus untuk masalah nyata di desa dan belajar langsung dari warga.",
                'skills' => json_encode(array_slice($skills, 0, 3)), 'interests' => json_encode([$interests[$i % 8], $interests[($i + 3) % 8]]),
                'stories' => json_encode(['Mengikuti pelatihan dasar pendampingan masyarakat dan ikut kerja bakti lingkungan di sekitar kampus.']),
                'portfolio_visible' => true, 'created_at' => $at, 'updated_at' => $at,
            ]);
            $out['students'][] = ['id' => $sid, 'user_id' => $uid, 'name' => "$first $lastName", 'univ' => $univ->name, 'major' => $major];
        }

        // perusahaan
        $cos = [
            ['PT Nusa Digital Karya', 'Teknologi Informasi', 'Teknologi yang dipakai warga', 'perusahaan', 'perusahaan@kolabora.test'],
            ['PT Tani Makmur Teknologi', 'Pertanian', 'Pertanian presisi untuk petani kecil', 'tani-makmur', null],
            ['PT Energi Surya Nusantara', 'Energi Terbarukan', 'Listrik bersih sampai ke dusun', 'energi-surya', null],
            ['CV Edukasi Cerdas', 'Pendidikan', 'Belajar yang menjangkau semua anak', 'edukasi-cerdas', null],
            ['PT Layanan Kesehatan Prima', 'Kesehatan', 'Layanan kesehatan dekat dengan rumah', 'kesehatan-prima', null],
            ['PT Hijau Lestari Konsultan', 'Lingkungan', 'Konsultasi lingkungan untuk daerah', 'hijau-lestari', null],
            ['PT Logistik Pangan Nusantara', 'Logistik', 'Pangan sampai tepat waktu', 'logistik-pangan', null],
            ['Yayasan Mitra Desa Mandiri', 'Pemberdayaan Masyarakat', 'Desa mandiri, warga berdaya', 'mitra-desa', null],
        ];
        $cities = ['Bandung', 'Yogyakarta', 'Semarang', 'Surabaya', 'Jakarta', 'Bogor', 'Malang', 'Cirebon'];
        foreach ($cos as $i => [$name, $industry, $tag, $uname, $email]) {
            $at = $this->ago(30, 25);
            $email ??= $uname . '@perusahaan.kolabora.test';
            $uid = $this->userRow($name, $email, $uname, 'company', $hash, $at);
            $prov = DB::table('provinces')->orderBy('id')->offset(10 + $i)->value('id') ?? DB::table('provinces')->value('id');
            $id = DB::table('companies')->insertGetId([
                'user_id' => $uid, 'name' => $name, 'industry' => $industry, 'tagline' => $tag, 'email' => $email, 'phone' => '021555' . str_pad((string) (100 + $i), 4, '0', STR_PAD_LEFT),
                'description' => "$name bekerja di bidang $industry dan membuka kesempatan bagi mahasiswa yang berpengalaman KKN untuk berkontribusi pada proyek berdampak.",
                'city' => $cities[$i], 'location' => $cities[$i] . ', Indonesia', 'province_id' => $prov, 'company_size' => ['11-50', '51-200', '201-500'][$i % 3],
                'employee_count' => ['11-50', '51-200', '201-500'][$i % 3], 'founded_year' => 2008 + $i, 'verification_status' => 'verified', 'verified_at' => $at,
                'created_at' => $at, 'updated_at' => $at,
            ]);
            $out['companies'][] = ['id' => $id, 'user_id' => $uid, 'name' => $name, 'industry' => $industry];
        }

        return $out;
    }

    // ───────────────────── masalah, lamaran, proyek ─────────────────────

    private function seedProblemsAndActivity(array &$u): void
    {
        // [instansi, judul, SDG, skill, tema foto, dokumen (hanya selesai)]
        $completed = [
            [0, 'Digitalisasi arsip desa dan layanan surat online', [16, 9], ['Laravel', 'Basis Data'], 'desa', 'laporan-arsip-desa'],
            [1, 'Pengelolaan bank sampah berbasis warga', [11, 12], ['Survei Lapangan', 'Edukasi'], 'komunitas', 'laporan-bank-sampah'],
            [2, 'Pengelolaan air bersih dan sanitasi dusun', [6, 3], ['Survei Lapangan', 'GIS'], 'desa', 'laporan-air-bersih'],
            [3, 'Pelatihan literasi digital untuk pelaku UMKM', [8, 4], ['Pemasaran Digital', 'Fotografi'], 'pasar', 'laporan-literasi-digital-umkm'],
            [4, 'Sistem informasi posyandu dan pencatatan balita', [3, 9], ['Laravel', 'UI/UX'], 'desa', 'laporan-posyandu-digital'],
            [5, 'Pemetaan potensi wisata desa', [8, 11], ['GIS', 'Fotografi'], 'desa', 'laporan-wisata-desa'],
            [6, 'Penguatan koperasi tani dan pencatatan keuangan', [1, 2], ['Akuntansi Dasar', 'Pendampingan'], 'pertanian', 'laporan-koperasi-tani'],
            [7, 'Pendampingan belajar sekolah dasar', [4, 10], ['Mengajar', 'Desain Modul'], 'pendidikan', 'laporan-pendampingan-sd'],
            [8, 'Mitigasi banjir berbasis warga', [13, 11], ['Survei Lapangan', 'GIS'], 'komunitas', 'laporan-mitigasi-banjir'],
            [9, 'Pemasaran produk olahan lokal', [8, 12], ['Pemasaran Digital', 'Penulisan'], 'pasar', 'laporan-produk-olahan'],
            [10, 'Rehabilitasi mangrove pesisir', [14, 13], ['Survei Lapangan', 'Edukasi'], 'pesisir', 'laporan-mangrove'],
            [11, 'Edukasi gizi dan kesehatan ibu hamil', [3, 2], ['Edukasi', 'Pendampingan'], 'komunitas', 'laporan-literasi-kesehatan'],
        ];
        $running = [
            [0, 'Pembuatan peta digital fasilitas umum desa', [11, 9], ['GIS', 'Basis Data'], 'desa'],
            [1, 'Pemilahan sampah rumah tangga di tiga RW', [12, 11], ['Edukasi', 'Pendampingan'], 'komunitas'],
            [3, 'Katalog digital produk UMKM desa', [8, 9], ['Fotografi', 'Pemasaran Digital'], 'pasar'],
            [4, 'Kelas edukasi pencegahan stunting', [2, 3], ['Edukasi', 'Pendampingan'], 'komunitas'],
            [6, 'Pengolahan hasil panen padi dan kemasan produk', [2, 12], ['Pemasaran Digital', 'Pendampingan'], 'pertanian'],
            [8, 'Sistem peringatan dini banjir tingkat RW', [13, 11], ['GIS', 'Survei Lapangan'], 'komunitas'],
            [10, 'Budidaya ikan air payau untuk kelompok pembudidaya', [14, 8], ['Pendampingan', 'Edukasi'], 'pesisir'],
        ];
        $open = [
            [0, 'Aplikasi pelayanan administrasi kependudukan desa', [16, 9], ['Laravel', 'UI/UX'], 'desa'],
            [0, 'Pelatihan pengelolaan media sosial desa', [8, 16], ['Penulisan', 'Fotografi'], 'komunitas'],
            [1, 'Kompos rumah tangga dan pemanfaatan sampah organik', [12, 11], ['Pengelolaan Sampah', 'Edukasi'], 'komunitas'],
            [1, 'Pemetaan titik pembuangan sampah liar', [11, 15], ['GIS', 'Survei Lapangan'], 'desa'],
            [2, 'Pembuatan sumur resapan dan pengelolaan air hujan', [6, 13], ['Survei Lapangan', 'Pendampingan'], 'desa'],
            [2, 'Pencatatan iuran air warga secara digital', [6, 9], ['Laravel', 'Basis Data'], 'desa'],
            [3, 'Pasar tani daring untuk hasil kebun warga', [2, 8], ['Pemasaran Digital', 'UI/UX'], 'pasar'],
            [3, 'Pendampingan pembukuan sederhana untuk warung desa', [8, 1], ['Akuntansi Dasar', 'Pendampingan'], 'pasar'],
            [4, 'Penyuluhan sanitasi dan cuci tangan di posyandu', [3, 6], ['Edukasi', 'Public Speaking'], 'komunitas'],
            [4, 'Pendataan lansia dan jadwal pemeriksaan rutin', [3, 10], ['Survei Lapangan', 'Analisis Data'], 'komunitas'],
            [5, 'Paket wisata edukasi pertanian untuk sekolah', [8, 4], ['Penulisan', 'Pendampingan'], 'pertanian'],
            [5, 'Dokumentasi budaya dan kerajinan desa wisata', [11, 8], ['Fotografi', 'Penulisan'], 'desa'],
            [6, 'Pemanfaatan limbah jerami menjadi pakan dan kompos', [12, 2], ['Pengelolaan Sampah', 'Pendampingan'], 'pertanian'],
            [6, 'Kalender tanam dan informasi harga hasil tani', [2, 9], ['Laravel', 'Analisis Data'], 'pertanian'],
            [7, 'Perpustakaan mini dan kegiatan literasi anak', [4, 10], ['Mengajar', 'Desain Modul'], 'pendidikan'],
            [7, 'Pelatihan guru memakai media belajar digital', [4, 9], ['Mengajar', 'UI/UX'], 'pendidikan'],
            [8, 'Pembuatan taman kota mini dan kebun gizi warga', [11, 2], ['Pendampingan', 'Edukasi'], 'komunitas'],
            [8, 'Pendataan rumah rawan dan jalur evakuasi', [11, 13], ['GIS', 'Survei Lapangan'], 'komunitas'],
            [9, 'Desain label dan foto produk untuk lima kelompok usaha', [8, 12], ['Fotografi', 'Pemasaran Digital'], 'pasar'],
            [9, 'Pelatihan standar mutu pangan olahan rumahan', [3, 12], ['Edukasi', 'Pendampingan'], 'pasar'],
            [10, 'Pengelolaan sampah plastik di pesisir', [14, 12], ['Pengelolaan Sampah', 'Edukasi'], 'pesisir'],
            [10, 'Pemetaan zona tangkap dan musim ikan nelayan kecil', [14, 1], ['GIS', 'Survei Lapangan'], 'pesisir'],
            [11, 'Aplikasi pengingat jadwal imunisasi dan kunjungan ibu hamil', [3, 9], ['Laravel', 'UI/UX'], 'desa'],
            [11, 'Kampanye gizi seimbang untuk remaja putri', [3, 5], ['Edukasi', 'Desain Modul'], 'pendidikan'],
            [2, 'Pelatihan pengolahan hasil kebun menjadi produk bernilai', [2, 8], ['Pendampingan', 'Pemasaran Digital'], 'pertanian'],
            [5, 'Sistem reservasi homestay desa wisata', [8, 9], ['Laravel', 'UI/UX'], 'desa'],
            [9, 'Pembukuan keuangan kelompok usaha perempuan', [5, 8], ['Akuntansi Dasar', 'Pendampingan'], 'pasar'],
            [7, 'Kelas coding dasar untuk siswa kelas 5 dan 6', [4, 9], ['Mengajar', 'Laravel'], 'pendidikan'],
        ];

        $bg = ['Warga dan perangkat desa sudah membahas kebutuhan ini dalam musyawarah, tetapi belum ada tenaga pendamping yang cukup untuk mengerjakannya secara terstruktur.',
            'Selama ini pekerjaan ini dilakukan seadanya oleh relawan sehingga hasilnya belum konsisten dan sulit dilanjutkan.',
            'Kebutuhan ini muncul dari keluhan warga yang berulang, dan instansi ingin memulainya bersama mahasiswa yang mau belajar langsung di lapangan.'];
        $obj = ['Menghasilkan solusi yang dapat dipakai dan dirawat sendiri oleh warga setelah program selesai.',
            'Mengukur kondisi awal, menjalankan kegiatan, lalu mencatat perubahan yang bisa ditunjukkan dengan angka.',
            'Melatih sedikitnya satu kelompok warga agar dapat meneruskan kegiatan tanpa pendamping.'];
        $fac = [['Tempat tinggal di rumah warga', 'Makan siang saat kegiatan lapangan', 'Surat pengantar dan koordinasi dengan perangkat desa'],
            ['Posko kerja di balai desa', 'Pendamping lapangan dari instansi', 'Biaya transportasi lokal'],
            ['Akses ke data instansi', 'Ruang pertemuan dan peralatan dasar', 'Sertifikat dan surat keterangan selesai program']];
        $deliv = [['Laporan awal dan rencana kerja', 'Laporan mingguan', 'Laporan akhir dan serah terima'], ['Dokumentasi kegiatan', 'Materi atau modul yang dapat dipakai ulang', 'Laporan akhir']];

        $mkProblem = function (array $d, string $status, int $idx) use (&$u, $bg, $obj, $fac, $deliv) {
            [$ii, $title, $sdg, $skills, $theme] = $d;
            $inst = $u['institutions'][$ii];
            $created = $status === 'open' ? $this->ago(28, 1) : $this->ago(30, 26);
            $start = match ($status) { 'completed' => '2026-07-06', 'in_progress' => '2026-11-02', default => Carbon::parse('2027-01-11')->addDays($idx % 5 * 3)->toDateString() };
            $end = match ($status) { 'completed' => '2026-09-04', 'in_progress' => '2027-01-02', default => Carbon::parse('2027-03-08')->addDays($idx % 5 * 3)->toDateString() };
            $deadline = $status === 'open' ? Carbon::parse('2026-12-01')->addDays(($idx * 3) % 38)->toDateString() : ($status === 'completed' ? '2026-06-20' : '2026-10-20');
            $pid = DB::table('problems')->insertGetId([
                'institution_id' => $inst['id'], 'title' => $title,
                'description' => "$title. " . $bg[$idx % 3] . ' Mahasiswa akan bekerja bersama perangkat desa dan warga setempat selama periode KKN.',
                'background' => $bg[($idx + 1) % 3], 'objectives' => $obj[$idx % 3],
                'scope' => 'Kegiatan dilakukan di wilayah ' . $inst['village'] . ' dan sekitarnya, dengan koordinasi mingguan bersama pendamping lapangan.',
                'province_id' => $inst['province_id'], 'regency_id' => $inst['regency_id'], 'village' => Str::words($inst['village'], 2, ''),
                'detailed_location' => 'Balai ' . $inst['village'],
                'sdg_categories' => json_encode($sdg), 'required_students' => 3 + $idx % 4, 'required_skills' => json_encode($skills),
                'required_majors' => null,'start_date' => $start, 'end_date' => $end, 'application_deadline' => $deadline,
                'duration_months' => 2, 'difficulty_level' => ['beginner', 'intermediate', 'advanced'][$idx % 3], 'status' => $status,
                'expected_outcomes' => 'Hasil kerja dipakai langsung oleh warga dan dapat dilanjutkan oleh pengelola setempat.',
                'deliverables' => json_encode($deliv[$idx % 2]), 'facilities_provided' => json_encode($fac[$idx % 3]),
                'views_count' => 25 + ($idx * 17) % 260, 'applications_count' => 0, 'accepted_students' => 0,
                'is_featured' => $status === 'open' && $idx < 4, 'is_urgent' => $status === 'open' && $idx % 9 === 4,
                'created_at' => $created, 'updated_at' => $created,
            ]);
            foreach ([0, 1, 2] as $n) {
                if ($n && ($idx + $n) % 3 === 0) {
                    continue;
                }
                DB::table('problem_images')->insert([
                    'problem_id' => $pid, 'image_path' => $this->pickPhoto($theme, $n === 0), 'caption' => $n === 0 ? 'Kondisi lapangan' : 'Dokumentasi pendukung',
                    'order' => $n, 'is_cover' => $n === 0, 'created_at' => $created, 'updated_at' => $created,
                ]);
            }
            return ['id' => $pid, 'inst' => $inst, 'title' => $title, 'sdg' => $sdg, 'start' => $start, 'end' => $end, 'created' => $created];
        };

        $apply = function (int $sid, int $pid, string $status, ?Carbon $at = null) use ($u) {
            $at ??= $this->ago(14, 1);
            return DB::table('applications')->insertGetId([
                'student_id' => $sid, 'problem_id' => $pid, 'status' => $status,
                'motivation' => $this->pick([
                    'Saya ingin terlibat langsung membantu warga dan menerapkan ilmu dari kampus untuk menyelesaikan masalah nyata di lapangan, serta belajar bekerja bersama perangkat desa.',
                    'Saya pernah mengikuti pelatihan pendampingan masyarakat dan tertarik pada bidang ini. Saya siap tinggal di desa selama program dan bekerja sesuai jadwal.',
                    'Masalah ini sejalan dengan bidang studi saya. Saya berpengalaman mengerjakan proyek kelompok dan siap berkoordinasi rutin dengan pendamping lapangan.']),
                'applied_at' => $at, 'reviewed_at' => $status === 'pending' ? null : $at->copy()->addDays(2),
                'accepted_at' => $status === 'accepted' ? $at->copy()->addDays(2) : null, 'rejected_at' => $status === 'rejected' ? $at->copy()->addDays(2) : null,
                'feedback' => $status === 'rejected' ? 'Terima kasih sudah melamar. Kuota untuk periode ini sudah terpenuhi.' : ($status === 'reviewed' ? 'Lamaran sedang kami pertimbangkan, mohon menunggu kabar berikutnya.' : null),
                'created_at' => $at, 'updated_at' => $at,
            ]);
        };

        $nStu = count($u['students']);
        $sid = fn (int $i) => $u['students'][$i % $nStu]['id'];

        // proyek selesai dan berjalan
        $forced = [0 => [0, 1], 1 => [0, 2], 3 => [2, 0], 4 => [3, 1], 7 => [3], 9 => [2, 1]];
        $forcedRun = [0 => [0, 3], 2 => [0, 1], 3 => [2], 5 => [1, 3]];
        $reports = [];
        $docRows = [];
        foreach (['completed' => $completed, 'in_progress' => $running] as $status => $defs) {
            foreach ($defs as $k => $d) {
                $p = $mkProblem($d, $status, $k + ($status === 'completed' ? 0 : 40));
                $team = ($status === 'completed' ? ($forced[$k] ?? []) : ($forcedRun[$k] ?? []));
                for ($t = 4 + $k * 3; count($team) < 2 + ($k % 2); $t++) {
                    if (! in_array($t % $nStu, $team, true)) {
                        $team[] = $t % $nStu;
                    }
                }
                $projectIds = [];
                foreach ($team as $ti => $si) {
                    $appId = $apply($sid($si), $p['id'], 'accepted', Carbon::parse($p['start'])->subDays(30));
                    $done = $status === 'completed';
                    $pct = $done ? 100 : [25, 40, 55, 70, 80, 65, 35][$k % 7];
                    $projectIds[] = $pr = DB::table('projects')->insertGetId([
                        'application_id' => $appId, 'student_id' => $sid($si), 'problem_id' => $p['id'], 'institution_id' => $p['inst']['id'], 'title' => $p['title'],
                        'description' => 'Pendampingan terstruktur bersama perangkat desa dan warga selama periode KKN.', 'status' => $done ? 'completed' : 'active',
                        'start_date' => $p['start'], 'end_date' => $p['end'], 'actual_start_date' => $p['start'], 'actual_end_date' => $done ? $p['end'] : null,
                        'progress_percentage' => $pct, 'is_portfolio_visible' => true, 'role_in_team' => ['Koordinator tim', 'Anggota tim', 'Dokumentasi'][$ti % 3],
                        'completed_at' => $done ? $p['end'] : null, 'submitted_at' => $done ? Carbon::parse($p['end'])->addDays(2) : null,
                        'final_report_summary' => $done ? 'Seluruh target program tercapai dan hasilnya sudah diserahterimakan kepada pengelola setempat.' : null,
                        'impact_metrics' => $done ? json_encode(['beneficiaries' => 40 + $k * 9, 'activities' => 8 + $k % 5]) : null,
                        'rating' => $done ? 4 + (($k + $ti) % 2) : null,
                        'institution_review' => $done ? 'Mahasiswa bekerja profesional, komunikatif, dan hasilnya langsung dipakai warga.' : null,
                        'reviewed_at' => $done ? Carbon::parse($p['end'])->addDays(5) : null,
                        'created_at' => $this->ago(29, 20), 'updated_at' => $this->ago(5),
                    ]);
                    foreach ([['Observasi dan pemetaan kebutuhan', 'completed'], ['Pelaksanaan program', $done ? 'completed' : 'in_progress'], ['Evaluasi dan serah terima', $done ? 'completed' : 'pending']] as $o => [$title, $st]) {
                        DB::table('project_milestones')->insert([
                            'project_id' => $pr, 'title' => $title, 'description' => 'Tahap ' . ($o + 1) . ' pendampingan.', 'order' => $o + 1,
                            'target_date' => Carbon::parse($p['start'])->addDays(18 * ($o + 1))->toDateString(), 'status' => $st,
                            'progress_percentage' => $st === 'completed' ? 100 : ($st === 'in_progress' ? 50 : 0),
                            'completed_at' => $st === 'completed' ? Carbon::parse($p['start'])->addDays(16 * ($o + 1))->toDateString() : null,
                            'created_at' => $this->ago(29, 20), 'updated_at' => $this->ago(5),
                        ]);
                    }
                    foreach (range(1, $done ? 3 : 1 + $k % 3) as $w) {
                        DB::table('project_reports')->insert([
                            'project_id' => $pr, 'student_id' => $sid($si), 'type' => ($done && $w === 3) ? 'final' : 'weekly', 'title' => ($done && $w === 3) ? 'Laporan akhir program' : "Laporan kemajuan minggu ke-$w",
                            'summary' => 'Kegiatan berjalan sesuai rencana. Warga aktif terlibat dan target mingguan tercapai.',
                            'activities' => 'Pertemuan dengan perangkat desa, pelatihan kelompok warga, dan pendataan di lapangan.',
                            'challenges' => 'Jadwal warga kadang bentrok dengan pekerjaan di ladang.', 'next_plans' => 'Melanjutkan pelatihan dan menyiapkan bahan serah terima.',
                            'period_start' => Carbon::parse($p['start'])->addDays(($w - 1) * 7)->toDateString(), 'period_end' => Carbon::parse($p['start'])->addDays($w * 7 - 1)->toDateString(),
                            'status' => $w === 1 || $done ? 'approved' : 'pending', 'institution_feedback' => ($w === 1 || $done) ? 'Laporan jelas dan sesuai rencana.' : null,
                            'reviewed_at' => ($w === 1 || $done) ? $this->ago(10, 2) : null, 'created_at' => $this->ago(20, 3), 'updated_at' => $this->ago(3),
                        ]);
                    }
                    if ($done) {
                        $uidStu = $u['students'][$si % $nStu]['user_id'];
                        DB::table('reviews')->insert([
                            'project_id' => $pr, 'reviewer_id' => $p['inst']['user_id'], 'reviewee_id' => $uidStu, 'type' => 'institution_to_student', 'rating' => 4 + (($k + $ti) % 2),
                            'professionalism_rating' => 5, 'communication_rating' => 4 + $k % 2, 'quality_rating' => 5, 'timeliness_rating' => 4,
                            'review_text' => $this->pick(['Mahasiswa bekerja profesional, komunikatif, dan hasilnya langsung dipakai warga.', 'Inisiatif tinggi dan mudah berbaur dengan warga. Kami berharap kerja sama ini berlanjut.', 'Hasil kerja rapi dan tepat waktu. Warga merasakan manfaatnya.']),
                            'strengths' => 'Inisiatif tinggi, komunikasi baik dengan warga.', 'improvements' => 'Dokumentasi bisa dibuat lebih rutin.',
                            'is_public' => true, 'is_featured' => $k < 4, 'created_at' => $this->ago(18, 4), 'updated_at' => $this->ago(4),
                        ]);
                        if (($k + $ti) % 2 === 0) {
                            DB::table('reviews')->insert([
                                'project_id' => $pr, 'reviewer_id' => $uidStu, 'reviewee_id' => $p['inst']['user_id'], 'type' => 'student_to_institution', 'rating' => 5,
                                'professionalism_rating' => 5, 'communication_rating' => 5, 'quality_rating' => 4, 'timeliness_rating' => 5,
                                'review_text' => 'Pendamping lapangan sangat membantu dan warga menyambut kami dengan ramah. Pengalaman yang berharga.',
                                'is_public' => true, 'created_at' => $this->ago(17, 3), 'updated_at' => $this->ago(3),
                            ]);
                        }
                    }
                }
                DB::table('problems')->where('id', $p['id'])->update(['accepted_students' => count($team), 'applications_count' => count($team)]);
                if ($status === 'completed') {
                    $docRows[] = [$d, $p, $projectIds[0], $team[0]];
                }
            }
        }

        // dokumen repositori dari proyek selesai
        $univFor = fn (int $si) => $u['students'][$si % $nStu];
        foreach ($docRows as $n => [$d, $p, $projectId, $si]) {
            $path = 'demo/dokumen/' . $d[5] . '.pdf';
            $stu = $univFor($si);
            DB::table('documents')->insert([
                'project_id' => $projectId, 'uploaded_by' => $stu['user_id'], 'title' => 'Laporan Akhir: ' . $d[1],
                'description' => 'Laporan akhir program KKN: kondisi awal, kegiatan, hasil, dan rekomendasi lanjutan bagi pengelola setempat.',
                'file_path' => $path, 'file_type' => 'pdf', 'file_size' => (int) @filesize(public_path($path)) ?: 2100,
                'categories' => json_encode($d[2]), 'tags' => json_encode(['KKN', 'laporan akhir', Str::lower($d[4]), 'desa']),
                'author_name' => $stu['name'], 'institution_name' => $p['inst']['name'], 'university_name' => $stu['univ'], 'year' => 2026,
                'province_id' => $p['inst']['province_id'], 'regency_id' => $p['inst']['regency_id'],
                'download_count' => 8 + ($n * 13) % 70, 'view_count' => 60 + ($n * 41) % 400, 'citation_count' => $n % 5,
                'is_public' => true, 'is_featured' => $n < 4, 'status' => 'approved', 'approved_at' => $this->ago(20, 5),
                'created_at' => $this->ago(22, 6), 'updated_at' => $this->ago(5),
            ]);
        }

        // masalah terbuka dan lamarannya
        $openIds = [];
        foreach ($open as $k => $d) {
            $p = $mkProblem($d, 'open', $k);
            $openIds[] = $p['id'];
            $applied = [];
            $count = 2 + ($k * 3) % 6;
            $demoSlots = [0 => 'pending', 1 => 'reviewed', 2 => 'rejected', 3 => 'pending', 4 => 'pending'];
            if (isset($demoSlots[$k])) {
                $apply($sid(0), $p['id'], $demoSlots[$k]);
                $applied[] = 0;
            }
            for ($t = 5 + $k * 4; count($applied) < $count; $t++) {
                $si = $t % $nStu;
                if (in_array($si, $applied, true) || ($si === 0)) {
                    continue;
                }
                $apply($sid($si), $p['id'], $this->pick(['pending', 'pending', 'pending', 'reviewed', 'reviewed', 'rejected']));
                $applied[] = $si;
            }
            DB::table('problems')->where('id', $p['id'])->update(['applications_count' => count($applied)]);
        }
        $u['open'] = $openIds;
    }

    // ───────────────────────── lowongan ─────────────────────────

    private function seedJobs(array $u): void
    {
        $cats = DB::table('job_categories')->orderBy('id')->pluck('id')->all();
        $jobs = [
            [0, 'Pengembang Web Junior (Magang)', 'internship', ['Laravel', 'Basis Data', 'Git'], 'Membangun dan merawat aplikasi layanan publik berbasis web bersama tim produk.'],
            [0, 'Analis Data Program Sosial', 'full_time', ['Analisis Data', 'Excel', 'Visualisasi'], 'Mengolah data lapangan program pemberdayaan menjadi laporan dampak yang mudah dipahami.'],
            [0, 'Desainer UI/UX Aplikasi Desa', 'contract', ['UI/UX', 'Figma', 'Riset Pengguna'], 'Merancang antarmuka aplikasi untuk pengguna dengan literasi digital beragam.'],
            [0, 'Penulis Konten Edukasi Digital', 'part_time', ['Penulisan', 'Penyuntingan'], 'Menulis materi edukasi untuk pelatihan literasi digital warga.'],
            [1, 'Asisten Agronomi Lapangan', 'full_time', ['Pendampingan', 'Survei Lapangan'], 'Mendampingi kelompok tani dalam budidaya dan pencatatan hasil panen.'],
            [1, 'Analis Data Pertanian', 'internship', ['Analisis Data', 'GIS'], 'Menganalisis data cuaca dan hasil panen untuk rekomendasi tanam.'],
            [1, 'Fasilitator Koperasi Tani', 'contract', ['Pendampingan', 'Akuntansi Dasar'], 'Melatih pengurus koperasi dalam pembukuan dan perencanaan usaha.'],
            [2, 'Teknisi Lapangan Panel Surya', 'full_time', ['Survei Lapangan', 'Teknik Listrik'], 'Memasang dan merawat sistem surya untuk dusun terpencil.'],
            [2, 'Staf Edukasi Energi Bersih', 'part_time', ['Edukasi', 'Public Speaking'], 'Memberi penyuluhan hemat energi di sekolah dan balai desa.'],
            [2, 'Analis Studi Kelayakan Energi', 'internship', ['Analisis Data', 'GIS'], 'Menyiapkan studi kelayakan lokasi pembangkit skala kecil.'],
            [3, 'Pengembang Konten Pembelajaran', 'full_time', ['Desain Modul', 'Mengajar'], 'Menyusun modul belajar bergambar untuk sekolah dasar di daerah.'],
            [3, 'Fasilitator Pelatihan Guru', 'contract', ['Mengajar', 'Public Speaking'], 'Melatih guru memakai media belajar digital sederhana.'],
            [3, 'Desainer Grafis Pendidikan', 'internship', ['Desain Grafis', 'UI/UX'], 'Membuat ilustrasi dan tata letak materi ajar.'],
            [4, 'Penyuluh Kesehatan Masyarakat', 'full_time', ['Edukasi', 'Pendampingan'], 'Menjalankan penyuluhan gizi dan kesehatan ibu di desa binaan.'],
            [4, 'Analis Data Kesehatan Komunitas', 'internship', ['Analisis Data', 'Survei Lapangan'], 'Mengolah data posyandu dan puskesmas menjadi laporan bulanan.'],
            [4, 'Pengembang Aplikasi Pencatatan Kesehatan', 'contract', ['Laravel', 'UI/UX'], 'Mengembangkan aplikasi pencatatan kesehatan untuk kader.'],
            [5, 'Konsultan Pengelolaan Sampah', 'full_time', ['Pengelolaan Sampah', 'Survei Lapangan'], 'Menyusun rencana pengelolaan sampah untuk pemerintah daerah.'],
            [5, 'Staf Pemantauan Lingkungan', 'internship', ['GIS', 'Analisis Data'], 'Memantau kualitas air dan tutupan lahan di wilayah proyek.'],
            [5, 'Fasilitator Pendidikan Lingkungan', 'part_time', ['Edukasi', 'Public Speaking'], 'Memandu kegiatan pendidikan lingkungan di sekolah.'],
            [6, 'Staf Rantai Pasok Pangan Lokal', 'full_time', ['Analisis Data', 'Pemasaran Digital'], 'Mengatur alur pasok hasil tani lokal ke pasar kota.'],
            [6, 'Analis Logistik Magang', 'internship', ['Analisis Data', 'Excel'], 'Menghitung biaya dan waktu pengiriman hasil panen.'],
            [7, 'Koordinator Program Pemberdayaan Desa', 'full_time', ['Pendampingan', 'Penulisan'], 'Mengoordinasikan program pendampingan di beberapa desa mitra.'],
            [7, 'Staf Pemantauan dan Evaluasi', 'contract', ['Analisis Data', 'Penulisan'], 'Menyusun indikator dan laporan dampak program.'],
            [7, 'Relawan Profesional Dokumentasi', 'part_time', ['Fotografi', 'Penulisan'], 'Mendokumentasikan kegiatan program untuk laporan dan publikasi.'],
            [1, 'Pemasar Digital Produk Tani', 'internship', ['Pemasaran Digital', 'Fotografi'], 'Mengelola katalog daring produk kelompok tani.'],
            [3, 'Pengembang Game Edukasi', 'contract', ['Desain Modul', 'Laravel'], 'Membuat permainan belajar ringan untuk anak sekolah dasar.'],
        ];
        $studentUsers = array_column($u['students'], 'user_id');
        $postIds = [];
        foreach ($jobs as $i => [$ci, $title, $type, $skills, $desc]) {
            $co = $u['companies'][$ci];
            $status = $i === 24 ? 'closed' : ($i === 25 ? 'draft' : ($i === 11 ? 'closed' : 'posted'));
            $pub = $this->ago(27, 2);
            $min = [3500000, 4500000, 5500000, 6500000][$i % 4];
            $postIds[] = $jid = DB::table('job_postings')->insertGetId([
                'company_id' => $co['id'], 'job_category_id' => $cats ? $cats[$i % count($cats)] : null, 'title' => $title,
                'slug' => 'demo-' . ($i + 1) . '-' . Str::slug($title), 'department' => 'Program', 'location' => ['Bandung', 'Yogyakarta', 'Semarang', 'Surabaya', 'Jakarta', 'Bogor', 'Malang', 'Cirebon'][$ci],
                'job_type' => $type, 'salary_min' => $min, 'salary_max' => $min + 2500000, 'salary_currency' => 'IDR', 'salary_period' => 'monthly', 'description' => $desc,
                'responsibilities' => "Bekerja dalam tim lintas fungsi
Menyampaikan hasil kerja secara berkala
Berkoordinasi dengan mitra lapangan",
                'qualifications' => "Mahasiswa tingkat akhir atau lulusan baru
Pernah mengikuti KKN atau proyek komunitas
Mampu bekerja mandiri dan dalam tim",
                'benefits' => "Mentoring
Sertifikat
Jam kerja fleksibel", 'skills' => json_encode($skills), 'sdg_alignment' => json_encode([8, 9]),
                'required_sdgs' => json_encode([8, 9]), 'status' => $status, 'allow_guest_applications' => false, 'views_count' => 30 + ($i * 19) % 280,
                'applications_count' => 0, 'published_at' => $status === 'draft' ? null : $pub, 'expires_at' => $status === 'closed' ? $this->now->copy()->subDays(2) : $this->now->copy()->addDays(20 + $i % 25),
                'created_at' => $pub, 'updated_at' => $pub,
            ]);
            if ($status === 'draft') {
                continue;
            }
            $applicants = [];
            $n = 2 + ($i * 5) % 6;
            if ($i < 4 && $i !== 3) {
                DB::table('job_applications')->insert([
                    'job_posting_id' => $jid, 'user_id' => $studentUsers[0], 'status' => ['pending', 'reviewed', 'shortlisted'][$i], 'cover_letter' => 'Saya tertarik karena posisi ini sejalan dengan pengalaman KKN dan minat saya di bidang teknologi untuk masyarakat.',
                    'created_at' => $this->ago(10, 2), 'updated_at' => $this->ago(2),
                ]);
                $applicants[] = $studentUsers[0];
            }
            for ($t = 3 + $i * 2; count($applicants) < $n; $t++) {
                $su = $studentUsers[$t % count($studentUsers)];
                if (in_array($su, $applicants, true)) {
                    continue;
                }
                DB::table('job_applications')->insert([
                    'job_posting_id' => $jid, 'user_id' => $su, 'status' => $this->pick(['pending', 'pending', 'reviewed', 'shortlisted', 'rejected', 'accepted']),
                    'cover_letter' => 'Saya ingin berkontribusi lewat posisi ini dan membawa pengalaman pendampingan masyarakat dari program KKN.',
                    'created_at' => $this->ago(18, 1), 'updated_at' => $this->ago(2),
                ]);
                $applicants[] = $su;
            }
            DB::table('job_postings')->where('id', $jid)->update(['applications_count' => count($applicants)]);
        }

        // tersimpan dan peringatan
        foreach ([1, 4, 5, 10, 13] as $j) {
            DB::table('saved_jobs')->insert(['user_id' => $studentUsers[0], 'job_posting_id' => $postIds[$j], 'notes' => null, 'created_at' => $this->ago(8), 'updated_at' => $this->ago(8)]);
        }
        DB::table('job_alerts')->insert([
            ['user_id' => $studentUsers[0], 'name' => 'Magang teknologi', 'keywords' => 'web, laravel, data', 'job_types' => json_encode(['internship']), 'locations' => json_encode(['Bandung']), 'skills' => json_encode(['Laravel']), 'frequency' => 'weekly', 'is_active' => true, 'created_at' => $this->ago(12), 'updated_at' => $this->ago(12)],
            ['user_id' => $studentUsers[0], 'name' => 'Pendidikan dan kesehatan', 'keywords' => 'edukasi, kesehatan', 'job_types' => json_encode(['full_time', 'part_time']), 'locations' => json_encode([]), 'skills' => json_encode(['Edukasi']), 'frequency' => 'daily', 'is_active' => true, 'created_at' => $this->ago(9), 'updated_at' => $this->ago(9)],
        ]);
        foreach ([0, 1, 2, 3, 4, 6, 9] as $s) {
            DB::table('saved_talents')->insert(['company_id' => $u['companies'][0]['id'], 'user_id' => $studentUsers[$s], 'category' => ['Teknologi', 'Data', 'Desain'][$s % 3], 'notes' => 'Pengalaman KKN relevan.', 'saved_at' => $this->ago(10), 'created_at' => $this->ago(10), 'updated_at' => $this->ago(10)]);
        }
    }

    // ───────────────────── sosial dan notifikasi ─────────────────────

    private function seedSocial(array $u): void
    {
        $sids = array_column($u['students'], 'id');
        $pairs = [];
        $add = function (int $a, int $b, string $st) use (&$pairs, $sids) {
            $k = min($a, $b) . '-' . max($a, $b);
            if ($a === $b || isset($pairs[$k])) {
                return;
            }
            $pairs[$k] = true;
            DB::table('friends')->insert([
                'requester_id' => $sids[$a], 'receiver_id' => $sids[$b], 'status' => $st, 'message' => $st === 'pending' ? 'Halo, kita satu program KKN. Boleh berteman?' : null,
                'responded_at' => $st === 'accepted' ? $this->ago(15, 1) : null, 'created_at' => $this->ago(20, 2), 'updated_at' => $this->ago(10),
            ]);
        };
        foreach ([1, 2, 3, 5, 6, 8, 11, 14, 17] as $b) {
            $add(0, $b, 'accepted');
        }
        $add(20, 0, 'pending');
        $add(23, 0, 'pending');
        for ($i = 1; $i < 40; $i++) {
            foreach ([3, 7, 13] as $step) {
                if (($i + $step) % 3 !== 0) {
                    $add($i, ($i + $step) % 40, 'accepted');
                }
            }
        }

        // wishlist
        foreach ($u['open'] as $k => $pid) {
            foreach ([0 => [5, 9, 12, 15, 19], 1 => [3, 8, 22], 2 => [1, 14, 25]] as $si => $list) {
                if (in_array($k, $list, true)) {
                    DB::table('wishlists')->insert(['student_id' => $sids[$si], 'problem_id' => $pid, 'notes' => null, 'created_at' => $this->ago(10), 'updated_at' => $this->ago(10)]);
                }
            }
        }
        for ($i = 4; $i < 40; $i++) {
            DB::table('wishlists')->insert(['student_id' => $sids[$i], 'problem_id' => $u['open'][($i * 3) % count($u['open'])], 'notes' => null, 'created_at' => $this->ago(12), 'updated_at' => $this->ago(12)]);
        }

        // notifikasi
        $n = fn (int $uid, string $type, string $title, string $msg, int $d) => DB::table('notifications')->insert([
            'user_id' => $uid, 'type' => $type, 'title' => $title, 'message' => $msg, 'is_read' => $d > 6, 'read_at' => $d > 6 ? $this->ago($d - 1) : null,
            'created_at' => $this->ago($d, max(0, $d - 1)), 'updated_at' => $this->ago($d, max(0, $d - 1)),
        ]);
        $s0 = $u['students'][0]['user_id'];
        $n($s0, 'application', 'Lamaran sedang ditinjau', 'Lamaran Anda sedang ditinjau oleh instansi.', 2);
        $n($s0, 'application', 'Lamaran belum diterima', 'Instansi belum dapat menerima lamaran Anda kali ini. Masih banyak masalah lain yang bisa dilamar.', 4);
        $n($s0, 'project', 'Ulasan baru dari instansi', 'Instansi memberi ulasan untuk proyek yang Anda selesaikan.', 8);
        $n($s0, 'friend', 'Permintaan pertemanan', 'Ada dua permintaan pertemanan baru dari sesama mahasiswa.', 1);
        $n($s0, 'job', 'Lowongan baru sesuai peringatan', 'Ada lowongan baru yang cocok dengan peringatan "Magang teknologi".', 3);
        foreach ($u['institutions'] as $i => $inst) {
            $n($inst['user_id'], 'application', 'Lamaran baru', 'Ada lamaran baru yang menunggu ditinjau.', 1 + $i % 5);
            $n($inst['user_id'], 'project', 'Laporan baru dari mahasiswa', 'Mahasiswa mengirim laporan kemajuan mingguan.', 3 + $i % 4);
        }
        foreach ($u['companies'] as $i => $co) {
            $n($co['user_id'], 'job', 'Lamaran lowongan baru', 'Ada pelamar baru untuk lowongan Anda.', 1 + $i % 6);
            $n($co['user_id'], 'job', 'Lowongan Anda dilihat banyak orang', 'Lowongan Anda dilihat puluhan mahasiswa minggu ini.', 7 + $i % 3);
        }
        $admin = DB::table('users')->where('email', 'admin@kolabora.test')->value('id');
        $n($admin, 'system', 'Ringkasan mingguan', 'Pengguna dan aktivitas platform bertambah minggu ini.', 2);
    }
}
