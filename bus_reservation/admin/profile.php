<?php
// admin/profile.php - Admin's own profile (shares logic with the passenger profile).
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin('admin');
$pdo = getConnection();
$selfUrl = url('admin/profile.php');
$pageTitle = 'Admin Profile'; $layout = 'admin';
ob_start();
require __DIR__ . '/../includes/profile_form.php';
$body = ob_get_clean();
require __DIR__ . '/../includes/header.php';
echo $body;
require __DIR__ . '/../includes/footer.php';
