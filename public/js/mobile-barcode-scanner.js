(() => {
    'use strict';

    const FIELD_SELECTOR = '.scan-input';
    const QRCODE_URL = 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js';
    const STORE_KEY = 'stockTrackingNgrokBaseUrl';
    let modal;
    let targetField;
    let targetForm;
    let currentToken = null;
    let pollTimer = null;
    let qrScriptPromise = null;
    let lastSequence = 0;
    let pairingButton = null;

    const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value
        || '';

    function addStyles() {
        if (document.getElementById('remote-scanner-pairing-styles')) return;
        const style = document.createElement('style');
        style.id = 'remote-scanner-pairing-styles';
        style.textContent = `
        .remote-pair-btn{display:inline-flex;align-items:center;gap:8px;margin-top:8px;border:1px solid #b9cffb;background:#eff6ff;color:#1746a2;padding:9px 13px;border-radius:10px;font:700 13px/1.2 inherit;cursor:pointer;touch-action:manipulation;transition:transform .16s,background .16s;}
        .remote-pair-btn:hover{background:#dfeaff;transform:translateY(-1px)}
        .remote-pair-overlay{position:fixed;inset:0;z-index:10000;background:rgba(15,32,59,.58);display:flex;align-items:center;justify-content:center;padding:18px;}
        .remote-pair-overlay[hidden]{display:none!important}
        .remote-pair-dialog{width:min(100%,560px);max-height:92vh;overflow:auto;background:#fff;border:1px solid #dbe5f6;border-radius:20px;box-shadow:0 28px 90px rgba(15,32,59,.32);padding:22px;color:#162846;font-family:Inter,ui-sans-serif,system-ui,-apple-system,'Segoe UI',sans-serif;}
        .remote-pair-head{display:flex;align-items:flex-start;justify-content:space-between;gap:14px}.remote-pair-head h2{margin:0;font-size:21px;letter-spacing:-.4px}.remote-pair-sub{margin:7px 0 18px;color:#60718b;line-height:1.5;font-size:14px}
        .remote-pair-close{width:40px;height:40px;border:0;border-radius:12px;background:#eef4ff;color:#2454ad;font-size:24px;cursor:pointer;flex-shrink:0}
        .remote-pair-label{display:block;margin:12px 0 6px;font-size:12px;font-weight:800;color:#435875}.remote-pair-input{width:100%;box-sizing:border-box;border:1px solid #cbd8ec;border-radius:10px;padding:11px 12px;font:inherit;color:#172b4b;background:white;min-height:44px}
        .remote-pair-actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}.remote-pair-action{border:1px solid #ccd8ed;border-radius:10px;padding:10px 13px;cursor:pointer;font-weight:700;font-size:13px;background:#f7f9fd;color:#244064}.remote-pair-action.primary{background:#2458c6;border-color:#2458c6;color:white}.remote-pair-action:disabled{opacity:.5;cursor:wait}
        .remote-pair-status{margin:12px 0 0;color:#526782;line-height:1.5;font-size:13px;overflow-wrap:anywhere}.remote-pair-status.error{color:#b42318}.remote-pair-status.success{color:#067647}
        .remote-pair-result{margin-top:18px;padding-top:18px;border-top:1px solid #e5ebf5}.remote-pair-result[hidden]{display:none!important}.remote-pair-result-grid{display:grid;grid-template-columns:210px 1fr;align-items:center;gap:18px}.remote-pair-qr{width:210px;min-height:210px;border:1px solid #e2e9f5;border-radius:14px;padding:8px;display:grid;place-items:center;background:white;box-sizing:border-box}.remote-pair-qr img{max-width:100%;height:auto}.remote-pair-url{font-size:12px;color:#536780;overflow-wrap:anywhere;line-height:1.5}.remote-pair-expiry{font-size:12px;color:#6a7b92;margin-top:8px}
        @media(max-width:520px){.remote-pair-dialog{padding:16px}.remote-pair-result-grid{grid-template-columns:1fr}.remote-pair-qr{margin:0 auto}.remote-pair-head h2{font-size:19px}}
        `;
        document.head.appendChild(style);
    }

    function setStatus(text, kind = '') {
        const el = modal?.querySelector('[data-pair-status]');
        if (!el) return;
        el.textContent = text;
        el.classList.toggle('error', kind === 'error');
        el.classList.toggle('success', kind === 'success');
    }

    function loadQrLibrary() {
        if (window.QRCode) return Promise.resolve();
        if (qrScriptPromise) return qrScriptPromise;
        qrScriptPromise = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = QRCODE_URL;
            script.async = true;
            script.onload = resolve;
            script.onerror = () => reject(new Error('Library QR tidak dapat dimuat. Kamu tetap bisa membuka link scanner secara manual.'));
            document.head.appendChild(script);
        });
        return qrScriptPromise;
    }

    function createModal() {
        if (modal) return;
        modal = document.createElement('div');
        modal.className = 'remote-pair-overlay';
        modal.hidden = true;
        modal.innerHTML = `
          <section class="remote-pair-dialog" role="dialog" aria-modal="true" aria-labelledby="remote-pair-title">
            <div class="remote-pair-head"><div><h2 id="remote-pair-title">Hubungkan HP sebagai scanner</h2><div class="remote-pair-sub">Barcode yang dipindai HP masuk ke form laptop. Periksa data pada laptop, lalu tekan Submit Transaksi dari HP untuk mengirim form tersebut.</div></div><button type="button" class="remote-pair-close" data-pair-close aria-label="Tutup">×</button></div>
            <label class="remote-pair-label" for="remote-pair-base-url">URL HTTPS ngrok yang sedang aktif</label>
            <input id="remote-pair-base-url" class="remote-pair-input" type="url" inputmode="url" placeholder="https://nama-tunnel.ngrok-free.dev" autocomplete="url">
            <div class="remote-pair-actions"><button type="button" class="remote-pair-action primary" data-pair-create>Buat QR untuk HP</button><button type="button" class="remote-pair-action" data-pair-copy-link hidden>Salin link scanner</button><button type="button" class="remote-pair-action" data-pair-close>Tutup</button></div>
            <p class="remote-pair-status" data-pair-status aria-live="polite">Masukkan URL HTTPS dari terminal ngrok, lalu buat sesi scanner. Sesi berlaku 15 menit.</p>
            <div class="remote-pair-result" data-pair-result hidden><div class="remote-pair-result-grid"><div class="remote-pair-qr" data-pair-qr></div><div><strong>1. Pindai QR ini dengan HP</strong><p class="remote-pair-url" data-pair-url></p><p class="remote-pair-expiry">2. Tekan “Mulai Kamera” di HP. Setelah scan, periksa data pada laptop lalu tekan “Submit Transaksi” dari HP.</p></div></div></div>
          </section>`;
        document.body.appendChild(modal);
        const input = modal.querySelector('#remote-pair-base-url');
        input.value = localStorage.getItem(STORE_KEY) || '';
        modal.querySelectorAll('[data-pair-close]').forEach((b) => b.addEventListener('click', closeModal));
        modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
        modal.querySelector('[data-pair-create]').addEventListener('click', createPair);
        modal.querySelector('[data-pair-copy-link]').addEventListener('click', copyPhoneLink);
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && modal && !modal.hidden) closeModal(); });
    }

    function openModal(field, button) {
        targetField = field;
        targetForm = field.closest('form');
        pairingButton = button;
        createModal();
        modal.hidden = false;
        modal.querySelector('[data-pair-result]').hidden = true;
        modal.querySelector('[data-pair-copy-link]').hidden = true;
        modal.querySelector('[data-pair-qr]').innerHTML = '';
        setStatus('Masukkan URL HTTPS dari terminal ngrok. Setelah scan, data dapat diperiksa di laptop dan disubmit dari HP. Sesi berlaku 15 menit.');
        modal.querySelector('#remote-pair-base-url').focus();
    }

    async function createPair() {
        const baseInput = modal.querySelector('#remote-pair-base-url');
        const createButton = modal.querySelector('[data-pair-create]');
        let baseUrl;
        try {
            baseUrl = new URL(baseInput.value.trim());
            if (baseUrl.protocol !== 'https:') throw new Error('Gunakan URL HTTPS ngrok, bukan localhost atau alamat HTTP.');
            baseUrl.pathname = baseUrl.pathname.replace(/\/$/, '');
            baseUrl.search = '';
            baseUrl.hash = '';
        } catch (error) {
            setStatus(error.message || 'Masukkan URL HTTPS ngrok yang valid.', 'error');
            return;
        }
        if (!csrfToken()) {
            setStatus('Token keamanan halaman tidak ditemukan. Tambahkan meta csrf-token pada layout Blade.', 'error');
            return;
        }

        createButton.disabled = true;
        setStatus('Membuat sesi scanner…');
        try {
            if (currentToken) await closeRemoteSession(currentToken);
            const mode = targetField instanceof HTMLTextAreaElement ? 'multiple' : 'single';
            const response = await fetch('/remote-scanner/sessions', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                body: JSON.stringify({ mode }),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(data.message || 'Sesi scanner gagal dibuat. Periksa route dan migration.');

            currentToken = data.token;
            lastSequence = 0;
            localStorage.setItem(STORE_KEY, baseUrl.origin);
            const phoneUrl = `${baseUrl.origin}/phone-scanner/${encodeURIComponent(currentToken)}`;
            const result = modal.querySelector('[data-pair-result]');
            const qrHolder = modal.querySelector('[data-pair-qr]');
            modal.querySelector('[data-pair-url]').textContent = phoneUrl;
            modal.querySelector('[data-pair-copy-link]').hidden = false;
            result.hidden = false;

            try {
                await loadQrLibrary();
                qrHolder.innerHTML = '';
                new window.QRCode(qrHolder, { text: phoneUrl, width: 190, height: 190, correctLevel: window.QRCode.CorrectLevel.M });
            } catch (qrError) {
                qrHolder.textContent = 'QR tidak dapat dimuat. Gunakan tombol Salin link scanner.';
                setStatus(qrError.message, 'error');
            }
            setStatus('Sesi siap. Pindai QR menggunakan HP, tekan Mulai Kamera, lalu scan barcode. Setelah memeriksa data di laptop, tekan Submit Transaksi dari HP.', 'success');
            startPolling(currentToken);
        } catch (error) {
            setStatus(error.message || 'Tidak dapat membuat sesi scanner.', 'error');
        } finally {
            createButton.disabled = false;
        }
    }

    async function copyPhoneLink() {
        const link = modal.querySelector('[data-pair-url]').textContent;
        try {
            await navigator.clipboard.writeText(link);
            setStatus('Link scanner berhasil disalin. Kirim atau buka link tersebut dari HP.', 'success');
        } catch (_) {
            setStatus('Clipboard tidak tersedia. Salin link yang tampil secara manual.', 'error');
        }
    }

    function applyBarcode(value) {
        if (!targetField || !value) return;
        const barcode = String(value).trim();
        if (!barcode) return;

        if (targetField instanceof HTMLTextAreaElement) {
            const lines = targetField.value.split(/\r?\n/).map((line) => line.trim()).filter(Boolean);
            if (!lines.includes(barcode)) {
                targetField.value = targetField.value.trim() ? targetField.value.trimEnd() + '\n' + barcode : barcode;
            }
        } else {
            targetField.value = barcode;
        }
        targetField.dispatchEvent(new Event('input', { bubbles: true }));
        targetField.dispatchEvent(new Event('change', { bubbles: true }));
        targetField.focus({ preventScroll: true });
        setStatus(`✓ Barcode ${barcode} diterima dan dimasukkan ke form laptop.`, 'success');
    }

    async function reportSubmitResult(token, result, message) {
        const response = await fetch(`/remote-scanner/sessions/${encodeURIComponent(token)}/submit-result`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            body: JSON.stringify({ result, message }),
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(data.message || 'Status Submit tidak dapat dikirim.');
    }

    async function handleSubmitRequest(token) {
        if (!targetForm) {
            await reportSubmitResult(token, 'validation_error', 'Form transaksi pada laptop tidak ditemukan. Tutup pairing dan hubungkan ulang dari kolom barcode.');
            setStatus('Form transaksi tidak ditemukan. Hubungkan ulang dari kolom barcode yang benar.', 'error');
            return false;
        }

        if (typeof targetForm.reportValidity === 'function' && !targetForm.reportValidity()) {
            const message = 'Form belum lengkap. Lengkapi field wajib yang ditandai pada laptop, kemudian tekan Submit Transaksi dari HP lagi.';
            await reportSubmitResult(token, 'validation_error', message);
            setStatus(message, 'error');
            return false;
        }

        try {
            await reportSubmitResult(token, 'dispatched', 'Perintah Submit dari HP diteruskan. Periksa hasil penyimpanan pada halaman laptop.');
            setStatus('Perintah Submit diterima. Form transaksi sedang dikirim dari laptop…', 'success');
            const submitter = targetForm.querySelector('button[type="submit"]:not([disabled]), input[type="submit"]:not([disabled])');
            if (typeof targetForm.requestSubmit === 'function') {
                submitter ? targetForm.requestSubmit(submitter) : targetForm.requestSubmit();
            } else {
                targetForm.submit();
            }
            return true;
        } catch (error) {
            await reportSubmitResult(token, 'validation_error', 'Laptop gagal meneruskan Submit. Periksa koneksi lalu coba lagi dari HP.').catch(() => {});
            setStatus(error.message || 'Laptop gagal meneruskan Submit dari HP.', 'error');
            return false;
        }
    }

    function startPolling(token) {
        stopPolling();
        let pollInFlight = false;
        pollTimer = window.setInterval(async () => {
            if (pollInFlight) return;
            pollInFlight = true;
            try {
                const response = await fetch(`/remote-scanner/sessions/${encodeURIComponent(token)}/poll`, {
                    method: 'GET', credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'application/json' },
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(data.message || 'Sesi scanner terputus.');
                const queuedBarcodes = Array.isArray(data.barcodes) ? data.barcodes : (data.barcode ? [{ barcode: data.barcode, sequence: data.sequence }] : []);
                queuedBarcodes.forEach((event) => {
                    if (event.barcode && Number(event.sequence) > lastSequence) {
                        lastSequence = Number(event.sequence);
                        applyBarcode(event.barcode);
                    }
                });
                if (data.submit_requested) {
                    const dispatched = await handleSubmitRequest(token);
                    if (dispatched) {
                        stopPolling();
                        return;
                    }
                }
                if (!data.active) {
                    stopPolling();
                    if (data.status === 'completed') setStatus('Perintah Submit dari HP telah diteruskan. Periksa hasil transaksi pada halaman laptop.', 'success');
                    else if (data.status === 'expired') setStatus('Sesi kedaluwarsa. Buat sesi baru untuk melanjutkan scan.', 'error');
                    else if (data.status === 'closed') setStatus('Sesi scanner ditutup.', '');
                }
            } catch (error) {
                stopPolling();
                setStatus(error.message || 'Koneksi scanner terputus.', 'error');
            } finally {
                pollInFlight = false;
            }
        }, 650);
    }

    function stopPolling() {
        if (pollTimer) window.clearInterval(pollTimer);
        pollTimer = null;
    }

    async function closeRemoteSession(token) {
        try {
            await fetch(`/remote-scanner/sessions/${encodeURIComponent(token)}/close`, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            });
        } catch (_) { /* Session naturally expires in 15 minutes. */ }
        if (currentToken === token) currentToken = null;
    }

    async function closeModal() {
        stopPolling();
        if (currentToken) await closeRemoteSession(currentToken);
        if (modal) modal.hidden = true;
        targetField = null;
        targetForm = null;
        pairingButton = null;
    }

    function enhanceBarcodeFields() {
        document.querySelectorAll(FIELD_SELECTOR).forEach((field) => {
            if (field.dataset.remoteScannerReady === 'true') return;
            field.dataset.remoteScannerReady = 'true';
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'remote-pair-btn';
            button.innerHTML = '<span aria-hidden="true">▦</span><span>Hubungkan HP untuk Scan</span>';
            button.setAttribute('aria-label', 'Hubungkan HP sebagai scanner barcode untuk kolom ini');
            button.addEventListener('click', () => openModal(field, button));
            field.insertAdjacentElement('afterend', button);
        });
    }

    function init() {
        addStyles();
        enhanceBarcodeFields();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
    else init();
})();
