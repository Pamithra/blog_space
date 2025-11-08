<?php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';
if (!is_logged_in()){ $_SESSION['flash']='Login required'; header('Location: '.BASE_URL.'auth/login.php'); exit; }
if ($_SERVER['REQUEST_METHOD']!=='POST' || !csrf_verify($_POST['_csrf'] ?? '')){ $_SESSION['flash']='Invalid'; header('Location: '.BASE_URL); exit; }
$post_id=(int)($_POST['post_id']??0);
$body=trim($_POST['body']??'');
if ($post_id<=0 || $body===''){ $_SESSION['flash']='Empty'; header('Location: '.BASE_URL.'posts/view.php?id='.$post_id); exit; }
$ins=$pdo->prepare('INSERT INTO comment (post_id,user_id,body) VALUES (:pid,:uid,:b)');
$ins->execute([':pid'=>$post_id,':uid'=>$_SESSION['user']['id'],':b'=>$body]);
$_SESSION['flash']='Comment added';
header('Location: '.BASE_URL.'posts/view.php?id='.$post_id);
exit;
?>