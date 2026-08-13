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
        Konfigurasi Koordinat GPS Kantor Montaseu Studio, Pencarian Tempat Terdaftar Google Maps, & Jam Operasional
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

        <!-- Interactive Map Picker & Google Places Search Engine -->
        <div class="card-studio">
            <div class="card-title" style="margin-bottom:0.75rem;">
                <i class="fas fa-map-marked-alt" style="color:var(--accent-gold);"></i> Pencarian Tempat Terdaftar Google Maps
            </div>

            <!-- Google Maps Info Box -->
            <div style="background: rgba(197, 168, 128, 0.08); border:1px solid var(--border-color); border-radius:8px; padding:10px; margin-bottom:0.75rem; font-size:0.8rem; color:var(--text-secondary);">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                    <div>
                        <i class="fab fa-google" style="color:var(--accent-gold);"></i> <strong>Cari Tempat & Gedung Terdaftar:</strong><br>
                        Ketik nama gedung/tempat terdaftar di Google Maps (misal: <em>Gedung Sate, Kinanti Bandung, Trans Studio</em>) atau <strong>tempelkan Link / Koordinat Google Maps</strong>.
                    </div>
                    <a href="https://www.google.com/maps" target="_blank" class="btn-gold" style="font-size:0.7rem; padding:4px 8px; text-decoration:none; white-space:nowrap;">
                        <i class="fas fa-external-link-alt"></i> Buka Google Maps
                    </a>
                </div>
            </div>

            <!-- Real-Time Places Search Input with Auto-complete -->
            <div style="position:relative; margin-bottom:0.75rem;">
                <div style="display:flex; gap:8px;">
                    <input type="text" id="search-address-input" class="form-input" placeholder="Ketik nama tempat/gedung/jalan (misal: Kinanti Bandung, Gedung Sate)..." style="font-size:0.85rem;" oninput="onPlaceInput(this.value)" onkeypress="if(event.key === 'Enter') { event.preventDefault(); searchLocationOnMap(); }">
                    <button type="button" onclick="searchLocationOnMap()" class="btn-gold" style="font-size:0.8rem; padding:6px 14px; flex-shrink:0;">
                        <i class="fas fa-search"></i> Cari
                    </button>
                </div>

                <!-- Auto-complete suggestion list dropdown -->
                <div id="search-results-list" style="display:none; position:absolute; left:0; right:0; top:100%; z-index:1000; background:#181C23; border:1px solid var(--border-color); border-radius:8px; max-height:220px; overflow-y:auto; font-size:0.8rem; box-shadow:0 10px 25px rgba(0,0,0,0.5); margin-top:4px;"></div>
            </div>

            <!-- Button Detect Current Admin GPS -->
            <button type="button" id="btn-detect-gps" onclick="useAdminCurrentLocation()" class="btn-secondary" style="font-size:0.8rem; width:100%; margin-bottom:0.75rem;">
                <i class="fas fa-crosshairs" style="color:var(--accent-gold);"></i> Gunakan Lokasi GPS Saya Saat Ini
            </button>

            <div id="office-map-picker" style="width:100%; height:310px; border-radius:var(--radius-md); border:1px solid var(--border-color);"></div>
        </div>
    </div>
</form>

