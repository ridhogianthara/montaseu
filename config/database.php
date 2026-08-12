<?php
/**
 * JSON File-Based Data Storage & Session Configuration (Zero Database / Vercel Serverless Compatible)
 * Montaseu Studio - Interior Design Attendance System
 */

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

// Helper untuk membaca & menulis JSON
function loadJSON($file, $default = []) {
    $path = DATA_DIR . $file;
    if (!file_exists($path)) {
        saveJSON($file, $default);
        return $default;
    }
    $content = file_get_contents($path);
    $data = json_decode($content, true);
    return is_array($data) ? $data : $default;
}

function saveJSON($file, $data) {
    if (!file_exists(DATA_DIR)) {
        @mkdir(DATA_DIR, 0777, true);
    }
    $path = DATA_DIR . $file;
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

// Inisialisasi Data Default (Seed JSON jika belum ada)
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

    // Users Default
    $users = loadJSON('users.json', []);
    $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
    $userPass = password_hash('user123', PASSWORD_DEFAULT);

    $defaultAccounts = [
        ['id' => 1, 'name' => 'Admin Montaseu', 'email' => 'admin@montaseu.com', 'password' => $adminPass, 'role' => 'admin', 'job_title' => 'Studio Manager / Admin', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 2, 'name' => 'Karyawan Montaseu', 'email' => 'karyawan@montaseu.com', 'password' => $userPass, 'role' => 'karyawan', 'job_title' => 'Staff Interior Designer', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 3, 'name' => 'Rian Pratama', 'email' => 'designer@montaseu.com', 'password' => $userPass, 'role' => 'karyawan', 'job_title' => 'Lead Interior Designer', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 4, 'name' => 'Siti Amalia', 'email' => 'architect@montaseu.com', 'password' => $userPass, 'role' => 'karyawan', 'job_title' => 'Senior Project Architect', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 5, 'name' => 'Budi Santoso', 'email' => 'supervisor@montaseu.com', 'password' => $userPass, 'role' => 'karyawan', 'job_title' => 'Site Supervisor', 'created_at' => date('Y-m-d H:i:s')]
    ];

    if (empty($users)) {
        saveJSON('users.json', $defaultAccounts);
    } else {
        // Ensure default accounts exist & have valid password hashes
        $existingEmails = array_column($users, 'email');
        $updated = false;
        foreach ($defaultAccounts as $acc) {
            $key = array_search($acc['email'], $existingEmails);
            if ($key === false) {
                $users[] = $acc;
                $updated = true;
            } else {
                $users[$key]['password'] = $acc['password'];
                $users[$key]['role'] = $acc['role'];
                $updated = true;
            }
        }
        if ($updated) saveJSON('users.json', $users);
    }

    // Attendances Default File
    if (!file_exists(DATA_DIR . 'attendances.json')) {
        saveJSON('attendances.json', []);
    }
}

initJSONData();

// --- DATA ACCESS API FUNCTIONS (JSON BASED) ---

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

function getUserByEmail($email) {
    $users = getUsers();
    foreach ($users as $u) {
        if (strtolower($u['email']) === strtolower($email)) {
            return $u;
        }
    }
    return null;
}

function getUserById($id) {
    $users = getUsers();
    foreach ($users as $u) {
        if ($u['id'] == $id) {
            return $u;
        }
    }
    return null;
}

function saveUser($name, $email, $password, $role, $jobTitle, $id = null) {
    $users = getUsers();
    if ($id) {
        foreach ($users as &$u) {
            if ($u['id'] == $id) {
                $u['name'] = $name;
                $u['email'] = $email;
                $u['role'] = $role;
                $u['job_title'] = $jobTitle;
                if (!empty($password)) {
                    $u['password'] = password_hash($password, PASSWORD_DEFAULT);
                }
                saveJSON('users.json', $users);
                return $u;
            }
        }
    } else {
        $maxId = 0;
        foreach ($users as $u) {
            if ($u['id'] > $maxId) $maxId = $u['id'];
        }
        $newUser = [
            'id' => $maxId + 1,
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
        return $u['id'] != $id;
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
        if ($a['user_id'] == $userId && $a['date'] === $date) {
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
