<?php
// logout.php - End the session completely.
require_once __DIR__ . '/includes/bootstrap.php';

$_SESSION = [];                 // 1. remove all data stored for this user
session_regenerate_id(true);    // 2. throw away the old session id (the old session file is deleted)
setFlash('success', 'You have been logged out.');   // 3. a message for the next page
redirect(url('login.php'));
