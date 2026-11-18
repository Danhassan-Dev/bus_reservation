<?php
// admin/trip_seats.php - See available and occupied seats for one trip.
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin('admin');
$pdo = getConnection();
$id = (int)($_GET['id'] ?? 0);

$st = $pdo->prepare("SELECT t.*, lo.name AS origin, ld.name AS destination, b.bus_number FROM trips t
    JOIN routes r ON r.id = t.route_id JOIN locations lo ON lo.id = r.origin_id JOIN locations ld ON ld.id = r.destination_id
    JOIN buses b ON b.id = t.bus_id WHERE t.id = :id");
$st->execute([':id' => $id]); $trip = $st->fetch();
if (!$trip) { setFlash('error', 'Trip not found.'); redirect(url('admin/trips.php')); }

$st = $pdo->prepare("SELECT s.id, s.seat_number, (SELECT COUNT(*) FROM bookings k WHERE k.trip_id = :t AND k.seat_id = s.id AND k.status <> 'cancelled') AS taken
    FROM seats s WHERE s.bus_id = :b ORDER BY CAST(s.seat_number AS UNSIGNED), s.seat_number");
$st->execute([':t' => $id, ':b' => $trip['bus_id']]); $seats = $st->fetchAll();

$st = $pdo->prepare("SELECT b.id, b.booking_reference, b.passenger_name, b.passenger_phone, b.status, s.seat_number
    FROM bookings b JOIN seats s ON s.id = b.seat_id WHERE b.trip_id = :t AND b.status <> 'cancelled' ORDER BY CAST(s.seat_number AS UNSIGNED), s.seat_number");
$st->execute([':t' => $id]); $passengers = $st->fetchAll();

$selectable = false;
$pageTitle = 'Trip Seats'; $layout = 'admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="toolbar"><div><h1><?= e($trip['origin']) ?> → <?= e($trip['destination']) ?></h1>
  <p class="muted"><?= e(formatDateTime($trip['departure_time'])) ?> · Bus <?= e($trip['bus_number']) ?> · <?= count($passengers) ?> of <?= count($seats) ?> seats occupied</p></div>
  <a class="btn btn-grey btn-sm" href="<?= url('admin/trips.php') ?>">← Back to trips</a></div>
<div class="two-col">
  <div><?php require __DIR__ . '/../includes/seat_map.php'; ?></div>
  <div class="table-wrap"><table>
    <thead><tr><th>Seat</th><th>Passenger</th><th>Phone</th><th>Ref</th></tr></thead><tbody>
    <?php foreach ($passengers as $p): ?><tr><td><strong><?= e($p['seat_number']) ?></strong></td><td><?= e($p['passenger_name']) ?></td><td><?= e($p['passenger_phone']) ?></td>
      <td><a href="<?= url('passenger/booking_details.php') ?>?id=<?= (int)$p['id'] ?>"><?= e($p['booking_reference']) ?></a></td></tr>
    <?php endforeach; if (!$passengers): ?><tr><td colspan="4" class="text-center muted">No passengers yet.</td></tr><?php endif; ?></tbody></table></div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
