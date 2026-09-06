<?php
// includes/active_plugin.php

require_once __DIR__ . '/../config.php';
redirectIfNotAdmin();

if (isset($_GET['slug']) && isset($_GET['action'])) {
    $active = ($_GET['action'] === 'activate') ? 1 : 0;
    
    $stmt = $pdo->prepare("UPDATE plugins SET active = ? WHERE slug = ?");
    $stmt->execute([$active, $_GET['slug']]);
    
    $_SESSION['message'] = "Plugin " . ($active ? "activated" : "deactivated") . " successfully";
}


$active_plugins = [];

// Load active plugins from database
$stmt = $pdo->query("SELECT slug FROM plugins WHERE active = 1");
while ($plugin = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $active_plugins[] = $plugin['slug'];
}

// Load active plugins
//foreach ($active_plugins as $plugin_slug) {
//    $plugin_dir = __DIR__ . '/../../plugins/' . $plugin_slug;
//    $php_files = glob($plugin_dir . '/*.php');
    
//    foreach ($php_files as $php_file) {
 //       if (preg_match('/Plugin Name:/i', file_get_contents($php_file))) {
//            require_once $php_file;
//            break;
//        }
//    }
//}

header("Location: ../admin_dashboard.php?page=plugins");
exit;