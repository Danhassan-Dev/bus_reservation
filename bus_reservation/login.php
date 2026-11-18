<?php
// login.php - Log in with email and password.
require_once __DIR__ . '/includes/bootstrap.php';

if (isLoggedIn()) { redirect(dashboardFor(currentRole() ?? 'passenger')); }

$errors = [];
$email = '';
$redirectTo = (string)($_GET['redirect'] ?? $_POST['redirect'] ?? '');

if (isPost()) {
    verifyCsrf();
    $email    = strtolower(post('email'));
    $password = (string)($_POST['password'] ?? '');

    // Simple brute-force protection: 5 wrong tries = wait 5 minutes.
    $lockedUntil = $_SESSION['login_locked_until'] ?? 0;
    if ($lockedUntil > time()) {
        $errors[] = 'Too many failed attempts. Please wait a few minutes and try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $errors[] = 'Enter your email and password.';
    } else {
        $st = getConnection()->prepare("SELECT id, full_name, password_hash, role, status FROM users WHERE email = :e");
        $st->execute([':e' => $email]);
        $user = $st->fetch();

        // password_verify() compares the typed password with the stored hash.
        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['status'] !== 'active') {
                $errors[] = 'This account has been suspended. Please contact support.';
            } else {
                session_regenerate_id(true);      // new session id after login (stops session fixation)
                unset($_SESSION['login_fails'], $_SESSION['login_locked_until']);
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['name']    = $user['full_name'];
                $_SESSION['role']    = $user['role'];
                setFlash('success', 'Welcome back, ' . $user['full_name'] . '!');
                redirect(safeRedirectTarget($redirectTo, dashboardFor($user['role'])));
            }
        } else {
            $_SESSION['login_fails'] = ($_SESSION['login_fails'] ?? 0) + 1;
            if ($_SESSION['login_fails'] >= 5) { $_SESSION['login_locked_until'] = time() + 300; $_SESSION['login_fails'] = 0; }
            $errors[] = 'Incorrect email or password.';   // same message either way, so attackers learn nothing
        }
    }
}

$pageTitle = 'Login';
require __DIR__ . '/includes/header.php';
?>
<div class="container">
  <div class="form-card">
    <h2 class="mb">Welcome back</h2>
    <?php showFlash(); foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
    <form method="post" novalidate>
      <?= csrfField() ?>
      <input type="hidden" name="redirect" value="<?= e($redirectTo) ?>">
      <div class="form-group"><label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($email) ?>" required autofocus></div>
      <div class="form-group"><label for="password">Password</label>
        <input type="password" id="password" name="password" required></div>
      <button class="btn btn-orange btn-block" type="submit">Log in</button>
    </form>
    <p class="text-center mt muted">New here? <a href="<?= url('register.php') ?>">Create an account</a></p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
