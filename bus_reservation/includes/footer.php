<?php // includes/footer.php - Bottom of every page. Closes what header.php opened. ?>
  </main>
<?php if (($layout ?? 'public') !== 'public'): ?>
</div>
<?php endif; ?>
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div><h4>🚌 <?= e(APP_NAME) ?></h4><p>Book safe, comfortable bus trips across the country in a few clicks.</p></div>
      <div><h4>Quick links</h4><a href="<?= url('search.php') ?>">Find trips</a><a href="<?= url('about.php') ?>">About us</a><a href="<?= url('contact.php') ?>">Contact</a></div>
      <div><h4>Account</h4><a href="<?= url('login.php') ?>">Login</a><a href="<?= url('register.php') ?>">Register</a></div>
    </div>
    <div class="copy">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. Payments in this demo are simulated.</div>
  </div>
</footer>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>
