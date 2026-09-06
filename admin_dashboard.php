<?php

// admin_dashboard.php

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo "<div style='background: #ffdddd; color: red; padding: 20px; font-weight: bold;'>";
        echo "Fatal Error: " . htmlspecialchars($error['message']) . "<br>";
        echo "File: " . htmlspecialchars($error['file']) . "<br>";
        echo "Line: " . htmlspecialchars($error['line']) . "<br>";
        echo "</div>";
    }
});


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// admin_dashboard.php and user_dashboard.php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// ✅ Move redirect BEFORE plugins
redirectIfNotLoggedIn();

// Load plugins BEFORE any dashboard logic
try {
    require_once __DIR__ . '/includes/load_plugins.php';
} catch (Throwable $e) {
    echo '<div style="color:red; font-weight:bold;">Plugin loader crashed: ' . $e->getMessage() . '</div>';
}

// Add this right before rendering menus:
do_action('plugins_loaded');

global $menu, $submenu;
$menu = [];
$submenu = [];

// Trigger the admin_menu hook so plugins can register their menus admin menu creation IMMEDIATELY after plugins
do_action('admin_menu');

redirectIfNotAdmin();

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new user functionality
    if (isset($_POST['add_user'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $role = $_POST['role'];
        
        try {
            // Validate inputs
            if (empty($username) || empty($email) || empty($password)) {
                throw new Exception("All fields are required");
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format");
            }
            
            if (strlen($password) < 8) {
                throw new Exception("Password must be at least 8 characters");
            }
            
            // Check if username or email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                throw new Exception("Username or email already exists");
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            
            // Insert new user
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, $hashedPassword, $role]);
            
            $_SESSION['message'] = "User added successfully";
            header("Location: admin_dashboard.php?page=users");
            exit;
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header("Location: admin_dashboard.php?page=add_user");
            exit;
        }
    }
    
    // Existing user deletion functionality
    if (isset($_POST['delete_user'])) {
        $userId = $_POST['user_id'];
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $_SESSION['message'] = "User deleted successfully";
        header("Location: admin_dashboard.php");
        exit;
    }
    
    // Existing role update functionality
    if (isset($_POST['update_role'])) {
        $userId = $_POST['user_id'];
        $newRole = $_POST['new_role'];
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$newRole, $userId]);
        $_SESSION['message'] = "User role updated successfully";
        header("Location: admin_dashboard.php");
        exit;
    }
}

// Existing settings handling
if (isset($_POST['save_settings'])) {
    $settings = [
        'site_name' => $_POST['site_name'],
        'users_can_register' => $_POST['users_can_register'],
        'default_role' => $_POST['default_role'],
        'items_per_page' => $_POST['items_per_page']
    ];
    
    foreach ($settings as $name => $value) {
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_name, setting_value) 
                              VALUES (?, ?) 
                              ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$name, $value, $value]);
    }
    
    $_SESSION['message'] = "Settings updated successfully";
    header("Location: admin_dashboard.php?page=settings");
    exit;
}

