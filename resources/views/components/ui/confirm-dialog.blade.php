{{-- satu dialog konfirmasi bersama (dirender sekali di layouts.app). dipakai lewat:
     <form data-kb-confirm="Hapus dokumen ini?" data-kb-confirm-label="Hapus"> … atau window.kbConfirm({...}).
     fokus awal pada "Batal"; esc/klik latar = batal. teks diisi resources/js/kolabora/dialog.js. --}}
<dialog id="kb-confirm" class="kb-dialog" aria-labelledby="kb-confirm-title" aria-describedby="kb-confirm-message">
    <form method="dialog">
        <div class="kb-dialog__head">
            <h2 id="kb-confirm-title" data-kb-confirm-title>Konfirmasi tindakan</h2>
        </div>
        <div class="kb-dialog__body">
            <p id="kb-confirm-message" data-kb-confirm-message></p>
        </div>
        <div class="kb-dialog__foot">
            <button type="submit" value="cancel" class="kb-btn kb-btn--secondary" data-kb-confirm-cancel>Batal</button>
            <button type="submit" value="confirm" class="kb-btn kb-btn--danger-solid" data-kb-confirm-ok>Ya, lanjutkan</button>
        </div>
    </form>
</dialog>
