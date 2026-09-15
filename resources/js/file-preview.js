let pendingPreview = null;

document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');
    if (link && !link.hasAttribute('download') && /\.(docx?|xlsx?|pptx?|zip|rar)$/i.test(new URL(link.href).pathname)) {
        event.preventDefault();
        const tab = window.open('about:blank', '_blank');
        if (tab) {
            tab.opener = null;
            tab.document.title = 'Pratinjau berkas';
            tab.document.body.style.cssText = 'font:16px system-ui;margin:32px;color:#18181b';
            const title = tab.document.createElement('h1');
            title.textContent = link.textContent.trim() || 'Pratinjau berkas';
            const note = tab.document.createElement('p');
            note.textContent = 'Format ini tidak didukung penampil bawaan browser. Unduh berkas untuk melihat isinya di aplikasi yang sesuai.';
            const download = tab.document.createElement('a');
            download.href = link.href;
            download.download = '';
            download.textContent = 'Unduh berkas';
            tab.document.body.append(title, note, download);
        }
        return;
    }
    const action = event.target.closest('[wire\\:click]');
    if (action && /^(exportCsv|exportExcel|downloadCsvTemplate)(\(|$)/.test(action.getAttribute('wire:click'))) {
        pendingPreview = window.open('about:blank', '_blank');
        if (pendingPreview) {
            pendingPreview.opener = null;
            pendingPreview.document.title = 'Menyiapkan pratinjau';
            pendingPreview.document.body.textContent = 'Menyiapkan berkas… Jika proses gagal, tutup tab ini dan periksa pesan pada form.';
        }
    }
}, true);

window.addEventListener('file-preview', (event) => {
    const file = event.detail;
    const tab = pendingPreview && !pendingPreview.closed ? pendingPreview : window.open('about:blank', '_blank');
    pendingPreview = null;
    if (!tab) {
        window.alert('Izinkan tab baru untuk membuka pratinjau, kemudian ulangi ekspor.');
        return;
    }
    tab.opener = null;
    const bytes = Uint8Array.from(atob(file.content), (character) => character.charCodeAt(0));
    const mime = (file.contentType || '').split(';')[0];
    const url = URL.createObjectURL(new Blob([bytes], { type: mime }));
    const doc = tab.document;
    doc.title = file.name;
    doc.documentElement.lang = 'id';
    doc.body.replaceChildren();
    doc.body.style.cssText = 'font:16px system-ui;margin:32px;color:#18181b;background:#fafafa';
    const title = doc.createElement('h1');
    title.textContent = file.name;
    const download = doc.createElement('a');
    download.href = url;
    download.download = file.name;
    download.textContent = 'Unduh berkas';
    doc.body.append(title, download);
    if (file.previewText !== null && file.previewText !== undefined || ['text/csv', 'text/plain'].includes(mime) || /\.csv$/i.test(file.name)) {
        const preview = doc.createElement('pre');
        preview.style.cssText = 'white-space:pre-wrap;overflow-wrap:anywhere;background:white;padding:24px';
        preview.textContent = file.previewText ?? new TextDecoder().decode(bytes);
        if (file.previewText !== null && file.previewText !== undefined) {
            const note = doc.createElement('p');
            note.textContent = 'Pratinjau teks lembar pertama, maksimal 200 baris dan 52 kolom. Unduh untuk melihat tata letak dan isi lengkap.';
            doc.body.append(note);
        }
        doc.body.append(preview);
    } else if (['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/gif'].includes(mime)) {
        const preview = doc.createElement('iframe');
        preview.src = url;
        preview.title = 'Isi berkas';
        preview.style.cssText = 'display:block;width:100%;height:80vh;border:0;margin-top:24px';
        doc.body.append(preview);
    } else {
        const note = doc.createElement('p');
        note.textContent = 'Format ini tidak didukung penampil bawaan browser. Unduh berkas untuk membukanya di aplikasi yang sesuai.';
        doc.body.append(note);
    }
    tab.addEventListener('beforeunload', () => URL.revokeObjectURL(url), { once: true });
});
