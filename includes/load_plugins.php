<?php
// includes/load_plugins.php

// Add this at the top of load_plugins.php
if (!defined('WPINC')) {
    define('WPINC', 'wp-includes');
}

// ADD THIS AT THE TOP
global $plugin_menus;
$plugin_menus = [
    'admin_menu' => [],
    'user_menu' => []
];

// Add this at the top
require_once __DIR__ . '/helpers.php';

function get_plugin_main_class($file_path) {
    // Naively guess the main class by file name, e.g. billing-waybills.php → Billing_Waybills
    $filename = basename($file_path, '.php');
    return str_replace(' ', '_', ucwords(str_replace('-', ' ', $filename)));
}

function is_plugin_class_loaded($class_name) {
    global $loaded_plugin_classes;
    return in_array($class_name, $loaded_plugin_classes) || class_exists($class_name);
}



define('PLUGIN_DIR', __DIR__ . '/../plugins');

if (!function_exists('add_shortcode')) {
    function add_shortcode($tag, $callback) {
        // Optional: Store it globally for future use if you want to simulate it
        global $shortcodes;
        if (!isset($shortcodes)) {
            $shortcodes = [];
        }
        $shortcodes[$tag] = $callback;
        
        // You can extend this to parse shortcodes manually later if needed
    }
}

// Add this to includes/load_plugins.php right after add_shortcode

// Fix: wp_localize_script shim to avoid crashing in non-WP environment

// Add this after WordPress shim functions

if (!function_exists('wp_localize_script')) {
    function wp_localize_script($handle, $object_name, $l10n) {
        echo "<script>window." . $object_name . " = " . json_encode($l10n) . ";</script>";
    }
}

// Fix: add_menu_page & add_submenu_page to register plugin menu in custom $menu/$submenu global
// Add this to the WordPress Compatibility Shims section
if (!function_exists('add_menu_page')) {
    function add_menu_page($page_title, $menu_title, $capability, $menu_slug, $callback = '', $icon_url = '', $position = null) {
        global $menu;
        $menu[$menu_slug] = [
            'page_title' => $page_title,
            'menu_title' => $menu_title,
            'capability' => $capability,
            'menu_slug'  => $menu_slug,
            'callback'   => $callback,
            'icon_url'   => $icon_url,
            'position'   => $position
        ];
    }
}

if (!function_exists('add_submenu_page')) {
    function add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback = '') {
        global $submenu;
        if (!isset($submenu[$parent_slug])) {
            $submenu[$parent_slug] = [];
        }
        $submenu[$parent_slug][] = [
            'page_title' => $page_title,
            'menu_title' => $menu_title,
            'capability' => $capability,
            'menu_slug'  => $menu_slug,
            'callback'   => $callback
        ];
    }
}

