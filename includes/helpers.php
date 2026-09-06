<?php
// includes/helpers.php

function deleteDirectory($dir) {
    if (!file_exists($dir)) return;
    
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    foreach ($files as $fileinfo) {
        if ($fileinfo->isDir() && !$fileinfo->isLink()) {
            rmdir($fileinfo->getRealPath());
        } else {
            unlink($fileinfo->getRealPath());
        }
    }
    
    rmdir($dir);
}

// Helper function to sanitize titles (if not already defined)
// In plugin_api.php, replace the existing function with this
if (!function_exists('sanitize_title')) {
    function sanitize_title($title) {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
    }
} else {
    // Log a warning if the function already exists
    error_log('Warning: sanitize_title() function already exists');
}

// Helper function to create nonces (if not already defined)
if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action = '') {
        return md5($action . time() . mt_rand());
    }
}

// Helper function to handle script dependencies in a custom framework
function handle_script_dependencies() {
    global $wp_scripts;
    
    if (isset($wp_scripts) && is_array($wp_scripts)) {
        foreach ($wp_scripts as $handle => $script) {
            if (isset($script['deps']) && !empty($script['deps'])) {
                // Handle dependencies here if needed
                // This is a placeholder for custom dependency handling
            }
        }
    }
}