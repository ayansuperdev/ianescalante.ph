<?php
// dashboard.php

require_once 'includes/config.php';
require_once 'includes/auth.php';

redirectIfNotLoggedIn();

// Remove the debug output that's causing issues
// echo "<!-- Debug: Enqueued scripts -->\n";
// echo "<!-- " . print_r($GLOBALS['enqueued_scripts'], true) . " -->\n";

if (isAdmin()) {
    header("Location: " . BASE_URL . "/admin_dashboard.php");
} else {
    header("Location: " . BASE_URL . "/user_dashboard.php");
}
exit();