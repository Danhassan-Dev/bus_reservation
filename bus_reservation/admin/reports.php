<?php
// admin/reports.php - Booking and revenue reports for a date range (by booking date).
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin('admin');
$pdo = getConnection();

$valid = fn($d) => ($x = DateTime::createFromFormat('Y-m-d', (string)$d)) && $x->format('Y-m-d') === $d;
$from = $valid($_GET['from'] ?? '') ? $_GET['from'] : date('Y-m-d', strtotime('-30 days'));
$to   = $valid($_GET['to'] ?? '')   ? $_GET['to']   : date('Y-m-d');
if ($from > $to) { [$from, $to] = [$to, $from]; }
$p = [':f' => $from . ' 00:00:00', ':t' => $to . ' 23:59:59'];

$st = $pdo->prepare("SELECT COUNT(*) total, SUM(status='confirmed') confirmed, SUM(status='pending') pending, SUM(status='cancelled') cancelled
    FROM bookings WHERE booked_at BETWEEN :f AND :t");
$st->execute($p); $sum = $st->fetch();

$st = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN p.status='paid' THEN p.amount END),0) revenue, COALESCE(SUM(CASE WHEN p.status='refunded' THEN p.amount END),0) refunded
    FROM payments p JOIN bookings b ON b.id = p.booking_id WHERE b.booked_at BETWEEN :f AND :t");
$st->execute($p); $money = $st->fetch();

$st = $pdo->prepare("SELECT lo.name o, ld.name d, COUNT(*) bookings, COALESCE(SUM(CASE WHEN b.status<>'cancelled' THEN b.amount END),0) revenue
    FROM bookings b JOIN trips t ON t.id=b.trip_id JOIN routes r ON r.id=t.route_id JOIN locations lo ON lo.id=r.origin_id JOIN locations ld ON ld.id=r.destination_id
    WHERE b.booked_at BETWEEN :f AND :t AND b.status<>'cancelled' GROUP BY r.id, lo.name, ld.name ORDER BY revenue DESC");
$st->execute($p); $byRoute = $st->fetchAll();

$st = $pdo->prepare("SELECT DATE(booked_at) day, COUNT(*) bookings, COALESCE(SUM(CASE WHEN status<>'cancelled' THEN amount END),0) revenue
    FROM bookings WHERE booked_at BETWEEN :f AND :t GROUP BY DATE(booked_at) ORDER BY day DESC");
$st->execute($p); $byDay = $st->fetchAll();

$pageTitle = 'Reports'; $layout = 'admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="toolbar"><h1>Reports</h1><button class="btn btn-sm no-print" data-print type="button">🖨️ Print</button></div>
<form class="filters no-print" method="get">
  <label style="margin:0;align-self:center">From</label><input type="date" name="from" value="<?= e($from) ?>">
  <label style="margin:0;align-self:center">To</label><input type="date" name="to" value="<?= e($to) ?>">
  <button class="btn btn-sm">Generate</button>
</form>
<p class="muted mb">Period: <?= e(date('d M Y', strtotime($from))) ?> to <?= e(date('d M Y', strtotime($to))) ?> (by booking date)</p>
<div class="grid grid-4 mb">
  <div class="card stat"><div class="ico">🎫</div><div><div class="num"><?= (int)$sum['total'] ?></div><div class="muted">Bookings</div></div></div>
  <div class="card stat green"><div class="ico">✅</div><div><div class="num"><?= (int)$sum['confirmed'] ?></div><div class="muted">Confirmed</div></div></div>
  <div class="card stat purple"><div class="ico">↩️</div><div><div class="num"><?= (int)$sum['cancelled'] ?></div><div class="muted">Cancelled</div></div></div>
  <div class="card stat orange"><div class="ico">💰</div><div><div class="num" style="font-size:1.3rem"><?= money($money['revenue']) ?></div><div class="muted">Revenue (refunded <?= money($money['refunded']) ?>)</div></div></div>
</div>
<h2 class="mb">Revenue by route</h2>
<div class="table-wrap mb"><table><thead><tr><th>Route</th><th>Bookings</th><th>Revenue</th></tr></thead><tbody>
  <?php foreach ($byRoute as $r): ?><tr><td><?= e($r['o']) ?> → <?= e($r['d']) ?></td><td><?= (int)$r['bookings'] ?></td><td><?= money($r['revenue']) ?></td></tr>
  <?php endforeach; if (!$byRoute): ?><tr><td colspan="3" class="text-center muted">No data for this period.</td></tr><?php endif; ?></tbody></table></div>
<h2 class="mb">Bookings by day</h2>
<div class="table-wrap"><table><thead><tr><th>Date</th><th>Bookings</th><th>Revenue</th></tr></thead><tbody>
  <?php foreach ($byDay as $r): ?><tr><td><?= e(date('D, d M Y', strtotime($r['day']))) ?></td><td><?= (int)$r['bookings'] ?></td><td><?= money($r['revenue']) ?></td></tr>
  <?php endforeach; if (!$byDay): ?><tr><td colspan="3" class="text-center muted">No data for this period.</td></tr><?php endif; ?></tbody></table></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
