<?php
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

$today = date('Y-m-d');
$allUsers = getUsers();
$allAttendances = getAttendances();

$karyawanList = array_filter($allUsers, function($u) {
    return $u['role'] === 'karyawan';
});
$totalKaryawan = count($karyawanList);

$todayList = array_filter($allAttendances, function($a) use ($today) {
    return $a['date'] === $today;
});

$totalHadirToday = count($todayList);
$totalLateToday = 0;
$totalSiteToday = 0;

foreach ($todayList as $t) {
    if ($t['clock_in_status'] === 'Late') $totalLateToday++;
    if ($t['location_type'] !== 'Studio Office') $totalSiteToday++;
}

// Join user details
$todayFullList = [];
foreach ($todayList as $t) {
    $u = getUserById($t['user_id']);
    $t['name'] = $u ? $u['name'] : 'Unknown User';
    $t['job_title'] = $u ? $u['job_title'] : 'Staff';
    $t['email'] = $u ? $u['email'] : '';
    $todayFullList[] = $t;
}

usort($todayFullList, function($a, $b) {
    return strcmp($b['clock_in_time'] ?? '', $a['clock_in_time'] ?? '');
});
?>

<div style="margin-bottom: 2rem;">
    <h1 class="brand-title" style="font-size: 1.8rem; margin-bottom: 4px;">Admin Executive Dashboard</h1>
    <p style="color: var(--text-secondary); font-size: 0.9rem;">
        Monitoring Kehadiran & Tracking Lokasi Real-Time Studio Montaseu Interior (Zero Database)
    </p>
</div>

<!-- Grid Statistics Cards -->
<div class="grid-stats">
    <div class="stat-card">
        <div class="stat-label">Total Karyawan Active</div>
        <div class="stat-value"><?= $totalKaryawan ?> <span style="font-size:1rem; font-weight:normal;">Orang</span></div>
        <div class="stat-desc">Terdaftar dalam sistem</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Hadir Hari Ini (<?= date('d/m') ?>)</div>
        <div class="stat-value" style="color:var(--accent-emerald);"><?= $totalHadirToday ?> / <?= $totalKaryawan ?></div>
        <div class="stat-desc">Telah melakukan presensi masuk</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Terlambat Hari Ini</div>
        <div class="stat-value" style="color:var(--accent-rose);"><?= $totalLateToday ?></div>
        <div class="stat-desc">Presensi lewat dari jam operasional</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Presensi Site Visit / Field</div>
        <div class="stat-value" style="color:var(--accent-gold);"><?= $totalSiteToday ?></div>
        <div class="stat-desc">Kunjungan lokasi proyek client / vendor</div>
    </div>
</div>

