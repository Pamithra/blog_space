<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

/* ✅ Add these lines to generate a CSRF token if it doesn't exist */
if (empty($_SESSION['_csrf'])) {
  $_SESSION['_csrf'] = bin2hex(random_bytes(16));
}
?>
<!doctype html>
<html lang="en">
<head>
<!-- add in inc/header.php inside <head> -->
<style>
/* blog post card image (list view) */
.post-img {
  width: 100%;
  height: 250px;        /* desired fixed height; change if you want */
  object-fit: cover;    /* crop center without stretching */
  display: block;
  border-radius: 6px;
}

/* single post featured image */
.post-detail-img {
  width: 100%;
  height: 420px;
  object-fit: cover;
  display: block;
  border-radius: 6px;
}

/* small avatar on profile */
.profile-avatar {
  width: 140px;
  height: 140px;
  object-fit: cover;
  border-radius: 8px;
}
</style>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo htmlspecialchars(SITE_NAME);?></title>
<link rel="stylesheet" href="<?php echo BASE_URL;?>assets/css/styles.css?v=<?php echo time(); ?>">
</head>
<body>

<!-- ✅ keep BASE_URL script exactly as is -->
<script>var BASE_URL = '<?php echo BASE_URL;?>';</script>

<header class="site-header">
  <div class="container" style="display:flex;justify-content:space-between;align-items:center;">
    <a class="brand" href="<?php echo BASE_URL;?>index.php"><?php echo htmlspecialchars(SITE_NAME);?></a>

    <div class="user-info" style="display:flex;align-items:center;gap:10px;">
      <?php if (is_logged_in()): ?>
        <span style="font-weight:bold;">👋 Welcome, <?php echo htmlspecialchars($_SESSION['user']['username']); ?></span>
        <a href="<?php echo BASE_URL;?>posts/new.php">New</a>
        <a href="<?php echo BASE_URL;?>profile.php">Profile</a>
        <?php if (is_admin()): ?><a href="<?php echo BASE_URL;?>admin/index.php">Admin</a><?php endif; ?>
        <a href="<?php echo BASE_URL;?>auth/logout.php">Logout</a>
      <?php else: ?>
        <a href="<?php echo BASE_URL;?>auth/login.php">Login</a>
        <a href="<?php echo BASE_URL;?>auth/register.php">Register</a>
      <?php endif; ?>
    </div>
  </div>
</header>


<main class="container">
<?php if (!empty($_SESSION['flash'])){
  echo '<div class="flash">'.htmlspecialchars($_SESSION['flash']).'</div>';
  unset($_SESSION['flash']);
} ?>
