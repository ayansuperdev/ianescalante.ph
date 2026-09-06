<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Truck_Manifest {

    public function __construct() {
        add_action('wp_ajax_live_search', [$this, 'handle_live_search']);
        add_action('wp_ajax_nopriv_live_search', [$this, 'handle_live_search']);

        // Status update (Add handle_update_status here)
        add_action('wp_ajax_update_status', [$this, 'handle_update_status']);
        add_action('wp_ajax_nopriv_update_status', [$this, 'handle_update_status']); // If non-logged-in users should also access it

        // Initialization code here
        add_action('wp_ajax_get_waybill_details', array($this, 'get_waybill_details'));
        add_action('wp_ajax_nopriv_get_waybill_details', array($this, 'get_waybill_details'));
        add_action('wp_ajax_update_status', [$this, 'update_status']); // New action for status update

        add_action('wp_ajax_check_uncollected_status', [$this, 'check_uncollected_status']);
        add_action('check_uncollected_status_event', [$this, 'process_uncollected_status_update']);

        //Heartbeat
        add_action('wp_ajax_process_uncollected_truck_manifest', [$this, 'process_uncollected_truck_manifest']);        

        // New action for updating waybill details
        add_action('wp_ajax_update_waybill_details', array($this, 'update_waybill_details'));
        // AJAX handler for updating waybill status and delivery date
        add_action('wp_ajax_update_waybill_status', [$this, 'update_waybill_status']);


        // Register AJAX actions for both logged-in and non-logged-in users
        add_action('wp_ajax_filter_manifest_by_date_range', array($this, 'filter_manifest_by_date_range'));
        add_action('wp_ajax_nopriv_filter_manifest_by_date_range', array($this, 'filter_manifest_by_date_range'));

        // Hook the function to an AJAX action
        add_action('wp_ajax_search_waybills', [$this, 'ajax_search_waybills']);
        // Hook the function to an AJAX action
        add_action('wp_ajax_get_default_rows', [$this, 'ajax_get_default_rows']);
        // Hook the reset action to the AJAX handler
        add_action('wp_ajax_reset_waybill_status', [$this, 'ajax_reset_waybill_status']);

                // Instantiate Billing_Waybills
                $this->billing_page = new Billing_Waybills();
                // Instantiate Assign_Roles
                $this->assign_roles = new Assign_Roles();
                // Instantiate Waybills_Reports
                $this->waybills_reports = new Waybills_Reports();
                // Instantiate Revenue_Report
                $this->revenue_reports = new Revenue_Reports();
                // Instantiate Revenue_Report
                $this->prepaid_reports = new Prepaid_Reports();


        add_action('wp_ajax_delete_role', [$this, 'ajax_delete_role']);
        add_action('wp_ajax_save_role', [$this, 'ajax_save_role']);
        add_action('wp_ajax_load_roles', [$this, 'ajax_load_roles']);
        add_action('wp_ajax_nopriv_load_roles', [$this, 'ajax_load_roles']);
        wp_localize_script('assign-roles-js', 'assignRolesParams', [
            'ajax_url' => admin_url('admin-ajax.php')
        ]);


        // Register the AJAX action 
        add_action('wp_ajax_search_assign_roles', [$this, 'ajax_search_assign_roles']);
        add_action('wp_ajax_nopriv_search_assign_roles', [$this, 'ajax_search_assign_roles']);

        add_action('wp_ajax_update_assigned_role', [$this, 'ajax_update_assigned_role']);
        //add_action('wp_ajax_search_assign_roles', [$this, 'search_assign_roles']);

        add_action('wp_ajax_update_role_for_waybill', [$this, 'update_role_for_waybill']);

        add_action('wp_enqueue_scripts', [$this,  'enqueue_select2_scripts']);

        add_action('admin_enqueue_scripts', [$this, 'enqueue_datatables_scripts']);

        add_action('wp_ajax_generate_bill_pdf', [$this, 'generate_bill_pdf']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_generate_bill_script']);
        //enqueue_generate_report_script
        add_action('admin_enqueue_scripts', [$this, 'enqueue_generate_report_script']);
        // enqueue_generate_revenue_script
        add_action('admin_enqueue_scripts', [$this, 'enqueue_generate_revenue_script']);
        // enqueue_generate_prepaid_script
        add_action('admin_enqueue_scripts', [$this, 'enqueue_generate_prepaid_script']);
        add_action('wp_ajax_test_tcpdf', [$this, 'test_tcpdf_output']);

        add_action('wp_ajax_generate_selected_waybills_pdf', [$this, 'generate_selected_waybills_pdf']);
        add_action('wp_ajax_nopriv_generate_selected_waybills_pdf', [$this, 'generate_selected_waybills_pdf']);
        
        // Debugging log
        error_log('AJAX Hook: generate_selected_waybills_pdf is triggered.');

        add_action('wp_ajax_fetch_collected_reports', [$this, 'fetch_collected_reports']);

        // This is for the revenue reports
        add_action('wp_ajax_generate_revenue_report_pdf', [$this, 'generate_revenue_report_pdf']);
        add_action('wp_ajax_fetch_manifest_details', [$this, 'fetch_manifest_details']);

        add_action('wp_ajax_update_collected_date_popup_only',[$this, 'handle_update_collected_date_popup_only']);

        // Hook the AJAX action
        add_action('wp_ajax_fetch_prepaid_reports', [$this, 'fetch_prepaid_reports']);
        add_action('wp_ajax_nopriv_fetch_prepaid_reports', [$this, 'fetch_prepaid_reports']);

        add_action('wp_ajax_generate_prepaid_report', [$this, 'generate_prepaid_report']);

        add_action('wp_ajax_get_users_by_role', [$this, 'get_users_by_role']);
        add_action('wp_ajax_nopriv_get_users_by_role', [$this, 'get_users_by_role']);
    }

    public function run() {
        // Add admin menu
        add_action('admin_menu', array($this, 'create_menu'));
        // Enqueue admin scripts for error handling and popups
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

     // Enqueue scripts for the admin pages
     public function enqueue_admin_scripts() {
    // Load jQuery
    wp_enqueue_script('jquery');

        //wp_enqueue_script('truck-manifest-popup', plugin_dir_url(__FILE__) . '../js/truck-manifest-popup.js', array('jquery'), '1.0', true);
        // Include the CSS file for popup styling
        wp_enqueue_style('truck-manifest-popup', plugin_dir_url(__FILE__) . '../css/truck-manifest-popup.css', array(), '1.0', 'all');
        // Include the additional CSS file for truck manifest styles
        wp_enqueue_style('truck-manifest-styles', plugin_dir_url(__FILE__) . '../css/truck-manifest-styles.css', array(), '1.0', 'all');

        wp_enqueue_script('status-filter', plugin_dir_url(__FILE__) . '../js/status-filter.js', array('jquery', 'select2-js'), '1.0', true);

        // Enqueue the JavaScript file and pass ajax_url
        wp_enqueue_script('live-search', plugins_url('../js/live-search.js', __FILE__), array('jquery'), null, true);
        // Localize the script with the AJAX URL
        wp_localize_script('live-search', 'liveSearchParams', array(
            'ajax_url' => admin_url('admin-ajax.php')
        ));

        wp_enqueue_script('assign-roles-js', plugin_dir_url(__FILE__) . '../js/assign-roles.js', ['jquery'], '1.0', true);
        wp_localize_script('assign-roles-js', 'assignRolesParams', [
            'ajax_url' => admin_url('admin-ajax.php') // Ensure this matches
        ]);
        

        // Load Select2 library
    wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', ['jquery'], '4.0.13', true);
    wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css', [], '4.0.13');

      // Load your custom script
      wp_enqueue_script('select-search', plugin_dir_url(__FILE__) . '../js/status-filter.js', ['jquery', 'select2'], '1.0.0', true);
      wp_localize_script('select-search', 'ajax_assign', [
          'ajax_url' => admin_url('admin-ajax.php'),
      ]);
        
    }

    function enqueue_select2_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', ['jquery'], '4.0.13', true);
        wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css', [], '4.0.13');
    }

    function enqueue_datatables_scripts() {
        wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css');
        wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js', array('jquery'), null, true);
        wp_add_inline_script('datatables-js', 'jQuery(document).ready(function($) { $("#waybills-table").DataTable(); });');

        //wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css');
        //wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js', ['jquery'], null, true);
        wp_add_inline_script('datatables-js', 'jQuery(document).ready(function($) { $("#waybills-reports-table").DataTable(); });');

        wp_add_inline_script('datatables-js', 'jQuery(document).ready(function($) { $("#revenue-reports-table").DataTable(); });');
        wp_add_inline_script('datatables-js', 'jQuery(document).ready(function($) { $("#prepaid-table").DataTable(); });');

    }
 
    function enqueue_generate_bill_script() {
        wp_enqueue_script(
            'generate-bill-script', // Unique handle for the script
            plugin_dir_url(__FILE__) . '../js/generate-bill.js', // Path to the script file
            array('jquery'), // Dependencies
            '1.0', // Version
            true // Load in footer
        );


    
        // Pass WordPress AJAX URL to the script
        wp_localize_script('generate-bill-script', 'generateBillParams', [
            'ajaxurl' => admin_url('admin-ajax.php')
        ]);
        
    }

    function enqueue_generate_report_script() {
        wp_enqueue_script(
            'generate-report-script', // Unique handle for the script
            plugin_dir_url(__FILE__) . '../js/generate-report.js', // Path to the script file
            array('jquery'), // Dependencies
            '1.0', // Version
            true // Load in footer
        );


    
        // Pass WordPress AJAX URL to the script
        wp_localize_script('generate-report-script', 'generateReportParams', [
            'ajaxurl' => admin_url('admin-ajax.php')
        ]);
        
    }

    function enqueue_generate_revenue_script() {
        wp_enqueue_script(
            'generate-revenue-script', // Unique handle for the script
           plugin_dir_url(__FILE__) . '../js/generate-revenue.js', // Path to the script file
           array('jquery'), // Dependencies
            '1.0', // Version
            true // Load in footer
        );


    
        // Pass WordPress AJAX URL to the script
        wp_localize_script('generate-revenue-script', 'generateRevenueParams', [
            'ajaxurl' => admin_url('admin-ajax.php')
        ]);
        
    }

    function enqueue_generate_prepaid_script() {
        wp_enqueue_script(
            'generate-prepaid-script', // Unique handle for the script
           plugin_dir_url(__FILE__) . '../js/generate-prepaid.js', // Path to the script file
           array('jquery'), // Dependencies
            '1.0', // Version
            true // Load in footer
        );


    
        // Pass WordPress AJAX URL to the script
        wp_localize_script('generate-prepaid-script', 'generatePrepaidParams', [
            'ajaxurl' => admin_url('admin-ajax.php')
        ]);
        
    }
 
    
    
    // Create the admin menu
    public function create_menu() {
        add_menu_page(
            'Truck Manifest',      // Page title
            'Truck Manifest',      // Menu title
            'manage_options',      // Capability
            'truck-manifest',      // Menu slug
            array($this, 'upload_form_page') // Callback for upload form
        );

        // Add a submenu for viewing the manifests
        add_submenu_page(
            'truck-manifest',      // Parent slug
            'View Manifests',      // Page title
            'View Manifests',      // Menu title
            'manage_options',      // Capability
            'view-manifests',      // Menu slug
            array($this, 'view_manifests') // Callback for view manifests
        );

        // Add Waybills Reports submenu
        add_submenu_page(
            'truck-manifest',
            'Waybills Reports',
            'Waybills Reports',
            'manage_options',
            'waybills-reports',
            array($this->waybills_reports, 'waybills_reports_page') // Use the Waybills_Reports class's method
        );

        // Add to the Truck Manifest plugin's `create_menu` function
        add_submenu_page(
            'truck-manifest',
            'Billing Waybills',
            'Billing Waybills',
            'manage_options',
            'billing-waybills',
            array($this->billing_page, 'display_billing_page') // Use the Billing_Waybills class's method
        );

        add_submenu_page(
            'truck-manifest',
            'Revenue Reports',
            'Revenue Reports',
            'manage_options',
            'revenue-reports',
            array($this->revenue_reports, 'revenue_reports_page') // Use the Revenue_Report class's method
        );

        add_submenu_page(
            'truck-manifest',
            'Prepaid Reports',
            'Prepaid Reports',
            'manage_options',
            'prepaid-reports',
            array($this->prepaid_reports, 'prepaid_reports_page') // Use the Prepaid_Reports class's method
        );

        add_submenu_page(
            'truck-manifest',
            'Assign Roles',
            'Assign Roles',
            'manage_options',
            'assign-roles',
            array($this->assign_roles, 'display_assign_roles_page') // Use the Assign_Roles class's method
        );


    }

    public function fetch_prepaid_reports() {
        global $wpdb;
    
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
    
        if (!empty($date_from) && !empty($date_to)) {
            $results = $wpdb->get_results($wpdb->prepare("
                SELECT collected_date, delivery_date, waybill_no, consignee, consignor, descriptions, prepaid 
                FROM wp_truck_manifest 
                WHERE prepaid > 0 
                AND collected_date BETWEEN %s AND %s
                ORDER BY collected_date ASC
            ", $date_from, $date_to), ARRAY_A);
        } else {
            $results = $wpdb->get_results("
                SELECT collected_date, delivery_date, waybill_no, consignee, consignor, descriptions, prepaid 
                FROM wp_truck_manifest 
                WHERE prepaid > 0
                ORDER BY collected_date ASC
            ", ARRAY_A);
        }
    
        if (!empty($results)) {
            wp_send_json_success($results);
        } else {
            wp_send_json_error(['message' => 'No prepaid reports found.']);
        }
    
        wp_die();
    }
    
    public function generate_prepaid_report() {
        if (!isset($_POST['selected_rows']) || !is_array($_POST['selected_rows'])) {
            wp_send_json_error(['message' => 'Invalid request.']);
        }
// Validate and sanitize input dates
$date_from = !empty($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : 'Not Specified';
$date_to = !empty($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : 'Not Specified';

        $selected_rows = array_map('sanitize_text_field', $_POST['selected_rows']);
    
        global $wpdb;
        $results = $wpdb->get_results("
            SELECT collected_date, delivery_date, waybill_no, consignee, consignor, descriptions, prepaid
            FROM wp_truck_manifest
            WHERE waybill_no IN ('" . implode("','", $selected_rows) . "')
        ", ARRAY_A);
    
        if (empty($results)) {
            wp_send_json_error(['message' => 'No records found for the selected rows.']);
        }
    
        // Include TCPDF
        require_once plugin_dir_path(__FILE__) . '../vendor/tcpdf/tcpdf.php';
    
        $pdf = new TCPDF();
        $pdf->SetMargins(15, 15, 15);
        $pdf->AddPage();
    
        // Set Title
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'PREPAID REPORT', 0, 1, 'C');
    
        // Set Date Range
        $pdf->SetFont('helvetica', '', 12);
       // $pdf->Cell(0, 10, 'FROM: ________________   TO: ________________', 0, 1, 'C');
        $pdf->Cell(0, 10, "FROM: " . $date_from, 0, 1, 'L');
        $pdf->Cell(0, 10, "TO: " . $date_to, 0, 1, 'L');
        $pdf->Ln(5);
    
        // Generate HTML Table
        $html = '<table border="1" cellpadding="4" cellspacing="0" style="width:100%; text-align:center; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color:#f2f2f2;">
                            <th>DATE DELIVERED</th>
                            <th>WAYBILL NUMBER</th>
                            <th>CONSIGNEE</th>
                            <th>CONSIGNOR</th>
                            <th>DESCRIPTION</th>
                            <th>AMOUNT</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        $totalAmount = 0;
        foreach ($results as $row) {
            $html .= '<tr>
                        <td>' . htmlspecialchars($row['delivery_date']) . '</td>
                        <td>' . htmlspecialchars($row['waybill_no']) . '</td>
                        <td>' . htmlspecialchars($row['consignee']) . '</td>
                        <td>' . htmlspecialchars($row['consignor']) . '</td>
                        <td>' . htmlspecialchars($row['descriptions']) . '</td>
                        <td>' . number_format($row['prepaid'], 2) . '</td>
                    </tr>';
            $totalAmount += $row['prepaid'];
        }
    
 
    
        $html .= '</tbody></table>';

                // Write HTML Table to PDF
                $pdf->SetFont('helvetica', '', 10);
                $pdf->writeHTML($html, true, false, true, false, '');

        // Add Total Row
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(150, 8, 'TOTAL', 0, 0, 'R');
        $pdf->Cell(25, 8, number_format($totalAmount, 2), 0, 1, 'R');
    

    
        // Footer Section
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 10, 'PREPARED BY:', 0, 1);
        $pdf->Cell(0, 10, '__________________________', 0, 1);
        $pdf->Cell(0, 5, 'SIGNATURE OVER PRINTED NAME & DATE', 0, 1);
        $pdf->Ln(5);
        $pdf->Cell(0, 10, 'NOTED BY:', 0, 1);
        $pdf->Cell(0, 10, '__________________________', 0, 1);
        $pdf->Cell(0, 5, 'SIGNATURE OVER PRINTED NAME & DATE', 0, 1);
    
        // Output PDF as Base64
        $pdfContent = $pdf->Output('', 'S');
        $pdfBase64 = base64_encode($pdfContent);
    
        wp_send_json_success(['pdf_content' => $pdfBase64]);
    }
    
    

    public function fetch_manifest_details() {
        global $wpdb;
    
        // Sanitize inputs
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        $truck_number = isset($_GET['truck_number']) ? sanitize_text_field($_GET['truck_number']) : '';
    
        // Build table name
        $table_name = $wpdb->prefix . 'manifest_details';
    
        // Default query: fetch all records if no date range is provided
        if (empty($date_from) || empty($date_to)) {
            $query = "SELECT manifest_number, loading_date, truck_number, driver, arrival_date, manifest_revenue AS revenue 
                      FROM $table_name";
            $results = $wpdb->get_results($query);
        } else {
            // Filtered query: fetch based on date range and truck number
            $query = "SELECT manifest_number, loading_date, truck_number, driver, arrival_date, manifest_revenue AS revenue
                      FROM $table_name
                      WHERE loading_date BETWEEN %s AND %s";
            $params = [$date_from, $date_to];
    
            if (!empty($truck_number)) {
                $query .= " AND truck_number = %s";
                $params[] = $truck_number;
            }
    
            $results = $wpdb->get_results($wpdb->prepare($query, $params));
        }
    
        // Return results
        if ($results) {
            wp_send_json_success($results);
        } else {
            wp_send_json_error(['message' => 'No records found.']);
        }
    
        wp_die();
    }
    

    public function generate_revenue_report_pdf() {
        if (!class_exists('TCPDF')) {
            require_once plugin_dir_path(__FILE__) . '../vendor/tcpdf/tcpdf.php';
        }
    
        // Retrieve form inputs
        $selected_rows = isset($_POST['selected_rows']) ? (array) $_POST['selected_rows'] : [];
        $truck_number = isset($_POST['truck_number']) ? sanitize_text_field($_POST['truck_number']) : '';
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : 'N/A';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : 'N/A';
    
        global $wpdb;
        $table_name = $wpdb->prefix . 'manifest_details';
    
        // Fetch records from the database
        $placeholders = implode(', ', array_fill(0, count($selected_rows), '%s'));
        $query = $wpdb->prepare(
            "SELECT manifest_number, loading_date, truck_number, driver, arrival_date, manifest_revenue AS revenue
             FROM $table_name
             WHERE manifest_number IN ($placeholders)",
            $selected_rows
        );
        $results = $wpdb->get_results($query, ARRAY_A);
    
        if (!$results) {
            wp_send_json_error(['message' => 'No records found for the selected rows.']);
            wp_die();
        }
    
        // Initialize PDF
        $pdf = new TCPDF();
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
        $pdf->AddPage();
        // $pdf->SetFont('helvetica', '', 12);
        $pdf->SetFont('dejavusans', '', 9); // Use DejaVuSans for UTF-8 support
    
        // Add header image
        $headerImage = plugin_dir_path(__FILE__) . '../images/header.jpg';
        if (file_exists($headerImage)) {
            $pdf->Image($headerImage, 15, 10, 180, 30, '', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
    
        $pdf->Ln(40); // Add space below the image
    
        // Header Section
        $pdf->Cell(0, 10, "FROM: " . $date_from, 0, 1, 'L');
        $pdf->Cell(0, 10, "TO: " . $date_to, 0, 1, 'L');
        $pdf->Cell(0, 10, $truck_number ? "Revenue Reports - Plate Number: {$truck_number}" : "All Revenue Reports", 0, 1, 'C');
        $pdf->Ln();
    
        // Generate HTML Table
        $html = '<table border="1" cellpadding="4" cellspacing="0" style="width:100%; text-align:center; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color:#f2f2f2;">
                            <th>Number of Trucks</th>
                            <th>Date Arrived</th>
                            <th>Loading Date</th>
                            <th>Plate Number</th>
                            <th>Driver</th>
                            <th>Manifest Number</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>';
        $total_amount = 0;
        $truck_count = 1;
    
        foreach ($results as $row) {
            $html .= '<tr>';
            $html .= '<td align="center">' . $truck_count++ . '</td>';
            $html .= '<td align="center">' . esc_html($row['arrival_date']) . '</td>';
            $html .= '<td align="center">' . esc_html($row['loading_date']) . '</td>';
            $html .= '<td align="center">' . esc_html($row['truck_number']) . '</td>';
            $html .= '<td align="center">' . esc_html($row['driver']) . '</td>';
            $html .= '<td align="center">' . esc_html($row['manifest_number']) . '</td>';
            $html .= '<td align="right">₱' . number_format($row['revenue'], 2) . '</td>';
            $html .= '</tr>';
            $total_amount += $row['revenue'];
        }
    

        $html .= '</tbody></table>';

        // Write the HTML content to the PDF
        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->SetFont('dejavusans', '', 9);
        $pdf->Cell(190, 7, 'TOTAL: ' . number_format($total_amount, 2), 0, 1, 'R');

    
        // Footer Section: Prepared By / Noted By
        $pdf->Ln(10);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(0, 10, 'PREPARED BY', 0, 1, 'L');
        $pdf->Ln(10);
        $pdf->Cell(0, 10, '__________________________', 0, 1);
        $pdf->Cell(0, 10, 'SIGNATURE OVER PRINTED NAME', 0, 1, 'L');
        $pdf->Cell(0, 10, 'POSITION', 0, 1, 'L');
        $pdf->Ln(10);
        $pdf->Cell(0, 10, 'NOTED BY', 0, 1, 'L');
        $pdf->Ln(10);
        $pdf->Cell(0, 10, '__________________________', 0, 1);
        $pdf->Cell(0, 10, 'SIGNATURE OVER PRINTED NAME', 0, 1, 'L');
        $pdf->Cell(0, 10, 'POSITION', 0, 1, 'L');
    
        // Output PDF as base64
        $pdf_content = $pdf->Output('', 'S');
        $base64_pdf = base64_encode($pdf_content);
    
        wp_send_json_success(['pdf_base64' => $base64_pdf]);
        wp_die();
    }
    
    
    

    public function register_ajax_actions() {
        $ajax_actions = [
            'live_search' => 'handle_live_search',
            'update_status' => 'handle_update_status',
            'generate_bill_pdf' => 'generate_bill_pdf',
            'filter_manifest_by_date_range' => 'filter_manifest_by_date_range',
            // Add more actions as needed
        ];
    
        foreach ($ajax_actions as $action => $callback) {
            add_action("wp_ajax_$action", [$this, $callback]);
            add_action("wp_ajax_nopriv_$action", [$this, $callback]);
        }
    }
    
    function test_tcpdf_output() {
        require_once plugin_dir_path(__FILE__) . '../vendor/tcpdf/tcpdf.php';
        
        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Write(0, 'Test PDF Generation');
        $pdf->Output('test.pdf', 'I'); // Output directly to browser
        exit;
    }


    
    function get_users_by_role() {
        global $wpdb;
    
        $role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '';
    
        if (!empty($role)) {
            $users = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT user_name FROM {$wpdb->prefix}assignroles WHERE role = %s", $role));
        } else {
            $users = $wpdb->get_col("SELECT DISTINCT user_name FROM {$wpdb->prefix}assignroles");
        }
    
        wp_send_json($users);
    }
    

    public function generate_bill_pdf() {
        try {
            require_once plugin_dir_path(__FILE__) . '../vendor/tcpdf/tcpdf.php';
    
            if (!isset($_POST['waybill_ids']) || !is_array($_POST['waybill_ids']) || empty($_POST['waybill_ids'])) {
                wp_send_json_error('No waybill IDs provided. Please select at least one waybill.');
                return;
            }
    
            $waybill_ids = array_map('intval', $_POST['waybill_ids']);
            global $wpdb;
            $table_name = $wpdb->prefix . 'truck_manifest';
    
            $placeholders = implode(',', array_fill(0, count($waybill_ids), '%d'));
            $query = $wpdb->prepare(
                "SELECT tm.delivery_date, tm.arrival_date, tm.waybill_no, tm.consignee, tm.consignor, tm.descriptions, tm.collect, 
                        TIMESTAMPDIFF(DAY, tm.arrival_date, NOW()) AS aging, tm.remarks, 
                        ar.user_name, ar.role AS user_role
                 FROM {$table_name} tm
                 LEFT JOIN {$wpdb->prefix}assignroles ar ON tm.role_id = ar.id
                 WHERE tm.id IN ($placeholders)",
                ...$waybill_ids
            );
    
            $results = $wpdb->get_results($query, ARRAY_A);
            if (empty($results)) {
                wp_send_json_error('No waybills found.');
                return;
            }
    
            // Generate a new billing number
            $billing_number = 'BILL-' . time();

            // Calculate the total amount
            $total_amount = array_sum(array_column($results, 'collect'));
    
            // Update each selected waybill with the new billing number
            foreach ($waybill_ids as $waybill_id) {
                // Check if the waybill already has a billing number
                $existing_billing = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT id FROM {$wpdb->prefix}billing_numbers WHERE FIND_IN_SET(%d, waybill_ids) > 0",
                        $waybill_id
                    ),
                    ARRAY_A
                );
    
                if ($existing_billing) {
                    // Update the billing number for the existing record
                    $wpdb->update(
                        $wpdb->prefix . 'billing_numbers',
                        [
                            'billing_number' => $billing_number,
                            'user_role'      => $results[0]['user_role'],
                            'user_name'      => $results[0]['user_name'],
                            'total_amount'   => $total_amount,
                            'billing_date'   => current_time('mysql'),
                        ],
                        ['id' => $existing_billing['id']],
                        ['%s', '%s', '%s', '%f', '%s'],
                        ['%d']
                    );
                } else {
                    // Insert a new billing record for this waybill
                    $wpdb->insert(
                        $wpdb->prefix . 'billing_numbers',
                        [
                            'billing_number' => $billing_number,
                            'user_role'      => $results[0]['user_role'],
                            'user_name'      => $results[0]['user_name'],
                            'total_amount'   => $total_amount,
                            'billing_date'   => current_time('mysql'),
                            'waybill_ids'    => $waybill_id, // Store as a single ID
                        ],
                        ['%s', '%s', '%s', '%f', '%s', '%d']
                    );
                }
            }
            // Generate PDF
            $pdf = new TCPDF();
            $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
            $pdf->AddPage();
            $pdf->SetFont('helvetica', '', 12);
    
            // Add header image
            $headerImage = plugin_dir_path(__FILE__) . '../images/header.jpg';
            if (file_exists($headerImage)) {
                $pdf->Image($headerImage, 15, 10, 180, 30, '', '', 'T', false, 300, '', false, false, 0, false, false, false);
            }
    
            $pdf->Ln(40);
    
            // Title and details
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, 'BILLING STATEMENT', 0, 1, 'C');
                        // General details
            $pdf->SetFont('helvetica', '', 12);
            $billing_date = date('Y-m-d h:i:s A');
            $pdf->Ln(5);
            $pdf->Cell(0, 10, 'Billing Date: ' . $billing_date, 0, 1, 'L');
            $pdf->Cell(0, 10, 'Billing Number: ' . $billing_number, 0, 1, 'L');
        // Determine whether to show Customer Name or Collector Name based on the role
        if ($results[0]['user_role'] === 'Customer') {
            $pdf->Cell(0, 10, 'Customer Name: ' . $results[0]['consignee'], 0, 1, 'L');
        } else if ($results[0]['user_role'] === 'Collector') {
            $pdf->Cell(0, 10, 'Collector Name: ' . $results[0]['consignor'], 0, 1, 'L');
        }
            // Add table content
            // (same as your existing logic)
            $pdf->Ln(10); // Add space
        
        // Define table columns based on the role
        $columns = ($results[0]['user_role'] === 'Customer')
            ? ['Date Delivered', 'Waybill Number', 'Consignee', 'Description', 'Amount'] // For Customer
            : ['Date Delivered', 'Waybill Number', 'Consignee', 'Consignor', 'Description', 'Amount']; // For Collector

    
            // Generate table
            $html = '<table border="1" cellpadding="4">
                        <thead>
                            <tr>';
            foreach ($columns as $col) {
                $html .= '<th style="background-color:#f2f2f2;">' . esc_html($col) . '</th>'; // Add background color to header
            }
            $html .= '   </tr>
                        </thead>
                        <tbody>';
            foreach ($results as $waybill) {
                $html .= '<tr>';
                $html .= '<td>' . esc_html($waybill['delivery_date']) . '</td>';
                $html .= '<td>' . esc_html($waybill['waybill_no']) . '</td>';
                if ($results[0]['user_role'] === 'Collector') { //($results[0]['user_role'] === 'Customer')
                    $html .= '<td>' . esc_html($waybill['consignee']) . '</td>';
                }
                $html .= '<td>' . esc_html($waybill['consignor']) . '</td>';
                $html .= '<td>' . esc_html($waybill['descriptions']) . '</td>';
                $html .= '<td>' . esc_html(number_format($waybill['collect'], 2)) . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
            $pdf->writeHTML($html, true, false, true, false, '');
    
            // Totals
            $pdf->Ln(10);
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Cell(180, 7, 'TOTAL: ' . number_format($total_amount, 2), 0, 1, 'R');
            $pdf->Cell(180, 7, 'DISCOUNT: ______________________', 0, 1, 'R');
            $pdf->Cell(180, 7, 'NET AMOUNT: ______________________', 0, 1, 'R');
    
            // Footer section
            $pdf->Ln(10);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 5, 'Please settle this amount immediately. Thank you.', 0, 1, 'L');
    
            // Signature placeholders
            $html = '<table cellpadding="4">
                        <tr><td>PREPARED BY:</td><td></td></tr>
                        <tr><td>SIGNATURE OVER PRINTED NAME</td><td>DATE</td></tr>
                        <tr><td>NOTED BY:</td><td></td></tr>
                        <tr><td>SIGNATURE OVER PRINTED NAME</td><td>DATE</td></tr>';
            if ($user_role === 'Collector') {
                $html .= '<tr><td>COLLECTED BY:</td><td></td></tr>';
            }
            $html .= '<tr><td>RECEIVED BY:</td><td></td></tr>
                        <tr><td>SIGNATURE OVER PRINTED NAME</td><td>DATE</td></tr>
                      </table>';
            $pdf->writeHTML($html, true, false, true, false, '');
    
            // Output PDF
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="waybill.pdf"');
            echo $pdf->Output('', 'S');
            wp_die();
        } catch (Exception $e) {
            error_log('Error in generate_bill_pdf: ' . $e->getMessage());
            wp_send_json_error('Failed to generate the PDF: ' . $e->getMessage());
        }
    }
    
    
    
public function generate_selected_waybills_pdf() {
    if (!defined('DOING_AJAX') || !DOING_AJAX) {
        return;
    }

    global $wpdb;

    // Fetch waybill IDs and filters
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
    $waybill_ids = isset($_POST['waybill_ids']) ? array_map('intval', explode(',', $_POST['waybill_ids'])) : [];
    $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
    $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
    $role_filter = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '';

    // Build the query dynamically
    $where = "1=1";
    $params = [];

    if (!empty($waybill_ids)) {
        $placeholders = implode(',', array_fill(0, count($waybill_ids), '%d'));
        $where .= " AND tm.id IN ($placeholders)";
        $params = array_merge($params, $waybill_ids);
    }

    if (!empty($status)) {
        $where .= " AND tm.status = %s";
        $params[] = $status;
    }

    if (!empty($date_from) && !empty($date_to)) {
        if ($status === 'Undelivered') {
            $where .= " AND DATE(tm.arrival_date) BETWEEN %s AND %s"; // Use arrival_date for Undelivered
        } else {
            $where .= " AND DATE(tm.delivery_date) BETWEEN %s AND %s"; // Use delivery_date for other statuses
        }
        $params[] = $date_from;
        $params[] = $date_to;
    }

    if (!empty($role_filter)) {
        $where .= " AND ar.role = %s";
        $params[] = $role_filter;
    }

    // Fetch records
    $query = "
        SELECT tm.*, ar.user_name, TIMESTAMPDIFF(DAY, tm.arrival_date, NOW()) AS aging 
        FROM {$wpdb->prefix}truck_manifest tm
        LEFT JOIN {$wpdb->prefix}assignroles ar ON tm.role_id = ar.id
        WHERE $where
        ORDER BY ar.user_name ASC
    ";
    $prepared_query = $wpdb->prepare($query, $params);
    $results = $wpdb->get_results($prepared_query);

            // Debugging: Log query and results
            error_log("Generated Query: " . $prepared_query);
            error_log("Results Count: " . count($results));

    // Group results by user_name
    $grouped_data = [];
    foreach ($results as $row) {
        $user_name = $row->user_name ?? 'Unknown';
        $grouped_data[$user_name][] = $row;
    }

    // Table headers discount_rate
    $headers = [
        'Undelivered' => ['Date Arrived', 'Waybill Number', 'Consignee', 'Consignor', 'Description', 'Amount', 'Aging', 'Remarks'],
        'Uncollected' => ['Date Delivered', 'Waybill Number', 'Consignee', 'Consignor', 'Description', 'Amount', 'Aging', 'Remarks'],
        'Delivered'   => ['Date Delivered', 'Waybill Number', 'Consignee', 'Consignor', 'Description', 'Amount'],
        'Collected'   => ['Date Delivered', 'Date Collected', 'Waybill Number', 'Consignee', 'Consignor', 'Description', 'Amount', 'Discount', 'Discount Rate', 'Net Amount']
    ];
    $columns = isset($headers[$status]) ? $headers[$status] : ['Date Delivered', 'Date Collected', 'Waybill Number', 'Consignee', 'Consignor', 'Description', 'Amount', 'Discount', 'Discount Rate', 'Net Amount'];

    // Generate PDF
    require_once plugin_dir_path(__FILE__) . '../vendor/tcpdf/tcpdf.php';
    $pdf = new TCPDF();
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 14);

    // Add header image
    $headerImage = plugin_dir_path(__FILE__) . '../images/header.jpg';
    if (file_exists($headerImage)) {
        $pdf->Image($headerImage, 15, 10, 180, 30, '', '', 'T', false, 300, '', false, false, 0, false, false, false);
    }
    $pdf->Ln(40);

    // Report Title and Date Range
    $title = !empty($status) ? strtoupper($status) . " Waybills Report" : " Waybills Report";
    $pdf->Cell(0, 10, $title, 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 10, "FROM: {$date_from} TO: {$date_to}", 0, 1, 'L');
    $pdf->Ln(5);

    // Initialize global totals
    $total_collect = 0;
    $total_discount = 0;
    $total_discount_rate = 0;
    $total_net_amount = 0;

    // Loop through grouped data
    foreach ($grouped_data as $user_name => $rows) {
        // Customer or Collector Name
        if ($status === 'Collected') {
            $label = ($role_filter === 'collector') ? "COLLECTOR NAME: " : "CUSTOMER NAME: ";
            $pdf->SetFont(family: 'helvetica', style: 'B', size: 12);
            $pdf->Cell(0, 10, "{$label}{$user_name}", 0, 1, 'L');
        }
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Ln(5);

        // Generate table
        $html = '<table border="1" cellpadding="4"><thead><tr>';
        foreach ($columns as $col) {
            $html .= '<th style="background-color:#f2f2f2;">' . esc_html($col) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>';
    
            if ($status === 'Undelivered') {
                $html .= '<td>' . esc_html($row->arrival_date) . '</td>';
            } elseif ($status === 'Collected') {
                $html .= '<td>' . esc_html($row->delivery_date) . '</td>';
                $html .= '<td>' . esc_html($row->collected_date) . '</td>';
            } else {
                $html .= '<td>' . esc_html($row->delivery_date) . '</td>';
                $html .= '<td>' . esc_html($row->collected_date) . '</td>';
            }
    
            $html .= '<td>' . esc_html($row->waybill_no) . '</td>';
            $html .= '<td>' . esc_html($row->consignee) . '</td>';
            $html .= '<td>' . esc_html($row->consignor) . '</td>';
            $html .= '<td>' . esc_html($row->descriptions) . '</td>';
            $html .= '<td>' . number_format($row->collect, 2) . '</td>';
    
            if ($status === 'Uncollected' || $status === 'Undelivered') {
                $html .= '<td>' . esc_html($row->aging . ' days') . '</td>';
                $html .= '<td>' . esc_html($row->remarks) . '</td>';
            }

            if ($status === 'Collected') {
                $html .= '<td>' . number_format($row->discount, 2) . '</td>';
                $html .= '<td>' . number_format($row->discount_rate, 2) . '</td>';
                $html .= '<td>' . number_format($row->net_amount, 2) . '</td>';
                $total_discount += $row->discount;
                $total_discount_rate += $row->discount_rate;
                $total_net_amount += $row->net_amount;
            }else {
                $html .= '<td>' . number_format($row->discount, 2) . '</td>';
                $html .= '<td>' . number_format($row->discount_rate, 2) . '</td>';
                $html .= '<td>' . number_format($row->net_amount, 2) . '</td>';
            }

            // Accumulate global totals
            $total_collect += $row->collect;
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        // Write HTML for current group
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Ln(5);
    }
        // Append Totals discount_rate Add global totals at the end of the entire table
        $html_totals .= '<table border="0" cellpadding="4">
                    <tr>
                        <td colspan="7" align="right"><strong>TOTAL AMOUNT:</strong></td>
                        <td>' . number_format($total_collect, 2) . '</td>
                    </tr>';
        if ($status === 'Collected') {
            $html_totals .= '<tr>
                        <td colspan="7" align="right"><strong>TOTAL DISCOUNT:</strong></td>
                        <td>' . number_format($total_discount, 2) . '</td>
                      </tr>
                        <tr>
                        <td colspan="7" align="right"><strong>Discount Rate:</strong></td>
                        <td>' . number_format($total_discount_rate, 2) . '</td>
                      </tr>
                      <tr>
                        <td colspan="7" align="right"><strong>TOTAL NET AMOUNT:</strong></td>
                        <td>' . number_format($total_net_amount, 2) . '</td>
                      </tr>';
        }
        $html_totals .= '</table>';

        $pdf->writeHTML($html_totals, true, false, true, false, '');
        $pdf->Ln(5);

    // Prepared By Section
            $pdf->Ln(10);
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(0, 10, 'PREPARED BY:', 0, 1, 'L');
            $pdf->Cell(0, 10, '__________________________', 0, 1);
            $pdf->Cell(0, 10, 'SIGNATURE OVER PRINTED NAME DATE', 0, 1, 'L');
            $pdf->Cell(0, 10, 'POSITION', 0, 1, 'L');
        
            if ($status === 'Collected') {
                $pdf->Ln(5);
                $pdf->Cell(0, 10, 'COLLECTED BY:', 0, 1, 'L');
                $pdf->Cell(0, 10, '__________________________', 0, 1);
                $pdf->Cell(0, 10, 'SIGNATURE OVER PRINTED NAME DATE', 0, 1, 'L');
                $pdf->Cell(0, 10, 'POSITION', 0, 1, 'L');
            }
        
            $pdf->Ln(5);
            $pdf->Cell(0, 10, 'NOTED BY:', 0, 1, 'L');
            $pdf->Cell(0, 10, '__________________________', 0, 1);
            $pdf->Cell(0, 10, 'SIGNATURE OVER PRINTED NAME DATE', 0, 1, 'L');
            $pdf->Cell(0, 10, 'POSITION', 0, 1, 'L');
        
            // Output the PDF
            $pdf->Output(strtolower($status) . '_waybills_report.pdf', 'I');
    
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="waybills_report.pdf"');
    
    // $pdf->Output(strtolower($status) . '_waybills_report.pdf', 'I');
    wp_die();
}

       
    
    
    public function fetch_collected_reports() {
        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            return;
        }
        
        global $wpdb;
    
        // Sanitize date inputs
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
    
        // Base query: fetch only "Collected" waybills
        $query = "SELECT delivery_date, collected_date, waybill_no, consignee, consignor, descriptions, collect, discount, net_amount 
                  FROM {$wpdb->prefix}truck_manifest
                  WHERE status = 'Collected'
                  AND (prepaid IS NULL OR prepaid = 0)"; // Exclude rows where Prepaid has a value
    
        // Build date filters dynamically
        $params = [];
        if (!empty($date_from)) {
            $query .= " AND DATE(delivery_date) >= %s";
            $params[] = $date_from;
        }
        if (!empty($date_to)) {
            $query .= " AND DATE(delivery_date) <= %s";
            $params[] = $date_to;
        }
    
        // Prepare and execute the query
        if (!empty($params)) {
            $query = $wpdb->prepare($query, ...$params);
        }
    
        $results = $wpdb->get_results($query);
    
        // Check results and send response
        if (empty($results)) {
            wp_send_json_error('No Collected waybills found within the specified dates.');
        } else {
            wp_send_json_success($results);
        }
    }
    
    
    public function ajax_search_assign_roles() {
        global $wpdb;
        $assignroles_table = $wpdb->prefix . 'assignroles';
    
        // Get the search term
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    
        // Return empty results for an empty search term
        if (empty($search)) {
            wp_send_json([
                'success' => true,
                'results' => [] // Return an empty array
            ]);
            return;
        }
    
        // Fetch matching users
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, user_name AS text 
                 FROM $assignroles_table 
                 WHERE user_name LIKE %s 
                 LIMIT 10",
                '%' . $wpdb->esc_like($search) . '%'
            ),
            ARRAY_A
        );
    
        // Log for debugging
        error_log("Search term: $search");
        error_log("Results: " . print_r($results, true));
    
        // Return results or a "no matches" message if empty
        if (empty($results)) {
            wp_send_json([
                'success' => true,
                'results' => [
                    [
                        'id' => 'no-matches',
                        'text' => 'No matching users found.'
                    ]
                ]
            ]);
        } else {
            wp_send_json([
                'success' => true,
                'results' => $results
            ]);
        }
    }

    public function ajax_update_assigned_role() {
        global $wpdb;
        $manifests_table = $wpdb->prefix . 'truck_manifest';
    
        $waybill_id = isset($_POST['waybill_id']) ? intval($_POST['waybill_id']) : 0;
        $role_id = isset($_POST['role_id']) ? intval($_POST['role_id']) : 0;
    
        if ($waybill_id > 0 && $role_id > 0) {
            $result = $wpdb->update(
                $manifests_table,
                ['role_id' => $role_id],
                ['id' => $waybill_id],
                ['%d'],
                ['%d']
            );
    
            if ($result !== false) {
                wp_send_json_success();
            } else {
                wp_send_json_error('Failed to update the assigned role.');
            }
        } else {
            wp_send_json_error('Invalid waybill or role ID.');
        }
    }
    public function save_assigned_user() {
        global $wpdb;
        $waybill_id = isset($_POST['waybill_id']) ? intval($_POST['waybill_id']) : 0;
        $role_id = isset($_POST['role_id']) ? sanitize_text_field($_POST['role_id']) : '';
    
        if ($waybill_id > 0 && !empty($role_id)) {
            $result = $wpdb->update(
                $wpdb->prefix . 'truck_manifest',
                ['role_id' => $role_id],
                ['id' => $waybill_id],
                ['%s'],
                ['%d']
            );
    
            error_log("Waybill ID: $waybill_id, Role ID: $role_id, Result: $result");
    
            if ($result !== false) {
                wp_send_json_success("Assigned user saved successfully.");
            } else {
                wp_send_json_error("Database update failed.");
            }
        } else {
            wp_send_json_error("Invalid input.");
        }
    
        wp_die();
    }
    


// Form for uploading Excel file and adding manifest details
public function upload_form_page() {
    ?>
    <div class="wrap">
        <h1>Submit Manifest Details</h1>
        <div class="container">
        <form method="post" enctype="multipart/form-data">
            <h2>Upload Truck Manifest Excel File</h2>
            <input type="file" name="manifest_file" accept=".xlsx" required><br><br>

            <h2>Manifest Details</h2>
            <label for="manifest_number">Manifest Number:</label>
            <input type="text" name="manifest_number" required><br><br>

            <label for="loading_date">Loading Date:</label>
            <input type="date" name="loading_date" required><br><br>

            <label for="truck_number">Plate Number:</label>
            <input type="text" name="truck_number" required><br><br>

            <label for="driver">Driver:</label>
            <input type="text" name="driver" required><br><br>

            <label for="arrival_date">Arrival Date:</label>
            <input type="date" name="arrival_date" required><br><br>

            <label for="manifest_revenue">Revenue:</label>
            <input type="text" name="manifest_revenue" placeholder="Enter Revenue Amount" required><br><br>


            <?php submit_button('Submit Manifest Details'); ?>
        </form>
        </div>
        <?php
        // Handle form submission after form is submitted
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['manifest_file'])) {
            // Handle the file upload
            $upload_success = $this->handle_file_upload();
            if ($upload_success) {
                // Save manifest details to the database
                $this->save_manifest_details($_POST);
                // Also save the truck manifest
                $this->save_truck_manifest($_POST); // Call this function to save to truck_manifest
                echo "<script>showPopup('Manifest submitted successfully', 'success');</script>";
            } else {
                echo "<script>showPopup('Error occurred during upload', 'error');</script>";
            }
        }
        
        ?>
    </div>
    <?php
}

