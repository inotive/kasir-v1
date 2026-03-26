import { Html5Qrcode } from 'html5-qrcode';

function el(id) {
    return document.getElementById(id);
}

function setStatus(message, tone = 'info') {
    const status = el('scan-status');
    if (!status) {
        return;
    }

    if (!message) {
        status.textContent = '';
        status.classList.add('hidden');
        status.classList.remove('border-error-200', 'bg-error-50', 'text-error-700', 'border-success-200', 'bg-success-50', 'text-success-700');
        return;
    }

    status.textContent = message;
    status.classList.remove('hidden');
    status.classList.remove('border-error-200', 'bg-error-50', 'text-error-700', 'border-success-200', 'bg-success-50', 'text-success-700');
    status.classList.add('border-gray-200/80', 'bg-white', 'text-gray-700');

    if (tone === 'error') {
        status.classList.remove('border-gray-200/80', 'bg-white', 'text-gray-700');
        status.classList.add('border-error-200', 'bg-error-50', 'text-error-700');
    }

    if (tone === 'success') {
        status.classList.remove('border-gray-200/80', 'bg-white', 'text-gray-700');
        status.classList.add('border-success-200', 'bg-success-50', 'text-success-700');
    }
}

function parseQrPayload(decodedText) {
    const text = String(decodedText || '').trim();
    if (!text) {
        return null;
    }

    if (text.startsWith('http://') || text.startsWith('https://')) {
        const url = new URL(text);
        const parts = url.pathname.split('/').filter(Boolean);
        const tIndex = parts.findIndex((p) => p === 't');
        if (tIndex !== -1 && parts[tIndex + 1]) {
            return parts[tIndex + 1];
        }

        return parts[parts.length - 1] || text;
    }

    return text;
}

function redirectToTable(code) {
    window.location.href = '/t/' + encodeURIComponent(code);
}

let qr = null;
let isRunning = false;
let isStarting = false;
let bound = false;

async function stop() {
    if (!qr) {
        return;
    }

    if (isRunning) {
        try {
            await qr.stop();
        } catch (e) {
        }
        isRunning = false;
    }

    try {
        await qr.clear();
    } catch (e) {
    }
}

async function startCamera() {
    const reader = el('reader');
    if (!reader) {
        return;
    }

    const startBtn = el('start-scan');
    if (startBtn) {
        startBtn.disabled = true;
        startBtn.classList.add('opacity-60');
    }

    if (isStarting) {
        return;
    }
    isStarting = true;

    try {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            setStatus('Browser tidak mendukung akses kamera. Gunakan browser lain atau scan dari foto.', 'error');
            return;
        }

        setStatus('Meminta izin kamera...', 'info');

        if (!qr) {
            qr = new Html5Qrcode('reader');
        }

        await stop();

        const config = {
            fps: 10,
            qrbox: { width: 250, height: 250 },
        };

        try {
            await qr.start(
                { facingMode: 'environment' },
                config,
                async (decodedText) => {
                    const code = parseQrPayload(decodedText);
                    if (!code) {
                        setStatus('QR tidak valid. Coba ulangi.', 'error');
                        return;
                    }

                    await stop();
                    redirectToTable(code);
                },
                () => {
                }
            );
        } catch (error) {
            const cameras = await Html5Qrcode.getCameras();
            if (!Array.isArray(cameras) || cameras.length === 0) {
                throw error;
            }

            const preferred =
                cameras.find((c) => /back|rear|environment/i.test(String(c.label || ''))) ??
                cameras[cameras.length - 1];

            await qr.start(
                preferred.id,
                config,
                async (decodedText) => {
                    const code = parseQrPayload(decodedText);
                    if (!code) {
                        setStatus('QR tidak valid. Coba ulangi.', 'error');
                        return;
                    }

                    await stop();
                    redirectToTable(code);
                },
                () => {
                }
            );
        }

        isRunning = true;
        setStatus('Kamera aktif. Arahkan ke QR meja.', 'success');
    } catch (error) {
        const name = error && error.name ? String(error.name) : '';
        const rawDetail = error && (error.message || error.toString) ? String(error.message || error.toString()) : '';
        const detail = rawDetail && rawDetail !== '[object Object]' ? rawDetail : '';
        let message = 'Kamera gagal dibuka. Pastikan izin kamera aktif dan coba ulangi.';

        if (name === 'NotAllowedError' || name === 'SecurityError') {
            message = 'Izin kamera ditolak. Aktifkan izin kamera pada browser, lalu klik “Aktifkan Kamera” lagi.';
        } else if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
            message = 'Kamera tidak ditemukan pada perangkat ini.';
        } else if (name === 'NotReadableError' || name === 'TrackStartError') {
            message = 'Kamera sedang dipakai aplikasi lain. Tutup aplikasi kamera lain lalu coba lagi.';
        } else if (name === 'OverconstrainedError') {
            message = 'Kamera belakang tidak tersedia. Coba ulangi atau gunakan scan dari foto.';
        }

        if (detail && !message.includes(detail)) {
            message = message + ' (' + detail + ')';
        }

        setStatus(message, 'error');
    } finally {
        isStarting = false;
        if (startBtn) {
            startBtn.disabled = false;
            startBtn.classList.remove('opacity-60');
        }
    }
}

async function scanFromFile(file) {
    const reader = el('reader');
    if (!reader) {
        return;
    }

    if (!file) {
        return;
    }

    try {
        setStatus('Memindai foto...', 'info');

        if (!qr) {
            qr = new Html5Qrcode('reader');
        }

        await stop();

        const decodedText = await qr.scanFile(file, true);
        const code = parseQrPayload(decodedText);
        if (!code) {
            setStatus('QR tidak valid. Coba ulangi.', 'error');
            return;
        }

        await stop();
        redirectToTable(code);
    } catch (e) {
        setStatus('Foto tidak terbaca. Pastikan QR jelas dan tidak blur.', 'error');
    }
}

export function initQrScanner() {
    const reader = el('reader');
    const startBtn = el('start-scan');
    const input = el('qr-input-file');

    if (!reader || !startBtn || !input) {
        void stop();
        bound = false;
        return;
    }

    if (bound) {
        return;
    }
    bound = true;

    setStatus('', 'info');

    startBtn.addEventListener('click', () => void startCamera());
    input.addEventListener('change', async (e) => {
        const files = e.target && e.target.files ? e.target.files : null;
        const file = files && files.length ? files[0] : null;
        e.target.value = '';
        await scanFromFile(file);
    });

    window.addEventListener('pagehide', () => void stop());
    document.addEventListener('livewire:navigating', () => void stop());
}
