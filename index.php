<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';

$perPage = 6; $page = max(1,(int)($_GET['page'] ?? 1)); $offset = ($page-1)*$perPage;
$search = trim($_GET['q'] ?? '');

$where=[]; $params=[];
if ($search !== '') { $where[] = '(p.title LIKE :s OR p.content LIKE :s)'; $params[':s'] = '%'.$search.'%'; }
$whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

$totalQ = $pdo->prepare("SELECT COUNT(*) FROM blogPost p $whereSql"); $totalQ->execute($params); $total = (int)$totalQ->fetchColumn();
$stmt = $pdo->prepare("SELECT p.*, u.username FROM blogPost p JOIN user u ON p.user_id=u.id $whereSql ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset");
foreach($params as $k=>$v){}; $stmt->bindValue(':limit',$perPage,PDO::PARAM_INT); $stmt->bindValue(':offset',$offset,PDO::PARAM_INT);
if (!empty($params)) foreach($params as $k=>$v) $stmt->bindValue($k,$v);
$stmt->execute(); $posts = $stmt->fetchAll();
require_once __DIR__ . '/inc/header.php';
?>
<div class="hero"><form method="get"><input name="q" placeholder="Search" value="<?php echo htmlspecialchars($search); ?>"></form></div>
<div class="posts-grid">
<?php foreach ($posts as $p): ?>
  <article class="card">
    <?php if ($p['image']): ?>
      <img src="<?php echo BASE_URL;?>uploads/<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['title']); ?>" class="post-img">
    <?php endif; ?>
    <h3><a href="posts/view.php?id=<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['title']); ?></a></h3>
    <div class="meta">By <?php echo htmlspecialchars($p['username']); ?> • <?php echo date('M j, Y', strtotime($p['created_at'])); ?></div>
    <p class="excerpt"><?php echo htmlspecialchars(mb_substr(strip_tags($p['content']),0,160)); ?>...</p>
    <div style="display:flex;justify-content:space-between;align-items:center">
      <a class="read-more" href="posts/view.php?id=<?php echo $p['id']; ?>">Read</a>
      <?php $cnt = $pdo->prepare('SELECT COUNT(*) FROM post_like WHERE post_id=:pid'); $cnt->execute([':pid'=>$p['id']]); $count = $cnt->fetchColumn(); $liked=false; if (is_logged_in()){ $l = $pdo->prepare('SELECT id FROM post_like WHERE post_id=:pid AND user_id=:uid'); $l->execute([':pid'=>$p['id'],':uid'=>$_SESSION['user']['id']]); $liked = (bool)$l->fetch(); } ?>
      <?php if (isset($count)){} ?>
<button class="like-btn <?php echo $liked ? 'liked' : ''; ?>" 
        data-post-id="<?php echo (int)$p['id']; ?>" 
        data-csrf="<?php echo csrf_token(); ?>" 
        type="button">
  <span class="count"><?php echo (int)$count; ?></span> ♥
</button>

    </div>
  </article>
<?php endforeach; ?>
</div>
<div class="footer">
  <form id="newsletter-form"><input type="hidden" name="_csrf" value="<?php echo csrf_token();?>"><input type="email" name="email" placeholder="Subscribe" required><button>Subscribe</button></form>
</div>
<?php require_once __DIR__ . '/inc/footer.php'; ?>
