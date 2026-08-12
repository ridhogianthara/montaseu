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
                Monitoring Detail Foto Snapshot & Peta GPS Presensi Seluruh Karyawan (JSON Data)
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
                    <th>No</th>
                    <th>Nama Karyawan</th>
                    <th>Foto Selfie</th>
                    <th>Jam Masuk</th>
                    <th>Status</th>
                    <th>Jam Pulang</th>
                    <th>Durasi</th>
                    <th>Tipe Lokasi</th>
                    <th>Catatan & Alamat GPS</th>
                    <th>Aksi Peta</th>
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
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td>
                                <strong style="color:var(--text-primary); display:block;"><?= sanitize($r['name']) ?></strong>
                                <span style="font-size:0.75rem; color:var(--accent-gold);"><?= sanitize($r['job_title']) ?></span>
                            </td>
                            <td>
                                <?php if ($r['clock_in_photo']): ?>
                                    <img src="<?= $r['clock_in_photo'] ?>" class="img-thumb" onclick="viewImageModal('<?= $r['clock_in_photo'] ?>', 'Foto Selfie Presensi - <?= sanitize($r['name']) ?>')">
                                <?php else: ?>
                                    <span style="color:var(--text-muted);">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $r['clock_in_time'] ? date('H:i', strtotime($r['clock_in_time'])) . ' WIB' : '-' ?></td>
                            <td>
                                <span class="badge <?= $r['clock_in_status'] === 'On Time' ? 'badge-on-time' : 'badge-late' ?>">
                                    <?= $r['clock_in_status'] ?>
                                </span>
                            </td>
                            <td><?= $r['clock_out_time'] ? date('H:i', strtotime($r['clock_out_time'])) . ' WIB' : '-' ?></td>
                            <td><?= $r['work_duration'] ?: '-' ?></td>
                            <td><span class="badge badge-role"><?= sanitize($r['location_type']) ?></span></td>
                            <td style="max-width:240px; font-size:0.8rem;">
                                <div style="color:var(--text-primary); font-weight:600;"><?= sanitize($r['clock_in_notes'] ?: 'Tanpa Catatan') ?></div>
                                <div style="color:var(--text-muted); font-size:0.75rem; text-overflow:ellipsis; overflow:hidden; white-space:nowrap;" title="<?= sanitize($r['clock_in_address'] ?? '') ?>">
                                    <?= sanitize($r['clock_in_address'] ?? '-') ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($r['clock_in_lat'] && $r['clock_in_lng']): ?>
                                    <button class="btn-gold" style="font-size:0.75rem; padding:4px 8px;" onclick="viewMapModal(<?= $r['clock_in_lat'] ?>, <?= $r['clock_in_lng'] ?>, '<?= addslashes(sanitize($r['clock_in_address'] ?? '')) ?>', '<?= addslashes(sanitize($r['name'])) ?>')">
                                        <i class="fas fa-map-marked-alt"></i> Peta Lokasi
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
