<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD']!=='POST'){ echo json_encode(['ok'=>false,'message'=>'Invalid']); exit; }
$email = trim($_POST['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)){ echo json_encode(['ok'=>false,'message'=>'Invalid email']); exit; }
try { $ins = $pdo->prepare('INSERT INTO newsletter (email) VALUES (:e)'); $ins->execute([':e'=>$email]); echo json_encode(['ok'=>true,'message'=>'Subscribed']); }
catch (PDOException $e) { echo json_encode(['ok'=>false,'message'=>'Already subscribed or error']); }
?>