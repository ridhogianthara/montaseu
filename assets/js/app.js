/**
 * Montaseu Studio - General UI Helpers
 */

function updateLiveClock(clockElId, dateElId) {
    const clockEl = document.getElementById(clockElId);
    const dateEl = document.getElementById(dateElId);

    if (!clockEl && !dateEl) return;

    function tick() {
        const now = new Date();
        if (clockEl) {
            const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            clockEl.innerText = timeStr + ' WIB';
        }
        if (dateEl) {
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            dateEl.innerText = now.toLocaleDateString('id-ID', options);
        }
    }

    tick();
    setInterval(tick, 1000);
}

function viewImageModal(src, title) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: title || 'Pratinjau Foto Presensi',
            imageUrl: src,
            imageAlt: 'Foto Presensi',
            background: '#181C23',
            color: '#F9FAFB',
            confirmButtonColor: '#C5A880',
            imageHeight: 380,
            showCloseButton: true
        });
    } else {
        window.open(src, '_blank');
    }
}

function viewMapModal(lat, lng, address, name) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: `Peta Lokasi: ${name || 'Presensi'}`,
            html: `
                <div style="text-align:left; font-size:0.85rem; margin-bottom:10px; color:#9CA3AF;">
                    <strong>Alamat:</strong> ${address || 'Koordinat GPS: ' + lat + ', ' + lng}<br>
                    <strong>Koordinat:</strong> ${lat}, ${lng}
                </div>
                <div id="modal-map-container" style="width:100%; height:300px; border-radius:8px;"></div>
                <div style="margin-top:12px;">
                    <a href="https://www.google.com/maps/search/?api=1&query=${lat},${lng}" target="_blank" class="btn-gold" style="font-size:0.8rem; padding:6px 12px;">
                        <i class="fas fa-external-link-alt"></i> Buka di Google Maps
                    </a>
                </div>
            `,
            background: '#181C23',
            color: '#F9FAFB',
            showConfirmButton: false,
            showCloseButton: true,
            didOpen: () => {
                setTimeout(() => {
                    const m = L.map('modal-map-container').setView([lat, lng], 16);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(m);
                    L.marker([lat, lng]).addTo(m).bindPopup(address || 'Lokasi Presensi').openPopup();
                }, 200);
            }
        });
    }
}
