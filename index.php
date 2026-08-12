<?php
require_once __DIR__ . '/config/database.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: " . BASE_URL . "/admin/dashboard.php");
        exit();
    } else {
        header("Location: " . BASE_URL . "/employee/dashboard.php");
        exit();
    }
} else {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}
