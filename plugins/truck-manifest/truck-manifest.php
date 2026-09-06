<?php
/*
Plugin Name: Truck Manifest
Description: Upload and extract Excel file data for truck manifests.
Version: 14.7
Author: Ian Escalante | Landways Cargo Service 
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include Composer autoload if it exists
if (file_exists(plugin_dir_path(__FILE__) . 'vendor/autoload.php')) {
    require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
}

// Include the main class file
require_once plugin_dir_path(__FILE__) . 'includes/class-truck-manifest.php';
require_once plugin_dir_path(__FILE__) . 'includes/billing-waybills.php';
require_once plugin_dir_path(__FILE__) . 'includes/assign-roles.php';
require_once plugin_dir_path(__FILE__) . 'includes/waybills-reports.php';
require_once plugin_dir_path(__FILE__) . 'includes/revenue-reports.php';
require_once plugin_dir_path(__FILE__) . 'includes/prepaid-reports.php';



// Instantiate the class
$truck_manifest = new Truck_Manifest();


// Initialize the plugin
function run_truck_manifest() {
    $plugin = new Truck_Manifest();
    $plugin->run();
}
run_truck_manifest();

// Database table creation
register_activation_hook(__FILE__, 'create_truck_manifest_table');

// Update Truck Manifest Table Creation with Status
function create_truck_manifest_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'truck_manifest';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        waybill_no varchar(255) NOT NULL,
        consignee varchar(255) NOT NULL,
        consignor varchar(255) NOT NULL,
        descriptions varchar(255) NOT NULL,
        collect DECIMAL(10,2) NOT NULL,
        prepaid DECIMAL(10,2) NOT NULL,
        manifest_number varchar(255) NOT NULL,
         
        status ENUM('Delivered','Undelivered','Collected','Uncollected') DEFAULT 'Undelivered',
        arrival_date date NOT NULL,
        discount int NOT NULL,
        net_amount int NOT NULL,
        remarks TEXT,
        delivery_date DATETIME DEFAULT NULL,
        collected_date DATETIME DEFAULT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}


// Database table creation for manifest details
register_activation_hook(__FILE__, 'create_manifest_details_table');

function create_manifest_details_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'manifest_details';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        manifest_number varchar(255) NOT NULL,
        loading_date date NOT NULL,
        truck_number varchar(255) NOT NULL,
        driver varchar(255) NOT NULL,
        arrival_date date NOT NULL,
        manifest_revenue int NOT NULL,
        upload_date DATETIME NOT NULL,,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// AJAX handler for updating waybill status
add_action('wp_ajax_update_waybill_status', 'update_waybill_status');
add_action('wp_ajax_nopriv_update_waybill_status', 'update_waybill_status');

function update_waybill_status() {
    global $wpdb;

    // Check if the user has the necessary capability
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized', 403);
    }

    // Retrieve the waybill ID and new status from the AJAX request
    $waybill_id = isset($_POST['waybill_id']) ? intval($_POST['waybill_id']) : 0;
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

    // Validate and update the status in the database
    if ($waybill_id && in_array($status, ['Delivered', 'Undelivered'])) {
        // Update the status in the truck_manifest table
        $result = $wpdb->update(
            $wpdb->prefix . 'truck_manifest', // Your table name
            ['status' => $status], // Data to update
            ['id' => $waybill_id] // Where clause
        );

        if ($result !== false) {
            // Send success response
            wp_send_json_success();
        } else {
            // Send error response for database failure
            wp_send_json_error('Database error');
        }
    } else {
        // Send error response for invalid input
        wp_send_json_error('Invalid data');
    }

    wp_die(); // Always die in AJAX functions
}

add_action('wp_ajax_filter_manifest_by_date', 'filter_manifest_by_date');
add_action('wp_ajax_nopriv_filter_manifest_by_date', 'filter_manifest_by_date');

function filter_manifest_by_date() {
    global $wpdb;
    $arrival_date = isset($_GET['arrival_date']) ? sanitize_text_field($_GET['arrival_date']) : '';

    // Query manifests based on arrival date
    $table_name = $wpdb->prefix . 'manifest_details';
    $manifests = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table_name WHERE arrival_date = %s", $arrival_date), ARRAY_A
    );

    wp_send_json($manifests);
    wp_die();
}

//timer

// Schedule the cron job on plugin activation
/*
register_activation_hook(__FILE__, 'schedule_uncollected_check_event');
function schedule_uncollected_check_event() {
    if (!wp_next_scheduled('update_uncollected_status_hook')) {
        wp_schedule_event(time(), 'thirty_seconds', 'update_uncollected_status_hook');
    }
    
}
*/

// Clear the event on plugin deactivation
register_deactivation_hook(__FILE__, 'clear_uncollected_check_event');
function clear_uncollected_check_event() {
    wp_clear_scheduled_hook('check_uncollected_status_event');
}

// Add custom 5-minute interval for cron
add_filter('cron_schedules', 'add_five_minute_cron_interval');
function add_five_minute_cron_interval($schedules) {
    $schedules['five_minutes'] = [
        'interval' => 5 * 60,
        'display'  => __('Every 5 Minutes')
    ];
    return $schedules;
}

//Use WordPress's wp_schedule_event to run checkAndUpdateUncollectedStatus() periodically. This will keep the status updates running independently of any user interaction.
// Hook into WordPress initialization to schedule the event
/*add_action('init', 'schedule_uncollected_status_check');
function schedule_uncollected_status_check() {
    if (!wp_next_scheduled('update_uncollected_status_hook')) {
        wp_schedule_event(time(), 'hourly', 'update_uncollected_status_hook');
    }
}*/

#add_action('update_uncollected_status_hook', 'checkAndUpdateUncollectedStatus');

// Clear the scheduled event on plugin deactivation
/*register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('update_uncollected_status_hook');
});*/

// Clear the scheduled event on plugin deactivation
/*register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('update_uncollected_status_hook');
});*/

// Define custom interval (1 minute for testing, set to 24 hours for production)
add_filter('cron_schedules', function($schedules) {
    $schedules['every_minute'] = [
        'interval' => 60,  // 1 minute for testing (86400 seconds for 24 hours in production)
        'display'  => __('Every Minute')
    ];
    return $schedules;
});

add_filter('cron_schedules', function ($schedules) {
    $schedules['thirty_seconds'] = [
        'interval' => 30,
        'display' => __('Every 30 Seconds')
    ];
    return $schedules;
});
