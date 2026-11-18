<?php
// passenger/booking_details.php - View one booking (passengers: own bookings only; admins: any).
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
$booking = getBookingDetails(getConnection(), (int)($_GET['id'] ?? 0));
$isAdmin = currentRole() === 'admin';
if (!$booking || (!$isAdmin && (int)$booking['user_id'] !== currentUserId())) {
    setFlash('error', 'Booking not found.'); redirect(dashboardFor(currentRole()));
}
$pageTitle = 'Booking ' . $booking['booking_reference'];
$layout = $isAdmin ? 'admin' : 'passenger';
require __DIR__ . '/../includes/header.php';
?>
<div class="toolbar no-print">
  <h1>Booking details</h1>
  <a class="btn btn-grey btn-sm" href="<?= $isAdmin ? url('admin/bookings.php') : url('passenger/bookings.php') ?>">← Back</a>
</div>
<?php require __DIR__ . '/../includes/ticket.php'; ?>
<div class="text-center mt no-print actions" style="justify-content:center">
  <button class="btn btn-orange" data-print type="button">🖨️ Print / Save as PDF</button>
  <?php if (!$isAdmin && canPassengerCancel($booking)): ?>
    <form method="post" action="<?= url('passenger/cancel_booking.php') ?>" data-confirm="Cancel this booking? The seat will be released.">
      <?= csrfField() ?><input type="hidden" name="id" value="<?= (int)$booking['id'] ?>">
      <button class="btn btn-danger" type="submit">Cancel booking</button>
    </form>
  <?php elseif (!$isAdmin && $booking['status'] !== 'cancelled'): ?>
    <span class="muted">Cancellation window closed (<?= (int)getSetting('cancellation_hours', '24') ?>h before departure).</span>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