// Function to save manifest details in the manifest_details table
public function save_manifest_details($data) {
    global $wpdb;
    $details_table_name = $wpdb->prefix . 'manifest_details';
    $manifest_table_name = $wpdb->prefix . 'truck_manifest';

    // Validate required fields
    if (empty($data['manifest_number']) || empty($data['loading_date']) || empty($data['truck_number']) || empty($data['driver']) || empty($data['arrival_date']) || empty($data['manifest_revenue'])) {
        wp_send_json_error('Required fields are missing.');
        return;
    }

    // Function to remove currency symbols and commas
    function sanitize_currency($value) {
        return floatval(str_replace([',', '₱', '$'], '', $value));
    }

    // Start transaction to maintain data consistency
    $wpdb->query('START TRANSACTION');

    error_log("Sanitized manifest_revenue amount: " . sanitize_currency($data['manifest_revenue']));

    // Insert into manifest_details table
    $inserted = $wpdb->insert($details_table_name, array(
        'manifest_number' => sanitize_text_field($data['manifest_number']),
        'loading_date'    => sanitize_text_field($data['loading_date']),
        'truck_number'    => sanitize_text_field($data['truck_number']),
        'driver'          => sanitize_text_field($data['driver']),
        'arrival_date'    => sanitize_text_field($data['arrival_date']),
        'manifest_revenue' => sanitize_text_field($data['manifest_revenue']),
        
        'upload_date' => isset($data['upload_date']) ? sanitize_text_field($data['upload_date']) : current_time('mysql')
    ));

    // Check for successful insert in manifest_details
    if ($inserted === false) {
        $wpdb->query('ROLLBACK'); // Rollback on failure
        echo '<div class="truck-manifest-notice notice notice-error is-dismissible"><p>Database insert failed: ' . $wpdb->last_error . '</p></div>';
        return;
    }

    // Get the inserted manifest_number
    $manifest_number = sanitize_text_field($data['manifest_number']);

    // Update each record in truck_manifest that should have this manifest_number
    $result = $wpdb->update(
        $manifest_table_name,
        array('manifest_number' => $manifest_number), // Update with manifest_number
        array('manifest_number' => '') // Only update rows where manifest_number is empty
    );

    if ($result === false) {
        $wpdb->query('ROLLBACK'); // Rollback if update fails
        echo '<div class="truck-manifest-notice notice notice-error is-dismissible"><p>Failed to link manifest details with truck manifest records.</p></div>';
    } else {
        $wpdb->query('COMMIT'); // Commit if update is successful
        echo '<div class="truck-manifest-notice notice notice-success is-dismissible"><p>Manifest details saved and all truck manifest records updated with manifest_number: ' . $manifest_number . '</p></div>';
    }
}

