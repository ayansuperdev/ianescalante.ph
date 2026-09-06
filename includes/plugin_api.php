<?php
// includes/plugin_api.php

$GLOBALS['plugin_hooks'] = $GLOBALS['plugin_hooks'] ?? [];
$GLOBALS['wp_menu'] = $GLOBALS['wp_menu'] ?? [];
$GLOBALS['wp_submenu'] = $GLOBALS['wp_submenu'] ?? [];
$GLOBALS['plugin_menus'] = $GLOBALS['plugin_menus'] ?? ['admin_menu' => []];

// Add this at the top of plugin_api.php
if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('add_action')) {
    function add_action($hook_name, $callback) {
        if (!isset($GLOBALS['plugin_hooks'][$hook_name])) {
            $GLOBALS['plugin_hooks'][$hook_name] = [];
        }
        $GLOBALS['plugin_hooks'][$hook_name][] = $callback;
    }
}

if (!function_exists('do_action')) {
    function do_action($hook_name) {
        if (!empty($GLOBALS['plugin_hooks'][$hook_name])) {
            foreach ($GLOBALS['plugin_hooks'][$hook_name] as $callback) {
                if (is_callable($callback)) {
                    call_user_func($callback);
                }
            }
        }
    }
}


if (!function_exists('add_submenu_page')) {
    function add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback = '') {
        global $submenu;
        if (!isset($submenu[$parent_slug])) {
            $submenu[$parent_slug] = [];
        }
        
        // Store with proper structure
        $submenu[$parent_slug][$menu_slug] = [
            'parent_slug' => $parent_slug,
            'page_title' => $page_title,
            'menu_title' => $menu_title,
            'capability' => $capability,
            'menu_slug'  => $menu_slug,
            'callback'   => $callback
        ];
    }
}


if (!function_exists('register_plugin_menu')) {
    function register_plugin_menu($hook_name, $menu) {
        if (!isset($GLOBALS['plugin_menus'][$hook_name])) {
            $GLOBALS['plugin_menus'][$hook_name] = [];
        }
        $GLOBALS['plugin_menus'][$hook_name][] = $menu;
    }
}

// Add this before the render_plugin_menus function
if (!function_exists('register_setting')) {
    function register_setting($option_group, $option_name) {
        // No-op in our framework
    }
}

if (!function_exists('add_settings_section')) {
    function add_settings_section($id, $title, $callback, $page) {
        global $wp_settings_sections;
        
        if (!isset($wp_settings_sections[$page])) {
            $wp_settings_sections[$page] = [];
        }
        
        $wp_settings_sections[$page][$id] = [
            'id' => $id,
            'title' => $title,
            'callback' => $callback
        ];
    }
}

if (!function_exists('add_settings_field')) {
    function add_settings_field($id, $title, $callback, $page, $section = 'default') {
        // Simplified implementation
        global $wp_settings_fields;
        
        if (!isset($wp_settings_fields[$page][$section])) {
            $wp_settings_fields[$page][$section] = [];
        }
        
        $wp_settings_fields[$page][$section][$id] = [
            'id' => $id,
            'title' => $title,
            'callback' => $callback
        ];
    }
}

// UPDATE render_plugin_menus FUNCTION
if (!function_exists('render_plugin_menus')) {
    function render_plugin_menus($hook_name) {
        // Use global instead of GLOBALS
        global $plugin_menus;
        
        if (!isset($plugin_menus[$hook_name]) || empty($plugin_menus[$hook_name])) {
            return '';
        }
        
        $html = '';
        foreach ($plugin_menus[$hook_name] as $menu) {
            $active = (isset($_GET['page']) && $_GET['page'] === $menu['page']) ? 'active' : '';
            $icon = $menu['icon'] ?? 'fa-puzzle-piece';
            
            $html .= '<a href="?page=' . $menu['page'] . '" class="' . $active . '">';
            $html .= '<i class="fas ' . $icon . '"></i> ' . htmlspecialchars($menu['title']);
            $html .= '</a>';
        }
        return $html;
    }
}

// Add this function to properly handle WordPress-style menus
function render_wordpress_menus() {
    global $menu, $submenu;
    $current_page = $_GET['page'] ?? '';
    $html = '';
    
    foreach ($menu as $parent_slug => $menu_item) {
        if (!is_array($menu_item)) continue;
        
        $menu_title = $menu_item['menu_title'] ?? 'Untitled';
        $is_active = ($current_page === $parent_slug);
        $has_submenu = isset($submenu[$parent_slug]) && is_array($submenu[$parent_slug]);
        
        $html .= '<div class="wp-menu-group">';
        $html .= '<a href="?page=' . $parent_slug . '" class="' . ($is_active ? 'active' : '') . '">';
        $html .= '<i class="fas fa-folder"></i> ' . htmlspecialchars($menu_title);
        $html .= $has_submenu ? ' <i class="fas fa-caret-down"></i>' : '';
        $html .= '</a>';
        
        if ($has_submenu) {
            $html .= '<div class="wp-submenu" style="display:' . ($is_active ? 'block' : 'none') . '">';
            foreach ($submenu[$parent_slug] as $subitem) {
                $sub_slug = $subitem['menu_slug'] ?? $subitem[2] ?? '';
                $sub_title = $subitem['menu_title'] ?? $subitem[0] ?? 'Untitled';
                
                if ($sub_slug) {
                    $sub_active = ($current_page === $sub_slug);
                    $html .= '<a href="?page=' . $sub_slug . '" class="' . ($sub_active ? 'active' : '') . '">';
                    $html .= '<i class="fas fa-circle"></i> ' . htmlspecialchars($sub_title);
                    $html .= '</a>';
                }
            }
            $html .= '</div>';
        }
        $html .= '</div>';
    }
    return $html;
}

// Add this after the existing functions
// Replace the existing submit_button function
if (!function_exists('submit_button')) {
    function submit_button($text = null, $type = 'primary', $name = 'submit', $wrap = true) {
        $text = $text ?: 'Save Changes';
        echo '<button type="submit" name="' . esc_attr($name) . '" class="button button-' . esc_attr($type) . '">' 
            . esc_html($text) 
            . '</button>';
    }
}

// Add this after the existing functions
if (!function_exists('settings_fields')) {
    function settings_fields($option_group) {
        echo '<input type="hidden" name="option_page" value="' . esc_attr($option_group) . '">';
    }
}

if (!function_exists('do_settings_sections')) {
    function do_settings_sections($page) {
        global $wp_settings_sections;
        if (!isset($wp_settings_sections[$page])) return;
        
        foreach ($wp_settings_sections[$page] as $section) {
            echo '<div class="settings-section">';
            echo '<h3>' . esc_html($section['title']) . '</h3>';
            call_user_func($section['callback']);
            echo '</div>';
        }
    }
}

if (!function_exists('sanitize_title')) {
    function sanitize_title($title) {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
    }
}

if (!function_exists('maybe_unserialize')) {
    function maybe_unserialize($data) {
        if (is_serialized($data)) {
            return @unserialize($data);
        }
        return $data;
    }
}

// Add to includes/plugin_api.php

// Global asset queues
$GLOBALS['enqueued_styles'] = [];
$GLOBALS['enqueued_scripts'] = [];

/**
 * Enhanced enqueue functions with dependency support
 */
