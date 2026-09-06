<?php
require_once 'includes/config.php';

$testDir = __DIR__ . '/uploads/avatars';
$testFile = $testDir . '/test.txt';

// Try to create directory if it doesn't exist
if (!file_exists($testDir)) {
    if (!mkdir($testDir, 0777, true)) {
        die("Failed to create directory");
    }
}

// Try to write a test file
if (file_put_contents($testFile, 'test') !== false) {
    echo "Success! File written to: " . $testFile;
    unlink($testFile); // Clean up
} else {
    echo "Failed to write file. Check permissions for: " . $testDir;
    
    // Additional debugging
    echo "<br>Is directory writable? " . (is_writable($testDir) ? 'Yes' : 'No');
    echo "<br>Directory exists? " . (file_exists($testDir) ? 'Yes' : 'No');
    echo "<br>DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'];
    echo "<br>Full path: " . $testDir;
}