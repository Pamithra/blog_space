<?php
// auth/register.php
// User registration with validation, password hashing, and clean UI

require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: ' . BASE_URL);
    exit;
}

$enteredUsername = '';
$enteredEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? '')) {
        set_flash('Session verification failed. Please try again.', 'error');
        header('Location: ' . BASE_URL . 'auth/register.php');
        exit;
    }

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    $enteredUsername = $username;
    $enteredEmail = $email;

    if (empty($username) || empty($email) || empty($password)) {
        set_flash('All fields are required.', 'error');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('Please enter a valid email address.', 'error');
    } elseif (strlen($password) < 6) {
        set_flash('Password must be at least 6 characters long.', 'error');
    } elseif ($password !== $passwordConfirm) {
        set_flash('Passwords do not match.', 'error');
    } else {
        // Check uniqueness
        $stmt = $pdo->prepare('SELECT id FROM `user` WHERE username = :u OR email = :e LIMIT 1');
        $stmt->execute([':u' => $username, ':e' => $email]);
        if ($stmt->fetch()) {
            set_flash('Username or email is already registered.', 'error');
        } else {
            // Hash password securely
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $ins = $pdo->prepare('
                INSERT INTO `user` (username, email, password, role, created_at) 
                VALUES (:u, :e, :p, "user", NOW())
            ');
            $ins->execute([
                ':u' => $username,
                ':e' => $email,
                ':p' => $hashedPassword
            ]);

            set_flash('Account created successfully! You can now log in.', 'success');
            header('Location: ' . BASE_URL . 'auth/login.php');
            exit;
        }
    }
}

$customTitle = 'Create an Account';
require_once __DIR__ . '/../inc/header.php';
?>

<div class="article-container" style="max-width:460px;margin-top:20px;">
  <div style="text-align:center;margin-bottom:28px;">
    <h1 style="font-size:30px;font-weight:800;letter-spacing:-0.5px;">Join BlogSpace</h1>
    <p style="color:var(--text-muted);font-size:15px;">Create your account to start writing and engaging.</p>
  </div>

  <form method="post" class="form-card" style="padding:28px;">
    <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">

    <div class="form-group">
      <label class="form-label">Username</label>
      <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($enteredUsername); ?>" placeholder="e.g. john_developer" required autofocus>
    </div>

    <div class="form-group">
      <label class="form-label">Email Address</label>
      <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($enteredEmail); ?>" placeholder="john@example.com" required>
    </div>

    <div class="form-group">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" placeholder="At least 6 characters" required>
    </div>

    <div class="form-group">
      <label class="form-label">Confirm Password</label>
      <input type="password" name="password_confirm" class="form-control" placeholder="Re-enter password" required>
    </div>

    <button type="submit" class="btn btn-primary" style="width:100%;margin-top:10px;padding:12px;">Create Account</button>

    <div style="text-align:center;margin-top:20px;font-size:14px;color:var(--text-muted);">
      Already have an account? <a href="<?php echo BASE_URL; ?>auth/login.php" style="font-weight:700;">Log in here</a>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>