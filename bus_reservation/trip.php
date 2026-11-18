<?php
// trip.php - Trip details and seat selection.
require_once __DIR__ . '/includes/bootstrap.php';
$pdo = getConnection();

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare(
    "SELECT t.*, lo.name AS origin, ld.name AS destination, b.bus_number, b.model, b.bus_type, b.capacity, r.distance_km
     FROM trips t
     JOIN routes r ON r.id = t.route_id
     JOIN locations lo ON lo.id = r.origin_id
     JOIN locations ld ON ld.id = r.destination_id
     JOIN buses b ON b.id = t.bus_id
     WHERE t.id = :id"
);
$st->execute([':id' => $id]);
$trip = $st->fetch();
if (!$trip) { http_response_code(404); setFlash('error', 'Trip not found.'); redirect(url('search.php')); }

// Every seat on the bus, with "taken" = number of active bookings for THIS trip.
$st = $pdo->prepare(
    "SELECT s.id, s.seat_number,
            (SELECT COUNT(*) FROM bookings k WHERE k.trip_id = :t AND k.seat_id = s.id AND k.status <> 'cancelled') AS taken
     FROM seats s WHERE s.bus_id = :b ORDER BY CAST(s.seat_number AS UNSIGNED), s.seat_number"
);
$st->execute([':t' => $id, ':b' => $trip['bus_id']]);
$seats = $st->fetchAll();
$free = count(array_filter($seats, fn($s) => !$s['taken']));
$bookable = $trip['status'] === 'scheduled' && strtotime($trip['departure_time']) > time();

$pageTitle = $trip['origin'] . ' to ' . $trip['destination'];
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><div class="container">
  <h1><?= e($trip['origin']) ?> → <?= e($trip['destination']) ?></h1>
  <p><?= e(formatDateTime($trip['departure_time'])) ?> · <?= e($trip['model'] ?: 'Bus') ?> (<?= e(ucfirst($trip['bus_type'])) ?>)</p>
</div></div>
<div class="container section">
  <?php showFlash(); ?>
  <form method="get" action="<?= url('passenger/book.php') ?>">
    <input type="hidden" name="trip_id" value="<?= (int)$trip['id'] ?>">
    <div class="two-col">
      <div>
        <h2 class="mb">Choose your seat</h2>
        <?php require __DIR__ . '/includes/seat_map.php'; ?>
      </div>
      <div class="card">
        <h3>Trip details</h3>
        <div class="summary-row"><span>Departure</span><strong><?= e(formatDateTime($trip['departure_time'])) ?></strong></div>
        <div class="summary-row"><span>Arrival</span><strong><?= e(formatDateTime($trip['arrival_time'])) ?></strong></div>
        <div class="summary-row"><span>Bus</span><strong><?= e($trip['bus_number']) ?></strong></div>
        <div class="summary-row"><span>Seats available</span><strong><?= $free ?> of <?= count($seats) ?></strong></div>
        <div class="summary-row"><span>Ticket price</span><strong><?= money($trip['price']) ?></strong></div>
        <div class="summary-row"><span>Selected seat</span><strong id="chosen-seat">None</strong></div>
        <?php if ($bookable && $free > 0): ?>
          <button id="continue-btn" class="btn btn-orange btn-block mt" type="submit" disabled>Continue to booking</button>
          <?php if (!isLoggedIn()): ?><p class="help mt">You will be asked to log in or register first.</p><?php endif; ?>
        <?php else: ?>
          <div class="alert alert-error mt">This trip is not available for booking.</div>
        <?php endif; ?>
      </div>
    </div>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
