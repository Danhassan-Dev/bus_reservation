<?php
// includes/booking.php - The business rules for booking, paying and cancelling.

/** Make a unique-looking reference, e.g. BUS-261107-K7M2QX */
function makeBookingReference(): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';   // no 0/O/1/I to avoid confusion
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return 'BUS-' . date('ymd') . '-' . $code;
}

/**
 * PAYMENT GATEWAY PLACEHOLDER.
 * Today this only SIMULATES a successful payment. No real money moves.
 * Later, replace the inside of this function with a real gateway call
 * (Paystack, Flutterwave, Opay...). The rest of the booking code stays the same.
 */
function processPayment(PDO $pdo, int $bookingId, float $amount): array
{
    $ref = 'SIM-' . strtoupper(bin2hex(random_bytes(5)));
    $st = $pdo->prepare(
        "INSERT INTO payments (booking_id, amount, method, status, transaction_ref, paid_at)
         VALUES (:b, :a, 'simulated', 'paid', :r, NOW())"
    );
    $st->execute([':b' => $bookingId, ':a' => $amount, ':r' => $ref]);
    return ['paid' => true, 'transaction_ref' => $ref];
}

/**
 * Reserve one seat on one trip. Everything happens inside a TRANSACTION:
 * either all steps succeed, or none of them are saved.
 * Returns ['ok' => bool, 'error' => string, 'booking_id' => int, 'reference' => string]
 */
function createBooking(PDO $pdo, int $userId, int $tripId, int $seatId, string $name, string $phone): array
{
    $fail = fn(string $msg) => ['ok' => false, 'error' => $msg, 'booking_id' => 0, 'reference' => ''];

    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) return $fail('Enter the passenger name (2 to 100 characters).');
    if (($err = validatePhone($phone)) !== '') return $fail($err);

    try {
        $pdo->beginTransaction();

        // 1. Lock the trip row so two people cannot change it at the same moment.
        $st = $pdo->prepare(
            "SELECT id, bus_id, price FROM trips
             WHERE id = :id AND status = 'scheduled' AND departure_time > NOW() FOR UPDATE"
        );
        $st->execute([':id' => $tripId]);
        $trip = $st->fetch();
        if (!$trip) { $pdo->rollBack(); return $fail('This trip is no longer available for booking.'); }

        // 2. The seat must really belong to the bus used on this trip.
        $st = $pdo->prepare("SELECT id FROM seats WHERE id = :s AND bus_id = :b");
        $st->execute([':s' => $seatId, ':b' => $trip['bus_id']]);
        if (!$st->fetch()) { $pdo->rollBack(); return $fail('That seat does not exist on this bus.'); }

        // 3. Is the seat still free?
        $st = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE trip_id = :t AND seat_id = :s AND status <> 'cancelled'");
        $st->execute([':t' => $tripId, ':s' => $seatId]);
        if ((int)$st->fetchColumn() > 0) { $pdo->rollBack(); return $fail('Sorry, that seat was just taken. Please choose another seat.'); }

        // 4. Insert the booking (retry if the random reference already exists).
        $ins = $pdo->prepare(
            "INSERT INTO bookings (booking_reference, user_id, trip_id, seat_id, passenger_name, passenger_phone, amount, status)
             VALUES (:ref, :u, :t, :s, :n, :p, :a, 'pending')"
        );
        $bookingId = 0; $reference = '';
        for ($try = 0; $try < 5 && !$bookingId; $try++) {
            $reference = makeBookingReference();
            try {
                $ins->execute([':ref' => $reference, ':u' => $userId, ':t' => $tripId, ':s' => $seatId,
                               ':n' => $name, ':p' => $phone, ':a' => $trip['price']]);
                $bookingId = (int)$pdo->lastInsertId();
            } catch (PDOException $e) {
                // Duplicate reference: try again. Duplicate seat: the database blocked a double booking.
                if (str_contains($e->getMessage(), 'uq_trip_seat_active')) { $pdo->rollBack(); return $fail('Sorry, that seat was just taken. Please choose another seat.'); }
                if (!str_contains($e->getMessage(), 'uq_booking_reference')) { throw $e; }
            }
        }
        if (!$bookingId) { $pdo->rollBack(); return $fail('Could not create a booking reference. Please try again.'); }

        // 5. Simulated payment, then confirm the booking.
        $pay = processPayment($pdo, $bookingId, (float)$trip['price']);
        if (!$pay['paid']) { $pdo->rollBack(); return $fail('Payment failed. No seat was reserved.'); }
        $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = :id")->execute([':id' => $bookingId]);

        $pdo->commit();
        return ['ok' => true, 'error' => '', 'booking_id' => $bookingId, 'reference' => $reference];

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('createBooking error: ' . $e->getMessage());
        return $fail('Something went wrong while booking. Please try again.');
    }
}

