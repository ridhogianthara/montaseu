<?php
/**
 * Data Storage, Session, & Data Preservation Configuration
 * Montaseu Studio - Interior Design Attendance System
 * Zero Database / Vercel Compatible with Auto-Backup & Self-Healing Data Access
 */

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

// Tentukan direktori penyimpanan data (fallback ke temp dir jika read-only seperti di Vercel)
$targetDataDir = __DIR__ . '/../data/';
if (!file_exists($targetDataDir)) {
    @mkdir($targetDataDir, 0777, true);
}
if (!@is_writable($targetDataDir)) {
    $targetDataDir = sys_get_temp_dir() . '/montaseu_data/';
}

$targetUploadsDir = __DIR__ . '/../uploads/selfies/';
if (!file_exists($targetUploadsDir)) {
    @mkdir($targetUploadsDir, 0777, true);
}
if (!@is_writable($targetUploadsDir)) {
    $targetUploadsDir = sys_get_temp_dir() . '/montaseu_uploads/';
}

define('DATA_DIR', rtrim($targetDataDir, '/') . '/');
define('UPLOADS_DIR', rtrim($targetUploadsDir, '/') . '/');

/**
 * --- MEKANISME PROTEKSI & BACKUP AUTOMATIS DATA PENGGUNA ---
 */