function enqueue_style($handle, $src, $deps = [], $ver = false, $media = 'all') {
    global $enqueued_styles;
    
    // Generate version if not provided
    if ($ver === false) {
        $ver = file_exists(str_replace(BASE_URL, $_SERVER['DOCUMENT_ROOT'], $src)) 
            ? filemtime(str_replace(BASE_URL, $_SERVER['DOCUMENT_ROOT'], $src)) 
            : '1.0.0';
    }
    
    $enqueued_styles[$handle] = [
        'src' => $src,
        'deps' => $deps,
        'ver' => $ver,
        'media' => $media,
        'handled' => false // Track if dependencies are resolved
    ];
}

// In plugin_api.php - Replace the enqueue_script function
// In plugin_api.php - Ensure clean URLs
function enqueue_script($handle, $src, $deps = [], $ver = false, $in_footer = false) {
    global $enqueued_scripts;
    
    // ALWAYS remove callback parameter
    $clean_src = $src;
    if (strpos($clean_src, 'callback=debugPluginJS') !== false) {
        $clean_src = preg_replace('/([?&])callback=debugPluginJS&?/', '$1', $clean_src);
        $clean_src = rtrim($clean_src, '?&');
        // Remove trailing ? if it exists
        if (strpos($clean_src, '?') === strlen($clean_src) - 1) {
            $clean_src = substr($clean_src, 0, -1);
        }
    }
    
    // Generate version if not provided
    if ($ver === false) {
        $ver = file_exists(str_replace(BASE_URL, $_SERVER['DOCUMENT_ROOT'], $clean_src)) 
            ? filemtime(str_replace(BASE_URL, $_SERVER['DOCUMENT_ROOT'], $clean_src)) 
            : '1.0.0';
    }
    
    $enqueued_scripts[$handle] = [
        'src' => $clean_src,
        'deps' => $deps,
        'ver' => $ver,
        'in_footer' => $in_footer,
        'handled' => false
    ];
}

// In plugin_api.php, update the ensure_jquery_available function
// In includes/plugin_api.php - Replace the ensure_jquery_available function
function ensure_jquery_available() {
    global $enqueued_scripts;
    
    // Always load jQuery first in header
    if (!isset($enqueued_scripts['jquery'])) {
        $jquery_cdn = 'https://code.jquery.com/jquery-3.6.0.min.js';
        enqueue_script('jquery', $jquery_cdn, [], '3.6.0', false); // false = load in header
        
        // Also add inline script as immediate fallback
        echo '<script>
        if (typeof jQuery === "undefined") {
            console.warn("jQuery not loaded, loading dynamically...");
            var jqScript = document.createElement("script");
            jqScript.src = "https://code.jquery.com/jquery-3.6.0.min.js";
            jqScript.integrity = "sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=";
            jqScript.crossOrigin = "anonymous";
            jqScript.onload = function() {
                console.log("jQuery dynamically loaded");
                window.dispatchEvent(new Event("jqueryReady"));
            };
            document.head.appendChild(jqScript);
        } else {
            console.log("jQuery already loaded");
            window.dispatchEvent(new Event("jqueryReady"));
        }
        </script>';
    }
}

// In plugin_api.php - Update the resolve_dependencies function
// In includes/plugin_api.php - Update resolve_dependencies function
function resolve_dependencies($items) {
    $resolved = [];
    $handled = [];
    
    // Define dependency order
    $dependency_order = ['jquery', 'datatables', 'jquery-ui'];
    
    // Handle core dependencies first
    foreach ($dependency_order as $core_dep) {
        if (isset($items[$core_dep]) && !isset($handled[$core_dep])) {
            $resolved[$core_dep] = $items[$core_dep];
            $handled[$core_dep] = true;
        }
    }
    
    // Recursive function to handle dependencies
    $resolve = function($item_id, $items, &$resolved, &$handled) use (&$resolve) {
        if (isset($handled[$item_id])) return;
        
        $handled[$item_id] = true;
        
        // Handle dependencies first
        if (isset($items[$item_id]['deps'])) {
            foreach ($items[$item_id]['deps'] as $dep) {
                if (isset($items[$dep]) && !isset($handled[$dep])) {
                    $resolve($dep, $items, $resolved, $handled);
                }
            }
        }
        
        // Then add the current item
        if (!isset($resolved[$item_id])) {
            $resolved[$item_id] = $items[$item_id];
        }
    };
    
    // Process all items
    foreach ($items as $id => $item) {
        $resolve($id, $items, $resolved, $handled);
    }
    
    return $resolved;
}

/**
 * Enhanced print functions with dependency resolution
 */
function print_styles() {
    global $enqueued_styles;
    
    if (empty($enqueued_styles)) return;
    
    // Resolve dependencies
    $resolved_styles = resolve_dependencies($enqueued_styles);
    
    foreach ($resolved_styles as $handle => $style) {
        $src = $style['src'];
        $ver = $style['ver'] ? '?ver=' . $style['ver'] : '';
        $media = $style['media'];
        
        echo '<link rel="stylesheet" href="' . htmlspecialchars($src . $ver) . '" media="' . $media . '">' . "\n";
    }
}

// In plugin_api.php - Replace the print_scripts function
function print_scripts($in_footer = false) {
    global $enqueued_scripts;
    
    if (empty($enqueued_scripts)) return;
    
    // Filter scripts by location
    $filtered_scripts = array_filter($enqueued_scripts, function($script) use ($in_footer) {
        return $script['in_footer'] === $in_footer;
    });
    
    if (empty($filtered_scripts)) return;
    
    // Resolve dependencies
    $resolved_scripts = resolve_dependencies($filtered_scripts);
    
    foreach ($resolved_scripts as $handle => $script) {
        $src = $script['src'];
        $ver = $script['ver'] ? '?ver=' . $script['ver'] : '';
        
        // Skip jQuery as we load it manually
        if ($handle === 'jquery') continue;
        
        // For plugin scripts, add a data attribute for identification
        if (strpos($handle, 'plugin-') === 0) {
            echo '<script src="' . htmlspecialchars($src . $ver) . '" data-plugin="true"></script>' . "\n";
        } else {
            echo '<script src="' . htmlspecialchars($src . $ver) . '"></script>' . "\n";
        }
    }
}

// In plugin_api.php - Update the dependency detection function
function detect_js_dependencies($js_file_path) {
    $dependencies = [];
    
    if (!file_exists($js_file_path)) {
        return $dependencies;
    }
    
    $content = @file_get_contents($js_file_path);
    if ($content === false) {
        return $dependencies;
    }
    
    // More comprehensive DataTables detection
    $datatables_patterns = [
        '/\.DataTable\s*\(/i',
        '/\.dataTable\s*\(/i',
        '/\$.fn.dataTable/i',
        '/jQuery.fn.dataTable/i',
        '/DataTable\./i',
        '/isDataTable/i',
        '/dataTable\(/i',
        '/datatables/i',
        '/data-table/i'
    ];
    
    foreach ($datatables_patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            $dependencies[] = 'datatables';
            break;
        }
    }
    
    // Check for jQuery with more patterns
    $jquery_patterns = [
        '/\$\s*\(/',
        '/jQuery\s*\(/',
        '/\$\s*\./',
        '/jQuery\s*\./',
        '/\.ready\s*\(/',
        '/document\.ready/',
        '/\$\s*\(document\)/',
        '/jQuery\s*\(document\)/'
    ];
    
    foreach ($jquery_patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            $dependencies[] = 'jquery';
            break;
        }
    }
    
    return array_unique($dependencies);
}

function maybe_localize_script($slug, $js_url, $handle) {
    // Define AJAX variables for plugins that need them
    echo "<script>
    // Localize script for plugin: {$slug}
    if (typeof ajaxurl === 'undefined') {
        window.ajaxurl = '" . admin_url('admin-ajax.php') . "';
    }
    if (typeof ajax_assign === 'undefined') {
        window.ajax_assign = window.ajaxurl;
    }
    if (typeof ajax_object === 'undefined') {
        window.ajax_object = {
            ajaxurl: window.ajaxurl,
            nonce: '" . wp_create_nonce('ajax-nonce') . "'
        };
    }
    </script>";
}

