<?php
// posts/edit.php
// Edit an existing blog post with EasyMDE Markdown editor

require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';

// Authentication guard
if (!is_logged_in()) {
    set_flash('You must be logged in to edit articles.', 'error');
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('Invalid post ID.', 'error');
    header('Location: ' . BASE_URL);
    exit;
}

// Fetch post
$stmt = $pdo->prepare('SELECT * FROM blogPost WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$post = $stmt->fetch();

if (!$post) {
    set_flash('Article not found.', 'error');
    header('Location: ' . BASE_URL);
    exit;
}

// Authorization guard: Ensure user is author or administrator
if ($post['user_id'] != current_user_id() && !is_admin()) {
    set_flash('You do not have permission to edit this article.', 'error');
    header('Location: ' . BASE_URL . 'posts/view.php?id=' . $id);
    exit;
}

// Fetch categories
$categories = $pdo->query('SELECT * FROM category ORDER BY name ASC')->fetchAll();

// Fetch existing tags
$tagQ = $pdo->prepare('SELECT t.name FROM tag t JOIN post_tag pt ON t.id = pt.tag_id WHERE pt.post_id = :pid');
$tagQ->execute([':pid' => $id]);
$currentTags = implode(', ', array_column($tagQ->fetchAll(), 'name'));

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? '')) {
        set_flash('Session verification failed. Please try again.', 'error');
        header('Location: ' . BASE_URL . 'posts/edit.php?id=' . $id);
        exit;
    }

    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $rawTags = trim($_POST['tags'] ?? '');

    if (empty($title) || empty($content)) {
        set_flash('Title and content are required.', 'error');
        header('Location: ' . BASE_URL . 'posts/edit.php?id=' . $id);
        exit;
    }

    // Check if new image was uploaded
    $uploadedImage = handle_image_upload('image', 'post');
    $imageToSave = $uploadedImage ? $uploadedImage : $post['image'];

    // Update post record
    $updateStmt = $pdo->prepare('
        UPDATE blogPost 
        SET category_id = :cid, title = :title, content = :content, image = :img, updated_at = NOW() 
        WHERE id = :id
    ');
    $updateStmt->execute([
        ':cid'     => $categoryId,
        ':title'   => $title,
        ':content' => $content,
        ':img'     => $imageToSave,
        ':id'      => $id
    ]);

    // Update tags: clear existing and re-bind
    $pdo->prepare('DELETE FROM post_tag WHERE post_id = :pid')->execute([':pid' => $id]);

    if (!empty($rawTags)) {
        $tags = array_unique(array_filter(array_map('trim', explode(',', $rawTags))));
        $selTag = $pdo->prepare('SELECT id FROM tag WHERE name = :name LIMIT 1');
        $insTag = $pdo->prepare('INSERT INTO tag (name) VALUES (:name)');
        $linkTag = $pdo->prepare('INSERT IGNORE INTO post_tag (post_id, tag_id) VALUES (:pid, :tid)');

        foreach ($tags as $tagName) {
            $tagName = strtolower(preg_replace('/[^a-zA-Z0-9_\-]/', '', $tagName));
            if ($tagName === '') continue;

            $selTag->execute([':name' => $tagName]);
            $tagRow = $selTag->fetch();

            if ($tagRow) {
                $tagId = $tagRow['id'];
            } else {
                $insTag->execute([':name' => $tagName]);
                $tagId = $pdo->lastInsertId();
            }

            $linkTag->execute([':pid' => $id, ':tid' => $tagId]);
        }
    }

    set_flash('Article updated successfully!', 'success');
    header('Location: ' . BASE_URL . 'posts/view.php?id=' . $id);
    exit;
}

$customTitle = 'Edit: ' . $post['title'];
require_once __DIR__ . '/../inc/header.php';
?>

<!-- EasyMDE Stylesheet -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">

<div class="article-container" style="max-width:860px;">
  <div style="margin-bottom:24px;">
    <h1 style="font-size:32px;font-weight:800;letter-spacing:-0.5px;">Edit Article</h1>
    <p style="color:var(--text-muted);font-size:15px;">Modify your content and preview live changes using the editor.</p>
  </div>

  <form method="post" enctype="multipart/form-data" class="form-card" style="max-width:100%;padding:28px;">
    <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">

    <div class="form-group">
      <label class="form-label">Article Title</label>
      <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($post['title']); ?>" required>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
      <div class="form-group">
        <label class="form-label">Category</label>
        <select name="category_id" class="form-control">
          <option value="">-- Select Category --</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?php echo $cat['id']; ?>" <?php echo ($post['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($cat['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Tags (comma separated)</label>
        <input type="text" name="tags" class="form-control" value="<?php echo htmlspecialchars($currentTags); ?>">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Cover Image</label>
      <?php if (!empty($post['image'])): ?>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
          <img src="<?php echo get_image_url($post['image']); ?>" alt="Current cover" style="width:70px;height:45px;object-fit:cover;border-radius:4px;">
          <span style="font-size:13px;color:var(--text-muted);">Current cover image</span>
        </div>
      <?php endif; ?>
      <input type="file" name="image" class="form-control" accept="image/*">
      <small style="color:var(--text-muted);display:block;margin-top:4px;">Upload a new file to replace the existing cover image.</small>
    </div>

    <div class="form-group">
      <label class="form-label">Content (Markdown Supported)</label>
      <textarea id="markdown-editor" name="content" rows="12"><?php echo htmlspecialchars($post['content']); ?></textarea>
    </div>

    <div style="display:flex;align-items:center;justify-content:flex-end;gap:12px;margin-top:24px;">
      <a href="<?php echo BASE_URL; ?>posts/view.php?id=<?php echo $post['id']; ?>" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary">Save Changes</button>
    </div>
  </form>
</div>

<!-- EasyMDE Script Initializer -->
<script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
<script>
  const easyMDE = new EasyMDE({
    element: document.getElementById('markdown-editor'),
    spellChecker: false,
    status: ['lines', 'words', 'cursor'],
    toolbar: [
      'bold', 'italic', 'heading', '|',
      'quote', 'unordered-list', 'ordered-list', '|',
      'link', 'image', 'code', '|',
      'preview', 'side-by-side', 'fullscreen', '|',
      'guide'
    ]
  });
</script>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
