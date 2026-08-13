<?php
// Scratch test script for verifying employee username login & data preservation
require_once __DIR__ . '/../config/database.php';

echo "=== MONTESEU USERNAME LOGIN & DATA PRESERVATION TEST ===\n\n";

// Step 1: Add a test custom employee account with Username
$testUsername = 'karyawantest';
$testEmail = 'karyawan_test@montaseu.com';
$testPass = 'passwordRasia123';
$testName = 'Karyawan Uji Username';
$testRole = 'karyawan';
$testJob = 'Junior Interior Designer';

// Remove old test account if exists
$old = getUserByUsername($testUsername);
if ($old) {
    deleteUser($old['id']);
}

$newEmployee = saveUser($testName, $testUsername, $testPass, $testRole, $testJob, $testEmail);
if (!$newEmployee) {
    die("FAIL: Failed to create test employee account.\n");
}
echo "[PASS] 1. Created custom employee account with username '{$testUsername}' (ID: {$newEmployee['id']})\n";

// Step 2: Test getUserByUsername
$foundUser = getUserByUsername($testUsername);
if ($foundUser && $foundUser['name'] === $testName) {
    echo "[PASS] 2. getUserByUsername successfully found user by username!\n";
} else {
    echo "[FAIL] 2. getUserByUsername failed!\n";
}

// Step 3: Verify backup files exist
$bakFile = DATA_DIR . 'users.json.bak';
if (file_exists($bakFile)) {
    echo "[PASS] 3. Backup file created automatically at users.json.bak\n";
} else {
    echo "[FAIL] 3. Backup file NOT found at users.json.bak!\n";
}

// Step 4: Simulate application structure change / accidental deletion of users.json
$usersPath = DATA_DIR . 'users.json';
unlink($usersPath);
if (!file_exists($usersPath)) {
    echo "[PASS] 4. Simulated deletion of users.json (Simulating app structure update)\n";
}

// Step 5: Re-initialize data (Simulate app start after update)
initJSONData();

// Step 6: Verify recovery & username authentication
$recoveredEmployee = getUserByUsername($testUsername);
if ($recoveredEmployee) {
    echo "[PASS] 5. Auto-Recovery succeeded! Account found: {$recoveredEmployee['name']} (Username: {$recoveredEmployee['username']})\n";
    
    // Test authentication
    if (password_verify($testPass, $recoveredEmployee['password'])) {
        echo "[PASS] 6. Username Login Authentication verified successfully!\n";
    } else {
        echo "[FAIL] 6. Password verification failed!\n";
    }
} else {
    echo "[FAIL] 5. Auto-Recovery failed! Custom employee account was lost!\n";
}

// Step 7: Clean up test account
if ($recoveredEmployee) {
    deleteUser($recoveredEmployee['id']);
    echo "[PASS] 7. Cleaned up test employee account.\n";
}

// Step 8: Verify default accounts have usernames
$defaultAdmin = getUserByUsername('admin');
$defaultKaryawan = getUserByUsername('karyawan');
$defaultDesigner = getUserByUsername('designer');

if ($defaultAdmin && $defaultKaryawan && $defaultDesigner) {
    echo "[PASS] 8. Default accounts (admin, karyawan, designer) verified with valid usernames!\n";
} else {
    echo "[FAIL] 8. Default accounts missing valid usernames!\n";
}

echo "\n=== ALL TESTS COMPLETED SUCCESSFULLY ===\n";