// Replace the find_plugin_assets function in plugin_api.php with this improved version
function find_plugin_assets($plugin_path, $plugin_url) {
    $assets = ['css' => [], 'js' => []];
    
    if (!is_dir($plugin_path)) {
        return $assets;
    }

    // Define allowed directories to scan (more comprehensive)
    $scan_dirs = [
        $plugin_path,
        $plugin_path . '/assets',
        $plugin_path . '/js',
        $plugin_path . '/javascript',
        $plugin_path . '/scripts',
        $plugin_path . '/src',
        $plugin_path . '/dist',
        $plugin_path . '/build',
        $plugin_path . '/public',
        $plugin_path . '/inc',
        $plugin_path . '/includes'
    ];
    
    // Define file patterns to look for (more comprehensive)
    $css_patterns = ['*.css', '*.min.css', '*.css.map'];
    $js_patterns = ['*.js', '*.min.js', '*.mjs', '*.js.map'];
    
    foreach ($scan_dirs as $dir) {
        if (!is_dir($dir)) continue;
        
        // Recursively scan for CSS files
        try {
            $css_files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($css_files as $file) {
                if ($file->isFile()) {
                    $filename = $file->getFilename();
                    foreach ($css_patterns as $pattern) {
                        if (fnmatch($pattern, $filename)) {
                            $relative_path = str_replace($plugin_path, '', $file->getPathname());
                            $relative_path = ltrim(str_replace('\\', '/', $relative_path), '/');
                            $assets['css'][] = $plugin_url . '/' . $relative_path;
                            break;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error scanning CSS files in {$dir}: " . $e->getMessage());
        }
        
        // Recursively scan for JS files
        try {
            $js_files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($js_files as $file) {
                if ($file->isFile()) {
                    $filename = $file->getFilename();
                    foreach ($js_patterns as $pattern) {
                        if (fnmatch($pattern, $filename)) {
                            $relative_path = str_replace($plugin_path, '', $file->getPathname());
                            $relative_path = ltrim(str_replace('\\', '/', $relative_path), '/');
                            $assets['js'][] = $plugin_url . '/' . $relative_path;
                            break;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error scanning JS files in {$dir}: " . $e->getMessage());
        }
    }
    
    // Remove duplicates while preserving order
    $assets['css'] = array_values(array_unique($assets['css']));
    $assets['js'] = array_values(array_unique($assets['js']));
    
    return $assets;
}

// Add this function to properly order JS files
function order_js_files_by_dependencies($js_files) {
    $ordered_files = [];
    $jquery_files = [];
    $other_files = [];
    
    // Separate jQuery-dependent files and others
    foreach ($js_files as $js_file) {
        $filename = basename($js_file);
        
        if (strpos($filename, 'jquery') !== false || 
            strpos($js_file, 'jquery') !== false) {
            $jquery_files[] = $js_file;
        } else {
            $other_files[] = $js_file;
        }
    }
    
    // Put jQuery files first (they often are dependencies)
    $ordered_files = array_merge($jquery_files, $other_files);
    
    return $ordered_files;
}

// includes/plugin_api.php (add these functions)
if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src = '', $deps = array(), $ver = false, $in_footer = false) {
        // Implementation for script enqueueing
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
        // Implementation for style enqueueing
        global $enqueued_styles;
        $enqueued_styles[$handle] = [
            'src' => $src,
            'deps' => $deps,
            'ver' => $ver,
            'media' => $media
        ];
    }
}

// Add more WordPress functions as needed...

// includes/plugin_api.php

// Add these functions after the existing ones
if (!function_exists('selected')) {
    function selected($selected, $current = true, $echo = true) {
        return __checked_selected_helper($selected, $current, $echo, 'selected');
    }
}

if (!function_exists('checked')) {
    function checked($checked, $current = true, $echo = true) {
        return __checked_selected_helper($checked, $current, $echo, 'checked');
    }
}

if (!function_exists('__checked_selected_helper')) {
    function __checked_selected_helper($helper, $current, $echo, $type) {
        if ((string) $helper === (string) $current)
            $result = " $type='$type'";
        else
            $result = '';

        if ($echo)
            echo $result;

        return $result;
    }
}

if (!function_exists('disabled')) {
    function disabled($disabled, $current = true, $echo = true) {
        return __checked_selected_helper($disabled, $current, $echo, 'disabled');
    }
}

// includes/plugin_api.php

// Add this function after the existing ones
if (!function_exists('absint')) {
    function absint($maybeint) {
        return abs(intval($maybeint));
    }
}

// ======================================================================
// ADDITIONAL WORDPRESS COMPATIBILITY FUNCTIONS
// ======================================================================

// String and Text Functions
if (!function_exists('esc_url')) {
    function esc_url($url, $protocols = null, $_context = 'display') {
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_textarea')) {
    function esc_textarea($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_js')) {
    function esc_js($text) {
        return htmlspecialchars($text, ENT_COMPAT, 'UTF-8');
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('_e')) {
    function _e($text, $domain = 'default') {
        echo $text;
    }
}

if (!function_exists('_x')) {
    function _x($text, $context, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('_n')) {
    function _n($single, $plural, $number, $domain = 'default') {
        return ($number == 1) ? $single : $plural;
    }
}

if (!function_exists('_ex')) {
    function _ex($text, $context, $domain = 'default') {
        echo $text;
    }
}

// URL Functions
if (!function_exists('admin_url')) {
    function admin_url($path = '') {
        return BASE_URL . '/admin_dashboard.php' . ($path ? '?page=' . $path : '');
    }
}

if (!function_exists('home_url')) {
    function home_url($path = '') {
        return BASE_URL . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('site_url')) {
    function site_url($path = '') {
        return BASE_URL . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('plugins_url')) {
    function plugins_url($path = '', $plugin = '') {
        return PLUGINS_URL . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return plugins_url('', $file) . '/';
    }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return dirname($file) . '/';
    }
}

if (!function_exists('content_url')) {
    function content_url($path = '') {
        return BASE_URL . '/wp-content' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

// Database and Option Functions
if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_name = ?");
            $stmt->execute([$option]);
            $result = $stmt->fetch();
            
            return $result ? maybe_unserialize($result['setting_value']) : $default;
        } catch (PDOException $e) {
            return $default;
        }
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null) {
        global $pdo;
        
        try {
            $serialized_value = maybe_serialize($value);
            
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_name, setting_value) 
                                  VALUES (?, ?) 
                                  ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$option, $serialized_value, $serialized_value]);
            
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}

if (!function_exists('add_option')) {
    function add_option($option, $value = '', $deprecated = '', $autoload = 'yes') {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM system_settings WHERE setting_name = ?");
            $stmt->execute([$option]);
            
            if ($stmt->fetchColumn() == 0) {
                $serialized_value = maybe_serialize($value);
                
                $stmt = $pdo->prepare("INSERT INTO system_settings (setting_name, setting_value) VALUES (?, ?)");
                $stmt->execute([$option, $serialized_value]);
                
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }
}

if (!function_exists('delete_option')) {
    function delete_option($option) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("DELETE FROM system_settings WHERE setting_name = ?");
            $stmt->execute([$option]);
            
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}

if (!function_exists('maybe_serialize')) {
    function maybe_serialize($data) {
        if (is_array($data) || is_object($data)) {
            return serialize($data);
        }
        
        return $data;
    }
}

if (!function_exists('is_serialized')) {
    function is_serialized($data, $strict = true) {
        if (!is_string($data)) {
            return false;
        }
        
        $data = trim($data);
        if ('N;' == $data) {
            return true;
        }
        
        if (strlen($data) < 4) {
            return false;
        }
        
        if (':' !== $data[1]) {
            return false;
        }
        
        if ($strict) {
            $lastc = substr($data, -1);
            if (';' !== $lastc && '}' !== $lastc) {
                return false;
            }
        } else {
            $semicolon = strpos($data, ';');
            $brace = strpos($data, '}');
            
            if (false === $semicolon && false === $brace) {
                return false;
            }
            
            if (false !== $semicolon && $semicolon < 3) {
                return false;
            }
            
            if (false !== $brace && $brace < 4) {
                return false;
            }
        }
        
        $token = $data[0];
        switch ($token) {
            case 's':
                if ($strict) {
                    if ('"' !== substr($data, -2, 1)) {
                        return false;
                    }
                } elseif (false === strpos($data, '"')) {
                    return false;
                }
                break;
            case 'a':
            case 'O':
                return (bool) preg_match("/^{$token}:[0-9]+:/s", $data);
            case 'b':
            case 'i':
            case 'd':
                $end = $strict ? '$' : '';
                return (bool) preg_match("/^{$token}:[0-9.E-]+;$end/", $data);
        }
        
        return false;
    }
}

// User and Role Functions
if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        if ($capability === 'manage_options') {
            return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
        }
        
        // For other capabilities, you might need to implement more complex logic
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('wp_get_current_user')) {
    function wp_get_current_user() {
        $user = new stdClass();
        $user->ID = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
        $user->user_login = isset($_SESSION['username']) ? $_SESSION['username'] : '';
        $user->user_email = '';
        $user->roles = isset($_SESSION['role']) ? [$_SESSION['role']] : [];
        
        return $user;
    }
}

// Post and Content Functions
if (!function_exists('get_post')) {
    function get_post($post = null, $output = OBJECT, $filter = 'raw') {
        // This is a basic implementation - you might need to extend it
        global $pdo;
        
        if (is_numeric($post)) {
            $post_id = $post;
        } else if (is_object($post)) {
            $post_id = $post->ID;
        } else {
            return null;
        }
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM posts WHERE ID = ?");
            $stmt->execute([$post_id]);
            $post_data = $stmt->fetch();
            
            if (!$post_data) {
                return null;
            }
            
            if ($output == ARRAY_A) {
                return $post_data;
            } elseif ($output == ARRAY_N) {
                return array_values($post_data);
            } else {
                return (object) $post_data;
            }
        } catch (PDOException $e) {
            return null;
        }
    }
}

if (!function_exists('get_posts')) {
    function get_posts($args = []) {
        // Basic implementation - you'll need to extend this based on your needs
        global $pdo;
        
        $defaults = [
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        ];
        
        $args = array_merge($defaults, $args);
        
        try {
            $query = "SELECT * FROM posts WHERE post_type = ? AND post_status = ? ORDER BY {$args['orderby']} {$args['order']} LIMIT {$args['numberposts']}";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$args['post_type'], $args['post_status']]);
            
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            return [];
        }
    }
}

// HTTP and Request Functions
if (!function_exists('wp_remote_get')) {
    function wp_remote_get($url, $args = []) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'WordPress/5.8; ' . home_url());
        curl_setopt($ch, CURLOPT_TIMEOUT, isset($args['timeout']) ? $args['timeout'] : 5);
        
        if (isset($args['headers'])) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $args['headers']);
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'body' => $response,
            'response' => ['code' => $http_code, 'message' => ''],
            'headers' => []
        ];
    }
}

if (!function_exists('wp_remote_post')) {
    function wp_remote_post($url, $args = []) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, isset($args['body']) ? $args['body'] : '');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'WordPress/5.8; ' . home_url());
        curl_setopt($ch, CURLOPT_TIMEOUT, isset($args['timeout']) ? $args['timeout'] : 5);
        
        if (isset($args['headers'])) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $args['headers']);
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'body' => $response,
            'response' => ['code' => $http_code, 'message' => ''],
            'headers' => []
        ];
    }
}

