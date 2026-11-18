<?php
// includes/header.php - Top of every page.
// Before including, a page may set:  $pageTitle  and  $layout ('public' | 'admin' | 'passenger')
$pageTitle = $pageTitle ?? 'Home';
$layout    = $layout ?? 'public';
$role      = currentRole();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>
<header class="navbar">
  <div class="container nav-inner">
    <a class="brand" href="<?= url('index.php') ?>">🚌 ABCOAD<span>Bus</span></a>
    <button class="nav-toggle" aria-label="Menu">☰</button>
    <ul class="nav-links">
      <li><a class="<?= navActive('index.php') ?>" href="<?= url('index.php') ?>">Home</a></li>
      <li><a class="<?= navActive('search.php') ?>" href="<?= url('search.php') ?>">Find Trips</a></li>
      <li><a class="<?= navActive('about.php') ?>" href="<?= url('about.php') ?>">About</a></li>
      <li><a class="<?= navActive('contact.php') ?>" href="<?= url('contact.php') ?>">Contact</a></li>
      <?php if (isLoggedIn()): ?>
        <li><a href="<?= e(dashboardFor($role ?? 'passenger')) ?>">Dashboard</a></li>
        <li><a class="btn btn-orange btn-sm" href="<?= url('logout.php') ?>">Logout</a></li>
      <?php else: ?>
        <li><a href="<?= url('login.php') ?>">Login</a></li>
        <li><a class="btn btn-orange btn-sm" href="<?= url('register.php') ?>">Register</a></li>
      <?php endif; ?>
    </ul>
  </div>
</header>

<?php if ($layout === 'admin'): ?>
<div class="layout">
  <aside class="sidebar">
    <h4>Overview</h4>
    <a class="<?= navActive('dashboard.php') ?>" href="<?= url('admin/dashboard.php') ?>">📊 Dashboard</a>
    <h4>Operations</h4>
    <a class="<?= navActive('buses.php') ?>" href="<?= url('admin/buses.php') ?>">🚌 Buses</a>
    <a class="<?= navActive('locations.php') ?>" href="<?= url('admin/locations.php') ?>">📍 Locations</a>
    <a class="<?= navActive('routes.php') ?>" href="<?= url('admin/routes.php') ?>">🛣️ Routes</a>
    <a class="<?= navActive('trips.php') ?>" href="<?= url('admin/trips.php') ?>">🗓️ Trips</a>
    <h4>Customers</h4>
    <a class="<?= navActive('bookings.php') ?>" href="<?= url('admin/bookings.php') ?>">🎫 Bookings</a>
    <a class="<?= navActive('payments.php') ?>" href="<?= url('admin/payments.php') ?>">💳 Payments</a>
    <a class="<?= navActive('users.php') ?>" href="<?= url('admin/users.php') ?>">👥 Passengers</a>
    <a class="<?= navActive('messages.php') ?>" href="<?= url('admin/messages.php') ?>">✉️ Messages</a>
    <h4>System</h4>
    <a class="<?= navActive('reports.php') ?>" href="<?= url('admin/reports.php') ?>">📈 Reports</a>
    <a class="<?= navActive('profile.php') ?>" href="<?= url('admin/profile.php') ?>">⚙️ My Profile</a>
  </aside>
  <main class="content">
<?php elseif ($layout === 'passenger'): ?>
<div class="layout">
  <aside class="sidebar">
    <h4>My Account</h4>
    <a class="<?= navActive('dashboard.php') ?>" href="<?= url('passenger/dashboard.php') ?>">🏠 Dashboard</a>
    <a class="<?= navActive('bookings.php') ?>" href="<?= url('passenger/bookings.php') ?>">🎫 My Bookings</a>
    <a class="<?= navActive('profile.php') ?>" href="<?= url('passenger/profile.php') ?>">👤 Profile</a>
    <a href="<?= url('search.php') ?>">🔎 Book a Trip</a>
  </aside>
  <main class="content">
<?php else: ?>
  <main>
<?php endif; ?>
<?php if ($layout !== 'public') { showFlash(); } ?>
