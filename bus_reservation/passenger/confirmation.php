<?php
// passenger/confirmation.php - Shown right after a successful booking.
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin('passenger');
$booking = getBookingDetails(getConnection(), (int)($_GET['id'] ?? 0));
if (!$booking || (int)$booking['user_id'] !== currentUserId()) {
    setFlash('error', 'Booking not found.'); redirect(url('passenger/bookings.php'));
}
$pageTitle = 'Booking Confirmed';
$layout = 'passenger';
require __DIR__ . '/../includes/header.php';
?>
<div class="text-center mb no-print"><div style="font-size:3rem">🎉</div><h1>Booking confirmed!</h1><p class="muted">Keep your booking reference safe.</p></div>
<?php require __DIR__ . '/../includes/ticket.php'; ?>
<div class="text-center mt no-print">
  <button class="btn btn-orange" data-print type="button">🖨️ Print / Save as PDF</button>
  <a class="btn btn-outline" href="<?= url('passenger/bookings.php') ?>">My bookings</a>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