public function import_excel_data($excel_data, $manifest_number) {
    global $wpdb;
    $manifest_table_name = $wpdb->prefix . 'truck_manifest';

    // Begin transaction for data consistency
    $wpdb->query('START TRANSACTION');

    try {
        foreach ($excel_data as $index => $row) {
            // Skip the header row explicitly (assuming the header is always the first row)
            if ($index === 0) {
                continue;
            }

            // Skip rows that don't contain essential data
            if (empty($row['waybill_no']) && empty($row['consignee']) && empty($row['consignor'])) {
                continue;
            }

            // Insert each row with the specified manifest_number directly
            $result = $wpdb->insert(
                $manifest_table_name,
                array(
                    'waybill_no'     => sanitize_text_field($row['waybill_no']),
                    'consignee'      => sanitize_text_field($row['consignee']),
                    'consignor'      => sanitize_text_field($row['consignor']),
                    'descriptions'   => sanitize_text_field($row['descriptions']),
                    'collect'        => intval($row['collect']),
                    'prepaid'        => intval($row['prepaid']),
                    'manifest_number' => $manifest_number
                )
            );

            if ($result === false) {
                throw new Exception('Failed to insert row: ' . $wpdb->last_error);
            }
        }

        // Commit the transaction if all inserts were successful
        $wpdb->query('COMMIT');
        echo '<div class="truck-manifest-notice notice notice-success is-dismissible"><p>All records from Excel imported successfully with manifest number: ' . $manifest_number . '</p></div>';
    } catch (Exception $e) {
        // Rollback the transaction if any insertion fails
        $wpdb->query('ROLLBACK');
        echo '<div class="truck-manifest-notice notice notice-error is-dismissible"><p>Import failed: ' . $e->getMessage() . '</p></div>';
    }
}

