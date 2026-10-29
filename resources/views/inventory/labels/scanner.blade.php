<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pindai QR Inventaris — SIMPRAM</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 560px; margin: 2rem auto; padding: 0 1rem; line-height: 1.5; }
        video { display: block; width: 100%; max-height: 360px; background: #222; border-radius: 12px; }
        input, button { padding: .7rem; margin-top: .6rem; }
        input { width: 100%; box-sizing: border-box; }
        .actions { display: flex; gap: .5rem; flex-wrap: wrap; }
        #message { min-height: 2rem; }
    </style>
</head>
<body>
    <main>
        <h1>Pindai QR Inventaris</h1>
        <p>Gunakan kamera untuk memindai label barang, atau tempel URL verifikasi dari hasil pemindaian.</p>
        <video id="preview" autoplay muted playsinline aria-label="Pratinjau kamera"></video>
        <div class="actions">
            <button type="button" id="start">Mulai kamera</button>
            <button type="button" id="stop">Hentikan kamera</button>
        </div>
        <form id="manual">
            <label for="qr">URL QR inventaris</label>
            <input id="qr" type="text" autocomplete="off" required placeholder="Tempel URL dari QR label">
            <button type="submit">Buka verifikasi</button>
        </form>
        <p id="message" role="status" aria-live="polite"></p>
    </main>
    <script>
        (() => {
            const video = document.getElementById('preview');
            const message = document.getElementById('message');
            let stream = null;
            let scanning = false;

            function stop() {
                scanning = false;
                if (stream) stream.getTracks().forEach(track => track.stop());
                stream = null;
                video.srcObject = null;
            }

            function navigate(raw) {
                try {
                    const url = new URL(raw.trim());
                    if (url.origin !== window.location.origin || !/^\\/inventaris\\/verifikasi\\/\\d+\\/\\d+\\/?$/.test(url.pathname) || url.search || url.hash) {
                        throw new Error('QR bukan tautan verifikasi inventaris SIMPRAM pada server ini.');
                    }
                    stop();
                    window.location.assign(url.href);
                } catch (error) {
                    message.textContent = error.message || 'QR tidak valid.';
                }
            }

            async function start() {
                stop();
                if (!navigator.mediaDevices?.getUserMedia || !('BarcodeDetector' in window)) {
                    message.textContent = 'Pemindaian kamera tidak tersedia di browser ini. Gunakan pemindai QR bawaan HP lalu buka tautan, atau tempel URL di bawah.';
                    return;
                }
                try {
                    const detector = new BarcodeDetector({ formats: ['qr_code'] });
                    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
                    video.srcObject = stream;
                    await video.play();
                    scanning = true;
                    message.textContent = 'Arahkan kamera ke QR inventaris.';
                    while (scanning) {
                        const codes = await detector.detect(video);
                        if (codes.length) {
                            navigate(codes[0].rawValue);
                            break;
                        }
                        await new Promise(resolve => requestAnimationFrame(resolve));
                    }
                } catch (error) {
                    message.textContent = 'Kamera gagal diakses. Periksa izin kamera dan gunakan HTTPS/localhost.';
                    stop();
                }
            }

            document.getElementById('start').addEventListener('click', start);
            document.getElementById('stop').addEventListener('click', stop);
            document.getElementById('manual').addEventListener('submit', event => {
                event.preventDefault();
                navigate(document.getElementById('qr').value);
            });
            window.addEventListener('pagehide', stop);
        })();
    </script>
</body>
</html>
