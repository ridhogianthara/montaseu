<?php
/**
 * Data Storage, Session, & Master Vault Data Preservation Configuration
 * Montaseu Studio - Interior Design Attendance System
 * Zero Database / Vercel Compatible with Master Vault Accumulator & Auto-Healing
 */

// Set Timezone Standar Indonesia (WIB - Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

if (ob_get_level() == 0) {
    ob_start();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Dynamic Base URL detection (Localhost XAMPP vs Vercel / Domain Root)
function getBaseUrl() {
    if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
        return '/Montaseu';
    }
    return '';
}
define('BASE_URL', getBaseUrl());

// Tentukan direktori penyimpanan data utama (Dukung Vercel Serverless Read-Only Filesystem)
function getWritableDataDir() {
    $localDataDir = __DIR__ . '/../data/';
    if (!file_exists($localDataDir)) {
        @mkdir($localDataDir, 0777, true);
    }
    
    // Uji apakah direktori lokal dapat ditulis
    $testFile = $localDataDir . '.test_write_' . md5((string)microtime(true));
    if (@file_put_contents($testFile, 'test') !== false) {
        @unlink($testFile);
        return rtrim(realpath($localDataDir) ?: $localDataDir, '/') . '/';
    }

    // Fallback otomatis ke /tmp jika direktori project read-only (seperti di Vercel Serverless)
    $tmpDataDir = sys_get_temp_dir() . '/montaseu_data/';
    if (!file_exists($tmpDataDir)) {
        @mkdir($tmpDataDir, 0777, true);
    }
    return rtrim($tmpDataDir, '/') . '/';
}

function getWritableUploadsDir() {
    $localUploadsDir = __DIR__ . '/../uploads/selfies/';
    if (!file_exists($localUploadsDir)) {
        @mkdir($localUploadsDir, 0777, true);
    }

    $testFile = $localUploadsDir . '.test_write_' . md5((string)microtime(true));
    if (@file_put_contents($testFile, 'test') !== false) {
        @unlink($testFile);
        return rtrim(realpath($localUploadsDir) ?: $localUploadsDir, '/') . '/';
    }

    $tmpUploadsDir = sys_get_temp_dir() . '/montaseu_uploads/';
    if (!file_exists($tmpUploadsDir)) {
        @mkdir($tmpUploadsDir, 0777, true);
    }
    return rtrim($tmpUploadsDir, '/') . '/';
}

define('DATA_DIR', getWritableDataDir());
define('UPLOADS_DIR', getWritableUploadsDir());

/**
 * --- ARSITEKTUR MASTER DATA VAULT (AKUMULATOR PROTEKSI KARYAWAN) ---
 * Memastikan setiap data akun karyawan yang pernah dibuat TERPANTAU DAN TERSIMPAN PERMANEN.
 * Meskipun file users.json terhapus, tereset oleh Git, atau kodenya di-edit, Master Vault
 * akan selalu memulihkan (auto-heal) data karyawan ke users.json secara otomatis.
 */

function getVaultUsers() {
    $vaultPaths = [
        DATA_DIR . 'users_vault.json',
        DATA_DIR . 'users.json.bak',
        DATA_DIR . 'backups/users_latest.json',
        __DIR__ . '/../data/users_vault.json',
        __DIR__ . '/../data/users.json.bak',
        __DIR__ . '/../data/users.json',
        sys_get_temp_dir() . '/montaseu_users_vault.json'
    ];

    $allVaultUsers = [];
    foreach ($vaultPaths as $path) {
        if (file_exists($path) && filesize($path) > 0) {
            $content = @file_get_contents($path);
            $data = json_decode($content, true);
            if (is_array($data)) {
                foreach ($data as $u) {
                    if (is_array($u) && (isset($u['username']) || isset($u['email']) || isset($u['id']))) {
                        $key = !empty($u['username']) ? strtolower(trim($u['username'])) : (!empty($u['email']) ? strtolower(trim($u['email'])) : 'id_' . $u['id']);
                        if (!isset($allVaultUsers[$key])) {
                            $allVaultUsers[$key] = $u;
                        } else {
                            $allVaultUsers[$key] = array_merge($allVaultUsers[$key], array_filter($u));
                        }
                    }
                }
            }
        }
    }
    return array_values($allVaultUsers);
}