// Function to force link the manifest number to all records in truck_manifest
private function force_link_manifest($arrival_date, $manifest_number) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'truck_manifest';

    // Sanitize inputs
    $manifest_number = sanitize_text_field($manifest_number);
    $arrival_date = sanitize_text_field($arrival_date); // Ensure date is sanitized

    // Check if there are any records in truck_manifest with the given manifest_number
    $existing_manifest = $wpdb->get_row($wpdb->prepare("SELECT id FROM $table_name WHERE manifest_number = %s", $manifest_number), ARRAY_A);

    if ($existing_manifest) {
        // If records exist, update to link the arrival_date
        $result = $wpdb->update(
            $table_name,
            array('arrival_date' => $arrival_date), // Set the arrival_date
            array('manifest_number' => $manifest_number) // Where manifest_number matches
        );

        if ($result === false) {
            error_log('Failed to link arrival date ' . $arrival_date . ' with existing manifest number ' . $manifest_number . ': ' . $wpdb->last_error);
            return false;
        } else {
            error_log('Successfully linked arrival date ' . $arrival_date . ' to all records with manifest number: ' . $manifest_number);
            return true;
        }
    } else {
        // If no records exist, insert a new row with manifest_number and arrival_date
        $result = $wpdb->insert(
            $table_name,
            array(
                'manifest_number' => $manifest_number,
                'arrival_date' => $arrival_date,
                'manifest_date' => current_time('mysql') // or any default date
            )
        );

        if ($result === false) {
            error_log('Failed to create new manifest entry for manifest number ' . $manifest_number . ': ' . $wpdb->last_error);
            return false;
        } else {
            error_log('Successfully created a new manifest entry with manifest number ' . $manifest_number . ' and arrival date: ' . $arrival_date);
            return true;
        }
    }
}

