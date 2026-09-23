<?php
// posts/comment_add.php
// Add a comment to an article

require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';

if (!is_logged_in()) {
    set_flash('You must be logged in to leave a comment.', 'error');
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify($_POST['_csrf'] ?? '')) {
    set_flash('Session verification failed. Please try again.', 'error');
    header('Location: ' . BASE_URL);
    exit;
}

$postId = (int)($_POST['post_id'] ?? 0);
$body = trim($_POST['body'] ?? '');

if ($postId <= 0 || empty($body)) {
    set_flash('Comment text cannot be empty.', 'error');
    header('Location: ' . BASE_URL . ($postId > 0 ? 'posts/view.php?id=' . $postId : ''));
    exit;
}

// Insert comment
$stmt = $pdo->prepare('INSERT INTO comment (post_id, user_id, body, created_at) VALUES (:pid, :uid, :body, NOW())');
$stmt->execute([
    ':pid'  => $postId,
    ':uid'  => current_user_id(),
    ':body' => $body
]);

set_flash('Your comment has been added!', 'success');
header('Location: ' . BASE_URL . 'posts/view.php?id=' . $postId . '#comments');
exit;