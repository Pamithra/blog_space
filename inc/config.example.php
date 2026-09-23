<?php
// inc/config.example.php
// Copy this file to inc/config.php for local development or configure environment variables in Render.

// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'blog_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_SSL_CA', getenv('DB_SSL_CA') ?: ''); // Path to SSL CA certificate if required by cloud DB

// Application Settings
define('SITE_NAME', getenv('SITE_NAME') ?: 'BlogSpace');

// Base URL Auto-Detection (or override with environment variable)
if (getenv('BASE_URL')) {
    $baseUrl = rtrim(getenv('BASE_URL'), '/') . '/';
} else {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Automatically detect script directory
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    // If inside a subfolder (like /posts or /auth or /admin), strip it to reach root
    $scriptDir = preg_replace('#/(posts|auth|admin|inc)$#', '', $scriptDir);
    $baseUrl = $protocol . $host . rtrim($scriptDir, '/') . '/';
}
define('BASE_URL', $baseUrl);

// Uploads & Media
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Optional Cloudinary Configuration (for persistent cloud media on Render)
define('CLOUDINARY_CLOUD_NAME', getenv('CLOUDINARY_CLOUD_NAME') ?: '');
define('CLOUDINARY_API_KEY', getenv('CLOUDINARY_API_KEY') ?: '');
define('CLOUDINARY_API_SECRET', getenv('CLOUDINARY_API_SECRET') ?: '');

// Secure Session Initialization
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}
