<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Prepaid_Reports {
    public function prepaid_reports_page() {
        global $wpdb;

        $results = $wpdb->get_results("
        SELECT collected_date, delivery_date, waybill_no, consignee, consignor, descriptions, prepaid 
        FROM wp_truck_manifest 
        WHERE prepaid > 0
        ORDER BY collected_date ASC
    ");
    
    error_log('Prepaid Query Results: ' . print_r($results, true));

        ?>
<div class="wrap">
    <h1>Prepaid Reports</h1>
    <form id="filter-form" method="post">
        <label for="date_from">From:</label>
        <input type="date" id="date_from" name="date_from">
        <label for="date_to">To:</label>
        <input type="date" id="date_to" name="date_to">
        <button type="button" id="filter-reports" class="button-primary">Filter</button>
    </form>
    
    <button type="button" id="generate-prepaid-btn" class="button-primary">Generate PDF</button>
    
    <table class="widefat fixed" id="prepaid-table">
        <thead>
            <tr>
                
                <th>Collected Date</th>
                <th>Delivered Date</th>
                <th>Waybill Number</th>
                <th>Consignee</th>
                <th>Consignor</th>
                <th>Description</th>
                <th>Prepaid Amount</th>

                <th>Select<input type="checkbox" id="select-all"></th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($results)): ?>
            <?php foreach ($results as $row): ?>
                <tr>
                    
                    <td><?php echo esc_html($row->collected_date); ?></td>
                    <td><?php echo esc_html($row->delivery_date); ?></td>
                    <td><?php echo esc_html($row->waybill_no); ?></td>
                    <td><?php echo esc_html($row->consignee); ?></td>
                    <td><?php echo esc_html($row->consignor); ?></td>
                    <td><?php echo esc_html($row->descriptions); ?></td>
                    <td><?php echo esc_html(number_format($row->prepaid, 2)); ?></td>

                    <td><input type="checkbox" class="row-select" data-id="<?php echo esc_attr($row->waybill_no); ?>"></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="8">No records found. Use the filter above to display prepaid reports.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <!-- PDF Preview Container -->
    <div id="pdf-preview-container" style="margin-top: 20px; display: none;">
        <h2>PDF Preview</h2>
        <embed id="pdf-preview" src="" type="application/pdf" width="100%" height="600px" />
    </div>
</div>
<script src="<?php echo plugin_dir_url(__FILE__) . '../js/generate-prepaid.js'; ?>"></script>

        <?php
    }
}
