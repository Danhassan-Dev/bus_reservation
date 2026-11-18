<?php
// admin/trips.php - Schedule trips: route + bus + departure time + price.
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin('admin');
$pdo = getConnection();
$self = url('admin/trips.php');

/** Convert "2026-11-08T07:00" from the form into "2026-11-08 07:00:00", or null if invalid. */
function parseLocal(string $v): ?string
{
    $d = DateTime::createFromFormat('Y-m-d\TH:i', $v);
    return ($d && $d->format('Y-m-d\TH:i') === $v) ? $d->format('Y-m-d H:i:s') : null;
}

if (isPost()) {
    verifyCsrf();
    $action = post('action'); $id = (int)post('id');
    try {
        if ($action === 'cancel_trip') {
            // Cancelling a trip cancels all its bookings and refunds (simulated) payments.
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE trips SET status = 'cancelled' WHERE id = :id")->execute([':id' => $id]);
            $pdo->prepare("UPDATE payments p JOIN bookings b ON b.id = p.booking_id SET p.status = 'refunded' WHERE b.trip_id = :id AND p.status = 'paid'")->execute([':id' => $id]);
            $pdo->prepare("UPDATE bookings SET status = 'cancelled', cancelled_at = NOW() WHERE trip_id = :id AND status <> 'cancelled'")->execute([':id' => $id]);
            $pdo->commit();
            setFlash('success', 'Trip cancelled. All its bookings were cancelled and refunded.');
        } elseif ($action === 'delete') {
            $pdo->prepare("DELETE FROM trips WHERE id = :id")->execute([':id' => $id]);
            setFlash('success', 'Trip deleted.');
        } else {
            $routeId = (int)post('route_id'); $busId = (int)post('bus_id');
            $dep = parseLocal(post('departure_time')); $arr = post('arrival_time') === '' ? null : parseLocal(post('arrival_time'));
            $price = post('price'); $status = post('status') ?: 'scheduled';
            $err = '';

            $st = $pdo->prepare("SELECT duration_minutes FROM routes WHERE id = :id"); $st->execute([':id' => $routeId]);
            $route = $st->fetch();
            $st = $pdo->prepare("SELECT status FROM buses WHERE id = :id"); $st->execute([':id' => $busId]);
            $bus = $st->fetch();

            if (!$route) $err = 'Choose a valid route.';
            elseif (!$bus || $bus['status'] !== 'active') $err = 'Choose an active bus.';
            elseif (!$dep) $err = 'Enter a valid departure date and time.';
            elseif (!is_numeric($price) || $price < 0 || $price > 9999999) $err = 'Enter a valid ticket price.';
            elseif (!in_array($status, ['scheduled', 'departed', 'completed', 'cancelled'], true)) $err = 'Invalid status.';
            if (!$err && $arr === null) {
                if (!$route['duration_minutes']) $err = 'Enter an arrival time (this route has no standard duration).';
                else $arr = date('Y-m-d H:i:s', strtotime($dep) + $route['duration_minutes'] * 60);
            }
            if (!$err && strtotime($arr) <= strtotime($dep)) $err = 'Arrival must be after departure.';
            if (!$err && $action === 'add' && strtotime($dep) <= time()) $err = 'Departure must be in the future.';

            // A bus cannot be on two trips at overlapping times.
            if (!$err) {
                $st = $pdo->prepare("SELECT COUNT(*) FROM trips WHERE bus_id = :b AND status <> 'cancelled' AND id <> :id AND departure_time < :arr AND arrival_time > :dep");
                $st->execute([':b' => $busId, ':id' => $id, ':arr' => $arr, ':dep' => $dep]);
                if ($st->fetchColumn() > 0) $err = 'That bus already has another trip at an overlapping time.';
            }
            if (!$err && $action === 'update') {
                $st = $pdo->prepare("SELECT bus_id, (SELECT COUNT(*) FROM bookings WHERE trip_id = trips.id AND status <> 'cancelled') AS active_bookings FROM trips WHERE id = :id");
                $st->execute([':id' => $id]); $cur = $st->fetch();
                if ($cur && (int)$cur['bus_id'] !== $busId && $cur['active_bookings'] > 0) $err = 'The bus cannot be changed because passengers have already booked seats.';
            }

            if ($err) { setFlash('error', $err); redirect($self . ($action === 'update' ? '?edit=' . $id : '')); }

            if ($action === 'add') {
                $pdo->prepare("INSERT INTO trips (route_id, bus_id, departure_time, arrival_time, price) VALUES (:r,:b,:d,:a,:p)")
                    ->execute([':r' => $routeId, ':b' => $busId, ':d' => $dep, ':a' => $arr, ':p' => $price]);
                setFlash('success', 'Trip scheduled.');
            } else {
                $pdo->prepare("UPDATE trips SET route_id=:r, bus_id=:b, departure_time=:d, arrival_time=:a, price=:p, status=:s WHERE id=:id")
                    ->execute([':r' => $routeId, ':b' => $busId, ':d' => $dep, ':a' => $arr, ':p' => $price, ':s' => $status, ':id' => $id]);
                setFlash('success', 'Trip updated.');
            }
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        setFlash('error', $action === 'delete' ? 'This trip has bookings, so it cannot be deleted. Cancel it instead.' : 'Could not save the trip (it may duplicate an existing one).');
    }
    redirect($self);
}

$edit = null;
if (isset($_GET['edit'])) { $st = $pdo->prepare("SELECT * FROM trips WHERE id = :id"); $st->execute([':id' => (int)$_GET['edit']]); $edit = $st->fetch() ?: null; }
$routes = $pdo->query("SELECT r.id, lo.name AS o, ld.name AS d FROM routes r JOIN locations lo ON lo.id = r.origin_id JOIN locations ld ON ld.id = r.destination_id ORDER BY lo.name, ld.name")->fetchAll();
$buses = $pdo->query("SELECT id, bus_number, model FROM buses WHERE status = 'active' ORDER BY bus_number")->fetchAll();

$filter = (string)($_GET['when'] ?? 'upcoming');
$where = $filter === 'past' ? "t.departure_time <= NOW()" : ($filter === 'all' ? "1=1" : "t.departure_time > NOW()");
$trips = $pdo->query(
    "SELECT t.*, lo.name AS origin, ld.name AS destination, b.bus_number,
            (SELECT COUNT(*) FROM seats s WHERE s.bus_id = t.bus_id) AS total_seats,
            (SELECT COUNT(*) FROM bookings k WHERE k.trip_id = t.id AND k.status <> 'cancelled') AS booked
     FROM trips t JOIN routes r ON r.id = t.route_id JOIN locations lo ON lo.id = r.origin_id
     JOIN locations ld ON ld.id = r.destination_id JOIN buses b ON b.id = t.bus_id
     WHERE $where ORDER BY t.departure_time " . ($filter === 'past' ? 'DESC' : 'ASC')
)->fetchAll();

$pageTitle = 'Trips'; $layout = 'admin';
require __DIR__ . '/../includes/header.php';
$val = fn($k, $d = '') => e($edit[$k] ?? $d);
$local = fn($k) => $edit ? e(date('Y-m-d\TH:i', strtotime($edit[$k]))) : '';
?>
<div class="toolbar"><h1>Trips</h1></div>
<div class="card mb">
  <h3><?= $edit ? 'Edit trip #' . (int)$edit['id'] : 'Schedule a trip' ?></h3>
  <form method="post" novalidate>
    <?= csrfField() ?><input type="hidden" name="action" value="<?= $edit ? 'update' : 'add' ?>"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <div class="form-row">
      <div class="form-group"><label>Route</label><select name="route_id" required><option value="">Select route</option>
        <?php foreach ($routes as $r): ?><option value="<?= (int)$r['id'] ?>" <?= (int)($edit['route_id'] ?? 0) === (int)$r['id'] ? 'selected' : '' ?>><?= e($r['o']) ?> → <?= e($r['d']) ?></option><?php endforeach; ?></select></div>
      <div class="form-group"><label>Bus</label><select name="bus_id" required><option value="">Select bus</option>
        <?php foreach ($buses as $b): ?><option value="<?= (int)$b['id'] ?>" <?= (int)($edit['bus_id'] ?? 0) === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['bus_number']) ?> (<?= e($b['model']) ?>)</option><?php endforeach; ?></select></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Departure</label><input type="datetime-local" name="departure_time" value="<?= $local('departure_time') ?>" required></div>
      <div class="form-group"><label>Arrival (optional)</label><input type="datetime-local" name="arrival_time" value="<?= $local('arrival_time') ?>"><div class="help">Leave blank to use the route's typical duration.</div></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Ticket price (<?= e(APP_CURRENCY) ?>)</label><input type="number" name="price" min="0" step="0.01" value="<?= $val('price') ?>" required></div>
      <?php if ($edit): ?>
      <div class="form-group"><label>Status</label><select name="status"><?php foreach (['scheduled', 'departed', 'completed', 'cancelled'] as $s): ?><option value="<?= $s ?>" <?= $edit['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
      <?php endif; ?>
    </div>
    <button class="btn btn-orange" type="submit"><?= $edit ? 'Save changes' : 'Schedule trip' ?></button>
    <?php if ($edit): ?><a class="btn btn-grey" href="<?= $self ?>">Cancel</a><?php endif; ?>
  </form>
</div>
<form class="filters" method="get">
  <select name="when" onchange="this.form.submit()"><?php foreach (['upcoming' => 'Upcoming', 'past' => 'Past', 'all' => 'All'] as $k => $l): ?><option value="<?= $k ?>" <?= $filter === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
</form>
<div class="table-wrap"><table>
  <thead><tr><th>#</th><th>Route</th><th>Departure</th><th>Bus</th><th>Price</th><th>Seats</th><th>Status</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($trips as $t): ?>
    <tr><td><?= (int)$t['id'] ?></td><td><strong><?= e($t['origin']) ?> → <?= e($t['destination']) ?></strong></td>
      <td><?= e(formatDateTime($t['departure_time'], 'd M Y · H:i')) ?></td><td><?= e($t['bus_number']) ?></td><td><?= money($t['price']) ?></td>
      <td><?= (int)$t['booked'] ?>/<?= (int)$t['total_seats'] ?></td><td><?= badge($t['status']) ?></td>
      <td class="actions">
        <a class="btn btn-sm btn-green" href="<?= url('admin/trip_seats.php') ?>?id=<?= (int)$t['id'] ?>">Seats</a>
        <a class="btn btn-sm" href="<?= $self ?>?edit=<?= (int)$t['id'] ?>">Edit</a>
        <?php if ($t['status'] === 'scheduled'): ?>
        <form method="post" data-confirm="Cancel this trip? All bookings on it will be cancelled and refunded."><?= csrfField() ?><input type="hidden" name="action" value="cancel_trip"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><button class="btn btn-grey btn-sm">Cancel trip</button></form>
        <?php endif; ?>
        <form method="post" data-confirm="Delete this trip?"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><button class="btn btn-danger btn-sm">Delete</button></form>
      </td></tr>
  <?php endforeach; if (!$trips): ?><tr><td colspan="8" class="text-center muted">No trips found.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