<script>
    let map = null;
    let marker = null;
    let searchDebounceTimer = null;

    document.addEventListener('DOMContentLoaded', function() {
        const curLat = parseFloat(document.getElementById('office_lat').value) || -6.230588;
        const curLng = parseFloat(document.getElementById('office_lng').value) || 106.808018;

        map = L.map('office-map-picker').setView([curLat, curLng], 16);

        // OpenStreetMap Layer
        const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; Montaseu Studio'
        });

        // Satellite Tile Layer (Esri World Imagery)
        const satLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles &copy; Esri &mdash; Montaseu Studio'
        });

        osmLayer.addTo(map);

        L.control.layers({
            "Peta Standar": osmLayer,
            "Satelit Google/Esri": satLayer
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

        // Close search suggestion dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const searchInput = document.getElementById('search-address-input');
            const searchResults = document.getElementById('search-results-list');
            if (searchResults && !searchResults.contains(e.target) && e.target !== searchInput) {
                searchResults.style.display = 'none';
            }
        });
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

    function onPlaceInput(val) {
        clearTimeout(searchDebounceTimer);
        const resultsEl = document.getElementById('search-results-list');

        if (!val || val.trim().length < 2) {
            if (resultsEl) resultsEl.style.display = 'none';
            return;
        }

        searchDebounceTimer = setTimeout(function() {
            searchLocationOnMap(true);
        }, 350);
    }

    function searchLocationOnMap(isAutoSuggest = false) {
        const query = document.getElementById('search-address-input').value.trim();
        const resultsEl = document.getElementById('search-results-list');

        if (!query) {
            if (!isAutoSuggest) alert("Masukkan nama tempat, gedung, atau tempel link/koordinat Google Maps.");
            return;
        }

        if (resultsEl) {
            resultsEl.style.display = 'block';
            resultsEl.innerHTML = '<div style="padding:10px; color:var(--text-muted);"><i class="fas fa-spinner fa-spin"></i> Mencari tempat terdaftar...</div>';
        }

        // Check 1: Direct Coordinates / Google Maps Link Parser (e.g. -6.9025, 107.6190 or @-6.9025,107.6190)
        const coordMatch = query.match(/(-?\d+\.\d+)\s*,\s*(-?\d+\.\d+)/);
        if (coordMatch) {
            const lat = parseFloat(coordMatch[1]);
            const lng = parseFloat(coordMatch[2]);
            if (!isNaN(lat) && !isNaN(lng) && Math.abs(lat) <= 90 && Math.abs(lng) <= 180) {
                selectSearchResult(lat, lng, `Koordinat Google Maps (${lat}, ${lng})`);
                if (resultsEl) resultsEl.innerHTML = `<div style="padding:10px; color:#10B981;"><strong><i class="fas fa-check-circle"></i> Koordinat Google Maps Terdeteksi:</strong> ${lat}, ${lng}</div>`;
                return;
            }
        }

        // Format Photon POI Place results
        function parsePhotonPlaces(json) {
            if (!json || !json.features || json.features.length === 0) return [];
            return json.features.map(f => {
                const p = f.properties || {};
                const coords = f.geometry ? f.geometry.coordinates : [0, 0];
                
                // Build crisp place label
                let placeTitle = p.name || p.street || p.district || 'Tempat Terdaftar';
                let placeSub = [p.street, p.district, p.city || p.county, p.state].filter(Boolean).join(', ');
                
                let iconClass = 'fa-building';
                if (p.osm_key === 'tourism' || p.osm_key === 'historic') iconClass = 'fa-landmark';
                if (p.osm_key === 'amenity' || p.osm_key === 'shop') iconClass = 'fa-store';

                return {
                    lat: coords[1],
                    lon: coords[0],
                    title: placeTitle,
                    sub: placeSub,
                    icon: iconClass,
                    display_name: (p.name ? p.name + ' - ' : '') + placeSub
                };
            });
        }

        // Clean query (strip exact house numbers for broader place matching)
        const cleanQuery = query.replace(/^jl\.?\s*/i, 'Jalan ').replace(/no\.?\s*\d+/gi, '').trim();

        // Multi-Geocoding Engine Query (Photon POI Places -> Nominatim -> Relaxed Places)
        fetch(`https://photon.komoot.io/api/?q=${encodeURIComponent(query)}&limit=7`)
            .then(res => res.json())
            .then(data => {
                let places = parsePhotonPlaces(data);

                if (places.length > 0) {
                    renderPlacesDropdown(places);
                } else {
                    fetch(`https://photon.komoot.io/api/?q=${encodeURIComponent(cleanQuery)}&limit=7`)
                        .then(res => res.json())
                        .then(relaxData => {
                            let relaxPlaces = parsePhotonPlaces(relaxData);
                            if (relaxPlaces.length > 0) {
                                renderPlacesDropdown(relaxPlaces);
                            } else {
                                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5`)
                                    .then(res => res.json())
                                    .then(nomData => {
                                        if (nomData && nomData.length > 0) {
                                            let nomPlaces = nomData.map(n => ({
                                                lat: parseFloat(n.lat),
                                                lon: parseFloat(n.lon),
                                                title: n.display_name.split(',')[0],
                                                sub: n.display_name,
                                                icon: 'fa-map-marker-alt',
                                                display_name: n.display_name
                                            }));
                                            renderPlacesDropdown(nomPlaces);
                                        } else {
                                            renderNoResultHelp(query);
                                        }
                                    })
                                    .catch(() => renderNoResultHelp(query));
                            }
                        })
                        .catch(() => renderNoResultHelp(query));
                }
            })
            .catch(err => {
                console.error("Place search error:", err);
                renderNoResultHelp(query);
            });
    }

    function renderPlacesDropdown(items) {
        const resultsEl = document.getElementById('search-results-list');
        if (!resultsEl) return;

        let html = '';
        items.forEach(item => {
            html += `
                <div style="padding:10px 12px; border-bottom:1px solid var(--border-color); cursor:pointer; color:var(--text-primary); transition:background 0.15s;" onmouseover="this.style.background='rgba(197,168,128,0.2)'" onmouseout="this.style.background='transparent'" onclick="selectSearchResult(${item.lat}, ${item.lon}, '${addslashes(item.display_name)}')">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <i class="fas ${item.icon}" style="color:var(--accent-gold); font-size:0.9rem; flex-shrink:0;"></i>
                        <div>
                            <strong style="color:var(--accent-gold); font-size:0.85rem; display:block;">${escapeHtml(item.title)}</strong>
                            <span style="font-size:0.75rem; color:var(--text-muted);">${escapeHtml(item.sub)}</span>
                        </div>
                    </div>
                </div>
            `;
        });
        resultsEl.innerHTML = html;
        resultsEl.style.display = 'block';
    }

    function renderNoResultHelp(query) {
        const resultsEl = document.getElementById('search-results-list');
        if (!resultsEl) return;

        resultsEl.innerHTML = `
            <div style="padding:12px; color:#F87171; font-size:0.85rem;">
                <strong><i class="fas fa-exclamation-triangle"></i> Tempat terdaftar belum ditemukan.</strong><br>
                <div style="margin-top:6px; color:var(--text-secondary); line-height:1.4;">
                    <strong>Petunjuk Mudah Pengubahan Lokasi Kantor:</strong><br>
                    1. Salin & tempelkan <strong>Link / Koordinat dari Google Maps</strong> ke dalam kotak di atas (contoh: <code>-6.9025, 107.6190</code>).<br>
                    2. Atau ketikkan kata kunci lebih umum, contoh: <code>Kinanti Bandung</code> atau <code>Lengkong Bandung</code>.<br>
                    3. Atau klik <a href="https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}" target="_blank" style="color:var(--accent-gold); text-decoration:underline;">Buka Google Maps <i class="fas fa-external-link-alt"></i></a> untuk memilih tempat dan salin koordinatnya.
                </div>
            </div>
        `;
        resultsEl.style.display = 'block';
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
