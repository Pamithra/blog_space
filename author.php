<?php
// author.php
// Public author profile page displaying bio, stats, and published articles

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';

$authorId = (int)($_GET['id'] ?? 0);
if ($authorId <= 0) {
    set_flash('Author not found.', 'error');
    header('Location: ' . BASE_URL);
    exit;
}

// Fetch author info
$stmt = $pdo->prepare('SELECT id, username, profile_image, bio, created_at FROM `user` WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $authorId]);
$author = $stmt->fetch();

if (!$author) {
    set_flash('Author does not exist.', 'error');
    header('Location: ' . BASE_URL);
    exit;
}

// Fetch author stats
$postCountStmt = $pdo->prepare('SELECT COUNT(*) FROM blogPost WHERE user_id = :uid');
$postCountStmt->execute([':uid' => $authorId]);
$totalPosts = (int)$postCountStmt->fetchColumn();

$likesCountStmt = $pdo->prepare('
    SELECT COUNT(*) FROM post_like pl 
    JOIN blogPost bp ON pl.post_id = bp.id 
    WHERE bp.user_id = :uid
');
$likesCountStmt->execute([':uid' => $authorId]);
$totalLikesReceived = (int)$likesCountStmt->fetchColumn();

// Fetch author posts
$postsStmt = $pdo->prepare('
    SELECT p.*, c.name AS category_name,
    (SELECT COUNT(*) FROM post_like WHERE post_id = p.id) AS likes_count 
    FROM blogPost p 
    LEFT JOIN category c ON p.category_id = c.id 
    WHERE p.user_id = :uid 
    ORDER BY p.created_at DESC
');
$postsStmt->execute([':uid' => $authorId]);
$authorPosts = $postsStmt->fetchAll();

$customTitle = $author['username'] . "'s Profile";
require_once __DIR__ . '/inc/header.php';
?>

<div class="article-container" style="max-width:960px;">
  <!-- Author Profile Banner -->
  <div class="author-card-box" style="margin-top:20px;padding:32px;">
    <img src="<?php echo get_avatar_url($author['profile_image']); ?>" alt="<?php echo htmlspecialchars($author['username']); ?>" class="author-card-avatar" style="width:90px;height:90px;">
    
    <div class="author-card-info" style="flex:1;">
      <h1 style="font-size:26px;font-weight:800;"><?php echo htmlspecialchars($author['username']); ?></h1>
      <p style="margin:8px 0 16px;font-size:15px;line-height:1.6;">
        <?php echo !empty($author['bio']) ? htmlspecialchars($author['bio']) : 'Contributing writer on ' . htmlspecialchars(SITE_NAME) . '.'; ?>
      </p>

      <div style="display:flex;align-items:center;gap:24px;font-size:14px;color:var(--text-muted);flex-wrap:wrap;">
        <span>📚 <strong><?php echo $totalPosts; ?></strong> Articles</span>
        <span>❤️ <strong><?php echo $totalLikesReceived; ?></strong> Likes received</span>
        <span>📅 Member since <?php echo date('M Y', strtotime($author['created_at'])); ?></span>
      </div>
    </div>

    <?php if (is_logged_in() && current_user_id() == $author['id']): ?>
      <div>
        <a href="<?php echo BASE_URL; ?>profile_edit.php" class="btn btn-secondary btn-sm">Edit Profile</a>
      </div>
    <?php endif; ?>
  </div>

  <!-- Author's Published Articles -->
  <div style="margin-top:40px;">
    <h2 style="font-size:22px;font-weight:700;margin-bottom:24px;">Articles by <?php echo htmlspecialchars($author['username']); ?> (<?php echo $totalPosts; ?>)</h2>

    <?php if (empty($authorPosts)): ?>
      <div class="empty-state">
        <p>This author hasn't published any articles yet.</p>
      </div>
    <?php else: ?>
      <div class="posts-grid" style="grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));">
        <?php foreach ($authorPosts as $p): ?>
          <?php 
            $readingTime = calculate_reading_time($p['content']); 
            $imageUrl = get_image_url($p['image']);
          ?>
          <article class="blog-card">
            <div class="card-img-wrapper" style="height:170px;">
              <?php if (!empty($imageUrl)): ?>
                <img src="<?php echo $imageUrl; ?>" alt="<?php echo htmlspecialchars($p['title']); ?>" class="card-img" loading="lazy">
              <?php else: ?>
                <div style="width:100%;height:100%;background:linear-gradient(135deg, var(--surface), var(--card));display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:28px;">
                  📰
                </div>
              <?php endif; ?>
              <?php if (!empty($p['category_name'])): ?>
                <span class="card-badge"><?php echo htmlspecialchars($p['category_name']); ?></span>
              <?php endif; ?>
            </div>

            <div class="card-body">
              <h3 class="card-title" style="font-size:17px;">
                <a href="<?php echo BASE_URL; ?>posts/view.php?id=<?php echo $p['id']; ?>">
                  <?php echo htmlspecialchars($p['title']); ?>
                </a>
              </h3>

              <div class="card-meta-row" style="margin-top:auto;">
                <span><?php echo date('M j, Y', strtotime($p['created_at'])); ?></span>
                <div class="card-stats">
                  <span>⏱ <?php echo $readingTime; ?>m</span>
                  <span>❤️ <?php echo (int)$p['likes_count']; ?></span>
                </div>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
