<?php
// includes/profile_form.php - Shared profile logic for passengers and admins.
// Expects $pdo, $selfUrl. Handles: update details, change password.
$errors = [];
$st = $pdo->prepare("SELECT full_name, email, phone FROM users WHERE id = :id");
$st->execute([':id' => currentUserId()]);
$me = $st->fetch();

if (isPost()) {
    verifyCsrf();
    if (post('action') === 'details') {
        $name = post('full_name'); $phone = post('phone');
        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) $errors[] = 'Enter your full name (2 to 100 characters).';
        if (($m = validatePhone($phone)) !== '') $errors[] = $m;
        if (!$errors) {
            $pdo->prepare("UPDATE users SET full_name = :n, phone = :p WHERE id = :id")
                ->execute([':n' => $name, ':p' => $phone, ':id' => currentUserId()]);
            $_SESSION['name'] = $name;
            setFlash('success', 'Profile updated.'); redirect($selfUrl);
        }
        $me['full_name'] = $name; $me['phone'] = $phone;
    } elseif (post('action') === 'password') {
        $cur = (string)($_POST['current_password'] ?? ''); $new = (string)($_POST['password'] ?? ''); $conf = (string)($_POST['confirm_password'] ?? '');
        $st = $pdo->prepare("SELECT password_hash FROM users WHERE id = :id"); $st->execute([':id' => currentUserId()]);
        if (!password_verify($cur, (string)$st->fetchColumn())) $errors[] = 'Your current password is incorrect.';
        if (($m = validatePassword($new)) !== '') $errors[] = $m;
        if ($new !== $conf) $errors[] = 'New passwords do not match.';
        if (!$errors) {
            $pdo->prepare("UPDATE users SET password_hash = :h WHERE id = :id")
                ->execute([':h' => password_hash($new, PASSWORD_DEFAULT), ':id' => currentUserId()]);
            session_regenerate_id(true);
            setFlash('success', 'Password changed.'); redirect($selfUrl);
        }
    }
}
?>
<h1>My profile</h1>
<p class="muted mb">Update your details and password.</p>
<?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
<div class="grid grid-2">
  <div class="card">
    <h3>Personal details</h3>
    <form method="post" novalidate>
      <?= csrfField() ?><input type="hidden" name="action" value="details">
      <div class="form-group"><label>Email (cannot be changed)</label><input type="email" value="<?= e($me['email']) ?>" disabled></div>
      <div class="form-group"><label for="full_name">Full name</label><input type="text" id="full_name" name="full_name" value="<?= e($me['full_name']) ?>" required></div>
      <div class="form-group"><label for="phone">Phone</label><input type="tel" id="phone" name="phone" value="<?= e($me['phone']) ?>" required></div>
      <button class="btn" type="submit">Save changes</button>
    </form>
  </div>
  <div class="card">
    <h3>Change password</h3>
    <form method="post" data-match novalidate>
      <?= csrfField() ?><input type="hidden" name="action" value="password">
      <div class="form-group"><label for="current_password">Current password</label><input type="password" id="current_password" name="current_password" required></div>
      <div class="form-group"><label for="password">New password</label><input type="password" id="password" name="password" required></div>
      <div class="form-group"><label for="confirm_password">Confirm new password</label><input type="password" id="confirm_password" name="confirm_password" required></div>
      <button class="btn" type="submit">Change password</button>
    </form>
  </div>
</div>