function updateVaultWithUsers($users) {
    if (!is_array($users) || empty($users)) return;

    $currentVault = getVaultUsers();
    $vaultMap = [];
    foreach ($currentVault as $u) {
        $key = !empty($u['username']) ? strtolower(trim($u['username'])) : (!empty($u['email']) ? strtolower(trim($u['email'])) : 'id_' . $u['id']);
        $vaultMap[$key] = $u;
    }

    foreach ($users as $u) {
        if (!is_array($u)) continue;
        $key = !empty($u['username']) ? strtolower(trim($u['username'])) : (!empty($u['email']) ? strtolower(trim($u['email'])) : 'id_' . ($u['id'] ?? 0));
        if (isset($vaultMap[$key])) {
            $vaultMap[$key] = array_merge($vaultMap[$key], array_filter($u));
        } else {
            $vaultMap[$key] = $u;
        }
    }

    $finalVault = array_values($vaultMap);
    $encoded = json_encode($finalVault, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    @file_put_contents(DATA_DIR . 'users_vault.json', $encoded, LOCK_EX);
    @file_put_contents(DATA_DIR . 'users.json.bak', $encoded, LOCK_EX);
    
    $backupDir = DATA_DIR . 'backups/';
    if (!file_exists($backupDir)) {
        @mkdir($backupDir, 0777, true);
    }
    @file_put_contents($backupDir . 'users_latest.json', $encoded, LOCK_EX);
    @file_put_contents(sys_get_temp_dir() . '/montaseu_users_vault.json', $encoded, LOCK_EX);
}

function removeFromVault($userId, $username = '') {
    $vaultPaths = [
        DATA_DIR . 'users_vault.json',
        DATA_DIR . 'users.json.bak',
        DATA_DIR . 'backups/users_latest.json',
        sys_get_temp_dir() . '/montaseu_users_vault.json'
    ];

    foreach ($vaultPaths as $path) {
        if (file_exists($path)) {
            $content = @file_get_contents($path);
            $data = json_decode($content, true);
            if (is_array($data)) {
                $filtered = array_filter($data, function($u) use ($userId, $username) {
                    $matchId = isset($u['id']) && (int)$u['id'] === (int)$userId;
                    $matchUsername = !empty($username) && isset($u['username']) && strtolower(trim($u['username'])) === strtolower(trim($username));
                    return !$matchId && !$matchUsername;
                });
                $encoded = json_encode(array_values($filtered), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                @file_put_contents($path, $encoded, LOCK_EX);
            }
        }
    }
}

// Helper untuk membaca & menulis JSON dengan proteksi Auto-Recovery
function loadJSON($file, $default = []) {
    $path = DATA_DIR . $file;
    if (!file_exists($path) || filesize($path) === 0) {
        if ($file === 'users.json') {
            $vaultUsers = getVaultUsers();
            if (!empty($vaultUsers)) {
                saveJSON('users.json', $vaultUsers);
                return $vaultUsers;
            }
        }
        saveJSON($file, $default);
        return $default;
    }

    $content = @file_get_contents($path);
    $data = json_decode($content, true);
    if (!is_array($data)) {
        if ($file === 'users.json') {
            $vaultUsers = getVaultUsers();
            if (!empty($vaultUsers)) {
                saveJSON('users.json', $vaultUsers);
                return $vaultUsers;
            }
        }
        return $default;
    }
    return $data;
}

function saveJSON($file, $data) {
    if (!file_exists(DATA_DIR)) {
        @mkdir(DATA_DIR, 0777, true);
    }
    $path = DATA_DIR . $file;
    @file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

    if ($file === 'users.json' && is_array($data) && !empty($data)) {
        updateVaultWithUsers($data);
    }
}

// Inisialisasi Data Default dengan Akumulasi Vault & Auto-Recovery Karyawan Existing
function initJSONData() {
    if (!file_exists(UPLOADS_DIR)) {
        @mkdir(UPLOADS_DIR, 0777, true);
    }

    // Settings Default
    $settingsPath = DATA_DIR . 'settings.json';
    if (!file_exists($settingsPath)) {
        $defaultSettings = [
            'company_name' => 'Montaseu Studio',
            'office_address' => 'Jl. Senopati No. 45, Kebayoran Baru, Jakarta Selatan',
            'office_lat' => '-6.230588',
            'office_lng' => '106.808018',
            'office_radius' => '500',
            'work_start' => '08:30',
            'work_end' => '17:30'
        ];
        saveJSON('settings.json', $defaultSettings);
    }

    // Akun Default (HANYA ADMIN STUDIO)
    $adminPass = password_hash('admin123', PASSWORD_DEFAULT);

    $defaultAccounts = [
        ['id' => 1, 'username' => 'admin', 'name' => 'Admin Montaseu', 'email' => 'admin@montaseu.com', 'password' => $adminPass, 'role' => 'admin', 'job_title' => 'Studio Manager / Admin', 'created_at' => date('Y-m-d H:i:s')]
    ];

    // Read active users from users.json
    $usersPath = DATA_DIR . 'users.json';
    $existingUsers = [];
    if (file_exists($usersPath) && filesize($usersPath) > 0) {
        $content = @file_get_contents($usersPath);
        $data = json_decode($content, true);
        if (is_array($data)) {
            $existingUsers = $data;
        }
    }

    // Retrieve ALL known users from Master Vault
    $vaultUsers = getVaultUsers();

    // Combine existing users with Vault users to recover any missing accounts
    $combinedMap = [];
    foreach ($vaultUsers as $u) {
        if (!is_array($u)) continue;
        $key = !empty($u['username']) ? strtolower(trim($u['username'])) : (!empty($u['email']) ? strtolower(trim($u['email'])) : 'id_' . ($u['id'] ?? 0));
        $combinedMap[$key] = $u;
    }
    foreach ($existingUsers as $u) {
        if (!is_array($u)) continue;
        $key = !empty($u['username']) ? strtolower(trim($u['username'])) : (!empty($u['email']) ? strtolower(trim($u['email'])) : 'id_' . ($u['id'] ?? 0));
        if (isset($combinedMap[$key])) {
            $combinedMap[$key] = array_merge($combinedMap[$key], $u);
        } else {
            $combinedMap[$key] = $u;
        }
    }

    // Add default accounts if missing
    foreach ($defaultAccounts as $def) {
        $key = strtolower($def['username']);
        if (!isset($combinedMap[$key])) {
            $combinedMap[$key] = $def;
        }
    }

    $finalUsers = array_values($combinedMap);

    // Schema normalization
    $maxId = 0;
    foreach ($finalUsers as &$u) {
        if (!isset($u['username']) || empty($u['username'])) {
            if (!empty($u['email'])) {
                $parts = explode('@', $u['email']);
                $u['username'] = strtolower(trim($parts[0]));
            } else {
                $u['username'] = 'user' . ($u['id'] ?? rand(10, 999));
            }
        }
        if (!isset($u['role'])) $u['role'] = 'karyawan';
        if (!isset($u['job_title'])) $u['job_title'] = 'Staff Interior';
        if (!isset($u['avatar'])) $u['avatar'] = null;
        if (!isset($u['created_at'])) $u['created_at'] = date('Y-m-d H:i:s');
        if (isset($u['id']) && (int)$u['id'] > $maxId) {
            $maxId = (int)$u['id'];
        }
    }
    unset($u);

    // Save normalized state to users.json and update Vault
    saveJSON('users.json', $finalUsers);
    updateVaultWithUsers($finalUsers);

    // Attendances Default File
    if (!file_exists(DATA_DIR . 'attendances.json')) {
        saveJSON('attendances.json', []);
    }
}

initJSONData();

// --- DATA ACCESS API FUNCTIONS ---

function getSettings() {
    return loadJSON('settings.json', []);
}

function updateSetting($key, $value) {
    $settings = getSettings();
    $settings[$key] = $value;
    saveJSON('settings.json', $settings);
    return true;
}

function getUsers() {
    return loadJSON('users.json', []);
}

function getUserByUsername($username) {
    $users = getUsers();
    $input = strtolower(trim((string)$username));
    if (empty($input)) return null;

    foreach ($users as $u) {
        $uUsername = isset($u['username']) ? strtolower(trim($u['username'])) : '';
        $uEmail = isset($u['email']) ? strtolower(trim($u['email'])) : '';
        if ($uUsername === $input || $uEmail === $input) {
            return $u;
        }
    }

    // Emergency Fallback: check Master Vault directly
    $vaultUsers = getVaultUsers();
    foreach ($vaultUsers as $u) {
        $uUsername = isset($u['username']) ? strtolower(trim($u['username'])) : '';
        $uEmail = isset($u['email']) ? strtolower(trim($u['email'])) : '';
        if ($uUsername === $input || $uEmail === $input) {
            // Self-heal active users list
            $users[] = $u;
            saveJSON('users.json', $users);
            return $u;
        }
    }

    return null;
}

function getUserByEmail($email) {
    return getUserByUsername($email);
}

function getUserById($id) {
    $users = getUsers();
    foreach ($users as $u) {
        if ((int)$u['id'] === (int)$id) {
            return $u;
        }
    }

    // Fallback to Vault
    $vaultUsers = getVaultUsers();
    foreach ($vaultUsers as $u) {
        if ((int)$u['id'] === (int)$id) {
            return $u;
        }
    }

    return null;
}

function saveUser($name, $username, $password, $role, $jobTitle, $email = '', $id = null) {
    $users = getUsers();
    $usernameClean = trim((string)$username);
    $usernameLower = strtolower($usernameClean);

    if (empty($email)) {
        $email = $usernameLower . '@montaseu.com';
    }

    // Cegah duplikasi username dengan akun lain
    foreach ($users as $u) {
        if ($id !== null && (int)$u['id'] === (int)$id) continue;
        $uUsername = isset($u['username']) ? strtolower(trim($u['username'])) : '';
        if (!empty($usernameLower) && $uUsername === $usernameLower) {
            return false; // Username sudah dipakai
        }
    }

    if ($id !== null && (int)$id > 0) {
        $updated = false;
        foreach ($users as &$u) {
            if ((int)$u['id'] === (int)$id) {
                $u['name'] = $name;
                $u['username'] = $usernameClean;
                $u['email'] = $email;
                $u['role'] = $role;
                $u['job_title'] = $jobTitle;
                if (!empty($password)) {
                    $u['password'] = password_hash($password, PASSWORD_DEFAULT);
                }
                if (!isset($u['avatar'])) $u['avatar'] = null;
                if (!isset($u['created_at'])) $u['created_at'] = date('Y-m-d H:i:s');
                $updated = true;
                break;
            }
        }
        unset($u);

        if ($updated) {
            saveJSON('users.json', $users);
            updateVaultWithUsers($users);
            return getUserById($id);
        }
    } else {
        // Calculate maxId using both active users and Vault
        $vaultUsers = getVaultUsers();
        $allKnown = array_merge($users, $vaultUsers);
        $maxId = 0;
        foreach ($allKnown as $u) {
            if (isset($u['id']) && (int)$u['id'] > $maxId) {
                $maxId = (int)$u['id'];
            }
        }

        $newUser = [
            'id' => $maxId + 1,
            'username' => $usernameClean,
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'job_title' => $jobTitle,
            'avatar' => null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $users[] = $newUser;
        saveJSON('users.json', $users);
        updateVaultWithUsers([$newUser]);
        return $newUser;
    }
    return false;
}

function deleteUser($id) {
    $user = getUserById($id);
    $username = $user ? ($user['username'] ?? '') : '';

    $users = getUsers();
    $newUsers = array_filter($users, function($u) use ($id) {
        return (int)$u['id'] !== (int)$id;
    });
    $reindexed = array_values($newUsers);

    $path = DATA_DIR . 'users.json';
    @file_put_contents($path, json_encode($reindexed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

    removeFromVault($id, $username);
    return true;
}

function getAttendances() {
    return loadJSON('attendances.json', []);
}

function getAttendanceById($id) {
    $attendances = getAttendances();
    foreach ($attendances as $a) {
        if ($a['id'] == $id) return $a;
    }
    return null;
}

function getTodayAttendance($userId, $date) {
    $attendances = getAttendances();
    foreach ($attendances as $a) {
        if ((int)$a['user_id'] === (int)$userId && $a['date'] === $date) {
            return $a;
        }
    }
    return null;
}

function saveClockIn($userId, $date, $photo, $lat, $lng, $address, $status, $notes, $locationType) {
    $attendances = getAttendances();
    $maxId = 0;
    foreach ($attendances as $a) {
        if ($a['id'] > $maxId) $maxId = $a['id'];
    }

    $record = [
        'id' => $maxId + 1,
        'user_id' => (int)$userId,
        'date' => $date,
        'clock_in_time' => date('Y-m-d H:i:s'),
        'clock_in_photo' => $photo,
        'clock_in_lat' => (float)$lat,
        'clock_in_lng' => (float)$lng,
        'clock_in_address' => $address,
        'clock_in_status' => $status,
        'clock_in_notes' => $notes,
        'clock_out_time' => null,
        'clock_out_photo' => null,
        'clock_out_lat' => null,
        'clock_out_lng' => null,
        'clock_out_address' => null,
        'clock_out_notes' => null,
        'work_duration' => null,
        'location_type' => $locationType,
        'created_at' => date('Y-m-d H:i:s')
    ];

    $attendances[] = $record;
    saveJSON('attendances.json', $attendances);
    return $record;
}

function saveClockOut($attendanceId, $photo, $lat, $lng, $address, $notes, $duration) {
    $attendances = getAttendances();
    foreach ($attendances as &$a) {
        if ($a['id'] == $attendanceId) {
            $a['clock_out_time'] = date('Y-m-d H:i:s');
            $a['clock_out_photo'] = $photo;
            $a['clock_out_lat'] = (float)$lat;
            $a['clock_out_lng'] = (float)$lng;
            $a['clock_out_address'] = $address;
            $a['clock_out_notes'] = $notes;
            $a['work_duration'] = $duration;
            saveJSON('attendances.json', $attendances);
            return $a;
        }
    }
    return false;
}

// Wrapper dummy untuk kompatibilitas jika ada panggilan getDB()
class JsonDBWrapper {
    public function prepare($sql) { return $this; }
    public function query($sql) { return $this; }
    public function execute($params = []) { return true; }
    public function fetch() { return null; }
    public function fetchAll() { return []; }
    public function fetchColumn() { return 0; }
}

function getDB() {
    return new JsonDBWrapper();
}

function sanitize($data) {
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isEmployee() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'karyawan';
}

function requireAuth() {
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "/auth/login.php");
        exit();
    }
}

function requireAdmin() {
    requireAuth();
    if (!isAdmin()) {
        header("Location: " . BASE_URL . "/employee/dashboard.php");
        exit();
    }
}

function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    if ($lat1 == 0 || $lon1 == 0 || $lat2 == 0 || $lon2 == 0) return 999999;
    $earthRadius = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return round($earthRadius * $c);
}
