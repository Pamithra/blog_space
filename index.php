<?php
// index.php
// Homepage with search, category filtering, tag filtering, pagination, and post grid

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';

// Pagination setup
$perPage = 6;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

// Filtering parameters
$search = trim($_GET['q'] ?? '');
$categoryId = !empty($_GET['category']) ? (int)$_GET['category'] : 0;
$tagFilter = trim($_GET['tag'] ?? '');
$authorId = !empty($_GET['author']) ? (int)$_GET['author'] : 0;

// Query conditions
$where = [];
$params = [];

if ($search !== '') {
    $where[] = '(p.title LIKE :search OR p.content LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

if ($categoryId > 0) {
    $where[] = 'p.category_id = :cat_id';
    $params[':cat_id'] = $categoryId;
}

if ($authorId > 0) {
    $where[] = 'p.user_id = :author_id';
    $params[':author_id'] = $authorId;
}

$joinSql = 'JOIN user u ON p.user_id = u.id LEFT JOIN category c ON p.category_id = c.id';

if ($tagFilter !== '') {
    $joinSql .= ' JOIN post_tag pt ON p.id = pt.post_id JOIN tag t ON pt.tag_id = t.id';
    $where[] = 't.name = :tag_name';
    $params[':tag_name'] = $tagFilter;
}

$whereSql = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

// Count total matching posts
$countStmt = $pdo->prepare("SELECT COUNT(DISTINCT p.id) FROM blogPost p $joinSql $whereSql");
$countStmt->execute($params);
$totalPosts = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalPosts / $perPage);

// Fetch posts
$query = "SELECT DISTINCT p.*, u.username, u.profile_image, c.name AS category_name,
          (SELECT COUNT(*) FROM post_like WHERE post_id = p.id) AS likes_count
          FROM blogPost p $joinSql $whereSql 
          ORDER BY p.created_at DESC 
          LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

// Check which posts the current logged-in user has liked
$userLikes = [];
if (is_logged_in() && !empty($posts)) {
    $postIds = array_column($posts, 'id');
    $placeholders = implode(',', array_fill(0, count($postIds), '?'));
    $likeCheck = $pdo->prepare("SELECT post_id FROM post_like WHERE user_id = ? AND post_id IN ($placeholders)");
    $likeCheck->execute(array_merge([current_user_id()], $postIds));
    $userLikes = array_flip($likeCheck->fetchAll(PDO::FETCH_COLUMN));
}

// Fetch all categories for pill filter bar
$categories = $pdo->query("SELECT * FROM category ORDER BY name ASC")->fetchAll();

// Custom SEO title for header
$customTitle = $search !== '' ? 'Search: ' . $search : 'Discover Great Stories & Code';
require_once __DIR__ . '/inc/header.php';
?>

<!-- Hero Banner & Search -->
<section class="hero-section">
  <h1 class="hero-title">Thoughts, Stories &amp; Tech Insights</h1>
  <p class="hero-subtitle">A modern space for programmers, writers, and thinkers to share knowledge.</p>

  <form method="get" action="<?php echo BASE_URL; ?>index.php" class="search-box-wrapper">
    <span class="search-icon">🔍</span>
    <input type="text" name="q" class="search-input" placeholder="Search by title, topic, or keyword..." value="<?php echo htmlspecialchars($search); ?>">
    <?php if ($categoryId > 0): ?><input type="hidden" name="category" value="<?php echo $categoryId; ?>"><?php endif; ?>
  </form>

  <!-- Category Pills -->
  <nav class="category-pills" aria-label="Category navigation">
    <a href="<?php echo BASE_URL; ?>index.php<?php echo $search !== '' ? '?q=' . urlencode($search) : ''; ?>" 
       class="pill-item <?php echo ($categoryId === 0 && empty($tagFilter)) ? 'active' : ''; ?>">
       All Topics
    </a>
    <?php foreach ($categories as $cat): ?>
      <a href="<?php echo BASE_URL; ?>index.php?category=<?php echo $cat['id']; ?><?php echo $search !== '' ? '&q=' . urlencode($search) : ''; ?>" 
         class="pill-item <?php echo ($categoryId === (int)$cat['id']) ? 'active' : ''; ?>">
         <?php echo htmlspecialchars($cat['name']); ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <!-- Active Tag / Author notification -->
  <?php if (!empty($tagFilter) || $authorId > 0): ?>
    <div style="margin-top:16px;font-size:14px;color:var(--text-muted);">
      <?php if (!empty($tagFilter)): ?>
        Filtering by tag: <strong style="color:var(--primary);">#<?php echo htmlspecialchars($tagFilter); ?></strong>
      <?php endif; ?>
      <?php if ($authorId > 0): ?>
        Filtering by author ID: <strong style="color:var(--primary);"><?php echo $authorId; ?></strong>
      <?php endif; ?>
      &bull; <a href="<?php echo BASE_URL; ?>index.php" style="text-decoration:underline;">Clear Filter</a>
    </div>
  <?php endif; ?>
