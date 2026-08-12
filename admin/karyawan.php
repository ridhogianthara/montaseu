<?php
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

$message = '';
$error = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_user') {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = sanitize($_POST['role'] ?? 'karyawan');
        $jobTitle = sanitize($_POST['job_title'] ?? 'Staff Interior');

        if (empty($name) || empty($email) || empty($password)) {
            $error = 'Nama, Email, dan Password wajib diisi!';
        } else {
            $existing = getUserByEmail($email);
            if ($existing) {
                $error = 'Email sudah terdaftar. Gunakan email lain!';
            } else {
                saveUser($name, $email, $password, $role, $jobTitle);
                $message = "Karyawan baru '$name' berhasil ditambahkan!";
            }
        }
    } elseif ($action === 'edit_user') {
        $id = (int)($_POST['id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $role = sanitize($_POST['role'] ?? 'karyawan');
        $jobTitle = sanitize($_POST['job_title'] ?? 'Staff Interior');
        $password = $_POST['password'] ?? '';

        if ($id > 0 && !empty($name) && !empty($email)) {
            saveUser($name, $email, $password, $role, $jobTitle, $id);
            $message = "Data akun '$name' berhasil diperbarui!";
        }
    } elseif ($action === 'delete_user') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === $_SESSION['user_id']) {
            $error = "Anda tidak dapat menghapus akun Anda sendiri!";
        } elseif ($id > 0) {
            deleteUser($id);
            $message = "Akun karyawan berhasil dihapus!";
        }
    }
}

// Fetch all users
$users = getUsers();
usort($users, function($a, $b) {
    if ($a['role'] === $b['role']) {
        return strcmp($a['name'], $b['name']);
    }
    return strcmp($a['role'], $b['role']);
});
?>

<div style="margin-bottom: 2rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <div>
            <h1 class="brand-title" style="font-size: 1.8rem; margin-bottom: 4px;">Kelola Data Karyawan</h1>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">
                Manajemen Akun Login Karyawan & Team Montaseu Studio (JSON Data)
            </p>
        </div>
        <button class="btn-gold" onclick="openAddModal()">
            <i class="fas fa-user-plus"></i> Tambah Karyawan Baru
        </button>
    </div>
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

<div class="card-studio">
    <div class="table-responsive">
        <table class="studio-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Lengkap</th>
                    <th>Email Login</th>
                    <th>Jabatan / Posisi Studio</th>
                    <th>Hak Akses Role</th>
                    <th>Tanggal Dibuat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $idx => $u): ?>
                    <tr>
                        <td><?= $idx + 1 ?></td>
                        <td>
                            <strong style="color:var(--text-primary);"><?= sanitize($u['name']) ?></strong>
                        </td>
                        <td><?= sanitize($u['email']) ?></td>
                        <td><span style="color:var(--accent-gold); font-weight:600;"><?= sanitize($u['job_title']) ?></span></td>
                        <td>
                            <span class="badge <?= $u['role'] === 'admin' ? 'badge-on-time' : 'badge-role' ?>">
                                <?= strtoupper($u['role']) ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y', strtotime($u['created_at'] ?? date('Y-m-d'))) ?></td>
                        <td>
                            <div style="display:flex; gap:6px;">
                                <button class="btn-secondary" style="font-size:0.75rem; padding:4px 8px;" 
                                    onclick='openEditModal(<?= json_encode($u) ?>)'>
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                    <form method="POST" action="" onsubmit="return confirm('Yakin ingin menghapus akun karyawan ini?');" style="display:inline;">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn-danger">
                                            <i class="fas fa-trash-alt"></i> Hapus
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Form Tambah / Edit Karyawan -->
<div id="userModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:2000; align-items:center; justify-content:center;">
    <div class="card-studio" style="width:100%; max-width:500px; background:#181C23; border-color:var(--border-gold);">
        <div class="card-header-flex">
            <h3 id="modalTitle" class="card-title"><i class="fas fa-user-plus"></i> Form Karyawan</h3>
            <button type="button" onclick="closeModal()" style="background:none; border:none; color:var(--text-muted); font-size:1.2rem; cursor:pointer;">&times;</button>
        </div>

        <form method="POST" action="" id="userForm">
            <input type="hidden" name="action" id="formAction" value="add_user">
            <input type="hidden" name="id" id="userId" value="">

            <div class="form-group">
                <label class="form-label">Nama Lengkap Karyawan</label>
                <input type="text" name="name" id="userName" class="form-input" required placeholder="Contoh: Rian Pratama">
            </div>

            <div class="form-group">
                <label class="form-label">Email Login</label>
                <input type="email" name="email" id="userEmail" class="form-input" required placeholder="Contoh: designer@montaseu.com">
            </div>

            <div class="form-group">
                <label class="form-label">Jabatan / Posisi Studio</label>
                <input type="text" name="job_title" id="userJobTitle" class="form-input" required placeholder="Contoh: Lead Interior Designer / Site Supervisor">
            </div>

            <div class="form-group">
                <label class="form-label">Hak Akses (Role)</label>
                <select name="role" id="userRole" class="form-select">
                    <option value="karyawan">Karyawan (Akses Absensi & Riwayat)</option>
                    <option value="admin">Admin Studio (Akses Full Control)</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" id="passLabel">Password Login</label>
                <input type="password" name="password" id="userPassword" class="form-input" placeholder="Masukkan password">
                <small id="passHelp" style="color:var(--text-muted); font-size:0.75rem; display:none;">Kosongkan jika tidak ingin mengubah password.</small>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:1.5rem;">
                <button type="button" onclick="closeModal()" class="btn-secondary">Batal</button>
                <button type="submit" class="btn-gold"><i class="fas fa-save"></i> Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus"></i> Tambah Karyawan Baru';
        document.getElementById('formAction').value = 'add_user';
        document.getElementById('userId').value = '';
        document.getElementById('userName').value = '';
        document.getElementById('userEmail').value = '';
        document.getElementById('userJobTitle').value = '';
        document.getElementById('userRole').value = 'karyawan';
        document.getElementById('userPassword').value = '';
        document.getElementById('userPassword').required = true;
        document.getElementById('passHelp').style.display = 'none';
        document.getElementById('userModal').style.display = 'flex';
    }

    function openEditModal(user) {
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-edit"></i> Edit Akun Karyawan';
        document.getElementById('formAction').value = 'edit_user';
        document.getElementById('userId').value = user.id;
        document.getElementById('userName').value = user.name;
        document.getElementById('userEmail').value = user.email;
        document.getElementById('userJobTitle').value = user.job_title;
        document.getElementById('userRole').value = user.role;
        document.getElementById('userPassword').value = '';
        document.getElementById('userPassword').required = false;
        document.getElementById('passHelp').style.display = 'block';
        document.getElementById('userModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('userModal').style.display = 'none';
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
