<?php
// profile.php
// Private profile management and user's post dashboard

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';

if (!is_logged_in()) {
    set_flash('You must be logged in to view your profile.', 'error');
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

$uid = current_user_id();

// Fetch current user details
$stmt = $pdo->prepare('SELECT id, username, email, profile_image, bio, role, created_at FROM `user` WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $uid]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: ' . BASE_URL);
    exit;
}

// User posts
$postStmt = $pdo->prepare('
    SELECT p.*, c.name AS category_name,
    (SELECT COUNT(*) FROM post_like WHERE post_id = p.id) AS likes_count 
    FROM blogPost p 
    LEFT JOIN category c ON p.category_id = c.id 
    WHERE p.user_id = :uid 
    ORDER BY p.created_at DESC
');
$postStmt->execute([':uid' => $uid]);
$myPosts = $postStmt->fetchAll();

// Total likes count
$likesCountStmt = $pdo->prepare('
    SELECT COUNT(*) FROM post_like pl 
    JOIN blogPost bp ON pl.post_id = bp.id 
    WHERE bp.user_id = :uid
');
$likesCountStmt->execute([':uid' => $uid]);
$totalLikes = (int)$likesCountStmt->fetchColumn();

$customTitle = 'My Dashboard';
require_once __DIR__ . '/inc/header.php';
?>

<div class="article-container" style="max-width:960px;">
  <!-- Profile Header Card -->
  <div class="author-card-box" style="margin-top:20px;padding:32px;">
    <img src="<?php echo get_avatar_url($user['profile_image']); ?>" alt="Profile" class="author-card-avatar" style="width:84px;height:84px;">
    
    <div class="author-card-info" style="flex:1;">
      <h1 style="font-size:24px;font-weight:800;"><?php echo htmlspecialchars($user['username']); ?></h1>
      <p style="color:var(--text-muted);font-size:14px;"><?php echo htmlspecialchars($user['email']); ?> &bull; <span style="text-transform:capitalize;"><?php echo htmlspecialchars($user['role']); ?></span></p>
      
      <p style="margin:8px 0 16px;font-size:14px;line-height:1.6;">
        <?php echo !empty($user['bio']) ? htmlspecialchars($user['bio']) : 'No bio added yet. Tell other readers about yourself!'; ?>
      </p>

      <div style="display:flex;align-items:center;gap:20px;font-size:13px;color:var(--text-muted);">
        <span><strong><?php echo count($myPosts); ?></strong> Published Articles</span>
        <span><strong><?php echo $totalLikes; ?></strong> Total Likes</span>
        <span>Member since <?php echo date('M j, Y', strtotime($user['created_at'])); ?></span>
      </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:10px;">
      <a href="<?php echo BASE_URL; ?>profile_edit.php" class="btn btn-secondary btn-sm">Edit Profile</a>
      <a href="<?php echo BASE_URL; ?>posts/new.php" class="btn btn-primary btn-sm">+ New Post</a>
    </div>
  </div>

  <!-- Manage User Articles -->
  <div style="margin-top:40px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
      <h2 style="font-size:20px;font-weight:700;">My Articles (<?php echo count($myPosts); ?>)</h2>
      <a href="<?php echo BASE_URL; ?>posts/new.php" class="btn btn-primary btn-sm">Write Article</a>
    </div>

    <?php if (empty($myPosts)): ?>
      <div class="empty-state">
        <p>You haven't written any articles yet.</p>
        <a href="<?php echo BASE_URL; ?>posts/new.php" class="btn btn-primary btn-sm">Write your first post</a>
      </div>
    <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:14px;">
        <?php foreach ($myPosts as $p): ?>
          <div class="card" style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;background:var(--card);border:1px solid var(--border);border-radius:var(--radius-md);flex-wrap:wrap;gap:12px;">
            <div>
              <h3 style="font-size:16px;font-weight:700;margin-bottom:4px;">
                <a href="<?php echo BASE_URL; ?>posts/view.php?id=<?php echo $p['id']; ?>" style="color:var(--text);">
                  <?php echo htmlspecialchars($p['title']); ?>
                </a>
              </h3>
              <div style="font-size:12px;color:var(--text-muted);display:flex;gap:12px;">
                <span>Published <?php echo date('M j, Y', strtotime($p['created_at'])); ?></span>
                <span>Category: <?php echo htmlspecialchars($p['category_name'] ?? 'None'); ?></span>
                <span>👁 <?php echo (int)($p['views'] ?? 0); ?> views</span>
                <span>❤️ <?php echo (int)$p['likes_count']; ?> likes</span>
              </div>
            </div>

            <div style="display:flex;align-items:center;gap:8px;">
              <a href="<?php echo BASE_URL; ?>posts/view.php?id=<?php echo $p['id']; ?>" class="btn btn-secondary btn-sm">View</a>
              <a href="<?php echo BASE_URL; ?>posts/edit.php?id=<?php echo $p['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
              <form method="post" action="<?php echo BASE_URL; ?>posts/delete.php" style="display:inline;" onsubmit="return confirm('Delete this post permanently?');">
                <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="post_id" value="<?php echo $p['id']; ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
