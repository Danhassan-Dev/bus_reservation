<?php
// admin/buses.php - Add, edit, view and delete buses. Seats are generated from the capacity.
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin('admin');
$pdo = getConnection();
$self = url('admin/buses.php');
$types = ['standard', 'luxury', 'executive']; $statuses = ['active', 'maintenance', 'retired'];

if (isPost()) {
    verifyCsrf();
    $action = post('action');
    $id = (int)post('id');
    $num = strtoupper(post('bus_number')); $model = post('model'); $type = post('bus_type');
    $cap = (int)post('capacity'); $status = post('status') ?: 'active';

    if ($action === 'delete') {
        try {
            $pdo->prepare("DELETE FROM buses WHERE id = :id")->execute([':id' => $id]);   // seats are removed automatically (CASCADE)
            setFlash('success', 'Bus deleted.');
        } catch (PDOException $e) {
            setFlash('error', 'This bus has trips, so it cannot be deleted. Set its status to "retired" instead.');
        }
        redirect($self);
    }

    $err = '';
    if (!preg_match('/^[A-Z0-9\- ]{3,20}$/', $num)) $err = 'Bus number must be 3 to 20 letters, numbers or dashes.';
    elseif (mb_strlen($model) > 100) $err = 'Model is too long.';
    elseif (!in_array($type, $types, true) || !in_array($status, $statuses, true)) $err = 'Invalid bus type or status.';
    elseif ($cap < 4 || $cap > 80) $err = 'Capacity must be between 4 and 80 seats.';

    if (!$err) {
        try {
            if ($action === 'add') {
                $pdo->beginTransaction();
                $pdo->prepare("INSERT INTO buses (bus_number, model, bus_type, capacity, status) VALUES (:n,:m,:t,:c,:s)")
                    ->execute([':n' => $num, ':m' => $model, ':t' => $type, ':c' => $cap, ':s' => $status]);
                generateSeats($pdo, (int)$pdo->lastInsertId(), $cap);
                $pdo->commit();
                setFlash('success', "Bus added with $cap seats.");
            } elseif ($action === 'update') {
                $st = $pdo->prepare("SELECT capacity FROM buses WHERE id = :id"); $st->execute([':id' => $id]);
                $oldCap = $st->fetchColumn();
                if ($oldCap === false) throw new RuntimeException('Bus not found.');
                $pdo->beginTransaction();
                if ((int)$oldCap !== $cap) {
                    $st = $pdo->prepare("SELECT COUNT(*) FROM bookings bk JOIN seats s ON s.id = bk.seat_id WHERE s.bus_id = :id");
                    $st->execute([':id' => $id]);
                    if ($st->fetchColumn() > 0) { throw new RuntimeException('Capacity cannot change because this bus already has bookings.'); }
                    $pdo->prepare("DELETE FROM seats WHERE bus_id = :id")->execute([':id' => $id]);
                    generateSeats($pdo, $id, $cap);
                }
                $pdo->prepare("UPDATE buses SET bus_number=:n, model=:m, bus_type=:t, capacity=:c, status=:s WHERE id=:id")
                    ->execute([':n' => $num, ':m' => $model, ':t' => $type, ':c' => $cap, ':s' => $status, ':id' => $id]);
                $pdo->commit();
                setFlash('success', 'Bus updated.');
            }
            redirect($self);
        } catch (PDOException $e) {          // NOTE: must come first, because PDOException is a kind of RuntimeException
            if ($pdo->inTransaction()) $pdo->rollBack();
            $err = str_contains($e->getMessage(), 'uq_buses_number') ? 'A bus with that number already exists.' : 'Database error. Please try again.';
        } catch (RuntimeException $e) {      // our own friendly messages (capacity locked, bus not found...)
            if ($pdo->inTransaction()) $pdo->rollBack();
            $err = $e->getMessage();
        }
    }
    setFlash('error', $err);
    redirect($self . ($action === 'update' ? '?edit=' . $id : ''));
}

$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare("SELECT * FROM buses WHERE id = :id"); $st->execute([':id' => (int)$_GET['edit']]);
    $edit = $st->fetch() ?: null;
}
$buses = $pdo->query(
    "SELECT b.*, (SELECT COUNT(*) FROM trips t WHERE t.bus_id = b.id) AS trip_count FROM buses b ORDER BY b.id DESC"
)->fetchAll();

$pageTitle = 'Buses'; $layout = 'admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="toolbar"><h1>Buses</h1></div>
<div class="card mb">
  <h3><?= $edit ? 'Edit bus' : 'Add a bus' ?></h3>
  <form method="post" novalidate>
    <?= csrfField() ?>
    <input type="hidden" name="action" value="<?= $edit ? 'update' : 'add' ?>"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <div class="form-row">
      <div class="form-group"><label>Bus number / plate</label><input type="text" name="bus_number" value="<?= e($edit['bus_number'] ?? '') ?>" required></div>
      <div class="form-group"><label>Model</label><input type="text" name="model" value="<?= e($edit['model'] ?? '') ?>"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Type</label><select name="bus_type"><?php foreach ($types as $t): ?><option value="<?= $t ?>" <?= ($edit['bus_type'] ?? 'standard') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option><?php endforeach; ?></select></div>
      <div class="form-group"><label>Seat capacity (4 to 80)</label><input type="number" name="capacity" min="4" max="80" value="<?= e($edit['capacity'] ?? 40) ?>" required>
        <div class="help">Seats are created automatically: 4 per row (A B | C D).</div></div>
    </div>
    <div class="form-group"><label>Status</label><select name="status"><?php foreach ($statuses as $s): ?><option value="<?= $s ?>" <?= ($edit['status'] ?? 'active') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
    <button class="btn btn-orange" type="submit"><?= $edit ? 'Save changes' : 'Add bus' ?></button>
    <?php if ($edit): ?><a class="btn btn-grey" href="<?= $self ?>">Cancel</a><?php endif; ?>
  </form>
</div>
<div class="table-wrap"><table>
  <thead><tr><th>Number</th><th>Model</th><th>Type</th><th>Seats</th><th>Trips</th><th>Status</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($buses as $b): ?>
    <tr><td><strong><?= e($b['bus_number']) ?></strong></td><td><?= e($b['model']) ?></td><td><?= e(ucfirst($b['bus_type'])) ?></td>
      <td><?= (int)$b['capacity'] ?></td><td><?= (int)$b['trip_count'] ?></td><td><?= badge($b['status']) ?></td>
      <td class="actions"><a class="btn btn-sm" href="<?= $self ?>?edit=<?= (int)$b['id'] ?>">Edit</a>
        <form method="post" data-confirm="Delete this bus and its seats?"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><button class="btn btn-danger btn-sm">Delete</button></form></td></tr>
  <?php endforeach; if (!$buses): ?><tr><td colspan="7" class="text-center muted">No buses yet.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
