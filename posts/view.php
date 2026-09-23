<?php
// posts/view.php
// Single post view with view tracking, safe Markdown rendering, social sharing, and comments

require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('Invalid post ID.', 'error');
    header('Location: ' . BASE_URL);
    exit;
}

// Fetch post with author and category
$stmt = $pdo->prepare('
    SELECT p.*, u.username, u.profile_image, u.bio, c.name AS category_name 
    FROM blogPost p 
    JOIN user u ON p.user_id = u.id 
    LEFT JOIN category c ON p.category_id = c.id 
    WHERE p.id = :id LIMIT 1
');
$stmt->execute([':id' => $id]);
$post = $stmt->fetch();

if (!$post) {
    set_flash('Article not found.', 'error');
    header('Location: ' . BASE_URL);
    exit;
}

// Increment post view counter
$pdo->prepare('UPDATE blogPost SET views = views + 1 WHERE id = :id')->execute([':id' => $id]);
$post['views'] = (int)($post['views'] ?? 0) + 1;

// Fetch tags
$tagQ = $pdo->prepare('SELECT t.name FROM tag t JOIN post_tag pt ON t.id = pt.tag_id WHERE pt.post_id = :pid ORDER BY t.name ASC');
$tagQ->execute([':pid' => $id]);
$tags = array_column($tagQ->fetchAll(), 'name');

// Fetch comments with author avatars
$comQ = $pdo->prepare('
    SELECT cm.*, u.username, u.profile_image 
    FROM comment cm 
    JOIN user u ON cm.user_id = u.id 
    WHERE cm.post_id = :pid 
    ORDER BY cm.created_at ASC
');
$comQ->execute([':pid' => $id]);
$comments = $comQ->fetchAll();

// Like count and status
$likeCountQ = $pdo->prepare('SELECT COUNT(*) FROM post_like WHERE post_id = :pid');
$likeCountQ->execute([':pid' => $id]);
$likesCount = (int)$likeCountQ->fetchColumn();

$isLiked = false;
if (is_logged_in()) {
    $lk = $pdo->prepare('SELECT id FROM post_like WHERE post_id = :pid AND user_id = :uid LIMIT 1');
    $lk->execute([':pid' => $id, ':uid' => current_user_id()]);
    $isLiked = (bool)$lk->fetch();
}

$readingTime = calculate_reading_time($post['content']);
$featuredImageUrl = get_image_url($post['image']);

// Metadata for OpenGraph sharing
$customTitle = $post['title'];
$customDescription = mb_substr(strip_tags($post['content']), 0, 160) . '...';
$customImage = !empty($featuredImageUrl) ? $featuredImageUrl : null;

require_once __DIR__ . '/../inc/header.php';
?>

<article class="article-container">
  <!-- Article Header -->
  <header class="article-header">
    <?php if (!empty($post['category_name'])): ?>
      <a href="<?php echo BASE_URL; ?>index.php?category=<?php echo (int)$post['category_id']; ?>" class="article-category">
        <?php echo htmlspecialchars($post['category_name']); ?>
      </a>
    <?php endif; ?>

    <h1 class="article-title"><?php echo htmlspecialchars($post['title']); ?></h1>

    <div class="article-meta">
      <div class="article-author-info">
        <img src="<?php echo get_avatar_url($post['profile_image']); ?>" alt="<?php echo htmlspecialchars($post['username']); ?>" class="article-author-avatar">
        <div>
          <a href="<?php echo BASE_URL; ?>author.php?id=<?php echo $post['user_id']; ?>" style="color:var(--text);font-weight:700;">
            <?php echo htmlspecialchars($post['username']); ?>
          </a>
          <div style="font-size:12px;color:var(--text-muted);">
            Published <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
            <?php if (!empty($post['updated_at'])): ?>
              &bull; Updated <?php echo date('M j, Y', strtotime($post['updated_at'])); ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div style="display:flex;align-items:center;gap:14px;">
        <span>⏱ <?php echo $readingTime; ?> min read</span>
        <span>👁 <?php echo $post['views']; ?> views</span>
      </div>
    </div>
  </header>

  <!-- Featured Image -->
  <?php if (!empty($featuredImageUrl)): ?>
    <img src="<?php echo $featuredImageUrl; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="article-featured-img">
  <?php endif; ?>

  <!-- Rendered Safe Markdown Content (XSS-protected) -->
  <div class="post-content">
    <?php echo render_markdown($post['content']); ?>
  </div>

  <!-- Tags -->
  <?php if (!empty($tags)): ?>
    <div class="post-tags-list">
      <?php foreach ($tags as $t): ?>
        <a href="<?php echo BASE_URL; ?>index.php?tag=<?php echo urlencode($t); ?>" class="tag-badge">
          #<?php echo htmlspecialchars($t); ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Actions Bar: Like + Social Sharing + Author Edit/Delete Controls -->
  <div class="article-actions-bar">
    <div style="display:flex;align-items:center;gap:12px;">
      <button type="button" 
              class="like-btn <?php echo $isLiked ? 'liked' : ''; ?>" 
              data-post-id="<?php echo $post['id']; ?>" 
              data-csrf="<?php echo csrf_token(); ?>"
              style="padding:8px 16px;font-size:14px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
        <span class="count"><?php echo $likesCount; ?></span> Likes
      </button>

      <?php if (is_logged_in() && (current_user_id() == $post['user_id'] || is_admin())): ?>
        <a href="<?php echo BASE_URL; ?>posts/edit.php?id=<?php echo $post['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
        
        <form method="post" action="<?php echo BASE_URL; ?>posts/delete.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to permanently delete this article?');">
          <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">
          <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
          <button type="submit" class="btn btn-danger btn-sm">Delete</button>
        </form>
      <?php endif; ?>
    </div>

    <!-- Social Sharing -->
    <div class="social-share-group">
      <span style="font-size:13px;color:var(--text-muted);font-weight:600;">Share:</span>
      <button class="share-btn" data-share="twitter" title="Share on X / Twitter">X</button>
      <button class="share-btn" data-share="linkedin" title="Share on LinkedIn">LinkedIn</button>
      <button class="share-btn" data-share="copy" title="Copy Article URL">🔗 Copy Link</button>
    </div>
  </div>

  <!-- Author Bio Card -->
  <div class="author-card-box">
    <img src="<?php echo get_avatar_url($post['profile_image']); ?>" alt="<?php echo htmlspecialchars($post['username']); ?>" class="author-card-avatar">
    <div class="author-card-info">
      <h4>Written by <a href="<?php echo BASE_URL; ?>author.php?id=<?php echo $post['user_id']; ?>"><?php echo htmlspecialchars($post['username']); ?></a></h4>
      <p><?php echo !empty($post['bio']) ? htmlspecialchars($post['bio']) : 'Contributing author and knowledge sharer on ' . htmlspecialchars(SITE_NAME) . '.'; ?></p>
      <p style="margin-top:8px;">
        <a href="<?php echo BASE_URL; ?>author.php?id=<?php echo $post['user_id']; ?>" style="font-weight:600;font-size:13px;">View all articles by <?php echo htmlspecialchars($post['username']); ?> &rarr;</a>
      </p>
    </div>
  </div>

  <!-- Comments Section -->
  <section class="comments-section" id="comments">
    <h3 class="comments-title">Discussion (<?php echo count($comments); ?>)</h3>

    <div class="comment-list">
      <?php if (empty($comments)): ?>
        <p style="color:var(--text-muted);font-size:15px;margin-bottom:20px;">No comments yet. Be the first to share your thoughts!</p>
      <?php else: ?>
        <?php foreach ($comments as $c): ?>
          <div class="comment-item">
            <div class="comment-header">
              <div style="display:flex;align-items:center;gap:8px;">
                <img src="<?php echo get_avatar_url($c['profile_image']); ?>" alt="" style="width:24px;height:24px;border-radius:50%;object-fit:cover;">
                <span class="comment-author"><?php echo htmlspecialchars($c['username']); ?></span>
              </div>
              <span><?php echo date('M j, Y \a\t H:i', strtotime($c['created_at'])); ?></span>
            </div>
            <div class="comment-body">
              <?php echo nl2br(htmlspecialchars($c['body'])); ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Add Comment Form -->
    <?php if (is_logged_in()): ?>
      <form method="post" action="<?php echo BASE_URL; ?>posts/comment_add.php" class="form-card" style="margin:0;max-width:100%;">
        <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">
        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
        
        <div class="form-group">
          <label class="form-label">Leave a comment</label>
          <textarea name="body" class="form-control" rows="3" placeholder="Share your perspective, question, or feedback..." required></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary btn-sm">Post Comment</button>
      </form>
    <?php else: ?>
      <div style="background:var(--card);padding:20px;border-radius:var(--radius-md);border:1px solid var(--border);text-align:center;">
        <p><a href="<?php echo BASE_URL; ?>auth/login.php" style="font-weight:700;">Log in</a> to join the conversation and leave a comment.</p>
      </div>
    <?php endif; ?>
  </section>
</article>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>