// This function should exist somewhere in your codebase to save truck manifests.
public function save_truck_manifest($data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'truck_manifest';

    // Check if required fields are present
    if (empty($data['manifest_number'])) {
        error_log('Manifest number is missing.');
        return false;
    }

    // Check if the manifest number already exists
    $existing_manifest = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE manifest_number = %s",
        sanitize_text_field($data['manifest_number'])
    ));

    if ($existing_manifest) {
        // Optionally, update the existing row instead of inserting a new one
        // $wpdb->update(...) can be used here if you want to update fields.
        error_log('Manifest number already exists: ' . $data['manifest_number']);
        return true; // Or return false if you want to stop here.
    }

    // Insert data into the truck_manifest table
    $inserted = $wpdb->insert($table_name, array(
        'manifest_number' => sanitize_text_field($data['manifest_number']),
        // Add other necessary fields here
    ));

    // Check for successful insert
    if ($inserted === false) {
        error_log('Database insert failed: ' . $wpdb->last_error);
        return false;
    } else {
        error_log('Successfully inserted manifest number: ' . $data['manifest_number']);
        return true;
    }
}

    // Handle the file upload
    public function handle_file_upload() {
        try {
            // Assuming you have a separate file for handling the actual upload
            require_once plugin_dir_path(__FILE__) . 'upload-handler.php';

            
            // Process the file (your upload logic goes here)
            // You can move the file to a specific directory, parse it, or save data to the database

            // Return true if successful
            return true;
        } catch (Exception $e) {
            // Log the error and return false
            error_log($e->getMessage());
            return false;
        }
    }

    // Handle the manifest details submission 
    public function handle_manifest_details() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'manifest_details';

        // Insert the form data into the database
        $wpdb->insert($table_name, array(
            'manifest_number' => sanitize_text_field($_POST['manifest_number']),
            'loading_date'    => sanitize_text_field($_POST['loading_date']),
            'truck_number'    => sanitize_text_field($_POST['truck_number']),
            'driver'          => sanitize_text_field($_POST['driver']),
            'arrival_date'    => sanitize_text_field($_POST['arrival_date']),
            'manifest_revenue'    => sanitize_text_field($_POST['manifest_revenue']),
            'upload_date' => isset($_POST['upload_date']) ? sanitize_text_field($_POST['upload_date']) : current_time('mysql')
        ));
    }

    
// Save a new role
public function ajax_save_role() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'assignroles';

    $user_name = sanitize_text_field($_POST['user_name']);
    $contact_number = sanitize_text_field($_POST['contact_number']);
    $email = sanitize_email($_POST['email']);
    $role = sanitize_text_field($_POST['role']);

    $wpdb->insert($table_name, [
        'user_name' => $user_name,
        'contact_number' => $contact_number,
        'email' => $email,
        'role' => $role
    ]);

    wp_send_json_success();
}


// Load assignroles
public function ajax_load_roles() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'assignroles';

    // Log for debugging
    error_log('ajax_load_roles function triggered');

    // Fetch all roles
    $results = $wpdb->get_results("SELECT id, user_name, contact_number, email, role FROM $table_name", ARRAY_A);

    // Log the query results for debugging
    error_log('Query results: ' . print_r($results, true));

    if ($results) {
        wp_send_json_success($results);
    } else {
        error_log('No roles found in the database.');
        wp_send_json_error('No roles found.');
    }
}



// Delete role
public function ajax_delete_role() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'assignroles';

    $id = intval($_POST['id']);
    $wpdb->delete($table_name, ['id' => $id]);

    wp_send_json_success();
}

// Search for users by name
function search_assign_roles() {
    global $wpdb;

    // Get the search term from the AJAX request
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

    // Handle empty search term gracefully
    if (empty($search)) {
        // Log the handling of the empty search term
        error_log("Search term is empty. Returning an empty result set.");
        wp_send_json([
            'success' => true,
            'data' => [] // Return an empty array for no search term
        ]);
        return;
    }

    // Fetch matching users from the database
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, user_name AS text 
             FROM {$wpdb->prefix}assignroles 
             WHERE user_name LIKE %s",
            '%' . $wpdb->esc_like($search) . '%'
        ),
        ARRAY_A // Ensure results are returned as an array
    );

    // Log the fetched results for debugging
    error_log("Search term: " . $search);
    error_log("Search results: " . print_r($results, true));

    // Return the results or an empty array
    wp_send_json([
        'success' => true,
        'data' => !empty($results) ? $results : [] // Return an empty array if no matches are found
    ]);
}

// Update the role_id for a specific waybill
function update_role_for_waybill() {
    global $wpdb;

    $role_id = isset($_POST['role_id']) ? intval($_POST['role_id']) : 0;
    $waybill_id = isset($_POST['waybill_id']) ? intval($_POST['waybill_id']) : 0;

    if ($role_id > 0 && $waybill_id > 0) {
        $updated = $wpdb->update(
            "{$wpdb->prefix}truck_manifest",
            ['role_id' => $role_id],
            ['id' => $waybill_id],
            ['%d'],
            ['%d']
        );

        if ($updated !== false) {
            wp_send_json_success('Role updated successfully.');
        } else {
            wp_send_json_error('Failed to update role.');
        }
    } else {
        wp_send_json_error('Invalid input.');
    }
}

function check_uncollected_status() {
    global $wpdb;
    $waybill_id = intval($_POST['waybill_id']);

    // Fetch relevant details
    $waybill = $wpdb->get_row($wpdb->prepare(
        "SELECT status, collect, prepaid, delivery_date FROM {$wpdb->prefix}waybill_table WHERE id = %d",
        $waybill_id
    ));

    if ($waybill && $waybill->status === 'Delivered' && $waybill->prepaid == 0 && $waybill->collect > 0) {
        // Check if 24 hours have passed since delivery
        $delivery_time = strtotime($waybill->delivery_date);
        $current_time = time();
        $time_diff = $current_time - $delivery_time;

        if ($time_diff >= 24 * 60 * 60) {
            wp_send_json_success(['should_change_to_uncollected' => true]);
            exit;
        }
    }

    wp_send_json_success(['should_change_to_uncollected' => false]);
    exit;
}


