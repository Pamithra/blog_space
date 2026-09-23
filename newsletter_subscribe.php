<?php
// newsletter_subscribe.php
// Asynchronous newsletter subscription handler

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (!csrf_verify($_POST['_csrf'] ?? '')) {
    echo json_encode(['ok' => false, 'message' => 'Session expired. Please refresh the page.']);
    exit;
}

$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

try {
    $ins = $pdo->prepare('INSERT INTO newsletter (email, created_at) VALUES (:e, NOW())');
    $ins->execute([':e' => $email]);
    echo json_encode(['ok' => true, 'message' => 'Thank you for subscribing to BlogSpace!']);
} catch (PDOException $e) {
    // Unique key violation or other SQL error
    echo json_encode(['ok' => false, 'message' => 'You are already subscribed to our newsletter!']);
}
exit;