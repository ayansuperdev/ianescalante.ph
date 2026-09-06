<?php
// includes/plugin_loader.php

class PluginLoader {
    private $active_plugins = [];
    private $plugin_metadata = [];
    private $pdo;
    
    public function __construct($pdo_connection) {
        $this->pdo = $pdo_connection;
        $this->setup_wordpress_shims();
        $this->load_active_plugins_list();
    }
    
    private function setup_wordpress_shims() {
        // Define essential WordPress functions if they don't exist
        if (!function_exists('add_action')) {
            function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
                global $plugin_hooks;
                if (!isset($plugin_hooks[$hook])) {
                    $plugin_hooks[$hook] = [];
                }
                $plugin_hooks[$hook][] = [
                    'callback' => $callback,
                    'priority' => $priority,
                    'accepted_args' => $accepted_args
                ];
            }
        }
        
        if (!function_exists('do_action')) {
            function do_action() {
                global $plugin_hooks;
                $args = func_get_args();
                $hook = array_shift($args);
                
                if (isset($plugin_hooks[$hook])) {
                    // Sort by priority
                    usort($plugin_hooks[$hook], function($a, $b) {
                        return $a['priority'] - $b['priority'];
                    });
                    
                    foreach ($plugin_hooks[$hook] as $hook_data) {
                        call_user_func_array($hook_data['callback'], $args);
                    }
                }
            }
        }
        
        if (!function_exists('add_filter')) {
            function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
                // For now, treat filters the same as actions
                add_action($hook, $callback, $priority, $accepted_args);
            }
        }
        
        if (!function_exists('apply_filters')) {
            function apply_filters($hook, $value) {
                global $plugin_hooks;
                $args = func_get_args();
                array_shift($args); // Remove hook
                
                if (isset($plugin_hooks[$hook])) {
                    // Sort by priority
                    usort($plugin_hooks[$hook], function($a, $b) {
                        return $a['priority'] - $b['priority'];
                    });
                    
                    foreach ($plugin_hooks[$hook] as $hook_data) {
                        $args[0] = call_user_func_array($hook_data['callback'], $args);
                    }
                }
                
                return $args[0];
            }
        }
        
        // Add more essential WordPress functions
        if (!function_exists('register_activation_hook')) {
            function register_activation_hook($file, $callback) {
                // Store activation hooks in a global variable
                global $activation_hooks;
                $activation_hooks[basename(dirname($file))] = $callback;
            }
        }
        
        if (!function_exists('register_deactivation_hook')) {
            function register_deactivation_hook($file, $callback) {
                // Store deactivation hooks in a global variable
                global $deactivation_hooks;
                $deactivation_hooks[basename(dirname($file))] = $callback;
            }
        }
        
        if (!function_exists('wp_enqueue_script')) {
            function wp_enqueue_script($handle, $src = '', $deps = array(), $ver = false, $in_footer = false) {
                global $enqueued_scripts;
                $enqueued_scripts[$handle] = [
                    'src' => $src,
                    'deps' => $deps,
                    'ver' => $ver,
                    'in_footer' => $in_footer
                ];
            }
        }
        
        if (!function_exists('wp_enqueue_style')) {
            function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all') {
                global $enqueued_styles;
                $enqueued_styles[$handle] = [
                    'src' => $src,
                    'deps' => $deps,
                    'ver' => $ver,
                    'media' => $media
                ];
            }
        }
        
        // Initialize global arrays
        $GLOBALS['plugin_hooks'] = $GLOBALS['plugin_hooks'] ?? [];
        $GLOBALS['activation_hooks'] = $GLOBALS['activation_hooks'] ?? [];
        $GLOBALS['deactivation_hooks'] = $GLOBALS['deactivation_hooks'] ?? [];
        $GLOBALS['enqueued_scripts'] = $GLOBALS['enqueued_scripts'] ?? [];
        $GLOBALS['enqueued_styles'] = $GLOBALS['enqueued_styles'] ?? [];
    }
    
    private function load_active_plugins_list() {
        try {
            $stmt = $this->pdo->query("SELECT slug, main_file FROM plugins WHERE active = 1");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as $row) {
                $this->active_plugins[$row['slug']] = $row['main_file'] ?: $row['slug'] . '.php';
            }
        } catch (PDOException $e) {
            error_log("Error loading active plugins: " . $e->getMessage());
            $this->active_plugins = [];
        }
    }
    
    public function load_plugins() {
        foreach ($this->active_plugins as $slug => $main_file) {
            $plugin_path = PLUGIN_DIR . '/' . $slug . '/';
            $main_file_path = $plugin_path . $main_file;
            
            if (file_exists($main_file_path)) {
                $this->extract_plugin_metadata($main_file_path, $slug);
                $this->include_plugin_files($plugin_path, $slug);
                $this->execute_plugin($main_file_path, $slug);
            } else {
                error_log("Plugin main file not found: " . $main_file_path);
            }
        }
        
        // Trigger plugins_loaded action
        do_action('plugins_loaded');
    }
    
    private function extract_plugin_metadata($file_path, $slug) {
        $file_content = file_get_contents($file_path);
        
        // Extract WordPress plugin headers
        $headers = [
            'Plugin Name' => 'Plugin Name',
            'Plugin URI' => 'Plugin URI',
            'Description' => 'Description',
            'Version' => 'Version',
            'Author' => 'Author',
            'Author URI' => 'Author URI',
            'Text Domain' => 'Text Domain',
            'Domain Path' => 'Domain Path',
            'Network' => 'Network',
            'RequiresWP' => 'Requires at least',
            'RequiresPHP' => 'Requires PHP'
        ];
        
        $metadata = [];
        foreach ($headers as $field => $regex) {
            if (preg_match('/^[ \t\/*#@]*' . preg_quote($regex, '/') . ':(.*)$/mi', $file_content, $match)) {
                $metadata[$field] = trim(preg_replace("/\s*(?:\*\/|\?>).*/", '', $match[1]));
            } else {
                $metadata[$field] = '';
            }
        }
        
        $this->plugin_metadata[$slug] = $metadata;
    }
    
    private function include_plugin_files($plugin_path, $slug) {
        // Recursively include all PHP files in the plugin directory
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($plugin_path),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                // Skip the main file as it will be executed separately
                if ($file->getPathname() !== $plugin_path . $this->active_plugins[$slug]) {
                    include_once $file->getPathname();
                }
            }
        }
    }
    
