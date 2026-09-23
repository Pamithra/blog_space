<?php
// inc/helpers.php
// Core helper functions for security, Markdown, media handling, and formatting

require_once __DIR__ . '/config.php';

/**
 * Generate or retrieve CSRF token
 */
function csrf_token() {
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['_csrf'];
}

/**
 * Verify CSRF token
 */
function csrf_verify($token) {
    return !empty($_SESSION['_csrf']) && !empty($token) && hash_equals($_SESSION['_csrf'], (string)$token);
}

/**
 * Authentication checks
 */
function is_logged_in() {
    return !empty($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

function is_admin() {
    return is_logged_in() && (($_SESSION['user']['role'] ?? '') === 'admin');
}

function current_user_id() {
    return $_SESSION['user']['id'] ?? 0;
}

/**
 * Calculate reading time in minutes
 */
function calculate_reading_time($content, $wpm = 200) {
    $cleanText = strip_tags($content);
    $wordCount = str_word_count($cleanText);
    $minutes = ceil($wordCount / $wpm);
    return max(1, (int)$minutes);
}

/**
 * Safe Markdown Parser with built-in XSS prevention
 * Sanitizes input first, then transforms Markdown syntax to valid semantic HTML.
 */
function render_markdown($markdown) {
    if (empty($markdown)) return '';

    // Step 1: Escape all raw HTML entities first to neutralize any injected scripts
    $text = htmlspecialchars($markdown, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $text = str_replace(["\r\n", "\r"], "\n", $text);

    // Step 2: Extract code blocks to protect them from inline formatting
    $codeBlocks = [];
    $text = preg_replace_callback('/```([a-zA-Z0-9_-]*)\n([\s\S]*?)```/', function($matches) use (&$codeBlocks) {
        $index = count($codeBlocks);
        $lang = $matches[1] ? ' class="language-' . htmlspecialchars($matches[1]) . '"' : '';
        $codeBlocks[$index] = '<pre><code' . $lang . '>' . trim($matches[2]) . '</code></pre>';
        return "@@CODEBLOCK_{$index}@@";
    }, $text);

    // Inline code `code`
    $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);

    // Headings (# h2, ## h3, ### h4) - keep h1 reserved for page title
    $text = preg_replace('/^### (.*?)$/m', '<h4>$1</h4>', $text);
    $text = preg_replace('/^## (.*?)$/m', '<h3>$1</h3>', $text);
    $text = preg_replace('/^# (.*?)$/m', '<h2>$1</h2>', $text);

    // Blockquotes
    $text = preg_replace('/^\> (.*?)$/m', '<blockquote>$1</blockquote>', $text);

    // Bold & Italics
    $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
    $text = preg_replace('/__(.*?)__/', '<strong>$1</strong>', $text);
    $text = preg_replace('/(?<!\*)\*(?!\*)(.*?)(?<!\*)\*(?!\*)/', '<em>$1</em>', $text);
    $text = preg_replace('/(?<!_)_(?!_)(.*?)(?<!_)_(?!_)/', '<em>$1</em>', $text);

    // Strikethrough
    $text = preg_replace('/~~(.*?)~~/', '<del>$1</del>', $text);

    // Images: ![alt](url)
    $text = preg_replace_callback('/!\[([^\]]*)\]\((https?:\/\/[^\s\)]+)\)/', function($m) {
        return '<img src="' . $m[2] . '" alt="' . $m[1] . '" class="post-content-img" loading="lazy">';
    }, $text);

    // Links: [text](url) - strictly whitelist http/https
    $text = preg_replace_callback('/\[([^\]]+)\]\((https?:\/\/[^\s\)]+)\)/', function($m) {
        return '<a href="' . $m[2] . '" target="_blank" rel="noopener noreferrer" class="post-link">' . $m[1] . '</a>';
    }, $text);

    // Unordered lists (- item or * item)
    $text = preg_replace('/^[-\*]\s+(.*?)$/m', '<li>$1</li>', $text);
    $text = preg_replace('/((?:<li>.*?<\/li>\s*)+)/s', '<ul>$1</ul>', $text);

    // Ordered lists (1. item)
    $text = preg_replace('/^\d+\.\s+(.*?)$/m', '<oli>$1</oli>', $text);
    $text = preg_replace('/((?:<oli>.*?<\/oli>\s*)+)/s', '<ol>$1</ol>', $text);
    $text = str_replace(['<oli>', '</oli>'], ['<li>', '</li>'], $text);

    // Horizontal rule
    $text = preg_replace('/^(?:---|\*\*\*|___)$/m', '<hr>', $text);

    // Paragraphs: Wrap text lines that are not inside tags
    $paragraphs = explode("\n\n", $text);
    $formatted = [];
    foreach ($paragraphs as $p) {
        $p = trim($p);
        if ($p === '') continue;
        // If it starts with block-level element, do not wrap in <p>
        if (preg_match('/^<(h[2-6]|pre|blockquote|ul|ol|hr|div|p)/', $p)) {
            $formatted[] = $p;
        } else {
            $formatted[] = '<p>' . nl2br($p) . '</p>';
        }
    }
    $text = implode("\n", $formatted);

    // Step 3: Re-insert preserved code blocks
    foreach ($codeBlocks as $index => $block) {
        $text = str_replace("@@CODEBLOCK_{$index}@@", $block, $text);
    }

    return $text;
}

/**
 * Get display URL for an image (supports database endpoints, Cloudinary URLs, local uploads, and fallbacks)
 */
function get_image_url($imagePath, $fallback = '') {
    if (empty($imagePath)) return $fallback;
    // Check if image is an external or Cloudinary URL
    if (preg_match('#^https?://#i', $imagePath)) {
        return $imagePath;
    }
    // Check if it is a database-backed image endpoint
    if (strpos($imagePath, 'image.php') !== false) {
        return BASE_URL . ltrim($imagePath, '/');
    }
    return BASE_URL . 'uploads/' . htmlspecialchars($imagePath);
}

/**
 * Get avatar URL with clean fallback to SVG default
 */
function get_avatar_url($profileImage) {
    if (!empty($profileImage)) {
        return get_image_url($profileImage, BASE_URL . 'assets/default-avatar.svg');
    }
    return BASE_URL . 'assets/default-avatar.svg';
}

/**
 * GD Image Resizer Helper
 */
function resize_image_gd($srcPath, $destPath, $maxWidth, $maxHeight) {
    if (!extension_loaded('gd')) return false;
    $info = @getimagesize($srcPath);
    if (!$info) return false;

    $mime = $info['mime'];
    if ($mime === 'image/jpeg') $src = imagecreatefromjpeg($srcPath);
    elseif ($mime === 'image/png') $src = imagecreatefrompng($srcPath);
    elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) $src = imagecreatefromwebp($srcPath);
    else return false;

    $origW = imagesx($src);
    $origH = imagesy($src);
    
    // Calculate new dimensions preserving aspect ratio
    $ratio = min($maxWidth / $origW, $maxHeight / $origH);
    if ($ratio >= 1) {
        // No need to upscale
        imagedestroy($src);
        return false;
    }
    $newW = (int)($origW * $ratio);
    $newH = (int)($origH * $ratio);

    $dst = imagecreatetruecolor($newW, $newH);
    if ($mime === 'image/png' || $mime === 'image/webp') {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
        imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
    }

    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

    if ($mime === 'image/jpeg') imagejpeg($dst, $destPath, 85);
    elseif ($mime === 'image/png') imagepng($dst, $destPath, 8);
    elseif ($mime === 'image/webp' && function_exists('imagewebp')) imagewebp($dst, $destPath, 85);

    imagedestroy($src);
    imagedestroy($dst);
    return true;
}

/**
 * Ensure uploaded_image table exists in the database
 */
function ensure_uploaded_image_table($pdo) {
    static $ensured = false;
    if ($ensured || !$pdo) return;
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS uploaded_image (
                id INT AUTO_INCREMENT PRIMARY KEY,
                mime_type VARCHAR(50) NOT NULL,
                file_data LONGBLOB NOT NULL,
                file_size INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $ensured = true;
    } catch (Exception $e) {
        error_log('Failed to ensure uploaded_image table: ' . $e->getMessage());
    }
}

/**
 * Store uploaded image binary directly into persistent MySQL database
 * Resizes and optimizes using GD to conserve space and bandwidth.
 */
function save_image_to_db($filePath, $mime, $prefix = 'post', $pdo = null) {
    if ($pdo === null) {
        global $pdo;
    }
    if (!$pdo) {
        return null;
    }

    ensure_uploaded_image_table($pdo);

    // Calculate maximum bounds based on image usage type
    $maxW = ($prefix === 'avatar') ? 400 : 1200;
    $maxH = ($prefix === 'avatar') ? 400 : 800;

    $tempOptimized = tempnam(sys_get_temp_dir(), 'opt_img_');
    $sourceToRead = $filePath;
    $savedMime = $mime;

    if (resize_image_gd($filePath, $tempOptimized, $maxW, $maxH)) {
        $sourceToRead = $tempOptimized;
    }

    $binaryData = @file_get_contents($sourceToRead);
    if ($tempOptimized && file_exists($tempOptimized)) {
        @unlink($tempOptimized);
    }

    if ($binaryData === false || strlen($binaryData) === 0) {
        return null;
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO uploaded_image (mime_type, file_data, file_size) VALUES (:mime, :data, :size)');
        $stmt->bindValue(':mime', $savedMime);
        $stmt->bindValue(':data', $binaryData, PDO::PARAM_LOB);
        $stmt->bindValue(':size', strlen($binaryData), PDO::PARAM_INT);
        $stmt->execute();

        $imageId = (int)$pdo->lastInsertId();
        if ($imageId > 0) {
            return 'image.php?id=' . $imageId;
        }
    } catch (Exception $e) {
        error_log('Database image upload error: ' . $e->getMessage());
    }

    return null;
}

/**
 * Upload Image to Cloudinary (for persistent cloud storage on Render)
 */
function upload_to_cloudinary($filePath) {
    if (empty(CLOUDINARY_CLOUD_NAME) || empty(CLOUDINARY_API_KEY) || empty(CLOUDINARY_API_SECRET)) {
        return false;
    }

    $timestamp = time();
    $params = [
        'timestamp' => $timestamp,
        'folder'    => 'blogspace_uploads'
    ];
    ksort($params);
    $signatureStr = '';
    foreach ($params as $key => $val) {
        $signatureStr .= $key . '=' . $val . '&';
    }
    $signatureStr = rtrim($signatureStr, '&') . CLOUDINARY_API_SECRET;
    $signature = sha1($signatureStr);

    $postData = [
        'file'      => new CURLFile($filePath),
        'api_key'   => CLOUDINARY_API_KEY,
        'timestamp' => $timestamp,
        'signature' => $signature,
        'folder'    => 'blogspace_uploads'
    ];

    $ch = curl_init('https://api.cloudinary.com/v1_1/' . CLOUDINARY_CLOUD_NAME . '/image/upload');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        return $data['secure_url'] ?? false;
    }
    return false;
}

