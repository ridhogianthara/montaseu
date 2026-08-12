<?php
/**
 * Vercel Serverless Function Router for Montaseu Studio
 */

require_once __DIR__ . '/../config/database.php';

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$parsed = parse_url($requestUri);
$path = urldecode($parsed['path'] ?? '/');

// Strip /Montaseu prefix if present
if (strpos($path, '/Montaseu') === 0) {
    $path = substr($path, 9);
}

if (empty($path) || $path === '/') {
    require __DIR__ . '/../index.php';
    exit();
}

$targetFile = realpath(__DIR__ . '/..' . $path);
$rootDir = realpath(__DIR__ . '/..');

if ($targetFile && strpos($targetFile, $rootDir) === 0 && file_exists($targetFile) && !is_dir($targetFile)) {
    require $targetFile;
    exit();
}

// Fallback to index.php
require __DIR__ . '/../index.php';
