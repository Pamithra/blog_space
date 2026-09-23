<?php
// inc/header.php
// Main layout header with SEO meta, theme toggler, and responsive navigation

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

// Prepare dynamic page metadata for SEO
$siteTitle = htmlspecialchars(SITE_NAME);
$pageTitle = !empty($customTitle) ? htmlspecialchars($customTitle) . ' | ' . $siteTitle : $siteTitle;
$pageDesc = !empty($customDescription) ? htmlspecialchars($customDescription) : 'Explore insightful articles, tutorials, and discussions on ' . $siteTitle;
$pageImage = !empty($customImage) ? htmlspecialchars($customImage) : BASE_URL . 'assets/default-avatar.svg';

// Retrieve and clear flash notification
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $pageTitle; ?></title>

  <!-- SEO & Social Open Graph Tags -->
  <meta name="description" content="<?php echo $pageDesc; ?>">
  <meta property="og:title" content="<?php echo $pageTitle; ?>">
  <meta property="og:description" content="<?php echo $pageDesc; ?>">
  <meta property="og:image" content="<?php echo $pageImage; ?>">
  <meta property="og:type" content="website">
  <meta name="twitter:card" content="summary_large_image">

  <!-- Stylesheets -->
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/styles.css'); ?>">

  <!-- Client-side Config Global -->
  <script>
    var BASE_URL = '<?php echo BASE_URL; ?>';
  </script>
</head>
<body>

<!-- Header Navigation -->
<header class="site-header">
  <div class="container header-container">
    <a href="<?php echo BASE_URL; ?>index.php" class="brand-logo">
      <span class="brand-badge">B</span>
      <span><?php echo htmlspecialchars(SITE_NAME); ?></span>
    </a>

    <!-- Desktop Navigation -->
    <nav class="nav-desktop">
      <a href="<?php echo BASE_URL; ?>index.php" class="nav-link">Home</a>
      
      <?php if (is_logged_in()): ?>
        <a href="<?php echo BASE_URL; ?>posts/new.php" class="btn btn-primary btn-sm">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
          Write Post
        </a>
        <a href="<?php echo BASE_URL; ?>profile.php" class="nav-link">
          <img src="<?php echo get_avatar_url($_SESSION['user']['profile_image'] ?? null); ?>" alt="Avatar" class="nav-avatar">
          <span><?php echo htmlspecialchars($_SESSION['user']['username']); ?></span>
        </a>
        <?php if (is_admin()): ?>
          <a href="<?php echo BASE_URL; ?>admin/index.php" class="nav-link">Admin</a>
        <?php endif; ?>
        <a href="<?php echo BASE_URL; ?>auth/logout.php" class="nav-link">Logout</a>
      <?php else: ?>
        <a href="<?php echo BASE_URL; ?>auth/login.php" class="nav-link">Log In</a>
        <a href="<?php echo BASE_URL; ?>auth/register.php" class="btn btn-primary btn-sm">Register</a>
      <?php endif; ?>
    </nav>

    <!-- Header Actions (Theme Toggle & Mobile Menu) -->
    <div style="display:flex;align-items:center;gap:10px;">
      <button class="btn-theme-toggle" type="button" aria-label="Toggle theme">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
      </button>
      <button class="mobile-menu-btn" type="button" aria-label="Open menu">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
      </button>
    </div>
  </div>
</header>

<!-- Mobile Navigation Drawer -->
<div class="drawer-overlay"></div>
<aside class="mobile-drawer">
  <div class="drawer-header">
    <div class="brand-logo">
      <span class="brand-badge">B</span>
      <span><?php echo htmlspecialchars(SITE_NAME); ?></span>
    </div>
    <button class="drawer-close-btn btn" type="button" style="background:none;border:none;padding:4px;color:var(--text);font-size:20px;">✕</button>
  </div>
  <div class="drawer-links">
    <a href="<?php echo BASE_URL; ?>index.php" class="nav-link">Home</a>
    <?php if (is_logged_in()): ?>
      <a href="<?php echo BASE_URL; ?>posts/new.php" class="nav-link" style="color:var(--primary);font-weight:700;">+ Write Post</a>
      <a href="<?php echo BASE_URL; ?>profile.php" class="nav-link">Profile (<?php echo htmlspecialchars($_SESSION['user']['username']); ?>)</a>
      <?php if (is_admin()): ?>
        <a href="<?php echo BASE_URL; ?>admin/index.php" class="nav-link">Admin Dashboard</a>
      <?php endif; ?>
      <a href="<?php echo BASE_URL; ?>auth/logout.php" class="nav-link">Logout</a>
    <?php else: ?>
      <a href="<?php echo BASE_URL; ?>auth/login.php" class="nav-link">Log In</a>
      <a href="<?php echo BASE_URL; ?>auth/register.php" class="nav-link" style="color:var(--primary);font-weight:700;">Register</a>
    <?php endif; ?>
  </div>
</aside>

<!-- Toast Container & PHP Flash Trigger -->
<div id="toast-container"></div>
<?php if ($flash): ?>
  <div id="php-flash-data" 
       data-message="<?php echo htmlspecialchars($flash['message']); ?>" 
       data-type="<?php echo htmlspecialchars($flash['type']); ?>" 
       style="display:none;"></div>
<?php endif; ?>

<!-- Main Content Area -->
<main class="site-main">
  <div class="container">
