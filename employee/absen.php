<?php
require_once __DIR__ . '/../config/database.php';
requireAuth();

if (!isEmployee()) {
    header("Location: " . BASE_URL . "/admin/dashboard.php");
    exit();
}

$userId = $_SESSION['user_id'];
$today = date('Y-m-d');
$settings = getSettings();

// Fetch today's record
$todayRecord = getTodayAttendance($userId, $today);

$mode = 'clock_in';
if ($todayRecord) {
    if (!$todayRecord['clock_out_time']) {
        $mode = 'clock_out';
    } else {
        $mode = 'completed';
    }
}

$message = '';
$error = '';
$redirectScript = '';

// Handle Submit Absensi (Clock In or Clock Out)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $lat = filter_input(INPUT_POST, 'lat', FILTER_VALIDATE_FLOAT) ?: 0;
    $lng = filter_input(INPUT_POST, 'lng', FILTER_VALIDATE_FLOAT) ?: 0;
    $address = sanitize($_POST['address'] ?? 'Lokasi GPS Tidak Diketahui');
    $locationType = sanitize($_POST['location_type'] ?? 'Office');
    $notes = sanitize($_POST['notes'] ?? '');
    $photoData = $_POST['photo_base64'] ?? '';

    // Security Check 1: Mandatory Photo Capture from live camera
    if (empty($photoData) || strpos($photoData, 'data:image') !== 0) {
        $error = "Foto selfie WAJIB diambil secara langsung menggunakan kamera! Dilarang menggunakan foto dari galeri.";
    }
    // Security Check 2: Validate coordinates bounds & Anti-Fake GPS
    elseif ($lat == 0 || $lng == 0 || abs($lat) > 90 || abs($lng) > 180) {
        $error = "Lokasi GPS tidak valid atau terdeteksi manipulasi lokasi (Fake GPS). Harap nyalakan GPS fisik perangkat Anda.";
    } 
    else {
        // Decode and save live camera photo
        $parts = explode(',', $photoData);
        $decoded = base64_decode($parts[1] ?? '');
        $photoPath = '';
        if ($decoded) {
            $fileName = 'selfie_' . $userId . '_' . time() . '_' . rand(100, 999) . '.jpg';
            $fullTarget = UPLOADS_DIR . $fileName;
            file_put_contents($fullTarget, $decoded);
            $photoPath = BASE_URL . '/uploads/selfies/' . $fileName;
        }

        // Server-Side Immutable Timestamp (Strictly non-editable by user/admin)
        $serverTime = date('Y-m-d H:i:s');
        $currentTimeShort = date('H:i', strtotime($serverTime));

        if ($action === 'clock_in') {
            $workStart = $settings['work_start'] ?? '08:30';
            $status = ($currentTimeShort <= $workStart) ? 'On Time' : 'Late';

            saveClockIn($userId, $today, $photoPath, $lat, $lng, $address, $status, $notes, $locationType);

            $message = "Presensi Masuk Berhasil Ditambahkan!";
            $redirectScript = "<script>setTimeout(function(){ window.location.href = '" . BASE_URL . "/employee/dashboard.php'; }, 1000);</script>";
        } elseif ($action === 'clock_out' && $todayRecord) {
            $inTime = new DateTime($todayRecord['clock_in_time']);
            $outTime = new DateTime($serverTime);
            $diff = $inTime->diff($outTime);
            $durationStr = $diff->h . ' Jam ' . $diff->i . ' Menit';

            saveClockOut($todayRecord['id'], $photoPath, $lat, $lng, $address, $notes, $durationStr);

            $message = "Presensi Pulang Berhasil Ditambahkan!";
            $redirectScript = "<script>setTimeout(function(){ window.location.href = '" . BASE_URL . "/employee/dashboard.php'; }, 1000);</script>";
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom: 1.5rem;">
    <h1 class="brand-title" style="font-size: 1.8rem; margin-bottom: 4px;">Form Absensi Kamera & Location GPS</h1>
    <p style="color: var(--text-secondary); font-size: 0.9rem;">
        Pencatatan Presensi Real-Time Montaseu Studio (Foto Kamera Wajib & Jam Terkunci Otomatis)
    </p>
</div>

<?php if (!empty($error)): ?>
    <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: var(--accent-rose); padding: 1rem; border-radius: var(--radius-sm); font-weight:700; margin-bottom: 1.5rem; text-align: center;">
        <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
    </div>
<?php endif; ?>

<?php if (!empty($message)): ?>
    <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: var(--accent-emerald); padding: 1rem; border-radius: var(--radius-sm); font-weight:700; margin-bottom: 1.5rem; text-align: center;">
        <i class="fas fa-check-circle"></i> <?= $message ?> Mengalihkan...
    </div>
    <?= $redirectScript ?>
<?php endif; ?>

<?php if ($mode === 'completed'): ?>
    <div class="card-studio" style="text-align:center; padding:3rem 1.5rem;">
        <div style="width:80px; height:80px; background:rgba(16, 185, 129, 0.15); border-radius:50%; color:var(--accent-emerald); display:flex; align-items:center; justify-content:center; font-size:2.5rem; margin:0 auto 1.5rem;">
            <i class="fas fa-check-double"></i>
        </div>
        <h2 style="font-size:1.5rem; margin-bottom:0.5rem; color:var(--text-primary);">Presensi Hari Ini Telah Lengkap</h2>
        <p style="color:var(--text-muted); max-width:450px; margin:0 auto 1.5rem;">
            Anda telah melakukan Absen Masuk (<?= date('H:i', strtotime($todayRecord['clock_in_time'])) ?> WIB) dan Absen Pulang (<?= date('H:i', strtotime($todayRecord['clock_out_time'])) ?> WIB).
        </p>
        <a href="<?= BASE_URL ?>/employee/dashboard.php" class="btn-gold">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>
<?php else: ?>

<form method="POST" action="" enctype="multipart/form-data" id="absen-form" onsubmit="return validateAbsenForm();">
    <input type="hidden" name="action" value="<?= $mode ?>">
    <input type="hidden" name="lat" id="lat-input" value="0">
    <input type="hidden" name="lng" id="lng-input" value="0">
    <input type="hidden" name="photo_base64" id="photo-base64" value="">

    <div class="attendance-layout">
        <!-- Panel Kiri: Kamera & Geolocation status -->
        <div class="card-studio">
            <div class="card-title" style="margin-bottom:1rem;">
                <i class="fas fa-camera" style="color:var(--accent-gold);"></i> 
                1. Foto Selfie Live Kamera (Wajib)
            </div>

            <div class="camera-box">
                <video id="webcam-video" autoplay playsinline></video>
                <img id="photo-preview" style="display:none;" alt="Preview Foto Selfie">
                
                <div id="camera-status" style="position:absolute; color:#FFF; font-size:0.85rem; text-align:center; padding:10px; background:rgba(0,0,0,0.6); border-radius:6px;">
                    <i class="fas fa-spinner fa-spin"></i> Menghubungkan Kamera...
                </div>
            </div>

            <div style="display:flex; justify-content:center; gap:10px; margin-top:1rem;">
                <button type="button" id="btn-snap" onclick="takeSnapshot('webcam-video', 'snap-canvas', 'photo-base64', 'photo-preview')" class="btn-gold" style="font-size:0.85rem;">
                    <i class="fas fa-camera-retro"></i> Ambil Foto Snapshot
                </button>
                <button type="button" id="btn-retake" onclick="retakeCamera('webcam-video', 'photo-preview', 'photo-base64')" class="btn-secondary" style="font-size:0.85rem; display:none;">
                    <i class="fas fa-redo"></i> Foto Ulang
                </button>
            </div>

            <canvas id="snap-canvas" style="display:none;"></canvas>

            <hr style="border-color:var(--border-color); margin:1.5rem 0;">

            <div class="card-title" style="margin-bottom:0.75rem;">
                <i class="fas fa-shield-alt" style="color:var(--accent-gold);"></i> 
                2. Status Pengaman GPS & Sensor
            </div>
            <div id="location-status" style="font-size:0.85rem; margin-bottom:0.75rem; color:var(--text-secondary);">
                Memulai tracking GPS fisik...
            </div>
            <button type="button" onclick="getLocation('lat-input', 'lng-input', 'address-input', 'map-preview', <?= $settings['office_lat'] ?? 0 ?>, <?= $settings['office_lng'] ?? 0 ?>)" class="btn-secondary" style="font-size:0.8rem; width:100%;">
                <i class="fas fa-sync-alt"></i> Refresh / Deteksi Ulang GPS Perangkat
            </button>
        </div>

        <!-- Panel Kanan: Maps, Form Detail, & Submit -->
        <div class="card-studio">
            <div class="card-title" style="margin-bottom:1rem;">
                <i class="fas fa-map-marked-alt" style="color:var(--accent-gold);"></i> 
                Peta & Detail Presensi Studio (Waktu Terkunci Server)
            </div>

            <!-- Map Container -->
            <div id="map-preview" style="margin-bottom:1.25rem;"></div>

            <div class="form-group">
                <label class="form-label" for="address-input"><i class="fas fa-map-marker-alt"></i> Alamat Lokasi Terdeteksi</label>
                <input type="text" name="address" id="address-input" class="form-input" readonly placeholder="Mencari nama jalan & lokasi...">
            </div>

            <div class="form-group">
                <label class="form-label" for="location_type"><i class="fas fa-building"></i> Tipe Lokasi Kerja</label>
                <select name="location_type" id="location_type" class="form-select">
                    <option value="Studio Office">Studio Office HQ (Montaseu Studio)</option>
                    <option value="Client Site Visit">Client Site Visit (Kunjungan Proyek Interior)</option>
                    <option value="Vendor / Workshop">Vendor / Workshop Supplier Visit</option>
                    <option value="Remote / WFH">Remote / Work From Home</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="notes"><i class="fas fa-pencil-alt"></i> Catatan Proyek / Aktivitas</label>
                <textarea name="notes" id="notes" class="form-textarea" rows="3" placeholder="Tuliskan catatan proyek interior atau agenda kerja Anda hari ini..."></textarea>
            </div>

            <div style="margin-top:1.5rem;">
                <button type="submit" class="btn-gold" style="width:100%; font-size:1.05rem; padding:0.9rem;">
                    <i class="fas fa-paper-plane"></i> 
                    KIRIM PRESENSI <?= $mode === 'clock_in' ? 'MASUK' : 'PULANG' ?>
                </button>
            </div>
        </div>
    </div>
</form>

<?php endif; ?>

<script>
    function validateAbsenForm() {
        // Validasi 1: Foto Selfie Wajib Diambil dari Kamera Live
        const photo = document.getElementById('photo-base64').value;
        if (!photo || photo === '' || !photo.startsWith('data:image')) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Foto Selfie Wajib!',
                    text: 'Harap mengambil foto selfie secara langsung menggunakan kamera (klik tombol "Ambil Foto Snapshot"). Dilarang menggunakan foto galeri.',
                    background: '#181C23', color: '#F9FAFB'
                });
            } else {
                alert("Foto selfie WAJIB diambil secara langsung menggunakan kamera!");
            }
            return false;
        }

        // Validasi 2: Anti-Fake GPS Check
        if (typeof isGpsSecure === 'function' && !isGpsSecure()) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Presensi Ditolak!',
                    text: 'Terdeteksi aplikasi Fake GPS / Mock Location pada perangkat Anda.',
                    background: '#181C23', color: '#F9FAFB'
                });
            } else {
                alert("Presensi Ditolak! Terdeteksi Fake GPS.");
            }
            return false;
        }

        // Validasi 3: Koordinat GPS
        const lat = document.getElementById('lat-input').value;
        const lng = document.getElementById('lng-input').value;

        if (!lat || !lng || parseFloat(lat) === 0 || parseFloat(lng) === 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Lokasi GPS Belum Terdeteksi',
                    text: 'Harap tunggu hingga sinyal GPS perangkat Anda terdeteksi dengan benar.',
                    background: '#181C23', color: '#F9FAFB'
                });
            } else {
                alert("Harap tunggu hingga sinyal GPS perangkat Anda terdeteksi.");
            }
            return false;
        }
        return true;
    }

    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($mode !== 'completed'): ?>
            initCamera('webcam-video', 'snap-canvas');
            getLocation('lat-input', 'lng-input', 'address-input', 'map-preview', <?= $settings['office_lat'] ?? 0 ?>, <?= $settings['office_lng'] ?? 0 ?>);
        <?php endif; ?>
    });

    window.addEventListener('beforeunload', function() {
        stopCamera();
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
