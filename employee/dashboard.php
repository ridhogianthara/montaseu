<?php
require_once __DIR__ . '/../includes/header.php';
if (!isEmployee()) {
    header("Location: " . BASE_URL . "/admin/dashboard.php");
    exit();
}

$userId = $_SESSION['user_id'];
$today = date('Y-m-d');
$currentMonth = date('Y-m');

// Fetch today's attendance record
$todayRecord = getTodayAttendance($userId, $today);

// Fetch summary metrics for this month
$allAttendances = getAttendances();
$monthRecords = array_filter($allAttendances, function($a) use ($userId, $currentMonth) {
    return $a['user_id'] == $userId && strpos($a['date'], $currentMonth) === 0;
});

$totalDays = count($monthRecords);
$totalOnTime = 0;
$totalLate = 0;
foreach ($monthRecords as $m) {
    if ($m['clock_in_status'] === 'On Time') $totalOnTime++;
    else if ($m['clock_in_status'] === 'Late') $totalLate++;
}

// Fetch recent 5 attendances for this user
$userRecords = array_filter($allAttendances, function($a) use ($userId) {
    return $a['user_id'] == $userId;
});
usort($userRecords, function($a, $b) {
    return strcmp($b['date'], $a['date']);
});
$recentList = array_slice($userRecords, 0, 5);

$settings = getSettings();
?>

<div style="margin-bottom: 2rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <div>
            <h1 class="brand-title" style="font-size: 1.8rem; margin-bottom: 4px;">Selamat Datang, <?= sanitize($currentUser) ?></h1>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">
                <i class="fas fa-briefcase" style="color:var(--accent-gold);"></i> <?= sanitize($_SESSION['job_title'] ?? 'Karyawan Studio') ?> | Montaseu Studio Interior Design
            </p>
        </div>
        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <a href="<?= BASE_URL ?>/employee/password.php" class="btn-secondary" style="font-size:0.85rem; padding:0.75rem 1rem;">
                <i class="fas fa-key" style="color:var(--accent-gold);"></i> Ganti Password
            </a>
            <div class="card-studio" style="padding: 0.75rem 1.25rem; display:flex; align-items:center; gap:12px; border-color:var(--border-gold);">
                <i class="fas fa-clock" style="font-size: 1.5rem; color: var(--accent-gold);"></i>
                <div>
                    <div id="live-clock" style="font-size: 1.1rem; font-weight: 800; color: var(--text-primary);">--:--:--</div>
                    <div id="live-date" style="font-size: 0.75rem; color: var(--text-muted);">--</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status Presensi Hari Ini -->
