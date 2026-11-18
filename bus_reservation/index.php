<?php
// index.php - Homepage.
require_once __DIR__ . '/includes/bootstrap.php';
$pdo = getConnection();
$locations = getLocations();

// Popular routes: upcoming routes with their lowest price (real data from the database).
$popular = $pdo->query(
    "SELECT r.id, lo.name AS origin, ld.name AS destination, lo.id AS oid, ld.id AS did,
            MIN(t.price) AS from_price, COUNT(t.id) AS trips
     FROM trips t
     JOIN routes r ON r.id = t.route_id
     JOIN locations lo ON lo.id = r.origin_id
     JOIN locations ld ON ld.id = r.destination_id
     WHERE t.status = 'scheduled' AND t.departure_time > NOW()
     GROUP BY r.id, lo.name, ld.name, lo.id, ld.id
     ORDER BY trips DESC, from_price ASC LIMIT 6"
)->fetchAll();

$pageTitle = 'Book Bus Tickets Online';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="container">
    <h1>Travel across the country, <em>book in seconds.</em></h1>
    <p>Pick your route, choose your seat and get your ticket instantly. Safe buses. Fair prices.</p>
  </div>
</section>

<div class="container">
  <?php showFlash(); ?>
  <div class="search-card"><?php require __DIR__ . '/includes/search_form.php'; ?></div>
</div>

<section class="section">
  <div class="container">
    <h2 class="section-title">Popular routes</h2>
    <p class="section-sub">Upcoming trips with seats available now.</p>
    <?php if (!$popular): ?>
      <div class="alert alert-info">No upcoming trips yet. Please check back soon.</div>
    <?php else: ?>
    <div class="grid grid-3">
      <?php foreach ($popular as $r): ?>
        <a class="card route-card" href="<?= url('search.php') ?>?from=<?= (int)$r['oid'] ?>&to=<?= (int)$r['did'] ?>&date=">
          <h3><?= e($r['origin']) ?> → <?= e($r['destination']) ?></h3>
          <p class="muted"><?= (int)$r['trips'] ?> upcoming trip<?= $r['trips'] > 1 ? 's' : '' ?></p>
          <p class="price">From <?= money($r['from_price']) ?></p>
        </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="section" style="background:#fff">
  <div class="container">
    <h2 class="section-title text-center">Why travel with <?= e(APP_NAME) ?>?</h2>
    <p class="section-sub text-center">Everything you need for a smooth journey.</p>
    <div class="grid grid-4">
      <div class="card feature"><div class="icon">💺</div><h3>Choose your seat</h3><p class="muted">See the real seat map and pick the seat you like.</p></div>
      <div class="card feature"><div class="icon">⚡</div><h3>Instant ticket</h3><p class="muted">Get a booking reference as soon as you book.</p></div>
      <div class="card feature"><div class="icon">🔒</div><h3>Secure</h3><p class="muted">Your account and details are protected.</p></div>
      <div class="card feature"><div class="icon">↩️</div><h3>Easy cancellation</h3><p class="muted">Cancel online up to <?= (int)getSetting('cancellation_hours', '24') ?> hours before departure.</p></div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
