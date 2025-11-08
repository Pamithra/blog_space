<?php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';
if (!is_admin()){ $_SESSION['flash']='Admin required'; header('Location: '.BASE_URL); exit; }
$usersCount = $pdo->query('SELECT COUNT(*) FROM user')->fetchColumn();
$postsCount = $pdo->query('SELECT COUNT(*) FROM blogPost')->fetchColumn();
$commentsCount = $pdo->query('SELECT COUNT(*) FROM comment')->fetchColumn();
$likesCount = $pdo->query('SELECT COUNT(*) FROM post_like')->fetchColumn();
require_once __DIR__ . '/../inc/header.php';
?>
<h1>Admin Dashboard</h1>
<div style="display:flex;gap:20px;flex-wrap:wrap">
  <div class="card"><h3>Users</h3><p><?php echo $usersCount;?></p></div>
  <div class="card"><h3>Posts</h3><p><?php echo $postsCount;?></p></div>
  <div class="card"><h3>Comments</h3><p><?php echo $commentsCount;?></p></div>
  <div class="card"><h3>Likes</h3><p><?php echo $likesCount;?></p></div>
</div>
<?php require_once __DIR__ . '/../inc/footer.php'; ?>