// WordPress Compatibility Shims (only if not running inside WP)
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/'); // fallback

    // Stub WordPress functions to avoid crashing
    if (!function_exists('plugin_dir_path')) {
        function plugin_dir_path($file) {
            return dirname($file) . '/';
        }
    }

    if (!function_exists('add_action')) {
        function add_action($hook, $callback) {
            global $hooks;
            if (!isset($hooks[$hook])) {
                $hooks[$hook] = [];
            }
            $hooks[$hook][] = $callback;
        }
    }

    if (!function_exists('do_action')) {
        function do_action($hook) {
            global $hooks;
            if (isset($hooks[$hook])) {
                foreach ($hooks[$hook] as $callback) {
                    if (is_callable($callback)) {
                        call_user_func($callback);
                    }
                }
            }
        }
    }

    if (!function_exists('register_activation_hook')) {
        function register_activation_hook($file, $callback) {
            // Just run it immediately in custom framework
            if (is_callable($callback)) {
                $callback();
            }
        }
    }

    if (!function_exists('register_deactivation_hook')) {
        function register_deactivation_hook($file, $callback) {
            // Optional: no-op in your framework
        }
    }

    if (!function_exists('add_filter')) {
        function add_filter($hook, $callback) {
            // No-op or register in a similar way as hooks
        }
    }

    if (!function_exists('current_user_can')) {
        function current_user_can($capability) {
            // Assume admin in custom dashboard
            return true;
        }
    }

    if (!function_exists('wp_send_json')) {
        function wp_send_json($response) {
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    }

    if (!function_exists('wp_send_json_error')) {
        function wp_send_json_error($message = null, $code = 400) {
            http_response_code($code);
            wp_send_json(['success' => false, 'data' => $message]);
        }
    }

    if (!function_exists('wp_send_json_success')) {
        function wp_send_json_success($data = null) {
            wp_send_json(['success' => true, 'data' => $data]);
        }
    }

    if (!function_exists('wp_die')) {
        function wp_die($message = '') {
            echo '<div style="color:red;"><strong>wp_die:</strong> ' . htmlspecialchars($message) . '</div>';
            exit;
        }
    }

    // Replace existing dbDelta function with this:
    if (!function_exists('dbDelta')) {
        function dbDelta($sql) {
            global $pdo;
            
            // Normalize SQL formatting
            $sql = preg_replace('/\s+/', ' ', $sql);
            $sql = trim($sql, '; ');
            
            // Split into individual queries
            $queries = preg_split('/;\s*(?=CREATE|ALTER|DROP|INSERT|UPDATE|DELETE)/i', $sql);
            
            foreach ($queries as $query) {
                $query = trim($query);
                if (empty($query)) continue;
                
                // Handle CREATE TABLE specifically
                if (preg_match('/^CREATE\s+TABLE\s+/i', $query)) {
                    // Convert to CREATE TABLE IF NOT EXISTS
                    $query = preg_replace(
                        '/^CREATE\s+TABLE\s+(\S+)/i', 
                        'CREATE TABLE IF NOT EXISTS $1', 
                        $query
                    );
                    
                    // Ensure proper column definitions
                    $query = preg_replace_callback(
                        '/\(\s*(.*?)\s*\)/s',
                        function($matches) {
                            $columns = preg_replace('/,\s*\)/', ')', $matches[1]);
                            return '(' . trim($columns) . ')';
                        },
                        $query
                    );
                }
                
                try {
                    $pdo->exec($query);
                } catch (PDOException $e) {
                    // Handle specific MySQL errors
                    if ($e->errorInfo[1] === 1050) {
                        // Table already exists - ignore
                        continue;
                    } elseif ($e->errorInfo[1] === 1064) {
                        // Syntax error - log but don't break execution
                        error_log("SQL Syntax Error: " . $e->getMessage());
                        error_log("Problematic Query: " . $query);
                        continue;
                    }
                    
                    // For other errors, display notification
                    echo '<div style="color:red;">DB Error: ' . $e->getMessage() . '</div>';
                }
            }
        }
    }

    if (!isset($GLOBALS['wpdb'])) {
        $GLOBALS['wpdb'] = new class {
            public $prefix = 'wp_';
            public $last_error = '';
            public $last_query = '';
            public $num_rows = 0;
            public $last_result = [];

            public function get_results($query, $output = OBJECT) {
                global $pdo;
                try {
                    $stmt = $pdo->query($query);
                    $this->last_query = $query;
                    
                    if (!$stmt) {
                        $this->last_error = $pdo->errorInfo()[2];
                        return [];
                    }
                    
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $this->num_rows = count($results);
                    
                    switch ($output) {
                        case ARRAY_A: return $results;
                        case OBJECT: return array_map(function($i) { return (object)$i; }, $results);
                        case ARRAY_N: return array_map('array_values', $results);
                        default: return $results;
                    }
                } catch (PDOException $e) {
                    $this->last_error = $e->getMessage();
                    return [];
                }
            }

            // ADD MISSING METHODS
            public function get_var($query = null, $col = 0, $row = 0) {
                $results = $this->get_results($query, ARRAY_N);
                return $results[$row][$col] ?? null;
            }

            public function get_col($query = null, $col = 0) {
                $results = $this->get_results($query, ARRAY_N);
                return array_column($results, $col);
            }

            public function get_row($query = null, $output = OBJECT) {
                $results = $this->get_results($query, $output);
                return $results[0] ?? null;
            }

            // FIXED PREPARE METHOD
            public function prepare($query, ...$args) {
                // Handle array arguments
                if (count($args) === 1 && is_array($args[0])) {
                    $args = $args[0];
                }
                
                return vsprintf(str_replace(['%s', '%d'], ['"%s"', '%d'], $query), 
                    array_map([$this, 'escape'], $args)
                );
            }

            public function escape($data) {
                global $pdo;
                return $pdo->quote($data);
            }

            public function get_charset_collate() {
                return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
            }
        };
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '') {
        // Adjust this to match your custom admin URL structure
        $base = '/admin_dashboard.php';
        if (!empty($path)) {
            if (strpos($path, '?') !== false) {
                return $base . '&' . ltrim($path, '?');
            } else {
                return $base . '?page=' . ltrim($path, '/');
            }
        }
        return $base;
    }
}

