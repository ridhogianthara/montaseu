<?php
require_once __DIR__ . '/../config/database.php';
requireAuth();

$currentUser = $_SESSION['user_name'] ?? 'User';
$currentRole = $_SESSION['role'] ?? 'karyawan';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Montaseu Studio | Web Absensi & Tracking Lokasi</title>
    
    <!-- CSS Theme & Fonts -->
    <link rel="stylesheet" href="/Montaseu/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body>

<header class="app-header">
    <div class="header-container">
        <div class="nav-brand">
            <div class="logo-icon">M</div>
            <div>
                <div class="brand-title">MONTASEU</div>
                <div class="sub-title">Interior Design Studio</div>
            </div>
        </div>

        <nav>
            <ul class="nav-menu">
                <?php if (isAdmin()): ?>
                    <li>
                        <a href="/Montaseu/admin/dashboard.php" class="nav-link <?= ($currentPage == 'dashboard.php' && strpos($_SERVER['REQUEST_URI'], 'admin') !== false) ? 'active' : '' ?>">
                            <i class="fas fa-chart-pie"></i> Overview
                        </a>
                    </li>
                    <li>
                        <a href="/Montaseu/admin/rekap.php" class="nav-link <?= ($currentPage == 'rekap.php') ? 'active' : '' ?>">
                            <i class="fas fa-calendar-check"></i> Monitoring Presensi
                        </a>
                    </li>
                    <li>
                        <a href="/Montaseu/admin/karyawan.php" class="nav-link <?= ($currentPage == 'karyawan.php') ? 'active' : '' ?>">
                            <i class="fas fa-users"></i> Kelola Karyawan
                        </a>
                    </li>
                    <li>
                        <a href="/Montaseu/admin/laporan.php" class="nav-link <?= ($currentPage == 'laporan.php') ? 'active' : '' ?>">
                            <i class="fas fa-file-invoice"></i> Laporan
                        </a>
                    </li>
                    <li>
                        <a href="/Montaseu/admin/pengaturan.php" class="nav-link <?= ($currentPage == 'pengaturan.php') ? 'active' : '' ?>">
                            <i class="fas fa-cog"></i> Pengaturan Studio
                        </a>
                    </li>
                <?php else: ?>
                    <li>
                        <a href="/Montaseu/employee/dashboard.php" class="nav-link <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="/Montaseu/employee/absen.php" class="nav-link <?= ($currentPage == 'absen.php') ? 'active' : '' ?>">
                            <i class="fas fa-camera"></i> Absen Sekarang
                        </a>
                    </li>
                    <li>
                        <a href="/Montaseu/employee/riwayat.php" class="nav-link <?= ($currentPage == 'riwayat.php') ? 'active' : '' ?>">
                            <i class="fas fa-history"></i> Riwayat Saya
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>

        <div style="display:flex; align-items:center; gap:15px;">
            <div class="user-badge">
                <div class="user-avatar">
                    <?= strtoupper(substr($currentUser, 0, 1)) ?>
                </div>
                <div>
                    <div style="font-weight:700; font-size:0.85rem; color:var(--text-primary);"><?= sanitize($currentUser) ?></div>
                    <div style="font-size:0.75rem; color:var(--accent-gold); text-transform:capitalize;"><?= $currentRole === 'admin' ? 'Studio Administrator' : ($_SESSION['job_title'] ?? 'Staff') ?></div>
                </div>
            </div>
            <a href="/Montaseu/auth/logout.php" class="btn-danger" style="display:inline-flex; align-items:center; gap:6px;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</header>

<main class="main-wrapper">
