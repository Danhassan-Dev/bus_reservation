<?php
// admin/locations.php - Cities / terminals used for departures and destinations.
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin('admin');
$pdo = getConnection();
$self = url('admin/locations.php');

if (isPost()) {
    verifyCsrf();
    $action = post('action'); $id = (int)post('id'); $name = post('name'); $state = post('state');
    try {
        if ($action === 'delete') {
            $pdo->prepare("DELETE FROM locations WHERE id = :id")->execute([':id' => $id]);
            setFlash('success', 'Location deleted.');
        } elseif ($action === 'toggle') {
            $pdo->prepare("UPDATE locations SET is_active = 1 - is_active WHERE id = :id")->execute([':id' => $id]);
            setFlash('success', 'Location updated.');
        } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 100 || mb_strlen($state) > 100) {
            setFlash('error', 'Enter a location name (2 to 100 characters).');
        } elseif ($action === 'add') {
            $pdo->prepare("INSERT INTO locations (name, state) VALUES (:n, :s)")->execute([':n' => $name, ':s' => $state ?: null]);
            setFlash('success', 'Location added.');
        } elseif ($action === 'update') {
            $pdo->prepare("UPDATE locations SET name = :n, state = :s WHERE id = :id")->execute([':n' => $name, ':s' => $state ?: null, ':id' => $id]);
            setFlash('success', 'Location updated.');
        }
    } catch (PDOException $e) {
        setFlash('error', $action === 'delete'
            ? 'This location is used by a route, so it cannot be deleted. Deactivate it instead.'
            : 'That location name already exists.');
    }
    redirect($self);
}

$edit = null;
if (isset($_GET['edit'])) { $st = $pdo->prepare("SELECT * FROM locations WHERE id = :id"); $st->execute([':id' => (int)$_GET['edit']]); $edit = $st->fetch() ?: null; }
$rows = $pdo->query("SELECT * FROM locations ORDER BY name")->fetchAll();

$pageTitle = 'Locations'; $layout = 'admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="toolbar"><h1>Locations</h1></div>
<div class="card mb">
  <h3><?= $edit ? 'Edit location' : 'Add a location' ?></h3>
  <form method="post" novalidate>
    <?= csrfField() ?><input type="hidden" name="action" value="<?= $edit ? 'update' : 'add' ?>"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <div class="form-row">
      <div class="form-group"><label>City / terminal name</label><input type="text" name="name" value="<?= e($edit['name'] ?? '') ?>" required></div>
      <div class="form-group"><label>State / region</label><input type="text" name="state" value="<?= e($edit['state'] ?? '') ?>"></div>
    </div>
    <button class="btn btn-orange" type="submit"><?= $edit ? 'Save changes' : 'Add location' ?></button>
    <?php if ($edit): ?><a class="btn btn-grey" href="<?= $self ?>">Cancel</a><?php endif; ?>
  </form>
</div>
<div class="table-wrap"><table>
  <thead><tr><th>Name</th><th>State</th><th>Status</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr><td><strong><?= e($r['name']) ?></strong></td><td><?= e($r['state']) ?></td><td><?= badge($r['is_active'] ? 'active' : 'suspended') ?></td>
      <td class="actions"><a class="btn btn-sm" href="<?= $self ?>?edit=<?= (int)$r['id'] ?>">Edit</a>
        <form method="post"><?= csrfField() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-grey btn-sm"><?= $r['is_active'] ? 'Deactivate' : 'Activate' ?></button></form>
        <form method="post" data-confirm="Delete this location?"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-danger btn-sm">Delete</button></form></td></tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
