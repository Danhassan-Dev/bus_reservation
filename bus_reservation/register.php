<?php
// register.php - Create a passenger account.
require_once __DIR__ . '/includes/bootstrap.php';
if (isLoggedIn()) { redirect(dashboardFor(currentRole() ?? 'passenger')); }

$errors = [];
$old = ['full_name' => '', 'email' => '', 'phone' => ''];

if (isPost()) {
    verifyCsrf();
    $old['full_name'] = post('full_name');
    $old['email']     = strtolower(post('email'));
    $old['phone']     = post('phone');
    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['confirm_password'] ?? '');

    // Server-side validation (JavaScript alone is never enough).
    if (mb_strlen($old['full_name']) < 2 || mb_strlen($old['full_name']) > 100) $errors[] = 'Enter your full name (2 to 100 characters).';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 150) $errors[] = 'Enter a valid email address.';
    if (($m = validatePhone($old['phone'])) !== '') $errors[] = $m;
    if (($m = validatePassword($password)) !== '') $errors[] = $m;
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $pdo = getConnection();
        $st = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :e");
        $st->execute([':e' => $old['email']]);
        if ($st->fetchColumn() > 0) {
            $errors[] = 'An account with that email already exists.';
        } else {
            $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, role) VALUES (:n, :e, :p, :h, 'passenger')")
                ->execute([':n' => $old['full_name'], ':e' => $old['email'], ':p' => $old['phone'],
                           ':h' => password_hash($password, PASSWORD_DEFAULT)]);
            setFlash('success', 'Account created! You can now log in.');
            redirect(url('login.php'));
        }
    }
}

$pageTitle = 'Register';
require __DIR__ . '/includes/header.php';
?>
<div class="container">
  <div class="form-card">
    <h2 class="mb">Create your account</h2>
    <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
    <form method="post" data-match novalidate>
      <?= csrfField() ?>
      <div class="form-group"><label for="full_name">Full name</label>
        <input type="text" id="full_name" name="full_name" value="<?= e($old['full_name']) ?>" required></div>
      <div class="form-group"><label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required></div>
      <div class="form-group"><label for="phone">Phone number</label>
        <input type="tel" id="phone" name="phone" value="<?= e($old['phone']) ?>" required></div>
      <div class="form-row">
        <div class="form-group"><label for="password">Password</label>
          <input type="password" id="password" name="password" required>
          <div class="help">8+ characters, letters and numbers</div></div>
        <div class="form-group"><label for="confirm_password">Confirm password</label>
          <input type="password" id="confirm_password" name="confirm_password" required></div>
      </div>
      <button class="btn btn-orange btn-block" type="submit">Register</button>
    </form>
    <p class="text-center mt muted">Already registered? <a href="<?= url('login.php') ?>">Log in</a></p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