function process_uncollected_truck_manifest() {
    global $wpdb;

    // Query waybills that need to change to 'Uncollected'
    // 1440 minutes = 24 hours
    $waybills_to_update = $wpdb->get_results(" 
        SELECT * FROM {$wpdb->prefix}truck_manifest
        WHERE status = 'Delivered'
        AND prepaid = 0
        AND collect > 0
        AND TIMESTAMPDIFF(MINUTE, delivery_date, NOW()) >= 1 
    ");

    // Update status to 'Uncollected' for each eligible waybill
    //wp_send_json_success(['Prefex' => $wpdb->prefix]);
    //wp_send_json_success(['SQL_SELECT' => $waybills_to_update]);

    foreach ($waybills_to_update as $waybill) {
        $wpdb->update(
            "{$wpdb->prefix}truck_manifest",
            ['status' => 'Uncollected'],
            ['id' => $waybill->id],
            ['%s'],
            ['%d']
        );
        wp_send_json_success(['should_change_to_uncollected_id' => $waybill->id]);
        wp_send_json_success(['should_change_to_uncollected' => true]);
    }
    wp_send_json_success(['process' => true]);
    exit;
}

function process_uncollected_status_update() {
    global $wpdb;

    // Query waybills that need to change to 'Uncollected'
    $waybills_to_update = $wpdb->get_results("
        SELECT id FROM {$wpdb->prefix}waybill_table
        WHERE status = 'Delivered'
        AND prepaid = 0
        AND collect > 0
        AND TIMESTAMPDIFF(HOUR, delivery_date, NOW()) >= 24
    ");

    // Update status to 'Uncollected' for each eligible waybill
    foreach ($waybills_to_update as $waybill) {
        $wpdb->update(
            "{$wpdb->prefix}waybill_table",
            ['status' => 'Uncollected'],
            ['id' => $waybill->id],
            ['%s'],
            ['%d']
        );
    }
}

// AJAX handler for fetching waybill details
public function get_waybill_details() {
    global $wpdb;
    $manifests_table = $wpdb->prefix . 'truck_manifest';
    $assignroles_table = $wpdb->prefix . 'assignroles';

    $waybill_id = isset($_POST['waybill_id']) ? intval($_POST['waybill_id']) : 0; // Ensure waybill_id is properly checked

    if ($waybill_id > 0) {
        // Fetch waybill details from the database
        $waybill_details = $wpdb->get_row($wpdb->prepare(
            "SELECT tm.*, ar.user_name AS assigned_user 
             FROM $manifests_table tm
             LEFT JOIN $assignroles_table ar ON tm.role_id = ar.id
             WHERE tm.id = %d", 
            $waybill_id
        ), ARRAY_A);



        if ($waybill_details) {
        // Fetch all user names and roles from assignroles table
        $user_roles = $wpdb->get_results("SELECT user_name, role FROM $assignroles_table", ARRAY_A);



            // JSON response with data needed for JavaScript processing
            $response = array(
                // Safely handle array keys using isset()
                'collect' => isset($waybill_details['collect']) ? $waybill_details['collect'] : null,
                'prepaid' => isset($waybill_details['prepaid']) ? $waybill_details['prepaid'] : null,
                'status' => isset($waybill_details['status']) ? $waybill_details['status'] : null,
                'delivery_date' => isset($waybill_details['delivery_date']) ? $waybill_details['delivery_date'] : null,
                'collected_date' => !empty($waybill_details['collected_date']) ? $waybill_details['collected_date'] : '', // Ensure it's handled properly
                // 'arrival_date' => isset($waybill_details['arrival_date']) ? $waybill_details['arrival_date'] : null,
                // 'total_amount' => isset($waybill_details['collect']) ? $waybill_details['collect'] : null,
                // 'discount' => isset($waybill_details['discount']) ? $waybill_details['discount'] : null,
                'discount_rate' => isset($waybill_details['discount_rate']) ? $waybill_details['discount_rate'] : '',
                // 'net_amount' => isset($waybill_details['net_amount']) ? $waybill_details['net_amount'] : null,
                // 'remarks' => !empty($waybill_details['remarks']) ? $waybill_details['remarks'] : 'Click to add Remarks',
                'role_id' => isset($waybill_details['role_id']) ? $waybill_details['role_id'] : 0,
                'user_name' => isset($waybill_details['user_name']) ? $waybill_details['user_name'] : 'Unassigned',
                // 'role_contact' => isset($waybill_details['role_contact']) ? $waybill_details['role_contact'] : null,
                // 'role_email' => isset($waybill_details['role_email']) ? $waybill_details['role_email'] : null,
                 'role' => isset($waybill_details['role']) ? $waybill_details['role'] : null
            );

            // Log the collected_date for debugging
            error_log("Collected Date: " . print_r($waybill_details['collected_date'], true));

            // Build HTML content for the popup display
            ob_start();

            echo '<h2>Waybill Details</h2>';
            echo '<p><strong>Waybill No:</strong> ' . esc_html(isset($waybill_details['waybill_no']) ? $waybill_details['waybill_no'] : '') . '</p>';
            echo '<p><strong>Consignee:</strong> ' . esc_html(isset($waybill_details['consignee']) ? $waybill_details['consignee'] : '') . '</p>';
            echo '<p><strong>Consignor:</strong> ' . esc_html(isset($waybill_details['consignor']) ? $waybill_details['consignor'] : '') . '</p>';
            echo '<p><strong>Descriptions:</strong> ' . esc_html(isset($waybill_details['descriptions']) ? $waybill_details['descriptions'] : '') . '</p>';

            // Wrap collect and prepaid in span elements with unique IDs
            echo '<p><strong>Collect:</strong> <span id="collect">' . esc_html(isset($waybill_details['collect']) ? $waybill_details['collect'] : '') . '</span></p>';
            echo '<p><strong>Prepaid:</strong> <span id="prepaid">' . esc_html(isset($waybill_details['prepaid']) ? $waybill_details['prepaid'] : '') . '</span></p>';

            // Show the status and delivery date Status with a unique ID
            echo '<p><strong>Status:</strong> <span id="status">' . esc_html(isset($waybill_details['status']) ? $waybill_details['status'] : '') . '</span>';

            if ($waybill_details['status'] === 'Delivered' && !empty($waybill_details['delivery_date'])) {
                // Format the delivery date to show both date and time
                $formatted_date = date('Y-m-d h:i:s A', strtotime($waybill_details['delivery_date']));
                echo ' - ' . esc_html($formatted_date);
            }
            
            // Display collected date if available and if the status is 'Collected'
            if ($waybill_details['status'] === 'Collected' && !empty($waybill_details['collected_date'])) {
                // Format collected date
                $collected_date = date('Y-m-d h:i:s A', strtotime($waybill_details['collected_date']));
                echo ' - Collected on: ' . esc_html($collected_date);
            }
            
            echo '</p>';

            // To check the Delivered and Collected Date
            echo '<p><strong>Delivered Date:</strong> <input type="datetime-local" id="ddate" value="' . esc_html(isset($waybill_details['delivery_date']) ? $waybill_details['delivery_date'] : '') . '"></p>';
            echo '<p><strong>Collected Date:</strong> <input type="datetime-local" id="cdate" value="' . esc_html(isset($waybill_details['collected_date']) ? $waybill_details['collected_date'] : '') . '"></p>';
            echo '<p><strong>Arrival Date:</strong> ' . esc_html(isset($waybill_details['arrival_date']) ? $waybill_details['arrival_date'] : '') . '</p>';

            // Add the hidden input for waybill ID
            echo '<input type="hidden" id="waybill-id" value="' . esc_attr($waybill_id) . '">';

            //To show the assign Person
           // echo '<h3>Assign:</h3>';
            
            // Populate the dropdown in the popup
            echo '<p><strong>Assign:</strong><select id="assign-select" name="assign" style="width: 50%;"></p>';
            echo '<option value="">-- Select User --</option>';
            foreach ($user_roles as $user) {
                $selected = $user['user_name'] === $waybill_details['assigned_user'] ? 'selected' : '';
                //$selected = $user_roles['role'] === $waybill_details['assigned_user'] ? 'selected' : '';
                echo '<option value="' . esc_attr($user['user_name'] . '|' . $user_roles['role']) . '" ' . $selected . '>';
                echo esc_html($user['user_name'] . ' (' . $user['role'] . ')');
                echo '</option>';
                
            }
            echo '</select>';

            // New fields
            echo '<h3>Ready for Collection?</h3>';
            echo '<p><strong>Total Amount:</strong> <span id="total-amount">' . esc_html(isset($waybill_details['collect']) ? $waybill_details['collect'] : '') . '</span></p>';
            echo '<p><strong>Discount:</strong> <span id="discount" class="editable" contenteditable="false">' . esc_html(isset($waybill_details['discount']) ? $waybill_details['discount'] : '') . '</span></p>';
            
            //echo '<p><strong>Discount Rate %:</strong> <span id="discount-rate">' . number_format($discount_rate, 2) . '%</span></p>';
            echo '<p><strong>Discount Rate %:</strong> <span id="discount-rate">' . esc_html(isset($waybill_details['discount_rate']) ? $waybill_details['discount_rate'] : '') . '</span></p>';

            echo '<p><strong>Net Amount:</strong> <span id="net-amount">' . esc_html(isset($waybill_details['net_amount']) ? $waybill_details['net_amount'] : '') . '</span></p>';

            // Remarks
            $remarks = !empty($waybill_details['remarks']) ? esc_html($waybill_details['remarks']) : 'Click to add Remarks';
            echo '<p><strong>Remarks:</strong> <span id="remarks" class="editable" contenteditable="true">' . $remarks . '</span></p>';

            $popup_html = ob_get_clean();

            // Include both HTML and data in the JSON response
            wp_send_json_success(array(
                'html' => $popup_html,
                'data' => $response
            ));
        } else {
            wp_send_json_error("No details found for this waybill.");
        }
    } else {
        wp_send_json_error("Invalid waybill ID.");
    }

    wp_die(); // Terminate the AJAX request
}



public function ajax_search_waybills() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized access.']);
        return;
    }

    $search_query = isset($_POST['search_query']) ? sanitize_text_field($_POST['search_query']) : '';
    if (empty($search_query)) {
        wp_send_json_error(['message' => 'Search query is empty.']);
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'truck_manifest'; // Replace with your actual table name

    $sql = $wpdb->prepare(
        "SELECT * FROM $table_name WHERE 
        LOWER(waybill_no) LIKE %s OR 
        LOWER(consignee) LIKE %s OR 
        LOWER(consignor) LIKE %s OR 
        LOWER(descriptions) LIKE %s",
        "%{$search_query}%", "%{$search_query}%", "%{$search_query}%", "%{$search_query}%"
    );

    $results = $wpdb->get_results($sql, ARRAY_A);

    // Debugging logs
    error_log("Search Query: {$search_query}");
    error_log("SQL Query: $sql");
    error_log("Results: " . print_r($results, true));

    if (!empty($results)) {
        wp_send_json_success(['results' => $results]);
    } else {
        wp_send_json_error(['message' => 'No matching records found.']);
    }
}

public function ajax_get_default_rows() {
    // Verify user permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized access.']);
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'truck_manifest'; // Replace with your table name

    // Fetch the first 10 rows as default
    $results = $wpdb->get_results("SELECT * FROM $table_name LIMIT 10", ARRAY_A);

    if (!empty($results)) {
        wp_send_json_success(['results' => $results]);
    } else {
        wp_send_json_error(['message' => 'No default rows found.']);
    }
}

public function ajax_reset_waybill_status() {
    // Verify user permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized access.']);
        return;
    }

    global $wpdb;
    $waybill_id = intval($_POST['waybill_id']);
    $table_name = $wpdb->prefix . 'truck_manifest';

    if ($waybill_id <= 0) {
        wp_send_json_error(['message' => 'Invalid waybill ID.']);
        return;
    }

    // Manually construct the query to reset fields to NULL
    $query = $wpdb->prepare(
        "UPDATE {$table_name} 
         SET status = %s, 
             delivery_date = NULL, 
             collected_date = NULL 
         WHERE id = %d",
        'Undelivered',
        $waybill_id
    );

    // Execute the query
    $result = $wpdb->query($query);

    if ($result !== false) {
        error_log("Waybill {$waybill_id} reset successfully."); // Debugging
        wp_send_json_success(['message' => 'Waybill reset successfully.']);
    } else {
        error_log("Failed to reset waybill ID {$waybill_id}: " . $wpdb->last_error);
        wp_send_json_error(['message' => 'Failed to reset waybill.']);
    }
}



function handle_update_collected_date_popup_only() {
    // Verify required parameters
    if (!isset($_POST['waybill_id']) || !isset($_POST['collected_date'])) {
        wp_send_json_error(['message' => 'Missing required parameters.']);
        wp_die();
    }

    $waybill_id = sanitize_text_field($_POST['waybill_id']);
    $collected_date = sanitize_text_field($_POST['collected_date']);

    // Log received data for debugging
    error_log('Received AJAX request: ' . print_r($_POST, true));

    // Perform database operation
    global $wpdb;
    $table_name = $wpdb->prefix . 'truck_manifest'; // Replace with your actual table name
    $result = $wpdb->update(
        $table_name, // Table name
        ['collected_date' => $collected_date], // Data to update
        ['id' => $waybill_id], // WHERE condition
        ['%s'], // Data format for the column
        ['%d']  // Data format for the WHERE condition
    );

    if ($result === false) {
        error_log('Database update failed: ' . $wpdb->last_error);
        wp_send_json_error(['message' => 'Database update failed.']);
    } else {
        wp_send_json_success(['message' => 'Collected Date updated successfully.']);
    }

    wp_die();
}



// AJAX handler for updating waybill status
public function update_waybill_status() {
    global $wpdb;
    $table = $wpdb->prefix . 'truck_manifest'; // wp_truck_manifest table

    $waybill_id = isset($_POST['waybill_id']) ? intval($_POST['waybill_id']) : 0;
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
    $delivery_date = isset($_POST['delivery_date']) ? sanitize_text_field($_POST['delivery_date']) : null;
    $collected_date = isset($_POST['collected_date']) ? sanitize_text_field($_POST['collected_date']) : null;

    // Set PHP timezone explicitly to Asia/Manila
    date_default_timezone_set('Asia/Manila');

    if ($waybill_id > 0 && !empty($status)) {
        // Prepare the data for update
        $data = ['status' => $status];

        if ($status === 'Delivered') {
            $data['delivery_date'] = !empty($delivery_date) 
                ? date('Y-m-d h:i:s A', strtotime($delivery_date)) 
                : date('Y-m-d h:i:s A'); // Use current time if not provided
        }

        if ($status === 'Collected') {
            if (!empty($delivery_date)) {
                $data['delivery_date'] = date('Y-m-d h:i:s A', strtotime($delivery_date));
            }
            $data['collected_date'] = !empty($collected_date) 
                ? date('Y-m-d h:i:s A', strtotime($collected_date)) 
                : date('Y-m-d h:i:s A'); // Use current time if not provided
        }

        // Log the data for debugging
        error_log('Updating Waybill ID: ' . $waybill_id);
        error_log('Data to update: ' . print_r($data, true));

        // Update the table
        $updated = $wpdb->update(
            $table,
            $data,
            ['id' => $waybill_id],
            ['%s', '%s', '%s'], // Format for status, delivery_date, and collected_date
            ['%d']              // Format for ID
        );

        if ($updated !== false) {
            wp_send_json_success(['message' => 'Status updated successfully.']);
        } else {
            wp_send_json_error(['message' => 'Failed to update status.']);
        }
    } else {
        wp_send_json_error(['message' => 'Invalid data provided.']);
    }

    wp_die();
}