<!-- Realtime Attendance Today Table -->
<div class="card-studio">
    <div class="card-header-flex">
        <div class="card-title">
            <i class="fas fa-satellite-dish" style="color:var(--accent-gold);"></i> Real-Time Presensi Karyawan Hari Ini (<?= date('d F Y') ?>)
        </div>
        <a href="<?= BASE_URL ?>/admin/rekap.php" class="btn-secondary" style="font-size:0.8rem; padding:6px 12px;">
            <i class="fas fa-list"></i> Lihat Semua Monitoring
        </a>
    </div>

    <div class="table-responsive">
        <table class="studio-table">
            <thead>
                <tr>
                    <th rowspan="2" style="vertical-align:middle;">Karyawan</th>
                    <th colspan="3" style="background: rgba(16, 185, 129, 0.15); text-align:center; color:var(--accent-emerald); border-bottom:1px solid rgba(16, 185, 129, 0.3);">
                        <i class="fas fa-sign-in-alt"></i> DETAIL ABSEN MASUK
                    </th>
                    <th colspan="3" style="background: rgba(197, 168, 128, 0.15); text-align:center; color:var(--accent-gold); border-bottom:1px solid rgba(197, 168, 128, 0.3);">
                        <i class="fas fa-sign-out-alt"></i> DETAIL ABSEN PULANG
                    </th>
                </tr>
                <tr>
                    <!-- Sub-header Masuk -->
                    <th style="background: rgba(16, 185, 129, 0.08); font-size:0.75rem;">Foto Masuk</th>
                    <th style="background: rgba(16, 185, 129, 0.08); font-size:0.75rem;">Jam, Status & Lokasi</th>
                    <th style="background: rgba(16, 185, 129, 0.08); font-size:0.75rem;">Peta Masuk</th>
                    
                    <!-- Sub-header Pulang -->
                    <th style="background: rgba(197, 168, 128, 0.08); font-size:0.75rem;">Foto Pulang</th>
                    <th style="background: rgba(197, 168, 128, 0.08); font-size:0.75rem;">Jam, Durasi & Lokasi</th>
                    <th style="background: rgba(197, 168, 128, 0.08); font-size:0.75rem;">Peta Pulang</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($todayFullList)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:3rem; color:var(--text-muted);">
                            Belum ada presensi karyawan yang masuk hari ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($todayFullList as $row): ?>
                        <?php 
                            $inLocType = $row['clock_in_location_type'] ?? $row['location_type'] ?? 'Studio Office';
                            $outLocType = $row['clock_out_location_type'] ?? ($row['clock_out_time'] ? 'Studio Office' : '-');
                        ?>
                        <tr>
                            <td>
                                <strong style="color:var(--text-primary); display:block;"><?= sanitize($row['name']) ?></strong>
                                <span style="font-size:0.75rem; color:var(--accent-gold);"><?= sanitize($row['job_title']) ?></span>
                            </td>
                            
                            <!-- DETAIL MASUK -->
                            <td style="background: rgba(16, 185, 129, 0.03); text-align:center;">
                                <?php if (!empty($row['clock_in_photo'])): ?>
                                    <img src="<?= $row['clock_in_photo'] ?>" class="img-thumb" onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=Masuk&background=10B981&color=FFFFFF';" onclick="viewImageModal('<?= addslashes($row['clock_in_photo']) ?>', 'Foto Selfie Masuk - <?= sanitize($row['name']) ?>')">
                                <?php else: ?>
                                    <span style="color:var(--text-muted); font-size:0.8rem;">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="background: rgba(16, 185, 129, 0.03);">
                                <div style="font-weight:700; color:var(--text-primary);"><?= $row['clock_in_time'] ? date('H:i', strtotime($row['clock_in_time'])) . ' WIB' : '-' ?></div>
                                <span class="badge <?= $row['clock_in_status'] === 'On Time' ? 'badge-on-time' : 'badge-late' ?>" style="font-size:0.7rem;">
                                    <?= $row['clock_in_status'] ?>
                                </span>
                                <span class="badge badge-role" style="font-size:0.65rem; display:block; margin-top:3px;" title="Lokasi Masuk"><?= sanitize($inLocType) ?></span>
                            </td>
                            <td style="background: rgba(16, 185, 129, 0.03); text-align:center;">
                                <?php if ($row['clock_in_lat'] && $row['clock_in_lng']): ?>
                                    <button class="btn-gold" style="font-size:0.7rem; padding:3px 7px;" onclick="viewMapModal(<?= $row['clock_in_lat'] ?>, <?= $row['clock_in_lng'] ?>, '<?= addslashes(sanitize($row['clock_in_address'] ?? '')) ?>', 'Peta Masuk: <?= addslashes(sanitize($row['name'])) ?>')">
                                        <i class="fas fa-map-marker-alt"></i> Masuk
                                    </button>
                                <?php else: ?>
                                    <span style="color:var(--text-muted); font-size:0.75rem;">-</span>
                                <?php endif; ?>
                            </td>

                            <!-- DETAIL PULANG -->
                            <td style="background: rgba(197, 168, 128, 0.03); text-align:center;">
                                <?php if (!empty($row['clock_out_photo'])): ?>
                                    <img src="<?= $row['clock_out_photo'] ?>" class="img-thumb" onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=Pulang&background=C5A880&color=181C23';" onclick="viewImageModal('<?= addslashes($row['clock_out_photo']) ?>', 'Foto Selfie Pulang - <?= sanitize($row['name']) ?>')">
                                <?php else: ?>
                                    <span style="color:var(--text-muted); font-size:0.8rem;"><?= $row['clock_out_time'] ? '-' : '<span class="badge badge-late" style="font-size:0.65rem;">Belum Pulang</span>' ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="background: rgba(197, 168, 128, 0.03);">
                                <?php if ($row['clock_out_time']): ?>
                                    <div style="font-weight:700; color:var(--text-primary);"><?= date('H:i', strtotime($row['clock_out_time'])) . ' WIB' ?></div>
                                    <div style="font-size:0.75rem; color:var(--accent-gold); font-weight:600;"><?= $row['work_duration'] ?: '-' ?></div>
                                    <span class="badge badge-role" style="font-size:0.65rem; display:block; margin-top:3px;" title="Lokasi Pulang"><?= sanitize($outLocType) ?></span>
                                <?php else: ?>
                                    <span style="color:var(--text-muted); font-size:0.8rem;">--:--</span>
                                <?php endif; ?>
                            </td>
                            <td style="background: rgba(197, 168, 128, 0.03); text-align:center;">
                                <?php if ($row['clock_out_lat'] && $row['clock_out_lng']): ?>
                                    <button class="btn-secondary" style="font-size:0.7rem; padding:3px 7px;" onclick="viewMapModal(<?= $row['clock_out_lat'] ?>, <?= $row['clock_out_lng'] ?>, '<?= addslashes(sanitize($row['clock_out_address'] ?? '')) ?>', 'Peta Pulang: <?= addslashes(sanitize($row['name'])) ?>')">
                                        <i class="fas fa-map-marker-alt"></i> Pulang
                                    </button>
                                <?php else: ?>
                                    <span style="color:var(--text-muted); font-size:0.75rem;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
