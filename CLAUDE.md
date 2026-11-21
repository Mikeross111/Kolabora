# CLAUDE.md

Panduan untuk asisten AI yang bekerja di repositori ini.

## Proyek

Kolabora adalah platform web (Laravel 13, PHP 8.3) untuk Kuliah Kerja Nyata (KKN): instansi memublikasikan masalah, mahasiswa menelusuri dan melamar, perusahaan mencari talenta. Tiga peran terautentikasi (mahasiswa, instansi, perusahaan), area admin, halaman publik (beranda, tentang, kontak), dan asisten AI "Kora" (RAG: embedding Cohere, jawaban Google Gemini).

## Stack

Laravel + Sanctum, Blade, Tailwind CSS 4, Alpine.js, Vite, Chart.js, Leaflet. Database PostgreSQL (Supabase) untuk produksi; test memakai SQLite in-memory.

## Perintah

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
npm run build            # atau: npm run dev
php artisan test         # SQLite in-memory, semua request HTTP keluar diblokir
```

`.env` tidak boleh masuk repositori (kunci API: `COHERE_API_KEY`, `CLAUDE_API_KEY`).

## Struktur desain

Desain digerakkan token. Satu entry Vite: `resources/css/app.css` + `resources/js/app.js`.

- `resources/css/kolabora/tokens.css`: warna (teal utama, oranye aksen, navy untuk teks), bayangan, token `--kb-*`.
- `resources/css/kolabora/legacy-overrides.css`: font, ramp abu-abu, radius, dasar elemen, pemetaan palet Tailwind.
- `resources/css/kolabora/components.css`: komponen `.kb-*`.
- `resources/css/kolabora/*.css` lain: gaya per halaman (hero, public-pages, auth, student-*, institution, company, errors).
- `resources/views/components/ui/`: komponen Blade `x-ui.*`.

## Aturan kerja

- Bahasa antarmuka: Indonesia. Komentar kode: huruf kecil, singkat.
- Ubah tampilan lewat token dan CSS; jangan mengganti nama class, id, atau atribut Alpine tanpa alasan fungsional.
- Setiap perubahan harus menjaga `php artisan test` tetap hijau dan `npm run build` sukses.
- Model memakai koneksi `pgsql` secara eksplisit; `tests/TestCase.php` mengalihkannya ke SQLite.
- Jangan menaruh data pribadi, kunci API, atau aset berlisensi tidak jelas di repositori.

## Penggunaan AI

Asisten kode berbasis AI (Claude Code) dipakai membantu pengembangan, refaktor tampilan, dan dokumentasi; seluruh keluaran ditinjau pemilik repositori sebelum digabungkan. Fitur Kora memakai Cohere (embedding) dan Google Gemini (jawaban) sebagai bagian produk.
