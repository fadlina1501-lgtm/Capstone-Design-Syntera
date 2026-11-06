/*
 * Autofill untuk payload QR supplier demo Stock Tracking.
 * Format pendek contoh: STK1|B=SUP-AST-26018-01|P=CM-AST-401|S=SUP-DEMO-AST|Q=40|PO=PO26001|D=2026-10-18|N=1|R=D1-1
 * B=box barcode, P=part number, S=supplier code, Q=qty, PO=PO number,
 * D=delivery/receiving date, N=box sequence, R=rack/shelf.
 */
(() => {
    'use strict';

    const aliases = {
        box_barcode: ['boxbarcode', 'barcode', 'barcodebox', 'boxid', 'boxcode', 'b'],
        part_number: ['partnumber', 'partno', 'part', 'pn', 'p'],
        supplier_code: ['suppliercode', 'supplier', 'vendorcode', 'vendor', 's'],
        qty_in_box: ['qtyinbox', 'quantityinbox', 'boxquantity', 'quantity', 'qty', 'q'],
        po_number: ['ponumber', 'purchaseorder', 'purchaseordernumber', 'po'],
        delivery_date: ['deliverydate', 'receivingdate', 'receiveddate', 'date', 'd'],
        box_sequence: ['boxsequence', 'sequencenumber', 'sequence', 'seq', 'n'],
        shelf_no: ['shelfno', 'shelfnumber', 'rack', 'rackshelf', 'shelf', 'r'],
        part_id: ['partid'],
        supplier_id: ['supplierid'],
    };

    const normalizeKey = (key) => String(key || '').toLowerCase().replace(/[^a-z0-9]/g, '');
    const keyMap = new Map();
    Object.entries(aliases).forEach(([canonical, names]) => names.forEach((n) => keyMap.set(normalizeKey(n), canonical)));

    function parsePayload(raw) {
        const text = String(raw || '').trim();
        if (!text) return null;

        let source = null;
        if (text.startsWith('{') && text.endsWith('}')) {
            try {
                source = JSON.parse(text);
                if (source && typeof source.data === 'object' && source.data) source = source.data;
            } catch (_) {
                return null;
            }
        } else if (text.startsWith('STK1|')) {
            source = {};
            text.split('|').slice(1).forEach((pair) => {
                const at = pair.indexOf('=');
                if (at < 1) return;
                const shortKey = pair.slice(0, at).trim();
                const value = pair.slice(at + 1).trim();
                const canonical = keyMap.get(normalizeKey(shortKey));
                if (canonical) source[canonical] = value;
            });
        } else if (/[|;\n]/.test(text) && /[=:]/.test(text)) {
            source = {};
            text.split(/[|;\r\n]+/).forEach((part) => {
                const match = part.match(/^\s*([^:=]+?)\s*[:=]\s*(.*?)\s*$/);
                if (!match) return;
                const canonical = keyMap.get(normalizeKey(match[1]));
                if (canonical) source[canonical] = match[2];
            });
        } else {
            return null;
        }

        if (!source || typeof source !== 'object' || Array.isArray(source)) return null;
        const result = {};
        Object.entries(source).forEach(([key, value]) => {
            const canonical = keyMap.get(normalizeKey(key));
            if (canonical && value !== null && value !== undefined && String(value).trim() !== '') {
                result[canonical] = String(value).trim();
            }
        });
        if (!result.box_barcode) return null;
        return result;
    }

    function formatDate(value) {
        const text = String(value || '').trim();
        if (/^\d{4}-\d{2}-\d{2}$/.test(text)) return text;
        const match = text.match(/^(\d{1,2})[/-](\d{1,2})[/-](\d{4})$/);
        if (match) return `${match[3]}-${match[2].padStart(2, '0')}-${match[1].padStart(2, '0')}`;
        return text;
    }

    function notify(form, message, isError = false) {
        if (!form) return;
        let node = form.querySelector('[data-supplier-qr-status]');
        if (!node) {
            node = document.createElement('p');
            node.dataset.supplierQrStatus = 'true';
            node.setAttribute('aria-live', 'polite');
            node.style.cssText = 'margin:8px 0 0;font-size:12px;line-height:1.45;color:#067647;';
            const barcode = form.querySelector('[name="box_barcode"]');
            const host = barcode?.closest('.field') || barcode?.parentElement || form;
            host.appendChild(node);
        }
        node.textContent = message;
        node.style.color = isError ? '#b42318' : '#067647';
    }

    function setValue(form, name, value, options = {}) {
        if (value === undefined || value === null || String(value).trim() === '') return false;
        const control = form.querySelector(`[name="${name}"]`);
        if (!control) return false;
        const target = String(value).trim();

        if (control instanceof HTMLSelectElement && options.matchPrefix) {
            const targetNorm = target.toUpperCase();
            const option = Array.from(control.options).find((opt) => {
                if (!opt.value) return false;
                const text = opt.textContent.trim().toUpperCase();
                return opt.value === target || text === targetNorm || text.startsWith(`${targetNorm} —`) || text.startsWith(`${targetNorm} -`) || text.startsWith(`${targetNorm} `) || text.includes(` ${targetNorm} `);
            });
            if (!option) return false;
            control.value = option.value;
        } else {
            control.value = options.date ? formatDate(target) : target;
        }
        control.dispatchEvent(new Event('input', { bubbles: true }));
        control.dispatchEvent(new Event('change', { bubbles: true }));
        return true;
    }

    function fillIncomingForm(input, payload) {
        const form = input.form;
        if (!form) return false;
        const partSelect = form.querySelector('[name="part_id"]');
        // Only run full autofill on the Incoming Warehouse receiving form.
        if (!partSelect) return false;

        input.value = payload.box_barcode;
        if (payload.part_id) setValue(form, 'part_id', payload.part_id);
        else if (payload.part_number) setValue(form, 'part_id', payload.part_number, { matchPrefix: true });

        if (payload.supplier_id) setValue(form, 'supplier_id', payload.supplier_id);
        else if (payload.supplier_code) setValue(form, 'supplier_id', payload.supplier_code, { matchPrefix: true });

        setValue(form, 'qty_in_box', payload.qty_in_box);
        setValue(form, 'po_number', payload.po_number);
        setValue(form, 'delivery_date', payload.delivery_date, { date: true });
        setValue(form, 'box_sequence', payload.box_sequence);
        setValue(form, 'shelf_no', payload.shelf_no);

        notify(form, `QR supplier terbaca: ${payload.box_barcode}. Data yang tersedia telah diisikan; periksa sebelum menyimpan.`);
        return true;
    }

    function handleInput(event) {
        const field = event.target;
        if (!(field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement)) return;
        if (!field.matches('.scan-input')) return;
        if (field.dataset.supplierQrBusy === '1') return;

        if (field.name === 'box_barcode') {
            const payload = parsePayload(field.value);
            if (!payload) return; // A plain box barcode remains a valid value by itself.
            field.dataset.supplierQrBusy = '1';
            try {
                const isIncomingForm = Boolean(field.form?.querySelector('[name="part_id"]'));
                if (isIncomingForm) fillIncomingForm(field, payload);
                else field.value = payload.box_barcode; // Incoming Rack or other single-barcode transaction.
            } finally {
                delete field.dataset.supplierQrBusy;
            }
            return;
        }

        if (field.name === 'barcodes' && field instanceof HTMLTextAreaElement) {
            const lines = field.value.split(/\r?\n/);
            const index = lines.length - 1;
            const payload = parsePayload(lines[index]);
            if (!payload) return;
            field.dataset.supplierQrBusy = '1';
            try {
                lines[index] = payload.box_barcode;
                field.value = lines.join('\n');
                field.dispatchEvent(new Event('change', { bubbles: true }));
            } finally {
                delete field.dataset.supplierQrBusy;
            }
        }
    }

    document.addEventListener('input', handleInput);
    document.addEventListener('change', handleInput);
})();
