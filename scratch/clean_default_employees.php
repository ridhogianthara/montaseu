<?php
require_once __DIR__ . '/../config/database.php';

$users = getUsers();
$adminOnly = [];

foreach ($users as $u) {
    if (isset($u['username']) && $u['username'] === 'admin') {
        $adminOnly[] = $u;
    }
}

if (empty($adminOnly)) {
    $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
    $adminOnly = [
        ['id' => 1, 'username' => 'admin', 'name' => 'Admin Montaseu', 'email' => 'admin@montaseu.com', 'password' => $adminPass, 'role' => 'admin', 'job_title' => 'Studio Manager / Admin', 'avatar' => null, 'created_at' => date('Y-m-d H:i:s')]
    ];
}

$encoded = json_encode($adminOnly, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

@file_put_contents(DATA_DIR . 'users.json', $encoded, LOCK_EX);
@file_put_contents(DATA_DIR . 'users_vault.json', $encoded, LOCK_EX);
@file_put_contents(DATA_DIR . 'users.json.bak', $encoded, LOCK_EX);

$backupDir = DATA_DIR . 'backups/';
if (!file_exists($backupDir)) @mkdir($backupDir, 0777, true);
@file_put_contents($backupDir . 'users_latest.json', $encoded, LOCK_EX);
@file_put_contents(sys_get_temp_dir() . '/montaseu_users_vault.json', $encoded, LOCK_EX);

echo "CLEANUP COMPLETE. Active accounts:\n";
print_r(getUsers());