/**
 * Cancel a booking. Passengers: must own it and be within the policy.
 * Admins ($isAdmin = true) may cancel any booking at any time.
 * Setting status = 'cancelled' also frees the seat (see seat_lock in the database).
 */
function cancelBooking(PDO $pdo, int $bookingId, int $userId, bool $isAdmin = false): array
{
    try {
        $pdo->beginTransaction();
        $st = $pdo->prepare(
            "SELECT b.id, b.user_id, b.status, t.departure_time,
                    TIMESTAMPDIFF(MINUTE, NOW(), t.departure_time) AS minutes_left
             FROM bookings b JOIN trips t ON t.id = b.trip_id
             WHERE b.id = :id FOR UPDATE"
        );
        $st->execute([':id' => $bookingId]);
        $b = $st->fetch();

        if (!$b || (!$isAdmin && (int)$b['user_id'] !== $userId)) { $pdo->rollBack(); return ['ok' => false, 'error' => 'Booking not found.']; }
        if ($b['status'] === 'cancelled') { $pdo->rollBack(); return ['ok' => false, 'error' => 'This booking is already cancelled.']; }

        if (!$isAdmin) {
            $hours = (int)getSetting('cancellation_hours', '24');
            if ((int)$b['minutes_left'] < $hours * 60) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => "Bookings can only be cancelled at least $hours hours before departure."];
            }
        }

        $pdo->prepare("UPDATE bookings SET status = 'cancelled', cancelled_at = NOW() WHERE id = :id")->execute([':id' => $bookingId]);
        $pdo->prepare("UPDATE payments SET status = 'refunded' WHERE booking_id = :id AND status = 'paid'")->execute([':id' => $bookingId]);
        $pdo->commit();
        return ['ok' => true, 'error' => ''];

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('cancelBooking error: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Could not cancel the booking. Please try again.'];
    }
}

/** Can this booking still be cancelled by the passenger? */
function canPassengerCancel(array $booking): bool
{
    if ($booking['status'] === 'cancelled') return false;
    $hours = (int)getSetting('cancellation_hours', '24');
    return strtotime($booking['departure_time']) - time() >= $hours * 3600;
}

/** One booking with everything needed for a ticket. Returns null if not found. */
function getBookingDetails(PDO $pdo, int $id): ?array
{
    $st = $pdo->prepare(
        "SELECT b.*, t.departure_time, t.arrival_time, t.status AS trip_status,
                lo.name AS origin, ld.name AS destination,
                bu.bus_number, bu.model, bu.bus_type, s.seat_number,
                u.full_name AS account_name, u.email AS account_email,
                p.status AS payment_status, p.method AS payment_method, p.transaction_ref, p.paid_at
         FROM bookings b
         JOIN trips t ON t.id = b.trip_id
         JOIN routes r ON r.id = t.route_id
         JOIN locations lo ON lo.id = r.origin_id
         JOIN locations ld ON ld.id = r.destination_id
         JOIN buses bu ON bu.id = t.bus_id
         JOIN seats s ON s.id = b.seat_id
         JOIN users u ON u.id = b.user_id
         LEFT JOIN payments p ON p.booking_id = b.id
         WHERE b.id = :id"
    );
    $st->execute([':id' => $id]);
    return $st->fetch() ?: null;
}