</section>

<!-- Blog Posts Grid -->
<?php if (empty($posts)): ?>
  <div class="empty-state">
    <div class="empty-state-icon">📝</div>
    <h3>No articles found</h3>
    <p>We couldn't find any articles matching your search or filters.</p>
    <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-secondary btn-sm">Clear Search</a>
  </div>
<?php else: ?>
  <div class="posts-grid">
    <?php foreach ($posts as $p): ?>
      <?php 
        $readingTime = calculate_reading_time($p['content']); 
        $isLiked = isset($userLikes[$p['id']]);
        $imageUrl = get_image_url($p['image']);
      ?>
      <article class="blog-card">
        <div class="card-img-wrapper">
          <?php if (!empty($imageUrl)): ?>
            <img src="<?php echo $imageUrl; ?>" alt="<?php echo htmlspecialchars($p['title']); ?>" class="card-img" loading="lazy">
          <?php else: ?>
            <div style="width:100%;height:100%;background:linear-gradient(135deg, var(--surface), var(--card));display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:32px;">
              📰
            </div>
          <?php endif; ?>
          <?php if (!empty($p['category_name'])): ?>
            <span class="card-badge"><?php echo htmlspecialchars($p['category_name']); ?></span>
          <?php endif; ?>
        </div>

        <div class="card-body">
          <h2 class="card-title">
            <a href="<?php echo BASE_URL; ?>posts/view.php?id=<?php echo $p['id']; ?>">
              <?php echo htmlspecialchars($p['title']); ?>
            </a>
          </h2>

          <p class="card-excerpt">
            <?php echo htmlspecialchars(mb_substr(strip_tags($p['content']), 0, 150)); ?>...
          </p>

          <div class="card-meta-row">
            <a href="<?php echo BASE_URL; ?>author.php?id=<?php echo $p['user_id']; ?>" class="author-chip">
              <img src="<?php echo get_avatar_url($p['profile_image']); ?>" alt="<?php echo htmlspecialchars($p['username']); ?>">
              <span><?php echo htmlspecialchars($p['username']); ?></span>
            </a>

            <div class="card-stats">
              <span class="stat-item" title="Reading time">⏱ <?php echo $readingTime; ?>m</span>
              <span class="stat-item" title="Views">👁 <?php echo (int)($p['views'] ?? 0); ?></span>
              <button type="button" 
                      class="like-btn <?php echo $isLiked ? 'liked' : ''; ?>" 
                      data-post-id="<?php echo (int)$p['id']; ?>" 
                      data-csrf="<?php echo csrf_token(); ?>"
                      aria-label="Like post">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                <span class="count"><?php echo (int)$p['likes_count']; ?></span>
              </button>
            </div>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <!-- Pagination Controls -->
  <?php if ($totalPages > 1): ?>
    <?php 
      $queryParams = $_GET;
      unset($queryParams['page']);
      $queryString = !empty($queryParams) ? '&' . http_build_query($queryParams) : '';
    ?>
    <nav class="pagination" aria-label="Page navigation">
      <?php if ($page > 1): ?>
        <a href="?page=<?php echo ($page - 1) . $queryString; ?>" class="page-btn">&laquo; Previous</a>
      <?php else: ?>
        <span class="page-btn disabled">&laquo; Previous</span>
      <?php endif; ?>

      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <?php if ($i == $page || ($i >= $page - 2 && $i <= $page + 2) || $i == 1 || $i == $totalPages): ?>
          <a href="?page=<?php echo $i . $queryString; ?>" class="page-btn <?php echo $i == $page ? 'active' : ''; ?>">
            <?php echo $i; ?>
          </a>
        <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
          <span style="color:var(--text-muted);padding:4px;">...</span>
        <?php endif; ?>
      <?php endfor; ?>

      <?php if ($page < $totalPages): ?>
        <a href="?page=<?php echo ($page + 1) . $queryString; ?>" class="page-btn">Next &raquo;</a>
      <?php else: ?>
        <span class="page-btn disabled">Next &raquo;</span>
      <?php endif; ?>
    </nav>
  <?php endif; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
