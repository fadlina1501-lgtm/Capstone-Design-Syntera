(() => {
    'use strict';

    const FIELD_SELECTOR = '.scan-input';
    let modal = null;
    let scanner = null;
    let targetField = null;
    let lastAcceptedAt = 0;

    function addStyles() {
        if (document.getElementById('camera-barcode-scanner-styles')) return;

        const style = document.createElement('style');
        style.id = 'camera-barcode-scanner-styles';
        style.textContent = `
            .camera-scan-field{display:flex;align-items:stretch;gap:8px;width:100%;}
            .camera-scan-field .scan-input{flex:1 1 auto;min-width:0;}
            .camera-scan-btn{flex:0 0 auto;align-self:stretch;white-space:nowrap;}
            .camera-scan-overlay{position:fixed;inset:0;z-index:12000;display:flex;align-items:center;justify-content:center;padding:16px;background:rgba(15,23,42,.68);}
            .camera-scan-overlay[hidden]{display:none!important;}
            .camera-scan-dialog{width:min(100%,560px);max-height:92vh;overflow:auto;background:#fff;border:1px solid #dbe3ef;border-radius:18px;box-shadow:0 24px 80px rgba(15,23,42,.35);padding:20px;color:#172033;}
            .camera-scan-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:8px;}
            .camera-scan-head h2{margin:0;font-size:20px;}
            .camera-scan-sub{margin:5px 0 14px;color:#64748b;font-size:13px;line-height:1.5;}
            .camera-scan-close{width:38px;height:38px;border:0;border-radius:10px;background:#eef2f7;color:#334155;font-size:23px;cursor:pointer;}
            .camera-scan-reader{width:100%;min-height:260px;overflow:hidden;border:1px solid #dbe3ef;border-radius:12px;background:#0f172a;}
            .camera-scan-reader video{object-fit:cover!important;}
            .camera-scan-status{min-height:22px;margin:12px 0;color:#475569;font-size:13px;line-height:1.5;overflow-wrap:anywhere;}
            .camera-scan-status.error{color:#b42318;}
            .camera-scan-status.success{color:#067647;}
            .camera-scan-actions{display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-end;margin-top:8px;}
            .camera-scan-actions button{border:0;border-radius:9px;padding:10px 14px;font:inherit;font-size:12px;font-weight:700;cursor:pointer;}
            .camera-scan-next{background:#2563eb;color:#fff;}
            .camera-scan-cancel{background:#edf2f7;color:#25344b;}
            @media(max-width:520px){.camera-scan-field{flex-direction:column;}.camera-scan-btn{width:100%;}.camera-scan-dialog{padding:14px;}}
        `;
        document.head.appendChild(style);
    }

    function setStatus(message, type = '') {
        const status = modal?.querySelector('[data-camera-status]');
        if (!status) return;
        status.textContent = message;
        status.classList.toggle('error', type === 'error');
        status.classList.toggle('success', type === 'success');
    }

    function ensureModal() {
        if (modal) return;

        modal = document.createElement('div');
        modal.className = 'camera-scan-overlay';
        modal.hidden = true;
        modal.innerHTML = `
            <section class="camera-scan-dialog" role="dialog" aria-modal="true" aria-labelledby="camera-scan-title">
                <div class="camera-scan-head">
                    <div>
                        <h2 id="camera-scan-title">Scan Barcode dengan Kamera</h2>
                        <p class="camera-scan-sub">Arahkan kamera ke QR Code atau barcode. Hasil scan akan dimasukkan ke kolom transaksi dan tetap perlu diperiksa sebelum disimpan.</p>
                    </div>
                    <button type="button" class="camera-scan-close" data-camera-close aria-label="Tutup">×</button>
                </div>
                <div id="camera-scan-reader" class="camera-scan-reader"></div>
                <p class="camera-scan-status" data-camera-status aria-live="polite">Meminta izin kamera…</p>
                <div class="camera-scan-actions">
                    <button type="button" class="camera-scan-next" data-camera-next hidden>Scan QR Berikutnya</button>
                    <button type="button" class="camera-scan-cancel" data-camera-close>Tutup Kamera</button>
                </div>
            </section>`;
        document.body.appendChild(modal);

        modal.querySelectorAll('[data-camera-close]').forEach((button) => button.addEventListener('click', closeScanner));
        modal.querySelector('[data-camera-next]').addEventListener('click', resumeScanner);
        modal.addEventListener('click', (event) => {
            if (event.target === modal) closeScanner();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal && !modal.hidden) closeScanner();
        });
    }

    function fireFieldEvents(field) {
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function barcodeKey(raw) {
        const value = String(raw || '').trim();
        if (!value) return '';

        try {
            const parsed = JSON.parse(value);
            const data = parsed && typeof parsed.data === 'object' && parsed.data ? parsed.data : parsed;
            const key = data.box_barcode || data.boxBarcode || data.barcode || data.B;
            if (key) return String(key).trim();
        } catch (_) {
            // Supplier QR can also use the compact STK1|B=... format.
        }

        const match = value.match(/(?:^|[|;\r\n])(?:B|box_barcode|boxbarcode|barcode)\s*[:=]\s*([^|;\r\n]+)/i);
        return match ? match[1].trim() : value;
    }

    function putScanIntoField(decodedText) {
        if (!targetField) return;

        const value = String(decodedText || '').trim();
        if (!value) return;

        if (targetField instanceof HTMLTextAreaElement) {
            const key = barcodeKey(value);
            const currentLines = targetField.value.split(/\r?\n/).map((line) => line.trim()).filter(Boolean);
            const existingKeys = currentLines.map(barcodeKey);
            if (existingKeys.includes(key)) {
                // The scan callback pauses the camera before this function runs.
                // Keep the resume action visible even when a duplicate is rejected.
                modal.querySelector('[data-camera-next]').hidden = false;
                setStatus(`Barcode ${key} sudah ada di daftar. Tekan “Scan QR Berikutnya” untuk melanjutkan.`, 'error');
                return;
            }
            targetField.value = [...currentLines, value].join('\n');
            fireFieldEvents(targetField);
            setStatus(`Barcode ${key} berhasil ditambahkan. Tekan “Scan QR Berikutnya” untuk memindai box lain.`, 'success');
            modal.querySelector('[data-camera-next]').hidden = false;
            targetField.focus();
            return;
        }

        targetField.value = value;
        fireFieldEvents(targetField);
        targetField.focus();
        setStatus('Barcode berhasil dimasukkan ke formulir. Periksa data sebelum menyimpan.', 'success');
        window.setTimeout(closeScanner, 450);
    }

    async function startScanner() {
        const reader = modal.querySelector('#camera-scan-reader');
        const nextButton = modal.querySelector('[data-camera-next]');
        nextButton.hidden = true;
        reader.innerHTML = '';

        if (!window.Html5Qrcode) {
            setStatus('Library scanner tidak ditemukan. Muat ulang halaman dan coba kembali.', 'error');
            return;
        }

        if (!navigator.mediaDevices?.getUserMedia) {
            setStatus('Browser tidak mendukung akses kamera atau halaman tidak menggunakan HTTPS. Buka aplikasi melalui domain HTTPS Laravel Cloud.', 'error');
            return;
        }

        try {
            scanner = new window.Html5Qrcode('camera-scan-reader', { verbose: false });
            const supportedFormats = window.Html5QrcodeSupportedFormats;
            const formats = supportedFormats ? [
                supportedFormats.QR_CODE,
                supportedFormats.CODE_128,
                supportedFormats.CODE_39,
                supportedFormats.EAN_13,
                supportedFormats.UPC_A,
            ] : undefined;

            await scanner.start(
                { facingMode: 'environment' },
                {
                    fps: 10,
                    qrbox: (width, height) => {
                        const size = Math.max(160, Math.floor(Math.min(width, height) * 0.72));
                        return { width: Math.min(size, width), height: Math.min(size, height) };
                    },
                    aspectRatio: 1,
                    formatsToSupport: formats,
                    rememberLastUsedCamera: true,
                },
                (decodedText) => {
                    const now = Date.now();
                    if (now - lastAcceptedAt < 1300) return;
                    lastAcceptedAt = now;

                    if (scanner && typeof scanner.pause === 'function') {
                        try { scanner.pause(true); } catch (_) { /* Continue with the decoded value. */ }
                    }
                    putScanIntoField(decodedText);
                },
                () => {
                    // No QR in this frame. Keep the live camera running.
                }
            );
            setStatus('Kamera aktif. Arahkan ke QR Code atau barcode.', 'success');
        } catch (error) {
            const message = error?.message || String(error || 'Izin kamera gagal diberikan.');
            setStatus(`Kamera tidak dapat dibuka: ${message}. Pastikan izin kamera di browser diaktifkan.`, 'error');
            if (scanner) {
                try { await scanner.clear(); } catch (_) { /* Ignore cleanup errors. */ }
                scanner = null;
            }
        }
    }

    async function openScanner(field) {
        targetField = field;
        lastAcceptedAt = 0;
        ensureModal();
        modal.hidden = false;
        setStatus('Meminta izin kamera…');
        await startScanner();
    }

    async function resumeScanner() {
        const nextButton = modal?.querySelector('[data-camera-next]');
        if (!scanner || !modal || modal.hidden) return;

        try {
            lastAcceptedAt = Date.now();
            if (typeof scanner.resume === 'function') {
                scanner.resume();
                if (nextButton) nextButton.hidden = true;
                setStatus('Kamera aktif. Arahkan ke barcode berikutnya.');
            } else {
                const fieldToResume = targetField;
                await closeScanner();
                if (fieldToResume) await openScanner(fieldToResume);
            }
        } catch (error) {
            setStatus(error?.message || 'Scanner tidak dapat dilanjutkan. Tutup kamera lalu buka kembali.', 'error');
        }
    }

    async function closeScanner() {
        const activeScanner = scanner;
        scanner = null;
        if (activeScanner) {
            try {
                if (activeScanner.isScanning) await activeScanner.stop();
            } catch (_) {
                // Camera might already have stopped after a browser permission change.
            }
            try { await activeScanner.clear(); } catch (_) { /* Reader cleanup is best effort. */ }
        }
        if (modal) modal.hidden = true;
        targetField?.focus();
        targetField = null;
    }

    function enhanceFields() {
        document.querySelectorAll(FIELD_SELECTOR).forEach((field) => {
            if (field.dataset.cameraScannerReady === 'true' || !field.parentElement) return;
            field.dataset.cameraScannerReady = 'true';

            const wrapper = document.createElement('div');
            wrapper.className = 'camera-scan-field';
            field.parentElement.insertBefore(wrapper, field);
            wrapper.appendChild(field);

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn secondary small camera-scan-btn';
            button.textContent = '▣ Buka Kamera';
            button.setAttribute('aria-label', `Buka kamera untuk scan ${field.name || 'barcode'}`);
            button.addEventListener('click', () => openScanner(field));
            wrapper.appendChild(button);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        addStyles();
        enhanceFields();
    });
})();
