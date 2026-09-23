<?php
// inc/db.php
// Database connection layer with support for local MySQL and Cloud Providers (TiDB, Aiven, etc.)
require_once __DIR__ . '/config.php';

try {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // Detect SSL CA bundle path
    $caFile = null;
    if (!empty(DB_SSL_CA) && file_exists(DB_SSL_CA)) {
        $caFile = DB_SSL_CA;
    } elseif (file_exists(__DIR__ . '/cacert.pem')) {
        $caFile = __DIR__ . '/cacert.pem';
    } elseif (file_exists('/etc/ssl/certs/ca-certificates.crt')) {
        $caFile = '/etc/ssl/certs/ca-certificates.crt';
    }

    // Enable SSL for cloud databases (TiDB port 4000 or any non-localhost host)
    $isRemote = (DB_PORT == 4000) || (DB_HOST !== 'localhost' && DB_HOST !== '127.0.0.1');
    if ($caFile && $isRemote) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $caFile;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch (PDOException $e) {
    // Log details and display user-friendly message
    error_log('Database Connection Error: ' . $e->getMessage());
    die('
        <div style="font-family:sans-serif;padding:30px;max-width:600px;margin:50px auto;background:#fff3f3;border:1px solid #ffa3a3;border-radius:8px;color:#7a1a1a;">
            <h2 style="margin-top:0;">Database Connection Error</h2>
            <p>Could not connect to the database server. Please ensure MySQL is running and your connection settings in <code>inc/config.php</code> or environment variables are correct.</p>
            <p><small>Error: ' . htmlspecialchars($e->getMessage()) . '</small></p>
        </div>
    ');
}
