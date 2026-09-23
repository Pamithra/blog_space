<?php
// admin/index.php
// Administrator dashboard with platform metrics and recent content

require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';

// Strict administrator guard
if (!is_admin()) {
    set_flash('Administrator access required.', 'error');
    header('Location: ' . BASE_URL);
    exit;
}

// Fetch aggregate statistics
$usersCount = (int)$pdo->query('SELECT COUNT(*) FROM `user`')->fetchColumn();
$postsCount = (int)$pdo->query('SELECT COUNT(*) FROM blogPost')->fetchColumn();
$commentsCount = (int)$pdo->query('SELECT COUNT(*) FROM comment')->fetchColumn();
$likesCount = (int)$pdo->query('SELECT COUNT(*) FROM post_like')->fetchColumn();
$subscribersCount = (int)$pdo->query('SELECT COUNT(*) FROM newsletter')->fetchColumn();
$totalViews = (int)$pdo->query('SELECT SUM(views) FROM blogPost')->fetchColumn();

// Fetch recent posts
$recentPosts = $pdo->query('
    SELECT p.*, u.username, c.name AS category_name,
    (SELECT COUNT(*) FROM post_like WHERE post_id = p.id) AS likes_count 
    FROM blogPost p 
    JOIN `user` u ON p.user_id = u.id 
    LEFT JOIN category c ON p.category_id = c.id 
    ORDER BY p.created_at DESC 
    LIMIT 6
')->fetchAll();

// Fetch recent users
$recentUsers = $pdo->query('
    SELECT id, username, email, role, created_at 
    FROM `user` 
    ORDER BY created_at DESC 
    LIMIT 5
')->fetchAll();

$customTitle = 'Admin Dashboard';
require_once __DIR__ . '/../inc/header.php';
?>

<div class="article-container" style="max-width:1050px;margin-top:20px;">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px;">
    <div>
      <h1 style="font-size:28px;font-weight:800;letter-spacing:-0.5px;">Platform Analytics &amp; Control</h1>
      <p style="color:var(--text-muted);font-size:14px;">Real-time overview of users, content engagement, and subscriptions.</p>
    </div>
    <a href="<?php echo BASE_URL; ?>posts/new.php" class="btn btn-primary btn-sm">+ New Post</a>
  </div>

  <!-- Metric KPI Cards -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(160px, 1fr));gap:16px;margin-bottom:36px;">
    <div class="card" style="padding:20px;border:1px solid var(--border);border-radius:var(--radius-md);background:var(--card);">
      <div style="font-size:13px;color:var(--text-muted);font-weight:600;">Total Users</div>
      <div style="font-size:28px;font-weight:800;color:var(--primary);margin-top:4px;"><?php echo $usersCount; ?></div>
    </div>
    <div class="card" style="padding:20px;border:1px solid var(--border);border-radius:var(--radius-md);background:var(--card);">
      <div style="font-size:13px;color:var(--text-muted);font-weight:600;">Articles Published</div>
      <div style="font-size:28px;font-weight:800;color:var(--accent);margin-top:4px;"><?php echo $postsCount; ?></div>
    </div>
    <div class="card" style="padding:20px;border:1px solid var(--border);border-radius:var(--radius-md);background:var(--card);">
      <div style="font-size:13px;color:var(--text-muted);font-weight:600;">Total Post Views</div>
      <div style="font-size:28px;font-weight:800;color:var(--text);margin-top:4px;"><?php echo $totalViews; ?></div>
    </div>
    <div class="card" style="padding:20px;border:1px solid var(--border);border-radius:var(--radius-md);background:var(--card);">
      <div style="font-size:13px;color:var(--text-muted);font-weight:600;">Likes Recorded</div>
      <div style="font-size:28px;font-weight:800;color:var(--danger);margin-top:4px;"><?php echo $likesCount; ?></div>
    </div>
    <div class="card" style="padding:20px;border:1px solid var(--border);border-radius:var(--radius-md);background:var(--card);">
      <div style="font-size:13px;color:var(--text-muted);font-weight:600;">Comments</div>
      <div style="font-size:28px;font-weight:800;color:var(--text);margin-top:4px;"><?php echo $commentsCount; ?></div>
    </div>
    <div class="card" style="padding:20px;border:1px solid var(--border);border-radius:var(--radius-md);background:var(--card);">
      <div style="font-size:13px;color:var(--text-muted);font-weight:600;">Newsletter Members</div>
      <div style="font-size:28px;font-weight:800;color:var(--success);margin-top:4px;"><?php echo $subscribersCount; ?></div>
    </div>
  </div>

  <!-- Recent Articles -->
  <div style="margin-bottom:40px;">
    <h2 style="font-size:20px;font-weight:700;margin-bottom:16px;">Recent Articles</h2>
    <div style="overflow-x:auto;">
      <table style="width:100%;border-collapse:collapse;background:var(--card);border:1px solid var(--border);border-radius:var(--radius-md);font-size:14px;">
        <thead>
          <tr style="border-bottom:1px solid var(--border);text-align:left;color:var(--text-muted);background:var(--surface);">
            <th style="padding:12px 16px;">Title</th>
            <th style="padding:12px 16px;">Author</th>
            <th style="padding:12px 16px;">Category</th>
            <th style="padding:12px 16px;">Views</th>
            <th style="padding:12px 16px;">Likes</th>
            <th style="padding:12px 16px;">Date</th>
            <th style="padding:12px 16px;text-align:right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentPosts as $p): ?>
            <tr style="border-bottom:1px solid var(--border);">
              <td style="padding:12px 16px;font-weight:600;">
                <a href="<?php echo BASE_URL; ?>posts/view.php?id=<?php echo $p['id']; ?>" style="color:var(--text);">
                  <?php echo htmlspecialchars(mb_substr($p['title'], 0, 40)); ?><?php echo mb_strlen($p['title']) > 40 ? '...' : ''; ?>
                </a>
              </td>
              <td style="padding:12px 16px;"><?php echo htmlspecialchars($p['username']); ?></td>
              <td style="padding:12px 16px;"><?php echo htmlspecialchars($p['category_name'] ?? 'None'); ?></td>
              <td style="padding:12px 16px;"><?php echo (int)$p['views']; ?></td>
              <td style="padding:12px 16px;"><?php echo (int)$p['likes_count']; ?></td>
              <td style="padding:12px 16px;color:var(--text-muted);font-size:13px;"><?php echo date('M j, Y', strtotime($p['created_at'])); ?></td>
              <td style="padding:12px 16px;text-align:right;">
                <a href="<?php echo BASE_URL; ?>posts/edit.php?id=<?php echo $p['id']; ?>" class="btn btn-secondary btn-sm" style="padding:4px 8px;font-size:12px;">Edit</a>
                <form method="post" action="<?php echo BASE_URL; ?>posts/delete.php" style="display:inline;" onsubmit="return confirm('Permanently delete this article?');">
                  <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">
                  <input type="hidden" name="post_id" value="<?php echo $p['id']; ?>">
                  <button type="submit" class="btn btn-danger btn-sm" style="padding:4px 8px;font-size:12px;">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Recent Registered Users -->
  <div>
    <h2 style="font-size:20px;font-weight:700;margin-bottom:16px;">Recent Users</h2>
    <div style="overflow-x:auto;">
      <table style="width:100%;border-collapse:collapse;background:var(--card);border:1px solid var(--border);border-radius:var(--radius-md);font-size:14px;">
        <thead>
          <tr style="border-bottom:1px solid var(--border);text-align:left;color:var(--text-muted);background:var(--surface);">
            <th style="padding:12px 16px;">ID</th>
            <th style="padding:12px 16px;">Username</th>
            <th style="padding:12px 16px;">Email</th>
            <th style="padding:12px 16px;">Role</th>
            <th style="padding:12px 16px;">Registered</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentUsers as $u): ?>
            <tr style="border-bottom:1px solid var(--border);">
              <td style="padding:12px 16px;color:var(--text-muted);">#<?php echo $u['id']; ?></td>
              <td style="padding:12px 16px;font-weight:600;"><?php echo htmlspecialchars($u['username']); ?></td>
              <td style="padding:12px 16px;color:var(--text-muted);"><?php echo htmlspecialchars($u['email']); ?></td>
              <td style="padding:12px 16px;"><span class="card-badge" style="position:static;"><?php echo htmlspecialchars($u['role']); ?></span></td>
              <td style="padding:12px 16px;color:var(--text-muted);font-size:13px;"><?php echo date('M j, Y', strtotime($u['created_at'])); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>