{{-- Global Custom Confirmation & Alert Dialogs (Replaces native browser "localhost says" popups) --}}
<div id="app-confirm-dialog" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="app-confirm-title" style="z-index: 9999;">
    <div class="modal-box modal-box-confirm">
        <div style="padding: 24px; display: flex; gap: 16px; align-items: flex-start;">
            <div id="app-confirm-icon-wrapper" class="modal-confirm-icon icon-primary">
                <span id="app-confirm-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                </span>
            </div>
            <div style="flex: 1; min-width: 0;">
                <h3 id="app-confirm-title" class="modal-confirm-title">Konfirmasi Tindakan</h3>
                <p id="app-confirm-message" class="modal-confirm-message">Apakah Anda yakin ingin melanjutkan tindakan ini?</p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="app-confirm-btn-cancel">Batal</button>
            <button type="button" class="btn btn-primary" id="app-confirm-btn-submit">Ya, Lanjutkan</button>
        </div>
    </div>
</div>

<div id="app-alert-dialog" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="app-alert-title" style="z-index: 9999;">
    <div class="modal-box modal-box-confirm">
        <div style="padding: 24px; display: flex; gap: 16px; align-items: flex-start;">
            <div id="app-alert-icon-wrapper" class="modal-confirm-icon icon-primary">
                <span id="app-alert-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                </span>
            </div>
            <div style="flex: 1; min-width: 0;">
                <h3 id="app-alert-title" class="modal-confirm-title">Pemberitahuan</h3>
                <p id="app-alert-message" class="modal-confirm-message"></p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" id="app-alert-btn-ok">Mengerti</button>
        </div>
    </div>
</div>

<script>
(function() {
    let appConfirmResolve = null;
    let appAlertResolve = null;

    const svgIcons = {
        info: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>',
        warning: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
        danger: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>',
        close: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>'
    };

    const confirmDialog = document.getElementById('app-confirm-dialog');
    const confirmTitleEl = document.getElementById('app-confirm-title');
    const confirmMsgEl = document.getElementById('app-confirm-message');
    const confirmSubmitBtn = document.getElementById('app-confirm-btn-submit');
    const confirmCancelBtn = document.getElementById('app-confirm-btn-cancel');
    const confirmIconWrap = document.getElementById('app-confirm-icon-wrapper');
    const confirmIconEl = document.getElementById('app-confirm-icon');

    const alertDialog = document.getElementById('app-alert-dialog');
    const alertTitleEl = document.getElementById('app-alert-title');
    const alertMsgEl = document.getElementById('app-alert-message');
    const alertIconWrap = document.getElementById('app-alert-icon-wrapper');
    const alertIconEl = document.getElementById('app-alert-icon');
    const alertOkBtn = document.getElementById('app-alert-btn-ok');

    window.showConfirmModal = function(options = {}) {
        return new Promise((resolve) => {
            appConfirmResolve = resolve;

            confirmTitleEl.textContent = options.title || 'Konfirmasi Tindakan';
            confirmMsgEl.textContent = options.message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
            confirmSubmitBtn.textContent = options.confirmText || 'Ya, Lanjutkan';
            confirmCancelBtn.textContent = options.cancelText || 'Batal';

            const variant = options.variant || 'primary';
            confirmSubmitBtn.className = 'btn ' + (variant === 'danger' ? 'btn-danger' : (variant === 'warning' ? 'btn-warning' : 'btn-primary'));

            if (variant === 'danger') {
                confirmIconWrap.className = 'modal-confirm-icon icon-danger';
                confirmIconEl.innerHTML = svgIcons.danger;
            } else if (variant === 'warning') {
                confirmIconWrap.className = 'modal-confirm-icon icon-warning';
                confirmIconEl.innerHTML = svgIcons.warning;
            } else {
                confirmIconWrap.className = 'modal-confirm-icon icon-primary';
                confirmIconEl.innerHTML = svgIcons.info;
            }

            confirmDialog.classList.add('active');
            confirmSubmitBtn.focus();
        });
    };

    function smoothCloseModal(dialogEl, callback) {
        if (!dialogEl || !dialogEl.classList.contains('active')) return;
        dialogEl.classList.add('closing');
        setTimeout(() => {
            dialogEl.classList.remove('active', 'closing');
            if (callback) callback();
        }, 160);
    }

    function closeConfirm(result) {
        smoothCloseModal(confirmDialog, () => {
            if (appConfirmResolve) {
                appConfirmResolve(result);
                appConfirmResolve = null;
            }
        });
    }

    if (confirmSubmitBtn) confirmSubmitBtn.addEventListener('click', () => closeConfirm(true));
    if (confirmCancelBtn) confirmCancelBtn.addEventListener('click', () => closeConfirm(false));

    window.showAlertModal = function(message, title = 'Pemberitahuan', variant = 'info') {
        return new Promise((resolve) => {
            appAlertResolve = resolve;

            alertTitleEl.textContent = title;
            alertMsgEl.textContent = message;

            if (variant === 'danger' || variant === 'error') {
                alertIconWrap.className = 'modal-confirm-icon icon-danger';
                alertIconEl.innerHTML = svgIcons.close;
            } else if (variant === 'warning') {
                alertIconWrap.className = 'modal-confirm-icon icon-warning';
                alertIconEl.innerHTML = svgIcons.warning;
            } else {
                alertIconWrap.className = 'modal-confirm-icon icon-primary';
                alertIconEl.innerHTML = svgIcons.info;
            }

            alertDialog.classList.add('active');
            alertOkBtn.focus();
        });
    };

    function closeAlert() {
        smoothCloseModal(alertDialog, () => {
            if (appAlertResolve) {
                appAlertResolve();
                appAlertResolve = null;
            }
        });
    }

    if (alertOkBtn) alertOkBtn.addEventListener('click', closeAlert);

    // Close on backdrop click
    window.addEventListener('click', function(e) {
        if (e.target === confirmDialog) closeConfirm(false);
        if (e.target === alertDialog) closeAlert();
    });

    // Close on Escape key
    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (confirmDialog.classList.contains('active')) closeConfirm(false);
            if (alertDialog.classList.contains('active')) closeAlert();
        }
    });

    // Override browser window.alert to completely prevent "localhost says"
    window.alert = function(msg) {
        showAlertModal(String(msg), 'Pemberitahuan', 'warning');
    };

    // Global listener for forms with data-confirm
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form && form.hasAttribute('data-confirm')) {
            e.preventDefault();
            const message = form.getAttribute('data-confirm');
            const title = form.getAttribute('data-confirm-title') || 'Konfirmasi Tindakan';
            const confirmText = form.getAttribute('data-confirm-btn') || 'Ya, Lanjutkan';
            const cancelText = form.getAttribute('data-cancel-btn') || 'Batal';
            const variant = form.getAttribute('data-confirm-variant') || 'primary';

            showConfirmModal({
                title: title,
                message: message,
                confirmText: confirmText,
                cancelText: cancelText,
                variant: variant
            }).then((confirmed) => {
                if (confirmed) {
                    form.removeAttribute('data-confirm');
                    form.submit();
                }
            });
        }
    });
})();
</script>