<div class="card-studio" style="margin-bottom: 2rem; background: linear-gradient(135deg, var(--bg-surface), #1A212A);">
    <div class="card-header-flex">
        <div class="card-title">
            <i class="fas fa-calendar-day" style="color:var(--accent-gold);"></i> Status Presensi Hari Ini (<?= date('d M Y') ?>)
        </div>
        <?php if (!$todayRecord): ?>
            <span class="badge badge-late"><i class="fas fa-exclamation-circle"></i> Belum Absen</span>
        <?php elseif ($todayRecord && !$todayRecord['clock_out_time']): ?>
            <span class="badge badge-on-time"><i class="fas fa-user-check"></i> Sudah Absen Masuk</span>
        <?php else: ?>
            <span class="badge badge-info"><i class="fas fa-check-double"></i> Selesai (Masuk & Pulang)</span>
        <?php endif; ?>
    </div>

    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:1.25rem; margin-top:1rem;">
        <div style="background: var(--bg-card); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
            <div style="font-size:0.8rem; color:var(--text-secondary);"><i class="fas fa-sign-in-alt"></i> Jam Masuk</div>
            <div style="font-size:1.4rem; font-weight:700; color:var(--text-primary); margin-top:4px;">
                <?= $todayRecord && $todayRecord['clock_in_time'] ? date('H:i', strtotime($todayRecord['clock_in_time'])) . ' WIB' : '--:--' ?>
            </div>
            <?php if ($todayRecord && $todayRecord['clock_in_status']): ?>
                <span class="badge <?= $todayRecord['clock_in_status'] === 'On Time' ? 'badge-on-time' : 'badge-late' ?>" style="margin-top:6px;">
                    <?= $todayRecord['clock_in_status'] ?>
                </span>
            <?php endif; ?>
        </div>

        <div style="background: var(--bg-card); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
            <div style="font-size:0.8rem; color:var(--text-secondary);"><i class="fas fa-sign-out-alt"></i> Jam Pulang</div>
            <div style="font-size:1.4rem; font-weight:700; color:var(--text-primary); margin-top:4px;">
                <?= $todayRecord && $todayRecord['clock_out_time'] ? date('H:i', strtotime($todayRecord['clock_out_time'])) . ' WIB' : '--:--' ?>
            </div>
            <?php if ($todayRecord && $todayRecord['work_duration']): ?>
                <div style="font-size:0.8rem; color:var(--accent-gold); margin-top:6px;">
                    Durasi: <?= $todayRecord['work_duration'] ?>
                </div>
            <?php endif; ?>
        </div>

        <div style="background: var(--bg-card); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
            <div style="font-size:0.8rem; color:var(--text-secondary);"><i class="fas fa-map-marker-alt"></i> Lokasi Kerja Hari Ini</div>
            <div style="font-size:1rem; font-weight:700; color:var(--accent-gold); margin-top:4px;">
                <?= $todayRecord ? sanitize($todayRecord['location_type']) : 'Belum Ditentukan' ?>
            </div>
            <?php if ($todayRecord && $todayRecord['clock_in_notes']): ?>
                <div style="font-size:0.75rem; color:var(--text-muted); margin-top:4px; text-overflow:ellipsis; overflow:hidden; white-space:nowrap;">
                    <?= sanitize($todayRecord['clock_in_notes']) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div style="margin-top:1.5rem; text-align:right;">
        <a href="<?= BASE_URL ?>/employee/absen.php" class="btn-gold">
            <i class="fas fa-camera"></i> 
            <?php if (!$todayRecord): ?>
                Lakukan Absen Masuk Sekarang
            <?php elseif (!$todayRecord['clock_out_time']): ?>
                Lakukan Absen Pulang Sekarang
            <?php else: ?>
                Lihat Detail Presensi Hari Ini
            <?php endif; ?>
        </a>
    </div>
</div>

<!-- Stats Ringkasan Bulan Ini -->
<div class="grid-stats">
    <div class="stat-card">
        <div class="stat-label">Total Kehadiran (<?= date('M Y') ?>)</div>
        <div class="stat-value"><?= $totalDays ?> <span style="font-size:1rem; font-weight:normal;">Hari</span></div>
        <div class="stat-desc">Jumlah hari kerja tercatat</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Tepat Waktu</div>
        <div class="stat-value" style="color:var(--accent-emerald);"><?= $totalOnTime ?></div>
        <div class="stat-desc">Masuk sebelum jam <?= $settings['work_start'] ?? '08:30' ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Terlambat</div>
        <div class="stat-value" style="color:var(--accent-rose);"><?= $totalLate ?></div>
        <div class="stat-desc">Masuk setelah jam <?= $settings['work_start'] ?? '08:30' ?></div>
    </div>
</div>