// Load all plugins
global $hooks;
$hooks = [];

// STEP 1: Load active plugin slugs from DB
require_once __DIR__ . '/plugin_loader.php';

$config_path = 'includes/config.php';

if (file_exists($config_path)) {
    require_once $config_path;
} else {
    die("Missing config file at $config_path");
}

$active_plugins = [];
$stmt = $pdo->query("SELECT slug FROM plugins WHERE active = 1");
while ($plugin = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $active_plugins[] = $plugin['slug'];
}

global $loaded_plugin_classes;
$loaded_plugin_classes = [];

$declared_before = get_declared_classes();

global $loaded_plugins;
$loaded_plugins = [];
// Initialize $new_classes before the loop
$new_classes = [];

// STEP 2: Load each plugin's main file ONLY ONCE
foreach ($active_plugins as $slug) {
    if (in_array($slug, $loaded_plugins)) continue;

    $plugin_main = PLUGIN_DIR . '/' . $slug . '/' . $slug . '.php';

    if (file_exists($plugin_main)) {
        // ✅ Use error_log instead of echo for debugging
        error_log("Loading plugin: $slug");

        // Track declared classes BEFORE plugin load
        $classes_before = get_declared_classes();

        // Add after plugin is loaded
        $plugin_path = PLUGIN_DIR . '/' . $slug;
        $plugin_url = PLUGINS_URL . '/' . $slug;
        
        // Find and auto-enqueue assets
        $assets = find_plugin_assets($plugin_path, $plugin_url);

        $assets['js'] = order_js_files_by_dependencies($assets['js']);

        // Debug output
        error_log("[Plugin Loader] Found assets for {$slug}: " . 
                count($assets['css']) . " CSS, " . 
                count($assets['js']) . " JS files");

        // Enqueue CSS assets
        foreach ($assets['css'] as $css_url) {
            $handle = 'plugin-' . $slug . '-' . md5($css_url);
            
            // Try to detect if it's an admin CSS file
            $is_admin_css = (strpos($css_url, 'admin') !== false || 
                            strpos(basename($css_url), 'admin') !== false);
            
            if ($is_admin_css) {
                add_action('admin_head', function() use ($handle, $css_url) {
                    enqueue_style($handle, $css_url);
                });
            } else {
                add_action('user_head', function() use ($handle, $css_url) {
                    enqueue_style($handle, $css_url);
                });
                // Also add to admin for compatibility
                add_action('admin_head', function() use ($handle, $css_url) {
                    enqueue_style($handle, $css_url);
                });
            }
        }

// Update the JS enqueueing section to use dependencies
// In includes/load_plugins.php - Replace the JS registration section
foreach ($assets['js'] as $js_url) {
    $handle = 'plugin-' . $slug . '-' . sanitize_title(basename($js_url, '.js'));
    
    // Clean URL
    $clean_js_url = $js_url;
    if (strpos($clean_js_url, 'callback=debugPluginJS') !== false) {
        $clean_js_url = str_replace('callback=debugPluginJS', '', $clean_js_url);
        $clean_js_url = rtrim($clean_js_url, '?&');
    }
    
    // Get file path for dependency detection
    $js_relative_path = str_replace($plugin_url . '/', '', $clean_js_url);
    $js_full_path = $plugin_path . '/' . $js_relative_path;
    
    if (!file_exists($js_full_path)) {
        error_log("JS file not found: " . $js_full_path);
        continue;
    }

    // Detect dependencies
    $dependencies = detect_js_dependencies($js_full_path);
    $needs_jquery = false;
    $needs_datatables = in_array('datatables', $dependencies);
    
    // Check for jQuery usage
    $js_content = @file_get_contents($js_full_path);
    if ($js_content !== false) {
        $jquery_indicators = ['jQuery', '$', '$.', 'jQuery.', '.ready', '$(document)', '$(function', 'jQuery(function'];
        foreach ($jquery_indicators as $indicator) {
            if (strpos($js_content, $indicator) !== false) {
                $needs_jquery = true;
                break;
            }
        }
    }
    
    $plugin_name = basename($js_url, '.js');
    
    // Build dependencies array
    $deps = [];
    if ($needs_jquery) $deps[] = 'jquery';
    if ($needs_datatables) $deps[] = 'datatables';
    
    // Register with safety system - SIMPLIFIED APPROACH
    echo "<script>
    (function() {
        if (typeof pluginSafetySystem !== 'undefined') {
            pluginSafetySystem.register('{$plugin_name}', '{$clean_js_url}', " . json_encode($deps) . ");
        } else {
            // Fallback: load directly after a delay
            setTimeout(function() {
                var script = document.createElement('script');
                script.src = '{$clean_js_url}';
                document.head.appendChild(script);
                console.warn('Loaded plugin without safety system: {$plugin_name}');
            }, 1000);
        }
    })();
    </script>";
    
    // Also enqueue normally as fallback
    enqueue_script($handle, $clean_js_url, $deps, null, true);
}


        try {
            // Now include the plugin file AFTER setting up assets
            include_once $plugin_main;
            
            // Check if main plugin class already exists before including
            $plugin_class = get_plugin_main_class($plugin_main, $slug);
            
            if (!class_exists($plugin_class)) {
                include_once $plugin_main;
            }
            
            $loaded_plugins[] = $slug;

            // After loading, check new classes
            $classes_after = get_declared_classes();
            $new_classes = array_diff($classes_after, $classes_before);
            
            // Only process if we have new classes
            if (!empty($new_classes)) {
                foreach ($new_classes as $class) {
                    if (method_exists($class, 'init')) {
                        call_user_func([$class, 'init']);
                    }
                }
            }

        } catch (Throwable $e) {
            // echo '<div style="color:red; background:#ffeeee; padding:10px; font-weight:bold;">';
            // echo 'Plugin <strong>' . htmlspecialchars($slug) . '</strong> crashed: ' . $e->getMessage();
            // echo '</div>';

            // Replace with:
            error_log("Plugin $slug crashed: " . $e->getMessage());
        }
    }
}

