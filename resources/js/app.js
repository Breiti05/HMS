
// Verladescan: Kamera (html5-qrcode wird nur auf der Scan-Seite nachgeladen)
const scannerEl = document.getElementById('scanner');

if (scannerEl) {
    const out = document.getElementById('ergebnis');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    let busy = false;

    const show = (ok, text) => {
        out.hidden = false;
        out.className = 'ergebnis ' + (ok ? 'ok' : 'fehler-box');
        out.textContent = text;
    };

    const send = async (payload) => {
        if (busy) return;
        busy = true;
        try {
            const res = await fetch('/scan', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ payload }),
            });
            const data = await res.json();
            show(res.ok, res.ok
                ? `Verladen: ${data.quantity} ${data.unit} ${data.article} → ${data.site} (${data.site_number})`
                : data.message || 'Scan nicht möglich.');
        } catch {
            show(false, 'Keine Verbindung. Bitte erneut versuchen.');
        } finally {
            setTimeout(() => { busy = false; }, 2500);
        }
    };

    document.getElementById('manuell').addEventListener('submit', (e) => {
        e.preventDefault();
        send(new FormData(e.target).get('payload'));
    });

    import('html5-qrcode').then(({ Html5Qrcode }) => {
        const scanner = new Html5Qrcode('scanner');
        scanner
            .start({ facingMode: 'environment' }, { fps: 10, qrbox: 240 }, (text) => send(text))
            .catch(() => show(false, 'Kamera nicht verfügbar. Etiketteninhalt kann unten eingegeben werden.'));
    });
}
