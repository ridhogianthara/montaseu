<?php
/**
 * Vercel Serverless Function Router for Montaseu Studio
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Route to specific PHP files if requested
if ($uri === '/index.php' || $uri === '/') {
    require __DIR__ . '/../index.php';
    exit();
}

$targetFile = __DIR__ . '/..' . $uri;

if (file_exists($targetFile) && !is_dir($targetFile)) {
    require $targetFile;
    exit();
}

// Default fallback to index.php
require __DIR__ . '/../index.php';
