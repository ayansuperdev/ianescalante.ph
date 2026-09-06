<?php
// includes/config.php

session_start();

define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', true); // logs only


// Load plugin hook system before calling do_action
require_once __DIR__ . '/plugin_api.php';

// includes/config.php (add after plugin_api.php inclusion)
require_once __DIR__ . '/plugin_loader.php';


// --- HOOK SYSTEM: must come before using do_action ---

// ------------------------------------------------------

// Initialize plugin menus if not already done in plugin_api.php
$GLOBALS['plugin_menus'] = $GLOBALS['plugin_menus'] ?? [
    'admin_menu' => [],
    'user_menu' => []
];



// ✅ Database connection
$host = 'localhost';
$dbname = 'portfolio';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}



// Add this after your PDO connection in config.php
try {
    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'user') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // ADD PLUGINS TABLE HERE
    $pdo->exec("CREATE TABLE IF NOT EXISTS plugins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        description TEXT,
        version VARCHAR(20) NOT NULL DEFAULT '1.0.0',
        author VARCHAR(100),
        active TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Create system_settings table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_name VARCHAR(255) NOT NULL UNIQUE,
        setting_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Create user_profiles table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        avatar VARCHAR(255),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Create password_reset_otps table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_otps (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        otp VARCHAR(10) NOT NULL,
        expires_at DATETIME NOT NULL,
        used TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Check if admin exists, if not create one
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO users (username, email, password, role) 
                   VALUES ('admin', 'admin@example.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')");
        $pdo->exec("INSERT INTO users (username, email, password) 
                   VALUES ('user1', 'user1@example.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')");
    }
} catch (PDOException $e) {
    die("Table creation failed: " . $e->getMessage());
}

// Add this to includes/config.php
function getSetting($name, $default = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_name = ?");
        $stmt->execute([$name]);
        $result = $stmt->fetch();
        
        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

// Add to includes/config.php
function getAvatarUrl($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT avatar FROM user_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $avatar = $stmt->fetchColumn();
        
        if ($avatar) {
            $avatarPath = '/uploads/avatars/' . $avatar;
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . $avatarPath;
            
            if (file_exists($fullPath)) {
                return BASE_URL . $avatarPath;
            }
        }
    } catch (PDOException $e) {
        error_log("Error fetching avatar: " . $e->getMessage());
    }
    
    // Return default avatar if no custom one exists
    return BASE_URL . '/assets/images/default-avatar.png';
}

function handleFileUpload($file, $userId) {

        // Windows-compatible path
    $uploadDir = str_replace('/', DIRECTORY_SEPARATOR, $_SERVER['DOCUMENT_ROOT'] . '/uploads/avatars/');
    
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/avatars/';

        // Debug output
    error_log("Attempting to upload file to: " . $uploadDir);
    error_log("File info: " . print_r($file, true));
    
    // Verify upload directory
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception("Failed to create upload directory");
        }
    }
    
    // Verify directory is writable
    if (!is_writable($uploadDir)) {
        throw new Exception("Upload directory is not writable");
    }

    // Validate file
    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif'
    ];
    $maxSize = 2 * 1024 * 1024; // 2MB

    // Check if file is actually uploaded
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new Exception("Invalid file upload");
    }

    // Check file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!array_key_exists($mime, $allowedTypes)) {
        throw new Exception("Only JPG, PNG, and GIF files are allowed");
    }

    // Check file size
    if ($file['size'] > $maxSize) {
        throw new Exception("File size must be less than 2MB");
    }

    // Generate unique filename
    $extension = $allowedTypes[$mime];
    $filename = 'avatar_' . $userId . '_' . uniqid() . '.' . $extension;
    $targetPath = $uploadDir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception("Failed to move uploaded file");
    }

    // Create resized version (200x200)
    try {
        list($width, $height) = getimagesize($targetPath);
        $newWidth = $newHeight = 200;
        $image = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG/GIF
        if ($mime === 'image/png' || $mime === 'image/gif') {
            imagecolortransparent($image, imagecolorallocatealpha($image, 0, 0, 0, 127));
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        $source = null;
        switch ($mime) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($targetPath);
                break;
            case 'image/png':
                $source = imagecreatefrompng($targetPath);
                break;
            case 'image/gif':
                $source = imagecreatefromgif($targetPath);
                break;
        }

        if (!$source) {
            throw new Exception("Failed to create image resource");
        }

        imagecopyresampled($image, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Save resized image (overwrite original)
        $success = false;
        switch ($mime) {
            case 'image/jpeg':
                $success = imagejpeg($image, $targetPath, 90);
                break;
            case 'image/png':
                $success = imagepng($image, $targetPath);
                break;
            case 'image/gif':
                $success = imagegif($image, $targetPath);
                break;
        }

        imagedestroy($source);
        imagedestroy($image);

        if (!$success) {
            throw new Exception("Failed to save resized image");
        }

        return $filename;
    } catch (Exception $e) {
        // If resizing fails, keep the original but log the error
        error_log("Image resizing failed: " . $e->getMessage());
        return $filename;
    }
}

