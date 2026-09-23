<?php
// auth/login.php
// User login with session regeneration and security protections

require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: ' . BASE_URL);
    exit;
}

$enteredUsername = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? '')) {
        set_flash('Session verification failed. Please try again.', 'error');
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
    }

    $loginInput = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $enteredUsername = $loginInput;

    if (empty($loginInput) || empty($password)) {
        set_flash('Please fill in both fields.', 'error');
    } else {
        $stmt = $pdo->prepare('SELECT * FROM `user` WHERE username = :u OR email = :u LIMIT 1');
        $stmt->execute([':u' => $loginInput]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Prevent session fixation attack
            session_regenerate_id(true);

            $_SESSION['user'] = [
                'id'            => (int)$user['id'],
                'username'      => $user['username'],
                'email'         => $user['email'],
                'role'          => $user['role'],
                'profile_image' => $user['profile_image']
            ];

            set_flash('Welcome back, ' . htmlspecialchars($user['username']) . '!', 'success');
            header('Location: ' . BASE_URL);
            exit;
        } else {
            set_flash('Invalid username/email or password.', 'error');
        }
    }
}

$customTitle = 'Log In';
require_once __DIR__ . '/../inc/header.php';
?>

<div class="article-container" style="max-width:440px;margin-top:30px;">
  <div style="text-align:center;margin-bottom:28px;">
    <h1 style="font-size:30px;font-weight:800;letter-spacing:-0.5px;">Welcome Back</h1>
    <p style="color:var(--text-muted);font-size:15px;">Log in to write, like, and comment on articles.</p>
  </div>

  <form method="post" class="form-card" style="padding:28px;">
    <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">

    <div class="form-group">
      <label class="form-label">Username or Email</label>
      <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($enteredUsername); ?>" placeholder="your_username or email" required autofocus>
    </div>

    <div class="form-group">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" placeholder="••••••••" required>
    </div>

    <button type="submit" class="btn btn-primary" style="width:100%;margin-top:10px;padding:12px;">Log In</button>

    <div style="text-align:center;margin-top:20px;font-size:14px;color:var(--text-muted);">
      Don't have an account? <a href="<?php echo BASE_URL; ?>auth/register.php" style="font-weight:700;">Create an account</a>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>