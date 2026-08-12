<?php
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$startDate = sanitize($_GET['start_date'] ?? date('Y-m-01'));
$endDate = sanitize($_GET['end_date'] ?? date('Y-m-t'));
$userId = (int)($_GET['user_id'] ?? 0);
$export = $_GET['export'] ?? '';

$allAttendances = getAttendances();
$allUsers = getUsers();

$records = array_filter($allAttendances, function($a) use ($startDate, $endDate, $userId) {
    $inRange = ($a['date'] >= $startDate && $a['date'] <= $endDate);
    $matchUser = ($userId == 0 || $a['user_id'] == $userId);
    return $inRange && $matchUser;
});

// Join User Details
$fullRecords = [];
foreach ($records as $r) {
    $u = getUserById($r['user_id']);
    $r['name'] = $u ? $u['name'] : 'Unknown User';
    $r['job_title'] = $u ? $u['job_title'] : 'Staff';
    $r['email'] = $u ? $u['email'] : '';
    $fullRecords[] = $r;
}

usort($fullRecords, function($a, $b) {
    if ($a['date'] === $b['date']) {
        return strcmp($a['clock_in_time'] ?? '', $b['clock_in_time'] ?? '');
    }
    return strcmp($a['date'], $b['date']);
});

// Handle Excel Export (.xls HTML format for native Excel opening with styling)
if ($export === 'excel') {
    $filename = 'Laporan_Absensi_Montaseu_' . $startDate . '_sd_' . $endDate . '.xls';
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="utf-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Laporan Presensi</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head>';
    echo '<body>';
    echo '<h2 style="font-family:Arial, sans-serif;">MONTASEU STUDIO - LAPORAN PRESENSI KARYAWAN (JSON STORAGE)</h2>';
    echo '<p style="font-family:Arial, sans-serif;">Periode: ' . date('d/m/Y', strtotime($startDate)) . ' s/d ' . date('d/m/Y', strtotime($endDate)) . '</p>';
    echo '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse; font-family:Arial, sans-serif; font-size:12px;">';
    echo '<tr style="background-color:#C5A880; color:#111111; font-weight:bold; text-align:center;">
            <th>No</th>
            <th>Tanggal</th>
            <th>Nama Karyawan</th>
            <th>Jabatan</th>
            <th>Jam Masuk</th>
            <th>Status Masuk</th>
            <th>Jam Pulang</th>
            <th>Durasi Kerja</th>
            <th>Tipe Lokasi</th>
            <th>Alamat GPS</th>
            <th>Catatan Proyek</th>
          </tr>';

    foreach ($fullRecords as $idx => $r) {
        $bgColor = ($idx % 2 == 0) ? '#FFFFFF' : '#F4F5F7';
        echo '<tr style="background-color:' . $bgColor . ';">';
        echo '<td align="center">' . ($idx + 1) . '</td>';
        echo '<td align="center">' . date('d/m/Y', strtotime($r['date'])) . '</td>';
        echo '<td><strong>' . sanitize($r['name']) . '</strong></td>';
        echo '<td>' . sanitize($r['job_title']) . '</td>';
        echo '<td align="center">' . ($r['clock_in_time'] ? date('H:i:s', strtotime($r['clock_in_time'])) : '-') . '</td>';
        echo '<td align="center">' . sanitize($r['clock_in_status']) . '</td>';
        echo '<td align="center">' . ($r['clock_out_time'] ? date('H:i:s', strtotime($r['clock_out_time'])) : '-') . '</td>';
        echo '<td align="center">' . sanitize($r['work_duration'] ?: '-') . '</td>';
        echo '<td>' . sanitize($r['location_type']) . '</td>';
        echo '<td>' . sanitize($r['clock_in_address'] ?: '-') . '</td>';
        echo '<td>' . sanitize($r['clock_in_notes'] ?: '-') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '</body></html>';
    exit();
}

// Handle CSV Export
if ($export === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Laporan_Absensi_Montaseu_' . $startDate . '_sd_' . $endDate . '.csv');
    
    $output = fopen('php://output', 'w');
    fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

    fputcsv($output, ['No', 'Tanggal', 'Nama Karyawan', 'Jabatan', 'Jam Masuk', 'Status Masuk', 'Jam Pulang', 'Durasi Kerja', 'Tipe Lokasi', 'Alamat GPS', 'Catatan Proyek']);

    foreach ($fullRecords as $idx => $r) {
        fputcsv($output, [
            $idx + 1,
            $r['date'],
            $r['name'],
            $r['job_title'],
            $r['clock_in_time'] ? date('H:i:s', strtotime($r['clock_in_time'])) : '-',
            $r['clock_in_status'],
            $r['clock_out_time'] ? date('H:i:s', strtotime($r['clock_out_time'])) : '-',
            $r['work_duration'] ?: '-',
            $r['location_type'],
            $r['clock_in_address'] ?: '-',
            $r['clock_in_notes'] ?: '-'
        ]);
    }
    fclose($output);
    exit();
}

$karyawanList = array_filter($allUsers, function($u) {
    return $u['role'] === 'karyawan';
});
usort($karyawanList, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});

