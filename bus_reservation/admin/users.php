<?php
// admin/users.php - Manage passenger accounts.
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin('admin');
$pdo = getConnection();
$self = url('admin/users.php');

if (isPost()) {
    verifyCsrf();
    $id = (int)post('id'); $action = post('action');
    try {
        if ($action === 'toggle') {
            $pdo->prepare("UPDATE users SET status = IF(status = 'active', 'suspended', 'active') WHERE id = :id AND role = 'passenger'")->execute([':id' => $id]);
            setFlash('success', 'Account status changed.');
        } elseif ($action === 'delete') {
            $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'passenger'")->execute([':id' => $id]);
            setFlash('success', 'Passenger deleted.');
        }
    } catch (PDOException $e) {
        setFlash('error', 'This passenger has bookings, so the account cannot be deleted. Suspend it instead.');
    }
    redirect($self);
}

$q = trim((string)($_GET['q'] ?? ''));
$sql = "SELECT u.*, (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id) AS bookings FROM users u WHERE u.role = 'passenger'";
$params = [];
if ($q !== '') { $sql .= " AND (u.full_name LIKE :q OR u.email LIKE :q OR u.phone LIKE :q)"; $params[':q'] = '%' . $q . '%'; }
$st = $pdo->prepare($sql . " ORDER BY u.created_at DESC"); $st->execute($params); $users = $st->fetchAll();

$pageTitle = 'Passengers'; $layout = 'admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="toolbar"><h1>Passengers</h1></div>
<form class="filters" method="get"><input type="text" name="q" placeholder="Search name, email or phone" value="<?= e($q) ?>"><button class="btn btn-sm">Search</button></form>
<div class="table-wrap"><table>
  <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Bookings</th><th>Joined</th><th>Status</th><th>Actions</th></tr></thead><tbody>
  <?php foreach ($users as $u): ?>
    <tr><td><strong><?= e($u['full_name']) ?></strong></td><td><?= e($u['email']) ?></td><td><?= e($u['phone']) ?></td><td><?= (int)$u['bookings'] ?></td>
      <td><?= e(formatDateTime($u['created_at'], 'd M Y')) ?></td><td><?= badge($u['status']) ?></td>
      <td class="actions">
        <form method="post"><?= csrfField() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><button class="btn btn-grey btn-sm"><?= $u['status'] === 'active' ? 'Suspend' : 'Activate' ?></button></form>
        <form method="post" data-confirm="Delete this passenger permanently?"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><button class="btn btn-danger btn-sm">Delete</button></form></td></tr>
  <?php endforeach; if (!$users): ?><tr><td colspan="7" class="text-center muted">No passengers found.</td></tr><?php endif; ?></tbody></table></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
