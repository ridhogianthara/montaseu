<?php
/**
 * Data Storage, Session, & Google Sheets Database Configuration
 * Montaseu Studio - Interior Design Attendance System
 */

date_default_timezone_set('Asia/Jakarta');

if (ob_get_level() == 0) {
    ob_start();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getBaseUrl() {
    if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
        return '/Montaseu';
    }
    return '';
}
define('BASE_URL', getBaseUrl());

// Google Sheets & ImgBB Config
define('GOOGLE_APP_SCRIPT_URL', 'https://script.google.com/macros/s/AKfycbyWj3yl8PD9DjJrT8Ra0OYTQzK17LMpnaSa4SU-xWOVR0rRd8pGMI0VnxZJvVcS6QBWKA/exec');
define('IMGBB_API_KEY', '9a4e29e3dacea965c75e35b4dd2b6d3c');

// Helper to upload image to ImgBB
function uploadImageToImgbb($base64Data) {
    if (empty(IMGBB_API_KEY) || IMGBB_API_KEY === 'PASTE_YOUR_IMGBB_API_KEY_HERE') {
        return 'https://placehold.co/400x300?text=No+ImgBB+Key';
    }

    if (strpos($base64Data, 'data:image') === 0) {
        $parts = explode(',', $base64Data);
        $base64Data = $parts[1] ?? '';
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.imgbb.com/1/upload?key=' . IMGBB_API_KEY);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    $data = array('image' => $base64Data);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    
    // Disable SSL verify for local dev environments
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

    $result = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($result, true);
    if (isset($json['data']['url'])) {
        return $json['data']['url'];
    }
    
    // CRITICAL FIX: Do NOT return $base64Data here!
    // Google Sheets has a strict 50,000 character limit per cell.
    // Webcam base64 images are >1MB (1,000,000+ chars), which crashes Apps Script.
    return 'https://placehold.co/400x300?text=ImgBB+Forbidden/Failed'; 
}

// Global Cache to prevent multiple HTTP requests
$GLOBALS['GOOGLE_SHEET_DATA'] = null;

function fetchAllFromGoogle() {
    global $GOOGLE_SHEET_DATA;
    if ($GOOGLE_SHEET_DATA !== null) return $GOOGLE_SHEET_DATA;
    
    if (GOOGLE_APP_SCRIPT_URL === 'PASTE_YOUR_GOOGLE_APP_SCRIPT_URL_HERE') {
        // Return mock data so the app doesn't crash before setup
        return [
            'settings' => [
                'company_name' => 'Montaseu Studio',
                'office_address' => 'Setup Google Sheets URL first',
                'office_lat' => '-6.230588',
                'office_lng' => '106.808018',
                'office_radius' => '500',
                'work_start' => '08:30',
                'work_end' => '17:30'
            ],
            'users' => [
                ['id' => 1, 'username' => 'admin', 'name' => 'Admin', 'email' => 'admin@admin.com', 'password' => password_hash('admin123', PASSWORD_DEFAULT), 'role' => 'admin', 'job_title' => 'Admin']
            ],
            'attendances' => []
        ];
    }
    
    $url = GOOGLE_APP_SCRIPT_URL . '?action=get_all';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $json = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($json, true);
    
    if (isset($data['users'])) {
        $GOOGLE_SHEET_DATA = $data;
    } else {
        $GOOGLE_SHEET_DATA = ['users' => [], 'attendances' => [], 'settings' => []];
    }
    return $GOOGLE_SHEET_DATA;
}

function saveToGoogle($action, $data) {
    if (GOOGLE_APP_SCRIPT_URL === 'PASTE_YOUR_GOOGLE_APP_SCRIPT_URL_HERE') return false;

    $url = GOOGLE_APP_SCRIPT_URL;
    $ch = curl_init($url);
    
    $payload = json_encode([
        'action' => $action,
        'data' => $data
    ]);
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_POSTREDIR, 3); // Preserve POST on 301/302/303 redirect
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    curl_close($ch);
    
    // Invalidate cache
    global $GOOGLE_SHEET_DATA;
    $GOOGLE_SHEET_DATA = null;
    
    return true;
}

// --- DATA ACCESS API FUNCTIONS ---

function getSettings() {
    $data = fetchAllFromGoogle();
    $settings = $data['settings'] ?? [];
    if (empty($settings) && GOOGLE_APP_SCRIPT_URL !== 'PASTE_YOUR_GOOGLE_APP_SCRIPT_URL_HERE') {
        // Fallback default if sheet is empty
        $settings = [
            'company_name' => 'Montaseu Studio',
            'office_address' => 'Jl. Senopati No. 45, Kebayoran Baru, Jakarta Selatan',
            'office_lat' => '-6.230588',
            'office_lng' => '106.808018',
            'office_radius' => '500',
            'work_start' => '08:30',
            'work_end' => '17:30'
        ];
        saveToGoogle('save_settings', $settings);
    }
    return $settings;
}

function updateSetting($key, $value) {
    $settings = getSettings();
    $settings[$key] = $value;
    saveToGoogle('save_settings', $settings);
    return true;
}

function getUsers() {
    $data = fetchAllFromGoogle();
    $users = $data['users'] ?? [];
    
    if (empty($users) && GOOGLE_APP_SCRIPT_URL !== 'PASTE_YOUR_GOOGLE_APP_SCRIPT_URL_HERE') {
        $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
        $users = [
            ['id' => 1, 'username' => 'admin', 'name' => 'Admin Montaseu', 'email' => 'admin@montaseu.com', 'password' => $adminPass, 'role' => 'admin', 'job_title' => 'Studio Manager / Admin', 'created_at' => date('Y-m-d H:i:s')]
        ];
        saveToGoogle('save_users', $users);
    }
    return $users;
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
            saveToGoogle('save_users', $users);
            return getUserById($id);
        }
    } else {
        $maxId = 0;
        foreach ($users as $u) {
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
        saveToGoogle('save_users', $users);
        return $newUser;
    }
    return false;
}

function deleteUser($id) {
    $users = getUsers();
    $newUsers = array_filter($users, function($u) use ($id) {
        return (int)$u['id'] !== (int)$id;
    });
    $reindexed = array_values($newUsers);

    saveToGoogle('save_users', $reindexed);
    return true;
}

function getAttendances() {
    $data = fetchAllFromGoogle();
    return $data['attendances'] ?? [];
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
        'clock_in_location_type' => $locationType,
        'clock_out_location_type' => null,
        'created_at' => date('Y-m-d H:i:s')
    ];

    $attendances[] = $record;
    saveToGoogle('save_attendances', $attendances);
    return $record;
}

function saveClockOut($attendanceId, $photo, $lat, $lng, $address, $notes, $duration, $locationType = 'Studio Office') {
    $attendances = getAttendances();
    foreach ($attendances as &$a) {
        if ($a['id'] == $attendanceId) {
            $a['clock_out_time'] = date('Y-m-d H:i:s');
            $a['clock_out_photo'] = $photo;
            $a['clock_out_lat'] = (float)$lat;
            $a['clock_out_lng'] = (float)$lng;
            $a['clock_out_address'] = $address;
            $a['clock_out_notes'] = $notes;
            $a['clock_out_location_type'] = $locationType;
            $a['work_duration'] = $duration;
            saveToGoogle('save_attendances', $attendances);
            return $a;
        }
    }
    return false;
}

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
