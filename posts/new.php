<?php
// posts/new.php
// Create a new blog post with EasyMDE Markdown editor and dual-mode image upload

require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';

// Authentication guard
if (!is_logged_in()) {
    set_flash('You must be logged in to create a post.', 'error');
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

// Fetch categories for dropdown
$categories = $pdo->query('SELECT * FROM category ORDER BY name ASC')->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? '')) {
        set_flash('Session verification failed. Please try again.', 'error');
        header('Location: ' . BASE_URL . 'posts/new.php');
        exit;
    }

    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $rawTags = trim($_POST['tags'] ?? '');

    if (empty($title) || empty($content)) {
        set_flash('Title and content are required.', 'error');
        header('Location: ' . BASE_URL . 'posts/new.php');
        exit;
    }

    // Process image upload (Cloudinary or local storage)
    $uploadedImage = handle_image_upload('image', 'post');

    // Insert blog post
    $stmt = $pdo->prepare('
        INSERT INTO blogPost (user_id, category_id, title, content, image, views, created_at) 
        VALUES (:uid, :cid, :title, :content, :img, 0, NOW())
    ');
    $stmt->execute([
        ':uid'     => current_user_id(),
        ':cid'     => $categoryId,
        ':title'   => $title,
        ':content' => $content,
        ':img'     => $uploadedImage
    ]);
    $postId = (int)$pdo->lastInsertId();

    // Process tags
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

            $linkTag->execute([':pid' => $postId, ':tid' => $tagId]);
        }
    }

    set_flash('Your article has been published successfully!', 'success');
    header('Location: ' . BASE_URL . 'posts/view.php?id=' . $postId);
    exit;
}

$customTitle = 'Write a New Article';
require_once __DIR__ . '/../inc/header.php';
?>

<!-- EasyMDE Stylesheet -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">

<div class="article-container" style="max-width:860px;">
  <div style="margin-bottom:24px;">
    <h1 style="font-size:32px;font-weight:800;letter-spacing:-0.5px;">Create a New Article</h1>
    <p style="color:var(--text-muted);font-size:15px;">Use the rich Markdown editor below to write and format your story.</p>
  </div>

  <form method="post" enctype="multipart/form-data" class="form-card" style="max-width:100%;padding:28px;">
    <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">

    <div class="form-group">
      <label class="form-label">Article Title</label>
      <input type="text" name="title" class="form-control" placeholder="e.g. Modern Web Development with PHP & MySQL" required autofocus>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
      <div class="form-group">
        <label class="form-label">Category</label>
        <select name="category_id" class="form-control">
          <option value="">-- Select Category --</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Tags (comma separated)</label>
        <input type="text" name="tags" class="form-control" placeholder="php, web, tutorial">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Featured Cover Image (Optional)</label>
      <input type="file" name="image" class="form-control" accept="image/*">
      <small style="color:var(--text-muted);display:block;margin-top:4px;">Supports PNG, JPG, WebP up to 5MB.</small>
    </div>

    <div class="form-group">
      <label class="form-label">Content (Markdown Supported)</label>
      <textarea id="markdown-editor" name="content" rows="12"></textarea>
    </div>

    <div style="display:flex;align-items:center;justify-content:flex-end;gap:12px;margin-top:24px;">
      <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary">Publish Article</button>
    </div>
  </form>
</div>

<!-- EasyMDE Script Initializer -->
<script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
<script>
  const easyMDE = new EasyMDE({
    element: document.getElementById('markdown-editor'),
    spellChecker: false,
    placeholder: 'Write your article here... You can use **bold**, *italics*, # headings, and ```code blocks```!',
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