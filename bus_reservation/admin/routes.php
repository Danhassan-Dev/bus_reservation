<?php
// admin/routes.php - A route links an origin location to a destination location.
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin('admin');
$pdo = getConnection();
$self = url('admin/routes.php');

if (isPost()) {
    verifyCsrf();
    $action = post('action'); $id = (int)post('id');
    try {
        if ($action === 'delete') {
            $pdo->prepare("DELETE FROM routes WHERE id = :id")->execute([':id' => $id]);
            setFlash('success', 'Route deleted.');
        } else {
            $o = (int)post('origin_id'); $d = (int)post('destination_id');
            $km = post('distance_km') === '' ? null : (int)post('distance_km');
            $mins = post('duration_minutes') === '' ? null : (int)post('duration_minutes');
            if (!$o || !$d) setFlash('error', 'Choose an origin and a destination.');
            elseif ($o === $d) setFlash('error', 'Origin and destination must be different.');
            elseif (($km !== null && ($km < 1 || $km > 5000)) || ($mins !== null && ($mins < 1 || $mins > 4000))) setFlash('error', 'Distance or duration is out of range.');
            elseif ($action === 'add') {
                $pdo->prepare("INSERT INTO routes (origin_id, destination_id, distance_km, duration_minutes) VALUES (:o,:d,:k,:m)")
                    ->execute([':o' => $o, ':d' => $d, ':k' => $km, ':m' => $mins]);
                setFlash('success', 'Route added.');
            } elseif ($action === 'update') {
                $pdo->prepare("UPDATE routes SET origin_id=:o, destination_id=:d, distance_km=:k, duration_minutes=:m WHERE id=:id")
                    ->execute([':o' => $o, ':d' => $d, ':k' => $km, ':m' => $mins, ':id' => $id]);
                setFlash('success', 'Route updated.');
            }
        }
    } catch (PDOException $e) {
        setFlash('error', $action === 'delete' ? 'This route has trips, so it cannot be deleted.' : 'That route already exists.');
    }
    redirect($self);
}

$edit = null;
if (isset($_GET['edit'])) { $st = $pdo->prepare("SELECT * FROM routes WHERE id = :id"); $st->execute([':id' => (int)$_GET['edit']]); $edit = $st->fetch() ?: null; }
$locs = $pdo->query("SELECT id, name FROM locations ORDER BY name")->fetchAll();
$routes = $pdo->query(
    "SELECT r.*, lo.name AS origin, ld.name AS destination, (SELECT COUNT(*) FROM trips t WHERE t.route_id = r.id) AS trip_count
     FROM routes r JOIN locations lo ON lo.id = r.origin_id JOIN locations ld ON ld.id = r.destination_id ORDER BY lo.name, ld.name"
)->fetchAll();

$pageTitle = 'Routes'; $layout = 'admin';
require __DIR__ . '/../includes/header.php';
$opt = function ($sel) use ($locs) { foreach ($locs as $l) echo '<option value="' . (int)$l['id'] . '"' . ((int)$sel === (int)$l['id'] ? ' selected' : '') . '>' . e($l['name']) . '</option>'; };
?>
<div class="toolbar"><h1>Routes</h1></div>
<div class="card mb">
  <h3><?= $edit ? 'Edit route' : 'Add a route' ?></h3>
  <form method="post" novalidate>
    <?= csrfField() ?><input type="hidden" name="action" value="<?= $edit ? 'update' : 'add' ?>"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <div class="form-row">
      <div class="form-group"><label>Origin</label><select name="origin_id" required><option value="">Select</option><?php $opt($edit['origin_id'] ?? 0); ?></select></div>
      <div class="form-group"><label>Destination</label><select name="destination_id" required><option value="">Select</option><?php $opt($edit['destination_id'] ?? 0); ?></select></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Distance (km)</label><input type="number" name="distance_km" min="1" max="5000" value="<?= e($edit['distance_km'] ?? '') ?>"></div>
      <div class="form-group"><label>Typical duration (minutes)</label><input type="number" name="duration_minutes" min="1" max="4000" value="<?= e($edit['duration_minutes'] ?? '') ?>"></div>
    </div>
    <button class="btn btn-orange" type="submit"><?= $edit ? 'Save changes' : 'Add route' ?></button>
    <?php if ($edit): ?><a class="btn btn-grey" href="<?= $self ?>">Cancel</a><?php endif; ?>
  </form>
</div>
<div class="table-wrap"><table>
  <thead><tr><th>Route</th><th>Distance</th><th>Duration</th><th>Trips</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($routes as $r): ?>
    <tr><td><strong><?= e($r['origin']) ?> → <?= e($r['destination']) ?></strong></td><td><?= $r['distance_km'] ? (int)$r['distance_km'] . ' km' : '-' ?></td>
      <td><?= $r['duration_minutes'] ? floor($r['duration_minutes'] / 60) . 'h ' . ($r['duration_minutes'] % 60) . 'm' : '-' ?></td><td><?= (int)$r['trip_count'] ?></td>
      <td class="actions"><a class="btn btn-sm" href="<?= $self ?>?edit=<?= (int)$r['id'] ?>">Edit</a>
        <form method="post" data-confirm="Delete this route?"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-danger btn-sm">Delete</button></form></td></tr>
  <?php endforeach; if (!$routes): ?><tr><td colspan="5" class="text-center muted">No routes yet.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
