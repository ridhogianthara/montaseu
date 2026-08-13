<?php
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyName = sanitize($_POST['company_name'] ?? 'Montaseu Studio');
    $officeAddress = sanitize($_POST['office_address'] ?? '');
    $officeLat = sanitize($_POST['office_lat'] ?? '-6.230588');
    $officeLng = sanitize($_POST['office_lng'] ?? '106.808018');
    $officeRadius = sanitize($_POST['office_radius'] ?? '500');
    $workStart = sanitize($_POST['work_start'] ?? '08:30');
    $workEnd = sanitize($_POST['work_end'] ?? '17:30');

    updateSetting('company_name', $companyName);
    updateSetting('office_address', $officeAddress);
    updateSetting('office_lat', $officeLat);
    updateSetting('office_lng', $officeLng);
    updateSetting('office_radius', $officeRadius);
    updateSetting('work_start', $workStart);
    updateSetting('work_end', $workEnd);

    $message = "Pengaturan Studio & Koordinat Kantor Berhasil Diperbarui!";
}

$settings = getSettings();
?>

<div style="margin-bottom: 2rem;">
    <h1 class="brand-title" style="font-size: 1.8rem; margin-bottom: 4px;">Pengaturan Studio & Geofencing</h1>
    <p style="color: var(--text-secondary); font-size: 0.9rem;">
        Konfigurasi Koordinat GPS Kantor Montaseu Studio, Fitur Cari Alamat, & Jam Operasional Kerja
    </p>
</div>

<?php if (!empty($message)): ?>
    <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: var(--accent-emerald); padding: 0.85rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem; text-align: center;">
        <i class="fas fa-check-circle"></i> <?= $message ?>
    </div>
<?php endif; ?>

<form method="POST" action="" id="settings-form">
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem;">
        
        <!-- Form Pengaturan -->
        <div class="card-studio">
            <div class="card-title" style="margin-bottom:1.25rem;">
                <i class="fas fa-sliders-h" style="color:var(--accent-gold);"></i> Data Studio & Jam Operasional
            </div>

            <div class="form-group">
                <label class="form-label">Nama Perusahaan / Studio</label>
                <input type="text" name="company_name" value="<?= sanitize($settings['company_name'] ?? 'Montaseu Studio') ?>" class="form-input" required>
            </div>

            <div class="form-group">
                <label class="form-label">Alamat Lengkap Kantor HQ</label>
                <textarea name="office_address" id="office_address" class="form-textarea" rows="2" required placeholder="Tuliskan nama jalan, kota, dan lokasi kantor..."><?= sanitize($settings['office_address'] ?? '') ?></textarea>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                <div class="form-group">
                    <label class="form-label">Jam Masuk (Batas On-Time)</label>
                    <input type="time" name="work_start" value="<?= sanitize($settings['work_start'] ?? '08:30') ?>" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Jam Pulang Kantor</label>
                    <input type="time" name="work_end" value="<?= sanitize($settings['work_end'] ?? '17:30') ?>" class="form-input" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Radius Toleransi Geofencing Kantor (Meter)</label>
                <input type="number" name="office_radius" value="<?= sanitize($settings['office_radius'] ?? '500') ?>" class="form-input" required placeholder="Contoh: 500">
                <small style="color:var(--text-muted); font-size:0.75rem;">Karyawan di luar radius ini saat presensi Office akan ditandai sebagai Presensi Field / Site Visit.</small>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                <div class="form-group">
                    <label class="form-label">Latitude Kantor</label>
                    <input type="text" name="office_lat" id="office_lat" value="<?= sanitize($settings['office_lat'] ?? '-6.230588') ?>" class="form-input" required readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">Longitude Kantor</label>
                    <input type="text" name="office_lng" id="office_lng" value="<?= sanitize($settings['office_lng'] ?? '106.808018') ?>" class="form-input" required readonly>
                </div>
            </div>

            <button type="submit" class="btn-gold" style="width:100%; margin-top:1rem; padding:0.85rem; font-size:1rem;">
                <i class="fas fa-save"></i> SIMPAN PENGATURAN STUDIO
            </button>
        </div>

        <!-- Interactive Map Picker for Office Coordinates -->
        <div class="card-studio">
            <div class="card-title" style="margin-bottom:0.75rem;">
                <i class="fas fa-map-marker-alt" style="color:var(--accent-gold);"></i> Pick Point & Pencarian Lokasi Kantor
            </div>
            <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:1rem;">
                Gunakan pencarian nama lokasi, tombol GPS saya saat ini, atau klik langsung pada peta untuk menentukan posisi kantor.
            </p>

            <!-- Real-Time Location Search Input -->
            <div style="display:flex; gap:8px; margin-bottom:0.75rem;">
                <input type="text" id="search-address-input" class="form-input" placeholder="Cari alamat / jalan / gedung kantor (misal: Senopati Jakarta)..." style="font-size:0.85rem;" onkeypress="if(event.key === 'Enter') { event.preventDefault(); searchLocationOnMap(); }">
                <button type="button" onclick="searchLocationOnMap()" class="btn-gold" style="font-size:0.8rem; padding:6px 14px; flex-shrink:0;">
                    <i class="fas fa-search"></i> Cari
                </button>
            </div>
            <div id="search-results-list" style="display:none; background:var(--bg-card); border:1px solid var(--border-color); border-radius:6px; max-height:160px; overflow-y:auto; margin-bottom:0.75rem; font-size:0.8rem; padding:4px;"></div>

            <!-- Button Detect Current Admin GPS -->
            <button type="button" id="btn-detect-gps" onclick="useAdminCurrentLocation()" class="btn-secondary" style="font-size:0.8rem; width:100%; margin-bottom:0.75rem;">
                <i class="fas fa-crosshairs" style="color:var(--accent-gold);"></i> Gunakan Lokasi GPS Saya Saat Ini
            </button>

            <div id="office-map-picker" style="width:100%; height:320px; border-radius:var(--radius-md); border:1px solid var(--border-color);"></div>
        </div>
    </div>
