<?php
// contact.php - Contact form. Messages are saved in the database; admins read them.
require_once __DIR__ . '/includes/bootstrap.php';
$errors = []; $old = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];

if (isPost()) {
    verifyCsrf();
    foreach ($old as $k => $_) { $old[$k] = post($k); }
    if (mb_strlen($old['name']) < 2 || mb_strlen($old['name']) > 100) $errors[] = 'Enter your name.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (mb_strlen($old['subject']) < 3 || mb_strlen($old['subject']) > 150) $errors[] = 'Enter a subject (3 to 150 characters).';
    if (mb_strlen($old['message']) < 10 || mb_strlen($old['message']) > 2000) $errors[] = 'Message must be 10 to 2000 characters.';
    if (!$errors) {
        getConnection()->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (:n,:e,:s,:m)")
            ->execute([':n' => $old['name'], ':e' => $old['email'], ':s' => $old['subject'], ':m' => $old['message']]);
        setFlash('success', 'Thank you! Your message has been sent.');
        redirect(url('contact.php'));
    }
}
$pageTitle = 'Contact';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><div class="container"><h1>Contact us</h1><p>We usually reply within one working day.</p></div></div>
<div class="container">
  <div class="form-card wide">
    <?php showFlash(); foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
    <form method="post" novalidate>
      <?= csrfField() ?>
      <div class="form-row">
        <div class="form-group"><label for="name">Your name</label><input type="text" id="name" name="name" value="<?= e($old['name']) ?>" required></div>
        <div class="form-group"><label for="email">Email</label><input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required></div>
      </div>
      <div class="form-group"><label for="subject">Subject</label><input type="text" id="subject" name="subject" value="<?= e($old['subject']) ?>" required></div>
      <div class="form-group"><label for="message">Message</label><textarea id="message" name="message" rows="5" required><?= e($old['message']) ?></textarea></div>
      <button class="btn btn-orange" type="submit">Send message</button>
    </form>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
