<?php
// posts/delete.php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';

// Only accept POST to perform deletion
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash'] = 'Invalid request';
    header('Location: ' . BASE_URL);
    exit;
}

// Require login
if (!is_logged_in()) {
    $_SESSION['flash'] = 'You must be logged in to delete posts';
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

// CSRF check - adjust name if your forms use "_csrf" or other name
$token = $_POST['_csrf'] ?? '';
if (!csrf_verify($token)) {
    $_SESSION['flash'] = 'Invalid CSRF token';
    header('Location: ' . BASE_URL);
    exit;
}

$post_id = (int)($_POST['post_id'] ?? 0);
if ($post_id <= 0) {
    $_SESSION['flash'] = 'Invalid post id';
    header('Location: ' . BASE_URL);
    exit;
}

try {
    // Start transaction to keep DB consistent while deleting related rows
    $pdo->beginTransaction();

    // Get post (to check owner and remove image file)
    $stmt = $pdo->prepare('SELECT id, user_id, image FROM blogPost WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $post_id]);
    $post = $stmt->fetch();

    if (!$post) {
        $pdo->rollBack();
        $_SESSION['flash'] = 'Post not found';
        header('Location: ' . BASE_URL);
        exit;
    }

    $current_user_id = $_SESSION['user']['id'];

    // Allow only author or admin to delete
    if ($post['user_id'] != $current_user_id && !is_admin()) {
        $pdo->rollBack();
        $_SESSION['flash'] = 'Permission denied';
        header('Location: ' . BASE_URL . 'posts/view.php?id=' . $post_id);
        exit;
    }

    // Remove likes for the post (table in your app is post_like)
    $pdo->prepare('DELETE FROM post_like WHERE post_id = :id')->execute([':id' => $post_id]);

    // Remove comments for the post (table named comment in your app)
    $pdo->prepare('DELETE FROM comment WHERE post_id = :id')->execute([':id' => $post_id]);

    // Delete the post row itself
    $pdo->prepare('DELETE FROM blogPost WHERE id = :id')->execute([':id' => $post_id]);

    // Commit DB changes
    $pdo->commit();

    // Remove post image file from disk if exists (safe unlink)
    if (!empty($post['image'])) {
        $imgPath = UPLOAD_DIR . $post['image'];
        if (file_exists($imgPath) && is_file($imgPath)) {
            @unlink($imgPath);
        }
    }

    $_SESSION['flash'] = 'Post deleted successfully';
    // Redirect to homepage or profile page
    header('Location: ' . BASE_URL);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    // For debugging you might log $e->getMessage() to your error logger
    $_SESSION['flash'] = 'An error occurred while deleting the post';
    header('Location: ' . BASE_URL . 'posts/view.php?id=' . $post_id);
    exit;
}
?>
