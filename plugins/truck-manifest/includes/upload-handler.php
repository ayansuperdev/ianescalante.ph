<?php
use PhpOffice\PhpSpreadsheet\IOFactory;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function truck_manifest_extract_data($file_path) {
    // Ensure the autoload file is included correctly
    require_once(plugin_dir_path(__FILE__) . '../vendor/autoload.php');

    $spreadsheet = IOFactory::load($file_path);
    $sheet = $spreadsheet->getActiveSheet();
    $data = [];
    $manifest_number = null;
    $arrival_date = null;

    // Extract manifest number from cell A1
    $manifest_title = $sheet->getCell('A1')->getValue();
    if (strpos($manifest_title, 'Manifest Number:') !== false) {
        $manifest_number = trim(str_replace('Manifest Number:', '', $manifest_title));
    }

    // Extract arrival date from cell B5
    $arrival_date_raw = $sheet->getCell('B5')->getValue();
    if (!empty($arrival_date_raw)) {
        $arrival_date = (is_numeric($arrival_date_raw)) 
            ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($arrival_date_raw)->format('Y-m-d') 
            : date('Y-m-d', strtotime($arrival_date_raw));
    }

    // Iterate through each row in the spreadsheet starting from row 8
    foreach ($sheet->getRowIterator() as $row) {
        if ($row->getRowIndex() < 8) continue;

        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);

        $rowData = [];
        foreach ($cellIterator as $cell) {
            $rowData[] = $cell->getValue();
        }

        // Prepare the entry for insertion
        $entry = [
            'waybill_no' => !empty($rowData[0]) ? $rowData[0] : 'Unknown Waybill',
            'consignee' => !empty($rowData[1]) ? $rowData[1] : 'Unknown Consignee',
            'consignor' => !empty($rowData[2]) ? $rowData[2] : 'Unknown Consignor',
            'descriptions' => !empty($rowData[3]) ? $rowData[3] : 'Unknown Descriptions',
            'collect' => !empty($rowData[4]) ? $rowData[4] : '-',
            'prepaid' => !empty($rowData[5]) ? $rowData[5] : '-',
            'arrival_date' => $arrival_date // Include the extracted arrival_date for each entry
        ];

        // Add the manifest_number to the entry if it's set
        if ($manifest_number) {
            $entry['manifest_number'] = $manifest_number;
        }

        $data[] = $entry; // Add entry to data array
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'truck_manifest';

    foreach ($data as $entry) {
        $inserted = $wpdb->insert($table_name, $entry);

        if ($inserted === false) {
            error_log('Database insert error: ' . $wpdb->last_error);
            echo '<div class="notice notice-error is-dismissible"><p>Database insert failed: ' . $wpdb->last_error . '</p></div>';
        } else {
            echo '<div class="notice notice-success is-dismissible"><p>Row inserted successfully.</p></div>';
        }
    }
}


// Handling file upload
if (isset($_FILES['manifest_file'])) {
    $file = $_FILES['manifest_file'];
    $upload_dir = wp_upload_dir();
    $upload_path = $upload_dir['path'] . '/' . basename($file['name']);

    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        truck_manifest_extract_data($upload_path);
        echo '<div class="notice notice-success is-dismissible"><p>File uploaded and data extracted successfully.</p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>File upload failed.</p></div>';
    }
}
