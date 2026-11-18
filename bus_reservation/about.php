<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'About';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><div class="container"><h1>About <?= e(APP_NAME) ?></h1><p>Making bus travel simple.</p></div></div>
<div class="container section">
  <div class="grid grid-2">
    <div class="card"><h3>Our mission</h3><p class="muted">We help passengers find trips, choose seats and travel with confidence, while giving transport operators one place to manage buses, routes, trips and bookings.</p></div>
    <div class="card"><h3>How booking works</h3><p class="muted">1. Search a route and date. 2. Pick a trip and a seat. 3. Confirm your details. 4. Receive your booking reference and ticket.</p></div>
    <div class="card"><h3>Cancellation policy</h3><p class="muted">Bookings can be cancelled online up to <?= (int)getSetting('cancellation_hours', '24') ?> hours before departure. Cancelled seats become available to other passengers immediately.</p></div>
    <!-- <div class="card"><h3>About this project</h3><p class="muted">This is an academic Online Bus Reservation System built with PHP, MySQL, HTML, CSS and JavaScript. Payments are simulated.</p></div> -->
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
