<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Redirect to dashboard if logged in, otherwise to login
if($auth->isLoggedIn()) {
    header("Location: " . BASE_URL . "modules/dashboard/");
} else {
    header("Location: " . BASE_URL . "login.php");
}
exit;
?>