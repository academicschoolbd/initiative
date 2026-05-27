<?php
/**
 * Smart Maheshkhali — application bootstrap.
 *
 * Loads configuration, sets error handling, configures secure sessions,
 * sends shared security headers, and exposes a small set of helpers used
 * by every entry point.
 *
 * Every entry script (index, submit, admin, ...) does:
 *
 *     require_once __DIR__ . '/includes/bootstrap.php';
 *
 * before doing anything else.
 */

declare(strict_types=1);

if (defined('SMK_BOOTSTRAPPED')) {
    return;
}
define('SMK_BOOTSTRAPPED', true);

// ---------------------------------------------------------------------
// 1. Configuration loader
// ---------------------------------------------------------------------

$configPath = __DIR__ . '/../config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Configuration missing.\n\n";
    echo "Copy `config.example.php` to `config.php` and set your admin password hash.\n";
    echo "See README.md for instructions.\n";
    exit;
}

/** @var array<string, mixed> $CONFIG */
$CONFIG = require $configPath;
if (!is_array($CONFIG)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "config.php must return an array.";
    exit;
}

// Defaults (so older config.php files still work after upgrades)
$CONFIG += [
    'app_name'                    => 'Smart Maheshkhali',
    'app_env'                     => 'production',
    'app_debug'                   => false,
    'base_path'                   => '',
    'admin_username'              => 'admin',
    'admin_password_hash'         => '',
    'quotas'                      => ['primary' => 3, 'madrasah' => 3, 'high_school' => 3],
    'submission_cooldown_seconds' => 60,
    'force_https'                 => false,
    'sqlite_path'                 => __DIR__ . '/../database.sqlite',
];

// ---------------------------------------------------------------------
// 2. Error handling
// ---------------------------------------------------------------------

if ($CONFIG['app_env'] === 'development' || !empty($CONFIG['app_debug'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}
// Always emit plain-text errors so any leak does not contaminate HTML
// or CSV downloads with `<br />` markup.
ini_set('html_errors', '0');

// Convert any uncaught throwable into a generic 500 page so that
// stack traces and database error messages never reach end users.
set_exception_handler(function (Throwable $e): void {
    error_log('[Smart Maheshkhali] Uncaught: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    echo '<!doctype html><html lang="bn"><meta charset="utf-8">';
    echo '<title>সিস্টেম ত্রুটি</title>';
    echo '<body style="font-family:sans-serif;padding:40px;text-align:center;">';
    echo '<h2>সাময়িক সমস্যা হয়েছে</h2>';
    echo '<p>সিস্টেম প্রশাসকের কাছে রিপোর্ট করুন। অনুগ্রহ করে কিছুক্ষণ পর আবার চেষ্টা করুন।</p>';
    echo '</body></html>';
    exit;
});

// ---------------------------------------------------------------------
// 3. HTTPS enforcement (optional)
// ---------------------------------------------------------------------

if (!empty($CONFIG['force_https'])
    && empty($_SERVER['HTTPS'])
    && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') !== 'https'
    && PHP_SAPI !== 'cli'
    && PHP_SAPI !== 'cli-server'
) {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $uri  = $_SERVER['REQUEST_URI'] ?? '/';
    header('Location: https://' . $host . $uri, true, 301);
    exit;
}

// ---------------------------------------------------------------------
// 4. Security headers (sent for every request that uses bootstrap)
// ---------------------------------------------------------------------

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(self), microphone=(), camera=()');
    // Allow Google Fonts which the UI uses; everything else same-origin.
    header(
        "Content-Security-Policy: default-src 'self'; "
        . "img-src 'self' data:; "
        . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
        . "font-src 'self' https://fonts.gstatic.com; "
        . "script-src 'self' 'unsafe-inline'; "
        . "form-action 'self'; "
        . "frame-ancestors 'self'; "
        . "base-uri 'self'"
    );
}

// ---------------------------------------------------------------------
// 5. Secure session
// ---------------------------------------------------------------------

if (session_status() === PHP_SESSION_NONE) {
    $secure = !empty($_SERVER['HTTPS'])
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('SMKSESSID');
    if (PHP_SAPI !== 'cli') {
        session_start();
    }
}

// ---------------------------------------------------------------------
// 6. Helpers / database
// ---------------------------------------------------------------------

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../database.php';