if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response) {
        return isset($response['body']) ? $response['body'] : '';
    }
}

if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($response) {
        return isset($response['response']['code']) ? $response['response']['code'] : 0;
    }
}

// File and Media Functions
if (!function_exists('wp_upload_dir')) {
    function wp_upload_dir() {
        $upload_path = $_SERVER['DOCUMENT_ROOT'] . '/uploads';
        $upload_url = BASE_URL . '/uploads';
        
        return [
            'path' => $upload_path,
            'url' => $upload_url,
            'subdir' => '',
            'basedir' => $upload_path,
            'baseurl' => $upload_url,
            'error' => false
        ];
    }
}

// Date and Time Functions
if (!function_exists('current_time')) {
    function current_time($type, $gmt = 0) {
        switch ($type) {
            case 'mysql':
                return date('Y-m-d H:i:s');
            case 'timestamp':
                return time();
            default:
                return date($type);
        }
    }
}



// Utility Functions
if (!function_exists('wp_die')) {
    function wp_die($message = '', $title = '', $args = []) {
        echo '<div style="background: #f8d7da; color: #721c24; padding: 20px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px;">';
        if ($title) {
            echo '<h3 style="margin-top: 0;">' . esc_html($title) . '</h3>';
        }
        echo '<p>' . esc_html($message) . '</p>';
        echo '</div>';
        exit;
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return ($thing instanceof WP_Error);
    }
}

// WP_Error class for error handling
if (!class_exists('WP_Error')) {
    class WP_Error {
        private $errors = [];
        private $error_data = [];
        
        public function __construct($code = '', $message = '', $data = '') {
            if (!empty($code)) {
                $this->add($code, $message, $data);
            }
        }
        
        public function add($code, $message, $data = '') {
            $this->errors[$code][] = $message;
            
            if (!empty($data)) {
                $this->error_data[$code] = $data;
            }
        }
        
        public function get_error_codes() {
            return array_keys($this->errors);
        }
        
        public function get_error_messages($code = '') {
            if (empty($code)) {
                $all_messages = [];
                foreach ($this->errors as $code => $messages) {
                    $all_messages = array_merge($all_messages, $messages);
                }
                return $all_messages;
            }
            
            return isset($this->errors[$code]) ? $this->errors[$code] : [];
        }
        
        public function get_error_message($code = '') {
            if (empty($code)) {
                $codes = $this->get_error_codes();
                $code = $codes[0];
            }
            
            $messages = $this->get_error_messages($code);
            return $messages[0] ?? '';
        }
        
        public function get_error_data($code = '') {
            if (empty($code)) {
                $codes = $this->get_error_codes();
                $code = $codes[0];
            }
            
            return isset($this->error_data[$code]) ? $this->error_data[$code] : null;
        }
        
        public function has_errors() {
            return !empty($this->errors);
        }
    }
}

// Transients API (simplified implementation)
if (!function_exists('get_transient')) {
    function get_transient($transient) {
        return get_option('_transient_' . $transient, false);
    }
}

if (!function_exists('set_transient')) {
    function set_transient($transient, $value, $expiration = 0) {
        return update_option('_transient_' . $transient, $value);
    }
}

if (!function_exists('delete_transient')) {
    function delete_transient($transient) {
        return delete_option('_transient_' . $transient);
    }
}

// Nonce functions (simplified implementation)
if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action = -1) {
        return md5($action . $_SESSION['user_id'] . $_SESSION['username']);
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1) {
        $expected = wp_create_nonce($action);
        return hash_equals($expected, $nonce);
    }
}

