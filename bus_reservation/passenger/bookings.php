<?php
// passenger/bookings.php - Booking history.
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin('passenger');
$pdo = getConnection();

$filter = (string)($_GET['status'] ?? '');
$sql = "SELECT b.id, b.booking_reference, b.status, b.amount, b.passenger_name, t.departure_time,
               lo.name AS origin, ld.name AS destination, s.seat_number
        FROM bookings b JOIN trips t ON t.id = b.trip_id JOIN routes r ON r.id = t.route_id
        JOIN locations lo ON lo.id = r.origin_id JOIN locations ld ON ld.id = r.destination_id
        JOIN seats s ON s.id = b.seat_id
        WHERE b.user_id = :u";
$params = [':u' => currentUserId()];
if (in_array($filter, ['pending', 'confirmed', 'cancelled'], true)) { $sql .= " AND b.status = :s"; $params[':s'] = $filter; }
$sql .= " ORDER BY b.booked_at DESC";
$st = $pdo->prepare($sql); $st->execute($params);
$rows = $st->fetchAll();

$pageTitle = 'My Bookings';
$layout = 'passenger';
require __DIR__ . '/../includes/header.php';
?>
<div class="toolbar"><h1>My bookings</h1><a class="btn btn-orange btn-sm" href="<?= url('search.php') ?>">+ Book a trip</a></div>
<form class="filters" method="get">
  <select name="status" onchange="this.form.submit()">
    <option value="">All statuses</option>
    <?php foreach (['confirmed', 'pending', 'cancelled'] as $s): ?>
      <option value="<?= $s ?>" <?= $filter === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
    <?php endforeach; ?>
  </select>
  <noscript><button class="btn btn-sm">Filter</button></noscript>
</form>
<div class="table-wrap"><table>
  <thead><tr><th>Reference</th><th>Route</th><th>Departure</th><th>Seat</th><th>Amount</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><strong><?= e($r['booking_reference']) ?></strong></td>
      <td><?= e($r['origin']) ?> → <?= e($r['destination']) ?></td>
      <td><?= e(formatDateTime($r['departure_time'])) ?></td>
      <td><?= e($r['seat_number']) ?></td>
      <td><?= money($r['amount']) ?></td>
      <td><?= badge($r['status']) ?></td>
      <td><a class="btn btn-sm" href="<?= url('passenger/booking_details.php') ?>?id=<?= (int)$r['id'] ?>">View</a></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="7" class="text-center muted">No bookings yet.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
