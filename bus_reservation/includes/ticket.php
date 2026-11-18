<?php // includes/ticket.php - Printable ticket. Expects $booking (from getBookingDetails). ?>
<div class="ticket">
  <div class="ticket-head">
    <div><h2>🚌 <?= e(APP_NAME) ?> E-Ticket</h2><div style="color:#c9d8ee">Booking status: <?= badge($booking['status']) ?></div></div>
    <div class="ticket-ref"><?= e($booking['booking_reference']) ?></div>
  </div>
  <div class="ticket-body">
    <div><div class="k">Passenger</div><div class="v"><?= e($booking['passenger_name']) ?></div></div>
    <div><div class="k">Phone</div><div class="v"><?= e($booking['passenger_phone']) ?></div></div>
    <div><div class="k">From</div><div class="v"><?= e($booking['origin']) ?></div></div>
    <div><div class="k">To</div><div class="v"><?= e($booking['destination']) ?></div></div>
    <div><div class="k">Departure</div><div class="v"><?= e(formatDateTime($booking['departure_time'])) ?></div></div>
    <div><div class="k">Arrival</div><div class="v"><?= e(formatDateTime($booking['arrival_time'])) ?></div></div>
    <div><div class="k">Seat</div><div class="v"><?= e($booking['seat_number']) ?></div></div>
    <div><div class="k">Bus</div><div class="v"><?= e($booking['bus_number']) ?> · <?= e(ucfirst($booking['bus_type'])) ?></div></div>
    <div><div class="k">Amount</div><div class="v"><?= money($booking['amount']) ?></div></div>
    <div><div class="k">Payment</div><div class="v"><?= $booking['payment_status'] ? badge($booking['payment_status']) : '-' ?> <span class="muted" style="font-weight:400;font-size:.8rem"><?= e($booking['payment_method'] ?? '') ?></span></div></div>
  </div>
  <div class="ticket-foot">Booked on <?= e(formatDateTime($booking['booked_at'])) ?>. Please arrive 30 minutes before departure and show this reference to the driver. Payment in this demo is simulated.</div>
</div>