function cleanupExpiredOtps() {
    global $pdo;
    try {
        $pdo->exec("DELETE FROM password_reset_otps WHERE expires_at < NOW() OR used = 1");
    } catch (PDOException $e) {
        error_log("Error cleaning up OTPs: " . $e->getMessage());
    }
}

// Call this at the start of sensitive operations
cleanupExpiredOtps();

// Detect the base URL dynamically
$base_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', str_replace('\\', '/', __DIR__));
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . $base_path;
define('BASE_URL', rtrim($base_url, '/includes')); // Remove /includes if present

// Add after BASE_URL definition
define('PLUGINS_URL', BASE_URL . '/plugins');


// Add this to the plugin hook system section
$GLOBALS['wp_settings_sections'] = [];
$GLOBALS['wp_settings_fields'] = [];
// Plugin hook system
$plugin_hooks = [];
// Add this after $plugin_hooks initialization
$wp_settings_sections = [];
$wp_settings_fields = [];


// WordPress simulation functions
//function is_admin() {
//    return true;
//}

//function __($text, $domain = 'default') {
//    return $text;
//}

// UPDATE THE register_admin_menu FUNCTION:
// Add these functions if not exists
if (!function_exists('register_admin_menu')) {
    function register_admin_menu($title, $page, $icon = 'fa-plug') {
        $GLOBALS['plugin_menus']['admin_menu'][] = [
            'title' => $title,
            'page' => $page,
            'icon' => $icon
        ];
    }
}

// UPDATE THE register_user_menu FUNCTION:
if (!function_exists('register_user_menu')) {
    function register_user_menu($title, $page, $icon = 'fa-plug') {
        $GLOBALS['plugin_menus']['user_menu'][] = [
            'title' => $title,
            'page' => $page,
            'icon' => $icon
        ];
    }
}

// Initialize plugin system after loading plugins
do_action('plugins_loaded');

// Then initialize menus:
do_action('admin_menu');

// Add this after the existing code in config.php
// WordPress database constants for compatibility
define('OBJECT', 'OBJECT');
define('OBJECT_K', 'OBJECT_K');
define('ARRAY_A', 'ARRAY_A');
define('ARRAY_N', 'ARRAY_N');

// In includes/config.php - Add better debugging
define('DEBUG_PLUGINS', true); // Set to false in production

// Add this function to log plugin errors
function log_plugin_error($plugin_slug, $error_message, $error_type = 'JS') {
    if (DEBUG_PLUGINS) {
        error_log("PLUGIN ERROR [{$plugin_slug}][{$error_type}]: {$error_message}");
    }
    
    // You could also store these in a database table for admin viewing
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO plugin_errors (plugin_slug, error_message, error_type, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$plugin_slug, $error_message, $error_type]);
    } catch (Exception $e) {
        // Silently fail if table doesn't exist
    }
}

// Create plugin_errors table (run once)
function create_plugin_errors_table() {
    global $pdo;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS plugin_errors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plugin_slug VARCHAR(255) NOT NULL,
            error_message TEXT,
            error_type VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            resolved TINYINT(1) DEFAULT 0
        )");
    } catch (Exception $e) {
        // Table creation failed
    }
}

// Call this function once (you can add it to your setup script)
// create_plugin_errors_table();