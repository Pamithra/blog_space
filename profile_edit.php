<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';
if (!is_logged_in()) { header('Location: '.BASE_URL.'auth/login.php'); exit; }
$uid = $_SESSION['user']['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_verify($_POST['_csrf'] ?? '')){ $_SESSION['flash']='Invalid'; header('Location: '.BASE_URL.'profile_edit.php'); exit; }
  $username = trim($_POST['username'] ?? ''); $email = trim($_POST['email'] ?? '');
  if (!$username||!$email){ $_SESSION['flash']='Username and email required'; header('Location: '.BASE_URL.'profile_edit.php'); exit; }
  $stmt = $pdo->prepare('SELECT id FROM user WHERE (username=:u OR email=:e) AND id!=:id LIMIT 1'); $stmt->execute([':u'=>$username,':e'=>$email,':id'=>$uid]);
  if ($stmt->fetch()){ $_SESSION['flash']='Taken'; header('Location: '.BASE_URL.'profile_edit.php'); exit; }
  $img = save_uploaded_image('profile_image','profile');
  if ($img) { $pdo->prepare('UPDATE user SET profile_image = :img WHERE id = :id')->execute([':img'=>$img,':id'=>$uid]); $_SESSION['user']['profile_image']=$img; }
  $pdo->prepare('UPDATE user SET username=:u,email=:e WHERE id=:id')->execute([':u'=>$username,':e'=>$email,':id'=>$uid]);
  $_SESSION['user']['username']=$username; $_SESSION['user']['email']=$email;
  $_SESSION['flash']='Updated'; header('Location: '.BASE_URL.'profile.php'); exit;
}
require_once __DIR__ . '/inc/header.php';
?><h1>Edit profile</h1><form method="post" enctype="multipart/form-data" class="form"><input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>"><label>Username <input name="username" value="<?php echo htmlspecialchars($_SESSION['user']['username']); ?>" required></label><label>Email <input name="email" value="<?php echo htmlspecialchars($_SESSION['user']['email']); ?>" type="email" required></label><label>Profile image <input type="file" name="profile_image" accept="image/*"></label><button type="submit">Save</button></form><?php require_once __DIR__ . '/inc/footer.php'; ?>