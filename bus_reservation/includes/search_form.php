<?php
// includes/search_form.php - The trip search form (used on the homepage and search page).
// Expects $locations. Optional: $selFrom, $selTo, $selDate.
$selFrom = $selFrom ?? ''; $selTo = $selTo ?? ''; $selDate = $selDate ?? '';
?>
<form class="search-form" method="get" action="<?= url('search.php') ?>">
  <div class="form-group" style="margin:0">
    <label for="from">Leaving from</label>
    <select id="from" name="from" required>
      <option value="">Select city</option>
      <?php foreach ($locations as $l): ?>
        <option value="<?= (int)$l['id'] ?>" <?= (string)$l['id'] === (string)$selFrom ? 'selected' : '' ?>><?= e($l['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group" style="margin:0">
    <label for="to">Going to</label>
    <select id="to" name="to" required>
      <option value="">Select city</option>
      <?php foreach ($locations as $l): ?>
        <option value="<?= (int)$l['id'] ?>" <?= (string)$l['id'] === (string)$selTo ? 'selected' : '' ?>><?= e($l['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group" style="margin:0">
    <label for="date">Travel date</label>
    <input type="date" id="date" name="date" min="<?= date('Y-m-d') ?>" value="<?= e($selDate) ?>" required>
  </div>
  <button class="btn btn-orange" type="submit">Search trips</button>
</form>
