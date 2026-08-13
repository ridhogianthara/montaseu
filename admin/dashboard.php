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
                    <th>Karyawan</th>
                    <th>Foto Selfie</th>
                    <th>Jam Masuk</th>
                    <th>Status</th>
                    <th>Jam Pulang</th>
                    <th>Durasi</th>
                    <th>Tipe Lokasi</th>
                    <th>Alamat / Catatan Proyek</th>
                    <th>Tracking Peta</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($todayFullList)): ?>
                    <tr>
                        <td colspan="9" style="text-align:center; padding:3rem; color:var(--text-muted);">
                            Belum ada presensi karyawan yang masuk hari ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($todayFullList as $row): ?>
                        <tr>
                            <td>
                                <strong style="color:var(--text-primary); display:block;"><?= sanitize($row['name']) ?></strong>
                                <span style="font-size:0.75rem; color:var(--accent-gold);"><?= sanitize($row['job_title']) ?></span>
                            </td>
                            <td>
                                <?php if (!empty($row['clock_in_photo'])): ?>
                                    <img src="<?= $row['clock_in_photo'] ?>" class="img-thumb" onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=Selfie&background=C5A880&color=181C23';" onclick="viewImageModal('<?= addslashes($row['clock_in_photo']) ?>', 'Foto Selfie Presensi - <?= sanitize($row['name']) ?>')">
                                <?php else: ?>
                                    <span style="color:var(--text-muted); font-size:0.8rem;">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $row['clock_in_time'] ? date('H:i', strtotime($row['clock_in_time'])) . ' WIB' : '-' ?></td>
                            <td>
                                <span class="badge <?= $row['clock_in_status'] === 'On Time' ? 'badge-on-time' : 'badge-late' ?>">
                                    <?= $row['clock_in_status'] ?>
                                </span>
                            </td>
                            <td><?= $row['clock_out_time'] ? date('H:i', strtotime($row['clock_out_time'])) . ' WIB' : '-' ?></td>
                            <td><?= $row['work_duration'] ?: '-' ?></td>
                            <td><span class="badge badge-role"><?= sanitize($row['location_type']) ?></span></td>
                            <td style="max-width:220px; font-size:0.8rem;">
                                <div style="color:var(--text-primary); font-weight:600;"><?= sanitize($row['clock_in_notes'] ?: 'Studio Attendance') ?></div>
                                <div style="color:var(--text-muted); font-size:0.75rem; text-overflow:ellipsis; overflow:hidden; white-space:nowrap;" title="<?= sanitize($row['clock_in_address'] ?? '') ?>">
                                    <?= sanitize($row['clock_in_address'] ?? '-') ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($row['clock_in_lat'] && $row['clock_in_lng']): ?>
                                    <button class="btn-gold" style="font-size:0.75rem; padding:4px 8px;" onclick="viewMapModal(<?= $row['clock_in_lat'] ?>, <?= $row['clock_in_lng'] ?>, '<?= addslashes(sanitize($row['clock_in_address'] ?? '')) ?>', '<?= addslashes(sanitize($row['name'])) ?>')">
                                        <i class="fas fa-map-marked-alt"></i> Peta Lokasi
                                    </button>
                                <?php else: ?>
                                    <span style="color:var(--text-muted); font-size:0.75rem;">Tanpa GPS</span>
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
