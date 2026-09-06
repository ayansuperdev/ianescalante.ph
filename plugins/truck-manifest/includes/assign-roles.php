<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Assign_Roles {
    public function __construct() {
        add_action('init', [$this, 'register_roles']);
        add_shortcode('admin_dashboard', [$this, 'admin_dashboard_shortcode']);
        add_shortcode('user_dashboard', [$this, 'user_dashboard_shortcode']);
    }

    // Register custom roles
    public function register_roles() {
        add_role('truck_admin', 'Truck Admin', [
            'read' => true,
            'manage_options' => true,
            'edit_posts' => true
        ]);
        
        add_role('truck_user', 'Truck User', [
            'read' => true
        ]);
    }

    public function display_assign_roles_page() {
        ?>
        <div class="wrap">
            <h1>Assign Roles</h1>

            <form id="assign-roles-form">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="user_name">Name:</label></th>
                        <td><input type="text" id="user_name" name="user_name" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="contact_number">Contact Number:</label></th>
                        <td><input type="text" id="contact_number" name="contact_number" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="email">Email:</label></th>
                        <td><input type="email" id="email" name="email" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="role">Role:</label></th>
                        <td>
                            <select id="role" name="role" required>
                                <option value="truck_user">Truck User</option>
                                <option value="truck_admin">Truck Admin</option>
                                <option value="Customer">Customer</option>
                                <option value="Collector">Collector</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <button type="button" id="save-role" class="button button-primary">Save</button>
            </form>

            <h2>Assigned Roles</h2>
            <table class="wp-list-table widefat fixed striped" id="assigned-roles-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Contact No.</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Rows will be dynamically populated via JavaScript -->
                </tbody>
            </table>
        </div>
        <?php
    }

    // Shortcode for admin dashboard
    public function admin_dashboard_shortcode() {
        if (!current_user_can('manage_options')) {
            return '<p>You do not have permission to view this page.</p>';
        }
    
        ob_start();
        echo '<h2>Admin Dashboard</h2><p>Welcome, Admin!</p>';


    
        $includes = [
            'assign-roles.php',
            'billing-waybills.php',
            'class-truck-manifest.php',
            'prepaid-reports.php',
            'revenue-reports.php',
            'upload-handler.php',
            'waybills-reports.php'
        ];
    
        foreach ($includes as $file) {
            $path = plugin_dir_path(__FILE__) . $file; // No extra 'includes/'
            echo "<p>Looking for: $path</p>";
            if (file_exists($path)) {
                include $path;
            } else {
                echo "<p><strong>Missing file:</strong> $file</p>";
            }
        }
        
    
        return ob_get_clean();
    }
    

    // Shortcode for user dashboard
    public function user_dashboard_shortcode() {
        if (!current_user_can('read')) {
            return '<p>You do not have permission to view this page.</p>';
        }
        ob_start();
        echo '<h2>User Dashboard</h2><p>Welcome, User!</p>';
        if (file_exists(plugin_dir_path(__FILE__) . 'includes/user-dashboard.php')) {
            include plugin_dir_path(__FILE__) . 'includes/user-dashboard.php';
        } else {
            echo '<p>User dashboard features are not available.</p>';
        }
        return ob_get_clean();
    }
}

new Assign_Roles();
