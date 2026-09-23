<?php
// profile_edit.php
// Edit user profile details, bio, and profile avatar

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';

if (!is_logged_in()) {
    set_flash('You must be logged in to edit your profile.', 'error');
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

$uid = current_user_id();

// Fetch current user data
$stmt = $pdo->prepare('SELECT id, username, email, profile_image, bio FROM `user` WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $uid]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: ' . BASE_URL);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? '')) {
        set_flash('Session verification failed. Please try again.', 'error');
        header('Location: ' . BASE_URL . 'profile_edit.php');
        exit;
    }

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    if (empty($username) || empty($email)) {
        set_flash('Username and email are required.', 'error');
        header('Location: ' . BASE_URL . 'profile_edit.php');
        exit;
    }

    // Check unique username and email for other users
    $chk = $pdo->prepare('SELECT id FROM `user` WHERE (username = :u OR email = :e) AND id != :id LIMIT 1');
    $chk->execute([':u' => $username, ':e' => $email, ':id' => $uid]);
    if ($chk->fetch()) {
        set_flash('Username or email is already taken by another account.', 'error');
        header('Location: ' . BASE_URL . 'profile_edit.php');
        exit;
    }

    // Process avatar image upload
    $newAvatar = handle_image_upload('profile_image', 'avatar');
    $avatarToSave = $newAvatar ? $newAvatar : $user['profile_image'];

    // Update user
    $updateStmt = $pdo->prepare('
        UPDATE `user` 
        SET username = :u, email = :e, bio = :b, profile_image = :img 
        WHERE id = :id
    ');
    $updateStmt->execute([
        ':u'   => $username,
        ':e'   => $email,
        ':b'   => $bio,
        ':img' => $avatarToSave,
        ':id'  => $uid
    ]);

    // Update active session
    $_SESSION['user']['username'] = $username;
    $_SESSION['user']['email'] = $email;
    $_SESSION['user']['profile_image'] = $avatarToSave;

    set_flash('Your profile has been updated!', 'success');
    header('Location: ' . BASE_URL . 'profile.php');
    exit;
}

$customTitle = 'Edit Profile';
require_once __DIR__ . '/inc/header.php';
?>

<div class="article-container" style="max-width:620px;">
  <div style="margin-bottom:24px;text-align:center;">
    <h1 style="font-size:28px;font-weight:800;">Edit Profile</h1>
    <p style="color:var(--text-muted);font-size:14px;">Update your public profile details and avatar.</p>
  </div>

  <form method="post" enctype="multipart/form-data" class="form-card">
    <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">

    <div style="text-align:center;margin-bottom:24px;">
      <img src="<?php echo get_avatar_url($user['profile_image']); ?>" alt="Current Avatar" style="width:96px;height:96px;border-radius:50%;object-fit:cover;border:3px solid var(--primary);margin:0 auto 12px;">
      <div>
        <label class="btn btn-secondary btn-sm" style="cursor:pointer;">
          Upload New Avatar
          <input type="file" name="profile_image" accept="image/*" style="display:none;" onchange="this.parentElement.innerText = 'File Selected: ' + this.files[0].name;">
        </label>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Username</label>
      <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
    </div>

    <div class="form-group">
      <label class="form-label">Email Address</label>
      <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
    </div>

    <div class="form-group">
      <label class="form-label">Bio (Brief introduction)</label>
      <textarea name="bio" class="form-control" rows="3" placeholder="Tell readers about yourself, what you write about, etc."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
    </div>

    <div style="display:flex;align-items:center;justify-content:flex-end;gap:12px;margin-top:24px;">
      <a href="<?php echo BASE_URL; ?>profile.php" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary">Save Changes</button>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>