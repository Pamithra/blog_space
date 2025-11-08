<?php
function csrf_token() {
    if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['_csrf'];
}
function csrf_verify($t) { return isset($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $t); }
function is_logged_in() { return !empty($_SESSION['user']); }
function is_admin() { return is_logged_in() && ($_SESSION['user']['role'] ?? '') === 'admin'; }

function resize_image_gd($srcPath, $destPath, $w, $h) {
    if (!extension_loaded('gd')) return false;
    $info = getimagesize($srcPath);
    if (!$info) return false;
    $mime = $info['mime'];
    if ($mime === 'image/jpeg') $src = imagecreatefromjpeg($srcPath);
    elseif ($mime === 'image/png') $src = imagecreatefrompng($srcPath);
    else return false;
    $origW = imagesx($src);
    $origH = imagesy($src);
    $srcRatio = $origW / $origH;
    $dstRatio = $w / $h;
    if ($srcRatio > $dstRatio) {
        $newH = $origH;
        $newW = (int)($origH * $dstRatio);
        $srcX = (int)(($origW - $newW) / 2);
        $srcY = 0;
    } else {
        $newW = $origW;
        $newH = (int)($origW / $dstRatio);
        $srcX = 0;
        $srcY = (int)(($origH - $newH) / 2);
    }
    $dst = imagecreatetruecolor($w, $h);
    if ($mime === 'image/png') {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
        imagefilledrectangle($dst, 0, 0, $w, $h, $transparent);
    }
    imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $w, $h, $newW, $newH);
    if ($mime === 'image/jpeg') imagejpeg($dst, $destPath, 85);
    elseif ($mime === 'image/png') imagepng($dst, $destPath);
    imagedestroy($src); imagedestroy($dst);
    return true;
}
function handle_upload($file, $prefix='post') {
    // Ensure same behavior as save_uploaded_image
    if (empty($file) || $file['error'] === UPLOAD_ERR_NO_FILE) return false;
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > MAX_UPLOAD_SIZE) return false;

    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, ALLOWED_IMAGE_TYPES)) return false;

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $name = $prefix . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = UPLOAD_DIR . $name;

    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

    if (!move_uploaded_file($file['tmp_name'], $dest)) return false;

    // Optional resize
    $resizedName = $prefix . '_res_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $resizedPath = UPLOAD_DIR . $resizedName;

    if (resize_image_gd($dest, $resizedPath, 800, 450)) {
        @unlink($dest);
        return $resizedName;
    }

    return $name;
}

function save_uploaded_image($file_field, $prefix='img') {
    if (empty($_FILES[$file_field]) || $_FILES[$file_field]['error'] === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$file_field];
    if ($f['error'] !== UPLOAD_ERR_OK) return null;
    if ($f['size'] > MAX_UPLOAD_SIZE) return null;
    $mime = mime_content_type($f['tmp_name']);
    if (!in_array($mime, ALLOWED_IMAGE_TYPES)) return null;
    $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
    $name = $prefix . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = UPLOAD_DIR . $name;
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    if (!move_uploaded_file($f['tmp_name'], $dest)) return null;
    $resizedName = $prefix . '_res_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $resizedPath = UPLOAD_DIR . $resizedName;
    // Resize to 800x450 for consistent post display
    if (resize_image_gd($dest, $resizedPath, 800, 450)) {
        @unlink($dest);
        return $resizedName;
    }
    return $name;
}
?>