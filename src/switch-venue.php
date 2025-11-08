<?php
session_start();

if (isset($_GET['venue_id'])) {
    $_SESSION['test_venue_id'] = (int)$_GET['venue_id'];
}

// Redirect back to the referring page or home
$redirect = $_SERVER['HTTP_REFERER'] ?? '/';
header('Location: ' . $redirect);
exit;
