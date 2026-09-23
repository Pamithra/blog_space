<?php
// image.php
// Serves database-backed uploaded images with persistent caching and conditional HTTP headers

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(404);
    exit('Image not found.');
}

try {
    $stmt = $pdo->prepare('SELECT mime_type, file_data, file_size, created_at FROM uploaded_image WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $image = $stmt->fetch();

    if (!$image || empty($image['file_data'])) {
        http_response_code(404);
        exit('Image not found.');
    }

    $mimeType = $image['mime_type'] ?: 'image/jpeg';
    $fileData = $image['file_data'];
    $fileSize = !empty($image['file_size']) ? (int)$image['file_size'] : strlen($fileData);
    $etag = '"img-' . $id . '-' . md5($image['created_at'] ?? (string)$id) . '"';

    // HTTP Browser Caching & Conditional Get
    header('Cache-Control: public, max-age=31536000, immutable');
    header('ETag: ' . $etag);
    header('X-Content-Type-Options: nosniff');

    if (!empty($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
        http_response_code(304);
        exit;
    }

    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . $fileSize);

    echo $fileData;
    exit;

} catch (Exception $e) {
    error_log('Error serving image: ' . $e->getMessage());
    http_response_code(500);
    exit('Error loading image.');
}
