export function inventoryLoanScanner(wire) {
    let stream = null;
    let generation = 0;
    return {
        scanning: false,
        starting: false,
        message: '',
        stop() {
            generation++;
            this.scanning = false;
            this.starting = false;
            stream?.getTracks().forEach(track => track.stop());
            stream = null;
            if (this.$refs.video) this.$refs.video.srcObject = null;
        },
        destroy() {
            this.stop();
        },
        async start() {
            this.stop();
            const current = generation;
            this.message = '';
            if (!navigator.mediaDevices?.getUserMedia || !('BarcodeDetector' in globalThis)) {
                this.message = 'Scan kamera memerlukan HTTPS/localhost dan browser yang mendukung pembaca QR (misalnya Chrome Android). Gunakan scanner eksternal atau ketik kode jika tidak tersedia.';
                return;
            }
            this.starting = true;
            try {
                const detector = new BarcodeDetector({ formats: ['qr_code'] });
                const acquired = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
                if (current !== generation) {
                    acquired.getTracks().forEach(track => track.stop());
                    return;
                }
                stream = acquired;
                this.scanning = true;
                this.starting = false;
                await this.$nextTick();
                const video = this.$refs.video;
                video.srcObject = stream;
                await video.play();
                while (this.scanning && current === generation) {
                    const codes = await detector.detect(video);
                    if (current !== generation) return;
                    if (codes.length) {
                        this.stop();
                        await wire.$set('itemCode', codes[0].rawValue, false);
                        await wire.findByCode();
                        this.message = 'QR terbaca. Periksa barang dan jumlah, lalu tambahkan ke keranjang.';
                        return;
                    }
                    await new Promise(resolve => setTimeout(resolve, 200));
                }
            } catch {
                if (current !== generation) return;
                this.stop();
                this.message = 'Kamera gagal membaca QR. Periksa izin kamera, HTTPS/localhost, dan dukungan browser. Kode juga dapat diketik atau dipindai dengan scanner eksternal.';
            }
        },
    };
}

if (typeof window !== 'undefined') window.inventoryLoanScanner = inventoryLoanScanner;