function update_waybill_details() {
    global $wpdb;
    $manifests_table = $wpdb->prefix . 'truck_manifest';

    $waybill_id = isset($_POST['waybill_id']) ? intval($_POST['waybill_id']) : 0;
    $delivery_date = isset($_POST['delivery_date']) ? sanitize_text_field($_POST['delivery_date']) : null;
    $collected_date = isset($_POST['collected_date']) ? sanitize_text_field($_POST['collected_date']) : null;
    $discount = isset($_POST['discount']) ? floatval($_POST['discount']) : 0;
    $discount_rate = isset($_POST['discount_rate']) ? floatval($_POST['discount_rate']) : 0;
    $net_amount = isset($_POST['net_amount']) ? floatval($_POST['net_amount']) : 0;
    $remarks = isset($_POST['remarks']) ? sanitize_text_field($_POST['remarks']) : '';

    // Set the time zone to Asia/Manila
    date_default_timezone_set('Asia/Manila');

    // Conditionally format the dates
    $formatted_delivery_date = !empty($delivery_date) ? date('Y-m-d h:i:s A', strtotime($delivery_date)) : null;
    $formatted_collected_date = !empty($collected_date) ? date('Y-m-d h:i:s A', strtotime($collected_date)) : null;

    // Prepare the data for update
    $data = array(
        'delivery_date' => $formatted_delivery_date,
        'collected_date' => $formatted_collected_date,
        'discount' => $discount,
        'discount_rate' => $discount_rate,
        'net_amount' => $net_amount,
        'remarks' => $remarks
    );

    // Remove null values to avoid overwriting existing data with NULL
    $data = array_filter($data, function($value) {
        return $value !== null;
    });

    // Specify the format for each value being updated
    $data_format = array(
        'delivery_date' => '%s',
        'collected_date' => '%s',
        'discount' => '%f',
        'discount_rate' => '%f',
        'net_amount' => '%f',
        'remarks' => '%s'
    );

    // Match the format with the updated data
    $filtered_format = array_values(array_intersect_key($data_format, $data));

    // Update the waybill record in the database
    $result = $wpdb->update(
        $manifests_table,
        $data,
        array('id' => $waybill_id),
        $filtered_format,
        array('%d')
    );

    // Handle the result of the update query
    if ($result !== false) {
        wp_send_json_success(array('message' => 'Waybill details updated successfully.'));
    } else {
        wp_send_json_error(array('message' => 'Failed to update waybill details.'));
    }
}



function handle_update_status() {
    global $wpdb;
    $manifests_table = $wpdb->prefix . 'truck_manifest';

    $waybill_id = intval($_POST['waybill_id']);
    $status = sanitize_text_field($_POST['status']);
    $current_date_time = ($status === 'Delivered') ? current_time('mysql') : null;

    // Update status and delivery date
    $wpdb->update(
        $manifests_table,
        array(
            'status' => $status,
            'delivery_date' => $current_date_time, // Only update if Delivered
        ),
        array('id' => $waybill_id),
        array('%s', $current_date_time ? '%s' : 'NULL'), // If null, it will not update
        array('%d')
    );

    wp_send_json_success();
    wp_die();
}

public function update_status() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'truck_manifest';

    $waybill_id = isset($_POST['waybill_id']) ? intval($_POST['waybill_id']) : 0;
    $new_status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

    // Log input data for debugging
    error_log("Updating status for ID: {$waybill_id} to {$new_status}");

    if ($waybill_id && in_array($new_status, ['Delivered', 'Undelivered'])) {
        $result = $wpdb->update(
            $table_name,
            ['status' => $new_status],
            ['id' => $waybill_id],
            ['%s'],
            ['%d']
        );

        if ($result !== false) {
            wp_send_json_success();
        } else {
            error_log("Status update failed: " . $wpdb->last_error);
            wp_send_json_error(['message' => 'Failed to update status']);
        }
    } else {
        wp_send_json_error(['message' => 'Invalid input data']);
    }
    wp_die();
}


function handle_live_search() {
    global $wpdb;
    $manifests_table = $wpdb->prefix . 'truck_manifest';
    $details_table = $wpdb->prefix . 'manifest_details';

    // Get search term and status filter
    $search_term = isset($_GET['search_term']) ? sanitize_text_field($_GET['search_term']) : '';
    $status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';

    // SQL query to find relevant records based on the search term
    $sql = "
        SELECT tm.*, tm.arrival_date, tm.delivery_date  -- Adding delivery_date here
        FROM $manifests_table tm
        LEFT JOIN $details_table md ON tm.waybill_no = md.manifest_number
        WHERE (tm.waybill_no LIKE %s OR
               tm.consignee LIKE %s OR
               tm.consignor LIKE %s OR
               tm.descriptions LIKE %s OR
               tm.delivery_date LIKE %s)";  // Adding delivery_date to the searchable columns

    // Add status filter if provided
    if ($status_filter) {
        $sql .= " AND tm.status = %s";
        $query = $wpdb->prepare($sql,
            '%' . $wpdb->esc_like($search_term) . '%',
            '%' . $wpdb->esc_like($search_term) . '%',
            '%' . $wpdb->esc_like($search_term) . '%',
            '%' . $wpdb->esc_like($search_term) . '%',
            '%' . $wpdb->esc_like($search_term) . '%',
            $status_filter
        );
    } else {
        $query = $wpdb->prepare($sql,
            '%' . $wpdb->esc_like($search_term) . '%',
            '%' . $wpdb->esc_like($search_term) . '%',
            '%' . $wpdb->esc_like($search_term) . '%',
            '%' . $wpdb->esc_like($search_term) . '%',
            '%' . $wpdb->esc_like($search_term) . '%'
        );
    }

    // Execute the query
    $results = $wpdb->get_results($query, ARRAY_A);

    // Calculate aging for the response and determine visibility of the 'Collected' button
    foreach ($results as &$row) {
        // Status button with color based on status
        $status_color = ($row['status'] === 'Delivered') ? '#2E8B57' : '#FA8072';
        $row['status_html'] = '<span class="status-button" style="background-color: ' . esc_attr($status_color) . ';">' . esc_html($row['status']) . '</span>';

        // Aging calculation
        if ($row['status'] === 'Undelivered' && !empty($row['arrival_date'])) {
            $arrival_date = DateTime::createFromFormat('Y-m-d', $row['arrival_date']);
            if ($arrival_date !== false) {
                $current_date = new DateTime();
                $interval = $current_date->diff($arrival_date);
                $row['aging'] = $interval->days . ' days';
            } else {
                $row['aging'] = '<em>Invalid date</em>';
            }
        } elseif ($row['status'] === 'Delivered') {
            $row['aging'] = 'For Collection';
        } else {
            $row['aging'] = '-';
        }

        // 'Collected' button visibility check based on the status and prepaid/collect conditions
        $row['show_collected_button'] = false;
        if ($row['status'] === 'Delivered' || 
            ($row['status'] === 'Undelivered' && $row['prepaid'] > 0 && $row['collect'] == 0)) {
            $row['show_collected_button'] = true;
        }

        // Convert date formats for consistent frontend display if necessary
        $row['arrival_date'] = $row['arrival_date'] ? date('Y-m-d', strtotime($row['arrival_date'])) : null;
        $row['delivery_date'] = $row['delivery_date'] ? date('Y-m-d', strtotime($row['delivery_date'])) : null;
    }

    // Send response back to AJAX
    echo json_encode($results);
    wp_die();
}



