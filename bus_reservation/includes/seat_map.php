<?php
// includes/seat_map.php - Draws the seat map. Expects $seats (list of rows with id, seat_number, taken).
// Optional $selectable (default true): admin view shows the map without radio buttons.
$selectable = $selectable ?? true;
$rows = [];
foreach ($seats as $s) {
    $rowNo = (int)$s['seat_number'];            // "12B" becomes 12
    $rows[$rowNo][strtoupper(substr($s['seat_number'], -1))] = $s;
}
ksort($rows);
?>
<div class="bus-shell">
  <div class="bus-front">🚌 FRONT · Driver</div>
  <?php foreach ($rows as $rowNo => $cols): ?>
    <div class="seat-row">
      <?php foreach (['A', 'B', 'AISLE', 'C', 'D'] as $c):
        if ($c === 'AISLE') { echo '<div class="seat-aisle"></div>'; continue; }
        if (!isset($cols[$c])) { echo '<div class="seat"><span style="visibility:hidden"></span></div>'; continue; }
        $s = $cols[$c]; $taken = (int)$s['taken'] > 0; ?>
        <label class="seat <?= $taken ? 'taken' : '' ?>" title="Seat <?= e($s['seat_number']) ?><?= $taken ? ' (booked)' : '' ?>">
          <?php if ($selectable): ?>
            <input type="radio" name="seat_id" value="<?= (int)$s['id'] ?>" data-label="<?= e($s['seat_number']) ?>" <?= $taken ? 'disabled' : '' ?>>
          <?php endif; ?>
          <span><?= e($s['seat_number']) ?></span>
        </label>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
</div>
<div class="legend">
  <div><i style="background:#e9f2fe;border:2px solid #b7d4f6"></i>Available</div>
  <div><i style="background:var(--orange)"></i>Selected</div>
  <div><i style="background:#e3e7ee;border:2px solid #cdd4de"></i>Booked</div>
</div>