/**
 * Robust Image Upload Handler:
 * 1. Uses Cloudinary if configured.
 * 2. Saves to persistent MySQL database (Approach 2) - survives all Render redeploys and restarts.
 * 3. Falls back to local UPLOAD_DIR if database is unavailable.
 */
function handle_image_upload($fileField, $prefix = 'post') {
    if (empty($_FILES[$fileField]) || $_FILES[$fileField]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $f = $_FILES[$fileField];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    if ($f['size'] > MAX_UPLOAD_SIZE) {
        return null;
    }

    $mime = mime_content_type($f['tmp_name']);
    if (!in_array($mime, ALLOWED_IMAGE_TYPES)) {
        return null;
    }

    // 1. Try Cloudinary first if configured
    if (!empty(CLOUDINARY_CLOUD_NAME)) {
        $cloudUrl = upload_to_cloudinary($f['tmp_name']);
        if ($cloudUrl) {
            return $cloudUrl;
        }
    }

    // 2. Save directly to persistent MySQL database (Survives Render container restarts)
    $dbImageUrl = save_image_to_db($f['tmp_name'], $mime, $prefix);
    if ($dbImageUrl) {
        return $dbImageUrl;
    }

    // 3. Fallback to local storage (for environments with persistent disk)
    if (!is_dir(UPLOAD_DIR)) {
        @mkdir(UPLOAD_DIR, 0755, true);
    }

    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    $name = $prefix . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = UPLOAD_DIR . $name;

    if (!move_uploaded_file($f['tmp_name'], $dest)) {
        return null;
    }

    // Resize post images for faster page loading
    $resizedName = $prefix . '_thumb_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $resizedPath = UPLOAD_DIR . $resizedName;
    if (resize_image_gd($dest, $resizedPath, 1200, 800)) {
        @unlink($dest);
        return $resizedName;
    }

    return $name;
}


/**
 * Flash notification helper
 */
function set_flash($message, $type = 'info') {
    $_SESSION['flash'] = [
        'message' => $message,
        'type'    => $type
    ];
}

function get_flash() {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        if (is_string($flash)) {
            return ['message' => $flash, 'type' => 'info'];
        }
        return $flash;
    }
    return null;
}