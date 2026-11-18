<?php
// passenger/book.php - Step 2: confirm details and create the booking.
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin('passenger');          // not logged in? sent to login, then back here
$pdo = getConnection();

$tripId = (int)($_REQUEST['trip_id'] ?? 0);
$seatId = (int)($_REQUEST['seat_id'] ?? 0);

// Load trip + seat and check everything again on the server.
$st = $pdo->prepare(
    "SELECT t.*, lo.name AS origin, ld.name AS destination, b.bus_number, b.bus_type
     FROM trips t JOIN routes r ON r.id = t.route_id
     JOIN locations lo ON lo.id = r.origin_id JOIN locations ld ON ld.id = r.destination_id
     JOIN buses b ON b.id = t.bus_id
     WHERE t.id = :id AND t.status = 'scheduled' AND t.departure_time > NOW()"
);
$st->execute([':id' => $tripId]);
$trip = $st->fetch();
if (!$trip) { setFlash('error', 'That trip is not available.'); redirect(url('search.php')); }

$st = $pdo->prepare("SELECT id, seat_number FROM seats WHERE id = :s AND bus_id = :b");
$st->execute([':s' => $seatId, ':b' => $trip['bus_id']]);
$seat = $st->fetch();
if (!$seat) { setFlash('error', 'Please choose a seat first.'); redirect(url('trip.php') . '?id=' . $tripId); }

$st = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE trip_id = :t AND seat_id = :s AND status <> 'cancelled'");
$st->execute([':t' => $tripId, ':s' => $seatId]);
if ($st->fetchColumn() > 0) { setFlash('error', 'Sorry, that seat is already booked. Please choose another.'); redirect(url('trip.php') . '?id=' . $tripId); }

$st = $pdo->prepare("SELECT full_name, phone FROM users WHERE id = :id");
$st->execute([':id' => currentUserId()]);
$me = $st->fetch();

$errors = [];
$name  = $me['full_name'];
$phone = (string)$me['phone'];

if (isPost()) {
    verifyCsrf();
    $name  = post('passenger_name');
    $phone = post('passenger_phone');
    $result = createBooking($pdo, currentUserId(), $tripId, $seatId, $name, $phone);
    if ($result['ok']) {
        setFlash('success', 'Booking confirmed! Your reference is ' . $result['reference'] . '.');
        redirect(url('passenger/confirmation.php') . '?id=' . $result['booking_id']);
    }
    $errors[] = $result['error'];
}

$pageTitle = 'Confirm Booking';
$layout = 'passenger';
require __DIR__ . '/../includes/header.php';
?>
<h1>Confirm your booking</h1>
<p class="muted mb">Check the details, then confirm. Payment is simulated in this version.</p>
<?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
<div class="two-col">
  <div class="card">
    <h3>Passenger information</h3>
    <form method="post" novalidate>
      <?= csrfField() ?>
      <input type="hidden" name="trip_id" value="<?= $tripId ?>">
      <input type="hidden" name="seat_id" value="<?= $seatId ?>">
      <div class="form-group"><label for="passenger_name">Passenger full name</label>
        <input type="text" id="passenger_name" name="passenger_name" value="<?= e($name) ?>" required></div>
      <div class="form-group"><label for="passenger_phone">Passenger phone</label>
        <input type="tel" id="passenger_phone" name="passenger_phone" value="<?= e($phone) ?>" required></div>
      <div class="alert alert-info">💳 Simulated payment: no real money will be charged.</div>
      <button class="btn btn-orange btn-block" type="submit">Confirm and pay <?= money($trip['price']) ?></button>
      <a class="btn btn-outline btn-block mt" href="<?= url('trip.php') ?>?id=<?= $tripId ?>">Change seat</a>
    </form>
  </div>
  <div class="card">
    <h3>Trip summary</h3>
    <div class="summary-row"><span>Route</span><strong><?= e($trip['origin']) ?> → <?= e($trip['destination']) ?></strong></div>
    <div class="summary-row"><span>Departure</span><strong><?= e(formatDateTime($trip['departure_time'])) ?></strong></div>
    <div class="summary-row"><span>Bus</span><strong><?= e($trip['bus_number']) ?></strong></div>
    <div class="summary-row"><span>Seat</span><strong><?= e($seat['seat_number']) ?></strong></div>
    <div class="summary-row"><span>Total</span><strong><?= money($trip['price']) ?></strong></div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
