<?php
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

$filterDate = sanitize($_GET['date'] ?? date('Y-m-d'));
$filterUser = (int)($_GET['user_id'] ?? 0);

$allAttendances = getAttendances();
$allUsers = getUsers();

$records = array_filter($allAttendances, function($a) use ($filterDate, $filterUser) {
    $matchDate = ($a['date'] === $filterDate);
    $matchUser = ($filterUser == 0 || $a['user_id'] == $filterUser);
    return $matchDate && $matchUser;
});

// Join User info
$fullRecords = [];
foreach ($records as $r) {
    $u = getUserById($r['user_id']);
    $r['name'] = $u ? $u['name'] : 'Unknown User';
    $r['job_title'] = $u ? $u['job_title'] : 'Staff';
    $r['email'] = $u ? $u['email'] : '';
    $fullRecords[] = $r;
}

usort($fullRecords, function($a, $b) {
    return strcmp($b['clock_in_time'] ?? '', $a['clock_in_time'] ?? '');
});

$karyawanList = array_filter($allUsers, function($u) {
    return $u['role'] === 'karyawan';
});
usort($karyawanList, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});
?>

<div style="margin-bottom: 2rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <div>
            <h1 class="brand-title" style="font-size: 1.8rem; margin-bottom: 4px;">Monitoring Presensi & Lokasi</h1>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">
                Monitoring Terpisah Detail Foto Snapshot, Tipe Lokasi, & Peta GPS Presensi Masuk vs Pulang
            </p>
        </div>

        <form method="GET" action="" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <div>
                <input type="date" name="date" value="<?= $filterDate ?>" class="form-input" style="padding:0.4rem 0.75rem;">
            </div>
            <div>
                <select name="user_id" class="form-select" style="padding:0.4rem 0.75rem;">
                    <option value="0">-- Semua Karyawan --</option>
                    <?php foreach ($karyawanList as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $filterUser == $u['id'] ? 'selected' : '' ?>><?= sanitize($u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-gold" style="padding:0.45rem 0.9rem; font-size:0.85rem;">
                <i class="fas fa-search"></i> Filter
            </button>
        </form>
    </div>
</div>

<div class="card-studio">
    <div class="card-title" style="margin-bottom:1rem;">
        <i class="fas fa-calendar-check" style="color:var(--accent-gold);"></i> Presensi Tanggal <?= date('d F Y', strtotime($filterDate)) ?>
    </div>

    <div class="table-responsive">
        <table class="studio-table">
            <thead>
                <tr>
                    <th rowspan="2" style="vertical-align:middle; text-align:center;">No</th>
                    <th rowspan="2" style="vertical-align:middle;">Informasi Karyawan</th>
                    <th colspan="4" style="background: rgba(16, 185, 129, 0.15); text-align:center; color:var(--accent-emerald); border-bottom:1px solid rgba(16, 185, 129, 0.3);">
                        <i class="fas fa-sign-in-alt"></i> DETAIL ABSEN MASUK
                    </th>
                    <th colspan="4" style="background: rgba(197, 168, 128, 0.15); text-align:center; color:var(--accent-gold); border-bottom:1px solid rgba(197, 168, 128, 0.3);">
                        <i class="fas fa-sign-out-alt"></i> DETAIL ABSEN PULANG
                    </th>
                </tr>
                <tr>
                    <!-- Sub-header Masuk -->
                    <th style="background: rgba(16, 185, 129, 0.08); font-size:0.75rem;">Foto Masuk</th>
                    <th style="background: rgba(16, 185, 129, 0.08); font-size:0.75rem;">Jam, Status & Tipe Lokasi</th>
                    <th style="background: rgba(16, 185, 129, 0.08); font-size:0.75rem;">Alamat & Catatan Masuk</th>
                    <th style="background: rgba(16, 185, 129, 0.08); font-size:0.75rem;">Peta Masuk</th>
                    
                    <!-- Sub-header Pulang -->
                    <th style="background: rgba(197, 168, 128, 0.08); font-size:0.75rem;">Foto Pulang</th>
                    <th style="background: rgba(197, 168, 128, 0.08); font-size:0.75rem;">Jam, Durasi & Tipe Lokasi</th>
                    <th style="background: rgba(197, 168, 128, 0.08); font-size:0.75rem;">Alamat & Catatan Pulang</th>
                    <th style="background: rgba(197, 168, 128, 0.08); font-size:0.75rem;">Peta Pulang</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($fullRecords)): ?>
                    <tr>
                        <td colspan="10" style="text-align:center; padding:3rem; color:var(--text-muted);">
                            Tidak ada catatan presensi ditemukan untuk kriteria filter ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($fullRecords as $idx => $r): ?>
                        <?php 
                            $inLocType = $r['clock_in_location_type'] ?? $r['location_type'] ?? 'Studio Office';
                            $outLocType = $r['clock_out_location_type'] ?? ($r['clock_out_time'] ? 'Studio Office' : '-');
                        ?>
                        <tr>
                            <td style="text-align:center;"><?= $idx + 1 ?></td>
                            <td>
                                <strong style="color:var(--text-primary); display:block;"><?= sanitize($r['name']) ?></strong>
                                <span style="font-size:0.75rem; color:var(--accent-gold);"><?= sanitize($r['job_title']) ?></span>
                            </td>
                            
                            <!-- DETAIL ABSEN MASUK -->
                            <td style="background: rgba(16, 185, 129, 0.03); text-align:center;">
                                <?php if (!empty($r['clock_in_photo'])): ?>
                                    <img src="<?= $r['clock_in_photo'] ?>" class="img-thumb" onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=Masuk&background=10B981&color=FFFFFF';" onclick="viewImageModal('<?= addslashes($r['clock_in_photo']) ?>', 'Foto Selfie Masuk - <?= sanitize($r['name']) ?>')">
                                <?php else: ?>
                                    <span style="color:var(--text-muted); font-size:0.8rem;">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="background: rgba(16, 185, 129, 0.03);">
                                <div style="font-weight:700; color:var(--text-primary);"><?= $r['clock_in_time'] ? date('H:i', strtotime($r['clock_in_time'])) . ' WIB' : '-' ?></div>
                                <?php if ($r['clock_in_status']): ?>
                                    <span class="badge <?= $r['clock_in_status'] === 'On Time' ? 'badge-on-time' : 'badge-late' ?>" style="font-size:0.7rem;">
                                        <?= $r['clock_in_status'] ?>
                                    </span>
                                <?php endif; ?>
                                <span class="badge badge-role" style="font-size:0.65rem; display:block; margin-top:3px;" title="Tipe Lokasi Masuk"><?= sanitize($inLocType) ?></span>
                            </td>
                            <td style="background: rgba(16, 185, 129, 0.03); max-width:200px; font-size:0.8rem;">
                                <div style="color:var(--text-primary); font-weight:600;"><?= sanitize($r['clock_in_notes'] ?: 'Tanpa Catatan') ?></div>
                                <div style="color:var(--text-muted); font-size:0.75rem; text-overflow:ellipsis; overflow:hidden; white-space:nowrap;" title="<?= sanitize($r['clock_in_address'] ?? '') ?>">
                                    <?= sanitize($r['clock_in_address'] ?? '-') ?>
                                </div>
                            </td>
                            <td style="background: rgba(16, 185, 129, 0.03); text-align:center;">
                                <?php if ($r['clock_in_lat'] && $r['clock_in_lng']): ?>
                                    <button class="btn-gold" style="font-size:0.7rem; padding:3px 7px;" onclick="viewMapModal(<?= $r['clock_in_lat'] ?>, <?= $r['clock_in_lng'] ?>, '<?= addslashes(sanitize($r['clock_in_address'] ?? '')) ?>', 'Peta Masuk: <?= addslashes(sanitize($r['name'])) ?>')">
                                        <i class="fas fa-map-marker-alt"></i> Masuk
                                    </button>
                                <?php else: ?>
                                    <span style="color:var(--text-muted); font-size:0.75rem;">-</span>
                                <?php endif; ?>
                            </td>

                            <!-- DETAIL ABSEN PULANG -->
                            <td style="background: rgba(197, 168, 128, 0.03); text-align:center;">
                                <?php if (!empty($r['clock_out_photo'])): ?>
                                    <img src="<?= $r['clock_out_photo'] ?>" class="img-thumb" onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=Pulang&background=C5A880&color=181C23';" onclick="viewImageModal('<?= addslashes($r['clock_out_photo']) ?>', 'Foto Selfie Pulang - <?= sanitize($r['name']) ?>')">
                                <?php else: ?>
                                    <span style="color:var(--text-muted); font-size:0.8rem;"><?= $r['clock_out_time'] ? '-' : '<span class="badge badge-late" style="font-size:0.65rem;">Belum Pulang</span>' ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="background: rgba(197, 168, 128, 0.03);">
                                <?php if ($r['clock_out_time']): ?>
                                    <div style="font-weight:700; color:var(--text-primary);"><?= date('H:i', strtotime($r['clock_out_time'])) . ' WIB' ?></div>
                                    <div style="font-size:0.75rem; color:var(--accent-gold); font-weight:600;"><?= $r['work_duration'] ?: '-' ?></div>
                                    <span class="badge badge-role" style="font-size:0.65rem; display:block; margin-top:3px;" title="Tipe Lokasi Pulang"><?= sanitize($outLocType) ?></span>
                                <?php else: ?>
                                    <span style="color:var(--text-muted); font-size:0.8rem;">--:--</span>
                                <?php endif; ?>
                            </td>
                            <td style="background: rgba(197, 168, 128, 0.03); max-width:200px; font-size:0.8rem;">
                                <?php if ($r['clock_out_time']): ?>
                                    <div style="color:var(--text-primary); font-weight:600;"><?= sanitize($r['clock_out_notes'] ?: 'Tanpa Catatan Pulang') ?></div>
                                    <div style="color:var(--text-muted); font-size:0.75rem; text-overflow:ellipsis; overflow:hidden; white-space:nowrap;" title="<?= sanitize($r['clock_out_address'] ?? '') ?>">
                                        <?= sanitize($r['clock_out_address'] ?? '-') ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color:var(--text-muted); font-size:0.8rem;">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="background: rgba(197, 168, 128, 0.03); text-align:center;">
                                <?php if ($r['clock_out_lat'] && $r['clock_out_lng']): ?>
                                    <button class="btn-secondary" style="font-size:0.7rem; padding:3px 7px;" onclick="viewMapModal(<?= $r['clock_out_lat'] ?>, <?= $r['clock_out_lng'] ?>, '<?= addslashes(sanitize($r['clock_out_address'] ?? '')) ?>', 'Peta Pulang: <?= addslashes(sanitize($r['name'])) ?>')">
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
