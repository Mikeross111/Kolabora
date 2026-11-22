// kolabora: perilaku bersama shell dan komponen (phase 3). tidak ada dependensi baru;
// alpine tetap dimuat dari cdn (D-20). bundel ini dimuat di <head> sebelum alpine (defer),
// sehingga pendaftaran alpine:init selalu terpasang lebih dulu.
import { registerAlpine } from './kolabora/alpine';
import { NotificationManager } from './kolabora/toast';
import { initConfirm, initDialogs } from './kolabora/dialog';
import { initAvatars, initScrollTop } from './kolabora/misc';
import { initHeroVideo } from './kolabora/hero-video';
import { registerAuth } from './kolabora/auth';

document.addEventListener('alpine:init', () => {
    registerAlpine(window.Alpine);
    // phase 6: halaman masuk, registrasi dan reset password
    registerAuth(window.Alpine);
});

const ready = (fn) => (document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', fn) : fn());

ready(() => {
    // api lama tetap tersedia untuk halaman yang memanggilnya
    const notificationManager = new NotificationManager();
    window.notificationManager = notificationManager;
    window.showNotification = (message, type, duration) => notificationManager.show(message, type, duration);

    initDialogs();
    initConfirm();
    initAvatars();
    initScrollTop();
    initHeroVideo();

    // phase 21: chart.js lokal dimuat (chunk terpisah) hanya bila halaman punya grafik
    if (document.querySelector('canvas[data-kb-chart]')) {
        import('./kolabora/charts').then(({ initCharts }) => initCharts());
    }
});

// pesan validasi bawaan browser mengikuti bahasa browser (sering Inggris); ganti dengan bahasa Indonesia
document.addEventListener('invalid', (e) => {
    const el = e.target;
    if (!el || typeof el.setCustomValidity !== 'function') return;
    const v = el.validity;
    let msg = '';
    if (v.valueMissing) msg = el.type === 'checkbox' ? 'Centang kotak ini untuk melanjutkan.' : (el.tagName === 'SELECT' ? 'Pilih salah satu opsi.' : 'Kolom ini wajib diisi.');
    else if (v.typeMismatch) msg = el.type === 'email' ? 'Masukkan alamat email yang valid.' : (el.type === 'url' ? 'Masukkan alamat web yang valid.' : 'Format isian tidak sesuai.');
    else if (v.tooShort) msg = 'Isian terlalu pendek, minimal ' + el.minLength + ' karakter.';
    else if (v.tooLong) msg = 'Isian terlalu panjang, maksimal ' + el.maxLength + ' karakter.';
    else if (v.patternMismatch) msg = el.title || 'Format isian tidak sesuai.';
    else if (v.rangeUnderflow) msg = 'Nilai terlalu kecil, minimal ' + el.min + '.';
    else if (v.rangeOverflow) msg = 'Nilai terlalu besar, maksimal ' + el.max + '.';
    el.setCustomValidity(msg);
}, true);
document.addEventListener('input', (e) => {
    if (e.target && typeof e.target.setCustomValidity === 'function') e.target.setCustomValidity('');
}, true);
