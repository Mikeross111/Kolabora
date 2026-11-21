// dialog native (<dialog class="kb-dialog">): buka/tutup, klik backdrop, dan dialog konfirmasi
// <dialog> memberi fokus awal, esc, latar inert dan pengembalian fokus ke pemicu.

function open(id) {
    const dialog = document.getElementById(id);
    if (dialog && typeof dialog.showModal === 'function' && !dialog.open) dialog.showModal();
}

export function initDialogs() {
    document.addEventListener('click', (e) => {
        const opener = e.target.closest('[data-kb-open]');
        if (opener) {
            e.preventDefault();
            open(opener.dataset.kbOpen);
            return;
        }
        const closer = e.target.closest('[data-kb-close]');
        if (closer) {
            closer.closest('dialog')?.close();
            return;
        }
        // klik di luar kotak dialog (backdrop) menutup dialog
        const d = e.target;
        if (d instanceof HTMLDialogElement && d.open && d.classList.contains('kb-dialog')) {
            const r = d.getBoundingClientRect();
            if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) d.close();
        }
    });
    window.addEventListener('kb-open-modal', (e) => open(e.detail));
    window.addEventListener('kb-close-modal', (e) => document.getElementById(e.detail)?.close());
}

// window.kbConfirm({ title, message, confirmLabel, cancelLabel, tone }) -> Promise<boolean>
// <form data-kb-confirm="pesan"> meminta konfirmasi sebelum submit (submitter tetap dikirim)
export function initConfirm() {
    const dialog = document.getElementById('kb-confirm');

    window.kbConfirm = (opts = {}) => new Promise((resolve) => {
        if (!dialog || typeof dialog.showModal !== 'function') {
            resolve(window.confirm(opts.message || opts.title || 'Lanjutkan?'));
            return;
        }
        dialog.querySelector('[data-kb-confirm-title]').textContent = opts.title || 'Konfirmasi tindakan';
        dialog.querySelector('[data-kb-confirm-message]').textContent = opts.message || '';
        const ok = dialog.querySelector('[data-kb-confirm-ok]');
        ok.textContent = opts.confirmLabel || 'Ya, lanjutkan';
        ok.className = `kb-btn ${opts.tone === 'primary' ? 'kb-btn--primary' : 'kb-btn--danger-solid'}`;
        dialog.querySelector('[data-kb-confirm-cancel]').textContent = opts.cancelLabel || 'Batal';
        dialog.returnValue = '';
        const onClose = () => {
            dialog.removeEventListener('close', onClose);
            resolve(dialog.returnValue === 'confirm');
        };
        dialog.addEventListener('close', onClose);
        dialog.showModal();
        // fokus awal pada pilihan aman
        dialog.querySelector('[data-kb-confirm-cancel]').focus();
    });

    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement) || !form.dataset.kbConfirm) return;
        if (form.dataset.kbConfirmed) {
            delete form.dataset.kbConfirmed;
            return;
        }
        e.preventDefault();
        e.stopImmediatePropagation();
        const submitter = e.submitter && e.submitter.form === form ? e.submitter : undefined;
        window.kbConfirm({
            title: form.dataset.kbConfirmTitle,
            message: form.dataset.kbConfirm,
            confirmLabel: form.dataset.kbConfirmLabel,
            tone: form.dataset.kbConfirmTone,
        }).then((confirmed) => {
            if (!confirmed) return;
            form.dataset.kbConfirmed = '1';
            form.requestSubmit(submitter);
        });
    }, true);
}
