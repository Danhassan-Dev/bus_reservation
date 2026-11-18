<?php
// admin/payments.php - Payment records (simulated for now).
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin('admin');
$pdo = getConnection();
$status = (string)($_GET['status'] ?? '');
$sql = "SELECT p.*, b.booking_reference, b.passenger_name FROM payments p JOIN bookings b ON b.id = p.booking_id";
$params = [];
if (in_array($status, ['pending', 'paid', 'failed', 'refunded'], true)) { $sql .= " WHERE p.status = :s"; $params[':s'] = $status; }
$st = $pdo->prepare($sql . " ORDER BY p.created_at DESC, p.id DESC LIMIT 300"); $st->execute($params); $rows = $st->fetchAll();
$tot = $pdo->query("SELECT COALESCE(SUM(CASE WHEN status='paid' THEN amount END),0) paid, COALESCE(SUM(CASE WHEN status='refunded' THEN amount END),0) refunded FROM payments")->fetch();

$pageTitle = 'Payments'; $layout = 'admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="toolbar"><h1>Payments</h1></div>
<div class="alert alert-info">Payments are <strong>simulated</strong>. No real money is processed. A payment gateway can be added later in <code>processPayment()</code>.</div>
<div class="grid grid-2 mb">
  <div class="card stat green"><div class="ico">💰</div><div><div class="num"><?= money($tot['paid']) ?></div><div class="muted">Total paid</div></div></div>
  <div class="card stat purple"><div class="ico">↩️</div><div><div class="num"><?= money($tot['refunded']) ?></div><div class="muted">Total refunded</div></div></div>
</div>
<form class="filters" method="get"><select name="status" onchange="this.form.submit()"><option value="">All statuses</option>
  <?php foreach (['paid', 'pending', 'failed', 'refunded'] as $s): ?><option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></form>
<div class="table-wrap"><table>
  <thead><tr><th>Transaction</th><th>Booking</th><th>Passenger</th><th>Method</th><th>Amount</th><th>Status</th><th>Paid at</th></tr></thead><tbody>
  <?php foreach ($rows as $p): ?>
    <tr><td><code><?= e($p['transaction_ref']) ?></code></td><td><?= e($p['booking_reference']) ?></td><td><?= e($p['passenger_name']) ?></td>
      <td><?= e($p['method']) ?></td><td><?= money($p['amount']) ?></td><td><?= badge($p['status']) ?></td><td><?= e(formatDateTime($p['paid_at'], 'd M Y H:i')) ?></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="7" class="text-center muted">No payments yet.</td></tr><?php endif; ?></tbody></table></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
