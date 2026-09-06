<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Waybills_Reports {

    public function waybills_reports_page() {
        global $wpdb;
    
        // Fetch filters from the request
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $role_filter = isset($_GET['role']) ? sanitize_text_field($_GET['role']) : '';
    
        // Build SQL query dynamically based on filters
        $where = "1=1";
        $params = [];
    
        // Apply different date filtering for 'Undelivered' status
        if (!empty($date_from) && !empty($date_to)) {
            if ($status_filter === 'Undelivered') {
                $where .= " AND arrival_date BETWEEN %s AND %s";  // Filter by arrival_date for Undelivered
            } else {
                $where .= " AND delivery_date BETWEEN %s AND %s"; // Filter by delivery_date for other statuses
            }
            $params[] = $date_from;
            $params[] = $date_to;
        }

        
    
        if (!empty($status_filter)) {
            $where .= " AND status = %s";
            $params[] = $status_filter;
        }
    
        if (!empty($role_filter)) {
            $where .= " AND ar.role = %s";
            $params[] = $role_filter;
        }
    
        $query = $wpdb->prepare("
            SELECT tm.*, ar.user_name, ar.role 
            FROM {$wpdb->prefix}truck_manifest tm
            LEFT JOIN {$wpdb->prefix}assignroles ar ON tm.role_id = ar.id
            WHERE $where
            AND (prepaid IS NULL OR prepaid = 0)
        ", $params);
    $results = $wpdb->get_results($query, ARRAY_A);
    
    
        ?>
        <div class="wrap">
            <h1>Waybills Reports</h1>
            <form method="get" action="" class="waybills-reports-form">
                <input type="hidden" name="page" value="waybills-reports">

                <div class="form-group">
                    <label for="date_from">From:</label>
                    <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>">
                </div>

                <div class="form-group">
                    <label for="date_to">To:</label>
                    <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>">
                </div>

                <div class="form-group">
                    <label for="status">Status:</label>
                    <select name="status">
                        <option value="">All</option>
                        <option value="Undelivered" <?php selected($status_filter, 'Undelivered'); ?>>Undelivered</option>
                        <option value="Delivered" <?php selected($status_filter, 'Delivered'); ?>>Delivered</option>
                        <option value="Collected" <?php selected($status_filter, 'Collected'); ?>>Collected</option>
                        <option value="Uncollected" <?php selected($status_filter, 'Uncollected'); ?>>Uncollected</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="role">Role:</label>
                    <select name="role">
                        <option value="">All</option>
                        <option value="Collector" <?php selected($role_filter, 'Collector'); ?>>Collector</option>
                        <option value="Customer" <?php selected($role_filter, 'Customer'); ?>>Customer</option>
                    </select>
                </div>

                <button type="submit">Filter</button>
            </form>

            <!-- Generate PDF Button -->
            <button id="generate-pdf-btn" disabled>Generate PDF</button>
            <iframe id="pdf-preview" style="width: 100%; height: 600px; display: none;" frameborder="0"></iframe>

            <!-- Table -->
            <table id="waybills-reports-table" class="widefat striped">
                <thead>
                    <tr>
                        <th>Waybill No</th>
                        <th>Consignee</th>
                        <th>Consignor</th>
                        <th>Description</th>
                        <th>Collect</th>
                        <th>Prepaid</th>
                        <th>Status</th>
                        <th>Delivery Date</th>
                        <th>Role</th>
                        <th>Assigned User</th>
                        <th>Select<input type="checkbox" id="select-all"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($results)): ?>
                        <?php foreach ($results as $row): ?>
                            <tr>
                                <td><?php echo esc_html($row['waybill_no']); ?></td>
                                <td><?php echo esc_html($row['consignee']); ?></td>
                                <td><?php echo esc_html($row['consignor']); ?></td>
                                <td><?php echo esc_html($row['descriptions']); ?></td>
                                <td><?php echo esc_html(number_format($row['collect'], 2)); ?></td>
                                <td><?php echo esc_html(number_format($row['prepaid'], 2)); ?></td>
                                <td><?php echo esc_html($row['status']); ?></td>
                                <td><?php echo esc_html($row['delivery_date']); ?></td>
                                <td><?php echo esc_html($row['role']); ?></td>
                                <td><?php echo esc_html($row['user_name']); ?></td>
                                <td><input type="checkbox" class="waybill-checkbox" value="<?php echo esc_attr($row['id']); ?>"></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="10">No records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

            <script>

            </script>

        <?php
    }
}

