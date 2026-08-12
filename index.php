<?php
require_once __DIR__ . '/config/database.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: /Montaseu/admin/dashboard.php");
        exit();
    } else {
        header("Location: /Montaseu/employee/dashboard.php");
        exit();
    }
} else {
    header("Location: /Montaseu/auth/login.php");
    exit();
}
