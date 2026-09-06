<?php
// user_dashboard.php


// admin_dashboard.php and user_dashboard.php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// ✅ Move redirect BEFORE plugins
redirectIfNotLoggedIn();

// Load plugins BEFORE any dashboard logic
require_once __DIR__ . '/includes/load_plugins.php';

// Get user profile data
$stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$profile = $stmt->fetch();

// Get user settings
$stmt = $pdo->prepare("SELECT * FROM settings WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$settings = $stmt->fetch();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    
    if (isset($_POST['update_settings'])) {
        $theme = $_POST['theme'];
        $notifications = isset($_POST['notifications']) ? 1 : 0;
        
        if ($settings) {
            $stmt = $pdo->prepare("UPDATE settings SET theme = ?, notifications = ? WHERE user_id = ?");
            $stmt->execute([$theme, $notifications, $_SESSION['user_id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO settings (user_id, theme, notifications) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $theme, $notifications]);
        }
        $_SESSION['message'] = "Settings updated successfully";
        header("Location: user_dashboard.php");
        exit;
    }
}

// Add to existing POST handling
// Add to your existing POST handling in user_dashboard.php
if (isset($_POST['update_profile'])) {
    try {
        $fullName = $_POST['full_name'];
        $bio = $_POST['bio'];
        $email = $_POST['email']; // Add this line to capture email
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
        
        // Update profile in user_profiles table
        if ($profile) {
            $stmt = $pdo->prepare("UPDATE user_profiles SET full_name = ?, bio = ?, avatar = ? WHERE user_id = ?");
            $stmt->execute([$fullName, $bio, $avatar, $_SESSION['user_id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, full_name, bio, avatar) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $fullName, $bio, $avatar]);
        }
        
        // Commit transaction
        $pdo->commit();
        
        $_SESSION['message'] = "Profile updated successfully";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error updating profile: " . $e->getMessage();
        error_log("Profile update error: " . $e->getMessage());
    }
    
    header("Location: user_dashboard.php?page=profile");
    exit;
}

if (isset($_POST['update_settings'])) {
    $theme = $_POST['theme'];
    $language = $_POST['language'];
    $notifications = isset($_POST['notifications']) ? 1 : 0;
    $newsletter = isset($_POST['newsletter']) ? 1 : 0;
    
    if ($profile) {
        $stmt = $pdo->prepare("UPDATE user_profiles SET language = ? WHERE user_id = ?");
        $stmt->execute([$language, $_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, language) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $language]);
    }
    
    if ($settings) {
        $stmt = $pdo->prepare("UPDATE settings SET theme = ?, notifications = ?, newsletter = ? WHERE user_id = ?");
        $stmt->execute([$theme, $notifications, $newsletter, $_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO settings (user_id, theme, notifications, newsletter) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $theme, $notifications, $newsletter]);
    }
    
    $_SESSION['message'] = "Settings updated successfully";
    header("Location: user_dashboard.php?page=settings");
    exit;
}

// Get unread messages count
$stmt = $pdo->prepare("SELECT COUNT(*) as unread_count FROM messages WHERE receiver_id = ? AND is_read = FALSE");
$stmt->execute([$_SESSION['user_id']]);
$unreadMessages = $stmt->fetch()['unread_count'];

// Handle page navigation
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
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
    <div class="dashboard-container user-dashboard">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>User Panel</h2>
            </div>
<nav class="sidebar-nav">
    <a href="user_dashboard.php" class="<?= $page === 'dashboard' ? 'active' : '' ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="?page=profile" class="<?= $page === 'profile' ? 'active' : '' ?>"><i class="fas fa-user"></i> Profile</a>
    <a href="?page=messages" class="<?= $page === 'messages' ? 'active' : '' ?>"><i class="fas fa-envelope"></i> Messages <?php if ($unreadMessages > 0): ?><span class="badge"><?php echo $unreadMessages; ?></span><?php endif; ?></a>
    
    <!-- Render User Plugin Menus -->
    <?= render_plugin_menus('user_menu') ?>
    
    <a href="?page=settings" class="<?= $page === 'settings' ? 'active' : '' ?>"><i class="fas fa-cog"></i> Settings</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</nav>
        </aside>
        
        <main class="main-content">
            <?php do_action('user_dashboard_top'); // NEW: Plugin hook ?>
            <header class="main-header">
                <h1>User Dashboard</h1>
                    <div class="user-info">
                        <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <img src="<?= getAvatarUrl($_SESSION['user_id']) ?>" 
                            alt="User Avatar" 
                            class="avatar"
                            onerror="this.src='<?= BASE_URL ?>/assets/images/default-avatar.png'">
                    </div>
            </header>

            <!-- ADD THIS HOOK CALL -->
            <!-- do_action('user_dashboard_top'); -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
            <?php endif; ?>
            
            <?php if ($page === 'dashboard'): ?>
                <div class="welcome-message">
                    <h2>Hello, <?php echo htmlspecialchars($profile['full_name'] ?? $_SESSION['username']); ?>!</h2>
                    <p><?php echo htmlspecialchars($profile['bio'] ?? 'Welcome to your personal dashboard'); ?></p>
                </div>
                
                <div class="dashboard-grid">
                    <div class="card">
                        <h3><i class="fas fa-user"></i> Your Profile</h3>
                        <p><?php echo htmlspecialchars($profile['full_name'] ?? 'Not set'); ?></p>
                        <a href="?page=profile" class="btn-action">Edit Profile</a>
                    </div>
                    <div class="card">
                        <h3><i class="fas fa-cog"></i> Account Settings</h3>
                        <p>Theme: <?php echo ucfirst($settings['theme'] ?? 'light'); ?></p>
                        <a href="?page=settings" class="btn-action">Settings</a>
                    </div>
                    <div class="card">
                        <h3><i class="fas fa-envelope"></i> Notifications</h3>
                        <p><?php echo $unreadMessages; ?> unread messages</p>
                        <a href="?page=messages" class="btn-action">View All</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="content-section">
                    <?php 
                    // Include the requested page
                    switch ($page) {
                        case 'profile':
                            include 'includes/user/profile.php';
                            break;
                        case 'messages':
                            include 'includes/user/messages.php';
                            break;
                        case 'settings':
                            include 'includes/user/settings.php';
                            break;
                    }
                    ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <!-- Before </body> tag -->
<?php do_action('user_footer'); ?>
<?php print_scripts(true); ?>

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
// Enhanced plugin error handling and recovery
(function() {
    'use strict';
    
    // Store original console.error
    const originalConsoleError = console.error;
    let pluginErrors = [];
    
    // Capture plugin errors
    console.error = function() {
        const args = Array.from(arguments);
        const errorMsg = args.join(' ');
        
        // Check if it's a plugin-related error
        if (errorMsg.includes('plugins/') || errorMsg.includes('jQuery') || errorMsg.includes('$ is not defined')) {
            pluginErrors.push({
                message: errorMsg,
                timestamp: new Date().toISOString(),
                url: window.location.href
            });
            
            console.warn('Plugin error captured:', errorMsg);
            
            // Try to recover from jQuery errors
            if (errorMsg.includes('jQuery') || errorMsg.includes('$ is not defined')) {
                recoverFromjQueryError();
            }
        }
        
        // Call original console.error
        originalConsoleError.apply(console, args);
    };
    
    function recoverFromjQueryError() {
        if (typeof jQuery === 'undefined') {
            console.log('Attempting to recover missing jQuery...');
            
            // Method 1: Try to load jQuery dynamically
            if (!window.jQueryRecoveryAttempted) {
                window.jQueryRecoveryAttempted = true;
                
                const script = document.createElement('script');
                script.src = 'https://code.jquery.com/jquery-3.6.0.min.js';
                script.integrity = 'sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=';
                script.crossOrigin = 'anonymous';
                script.onload = function() {
                    console.log('jQuery recovery successful');
                    // Re-trigger any plugin initialization
                    if (typeof window.pluginRetryInit === 'function') {
                        window.pluginRetryInit();
                    }
                };
                script.onerror = function() {
                    console.error('jQuery recovery failed');
                };
                
                document.head.appendChild(script);
            }
        }
    }
    
    // Global function for plugins to call when they need jQuery
    window.whenJqueryReady = function(callback) {
        if (typeof jQuery !== 'undefined') {
            callback(jQuery);
        } else {
            // Wait for jQuery to load
            const checkJquery = setInterval(function() {
                if (typeof jQuery !== 'undefined') {
                    clearInterval(checkJquery);
                    callback(jQuery);
                }
            }, 100);
            
            // Timeout after 5 seconds
            setTimeout(function() {
                clearInterval(checkJquery);
                console.warn('jQuery wait timeout');
            }, 5000);
        }
    };
    
    // Provide global plugin error reporting
    window.reportPluginError = function(pluginName, error) {
        console.error(`Plugin ${pluginName} error:`, error);
        
        // You could send this to your server for logging
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            console.groupCollapsed('Plugin Error Details');
            console.error('Plugin:', pluginName);
            console.error('Error:', error);
            console.error('Stack:', error.stack);
            console.groupEnd();
        }
    };
    
    // Safe plugin initialization wrapper
    window.safePluginInit = function(pluginName, initFunction) {
        try {
            whenJqueryReady(function($) {
                try {
                    initFunction($);
                    console.log(`Plugin ${pluginName} initialized successfully`);
                } catch (e) {
                    reportPluginError(pluginName, e);
                }
            });
        } catch (e) {
            reportPluginError(pluginName, e);
        }
    };
    
    console.log('Plugin error handler installed');
})();
</script>


<script>
// Update header avatar when profile picture changes
document.addEventListener('DOMContentLoaded', function() {
    const headerAvatar = document.querySelector('.user-info .avatar');
    const profileForm = document.querySelector('.profile-form');
    
    if (profileForm) {
        const avatarInput = profileForm.querySelector('input[name="avatar"]');
        
        avatarInput.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    // Update both preview and header avatar
                    document.getElementById('avatarPreview').src = event.target.result;
                    headerAvatar.src = event.target.result;
                }
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    }
});


</script>
<script>
// Enhanced jQuery loading with better error handling
(function() {
    'use strict';
    
    console.log('🔧 Starting jQuery loading process...');
    
    // Method 1: Check if jQuery is already loaded
    if (typeof jQuery !== 'undefined') {
        console.log('✅ jQuery already loaded');
        window.dispatchEvent(new Event('jqueryReady'));
        return;
    }
    
    // Method 2: Check if jQuery is loading
    const existingJQueryScripts = document.querySelectorAll('script[src*="jquery"]');
    if (existingJQueryScripts.length > 0) {
        console.log('⏳ jQuery is loading, waiting...');
        waitForJQuery();
        return;
    }
    
    // Method 3: Load jQuery ourselves
    console.log('📥 Loading jQuery...');
    const script = document.createElement('script');
    script.src = 'https://code.jquery.com/jquery-3.6.0.min.js';
    script.integrity = 'sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=';
    script.crossOrigin = 'anonymous';
    script.onload = function() {
        console.log('✅ jQuery loaded successfully');
        window.dispatchEvent(new Event('jqueryReady'));
        initializeAfterJQuery();
    };
    script.onerror = function() {
        console.error('❌ Failed to load jQuery');
        // Try fallback CDN
        loadJQueryFallback();
    };
    document.head.appendChild(script);
    
    function loadJQueryFallback() {
        console.log('🔄 Trying jQuery fallback CDN...');
        const fallbackScript = document.createElement('script');
        fallbackScript.src = 'https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js';
        fallbackScript.onload = function() {
            console.log('✅ jQuery loaded via fallback CDN');
            window.dispatchEvent(new Event('jqueryReady'));
            initializeAfterJQuery();
        };
        fallbackScript.onerror = function() {
            console.error('❌ All jQuery CDNs failed');
        };
        document.head.appendChild(fallbackScript);
    }
    
    function waitForJQuery() {
        const checkInterval = setInterval(() => {
            if (typeof jQuery !== 'undefined') {
                clearInterval(checkInterval);
                console.log('✅ jQuery finished loading');
                window.dispatchEvent(new Event('jqueryReady'));
                initializeAfterJQuery();
            }
        }, 100);
        
        setTimeout(() => {
            clearInterval(checkInterval);
            console.warn('⚠️ jQuery wait timeout');
        }, 10000);
    }
    
    function initializeAfterJQuery() {
        console.log('✅ jQuery ready, initializing systems');
        // Initialize DataTables loader first
        if (typeof DataTablesLoader !== 'undefined') {
            DataTablesLoader.load();
        }
        // Then initialize plugin safety system
        if (typeof window.pluginSafetySystem !== 'undefined') {
            setTimeout(() => {
                window.pluginSafetySystem.loadAllReady();
            }, 100);
        }
    }
})();
</script>

<!-- Before </body> in both dashboard files -->
<script>
// FINAL PLUGIN LOADER - ensures everything loads
(function() {
    'use strict';
    
    console.log('🔧 Final plugin loader starting...');
    
    // Check jQuery
    if (typeof jQuery === 'undefined') {
        console.error('🆘 CRITICAL: jQuery not loaded, loading emergency version');
        const script = document.createElement('script');
        script.src = 'https://code.jquery.com/jquery-3.6.0.min.js';
        script.onload = function() {
            console.log('🆘 EMERGENCY jQuery loaded');
            loadAllPlugins();
        };
        document.head.appendChild(script);
    } else {
        loadAllPlugins();
    }
    
    function loadAllPlugins() {
        console.log('🚀 Loading all plugins...');
        
        // Method 1: Use safety system
        if (typeof pluginSafetySystem !== 'undefined') {
            console.log('✅ Using safety system to load plugins');
            pluginSafetySystem.loadAll();
            return;
        }
        
        // Method 2: Manual plugin loading
        console.log('⚠️ Safety system not available, loading plugins manually');
        
        // Find all plugin scripts that haven't loaded
        const scripts = document.querySelectorAll('script[src*="plugins/"]');
        scripts.forEach(script => {
            if (!script.loaded && script.src) {
                console.log('📦 Loading plugin manually:', script.src);
                const newScript = document.createElement('script');
                newScript.src = script.src;
                newScript.onload = () => console.log('✅ Manual load success:', script.src);
                newScript.onerror = () => console.error('❌ Manual load failed:', script.src);
                document.head.appendChild(newScript);
            }
        });
    }
    
    // Also try loading after a short delay
    setTimeout(loadAllPlugins, 1000);
})();
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
</body>
</html>