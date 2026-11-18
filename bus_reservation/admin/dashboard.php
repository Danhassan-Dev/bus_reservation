<?php
// admin/dashboard.php - Overview statistics and recent reservations.
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin('admin');
$pdo = getConnection();

$count = fn(string $sql) => (int)$pdo->query($sql)->fetchColumn();
$passengers = $count("SELECT COUNT(*) FROM users WHERE role = 'passenger'");
$buses      = $count("SELECT COUNT(*) FROM buses");
$trips      = $count("SELECT COUNT(*) FROM trips");
$bookings   = $count("SELECT COUNT(*) FROM bookings");
$upcoming   = $count("SELECT COUNT(*) FROM trips WHERE status = 'scheduled' AND departure_time > NOW()");
$unread     = $count("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0");
$revenue    = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = 'paid'")->fetchColumn();

$recent = $pdo->query(
    "SELECT b.id, b.booking_reference, b.passenger_name, b.amount, b.status, b.booked_at, lo.name AS origin, ld.name AS destination
     FROM bookings b JOIN trips t ON t.id = b.trip_id JOIN routes r ON r.id = t.route_id
     JOIN locations lo ON lo.id = r.origin_id JOIN locations ld ON ld.id = r.destination_id
     ORDER BY b.booked_at DESC, b.id DESC LIMIT 8"
)->fetchAll();

$pageTitle = 'Admin Dashboard'; $layout = 'admin';
require __DIR__ . '/../includes/header.php';
?>
<h1>Admin dashboard</h1>
<p class="muted mb">Welcome, <?= e($_SESSION['name'] ?? 'Admin') ?>. Here is how the business is doing.</p>
<div class="grid grid-4 mb">
  <div class="card stat"><div class="ico">👥</div><div><div class="num"><?= $passengers ?></div><div class="muted">Passengers</div></div></div>
  <div class="card stat orange"><div class="ico">🚌</div><div><div class="num"><?= $buses ?></div><div class="muted">Buses</div></div></div>
  <div class="card stat green"><div class="ico">🗓️</div><div><div class="num"><?= $trips ?></div><div class="muted">Trips (<?= $upcoming ?> upcoming)</div></div></div>
  <div class="card stat purple"><div class="ico">🎫</div><div><div class="num"><?= $bookings ?></div><div class="muted">Reservations</div></div></div>
</div>
<div class="grid grid-2 mb">
  <div class="card stat green"><div class="ico">💰</div><div><div class="num"><?= money($revenue) ?></div><div class="muted">Revenue (paid, simulated)</div></div></div>
  <a class="card stat" href="<?= url('admin/messages.php') ?>" style="color:inherit"><div class="ico">✉️</div><div><div class="num"><?= $unread ?></div><div class="muted">Unread messages</div></div></a>
</div>
<div class="toolbar"><h2>Recent reservations</h2><a class="btn btn-sm" href="<?= url('admin/bookings.php') ?>">View all</a></div>
<div class="table-wrap"><table>
  <thead><tr><th>Reference</th><th>Passenger</th><th>Route</th><th>Amount</th><th>Status</th><th>Booked</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($recent as $r): ?>
    <tr><td><strong><?= e($r['booking_reference']) ?></strong></td><td><?= e($r['passenger_name']) ?></td>
        <td><?= e($r['origin']) ?> → <?= e($r['destination']) ?></td><td><?= money($r['amount']) ?></td><td><?= badge($r['status']) ?></td>
        <td><?= e(formatDateTime($r['booked_at'], 'd M Y H:i')) ?></td>
        <td><a class="btn btn-sm" href="<?= url('passenger/booking_details.php') ?>?id=<?= (int)$r['id'] ?>">View</a></td></tr>
  <?php endforeach; if (!$recent): ?><tr><td colspan="7" class="text-center muted">No reservations yet.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
