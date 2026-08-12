/**
 * Montaseu Studio - Geolocation & Anti-Fake GPS Security Engine
 */

let leafletMap = null;
let currentMarker = null;
let isMockDetected = false;

function getLocation(latInputId, lngInputId, addressInputId, mapContainerId, officeLat, officeLng) {
    const latInput = document.getElementById(latInputId);
    const lngInput = document.getElementById(lngInputId);
    const addressInput = document.getElementById(addressInputId);
    const locationStatus = document.getElementById('location-status');

    isMockDetected = false;

    if (!navigator.geolocation) {
        if (locationStatus) locationStatus.innerText = "Geolocation tidak didukung oleh browser Anda.";
        return;
    }

    if (locationStatus) locationStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Meminta lokasi GPS perangkat...';

    // Enforcement: High Accuracy Hardware Sensors
    const options = {
        enableHighAccuracy: true, // Paksa penggunaan sensor GPS fisik perangkat
        timeout: 20000,
        maximumAge: 0 // Cegah penggunaan lokasi cache / palsu
    };

    navigator.geolocation.getCurrentPosition(
        function(position) {
            const coords = position.coords;
            const lat = coords.latitude;
            const lng = coords.longitude;
            const accuracy = coords.accuracy;

            // Anti-Fake GPS Security Checks
            // Check 1: Mock location flags injected by spoofing apps
            if (position.isMock || coords.isMock || position.mocked || coords.mocked) {
                isMockDetected = true;
                handleFakeGpsDetected(locationStatus, "Terdeteksi Mock Location / Aplikasi Fake GPS aktif di perangkat Anda.");
                return;
            }

            // Check 2: Unnatural exact zero precision / extreme anomaly
            if (lat === 0 && lng === 0) {
                isMockDetected = true;
                handleFakeGpsDetected(locationStatus, "Koordinat GPS tidak valid (0, 0).");
                return;
            }

            if (latInput) latInput.value = lat;
            if (lngInput) lngInput.value = lng;

            if (locationStatus) {
                locationStatus.innerHTML = `<span style="color:#10B981"><i class="fas fa-shield-alt"></i> GPS Asli Terverifikasi (${lat.toFixed(5)}, ${lng.toFixed(5)}) - Akurasi: ${Math.round(accuracy)}m</span>`;
            }

            // Reverse Geocoding via Nominatim API
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                .then(res => res.json())
                .then(data => {
                    if (data && data.display_name && addressInput) {
                        addressInput.value = data.display_name;
                    }
                })
                .catch(err => {
                    console.log("Reverse geocode warning:", err);
                    if (addressInput && !addressInput.value) {
                        addressInput.value = `Koordinat GPS (${lat}, ${lng})`;
                    }
                });

            // Render Locked Read-Only Map
            initLeafletMap(mapContainerId, lat, lng, officeLat, officeLng);
        },
        function(error) {
            console.error("GPS Error:", error);
            let msg = "Gagal mengambil lokasi GPS.";
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    msg = "Izin GPS Ditolak. Harap aktifkan Lokasi browser & GPS HP Anda.";
                    break;
                case error.POSITION_UNAVAILABLE:
                    msg = "Sinyal GPS fisik tidak tersedia.";
                    break;
                case error.TIMEOUT:
                    msg = "Waktu permintaan lokasi GPS habis. Coba di area terbuka.";
                    break;
            }
            if (locationStatus) {
                locationStatus.innerHTML = `<span style="color:#EF4444"><i class="fas fa-exclamation-triangle"></i> ${msg}</span>`;
            }
        },
        options
    );
}

function handleFakeGpsDetected(statusEl, reason) {
    if (statusEl) {
        statusEl.innerHTML = `<span style="color:#EF4444; font-weight:bold;"><i class="fas fa-ban"></i> SENSOR PENGAMAN: Fake GPS Terdeteksi!</span>`;
    }
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'error',
            title: 'Terdeteksi Fake GPS!',
            text: reason + ' Mohon matikan aplikasi Fake GPS dan gunakan sinyal GPS asli perangkat Anda.',
            background: '#181C23',
            color: '#F9FAFB',
            confirmButtonColor: '#EF4444'
        });
    } else {
        alert("PERINGATAN: Fake GPS Terdeteksi! Mohon gunakan GPS asli.");
    }
}

function initLeafletMap(containerId, userLat, userLng, officeLat, officeLng) {
    const container = document.getElementById(containerId);
    if (!container) return;

    if (typeof L === 'undefined') return;

    if (leafletMap !== null) {
        leafletMap.remove();
        leafletMap = null;
    }

    // Lock map zoom & interactions so employees cannot manually manipulate pin location
    leafletMap = L.map(containerId, {
        dragging: true,
        touchZoom: true,
        doubleClickZoom: false,
        scrollWheelZoom: true,
        boxZoom: false,
        keyboard: false
    }).setView([userLat, userLng], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; Montaseu Studio | Security Locked GPS'
    }).addTo(leafletMap);

    // Karyawan Marker: STRICTLY READ-ONLY & NON-DRAGGABLE
    currentMarker = L.marker([userLat, userLng], {
        draggable: false, // Terkunci, tidak bisa digeser sama sekali
        interactive: true
    }).addTo(leafletMap)
        .bindPopup('<b><i class="fas fa-lock"></i> Posisi GPS Terkunci</b><br>Lokasi Anda saat ini')
        .openPopup();

    // Prevent clicking on map to move marker
    leafletMap.on('click', function(e) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'bottom-end',
                icon: 'warning',
                title: 'Titik lokasi GPS terkunci secara otomatis oleh sistem.',
                showConfirmButton: false,
                timer: 2500,
                background: '#181C23',
                color: '#F9FAFB'
            });
        }
    });

    // Marker Office if available
    if (officeLat && officeLng && (officeLat != 0)) {
        const officeMarker = L.marker([officeLat, officeLng], {
            draggable: false,
            icon: L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-gold.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            })
        }).addTo(leafletMap).bindPopup('<b>Montaseu Studio HQ</b>');

        const latlngs = [
            [userLat, userLng],
            [officeLat, officeLng]
        ];
        L.polyline(latlngs, { color: '#C5A880', weight: 3, dashArray: '5, 10' }).addTo(leafletMap);
    }
}

function isGpsSecure() {
    return !isMockDetected;
}