// ======================================================================
// FINAL CHECK - Ensure no syntax errors
// ======================================================================

// This section ensures all code blocks are properly closed
// Add any missing WordPress functions below this line

// Security nonce field helper
if (!function_exists('wp_nonce_field')) {
    function wp_nonce_field($action = -1, $name = '_wpnonce', $referer = true, $echo = true) {
        $nonce_field = '<input type="hidden" name="' . $name . '" value="' . wp_create_nonce($action) . '" />';
        
        if ($referer) {
            $nonce_field .= wp_referer_field(false);
        }
        
        if ($echo) {
            echo $nonce_field;
        } else {
            return $nonce_field;
        }
    }
}

// Add any other missing WordPress functions here
// This ensures all code blocks are properly closed

// Final closing for the file
// No need to add anything after this comment

if (!function_exists('wp_referer_field')) {
    function wp_referer_field($echo = true) {
        $referer_field = '<input type="hidden" name="_wp_http_referer" value="' . esc_attr($_SERVER['REQUEST_URI']) . '" />';
        
        if ($echo) {
            echo $referer_field;
        } else {
            return $referer_field;
        }
    }
}

// ======================================================================
// END ADDITIONAL WORDPRESS COMPATIBILITY FUNCTIONS
// ======================================================================

// ======================================================================
// ADDITIONAL ESSENTIAL WORDPRESS FUNCTIONS
// ======================================================================

// Database Functions
if (!function_exists('wpdb')) {
    function wpdb() {
        global $pdo;
        return $pdo;
    }
}

if (!function_exists('get_results')) {
    function get_results($query, $output = OBJECT) {
        global $pdo;
        try {
            $stmt = $pdo->query($query);
            if ($output === ARRAY_A) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($output === ARRAY_N) {
                return $stmt->fetchAll(PDO::FETCH_NUM);
            } else {
                return $stmt->fetchAll(PDO::FETCH_OBJ);
            }
        } catch (PDOException $e) {
            return [];
        }
    }
}

if (!function_exists('get_row')) {
    function get_row($query, $output = OBJECT, $y = 0) {
        $results = get_results($query, $output);
        return isset($results[$y]) ? $results[$y] : null;
    }
}

if (!function_exists('get_var')) {
    function get_var($query, $x = 0, $y = 0) {
        $results = get_results($query, ARRAY_N);
        if (isset($results[$y])) {
            $row = $results[$y];
            return isset($row[$x]) ? $row[$x] : null;
        }
        return null;
    }
}

if (!function_exists('get_col')) {
    function get_col($query, $x = 0) {
        $results = get_results($query, ARRAY_N);
        $column = [];
        foreach ($results as $row) {
            if (isset($row[$x])) {
                $column[] = $row[$x];
            }
        }
        return $column;
    }
}

if (!function_exists('query')) {
    function query($query) {
        global $pdo;
        try {
            return $pdo->exec($query);
        } catch (PDOException $e) {
            return false;
        }
    }
}

// User Functions
if (!function_exists('get_userdata')) {
    function get_userdata($user_id) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            return false;
        }
    }
}

if (!function_exists('get_user_by')) {
    function get_user_by($field, $value) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE $field = ?");
            $stmt->execute([$value]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            return false;
        }
    }
}

if (!function_exists('wp_set_current_user')) {
    function wp_set_current_user($id, $name = '') {
        // This is a no-op in our implementation since we use sessions
    }
}

// Formatting Functions
if (!function_exists('wpautop')) {
    function wpautop($pee, $br = true) {
        $pre_tags = array();
        
        if (trim($pee) === '') {
            return '';
        }
        
        // Just to make things a little easier, pad the end
        $pee = $pee . "\n";
        
        // Pre tags shouldn't be touched by autop
        if (strpos($pee, '<pre') !== false) {
            $pee_parts = explode('</pre>', $pee);
            $last_pee = array_pop($pee_parts);
            $pee = '';
            $i = 0;
            
            foreach ($pee_parts as $pee_part) {
                $start = strpos($pee_part, '<pre');
                
                // Malformed html?
                if ($start === false) {
                    $pee .= $pee_part;
                    continue;
                }
                
                $name = "<pre wp-pre-tag-$i></pre>";
                $pre_tags[$name] = substr($pee_part, $start) . '</pre>';
                
                $pee .= substr($pee_part, 0, $start) . $name;
                $i++;
            }
            
            $pee .= $last_pee;
        }
        
        // Change multiple <br>s into two line breaks
        $pee = preg_replace('|<br\s*/?>\s*<br\s*/?>|', "\n\n", $pee);
        
        $allblocks = '(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|form|map|area|blockquote|address|math|style|p|h[1-6]|hr|fieldset|legend|section|article|aside|hgroup|header|footer|nav|figure|figcaption|details|menu|summary)';
        
        // Add a double line break above block-level opening tags
        $pee = preg_replace('!(<' . $allblocks . '[\s/>])!', "\n\n$1", $pee);
        
        // Add a double line break below block-level closing tags
        $pee = preg_replace('!(</' . $allblocks . '>)!', "$1\n\n", $pee);
        
        // Standardize newline characters to "\n"
        $pee = str_replace(array("\r\n", "\r"), "\n", $pee);
        
        // Remove more than two contiguous line breaks
        $pee = preg_replace("/\n\n+/", "\n\n", $pee);
        
        // Split up the contents into an array of strings, separated by double line breaks
        $pees = preg_split('/\n\s*\n/', $pee, -1, PREG_SPLIT_NO_EMPTY);
        
        // Reset $pee prior to rebuilding
        $pee = '';
        
        // Rebuild the content as a series of paragraphs
        foreach ($pees as $tinkle) {
            $pee .= '<p>' . trim($tinkle, "\n") . "</p>\n";
        }
        
        // Under certain strange conditions it could create a P of entirely whitespace
        $pee = preg_replace('|<p>\s*</p>|', '', $pee);
        
        // Add a <br> after <img> and <input>
        $pee = preg_replace('!(<img[^>]*>)!', '$1<br>', $pee);
        $pee = preg_replace('!(<input[^>]*>)!', '$1<br>', $pee);
        
        // If <br /> tags are available, use them
        if ($br) {
            $pee = preg_replace('|(?<!<br />)\s*\n|', "<br />\n", $pee);
        }
        
        // Remove <br /> tags immediately before closing block-level tags
        $pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*<br />!', '$1', $pee);
        
        // Remove <br /> tags immediately after opening block-level tags
        $pee = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $pee);
        
        // Replace preserved <pre> tags
        if (!empty($pre_tags)) {
            $pee = str_replace(array_keys($pre_tags), array_values($pre_tags), $pee);
        }
        
        return $pee;
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        $filtered = preg_replace('/[\r\n\t ]+/', ' ', $str);
        $filtered = trim($filtered);
        $filtered = htmlspecialchars($filtered, ENT_QUOTES, 'UTF-8');
        return $filtered;
    }
}

