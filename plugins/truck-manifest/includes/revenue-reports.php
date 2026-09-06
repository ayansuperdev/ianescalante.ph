<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Revenue_Reports {
    public function revenue_reports_page() {
        global $wpdb;

        // Fetch filters from the request
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        $truck_number = isset($_GET['truck_number']) ? sanitize_text_field($_GET['truck_number']) : '';

        // Build the SQL query based on the filters
        $where = '1=1';
        $params = [];

        if (!empty($date_from) && !empty($date_to)) {
            $where .= ' AND arrival_date BETWEEN %s AND %s';
            $params[] = $date_from;
            $params[] = $date_to;
        }

        if (!empty($truck_number)) {
            $where .= ' AND truck_number = %s';
            $params[] = $truck_number;
        }

        $query = $wpdb->prepare(
            "SELECT manifest_number, loading_date, truck_number, driver, arrival_date, manifest_revenue AS revenue
             FROM {$wpdb->prefix}manifest_details
             WHERE $where",
            $params
        );

        $results = $wpdb->get_results($query, ARRAY_A);
        ?>
        <div class="wrap">
            <h1>Revenue Reports</h1>

            <!-- Filter Form -->
            <div class="manifest-container revenue">
                <h2>Filter Reports</h2>
                <form id="revenue-filter-form">
                    <label for="date-from">From:</label>
                    <input type="date" id="date-from" name="date_from" value="<?php echo esc_attr($date_from); ?>" required>
                    <label for="date-to">To:</label>
                    <input type="date" id="date-to" name="date_to" value="<?php echo esc_attr($date_to); ?>" required>
                    <label for="truck-number">Truck Number (Optional):</label>
                    <input type="text" id="truck-number" name="truck_number" placeholder="Enter Truck Number" value="<?php echo esc_attr($truck_number); ?>">
                    <button type="button" id="filter-reports" class="button button-primary">Filter</button>
                </form>
            </div>

            <!-- Table for Filtered Revenue Reports -->
            <div>
                <table id="revenue-reports-table" class="widefat striped" >
                    <thead>
                        <tr>
                            
                            <th>Manifest Number</th>
                            <th>Loading Date</th>
                            <th>Truck Number</th>
                            <th>Driver</th>
                            <th>Arrival Date</th>
                            <th>Revenue</th>
                            <th>Select<input type="checkbox" id="select-all"></th>
                        </tr>
                    </thead>
                    <tbody id="revenue-table-body">
                    <?php if (!empty($results)): ?>
                        <?php foreach ($results as $row): ?>
                            <tr>
                                
                                <td><?php echo esc_html($row['manifest_number']); ?></td>
                                <td><?php echo esc_html($row['loading_date']); ?></td>
                                <td><?php echo esc_html($row['truck_number']); ?></td>
                                <td><?php echo esc_html($row['driver']); ?></td>
                                <td><?php echo esc_html($row['arrival_date']); ?></td>
                                <td>₱<?php echo esc_html(number_format($row['revenue'], 2)); ?></td>
                                <!-- Use manifest_number as the value -->
                                <td><input type="checkbox" class="waybill-checkbox" value="<?php echo esc_attr($row['manifest_number']); ?>"></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">No records found. Please filter to display results.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Generate PDF Button -->
            <div style="margin-top: 15px;">
                <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="download_revenue_pdf">
                    <input type="hidden" id="selected-rows" name="selected_rows" value="">
                    <button type="submit" class="button button-secondary" id="generate-pdf" disabled>Generate PDF</button>
                </form>
            </div>
            <!-- PDF Preview Container -->
            <h2 class="revenuepdf">Revenue PDF</h2>
            <div id="pdf-preview-container" style="margin-top: 20px;">
                <p class="paragrevenue">No PDF preview available. Select rows and click "Generate PDF" to preview.</p>
            </div>

        </div>

        <script>

        </script>
        <?php
    }
}