// Existing profile update functionality
if (isset($_POST['update_admin_profile'])) {
    try {
        $email = $_POST['email'];
        $avatar = $_POST['existing_avatar'] ?? null;
        
        // Handle file upload if a new avatar was provided
        if (!empty($_FILES['avatar']['name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $newAvatar = handleFileUpload($_FILES['avatar'], $_SESSION['user_id']);
            
            // Delete old avatar if it exists
            if (!empty($avatar)) {
                $oldAvatarPath = $_SERVER['DOCUMENT_ROOT'] . '/uploads/avatars/' . $avatar;
                if (file_exists($oldAvatarPath)) {
                    unlink($oldAvatarPath);
                }
            }
            $avatar = $newAvatar;
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Update email in users table
        $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        
        // Update or create profile in user_profiles table
        $stmt = $pdo->prepare("SELECT 1 FROM user_profiles WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE user_profiles SET avatar = ? WHERE user_id = ?");
            $stmt->execute([$avatar, $_SESSION['user_id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, avatar) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $avatar]);
        }
        
        // Commit transaction
        $pdo->commit();
        
        $_SESSION['message'] = "Profile updated successfully";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error updating profile: " . $e->getMessage();
        error_log("Admin profile update error: " . $e->getMessage());
    }
    
    header("Location: admin_dashboard.php?page=profile");
    exit;
}

// Get all users
$stmt = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
$totalUsers = $stmt->fetch()['total_users'];

$stmt = $pdo->query("SELECT COUNT(*) as total_admins FROM users WHERE role = 'admin'");
$totalAdmins = $stmt->fetch()['total_admins'];

$stmt = $pdo->query("SELECT COUNT(*) as total_messages FROM messages");
$totalMessages = $stmt->fetch()['total_messages'];

// Handle page navigation
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$current_page = $page; // Create alias for consistency
$pageHandled = false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    
    <!-- Load CSS first -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/plugins.css">
    
    <!-- Define global AJAX variables FIRST -->
    <script>
    window.ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    window.ajax_assign = window.ajaxurl;
    window.ajax_object = {
        ajaxurl: window.ajaxurl,
        nonce: '<?php echo wp_create_nonce('ajax-nonce'); ?>'
    };
    console.log('🔧 Global AJAX variables defined');
    </script>
    
    <!-- Load safety systems BEFORE jQuery -->
    <script src="<?php echo BASE_URL; ?>/assets/js/datatables-loader.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/plugin-interceptor.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/plugin-safety-wrapper.js"></script>
    
    <!-- Load jQuery AFTER safety systems -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Plugin hooks and styles -->
    <?php do_action('admin_head'); ?>
    <?php print_styles(); ?>
    <?php print_scripts(false); ?>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="admin_dashboard.php" class="<?= $page === 'dashboard' ? 'active' : '' ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="?page=users" class="<?= $page === 'users' ? 'active' : '' ?>"><i class="fas fa-users"></i> Users</a>
                <a href="?page=add_user" class="<?= $page === 'add_user' ? 'active' : '' ?>"><i class="fas fa-user-plus"></i> Add User</a>
                <a href="?page=plugins" class="<?= $page === 'plugins' ? 'active' : '' ?>"><i class="fas fa-plug"></i> Plugins</a>
                
                <?= render_wordpress_menus() ?>
                <?= render_plugin_menus('admin_menu') ?>
                
                <a href="?page=profile" class="<?= $page === 'profile' ? 'active' : '' ?>"><i class="fas fa-user"></i> My Profile</a>
                <a href="?page=messages" class="<?= $page === 'messages' ? 'active' : '' ?>"><i class="fas fa-envelope"></i> Messages</a>
                <a href="?page=settings" class="<?= $page === 'settings' ? 'active' : '' ?>"><i class="fas fa-cog"></i> Settings</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>
        
        <main class="main-content">
            <?php do_action('admin_dashboard_top'); ?>
            <header class="main-header">
                <h1>Admin Dashboard</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (Admin)</span>
                    <img src="<?= getAvatarUrl($_SESSION['user_id']) ?>" 
                         alt="Admin Avatar" 
                         class="avatar"
                         onerror="this.src='<?= BASE_URL ?>/assets/images/default-avatar.png'">
                </div>
            </header>
            
            <!-- Page content handling -->
            <?php
            if (isset($_GET['page'])) {
                $pageHandled = false;
                $current_page = $_GET['page'] ?? '';

                // 1. Try WordPress-style menu callbacks first
                global $menu, $submenu;
                
                // Check main menus
                foreach ($menu as $parent_slug => $menu_item) {
                    if ($parent_slug === $current_page && isset($menu_item['callback']) && is_callable($menu_item['callback'])) {
                        call_user_func($menu_item['callback']);
                        $pageHandled = true;
                        break;
                    }
                }
                
                // Check submenus
                if (!$pageHandled) {
                    foreach ($submenu as $parent => $submenus) {
                        foreach ($submenus as $submenu_item) {
                            if (isset($submenu_item['menu_slug']) && 
                                $submenu_item['menu_slug'] === $current_page && 
                                isset($submenu_item['callback']) && 
                                is_callable($submenu_item['callback'])) {
                                call_user_func($submenu_item['callback']);
                                $pageHandled = true;
                                break 2;
                            }
                        }
                    }
                }

                // 2. Try core pages
                if (!$pageHandled) {
                    switch ($current_page) {
                        case 'users':
                            include 'includes/admin/users.php';
                            $pageHandled = true;
                            break;
                        case 'add_user':
                            include 'includes/admin/add_user.php';
                            $pageHandled = true;
                            break;
                        case 'plugins':
                            include 'includes/admin/plugins.php';
                            $pageHandled = true;
                            break;
                        case 'profile':
                            include 'includes/admin/profile.php';
                            $pageHandled = true;
                            break;
                        case 'messages':
                            include 'includes/admin/messages.php';
                            $pageHandled = true;
                            break;
                        case 'settings':
                            include 'includes/admin/settings.php';
                            $pageHandled = true;
                            break;
                    }
                }

                // 3. Try custom plugin pages
                if (!$pageHandled && !empty($GLOBALS['plugin_menus']['admin_menu'])) {
                    foreach ($GLOBALS['plugin_menus']['admin_menu'] as $menu) {
                        if ($current_page === $menu['page']) {
                            $callback_function = 'plugin_' . sanitize_title($menu['page']) . '_callback';
                            
                            if (function_exists($callback_function)) {
                                call_user_func($callback_function);
                                $pageHandled = true;
                                break;
                            }
                        }
                    }
                }

                // 4. Show error if still not handled
                if (!$pageHandled) {
                    echo '<div class="alert error">Page not found: ' . 
                        htmlspecialchars($current_page) . '</div>';
                }
            }
            ?>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <?php if ($page === 'dashboard'): ?>
                <div class="dashboard-grid">
                    <div class="card">
                        <h3><i class="fas fa-users"></i> Total Users</h3>
                        <p><?php echo $totalUsers; ?></p>
                        <a href="?page=users" class="btn-action">View All</a>
                    </div>
                    <div class="card">
                        <h3><i class="fas fa-user-shield"></i> Admins</h3>
                        <p><?php echo $totalAdmins; ?></p>
                        <a href="?page=users&filter=admin" class="btn-action">Manage</a>
                    </div>
                    <div class="card">
                        <h3><i class="fas fa-envelope"></i> Messages</h3>
                        <p><?php echo $totalMessages; ?></p>
                        <a href="?page=messages" class="btn-action">View</a>
                    </div>
                    <div class="card">
                        <h3><i class="fas fa-user-plus"></i> Add User</h3>
                        <p>Create new accounts</p>
                        <a href="?page=add_user" class="btn-action">Add Now</a>
                    </div>
                </div>
                
                <div class="users-table">
                    <h2><i class="fas fa-user-cog"></i> User Management</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <form method="POST" class="role-form">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <select name="new_role" class="role-select">
                                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                        </select>
                                        <button type="submit" name="update_role" class="btn-update">Update</button>
                                    </form>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Are you sure?')">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="delete_user" class="btn-delete"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
        // Add this to track where the alert is coming from
        console.log("🔧 Debug: Checking for plugin alerts...");

        // Override alert temporarily to see the source
        const originalAlert = window.alert;
        window.alert = function(message) {
            console.trace("Alert called:", message);
            if (message === "No roles found!") {
                console.error("🚨 'No roles found!' alert detected. Stack trace above.");
                // Don't show the alert, just log it
                return;
            }
            originalAlert(message);
        };

        // Also check for any plugin errors
        window.addEventListener('error', function(e) {
            console.error('Plugin Error:', e.error, 'in', e.filename);
        });
    </script>

    <script>
        // Simple JavaScript for better UX
        document.querySelectorAll('.role-select').forEach(select => {
            select.addEventListener('change', function() {
                this.closest('form').submit();
            });
        });

        // Update header avatar when viewing user profiles
        document.addEventListener('DOMContentLoaded', function() {
            const headerAvatar = document.querySelector('.user-info .avatar');
            
            if (window.location.search.includes('page=profile')) {
                headerAvatar.src = headerAvatar.src.split('?')[0] + '?' + new Date().getTime();
            }
        });

        // Toggle submenus
        document.querySelectorAll('.wp-menu-group').forEach(group => {
            const link = group.querySelector('a');
            const submenu = group.querySelector('.wp-submenu');
            
            if (submenu) {
                link.addEventListener('click', function(e) {
                    if (e.target.closest('.wp-menu-group')) {
                        e.preventDefault();
                        const isActive = this.classList.contains('active');
                        this.classList.toggle('active', !isActive);
                        submenu.style.display = isActive ? 'none' : 'block';
                    }
                });
            }
        });
    </script>

    <!-- Footer hooks and scripts -->
    <?php do_action('admin_footer'); ?>
    <?php print_scripts(true); ?>

    <!-- SIMPLE plugin initialization - no complex safety systems -->
    <script>
    // Wait for jQuery and initialize plugins
    function initializePlugins() {
        if (typeof jQuery !== 'undefined') {
            console.log('✅ jQuery ready, initializing plugins');
            
            // Your jQuery-dependent plugin code here
            $(document).ready(function() {
                console.log('✅ Document ready, plugins should be working');
                
                // Plugin initialization can happen here
                if (typeof window.pluginInit === 'function') {
                    window.pluginInit();
                }
            });
        } else {
            // Retry after short delay
            setTimeout(initializePlugins, 100);
        }
    }
    
    // Start initialization
    initializePlugins();
    </script>
    <!-- Emergency DataTables Recovery -->
<script>
(function() {
    'use strict';
    
    // Emergency recovery for DataTables
    function emergencyDataTablesRecovery() {
        if (typeof jQuery !== 'undefined' && typeof jQuery.fn.dataTable === 'undefined') {
            console.log('🆘 EMERGENCY: DataTables not found, loading recovery version');
            
            // Load DataTables CSS
            const css = document.createElement('link');
            css.rel = 'stylesheet';
            css.href = 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css';
            document.head.appendChild(css);
            
            // Load DataTables JS
            const script = document.createElement('script');
            script.src = 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js';
            script.onload = function() {
                console.log('🆘 EMERGENCY DataTables recovery successful');
                // Retry plugin initialization
                if (typeof window.pluginSafetySystem !== 'undefined') {
                    window.pluginSafetySystem.loadAllReady();
                }
            };
            document.head.appendChild(script);
        }
    }
    
    // Run immediately
    emergencyDataTablesRecovery();
    
    // Also run after a delay in case plugins load later
    setTimeout(emergencyDataTablesRecovery, 2000);
    setTimeout(emergencyDataTablesRecovery, 5000);
})();
</script>

<!-- Enhanced DataTables Emergency Recovery -->
<script>
(function() {
    'use strict';
    
    // Enhanced emergency recovery for DataTables
    function enhancedDataTablesRecovery() {
        // Check if DataTables is needed but not loaded
        const needsDataTables = document.querySelector('script[src*="datatables"], [data-requires-datatables]');
        
        if (needsDataTables && (typeof jQuery === 'undefined' || typeof jQuery.fn.dataTable === 'undefined')) {
            console.log('🆘 ENHANCED EMERGENCY: DataTables not found, loading recovery version');
            
            // Use DataTablesLoader if available
            if (typeof DataTablesLoader !== 'undefined') {
                DataTablesLoader.load(function() {
                    console.log('🆘 EMERGENCY DataTables recovery via loader successful');
                    // Re-check plugin safety system
                    if (typeof window.pluginSafetySystem !== 'undefined') {
                        setTimeout(() => {
                            window.pluginSafetySystem.loadAllReady();
                        }, 500);
                    }
                });
            } else {
                // Fallback direct loading
                if (!document.querySelector('link[href*="datatables"]')) {
                    const css = document.createElement('link');
                    css.rel = 'stylesheet';
                    css.href = 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css';
                    document.head.appendChild(css);
                }
                
                const script = document.createElement('script');
                script.src = 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js';
                script.onload = function() {
                    console.log('🆘 EMERGENCY DataTables direct recovery successful');
                    if (typeof window.pluginSafetySystem !== 'undefined') {
                        setTimeout(() => {
                            window.pluginSafetySystem.loadAllReady();
                        }, 500);
                    }
                };
                document.head.appendChild(script);
            }
        }
    }
    
    // Run immediately
    enhancedDataTablesRecovery();
    
    // Also run after delays
    setTimeout(enhancedDataTablesRecovery, 2000);
    setTimeout(enhancedDataTablesRecovery, 5000);
    
    // Listen for jQuery ready event and check DataTables again
    window.addEventListener('jqueryReady', function() {
        console.log('jQuery ready event received, checking DataTables');
        setTimeout(enhancedDataTablesRecovery, 100);
    });
})();
</script>
</body>
</html>