</form>

<script>
    let map = null;
    let marker = null;

    document.addEventListener('DOMContentLoaded', function() {
        const curLat = parseFloat(document.getElementById('office_lat').value) || -6.230588;
        const curLng = parseFloat(document.getElementById('office_lng').value) || 106.808018;

        map = L.map('office-map-picker').setView([curLat, curLng], 16);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; Montaseu Studio'
        }).addTo(map);

        marker = L.marker([curLat, curLng], { draggable: true }).addTo(map)
            .bindPopup('<b>Titik Kantor Montaseu Studio</b><br>Geser marker untuk ubah koordinat.')
            .openPopup();

        marker.on('dragend', function(e) {
            const coord = e.target.getLatLng();
            updateCoordinates(coord.lat, coord.lng, true);
        });

        map.on('click', function(e) {
            updateCoordinates(e.latlng.lat, e.latlng.lng, true);
        });

        // Auto-detect Admin GPS location on page load if default values
        if (navigator.geolocation && (curLat === -6.230588 || curLat === 0)) {
            navigator.geolocation.getCurrentPosition(function(pos) {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                updateCoordinates(lat, lng, true);
            }, function(err) {
                console.log("GPS auto-detect skipped:", err.message);
            }, { enableHighAccuracy: true, timeout: 5000 });
        }
    });

    function updateCoordinates(lat, lng, doReverseGeocode = false) {
        const fixedLat = parseFloat(lat).toFixed(6);
        const fixedLng = parseFloat(lng).toFixed(6);

        document.getElementById('office_lat').value = fixedLat;
        document.getElementById('office_lng').value = fixedLng;

        if (marker) {
            marker.setLatLng([lat, lng]);
        }
        if (map) {
            map.panTo([lat, lng]);
        }

        if (doReverseGeocode) {
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${fixedLat}&lon=${fixedLng}`)
                .then(res => res.json())
                .then(data => {
                    if (data && data.display_name) {
                        const addrEl = document.getElementById('office_address');
                        if (addrEl && (!addrEl.value || addrEl.value.trim() === '' || addrEl.value.includes('Senopati'))) {
                            addrEl.value = data.display_name;
                        }
                    }
                })
                .catch(err => console.log("Reverse geocode error:", err));
        }
    }

    function useAdminCurrentLocation() {
        const btn = document.getElementById('btn-detect-gps');
        if (!navigator.geolocation) {
            alert("Browser Anda tidak mendukung fitur Geolocation GPS.");
            return;
        }

        if (btn) btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mendeteksi Lokasi GPS Anda...';

        navigator.geolocation.getCurrentPosition(function(pos) {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            updateCoordinates(lat, lng, true);
            if (map) map.setZoom(17);
            if (btn) btn.innerHTML = '<i class="fas fa-check-circle" style="color:#10B981;"></i> Lokasi GPS Berhasil Diterapkan!';
            setTimeout(function() {
                if (btn) btn.innerHTML = '<i class="fas fa-crosshairs" style="color:var(--accent-gold);"></i> Gunakan Lokasi GPS Saya Saat Ini';
            }, 3000);
        }, function(err) {
            if (btn) btn.innerHTML = '<i class="fas fa-crosshairs" style="color:var(--accent-gold);"></i> Gunakan Lokasi GPS Saya Saat Ini';
            alert("Gagal mendeteksi lokasi GPS Anda: " + err.message + ". Pastikan izin lokasi diaktifkan.");
        }, { enableHighAccuracy: true, timeout: 10000 });
    }

    function searchLocationOnMap() {
        const query = document.getElementById('search-address-input').value.trim();
        const resultsEl = document.getElementById('search-results-list');

        if (!query) {
            alert("Masukkan nama jalan, gedung, atau kota yang ingin dicari.");
            return;
        }

        resultsEl.style.display = 'block';
        resultsEl.innerHTML = '<div style="padding:8px; color:var(--text-muted);"><i class="fas fa-spinner fa-spin"></i> Mencari lokasi...</div>';

        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5`)
            .then(res => res.json())
            .then(data => {
                if (!data || data.length === 0) {
                    resultsEl.innerHTML = '<div style="padding:8px; color:#F87171;">Lokasi tidak ditemukan. Coba gunakan kata kunci alamat yang lebih spesifik.</div>';
                    return;
                }

                let html = '';
                data.forEach(item => {
                    html += `
                        <div style="padding:8px 10px; border-bottom:1px solid var(--border-color); cursor:pointer; color:var(--text-primary);" onmouseover="this.style.background='rgba(197,168,128,0.15)'" onmouseout="this.style.background='transparent'" onclick="selectSearchResult(${item.lat}, ${item.lon}, '${addslashes(item.display_name)}')">
                            <i class="fas fa-map-marker-alt" style="color:var(--accent-gold); margin-right:6px;"></i>
                            <strong>${escapeHtml(item.display_name)}</strong>
                        </div>
                    `;
                });
                resultsEl.innerHTML = html;
            })
            .catch(err => {
                console.error("Search error:", err);
                resultsEl.innerHTML = '<div style="padding:8px; color:#F87171;">Gagal melakukan pencarian lokasi. Periksa koneksi internet.</div>';
            });
    }

    function selectSearchResult(lat, lng, address) {
        updateCoordinates(lat, lng, false);
        if (map) map.setZoom(17);

        const addrEl = document.getElementById('office_address');
        if (addrEl) addrEl.value = address;

        const resultsEl = document.getElementById('search-results-list');
        if (resultsEl) resultsEl.style.display = 'none';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.innerText = text;
        return div.innerHTML;
    }

    function addslashes(str) {
        return (str + '').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
