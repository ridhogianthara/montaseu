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
        Konfigurasi Koordinat GPS Kantor Montaseu Studio & Jam Operasional Kerja
    </p>
</div>

<?php if (!empty($message)): ?>
    <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: var(--accent-emerald); padding: 0.85rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem; text-align: center;">
        <i class="fas fa-check-circle"></i> <?= $message ?>
    </div>
<?php endif; ?>

<form method="POST" action="">
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
                <textarea name="office_address" class="form-textarea" rows="2" required><?= sanitize($settings['office_address'] ?? '') ?></textarea>
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

            <button type="submit" class="btn-gold" style="width:100%; margin-top:1rem;">
                <i class="fas fa-save"></i> SIMPAN PENGATURAN STUDIO
            </button>
        </div>

        <!-- Interactive Map Picker for Office Coordinates -->
        <div class="card-studio">
            <div class="card-title" style="margin-bottom:1rem;">
                <i class="fas fa-map-marker-alt" style="color:var(--accent-gold);"></i> Pick Point Koordinat Kantor di Peta
            </div>
            <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:1rem;">
                Klik pada peta di bawah ini untuk menentukan titik kordinat latitude & longitude kantor Montaseu Studio secara presisi.
            </p>

            <div id="office-map-picker" style="width:100%; height:380px; border-radius:var(--radius-md); border:1px solid var(--border-color);"></div>
        </div>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const curLat = parseFloat(document.getElementById('office_lat').value) || -6.230588;
        const curLng = parseFloat(document.getElementById('office_lng').value) || 106.808018;

        const map = L.map('office-map-picker').setView([curLat, curLng], 16);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; Montaseu Studio'
        }).addTo(map);

        let marker = L.marker([curLat, curLng], { draggable: true }).addTo(map)
            .bindPopup('<b>Titik Kantor Montaseu Studio</b><br>Geser marker untuk ubah koordinat.')
            .openPopup();

        marker.on('dragend', function(e) {
            const coord = e.target.getLatLng();
            document.getElementById('office_lat').value = coord.lat.toFixed(6);
            document.getElementById('office_lng').value = coord.lng.toFixed(6);
        });

        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            document.getElementById('office_lat').value = e.latlng.lat.toFixed(6);
            document.getElementById('office_lng').value = e.latlng.lng.toFixed(6);
        });
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