<!-- Riwayat Terakhir -->
<div class="card-studio">
    <div class="card-header-flex">
        <div class="card-title">
            <i class="fas fa-history" style="color:var(--accent-gold);"></i> Riwayat Presensi Terbaru Anda
        </div>
        <a href="<?= BASE_URL ?>/employee/riwayat.php" class="btn-secondary" style="font-size:0.8rem; padding:5px 12px;">
            Lihat Semua Riwayat <i class="fas fa-arrow-right"></i>
        </a>
    </div>

    <div class="table-responsive">
        <table class="studio-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Foto Masuk</th>
                    <th>Jam, Status & Lokasi Masuk</th>
                    <th>Foto Pulang</th>
                    <th>Jam, Durasi & Lokasi Pulang</th>
                    <th>Peta GPS</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentList)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; color:var(--text-muted); padding:2rem;">
                            Belum ada riwayat presensi tercatat.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentList as $r): ?>
                        <?php 
                            $inLocType = $r['clock_in_location_type'] ?? $r['location_type'] ?? 'Studio Office';
                            $outLocType = $r['clock_out_location_type'] ?? ($r['clock_out_time'] ? 'Studio Office' : '-');
                        ?>
                        <tr>
                            <td><strong><?= date('d/m/Y', strtotime($r['date'])) ?></strong></td>
                            
                            <!-- Foto Masuk -->
                            <td>
                                <?php if (!empty($r['clock_in_photo'])): ?>
                                    <img src="<?= $r['clock_in_photo'] ?>" class="img-thumb" onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=Masuk&background=10B981&color=FFFFFF';" onclick="viewImageModal('<?= addslashes($r['clock_in_photo']) ?>', 'Foto Selfie Masuk - <?= date('d/m/Y', strtotime($r['date'])) ?>')">
                                <?php else: ?>
                                    <span style="color:var(--text-muted); font-size:0.8rem;">-</span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Jam & Lokasi Masuk -->
                            <td>
                                <div style="font-weight:700; color:var(--text-primary);"><?= $r['clock_in_time'] ? date('H:i', strtotime($r['clock_in_time'])) . ' WIB' : '-' ?></div>
                                <span class="badge <?= $r['clock_in_status'] === 'On Time' ? 'badge-on-time' : 'badge-late' ?>" style="font-size:0.7rem;">
                                    <?= $r['clock_in_status'] ?>
                                </span>
                                <span class="badge badge-role" style="font-size:0.65rem; display:block; margin-top:4px;" title="Lokasi Masuk">
                                    <i class="fas fa-building"></i> <?= sanitize($inLocType) ?>
                                </span>
                            </td>

                            <!-- Foto Pulang -->
                            <td>
                                <?php if (!empty($r['clock_out_photo'])): ?>
                                    <img src="<?= $r['clock_out_photo'] ?>" class="img-thumb" onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=Pulang&background=C5A880&color=181C23';" onclick="viewImageModal('<?= addslashes($r['clock_out_photo']) ?>', 'Foto Selfie Pulang - <?= date('d/m/Y', strtotime($r['date'])) ?>')">
                                <?php else: ?>
                                    <span style="color:var(--text-muted); font-size:0.8rem;"><?= $r['clock_out_time'] ? '-' : '<span class="badge badge-late" style="font-size:0.65rem;">Belum Pulang</span>' ?></span>
                                <?php endif; ?>
                            </td>

                            <!-- Jam & Lokasi Pulang -->
                            <td>
                                <?php if ($r['clock_out_time']): ?>
                                    <div style="font-weight:700; color:var(--text-primary);"><?= date('H:i', strtotime($r['clock_out_time'])) . ' WIB' ?></div>
                                    <div style="font-size:0.75rem; color:var(--accent-gold); font-weight:600;"><?= $r['work_duration'] ?: '-' ?></div>
                                    <span class="badge badge-role" style="font-size:0.65rem; display:block; margin-top:4px;" title="Lokasi Pulang">
                                        <i class="fas fa-building"></i> <?= sanitize($outLocType) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color:var(--text-muted); font-size:0.8rem;">--:--</span>
                                <?php endif; ?>
                            </td>

                            <!-- Peta -->
                            <td>
                                <div style="display:flex; gap:4px; flex-wrap:wrap;">
                                    <?php if ($r['clock_in_lat'] && $r['clock_in_lng']): ?>
                                        <button class="btn-gold" style="font-size:0.7rem; padding:3px 6px;" onclick="viewMapModal(<?= $r['clock_in_lat'] ?>, <?= $r['clock_in_lng'] ?>, '<?= addslashes(sanitize($r['clock_in_address'] ?? '')) ?>', 'Peta Masuk: <?= date('d/m/Y', strtotime($r['date'])) ?>')">
                                            Masuk
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($r['clock_out_lat'] && $r['clock_out_lng']): ?>
                                        <button class="btn-secondary" style="font-size:0.7rem; padding:3px 6px;" onclick="viewMapModal(<?= $r['clock_out_lat'] ?>, <?= $r['clock_out_lng'] ?>, '<?= addslashes(sanitize($r['clock_out_address'] ?? '')) ?>', 'Peta Pulang: <?= date('d/m/Y', strtotime($r['date'])) ?>')">
                                            Pulang
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        updateLiveClock('live-clock', 'live-date');
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