// Only process if we have new classes
if (!empty($new_classes)) {
    foreach ($new_classes as $class) {
        if (method_exists($class, 'init')) {
            call_user_func([$class, 'init']);
        }
    }
}

// Add this after including plugin files
foreach ($new_classes as $class) {
    if (method_exists($class, 'init')) {
        call_user_func([$class, 'init']);
    }
}

// STEP 3: Trigger plugin hooks correctly
// After plugins are loaded
do_action('plugins_loaded');   // plugin init phase

// Initialize WordPress-style settings arrays
global $wp_settings_sections, $wp_settings_fields;
$wp_settings_sections = [];
$wp_settings_fields = [];

// Initialize menu arrays if not set
global $menu, $submenu;
// Ensure arrays are initialized
if (!isset($menu)) $menu = [];
if (!isset($submenu)) $submenu = []; // Add this line

$plugin_menus = [];

$plugin_base_path = __DIR__ . '/../plugins/';

foreach ($active_plugins as $plugin_folder) {
    $plugin_init = $plugin_base_path . $plugin_folder . '/init.php';

    if (file_exists($plugin_init)) {
        include_once $plugin_init;

        // If plugin provides a menu
        if (function_exists('register_plugin_menu')) {
            $menu = register_plugin_menu();
            if (isset($menu['name']) && isset($menu['file'])) {
                $plugin_menus[] = $menu;
            }
        }
    }
}