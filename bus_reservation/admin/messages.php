<?php
// admin/messages.php - Messages sent through the Contact page.
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin('admin');
$pdo = getConnection();
$self = url('admin/messages.php');
if (isPost()) {
    verifyCsrf(); $id = (int)post('id');
    if (post('action') === 'read') $pdo->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = :id")->execute([':id' => $id]);
    if (post('action') === 'delete') { $pdo->prepare("DELETE FROM contact_messages WHERE id = :id")->execute([':id' => $id]); setFlash('success', 'Message deleted.'); }
    redirect($self);
}
$msgs = $pdo->query("SELECT * FROM contact_messages ORDER BY is_read, created_at DESC LIMIT 200")->fetchAll();
$pageTitle = 'Messages'; $layout = 'admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="toolbar"><h1>Messages</h1></div>
<?php foreach ($msgs as $m): ?>
  <div class="card mb" style="<?= $m['is_read'] ? '' : 'border-left:5px solid var(--orange)' ?>">
    <div class="toolbar" style="margin-bottom:6px"><div><strong><?= e($m['subject']) ?></strong><div class="muted" style="font-size:.85rem"><?= e($m['name']) ?> · <a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a> · <?= e(formatDateTime($m['created_at'], 'd M Y H:i')) ?></div></div>
      <div class="actions">
        <?php if (!$m['is_read']): ?><form method="post"><?= csrfField() ?><input type="hidden" name="action" value="read"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button class="btn btn-sm btn-green">Mark read</button></form><?php endif; ?>
        <form method="post" data-confirm="Delete this message?"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form></div></div>
    <p><?= nl2br(e($m['message'])) ?></p>
  </div>
<?php endforeach; if (!$msgs): ?><div class="alert alert-info">No messages yet.</div><?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
