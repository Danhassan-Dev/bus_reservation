<?php
// passenger/dashboard.php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin('passenger');
$pdo = getConnection();
$uid = currentUserId();

$st = $pdo->prepare(
    "SELECT COUNT(*) AS total,
            SUM(b.status = 'confirmed' AND t.departure_time > NOW()) AS upcoming,
            SUM(b.status = 'cancelled') AS cancelled,
            COALESCE(SUM(CASE WHEN b.status <> 'cancelled' THEN b.amount END), 0) AS spent
     FROM bookings b JOIN trips t ON t.id = b.trip_id WHERE b.user_id = :u"
);
$st->execute([':u' => $uid]);
$stats = $st->fetch();

$st = $pdo->prepare(
    "SELECT b.id, b.booking_reference, b.status, t.departure_time, lo.name AS origin, ld.name AS destination, s.seat_number
     FROM bookings b JOIN trips t ON t.id = b.trip_id JOIN routes r ON r.id = t.route_id
     JOIN locations lo ON lo.id = r.origin_id JOIN locations ld ON ld.id = r.destination_id JOIN seats s ON s.id = b.seat_id
     WHERE b.user_id = :u ORDER BY b.booked_at DESC LIMIT 5"
);
$st->execute([':u' => $uid]);
$recent = $st->fetchAll();

$pageTitle = 'My Dashboard';
$layout = 'passenger';
require __DIR__ . '/../includes/header.php';
?>
<h1>Hello, <?= e($_SESSION['name'] ?? 'traveller') ?> 👋</h1>
<p class="muted mb">Here is a quick look at your trips.</p>
<div class="grid grid-4 mb">
  <div class="card stat"><div class="ico">🎫</div><div><div class="num"><?= (int)$stats['total'] ?></div><div class="muted">Total bookings</div></div></div>
  <div class="card stat green"><div class="ico">🚌</div><div><div class="num"><?= (int)$stats['upcoming'] ?></div><div class="muted">Upcoming trips</div></div></div>
  <div class="card stat purple"><div class="ico">↩️</div><div><div class="num"><?= (int)$stats['cancelled'] ?></div><div class="muted">Cancelled</div></div></div>
  <div class="card stat orange"><div class="ico">💰</div><div><div class="num" style="font-size:1.3rem"><?= money($stats['spent']) ?></div><div class="muted">Total spent</div></div></div>
</div>
<div class="toolbar"><h2>Recent bookings</h2><a class="btn btn-orange btn-sm" href="<?= url('search.php') ?>">+ Book a trip</a></div>
<div class="table-wrap"><table>
  <thead><tr><th>Reference</th><th>Route</th><th>Departure</th><th>Seat</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($recent as $r): ?>
    <tr><td><strong><?= e($r['booking_reference']) ?></strong></td><td><?= e($r['origin']) ?> → <?= e($r['destination']) ?></td>
        <td><?= e(formatDateTime($r['departure_time'])) ?></td><td><?= e($r['seat_number']) ?></td><td><?= badge($r['status']) ?></td>
        <td><a class="btn btn-sm" href="<?= url('passenger/booking_details.php') ?>?id=<?= (int)$r['id'] ?>">View</a></td></tr>
  <?php endforeach; if (!$recent): ?><tr><td colspan="6" class="text-center muted">You have no bookings yet.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
