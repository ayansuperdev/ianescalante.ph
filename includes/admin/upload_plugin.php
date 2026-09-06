<?php
// includes/admin/upload_plugin.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';
// Add this at the top
require_once __DIR__ . '/../helpers.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

redirectIfNotAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['plugin_zip'])) {
    try {
        // Create plugins directory if it doesn't exist
        $pluginsDir = __DIR__ . '/../../plugins/';
        if (!file_exists($pluginsDir)) {
            mkdir($pluginsDir, 0755, true);
        }
        
        // Create temp directory
        $tempDir = __DIR__ . '/../../temp/';
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        $zipFile = $_FILES['plugin_zip'];
        $tempFile = $tempDir . basename($zipFile['name']);
        
        // Move uploaded file to temp directory
        if (!move_uploaded_file($zipFile['tmp_name'], $tempFile)) {
            throw new Exception("Failed to move uploaded file");
        }
        
        // Extract ZIP file
        $zip = new ZipArchive;
        $zipStatus = $zip->open($tempFile);
        if ($zipStatus !== TRUE) {
            throw new Exception("Failed to open ZIP file (Error: $zipStatus)");
        }
        
        // Get root directory name from ZIP
        $rootDir = $zip->getNameIndex(0);
        if ($rootDir === false) {
            throw new Exception("Failed to read ZIP contents");
        }
        
        if (substr($rootDir, -1) !== '/') {
            $rootDir = dirname($rootDir) . '/';
        }
        
        $slug = basename(rtrim($rootDir, '/'));
        $pluginDir = $pluginsDir . $slug;
        
        // Create plugin directory
        if (!file_exists($pluginDir)) {
            mkdir($pluginDir, 0755, true);
        }
        
        // Extract files
        if (!$zip->extractTo($pluginDir)) {
            throw new Exception("Failed to extract ZIP file");
        }
        $zip->close();

        // Flatten directory structure if needed
$items = scandir($pluginDir);
$topLevelItems = array_diff($items, ['.', '..']);

if (count($topLevelItems) === 1) {
    $firstItem = reset($topLevelItems);
    $innerDir = $pluginDir . DIRECTORY_SEPARATOR . $firstItem;
    
    if (is_dir($innerDir)) {
        // Move contents to main plugin directory
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($innerDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($files as $file) {
            $newPath = $pluginDir . DIRECTORY_SEPARATOR . $files->getSubPathName();
            if ($file->isDir()) {
                mkdir($newPath);
            } else {
                rename($file->getPathname(), $newPath);
            }
        }
        
        // Remove the empty directory
        rmdir($innerDir);
    }
}

// Add this helper function above deleteDirectory()
function recursiveCopy($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst);
    
    while (($file = readdir($dir)) !== false) {
        if ($file != '.' && $file != '..') {
            $srcFile = $src . '/' . $file;
            $dstFile = $dst . '/' . $file;
            
            if (is_dir($srcFile)) {
                recursiveCopy($srcFile, $dstFile);
            } else {
                copy($srcFile, $dstFile);
            }
        }
    }
    
    closedir($dir);
}

        // Delete temp file
        unlink($tempFile);
        
        // WordPress-style PHP plugin detection
        $metadata = [];
        $phpFiles = glob($pluginDir . '/*.php');
        
// Look for main plugin file with WordPress-style header
foreach ($phpFiles as $phpFile) {
    $fileContents = file_get_contents($phpFile);
    
    // Check for WordPress plugin header
    if (preg_match('/Plugin Name:\s*(.+)/i', $fileContents, $nameMatch)) {
        $metadata['name'] = trim($nameMatch[1]);
        
        // Extract other metadata
        if (preg_match('/Description:\s*(.+)/i', $fileContents, $descMatch)) {
            $metadata['description'] = trim($descMatch[1]);
        }
        if (preg_match('/Version:\s*(.+)/i', $fileContents, $verMatch)) {
            $metadata['version'] = trim($verMatch[1]);
        }
        if (preg_match('/Author:\s*(.+)/i', $fileContents, $authMatch)) {
            $metadata['author'] = trim($authMatch[1]);
        }
        
        // FIX: Preserve the original slug from ZIP directory
        $originalSlug = $slug;
        break;
    }
}

// If no metadata found, try to get from directory structure
if (empty($metadata)) {
    $metadata = [
        'name' => ucwords(str_replace(['-', '_'], ' ', $slug)),
        'description' => 'Custom PHP Plugin',
        'version' => '1.0.0',
        'author' => 'Unknown Author'
    ];
}

// FIX: Use consistent slug variable
$finalSlug = isset($originalSlug) ? $originalSlug : $slug;

// Check if plugin already exists
$stmt = $pdo->prepare("SELECT id FROM plugins WHERE slug = ?");
$stmt->execute([$finalSlug]);  // Use $finalSlug here
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing plugin - removed 'active' column
            $stmt = $pdo->prepare("UPDATE plugins SET 
                name = ?, 
                description = ?, 
                version = ?, 
                author = ?,
                updated_at = NOW()
                WHERE slug = ?");
            $stmt->execute([
                $metadata['name'],
                $metadata['description'] ?? '',
                $metadata['version'] ?? '1.0.0',
                $metadata['author'] ?? '',
                $slug
            ]);
            $message = "Plugin updated successfully";
        } else {
            // Insert new plugin - removed 'active' column
// Update the INSERT statement
$stmt = $pdo->prepare("INSERT INTO plugins 
    (name, slug, description, version, author, active) 
    VALUES (?, ?, ?, ?, ?, 1)"); // Set active=1 for new plugins
            $stmt->execute([
                $metadata['name'],
                $slug,
                $metadata['description'] ?? '',
                $metadata['version'] ?? '1.0.0',
                $metadata['author'] ?? ''
            ]);
            $message = "Plugin installed successfully";
        }
        
        $_SESSION['message'] = $message;
        header("Location: ../../admin_dashboard.php?page=plugins");
        exit;
        
    } catch (Exception $e) {
        // Clean up on error
        if (isset($pluginDir) && file_exists($pluginDir)) {
            deleteDirectory($pluginDir);
        }
        
        if (isset($tempFile) && file_exists($tempFile)) {
            @unlink($tempFile);
        }
        
        $_SESSION['error'] = "Plugin installation failed: " . $e->getMessage();
        header("Location: ../../admin_dashboard.php?page=plugins");
        exit;
    }
}

// If not a POST request, redirect back
header("Location: ../../admin_dashboard.php");
exit;

// Helper function to delete directory recursively
//function deleteDirectory($dir) {
//    if (!file_exists($dir)) return;
    
//    $files = new RecursiveIteratorIterator(
//        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
//        RecursiveIteratorIterator::CHILD_FIRST
//    );
    
//    foreach ($files as $fileinfo) {
//        if ($fileinfo->isDir()) {
//            rmdir($fileinfo->getRealPath());
//        } else {
//            unlink($fileinfo->getRealPath());
//        }
//    }
    
//    rmdir($dir);
//}

// In upload_plugin.php, add JS syntax validation
function validate_js_syntax($file_path) {
    $js_content = file_get_contents($file_path);
    
    // Check for common syntax issues
    if (preg_match('/[^\x20-\x7E\r\n\t]/', $js_content)) {
        throw new Exception("JavaScript file contains invalid characters: " . basename($file_path));
    }
    
    // Check for unclosed strings or comments
    $string_count = substr_count($js_content, '"') + substr_count($js_content, "'");
    if ($string_count % 2 !== 0) {
        throw new Exception("JavaScript file has unclosed strings: " . basename($file_path));
    }
    
    return true;
}

// Use this function when processing plugin JS files
foreach ($js_files as $js_file) {
    validate_js_syntax($js_file);
}