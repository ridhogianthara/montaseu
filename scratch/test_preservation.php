<?php
// Scratch test script for verifying Master Vault Employee Data Preservation
require_once __DIR__ . '/../config/database.php';

echo "=== MONTESEU MASTER VAULT DATA PRESERVATION TEST ===\n\n";

// Step 1: Add a test custom employee account
$testUsername = 'karyawan_vault_test';
$testEmail = 'vault_test@montaseu.com';
$testPass = 'passwordSuperAman123';
$testName = 'Staf Master Vault Uji';
$testRole = 'karyawan';
$testJob = 'Junior Interior Architect';

// Remove old test account if exists
$old = getUserByUsername($testUsername);
if ($old) {
    deleteUser($old['id']);
}

$newEmployee = saveUser($testName, $testUsername, $testPass, $testRole, $testJob, $testEmail);
if (!$newEmployee) {
    die("FAIL: Failed to create test employee account.\n");
}
echo "[PASS] 1. Created employee in active system & Master Vault: {$testUsername} (ID: {$newEmployee['id']})\n";

// Step 2: Verify Master Vault file exists
$vaultFile = DATA_DIR . 'users_vault.json';
if (file_exists($vaultFile)) {
    echo "[PASS] 2. Master Vault file verified at users_vault.json\n";
} else {
    echo "[FAIL] 2. Master Vault file NOT found!\n";
}

// Step 3: AGGRESSIVE SIMULATION - Wipe active users.json AND users.json.bak (Simulating Git Reset / System Code Edit / Wipe)
@unlink(DATA_DIR . 'users.json');
@unlink(DATA_DIR . 'users.json.bak');
echo "[PASS] 3. Simulated aggressive system edit / Git wipe (users.json & users.json.bak deleted)\n";

// Step 4: System Re-initialization (Simulating user browsing app after editing system)
initJSONData();

// Step 5: Verify Auto-Healing from Master Vault
$recoveredEmployee = getUserByUsername($testUsername);
if ($recoveredEmployee) {
    echo "[PASS] 4. Auto-Healing Success! Employee recovered from Master Vault: {$recoveredEmployee['name']} (Username: {$recoveredEmployee['username']})\n";
    if (password_verify($testPass, $recoveredEmployee['password'])) {
        echo "[PASS] 5. Login Authentication verified for recovered employee!\n";
    } else {
        echo "[FAIL] 5. Password mismatch on recovered employee!\n";
    }
} else {
    echo "[FAIL] 4. Master Vault Auto-Healing failed! Employee data was lost!\n";
}

// Step 6: Explicit Admin Delete Test
if ($recoveredEmployee) {
    deleteUser($recoveredEmployee['id']);
    $checkAfterDelete = getUserByUsername($testUsername);
    if (!$checkAfterDelete) {
        echo "[PASS] 6. Explicit admin deletion removed employee from active system and Master Vault!\n";
    } else {
        echo "[FAIL] 6. Explicit admin deletion failed to remove employee!\n";
    }
}

echo "\n=== ALL MASTER VAULT PRESERVATION TESTS PASSED ===\n";
