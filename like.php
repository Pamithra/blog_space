<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';

header('Content-Type: application/json');

// Must be logged in to like
if (!is_logged_in()) {
  echo json_encode(['ok' => false, 'error' => 'Login required']);
  exit;
}

// Check CSRF token
if (empty($_POST['_csrf']) || $_POST['_csrf'] !== ($_SESSION['_csrf'] ?? '')) {
  echo json_encode(['ok' => false, 'error' => 'Invalid CSRF token']);
  exit;
}

$post_id = (int)($_POST['id'] ?? 0);
$user_id = $_SESSION['user']['id'] ?? 0;

if ($post_id <= 0) {
  echo json_encode(['ok' => false, 'error' => 'Invalid post']);
  exit;
}

// Check if already liked
$stmt = $pdo->prepare('SELECT id FROM post_like WHERE post_id = :pid AND user_id = :uid');
$stmt->execute([':pid' => $post_id, ':uid' => $user_id]);
$like = $stmt->fetch();

if ($like) {
  // Unlike
  $pdo->prepare('DELETE FROM post_like WHERE id = :id')->execute([':id' => $like['id']]);
  $liked = false;
} else {
  // Like
  $pdo->prepare('INSERT INTO post_like (post_id, user_id, created_at) VALUES (:pid, :uid, NOW())')
      ->execute([':pid' => $post_id, ':uid' => $user_id]);
  $liked = true;
}

// Get updated count
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM post_like WHERE post_id = :pid');
$countStmt->execute([':pid' => $post_id]);
$count = $countStmt->fetchColumn();

echo json_encode(['ok' => true, 'count' => $count, 'liked' => $liked]);
