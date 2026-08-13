<?php
require_once __DIR__ . '/../includes/header.php';
if (!isEmployee()) {
    header("Location: /Montaseu/admin/dashboard.php");
    exit();
}

$userId = $_SESSION['user_id'];
$monthFilter = sanitize($_GET['month'] ?? date('Y-m'));

$allAttendances = getAttendances();
$records = array_filter($allAttendances, function($a) use ($userId, $monthFilter) {
    return $a['user_id'] == $userId && strpos($a['date'], $monthFilter) === 0;
});
usort($records, function($a, $b) {
    return strcmp($b['date'], $a['date']);
});
?>

<div style="margin-bottom: 2rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <div>
            <h1 class="brand-title" style="font-size: 1.8rem; margin-bottom: 4px;">Riwayat Presensi Personal</h1>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">
                Histori lengkap waktu, foto selfie, dan pelacakan lokasi presensi Anda.
            </p>
        </div>

        <!-- Month Filter Form -->
        <form method="GET" action="" style="display:flex; align-items:center; gap:8px;">
            <label class="form-label" style="margin:0;"><i class="fas fa-filter"></i> Periode:</label>
            <input type="month" name="month" value="<?= $monthFilter ?>" class="form-input" style="padding:0.4rem 0.75rem;" onchange="this.form.submit()">
        </form>
    </div>
</div>

<div class="card-studio">
    <div class="table-responsive">
        <table class="studio-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Foto Selfie</th>
                    <th>Jam Masuk</th>
                    <th>Status</th>
                    <th>Jam Pulang</th>
                    <th>Durasi Kerja</th>
                    <th>Tipe Lokasi</th>
                    <th>Catatan / Alamat GPS</th>
                    <th>Aksi Peta</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="10" style="text-align:center; padding:3rem; color:var(--text-muted);">
                            Tidak ada data presensi pada periode bulan <strong><?= date('F Y', strtotime($monthFilter . '-01')) ?></strong>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $idx = 1; foreach ($records as $r): ?>
                        <tr>
                            <td><?= $idx++ ?></td>
                            <td><strong><?= date('d/m/Y', strtotime($r['date'])) ?></strong></td>
                            <td>
                                <?php if (!empty($r['clock_in_photo'])): ?>
                                    <img src="<?= $r['clock_in_photo'] ?>" class="img-thumb" onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=Selfie&background=C5A880&color=181C23';" onclick="viewImageModal('<?= addslashes($r['clock_in_photo']) ?>', 'Foto Selfie Presensi <?= date('d/m/Y', strtotime($r['date'])) ?>')">
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
                            <td style="max-width:220px; font-size:0.8rem;">
                                <div style="color:var(--text-primary); font-weight:600;"><?= sanitize($r['clock_in_notes'] ?: 'Tanpa Catatan') ?></div>
                                <div style="color:var(--text-muted); font-size:0.75rem; text-overflow:ellipsis; overflow:hidden; white-space:nowrap;" title="<?= sanitize($r['clock_in_address'] ?? '') ?>">
                                    <?= sanitize($r['clock_in_address'] ?? '-') ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($r['clock_in_lat'] && $r['clock_in_lng']): ?>
                                    <button class="btn-secondary" style="font-size:0.75rem; padding:4px 8px;" onclick="viewMapModal(<?= $r['clock_in_lat'] ?>, <?= $r['clock_in_lng'] ?>, '<?= addslashes(sanitize($r['clock_in_address'] ?? '')) ?>', '<?= date('d/m/Y', strtotime($r['date'])) ?>')">
                                        <i class="fas fa-map-marker-alt" style="color:var(--accent-gold);"></i> Peta GPS
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