if (!function_exists('sanitize_email')) {
    function sanitize_email($email) {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
}

if (!function_exists('is_email')) {
    function is_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

// File System Functions
if (!function_exists('wp_upload_dir')) {
    function wp_upload_dir() {
        $upload_path = $_SERVER['DOCUMENT_ROOT'] . '/uploads';
        $upload_url = BASE_URL . '/uploads';
        
        return array(
            'path' => $upload_path,
            'url' => $upload_url,
            'subdir' => '',
            'basedir' => $upload_path,
            'baseurl' => $upload_url,
            'error' => false
        );
    }
}

if (!function_exists('wp_mkdir_p')) {
    function wp_mkdir_p($target) {
        $wrapper = null;
        
        // Strip the protocol
        if (wp_is_stream($target)) {
            list($wrapper, $target) = explode('://', $target, 2);
        }
        
        // From php.net/mkdir user contributed notes
        $target = str_replace('//', '/', $target);
        
        // Put the wrapper back on the target
        if ($wrapper !== null) {
            $target = $wrapper . '://' . $target;
        }
        
        // Safe mode fails with a trailing slash under certain PHP versions.
        $target = rtrim($target, '/');
        if (empty($target)) {
            $target = '/';
        }
        
        if (file_exists($target)) {
            return @is_dir($target);
        }
        
        // We need to find the permissions of the parent folder that exists
        // and inherit that
        $target_parent = dirname($target);
        while ('.' != $target_parent && !is_dir($target_parent)) {
            $target_parent = dirname($target_parent);
        }
        
        // Get the permission bits
        if ($stat = @stat($target_parent)) {
            $dir_perms = $stat['mode'] & 0007777;
        } else {
            $dir_perms = 0777;
        }
        
        if (@mkdir($target, $dir_perms, true)) {
            // If a umask is set that modifies $dir_perms, we'll have to re-set
            // the permissions with chmod()
            if ($dir_perms != ($dir_perms & ~umask())) {
                $folder_parts = explode('/', substr($target, strlen($target_parent) + 1));
                for ($i = 1, $c = count($folder_parts); $i <= $c; $i++) {
                    @chmod($target_parent . '/' . implode('/', array_slice($folder_parts, 0, $i)), $dir_perms);
                }
            }
            return true;
        }
        
        return false;
    }
}

if (!function_exists('wp_is_stream')) {
    function wp_is_stream($path) {
        $wrappers = stream_get_wrappers();
        $wrappers_re = '(' . join('|', $wrappers) . ')';
        
        return preg_match("!^$wrappers_re://!", $path) === 1;
    }
}

// Template Functions
if (!function_exists('get_template_directory')) {
    function get_template_directory() {
        return $_SERVER['DOCUMENT_ROOT'] . '/templates';
    }
}

if (!function_exists('get_template_directory_uri')) {
    function get_template_directory_uri() {
        return BASE_URL . '/templates';
    }
}

if (!function_exists('get_stylesheet_directory')) {
    function get_stylesheet_directory() {
        return get_template_directory();
    }
}

if (!function_exists('get_stylesheet_directory_uri')) {
    function get_stylesheet_directory_uri() {
        return get_template_directory_uri();
    }
}

if (!function_exists('get_header')) {
    function get_header($name = null) {
        do_action('get_header', $name);
        
        $templates = array();
        $name = (string) $name;
        if ('' !== $name) {
            $templates[] = "header-{$name}.php";
        }
        
        $templates[] = 'header.php';
        
        if (locate_template($templates, true, false) === '') {
            // Fallback to basic header
            echo '<!DOCTYPE html><html><head><title>' . get_bloginfo('name') . '</title></head><body>';
        }
    }
}

if (!function_exists('get_footer')) {
    function get_footer($name = null) {
        do_action('get_footer', $name);
        
        $templates = array();
        $name = (string) $name;
        if ('' !== $name) {
            $templates[] = "footer-{$name}.php";
        }
        
        $templates[] = 'footer.php';
        
        if (locate_template($templates, true, false) === '') {
            // Fallback to basic footer
            echo '</body></html>';
        }
    }
}

if (!function_exists('get_sidebar')) {
    function get_sidebar($name = null) {
        do_action('get_sidebar', $name);
        
        $templates = array();
        $name = (string) $name;
        if ('' !== $name) {
            $templates[] = "sidebar-{$name}.php";
        }
        
        $templates[] = 'sidebar.php';
        
        locate_template($templates, true, false);
    }
}

if (!function_exists('locate_template')) {
    function locate_template($template_names, $load = false, $require_once = true) {
        $located = '';
        foreach ((array) $template_names as $template_name) {
            if (!$template_name) {
                continue;
            }
            
            $template_path = get_template_directory() . '/' . $template_name;
            if (file_exists($template_path)) {
                $located = $template_path;
                break;
            }
        }
        
        if ($load && '' !== $located) {
            if ($require_once) {
                require_once $located;
            } else {
                require $located;
            }
        }
        
        return $located;
    }
}

// Blog Info Functions
if (!function_exists('get_bloginfo')) {
    function get_bloginfo($show = '', $filter = 'raw') {
        switch ($show) {
            case 'name':
                return getSetting('blogname', 'My Portfolio');
            case 'description':
                return getSetting('blogdescription', 'Just another portfolio site');
            case 'url':
            case 'home':
            case 'siteurl':
                return BASE_URL;
            case 'stylesheet_url':
                return BASE_URL . '/assets/css/style.css';
            case 'stylesheet_directory':
                return BASE_URL . '/assets/css';
            case 'template_url':
            case 'template_directory':
                return get_template_directory_uri();
            case 'admin_email':
                return getSetting('admin_email', 'admin@example.com');
            case 'charset':
                return 'UTF-8';
            case 'version':
                return '1.0.0';
            case 'language':
                return 'en-US';
            case 'text_direction':
                return 'ltr';
            default:
                return '';
        }
    }
}

if (!function_exists('bloginfo')) {
    function bloginfo($show = '') {
        echo get_bloginfo($show, 'display');
    }
}

// Conditional Tags
if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('is_admin')) {
    function is_admin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}

if (!function_exists('is_front_page')) {
    function is_front_page() {
        return (!isset($_GET['page']) || $_GET['page'] === 'home');
    }
}

if (!function_exists('is_home')) {
    function is_home() {
        return is_front_page();
    }
}

// Date and Time Functions
if (!function_exists('current_time')) {
    function current_time($type, $gmt = 0) {
        switch ($type) {
            case 'mysql':
                return date('Y-m-d H:i:s');
            case 'timestamp':
                return time();
            default:
                return date($type);
        }
    }
}

if (!function_exists('date_i18n')) {
    function date_i18n($dateformatstring, $unixtimestamp = false, $gmt = false) {
        if ($unixtimestamp === false) {
            $unixtimestamp = time();
        }
        
        return date($dateformatstring, $unixtimestamp);
    }
}

// ======================================================================
// END ADDITIONAL ESSENTIAL WORDPRESS FUNCTIONS
// ======================================================================

// Add these functions to your existing plugin_api.php file

if (!function_exists('get_post_meta')) {
    function get_post_meta($post_id, $key = '', $single = false) {
        global $pdo;
        try {
            if ($key) {
                $stmt = $pdo->prepare("SELECT meta_value FROM postmeta WHERE post_id = ? AND meta_key = ?");
                $stmt->execute([$post_id, $key]);
                if ($single) {
                    return $stmt->fetchColumn();
                }
                return $stmt->fetchAll(PDO::FETCH_COLUMN);
            } else {
                $stmt = $pdo->prepare("SELECT meta_key, meta_value FROM postmeta WHERE post_id = ?");
                $stmt->execute([$post_id]);
                return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            }
        } catch (PDOException $e) {
            return $single ? '' : [];
        }
    }
}

if (!function_exists('update_post_meta')) {
    function update_post_meta($post_id, $meta_key, $meta_value) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("INSERT INTO postmeta (post_id, meta_key, meta_value) 
                                  VALUES (?, ?, ?) 
                                  ON DUPLICATE KEY UPDATE meta_value = ?");
            $stmt->execute([$post_id, $meta_key, $meta_value, $meta_value]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}

if (!function_exists('delete_post_meta')) {
    function delete_post_meta($post_id, $meta_key) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("DELETE FROM postmeta WHERE post_id = ? AND meta_key = ?");
            return $stmt->execute([$post_id, $meta_key]);
        } catch (PDOException $e) {
            return false;
        }
    }
}

if (!function_exists('add_meta_box')) {
    function add_meta_box($id, $title, $callback, $screen = null, $context = 'advanced', $priority = 'default', $callback_args = null) {
        global $wp_meta_boxes;
        if (!isset($wp_meta_boxes)) $wp_meta_boxes = [];
        if (!isset($wp_meta_boxes[$screen])) $wp_meta_boxes[$screen] = [];
        if (!isset($wp_meta_boxes[$screen][$context])) $wp_meta_boxes[$screen][$context] = [];
        
        $wp_meta_boxes[$screen][$context][$priority][$id] = [
            'id' => $id,
            'title' => $title,
            'callback' => $callback,
            'args' => $callback_args
        ];
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink($post_id) {
        // You'll need to implement your own URL structure
        return BASE_URL . '/?p=' . $post_id;
    }
}

if (!function_exists('get_the_title')) {
    function get_the_title($post_id) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT title FROM posts WHERE ID = ?");
            return $stmt->execute([$post_id])->fetchColumn();
        } catch (PDOException $e) {
            return '';
        }
    }
}

