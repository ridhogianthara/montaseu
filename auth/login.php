<?php
require_once __DIR__ . '/../config/database.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: " . BASE_URL . "/admin/dashboard.php");
        exit();
    } else {
        header("Location: " . BASE_URL . "/employee/dashboard.php");
        exit();
    }
}

$error = '';
$msg = $_GET['msg'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan Password wajib diisi!';
    } else {
        $user = getUserByEmail($email);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['job_title'] = $user['job_title'];

            // Landing page menyesuaikan siapa yang login (Admin vs Karyawan)
            if ($user['role'] === 'admin') {
                header("Location: " . BASE_URL . "/admin/dashboard.php");
            } else {
                header("Location: " . BASE_URL . "/employee/dashboard.php");
            }
            exit();
        } else {
            $error = 'Email atau Password salah. Silakan periksa kembali.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Montaseu Studio Absensi</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-body">

<div class="login-card">
    <div class="login-logo">
        <div class="icon-box">M</div>
        <h1 class="brand-title" style="font-size: 1.8rem; margin-bottom: 2px;">MONTASEU</h1>
        <div class="sub-title" style="font-size: 0.75rem;">Interior Design Studio</div>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 10px;">Portal Web Absensi & Tracking Lokasi</p>
    </div>

    <?php if ($msg === 'logout'): ?>
        <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: var(--accent-emerald); padding: 0.75rem; border-radius: var(--radius-sm); font-size: 0.85rem; margin-bottom: 1.25rem; text-align: center;">
            <i class="fas fa-check-circle"></i> Anda telah berhasil logout.
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: var(--accent-rose); padding: 0.75rem; border-radius: var(--radius-sm); font-size: 0.85rem; margin-bottom: 1.25rem; text-align: center;">
            <i class="fas fa-exclamation-circle"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label class="form-label" for="email"><i class="fas fa-envelope" style="color:var(--accent-gold);"></i> Alamat Email</label>
            <input type="email" name="email" id="email" class="form-input" placeholder="Masukkan email Anda" required value="<?= sanitize($_POST['email'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="password"><i class="fas fa-lock" style="color:var(--accent-gold);"></i> Password</label>
            <input type="password" name="password" id="password" class="form-input" placeholder="Masukkan password Anda" required>
        </div>

        <button type="submit" class="btn-gold" style="width: 100%; margin-top: 1rem; font-size: 1rem; padding: 0.85rem;">
            <i class="fas fa-sign-in-alt"></i> MASUK KE PORTAL
        </button>
    </form>

    <div style="text-align: center; margin-top: 2rem; font-size: 0.8rem; color: var(--text-muted);">
        Montaseu Studio Interior Attendance System &copy; <?= date('Y') ?>
    </div>
</div>

</body>
</html>
