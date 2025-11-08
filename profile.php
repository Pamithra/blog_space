<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';

if (!is_logged_in()) {
    $_SESSION['flash'] = 'Login required';
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

$uid = $_SESSION['user']['id'];

try {
    $stmt = $pdo->prepare('SELECT id, username, email, profile_image, role, created_at FROM `user` WHERE id=:id LIMIT 1');
    $stmt->execute([':id' => $uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found');
    }
} catch (Exception $e) {
    echo "Error loading profile: " . htmlspecialchars($e->getMessage());
    exit;
}

require_once __DIR__ . '/inc/header.php';
?>

<h1>My Profile</h1>
<div class="card">
  <?php if (!empty($user['profile_image'])): ?>
    <img src="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($user['profile_image']); ?>" class="profile-avatar" alt="Profile picture">
  <?php else: ?>
    <img src="<?php echo BASE_URL . 'assets/default-avatar.png'; ?>" class="profile-avatar" alt="Default profile">
  <?php endif; ?>
  
  <h2><?php echo htmlspecialchars($user['username']); ?></h2>
  <p><?php echo htmlspecialchars($user['email']); ?></p>
  <p>Role: <?php echo htmlspecialchars($user['role']); ?></p>
  <p>Member since: <?php echo date('M j, Y', strtotime($user['created_at'])); ?></p>
  <p><a href="<?php echo BASE_URL; ?>profile_edit.php">Edit profile</a></p>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
