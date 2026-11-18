<?php
// search.php - Find trips by origin, destination and date.
require_once __DIR__ . '/includes/bootstrap.php';
$pdo = getConnection();
$locations = getLocations();

$selFrom = (string)($_GET['from'] ?? '');
$selTo   = (string)($_GET['to'] ?? '');
$selDate = (string)($_GET['date'] ?? '');
$searched = isset($_GET['from']) || isset($_GET['to']);
$errors = []; $trips = [];

if ($searched) {
    if (!ctype_digit($selFrom) || !ctype_digit($selTo)) $errors[] = 'Please choose where you are leaving from and going to.';
    elseif ($selFrom === $selTo) $errors[] = 'Departure and destination must be different.';
    $d = DateTime::createFromFormat('Y-m-d', $selDate);
    if ($selDate !== '' && (!$d || $d->format('Y-m-d') !== $selDate)) $errors[] = 'That date is not valid.';

    if (!$errors) {
        $sql = "SELECT t.id, t.departure_time, t.arrival_time, t.price, b.bus_type, b.model,
                       lo.name AS origin, ld.name AS destination,
                       (SELECT COUNT(*) FROM seats s WHERE s.bus_id = t.bus_id)
                     - (SELECT COUNT(*) FROM bookings k WHERE k.trip_id = t.id AND k.status <> 'cancelled') AS seats_left
                FROM trips t
                JOIN routes r ON r.id = t.route_id
                JOIN locations lo ON lo.id = r.origin_id
                JOIN locations ld ON ld.id = r.destination_id
                JOIN buses b ON b.id = t.bus_id
                WHERE r.origin_id = :f AND r.destination_id = :t
                  AND t.status = 'scheduled' AND t.departure_time > NOW() AND b.status = 'active'";
        $params = [':f' => (int)$selFrom, ':t' => (int)$selTo];
        if ($selDate !== '') { $sql .= " AND DATE(t.departure_time) = :d"; $params[':d'] = $selDate; }
        $sql .= " ORDER BY t.departure_time";
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $trips = $st->fetchAll();
    }
}

$pageTitle = 'Find Trips';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><div class="container"><h1>Find your trip</h1><p>Search available buses and choose your seat.</p></div></div>
<div class="container">
  <?php showFlash(); ?>
  <div class="search-card"><?php require __DIR__ . '/includes/search_form.php'; ?></div>
  <div class="section" style="padding-top:30px">
    <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>

    <?php if ($searched && !$errors): ?>
      <h2 class="mb"><?= count($trips) ?> trip<?= count($trips) === 1 ? '' : 's' ?> found</h2>
      <?php if (!$trips): ?>
        <div class="alert alert-info">No trips match your search. Try another date or route.</div>
      <?php endif; ?>
      <?php foreach ($trips as $t): $left = max(0, (int)$t['seats_left']); ?>
        <div class="trip-card">
          <div>
            <div class="trip-times">
              <div><div class="t"><?= e(date('H:i', strtotime($t['departure_time']))) ?></div><div class="trip-meta"><?= e($t['origin']) ?></div></div>
              <div class="arrow">→</div>
              <div><div class="t"><?= e(date('H:i', strtotime($t['arrival_time']))) ?></div><div class="trip-meta"><?= e($t['destination']) ?></div></div>
            </div>
          </div>
          <div class="trip-meta">
            📅 <?= e(formatDateTime($t['departure_time'], 'D, d M Y')) ?><br>
            🚌 <?= e($t['model'] ?: 'Bus') ?> · <?= e(ucfirst($t['bus_type'])) ?><br>
            💺 <strong style="color:<?= $left <= 5 ? 'var(--red)' : 'var(--green)' ?>"><?= $left ?> seat<?= $left === 1 ? '' : 's' ?> left</strong>
          </div>
          <div class="trip-price">
            <div class="amt"><?= money($t['price']) ?></div>
            <?php if ($left > 0): ?>
              <a class="btn btn-orange btn-sm mt" href="<?= url('trip.php') ?>?id=<?= (int)$t['id'] ?>">Select seat</a>
            <?php else: ?>
              <span class="badge badge-cancelled">Sold out</span>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php elseif (!$searched): ?>
      <div class="alert alert-info">Choose a route and date above to see available trips.</div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