function backupUserData($usersData) {
    if (!is_array($usersData) || empty($usersData)) return;
    $encoded = json_encode($usersData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    // Backup 1: File .bak di dalam DATA_DIR
    $primaryBak = DATA_DIR . 'users.json.bak';
    @file_put_contents($primaryBak, $encoded, LOCK_EX);

    // Backup 2: Subfolder backups di DATA_DIR
    $backupDir = DATA_DIR . 'backups/';
    if (!file_exists($backupDir)) {
        @mkdir($backupDir, 0777, true);
    }
    if (file_exists($backupDir)) {
        @file_put_contents($backupDir . 'users_latest.json', $encoded, LOCK_EX);
    }

    // Backup 3: Persistent temp dir untuk pemulihan darurat jika struktur folder bergeser
    $sysBak = sys_get_temp_dir() . '/montaseu_users_backup.json';
    @file_put_contents($sysBak, $encoded, LOCK_EX);
}

function restoreUserDataFromBackup() {
    $backupPaths = [
        DATA_DIR . 'users.json.bak',
        DATA_DIR . 'backups/users_latest.json',
        sys_get_temp_dir() . '/montaseu_users_backup.json'
    ];

    foreach ($backupPaths as $path) {
        if (file_exists($path) && filesize($path) > 0) {
            $content = file_get_contents($path);
            $data = json_decode($content, true);
            if (is_array($data) && count($data) > 0) {
                return $data;
            }
        }
    }
    return null;
}

// Helper untuk membaca & menulis JSON dengan proteksi Auto-Recovery
function loadJSON($file, $default = []) {
    $path = DATA_DIR . $file;
    if (!file_exists($path) || filesize($path) === 0) {
        if ($file === 'users.json') {
            $recovered = restoreUserDataFromBackup();
            if (!empty($recovered)) {
                saveJSON('users.json', $recovered);
                return $recovered;
            }
        }
        saveJSON($file, $default);
        return $default;
    }

    $content = file_get_contents($path);
    $data = json_decode($content, true);
    if (!is_array($data)) {
        if ($file === 'users.json') {
            $recovered = restoreUserDataFromBackup();
            if (!empty($recovered)) {
                saveJSON('users.json', $recovered);
                return $recovered;
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
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

    if ($file === 'users.json' && is_array($data) && !empty($data)) {
        backupUserData($data);
    }
}

// Inisialisasi Data Default dengan Preservasi Akun Existing & Normalisasi Skema Username
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

    // Standard Default Accounts (dengan Username Biasa)
    $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
    $userPass = password_hash('user123', PASSWORD_DEFAULT);

    $defaultAccounts = [
        ['id' => 1, 'username' => 'admin', 'name' => 'Admin Montaseu', 'email' => 'admin@montaseu.com', 'password' => $adminPass, 'role' => 'admin', 'job_title' => 'Studio Manager / Admin', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 2, 'username' => 'karyawan', 'name' => 'Karyawan Montaseu', 'email' => 'karyawan@montaseu.com', 'password' => $userPass, 'role' => 'karyawan', 'job_title' => 'Staff Interior Designer', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 3, 'username' => 'designer', 'name' => 'Rian Pratama', 'email' => 'designer@montaseu.com', 'password' => $userPass, 'role' => 'karyawan', 'job_title' => 'Lead Interior Designer', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 4, 'username' => 'architect', 'name' => 'Siti Amalia', 'email' => 'architect@montaseu.com', 'password' => $userPass, 'role' => 'karyawan', 'job_title' => 'Senior Project Architect', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 5, 'username' => 'supervisor', 'name' => 'Budi Santoso', 'email' => 'supervisor@montaseu.com', 'password' => $userPass, 'role' => 'karyawan', 'job_title' => 'Site Supervisor', 'created_at' => date('Y-m-d H:i:s')]
    ];

    $usersPath = DATA_DIR . 'users.json';
    if (!file_exists($usersPath)) {
        $recovered = restoreUserDataFromBackup();
        if (!empty($recovered)) {
            $existingUsers = $recovered;
        } else {
            $existingUsers = $defaultAccounts;
        }
    } else {
        $existingUsers = loadJSON('users.json', []);
    }

    if (empty($existingUsers)) {
        $existingUsers = $defaultAccounts;
    }

    // Normalisasi struktur data & Penggabungan aman (Safe Merge)
    $usernamesMap = [];
    $emailsMap = [];
    $idsMap = [];
    $maxId = 0;

    foreach ($existingUsers as &$u) {
        if (!isset($u['username']) || empty($u['username'])) {
            // Auto-generate username dari bagian depan email jika belum ada
            if (!empty($u['email'])) {
                $parts = explode('@', $u['email']);
                $u['username'] = strtolower(trim($parts[0]));
            } else {
                $u['username'] = 'user' . $u['id'];
            }
        }
        if (!isset($u['role'])) $u['role'] = 'karyawan';
        if (!isset($u['job_title'])) $u['job_title'] = 'Staff Interior';
        if (!isset($u['avatar'])) $u['avatar'] = null;
        if (!isset($u['created_at'])) $u['created_at'] = date('Y-m-d H:i:s');

        $usernamesMap[strtolower($u['username'])] = true;
        if (!empty($u['email'])) {
            $emailsMap[strtolower($u['email'])] = true;
        }
        $idsMap[(int)$u['id']] = true;
        if ((int)$u['id'] > $maxId) {
            $maxId = (int)$u['id'];
        }
    }
    unset($u);

    // Tambahkan default account HANYA jika username-nya belum ada
    foreach ($defaultAccounts as $def) {
        if (!isset($usernamesMap[strtolower($def['username'])])) {
            $newId = $def['id'];
            if (isset($idsMap[$newId])) {
                $maxId++;
                $newId = $maxId;
            }
            $def['id'] = $newId;
            $existingUsers[] = $def;
            $usernamesMap[strtolower($def['username'])] = true;
            $idsMap[$newId] = true;
        }
    }

    saveJSON('users.json', $existingUsers);
    backupUserData($existingUsers);

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
            return getUserById($id);
        }
    } else {
        $maxId = 0;
        foreach ($users as $u) {
            if ((int)$u['id'] > $maxId) $maxId = (int)$u['id'];
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
        return $newUser;
    }
    return false;
}

function deleteUser($id) {
    $users = getUsers();
    $newUsers = array_filter($users, function($u) use ($id) {
        return (int)$u['id'] !== (int)$id;
    });
    saveJSON('users.json', array_values($newUsers));
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
