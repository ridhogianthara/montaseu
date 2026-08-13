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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Montaseu Studio | Web Absensi & Tracking Lokasi</title>
    
    <!-- CSS Theme & Fonts -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body>

<header class="app-header">
    <div class="header-container">
        <div class="nav-brand-group">
            <a href="<?= isAdmin() ? BASE_URL . '/admin/dashboard.php' : BASE_URL . '/employee/dashboard.php' ?>" class="nav-brand">
                <div class="logo-icon">M</div>
                <div>
                    <div class="brand-title">MONTASEU</div>
                    <div class="sub-title">Interior Design Studio</div>
                </div>
            </a>

            <!-- Mobile Hamburger Toggle Button -->
            <button type="button" class="nav-toggle" id="nav-toggle-btn" onclick="toggleMobileMenu()" aria-label="Toggle Navigation Menu">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <nav class="nav-wrapper" id="nav-wrapper">
            <ul class="nav-menu">
                <?php if (isAdmin()): ?>
                    <li>
                        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="nav-link <?= ($currentPage == 'dashboard.php' && strpos($_SERVER['REQUEST_URI'], 'admin') !== false) ? 'active' : '' ?>">
                            <i class="fas fa-chart-pie"></i> Overview
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>/admin/rekap.php" class="nav-link <?= ($currentPage == 'rekap.php') ? 'active' : '' ?>">
                            <i class="fas fa-calendar-check"></i> Monitoring
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>/admin/karyawan.php" class="nav-link <?= ($currentPage == 'karyawan.php') ? 'active' : '' ?>">
                            <i class="fas fa-users"></i> Karyawan
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>/admin/laporan.php" class="nav-link <?= ($currentPage == 'laporan.php') ? 'active' : '' ?>">
                            <i class="fas fa-file-invoice"></i> Laporan
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>/admin/pengaturan.php" class="nav-link <?= ($currentPage == 'pengaturan.php') ? 'active' : '' ?>">
                            <i class="fas fa-cog"></i> Pengaturan
                        </a>
                    </li>
                <?php else: ?>
                    <li>
                        <a href="<?= BASE_URL ?>/employee/dashboard.php" class="nav-link <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>/employee/absen.php" class="nav-link <?= ($currentPage == 'absen.php') ? 'active' : '' ?>">
                            <i class="fas fa-camera"></i> Absen Sekarang
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>/employee/riwayat.php" class="nav-link <?= ($currentPage == 'riwayat.php') ? 'active' : '' ?>">
                            <i class="fas fa-history"></i> Riwayat Saya
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>/employee/password.php" class="nav-link <?= ($currentPage == 'password.php') ? 'active' : '' ?>">
                            <i class="fas fa-key"></i> Ganti Password
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="user-action-group">
                <div class="user-badge">
                    <div class="user-avatar">
                        <?= strtoupper(substr($currentUser, 0, 1)) ?>
                    </div>
                    <div class="user-info">
                        <div style="font-weight:700; font-size:0.85rem; color:var(--text-primary);"><?= sanitize($currentUser) ?></div>
                        <div style="font-size:0.75rem; color:var(--accent-gold); text-transform:capitalize;"><?= $currentRole === 'admin' ? 'Administrator' : ($_SESSION['job_title'] ?? 'Staff') ?></div>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>/auth/logout.php" class="btn-danger" style="display:inline-flex; align-items:center; gap:6px; font-size:0.8rem; padding:0.45rem 0.85rem;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </nav>
    </div>
</header>

<script>
    function toggleMobileMenu() {
        const navWrapper = document.getElementById('nav-wrapper');
        const toggleBtn = document.getElementById('nav-toggle-btn');
        if (navWrapper) {
            navWrapper.classList.toggle('mobile-open');
            if (toggleBtn) {
                const icon = toggleBtn.querySelector('i');
                if (navWrapper.classList.contains('mobile-open')) {
                    icon.className = 'fas fa-times';
                } else {
                    icon.className = 'fas fa-bars';
                }
            }
        }
    }
</script>

<main class="main-wrapper">