if (!function_exists('get_the_content')) {
    function get_the_content($post_id) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT content FROM posts WHERE ID = ?");
            return $stmt->execute([$post_id])->fetchColumn();
        } catch (PDOException $e) {
            return '';
        }
    }
}

if (!function_exists('has_post_thumbnail')) {
    function has_post_thumbnail($post_id) {
        $thumbnail_id = get_post_meta($post_id, '_thumbnail_id', true);
        return !empty($thumbnail_id);
    }
}

if (!function_exists('get_the_post_thumbnail')) {
    function get_the_post_thumbnail($post_id, $size = 'post-thumbnail', $attr = '') {
        $thumbnail_id = get_post_meta($post_id, '_thumbnail_id', true);
        if ($thumbnail_id) {
            return wp_get_attachment_image($thumbnail_id, $size, false, $attr);
        }
        return '';
    }
}

if (!function_exists('wp_get_attachment_image')) {
    function wp_get_attachment_image($attachment_id, $size = 'thumbnail', $icon = false, $attr = '') {
        $url = wp_get_attachment_url($attachment_id);
        if ($url) {
            return '<img src="' . esc_url($url) . '" alt="" />';
        }
        return '';
    }
}

if (!function_exists('wp_get_attachment_url')) {
    function wp_get_attachment_url($attachment_id) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT guid FROM posts WHERE ID = ? AND post_type = 'attachment'");
            return $stmt->execute([$attachment_id])->fetchColumn();
        } catch (PDOException $e) {
            return '';
        }
    }
}

if (!function_exists('get_categories')) {
    function get_categories($args = []) {
        global $pdo;
        try {
            $stmt = $pdo->query("SELECT * FROM terms WHERE taxonomy = 'category'");
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            return [];
        }
    }
}

if (!function_exists('get_the_category')) {
    function get_the_category($post_id) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT t.* FROM terms t 
                                  INNER JOIN term_relationships tr ON t.term_id = tr.term_taxonomy_id 
                                  WHERE tr.object_id = ? AND t.taxonomy = 'category'");
            $stmt->execute([$post_id]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            return [];
        }
    }
}

if (!function_exists('get_the_tags')) {
    function get_the_tags($post_id) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT t.* FROM terms t 
                                  INNER JOIN term_relationships tr ON t.term_id = tr.term_taxonomy_id 
                                  WHERE tr.object_id = ? AND t.taxonomy = 'post_tag'");
            $stmt->execute([$post_id]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            return [];
        }
    }
}

if (!function_exists('get_edit_post_link')) {
    function get_edit_post_link($post_id) {
        return admin_url('post.php?action=edit&post=' . $post_id);
    }
}

if (!function_exists('get_delete_post_link')) {
    function get_delete_post_link($post_id) {
        return admin_url('post.php?action=delete&post=' . $post_id);
    }
}

if (!function_exists('get_the_date')) {
    function get_the_date($format = '', $post_id = null) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT post_date FROM posts WHERE ID = ?");
            $date = $stmt->execute([$post_id])->fetchColumn();
            return $format ? date($format, strtotime($date)) : $date;
        } catch (PDOException $e) {
            return '';
        }
    }
}

if (!function_exists('get_the_time')) {
    function get_the_time($format = '', $post_id = null) {
        return get_the_date($format, $post_id);
    }
}

if (!function_exists('is_single')) {
    function is_single($post = '') {
        return isset($_GET['p']) && !empty($_GET['p']);
    }
}

if (!function_exists('is_page')) {
    function is_page($page = '') {
        return isset($_GET['page_id']) && !empty($_GET['page_id']);
    }
}

if (!function_exists('is_archive')) {
    function is_archive() {
        return isset($_GET['archive']) || isset($_GET['category_name']) || isset($_GET['tag']);
    }
}

if (!function_exists('is_category')) {
    function is_category($category = '') {
        return isset($_GET['category_name']) && !empty($_GET['category_name']);
    }
}

if (!function_exists('is_tag')) {
    function is_tag($tag = '') {
        return isset($_GET['tag']) && !empty($_GET['tag']);
    }
}

if (!function_exists('is_author')) {
    function is_author($author = '') {
        return isset($_GET['author']) && !empty($_GET['author']);
    }
}

if (!function_exists('is_404')) {
    function is_404() {
        http_response_code(404);
        return true;
    }
}

if (!function_exists('has_excerpt')) {
    function has_excerpt($post_id) {
        $excerpt = get_post_meta($post_id, 'post_excerpt', true);
        return !empty($excerpt);
    }
}

if (!function_exists('post_class')) {
    function post_class($class = '', $post_id = null) {
        $classes = is_array($class) ? $class : explode(' ', $class);
        $classes[] = 'post';
        $classes[] = 'post-' . $post_id;
        
        echo 'class="' . esc_attr(implode(' ', $classes)) . '"';
    }
}

if (!function_exists('body_class')) {
    function body_class($class = '') {
        $classes = is_array($class) ? $class : explode(' ', $class);
        $classes[] = 'custom-framework';
        
        if (is_admin()) {
            $classes[] = 'admin-area';
        }
        
        echo 'class="' . esc_attr(implode(' ', $classes)) . '"';
    }
}

if (!function_exists('wp_head')) {
    function wp_head() {
        do_action('wp_head');
    }
}

if (!function_exists('wp_footer')) {
    function wp_footer() {
        do_action('wp_footer');
    }
}

if (!function_exists('get_header')) {
    function get_header($name = null) {
        $templates = [];
        $name = (string) $name;
        if ('' !== $name) {
            $templates[] = "header-{$name}.php";
        }
        $templates[] = 'header.php';
        
        locate_template($templates, true);
    }
}

if (!function_exists('get_footer')) {
    function get_footer($name = null) {
        $templates = [];
        $name = (string) $name;
        if ('' !== $name) {
            $templates[] = "footer-{$name}.php";
        }
        $templates[] = 'footer.php';
        
        locate_template($templates, true);
    }
}

if (!function_exists('get_sidebar')) {
    function get_sidebar($name = null) {
        $templates = [];
        $name = (string) $name;
        if ('' !== $name) {
            $templates[] = "sidebar-{$name}.php";
        }
        $templates[] = 'sidebar.php';
        
        locate_template($templates, true);
    }
}

