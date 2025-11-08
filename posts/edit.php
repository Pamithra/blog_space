<?php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';

if (!is_logged_in()) { $_SESSION['flash']='Login required'; header('Location: '.BASE_URL.'auth/login.php'); exit; }

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0){ $_SESSION['flash']='Invalid post'; header('Location: '.BASE_URL); exit; }

// load post
$stmt = $pdo->prepare('SELECT * FROM blogPost WHERE id=:id LIMIT 1');
$stmt->execute([':id'=>$id]);
$post = $stmt->fetch();
if (!$post){ $_SESSION['flash']='Not found'; header('Location: '.BASE_URL); exit; }

// check ownership
if ($post['user_id'] != ($_SESSION['user']['id'] ?? 0) && !is_admin()) {
  $_SESSION['flash']='Permission denied';
  header('Location: '.BASE_URL.'posts/view.php?id='.$id);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_verify($_POST['_csrf'] ?? '')) {
    $_SESSION['flash']='Invalid CSRF';
    header('Location: '.BASE_URL.'posts/edit.php?id='.$id);
    exit;
  }
  $title = trim($_POST['title'] ?? '');
  $content = trim($_POST['content'] ?? '');
  if ($title === '' || $content === '') {
    $_SESSION['flash']='Title and content required';
    header('Location: '.BASE_URL.'posts/edit.php?id='.$id);
    exit;
  }
  // image upload optional
  if (!empty($_FILES['image']['name'])) {
    $img = handle_upload($_FILES['image'], 'post'); // uses helpers' upload handling
    if ($img) {
      // remove old image?
      $pdo->prepare('UPDATE blogPost SET title=:t, content=:c, image=:img WHERE id=:id')
        ->execute([':t'=>$title, ':c'=>$content, ':img'=>$img, ':id'=>$id]);
    } else {
      $pdo->prepare('UPDATE blogPost SET title=:t, content=:c WHERE id=:id')
        ->execute([':t'=>$title, ':c'=>$content, ':id'=>$id]);
    }
  } else {
    $pdo->prepare('UPDATE blogPost SET title=:t, content=:c WHERE id=:id')
      ->execute([':t'=>$title, ':c'=>$content, ':id'=>$id]);
  }
  $_SESSION['flash']='Post updated';
  header('Location: '.BASE_URL.'posts/view.php?id='.$id);
  exit;
}

require_once __DIR__ . '/../inc/header.php';
?>
<h1>Edit post</h1>
<form method="post" enctype="multipart/form-data" class="form">
  <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">
  <label>Title
    <input type="text" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
  </label>
  <label>Content
    <textarea name="content" rows="10" required><?php echo htmlspecialchars($post['content']); ?></textarea>
  </label>
  <label>Replace image
    <input type="file" name="image" accept="image/*">
  </label>
  <button type="submit">Save</button>
</form>
<?php require_once __DIR__ . '/../inc/footer.php'; ?>
