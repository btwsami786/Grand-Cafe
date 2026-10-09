<?php
/**
 * Grand Cafe - Vercel Serverless Gateway Router
 * Dispatches web requests to the appropriate PHP pages.
 */

// Buffer output so cookies/sessions can be dispatched cleanly
ob_start();
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = trim($uri, '/');

// Default home
if (empty($path) || $path === 'index.php') {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['PHP_SELF'] = '/index.php';
    require __DIR__ . '/../index.php';
    exit;
}

$rootPath = dirname(__DIR__);
$targetFile = $rootPath . '/' . $path;

// Direct file match (e.g. menu.php, setup.php)
if (is_file($targetFile)) {
    $_SERVER['SCRIPT_NAME'] = '/' . $path;
    $_SERVER['PHP_SELF'] = '/' . $path;
    require $targetFile;
    exit;
}

// Extensionless match (e.g. /menu -> menu.php)
if (is_file($targetFile . '.php')) {
    $_SERVER['SCRIPT_NAME'] = '/' . $path . '.php';
    $_SERVER['PHP_SELF'] = '/' . $path . '.php';
    require $targetFile . '.php';
    exit;
}

// 404 Fallback
http_response_code(404);
echo "<!DOCTYPE html><html><head><title>404 Not Found</title></head><body style='font-family:sans-serif;text-align:center;padding:50px;'><h2>404 - Page Not Found</h2><p>The requested page could not be found.</p><a href='/'>Return to Grand Cafe</a></body></html>";
exit;
