<?php
// like.php
// Asynchronous like/unlike handler

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['ok' => false, 'error' => 'You must log in to like this post.']);
    exit;
}

if (!csrf_verify($_POST['_csrf'] ?? '')) {
    echo json_encode(['ok' => false, 'error' => 'Session expired. Please refresh the page.']);
    exit;
}

$postId = (int)($_POST['id'] ?? 0);
$userId = current_user_id();

if ($postId <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Invalid article reference.']);
    exit;
}

// Check current like status
$stmt = $pdo->prepare('SELECT id FROM post_like WHERE post_id = :pid AND user_id = :uid LIMIT 1');
$stmt->execute([':pid' => $postId, ':uid' => $userId]);
$existingLike = $stmt->fetch();

if ($existingLike) {
    // Remove like
    $pdo->prepare('DELETE FROM post_like WHERE id = :id')->execute([':id' => $existingLike['id']]);
    $liked = false;
} else {
    // Add like
    $pdo->prepare('INSERT INTO post_like (post_id, user_id, created_at) VALUES (:pid, :uid, NOW())')
        ->execute([':pid' => $postId, ':uid' => $userId]);
    $liked = true;
}

// Fetch total updated likes
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM post_like WHERE post_id = :pid');
$countStmt->execute([':pid' => $postId]);
$totalLikes = (int)$countStmt->fetchColumn();

echo json_encode([
    'ok'    => true,
    'count' => $totalLikes,
    'liked' => $liked
]);
exit;
