(function (global) {
    'use strict';

    // Offline QR Code Model 2 encoder: Version 2-L, byte mode, mask 0.
    // Generated Part Tag values are short ASCII identifiers (e.g. TAG-YYMMDD-HHMMSS-XXXX).
    const SIZE = 25;
    const DATA_CODEWORDS = 34;
    const ECC_CODEWORDS = 10;
    const EXP = new Array(512);
    const LOG = new Array(256);

    (function buildGaloisTables() {
        let value = 1;
        for (let i = 0; i < 255; i++) {
            EXP[i] = value;
            LOG[value] = i;
            value <<= 1;
            if (value & 0x100) value ^= 0x11d;
        }
        for (let i = 255; i < 512; i++) EXP[i] = EXP[i - 255];
    }());

    function gfMultiply(a, b) {
        return (!a || !b) ? 0 : EXP[LOG[a] + LOG[b]];
    }

    function multiplyPolynomials(a, b) {
        const output = new Array(a.length + b.length - 1).fill(0);
        for (let i = 0; i < a.length; i++) {
            for (let j = 0; j < b.length; j++) output[i + j] ^= gfMultiply(a[i], b[j]);
        }
        return output;
    }

    function makeErrorCorrection(data) {
        let generator = [1];
        for (let i = 0; i < ECC_CODEWORDS; i++) generator = multiplyPolynomials(generator, [1, EXP[i]]);
        const remainder = data.concat(new Array(ECC_CODEWORDS).fill(0));
        for (let i = 0; i < data.length; i++) {
            const factor = remainder[i];
            if (factor) {
                for (let j = 0; j < generator.length; j++) remainder[i + j] ^= gfMultiply(generator[j], factor);
            }
        }
        return remainder.slice(data.length);
    }

    function appendBits(bits, value, length) {
        for (let i = length - 1; i >= 0; i--) bits.push((value >>> i) & 1);
    }

    function makeCodewords(text) {
        const bytes = Array.from(new TextEncoder().encode(text));
        if (bytes.length > 32) throw new Error('Kode Part Tag melebihi kapasitas QR.');

        const bits = [];
        appendBits(bits, 0x4, 4);       // Byte mode.
        appendBits(bits, bytes.length, 8); // Character count for versions 1-9.
        bytes.forEach((byte) => appendBits(bits, byte, 8));

        const maximumBits = DATA_CODEWORDS * 8;
        for (let i = 0; i < 4 && bits.length < maximumBits; i++) bits.push(0);
        while (bits.length % 8) bits.push(0);

        const data = [];
        for (let i = 0; i < bits.length; i += 8) {
            let value = 0;
            for (let j = 0; j < 8; j++) value = (value << 1) | bits[i + j];
            data.push(value);
        }
        let pad = 0;
        while (data.length < DATA_CODEWORDS) data.push(pad++ % 2 === 0 ? 0xec : 0x11);
        return data.concat(makeErrorCorrection(data));
    }

    function makeMatrix(text) {
        const codewords = makeCodewords(text);
        const dataBits = [];
        codewords.forEach((word) => appendBits(dataBits, word, 8));

        const matrix = Array.from({ length: SIZE }, () => new Array(SIZE).fill(null));
        const functional = Array.from({ length: SIZE }, () => new Array(SIZE).fill(false));

        function setFunction(x, y, dark) {
            if (x < 0 || x >= SIZE || y < 0 || y >= SIZE) return;
            matrix[y][x] = Boolean(dark);
            functional[y][x] = true;
        }

        function drawFinder(x0, y0) {
            for (let dy = -1; dy <= 7; dy++) {
                for (let dx = -1; dx <= 7; dx++) {
                    const x = x0 + dx;
                    const y = y0 + dy;
                    if (x < 0 || x >= SIZE || y < 0 || y >= SIZE) continue;
                    const inside = dx >= 0 && dx <= 6 && dy >= 0 && dy <= 6;
                    const dark = inside && (
                        dx === 0 || dx === 6 || dy === 0 || dy === 6 ||
                        (dx >= 2 && dx <= 4 && dy >= 2 && dy <= 4)
                    );
                    setFunction(x, y, dark);
                }
            }
        }

        drawFinder(0, 0);
        drawFinder(SIZE - 7, 0);
        drawFinder(0, SIZE - 7);

        for (let i = 8; i <= SIZE - 9; i++) {
            setFunction(i, 6, i % 2 === 0);
            setFunction(6, i, i % 2 === 0);
        }

        // Alignment pattern for Version 2, centered at (18, 18).
        for (let dy = -2; dy <= 2; dy++) {
            for (let dx = -2; dx <= 2; dx++) {
                setFunction(18 + dx, 18 + dy, Math.max(Math.abs(dx), Math.abs(dy)) !== 1);
            }
        }

        // Format information: level L (01), mask 0.
        const formatData = (1 << 3);
        let remainder = formatData;
        for (let i = 0; i < 10; i++) remainder = (remainder << 1) ^ ((remainder >>> 9) * 0x537);
        const formatBits = ((formatData << 10) | remainder) ^ 0x5412;
        const formatBit = (index) => ((formatBits >>> index) & 1) !== 0;

        for (let i = 0; i <= 5; i++) setFunction(8, i, formatBit(i));
        setFunction(8, 7, formatBit(6));
        setFunction(8, 8, formatBit(7));
        setFunction(7, 8, formatBit(8));
        for (let i = 9; i < 15; i++) setFunction(14 - i, 8, formatBit(i));
        for (let i = 0; i < 8; i++) setFunction(SIZE - 1 - i, 8, formatBit(i));
        for (let i = 8; i < 15; i++) setFunction(8, SIZE - 15 + i, formatBit(i));
        setFunction(8, SIZE - 8, true); // Fixed dark module.

        let bitIndex = 0;
        let upward = true;
        for (let right = SIZE - 1; right >= 1; right -= 2) {
            if (right === 6) right = 5;
            for (let vertical = 0; vertical < SIZE; vertical++) {
                const y = upward ? SIZE - 1 - vertical : vertical;
                for (let offset = 0; offset < 2; offset++) {
                    const x = right - offset;
                    if (functional[y][x]) continue;
                    let dark = bitIndex < dataBits.length ? dataBits[bitIndex] === 1 : false;
                    bitIndex++;
                    if ((x + y) % 2 === 0) dark = !dark; // Mask pattern 0.
                    matrix[y][x] = dark;
                }
            }
            upward = !upward;
        }
        return matrix;
    }

    function toSvg(text, scale, quietZone) {
        const matrix = makeMatrix(text);
        const margin = Number.isInteger(quietZone) ? quietZone : 4;
        const pixelScale = Number.isInteger(scale) ? scale : 8;
        const viewSize = SIZE + margin * 2;
        let path = '';
        for (let y = 0; y < SIZE; y++) {
            for (let x = 0; x < SIZE; x++) {
                if (matrix[y][x]) path += `M${x + margin},${y + margin}h1v1h-1z`;
            }
        }
        return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${viewSize} ${viewSize}" width="${viewSize * pixelScale}" height="${viewSize * pixelScale}" role="img" aria-label="QR Code Part Tag" shape-rendering="crispEdges"><rect width="100%" height="100%" fill="#fff"/><path d="${path}" fill="#111827"/></svg>`;
    }

    function mount() {
        document.querySelectorAll('[data-stock-qr]').forEach((element) => {
            const payload = element.getAttribute('data-value') || '';
            try {
                element.innerHTML = toSvg(payload, 9, 4);
                element.classList.remove('qr-error');
            } catch (error) {
                element.textContent = 'QR tidak dapat dibuat: ' + error.message;
                element.classList.add('qr-error');
            }
        });
    }

    global.StockTrackingQR = { makeMatrix, toSvg, mount };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mount);
    else mount();
}(window));