if (!function_exists('register_sidebar')) {
    function register_sidebar($args = []) {
        global $wp_registered_sidebars;
        if (!isset($wp_registered_sidebars)) {
            $wp_registered_sidebars = [];
        }
        
        $defaults = [
            'name' => '',
            'id' => '',
            'description' => '',
            'before_widget' => '<li id="%1$s" class="widget %2$s">',
            'after_widget' => '</li>',
            'before_title' => '<h2 class="widgettitle">',
            'after_title' => '</h2>'
        ];
        
        $sidebar = wp_parse_args($args, $defaults);
        $wp_registered_sidebars[$sidebar['id']] = $sidebar;
    }
}

if (!function_exists('dynamic_sidebar')) {
    function dynamic_sidebar($index = '') {
        global $wp_registered_sidebars;
        if (isset($wp_registered_sidebars[$index])) {
            echo '<div class="sidebar" id="' . esc_attr($index) . '">';
            do_action('dynamic_sidebar', $index);
            echo '</div>';
        }
    }
}

if (!function_exists('get_avatar')) {
    function get_avatar($id_or_email, $size = 96, $default = '', $alt = '', $args = null) {
        $user_id = is_numeric($id_or_email) ? $id_or_email : 0;
        return '<img src="' . getAvatarUrl($user_id) . '" width="' . $size . '" height="' . $size . '" alt="' . esc_attr($alt) . '" class="avatar" />';
    }
}

if (!function_exists('get_comments')) {
    function get_comments($args = []) {
        global $pdo;
        try {
            $query = "SELECT * FROM comments WHERE comment_approved = 1";
            if (isset($args['post_id'])) {
                $query .= " AND comment_post_ID = " . (int)$args['post_id'];
            }
            if (isset($args['number'])) {
                $query .= " LIMIT " . (int)$args['number'];
            }
            $stmt = $pdo->query($query);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            return [];
        }
    }
}

// In includes/plugin_api.php - Replace the DataTables function
function ensure_datatables_available() {
    // Load DataTables loader script
    echo '<script src="' . BASE_URL . '/assets/js/datatables-loader.js"></script>';
    
    // Initialize DataTables loading
    echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof DataTablesLoader !== "undefined") {
            // Pre-load DataTables for any plugins that might need it
            DataTablesLoader.load();
        } else {
            console.error("DataTablesLoader not found");
        }
    });
    </script>';
}

// Update the hook priorities
add_action('admin_head', 'ensure_datatables_available', 1); // Highest priority
add_action('user_head', 'ensure_datatables_available', 1);

// Add to includes/plugin_api.php - Better error handling for plugins
function handle_plugin_js_errors() {
    echo "
    <script>
    // Global error handler for plugin JavaScript
    window.addEventListener('error', function(e) {
        if (e.error && e.error.message && e.error.message.includes('jQuery') && e.filename.includes('plugins/')) {
            console.error('Plugin JS Error - jQuery not loaded:', e.filename);
            // Don't show alert in production, just log
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                console.warn('jQuery dependency issue detected in plugin. Retrying...');
                // Try to reload the script after jQuery is available
                setTimeout(function() {
                    if (typeof jQuery !== 'undefined') {
                        console.log('jQuery now available, plugin should work');
                    }
                }, 1000);
            }
        }
    });
    
    // Monitor when jQuery becomes available
    let jQueryCheckInterval = setInterval(function() {
        if (typeof jQuery !== 'undefined') {
            clearInterval(jQueryCheckInterval);
            console.log('jQuery is now available for plugins');
            // You could trigger a custom event here for plugins to listen to
            window.dispatchEvent(new CustomEvent('jqueryReady'));
        }
    }, 100);
    
    // Timeout after 5 seconds
    setTimeout(function() {
        clearInterval(jQueryCheckInterval);
    }, 5000);
    </script>
    ";
}

// Add this function to ensure jQuery loads before plugin scripts
function ensure_jquery_before_plugins() {
    global $enqueued_scripts;
    
    // If any plugin scripts depend on jQuery, make sure jQuery loads first
    $has_jquery_dependent_scripts = false;
    
    foreach ($enqueued_scripts as $handle => $script) {
        if (isset($script['deps']) && in_array('jquery', $script['deps'])) {
            $has_jquery_dependent_scripts = true;
            break;
        }
    }
    
    if ($has_jquery_dependent_scripts && !isset($enqueued_scripts['jquery'])) {
        // Force jQuery to load in header
        enqueue_script('jquery', 'https://code.jquery.com/jquery-3.6.0.min.js', [], '3.6.0', false);
    }
}

// In plugin_api.php - Add safety wrapper
// In plugin_api.php - Replace the safety wrapper function
function enqueue_plugin_safety_wrapper() {
    // Load safety wrapper IMMEDIATELY as inline script (not external file)
    $safety_wrapper_path = $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/assets/js/plugin-safety-wrapper.js';
    if (file_exists($safety_wrapper_path)) {
        echo '<script>';
        echo file_get_contents($safety_wrapper_path);
        echo '</script>';
    } else {
        // Fallback: define the safety system inline
        echo '<script>
        window.pluginSafetySystem = {
            plugins: {},
            register: function(pluginName, scriptUrl) {
                this.plugins[pluginName] = { url: scriptUrl, loaded: false };
                console.log("🔧 Plugin registered:", pluginName);
                
                // Auto-load if jQuery is already available
                if (typeof jQuery !== "undefined") {
                    this.load(pluginName);
                } else if (window.jQueryState && window.jQueryState.promise) {
                    window.jQueryState.promise.then(() => this.load(pluginName));
                }
            },
            load: function(pluginName) {
                const plugin = this.plugins[pluginName];
                if (!plugin || plugin.loaded) return;
                
                console.log("🚀 Loading plugin:", pluginName);
                const script = document.createElement("script");
                script.src = plugin.url;
                script.onload = () => {
                    plugin.loaded = true;
                    console.log("✅ Plugin loaded:", pluginName);
                };
                script.onerror = () => console.error("❌ Failed to load plugin:", pluginName);
                document.head.appendChild(script);
            },
            loadAll: function() {
                Object.keys(this.plugins).forEach(name => this.load(name));
            }
        };
        console.log("🔧 Plugin Safety System installed");
        </script>';
    }
}
add_action('admin_head', 'enqueue_plugin_safety_wrapper', 1); // Priority 1 = VERY EARLY
add_action('user_head', 'enqueue_plugin_safety_wrapper', 1);

// In plugin_api.php - Add plugin wrapper enqueue
function enqueue_plugin_wrapper() {
    enqueue_script('plugin-wrapper', BASE_URL . '/assets/js/plugin-wrapper.js', [], '1.0', false);
}
add_action('admin_head', 'enqueue_plugin_wrapper', 2); // After jQuery
add_action('user_head', 'enqueue_plugin_wrapper', 2);

// Remove any other jQuery enqueue calls at the bottom of the file
// And add this to force jQuery to load first
add_action('admin_head', 'ensure_jquery_available', 1); // Priority 1 = very early
add_action('user_head', 'ensure_jquery_available', 1);

// Add hook to run this before printing scripts
add_action('admin_head', 'ensure_jquery_before_plugins');
add_action('user_head', 'ensure_jquery_before_plugins');
add_action('admin_head', 'handle_plugin_js_errors');
add_action('user_head', 'handle_plugin_js_errors');

// Add this early in your initialization
//enqueue_script('jquery', 'https://code.jquery.com/jquery-3.6.0.min.js', [], '3.6.0', true);


