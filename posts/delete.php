<?php
// posts/delete.php
// Safe deletion handler with transaction and ownership authorization

require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('Invalid request method.', 'error');
    header('Location: ' . BASE_URL);
    exit;
}

if (!is_logged_in()) {
    set_flash('You must be logged in to delete articles.', 'error');
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

if (!csrf_verify($_POST['_csrf'] ?? '')) {
    set_flash('Session verification failed. Please try again.', 'error');
    header('Location: ' . BASE_URL);
    exit;
}

$postId = (int)($_POST['post_id'] ?? 0);
if ($postId <= 0) {
    set_flash('Invalid article ID.', 'error');
    header('Location: ' . BASE_URL);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT id, user_id, image FROM blogPost WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $postId]);
    $post = $stmt->fetch();

    if (!$post) {
        $pdo->rollBack();
        set_flash('Article not found.', 'error');
        header('Location: ' . BASE_URL);
        exit;
    }

    // Permission check: Author or Admin
    if ($post['user_id'] != current_user_id() && !is_admin()) {
        $pdo->rollBack();
        set_flash('You do not have permission to delete this article.', 'error');
        header('Location: ' . BASE_URL . 'posts/view.php?id=' . $postId);
        exit;
    }

    // Explicitly delete relations (even though CASCADE is set in DB)
    $pdo->prepare('DELETE FROM post_tag WHERE post_id = :id')->execute([':id' => $postId]);
    $pdo->prepare('DELETE FROM post_like WHERE post_id = :id')->execute([':id' => $postId]);
    $pdo->prepare('DELETE FROM comment WHERE post_id = :id')->execute([':id' => $postId]);
    $pdo->prepare('DELETE FROM blogPost WHERE id = :id')->execute([':id' => $postId]);

    $pdo->commit();

    // If local image, unlink it safely
    if (!empty($post['image']) && !preg_match('#^https?://#i', $post['image'])) {
        $imgPath = UPLOAD_DIR . $post['image'];
        if (file_exists($imgPath) && is_file($imgPath)) {
            @unlink($imgPath);
        }
    }

    set_flash('Article deleted successfully.', 'success');
    header('Location: ' . BASE_URL);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Delete Post Error: ' . $e->getMessage());
    set_flash('An error occurred while deleting the article.', 'error');
    header('Location: ' . BASE_URL . 'posts/view.php?id=' . $postId);
    exit;
}
