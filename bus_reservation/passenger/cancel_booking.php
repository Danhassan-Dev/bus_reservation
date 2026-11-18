<?php
// passenger/cancel_booking.php - Handles the Cancel button (POST only).
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin('passenger');
if (!isPost()) { redirect(url('passenger/bookings.php')); }
verifyCsrf();

$id = (int)($_POST['id'] ?? 0);
$result = cancelBooking(getConnection(), $id, currentUserId(), false);
setFlash($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Booking cancelled. Your seat has been released.' : $result['error']);
redirect(url('passenger/booking_details.php') . '?id=' . $id);