require_once __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom: 2rem;" class="no-print">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <div>
            <h1 class="brand-title" style="font-size: 1.8rem; margin-bottom: 4px;">Laporan Presensi & Export</h1>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">
                Rekap Laporan Presensi Karyawan Montaseu Studio Periode <?= date('d/m/Y', strtotime($startDate)) ?> s/d <?= date('d/m/Y', strtotime($endDate)) ?> (JSON Data)
            </p>
        </div>

        <div style="display:flex; gap:10px;">
            <a href="?start_date=<?= $startDate ?>&end_date=<?= $endDate ?>&user_id=<?= $userId ?>&export=excel" class="btn-gold" style="font-size:0.85rem;">
                <i class="fas fa-file-excel"></i> Export Excel (.xls)
            </a>
            <a href="?start_date=<?= $startDate ?>&end_date=<?= $endDate ?>&user_id=<?= $userId ?>&export=csv" class="btn-secondary" style="font-size:0.85rem;">
                <i class="fas fa-file-csv"></i> Export CSV
            </a>
            <button onclick="window.print()" class="btn-secondary" style="font-size:0.85rem;">
                <i class="fas fa-print"></i> Cetak Laporan
            </button>
        </div>
    </div>
</div>

<!-- Filter Box -->
<div class="card-studio no-print" style="margin-bottom: 1.5rem;">
    <form method="GET" action="" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; align-items:end;">
        <div class="form-group" style="margin:0;">
            <label class="form-label">Tanggal Mulai</label>
            <input type="date" name="start_date" value="<?= $startDate ?>" class="form-input">
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Tanggal Selesai</label>
            <input type="date" name="end_date" value="<?= $endDate ?>" class="form-input">
        </div>
        <div class="form-group" style="margin:0;">
            <label class="form-label">Pilih Karyawan</label>
            <select name="user_id" class="form-select">
                <option value="0">-- Semua Karyawan --</option>
                <?php foreach ($karyawanList as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $userId == $u['id'] ? 'selected' : '' ?>><?= sanitize($u['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <button type="submit" class="btn-gold" style="width:100%;">
                <i class="fas fa-filter"></i> Terapkan Filter
            </button>
        </div>
    </form>
</div>

<!-- Print Header Branding -->
<style>
    @media print {
        .no-print, .app-header, .app-footer { display: none !important; }
        body { background: #FFF !important; color: #000 !important; }
        .main-wrapper { padding: 0 !important; max-width: 100% !important; }
        .card-studio { border: none !important; box-shadow: none !important; padding: 0 !important; }
        .studio-table th, .studio-table td { border: 1px solid #ccc !important; color: #000 !important; }
        .badge { border: 1px solid #000 !important; color: #000 !important; background: none !important; }
        .print-header { display: block !important; margin-bottom: 20px; text-align: center; }
    }
    .print-header { display: none; }
</style>

<div class="print-header">
    <h2 style="font-family:serif;">MONTASEU STUDIO INTERIOR DESIGN</h2>
    <p>Jl. Senopati No. 45, Kebayoran Baru, Jakarta Selatan | Telp: (021) 555-8899</p>
    <hr style="margin:10px 0;">
    <h3>LAPORAN PRESENSI KARYAWAN</h3>
    <p>Periode: <?= date('d F Y', strtotime($startDate)) ?> s/d <?= date('d F Y', strtotime($endDate)) ?></p>
</div>

<!-- Laporan Table -->
<div class="card-studio">
    <div class="table-responsive">
        <table class="studio-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Nama Karyawan</th>
                    <th>Jabatan</th>
                    <th>Jam Masuk</th>
                    <th>Status</th>
                    <th>Jam Pulang</th>
                    <th>Durasi Kerja</th>
                    <th>Tipe Lokasi</th>
                    <th>Catatan / Alamat GPS</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($fullRecords)): ?>
                    <tr>
                        <td colspan="10" style="text-align:center; padding:3rem; color:var(--text-muted);">
                            Tidak ada data presensi pada rentang tanggal terpilih.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($fullRecords as $idx => $r): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><?= date('d/m/Y', strtotime($r['date'])) ?></td>
                            <td><strong><?= sanitize($r['name']) ?></strong></td>
                            <td><?= sanitize($r['job_title']) ?></td>
                            <td><?= $r['clock_in_time'] ? date('H:i', strtotime($r['clock_in_time'])) . ' WIB' : '-' ?></td>
                            <td>
                                <span class="badge <?= $r['clock_in_status'] === 'On Time' ? 'badge-on-time' : 'badge-late' ?>">
                                    <?= $r['clock_in_status'] ?>
                                </span>
                            </td>
                            <td><?= $r['clock_out_time'] ? date('H:i', strtotime($r['clock_out_time'])) . ' WIB' : '-' ?></td>
                            <td><?= $r['work_duration'] ?: '-' ?></td>
                            <td><?= sanitize($r['location_type']) ?></td>
                            <td style="max-width:220px; font-size:0.8rem;">
                                <?= sanitize($r['clock_in_notes'] ?: '-') ?><br>
                                <small style="color:var(--text-muted);"><?= sanitize($r['clock_in_address'] ?: '') ?></small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
