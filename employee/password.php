<?php
require_once __DIR__ . '/../includes/header.php';
if (!isEmployee()) {
    header("Location: " . BASE_URL . "/admin/dashboard.php");
    exit();
}

$userId = $_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Semua bidang form password wajib diisi!';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Konfirmasi password baru tidak cocok. Silakan periksa kembali.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password baru minimal harus 6 karakter!';
    } else {
        $user = getUserById($userId);
        if ($user && password_verify($oldPassword, $user['password'])) {
            $username = $user['username'] ?? str_replace('@montaseu.com', '', $user['email']);
            saveUser($user['name'], $username, $newPassword, $user['role'], $user['job_title'], $user['email'] ?? '', $userId);
            $message = 'Password Anda berhasil diperbarui!';
        } else {
            $error = 'Password lama yang Anda masukkan salah. Silakan periksa kembali.';
        }
    }
}
?>

<div style="margin-bottom: 2rem;">
    <h1 class="brand-title" style="font-size: 1.8rem; margin-bottom: 4px;">Ganti Password Akun</h1>
    <p style="color: var(--text-secondary); font-size: 0.9rem;">
        Perbarui password login pribadi Anda untuk keamanan akun Montaseu Studio
    </p>
</div>

<?php if (!empty($message)): ?>
    <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: var(--accent-emerald); padding: 0.85rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem; text-align: center;">
        <i class="fas fa-check-circle"></i> <?= $message ?>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: var(--accent-rose); padding: 0.85rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem; text-align: center;">
        <i class="fas fa-exclamation-circle"></i> <?= $error ?>
    </div>
<?php endif; ?>

<div class="card-studio" style="max-width: 520px; margin: 0 auto;">
    <div class="card-title" style="margin-bottom: 1.5rem;">
        <i class="fas fa-key" style="color:var(--accent-gold);"></i> Form Pembaruan Password
    </div>

    <form method="POST" action="">
        <div class="form-group">
            <label class="form-label" for="old_password"><i class="fas fa-lock" style="color:var(--text-secondary);"></i> Password Saat Ini (Lama)</label>
            <input type="password" name="old_password" id="old_password" class="form-input" required placeholder="Masukkan password lama Anda">
        </div>

        <div class="form-group">
            <label class="form-label" for="new_password"><i class="fas fa-key" style="color:var(--accent-gold);"></i> Password Baru</label>
            <input type="password" name="new_password" id="new_password" class="form-input" required placeholder="Masukkan password baru (min. 6 karakter)">
        </div>

        <div class="form-group">
            <label class="form-label" for="confirm_password"><i class="fas fa-check-double" style="color:var(--accent-gold);"></i> Konfirmasi Password Baru</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-input" required placeholder="Ulangi password baru">
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:2rem;">
            <a href="<?= BASE_URL ?>/employee/dashboard.php" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Batal
            </a>
            <button type="submit" class="btn-gold">
                <i class="fas fa-save"></i> SIMPAN PASSWORD BARU
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
