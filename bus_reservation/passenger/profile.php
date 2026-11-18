<?php
// passenger/profile.php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin('passenger');
$pdo = getConnection();
$selfUrl = url('passenger/profile.php');
$pageTitle = 'My Profile'; $layout = 'passenger';
ob_start();                       // run the shared logic first (it may redirect), then print the page
require __DIR__ . '/../includes/profile_form.php';
$body = ob_get_clean();
require __DIR__ . '/../includes/header.php';
echo $body;
require __DIR__ . '/../includes/footer.php';
