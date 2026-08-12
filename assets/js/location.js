/**
 * Montaseu Studio - Geolocation & Leaflet Maps Tracker
 */

let leafletMap = null;
let currentMarker = null;

function getLocation(latInputId, lngInputId, addressInputId, mapContainerId, officeLat, officeLng) {
    const latInput = document.getElementById(latInputId);
    const lngInput = document.getElementById(lngInputId);
    const addressInput = document.getElementById(addressInputId);
    const locationStatus = document.getElementById('location-status');

    if (!navigator.geolocation) {
        if (locationStatus) locationStatus.innerText = "Geolocation tidak didukung oleh browser Anda.";
        return;
    }

    if (locationStatus) locationStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Meminta lokasi GPS...';

    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            if (latInput) latInput.value = lat;
            if (lngInput) lngInput.value = lng;

            if (locationStatus) {
                locationStatus.innerHTML = `<span style="color:#10B981"><i class="fas fa-check-circle"></i> Lokasi GPS Terdeteksi (${lat.toFixed(5)}, ${lng.toFixed(5)})</span>`;
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

            // Initialize or update Leaflet Map
            initLeafletMap(mapContainerId, lat, lng, officeLat, officeLng);
        },
        function(error) {
            console.error("GPS Error:", error);
            let msg = "Gagal mengambil lokasi GPS.";
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    msg = "Izin GPS Ditolak. Harap aktifkan Lokasi browser Anda.";
                    break;
                case error.POSITION_UNAVAILABLE:
                    msg = "Informasi lokasi GPS tidak tersedia.";
                    break;
                case error.TIMEOUT:
                    msg = "Waktu permintaan lokasi GPS habis.";
                    break;
            }
            if (locationStatus) {
                locationStatus.innerHTML = `<span style="color:#EF4444"><i class="fas fa-exclamation-triangle"></i> ${msg}</span>`;
            }
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
}

function initLeafletMap(containerId, userLat, userLng, officeLat, officeLng) {
    const container = document.getElementById(containerId);
    if (!container) return;

    if (typeof L === 'undefined') return;

    if (leafletMap !== null) {
        leafletMap.remove();
        leafletMap = null;
    }

    leafletMap = L.map(containerId).setView([userLat, userLng], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; Montaseu Studio | OpenStreetMap'
    }).addTo(leafletMap);

    // Marker User
    currentMarker = L.marker([userLat, userLng]).addTo(leafletMap)
        .bindPopup('<b>Lokasi Anda Saat Ini</b>')
        .openPopup();

    // Marker Office if available
    if (officeLat && officeLng && (officeLat != 0)) {
        const officeMarker = L.marker([officeLat, officeLng], {
            icon: L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-gold.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            })
        }).addTo(leafletMap).bindPopup('<b>Montaseu Studio HQ</b>');

        // Draw line between User & Office
        const latlngs = [
            [userLat, userLng],
            [officeLat, officeLng]
        ];
        L.polyline(latlngs, { color: '#C5A880', weight: 3, dashArray: '5, 10' }).addTo(leafletMap);
    }
}