private function execute_plugin($main_file_path, $slug) {
    $plugin_execution = function() use ($main_file_path, $slug) {
        // Set up plugin constants
        if (!defined('PLUGIN_DIR')) define('PLUGIN_DIR', dirname($main_file_path));
        if (!defined('PLUGIN_URL')) define('PLUGIN_URL', PLUGINS_URL . '/' . $slug);
        
        // Check if plugin main class already exists
        $plugin_class = $this->get_plugin_main_class($main_file_path, $slug);
        
        if (!class_exists($plugin_class)) {
            // Execute the main plugin file only if class doesn't exist
            include $main_file_path;
        }
        
        // Initialize plugin if class exists
        if ($plugin_class && class_exists($plugin_class)) {
            if (method_exists($plugin_class, 'init')) {
                call_user_func([$plugin_class, 'init']);
            } elseif (method_exists($plugin_class, '__construct')) {
                new $plugin_class();
            }
        }
    };
    
    try {
        $plugin_execution();
    } catch (Exception $e) {
        error_log("Plugin {$slug} execution error: " . $e->getMessage());
    }
}
    
    private function get_plugin_main_class($file_path, $slug) {
        $file_content = file_get_contents($file_path);
        $classes = [];
        
        // Look for class definitions
        if (preg_match_all('/class\s+(\w+)(?:\s+extends\s+\w+)?(?:\s+implements\s+\w+)?\s*{/', $file_content, $matches)) {
            $classes = $matches[1];
        }
        
        // Try to find the most likely main class
        if (!empty($classes)) {
            // Prefer class names that match the plugin slug
            $slug_class = str_replace('-', '_', ucwords($slug, '-'));
            if (in_array($slug_class, $classes)) {
                return $slug_class;
            }
            
            // Return the first class found
            return $classes[0];
        }
        
        return null;
    }
    
    public function get_plugin_metadata($slug = null) {
        if ($slug) {
            return isset($this->plugin_metadata[$slug]) ? $this->plugin_metadata[$slug] : null;
        }
        
        return $this->plugin_metadata;
    }
}

// Initialize the plugin loader with the database connection
global $pdo;
if (isset($pdo)) {
    $plugin_loader = new PluginLoader($pdo);
    $plugin_loader->load_plugins();
} else {
    error_log("Database connection not available for plugin loader");
}