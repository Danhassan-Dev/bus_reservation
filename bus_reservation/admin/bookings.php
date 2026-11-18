<?php
// admin/bookings.php - See all reservations, confirm or cancel them.
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin('admin');
$pdo = getConnection();
$self = url('admin/bookings.php');

if (isPost()) {
    verifyCsrf();
    $id = (int)post('id'); $action = post('action');
    if ($action === 'cancel') {
        $res = cancelBooking($pdo, $id, currentUserId(), true);      // admins may cancel at any time
        setFlash($res['ok'] ? 'success' : 'error', $res['ok'] ? 'Booking cancelled and seat released.' : $res['error']);
    } elseif ($action === 'confirm') {
        $n = $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = :id AND status = 'pending'");
        $n->execute([':id' => $id]);
        $pdo->prepare("UPDATE payments SET status = 'paid', paid_at = COALESCE(paid_at, NOW()) WHERE booking_id = :id AND status = 'pending'")->execute([':id' => $id]);
        setFlash($n->rowCount() ? 'success' : 'error', $n->rowCount() ? 'Booking confirmed.' : 'Only pending bookings can be confirmed.');
    }
    redirect($self . (isset($_POST['back']) ? '?' . http_build_query(['status' => (string)($_POST['back_status'] ?? ''), 'q' => (string)($_POST['back_q'] ?? '')]) : ''));
}

$status = (string)($_GET['status'] ?? ''); $q = trim((string)($_GET['q'] ?? ''));
$sql = "SELECT b.id, b.booking_reference, b.passenger_name, b.amount, b.status, b.booked_at, t.departure_time,
               lo.name AS origin, ld.name AS destination, s.seat_number, u.email
        FROM bookings b JOIN trips t ON t.id = b.trip_id JOIN routes r ON r.id = t.route_id
        JOIN locations lo ON lo.id = r.origin_id JOIN locations ld ON ld.id = r.destination_id
        JOIN seats s ON s.id = b.seat_id JOIN users u ON u.id = b.user_id WHERE 1=1";
$params = [];
if (in_array($status, ['pending', 'confirmed', 'cancelled'], true)) { $sql .= " AND b.status = :s"; $params[':s'] = $status; }
if ($q !== '') { $sql .= " AND (b.booking_reference LIKE :q OR b.passenger_name LIKE :q OR u.email LIKE :q)"; $params[':q'] = '%' . $q . '%'; }
$st = $pdo->prepare($sql . " ORDER BY b.booked_at DESC, b.id DESC LIMIT 300"); $st->execute($params); $rows = $st->fetchAll();

$pageTitle = 'Bookings'; $layout = 'admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="toolbar"><h1>Reservations</h1></div>
<form class="filters" method="get">
  <input type="text" name="q" placeholder="Reference, name or email" value="<?= e($q) ?>">
  <select name="status" onchange="this.form.submit()"><option value="">All statuses</option>
    <?php foreach (['pending', 'confirmed', 'cancelled'] as $s): ?><option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select>
  <button class="btn btn-sm">Search</button>
</form>
<div class="table-wrap"><table>
  <thead><tr><th>Reference</th><th>Passenger</th><th>Route</th><th>Departure</th><th>Seat</th><th>Amount</th><th>Status</th><th>Actions</th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?>
    <tr><td><strong><?= e($r['booking_reference']) ?></strong></td><td><?= e($r['passenger_name']) ?><div class="muted" style="font-size:.8rem"><?= e($r['email']) ?></div></td>
      <td><?= e($r['origin']) ?> → <?= e($r['destination']) ?></td><td><?= e(formatDateTime($r['departure_time'], 'd M Y H:i')) ?></td>
      <td><?= e($r['seat_number']) ?></td><td><?= money($r['amount']) ?></td><td><?= badge($r['status']) ?></td>
      <td class="actions"><a class="btn btn-sm" href="<?= url('passenger/booking_details.php') ?>?id=<?= (int)$r['id'] ?>">View</a>
        <?php if ($r['status'] === 'pending'): ?>
          <form method="post"><?= csrfField() ?><input type="hidden" name="action" value="confirm"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-green btn-sm">Confirm</button></form>
        <?php endif; if ($r['status'] !== 'cancelled'): ?>
          <form method="post" data-confirm="Cancel this booking and release the seat?"><?= csrfField() ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-danger btn-sm">Cancel</button></form>
        <?php endif; ?></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="8" class="text-center muted">No reservations found.</td></tr><?php endif; ?></tbody></table></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