// AJAX Callback Function with Pagination
public function filter_manifest_by_date_range() {
    global $wpdb;

    $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
    $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
    $page = isset($_GET['page']) ? absint($_GET['page']) : 1;
    $per_page = 5;
    $offset = ($page - 1) * $per_page;

    if (!$date_from || !$date_to) {
        wp_send_json_error('Invalid date range');
        wp_die();
    }

    // Define the table name
    $details_table = $wpdb->prefix . 'manifest_details';

    // Count total records for pagination
    $total_records = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$details_table} 
             WHERE (upload_date BETWEEN %s AND %s) 
             OR (arrival_date BETWEEN %s AND %s)",
            $date_from, $date_to, $date_from, $date_to
        )
    );
    $total_pages = ceil($total_records / $per_page);

    // Log total records and pages for debugging
    error_log("Total Records: $total_records, Total Pages: $total_pages");

    // Query to retrieve records within the specified date range
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT manifest_number, loading_date, truck_number, driver, arrival_date, manifest_revenue, upload_date 
             FROM {$details_table} 
             WHERE (upload_date BETWEEN %s AND %s) 
             OR (arrival_date BETWEEN %s AND %s)
             LIMIT %d OFFSET %d",
            $date_from, $date_to, $date_from, $date_to, $per_page, $offset
        ),
        ARRAY_A
    );

    if ($wpdb->last_error) {
        error_log("Database error: " . $wpdb->last_error);
    }

    // Log the actual SQL query and results
    error_log("SQL Query: " . $wpdb->last_query);
    error_log("Results: " . print_r($results, true));

    wp_send_json_success([
        'results' => $results,
        'current_page' => $page,
        'total_pages' => $total_pages
    ]);
    wp_die();
}



    // View Manifests Page with output area for Manifest Details and Search
    public function view_manifests() {
      global $wpdb;
      $details_table = $wpdb->prefix . 'manifest_details';
      $manifests_table = $wpdb->prefix . 'truck_manifest';
            
      // Pagination variables
      $current_page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
      $per_page = 10;
      $offset = ($current_page - 1) * $per_page;
                
      // Filter by status if specified
      $selected_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
      $status_query = $selected_status ? $wpdb->prepare("AND t.status = %s", $selected_status) : '';

      $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
      $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

     // Modify the query to include the upload_date range
     $date_query = '';
     if ($date_from && $date_to) {
         $date_query = $wpdb->prepare("AND d.upload_date BETWEEN %s AND %s", $date_from, $date_to);
     }

     // Correct SQL to join truck_manifest with manifest_details and ensure delivery_date is retrieved from the correct table
     $manifests = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT t.*, d.arrival_date, d.upload_date, t.delivery_date
            FROM {$manifests_table} AS t
             LEFT JOIN {$details_table} AS d ON t.manifest_number = d.manifest_number
             WHERE 1=1 $status_query $date_query
            ORDER BY t.id DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
         ),
         ARRAY_A
         );

        $total_manifests = $wpdb->get_var(
            "SELECT COUNT(*) FROM $manifests_table AS t
             JOIN $details_table AS d ON t.manifest_number = d.manifest_number
             WHERE 1=1 $status_query"
        );

        // Get all waybills with status 'Delivered'
        $delivered_waybills = $wpdb->get_results(
            "SELECT t.*, d.arrival_date FROM $manifests_table AS t
            JOIN $details_table AS d ON t.manifest_number = d.manifest_number
            WHERE t.status = 'Delivered'
            ORDER BY t.id DESC", ARRAY_A
        );
    
        // Get selected manifest number if provided
        $selected_manifest_number = !empty($_GET['manifest_number']) ? sanitize_text_field($_GET['manifest_number']) : null;
    
        // Fetch all manifest numbers for the list
        $all_manifest_numbers = $wpdb->get_results(
            "SELECT manifest_number, loading_date, truck_number, driver, arrival_date, manifest_revenue, upload_date 
            FROM $details_table 
            ORDER BY id DESC",
            ARRAY_A
        );
        
            // Check if a specific manifest number is selected
            if ($selected_manifest_number) {
            // Fetch manifest details for the selected manifest number
            $selected_manifest_details = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $details_table WHERE manifest_number = %s", 
                $selected_manifest_number
            ), ARRAY_A);
    
            // Fetch only manifests associated with the selected manifest number
            $manifests = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $manifests_table WHERE manifest_number = %s ORDER BY id DESC LIMIT %d OFFSET %d",
                $selected_manifest_number,
                $per_page,
                $offset
            ), ARRAY_A);
    
            // Calculate total pages for filtered results
            $total_manifests = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $manifests_table WHERE manifest_number = %s", 
                $selected_manifest_number
                ));
            } else {
                // No specific manifest selected, so fetch all manifest records
                $manifests = $wpdb->get_results("SELECT * FROM $manifests_table ORDER BY id DESC LIMIT $per_page OFFSET $offset", ARRAY_A);
                
                // Calculate total pages for all results
                $total_manifests = $wpdb->get_var("SELECT COUNT(*) FROM $manifests_table");
            }
    
            $total_pages = ceil($total_manifests / $per_page); 
    
            ?>
            <div class="wrap">
                <h1>Waybills Manifests</h1>
                <div class="manifest-container">
                <!-- Display Manifest Details if a specific manifest is selected -->
                <div class="manifest-details-output">
                    <h2>Manifest Details</h2>
                    <?php if ($selected_manifest_number && $selected_manifest_details): ?>
                        <p><strong>Manifest Number:</strong> <?php echo esc_html($selected_manifest_details['manifest_number']); ?></p>
                        <p><strong>Loading Date:</strong> <?php echo esc_html($selected_manifest_details['loading_date']); ?></p>
                        <p><strong>Plate Number:</strong> <?php echo esc_html($selected_manifest_details['truck_number']); ?></p>
                        <p><strong>Driver:</strong> <?php echo esc_html($selected_manifest_details['driver']); ?></p>
                        <p><strong>Arrival Date:</strong> <?php echo esc_html($selected_manifest_details['arrival_date']); ?></p>
                        <p><strong>Revenue:</strong> <?php echo '₱' . number_format($selected_manifest_details['manifest_revenue'], 2); ?></p>
                    <?php else: ?>
                        <p>Select a manifest number to view details or view all manifests below.</p>
                    <?php endif; ?>
                </div>

                <!-- Table for List of Manifest Numbers with Searchable Date Range -->
                <div class="manifest-number-table">
                    <h2>Manifest Numbers Search 'Range Date'</h2>

                    <!-- Date Range Search Inputs -->
                    <div class="date-filter">
                        <label for="date-from">From:</label>
                        <input type="date" id="date-from">
                        <label for="date-to">To:</label>
                        <input type="date" id="date-to">
                        <button id="search-by-date" type="button">Search</button>
                    </div>

                    <!-- Manifest Table -->
                    <table class="widefat striped" id="manifest-table">
                        <thead>
                            <tr>
                                <th>Manifest Number</th>
                                <th>Loading Date</th>
                                <th>Plate Number</th>
                                <th>Driver</th>
                                <th>Arrival Date</th>
                                <th>Revenue</th>
                                <th>Date Stamp</th>
                            </tr>
                        </thead>
                        <tbody id="manifest-table-body">
                            <?php foreach ($all_manifest_numbers as $manifest): ?>
                                <tr>
                                    <td>
                                        <a href="?page=view-manifests&manifest_number=<?php echo esc_attr($manifest['manifest_number']); ?>">
                                            <?php echo esc_html($manifest['manifest_number']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo !empty($manifest['loading_date']) ? esc_html($manifest['loading_date']) : '-'; ?></td>
                                    <td><?php echo !empty($manifest['truck_number']) ? esc_html($manifest['truck_number']) : '-'; ?></td>
                                    <td><?php echo !empty($manifest['driver']) ? esc_html($manifest['driver']) : '-'; ?></td>
                                    <td><?php echo !empty($manifest['arrival_date']) ? esc_html($manifest['arrival_date']) : '-'; ?></td>
                                    <td><?php echo !empty($manifest['manifest_revenue']) ? '₱' . number_format($manifest['manifest_revenue'], 2) : '-'; ?></td>
                                    <td><?php echo !empty($manifest['upload_date']) ? esc_html($manifest['upload_date']) : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination Controls -->
                    <div class="pagination" id="pagination-controls"></div>
                </div>
            </div>
            <script>
            jQuery(document).ready(function($) {
                function loadPage(page = 1) {
                    var dateFrom = $('#date-from').val();
                    var dateTo = $('#date-to').val();

                    if (!dateFrom || !dateTo) {
                        alert("Please select both dates.");
                        return;
                    }

                    $.ajax({
                        url: '<?php echo admin_url("admin-ajax.php"); ?>',
                        type: 'GET',
                        data: {
                            action: 'filter_manifest_by_date_range',
                            date_from: dateFrom,
                            date_to: dateTo,
                            page: page
                        },
                        success: function(response) {
                            console.log("Full response:", response);  // Log entire response for debugging

                            if (response.success && response.data && response.data.results && response.data.results.length > 0) {
                                var tableBody = $('#manifest-table-body');
                                tableBody.empty();

                                // Populate table rows
                                $.each(response.data.results, function(index, manifest) {
                                    var formattedRevenue = new Intl.NumberFormat('en-US', {
                                        style: 'currency',
                                        currency: 'PHP'
                                    }).format(manifest.manifest_revenue).replace('PHP', '₱');

                                    var newRow = '<tr>' +
                                        '<td><a href="?page=view-manifests&manifest_number=' + manifest.manifest_number + '">' + manifest.manifest_number + '</a></td>' +
                                        '<td>' + (manifest.loading_date || '-') + '</td>' +
                                        '<td>' + (manifest.truck_number || '-') + '</td>' +
                                        '<td>' + (manifest.driver || '-') + '</td>' +
                                        '<td>' + (manifest.arrival_date || '-') + '</td>' +
                                        '<td>' + (formattedRevenue || '-') + '</td>' +
                                        '<td>' + (manifest.upload_date || '-') + '</td>' +
                                        '</tr>';
                                    tableBody.append(newRow);
                                });

                                // Pagination setup
                                var pagination = $('#pagination-controls');
                                pagination.empty();
                                for (let i = 1; i <= response.data.total_pages; i++) {
                                    var pageLink = $('<a>', {
                                        text: i,
                                        href: '#',
                                        class: i === response.data.current_page ? 'current' : '',
                                        click: function(e) {
                                            e.preventDefault();
                                            loadPage(i); // Load selected page
                                        }
                                    });
                                    pagination.append(pageLink);
                                }
                            } else {
                                console.warn("No data found in response.data.results");
                                $('#manifest-table-body').html('<tr><td colspan="7">No manifests found for the selected date range.</td></tr>');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("Error filtering by date range:", error);
                            alert("An error occurred while fetching data. Please try again.");
                        }
                    });
                }

                // Trigger live search when date inputs change
                $('#date-from, #date-to').on('change', function() {
                    loadPage(1);  // Reset to page 1 with each new date range selection
                });

                // Format manifest revenue with ₱ symbol and commas on input
                document.addEventListener('DOMContentLoaded', function() {
    const manifestRevenue = document.getElementById('manifest_revenue');
    if (manifestRevenue) {
        manifestRevenue.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[₱,]/g, ''); // Remove ₱ and commas
            if (!isNaN(value) && value !== "") {
                value = parseFloat(value).toLocaleString('en-US', { style: 'currency', currency: 'PHP' }).replace('PHP', '₱');
                e.target.value = value;
            }
        });
    }
});

            });
            </script>

            <!-- Display Manifest Table -->
            <table class="widefat striped" id="manifests-table">
                <div class="manifest-container"> 
                    <!-- Display Manifest Filter -->
                    <div class="manifest-Filter">
                        <h2>Manifest Filter</h2>
                            <div class="status-filter">
                                <label for="status">Filter by Status:</label>
                                <select id="status-filter" name="status">
                                    <option value="">All</option>
                                    <option value="Delivered" <?php selected($selected_status, 'Delivered'); ?>>Delivered</option>
                                    <option value="Undelivered" <?php selected($selected_status, 'Undelivered'); ?>>Undelivered</option>
                                    <option value="Collected" <?php selected($selected_status, 'Collected'); ?>>Collected</option>
                                    <option value="Uncollected" <?php selected($selected_status, 'Uncollected'); ?>>Uncollected</option>
                                </select>
                            </div>

                            <!-- Live Search Input -->
                            <div class="manifest-search">
                            <div>
                                <label for="live-search">Search any field:</label>
                                <input type="text" id="live-search" placeholder="Type to search..." autocomplete="off">
                            </div>
                    </div>
                </div>

                <thead>
                    <tr>
                        <th>Waybill No</th>
                        <th>Consignee</th>
                        <th>Consignor</th>
                        <th>Descriptions</th>
                        <th>Collect</th>
                        <th>Prepaid</th>
                        <th>Status</th> <!-- Add this line -->
                        <th>Aging</th>
                        <th>Status Date-Stamp</th> <!-- New column -->
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($manifests)): ?>
                        <?php foreach ($manifests as $row): ?>
                            <tr class="waybill-row" 
                                data-id="<?php echo esc_attr($row['id']); ?>" 
                                data-status="<?php echo esc_attr($row['status']); ?>" 
                                data-arrival-date="<?php echo esc_attr($row['arrival_date']); ?>" 
                                data-delivery-date="<?php echo esc_attr($row['delivery_date']); ?>"> <!-- Include delivery date as a data attribute -->
                                
                                <td><?php echo esc_html($row['waybill_no']); ?></td>
                                <td><?php echo esc_html($row['consignee']); ?></td>
                                <td><?php echo esc_html($row['consignor']); ?></td>
                                <td><?php echo esc_html($row['descriptions']); ?></td>
                                <td><?php echo esc_html($row['collect'])? '₱' . number_format($row['collect'], 2) : '-'; ?></td>
                                <td><?php echo esc_html($row['prepaid'])? '₱' . number_format($row['prepaid'], 2) : '-'; ?></td>

                                <td class="status-cell">
                                    <?php
                                        // Set the background color based on status
                                        $status_color = '#FA8072'; // Default color for 'Undelivered'
                                        if ($row['status'] === 'Delivered') {
                                            $status_color = '#2E8B57';
                                        } elseif ($row['status'] === 'Collected') {
                                            $status_color = '#669999';
                                        }  elseif ($row['status'] === 'Uncollected') {
                                            $status_color = '#e82f1a';
                                        }
                                    ?>
                                    <span id="status-<?php echo isset($row['waybill_id']) ? esc_attr($row['waybill_id']) : 'unknown'; ?>" class="status-button" style="background-color: <?php echo esc_attr($status_color); ?>;">
                                        <?php echo esc_html($row['status']); ?>
                                    </span>
                                </td>


                                <!-- Aging Calculation Logic -->
                                <td class="aging-cell">
                                <?php
                                $current_date = new DateTime();
                                $aging_text = "-";

                                if ($row['status'] === 'Delivered') {
                                    $aging_text = "For Collection";
                                } elseif ($row['status'] === 'Collected') {
                                    $aging_text = "Complete";
                                } elseif ($row['status'] === 'Uncollected' && !empty($row['delivery_date'])) {
                                    $delivery_date = DateTime::createFromFormat('Y-m-d', $row['delivery_date']) ?: DateTime::createFromFormat('Y-m-d H:i:s', $row['delivery_date']);
                                    if ($delivery_date) {
                                        $interval = $current_date->diff($delivery_date);
                                        $aging_text = esc_html($interval->days . ' days');
                                    } else {
                                        $aging_text = "<em>Invalid date</em>";
                                    }
                                } elseif ($row['status'] === 'Undelivered' && !empty($row['arrival_date'])) {
                                    $arrival_date = DateTime::createFromFormat('Y-m-d', $row['arrival_date']) ?: DateTime::createFromFormat('Y-m-d H:i:s', $row['arrival_date']);
                                    if ($arrival_date) {
                                        $interval = $current_date->diff($arrival_date);
                                        $aging_text = esc_html($interval->days . ' days');
                                    } else {
                                        $aging_text = "<em>Invalid date</em>";
                                    }
                                }

                                echo $aging_text;
                                ?>
                            </td>



                            <!-- New Delivery Date Column -->
                            <td class="delivery-date-cell">
                                <?php
                                // Check if the status is Delivered or Collected and a corresponding date exists
                                if (($row['status'] === 'Delivered' && !empty($row['delivery_date'])) || ($row['status'] === 'Collected' && !empty($row['collected_date']))) {
                                    // Determine which date to display
                                    $date_to_display = ($row['status'] === 'Delivered') ? $row['delivery_date'] : $row['collected_date'];

                                    // Convert the date to include time if needed
                                    $formatted_date = DateTime::createFromFormat('Y-m-d H:i:s', $date_to_display);

                                    // If the date is valid, display it in a readable format
                                    if ($formatted_date) {
                                        echo esc_html($formatted_date->format('Y-m-d H:i:s')); // Display date and time
                                    } else {
                                        echo esc_html($date_to_display); // In case there's no time, just display the raw value
                                    }
                                } else {
                                    // Display a placeholder if no date is available
                                    echo '-';
                                }
                                ?>
                                        </td>

                                        </tr>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="12">No manifests found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                
                            <div class="record-count-pagination-container">
                                <div class="record-count">
                                    Total Records: <?php echo $total_manifests; ?>
                                  <!--  Current Page: <?php echo $current_page; ?> -->
                                </div>
                                <!-- Pagination Links -->
                                <div class="pagination">
                                    <?php if ($current_page > 1): ?>
                                        <a href="?page=view-manifests&paged=<?php echo $current_page - 1; if($selected_manifest_number) echo '&manifest_number=' . urlencode($selected_manifest_number); ?>">« Previous</a>
                                    <?php endif; ?>
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <a href="?page=view-manifests&paged=<?php echo $i; if($selected_manifest_number) echo '&manifest_number=' . urlencode($selected_manifest_number); ?>" <?php if ($i == $current_page) echo 'class="current"'; ?>><?php echo $i; ?></a>
                                    <?php endfor; ?>
                                    <?php if ($current_page < $total_pages): ?>
                                        <a href="?page=view-manifests&paged=<?php echo $current_page + 1; if($selected_manifest_number) echo '&manifest_number=' . urlencode($selected_manifest_number); ?>">Next »</a>
                                    <?php endif; ?>
                                </div>
                                <script>
                                </script>
                            </div>
                
                            <!-- Waybill Details Popup -->
                                <!-- Background Overlay -->
                                <div id="popup-overlay" style="display: none;"></div>
                                    <div id="waybill-details-popup" style="display: none;">
                                    <!-- <input type="hidden" id="waybill-id" value=""> -->
                                        <!-- AJAX content will be loaded here -->
                                        <!-- <button onclick="jQuery('#waybill-details-popup').hide();">Close</button> -->
                                    </div>
                            <!-- 'Collected' button -->
                            
                
        <?php
    }
    
}