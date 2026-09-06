jQuery(document).ready(function ($) {
    let table;

    // Initialize DataTable
    function initializeDataTable() {
        if ($.fn.DataTable.isDataTable('#prepaid-table')) {
            table.destroy(); // Destroy any existing instance before reinitializing
        }

        table = $('#prepaid-table').DataTable({
            order: [], // Preserve unsorted state initially
            columnDefs: [
                { orderable: false, targets: [0] }, // Disable sorting on the checkbox column
            ],
        });
    }

    // Filter button click handler
    $('#filter-reports').on('click', function () {
        const dateFrom = $('#date_from').val();
        const dateTo = $('#date_to').val();

        // Validate the date range
        if (!dateFrom || !dateTo) {
            alert('Please select both start and end dates.');
            return;
        }

        // Fetch prepaid reports via AJAX
        $.ajax({
            url: generatePrepaidParams.ajaxurl,
            type: 'POST',
            data: {
                action: 'fetch_prepaid_reports',
                date_from: dateFrom,
                date_to: dateTo,
            },
            success: function (response) {
                if (response.success) {
                    populateTable(response.data);
                } else {
                    alert(response.data.message || 'No prepaid reports found.');
                    $('#prepaid-table tbody').html('<tr><td colspan="8">No records found.</td></tr>');
                    initializeDataTable(); // Reinitialize DataTable after updating the table
                }
            },
            error: function () {
                alert('Error fetching prepaid reports.');
            },
        });
    });

    // Populate table with data
    function populateTable(data) {
        let html = '';
        data.forEach(row => {
            html += `<tr>
                
                <td>${row.collected_date || ''}</td>
                <td>${row.delivery_date || ''}</td>
                <td>${row.waybill_no || ''}</td>
                <td>${row.consignee || ''}</td>
                <td>${row.consignor || ''}</td>
                <td>${row.descriptions || ''}</td>
                <td>${parseFloat(row.prepaid || 0).toFixed(2)}</td>

                <td><input type="checkbox" class="row-select" data-id="${row.waybill_no || ''}"></td>
            </tr>`;
        });

        $('#prepaid-table tbody').html(html);
        initializeDataTable(); // Reinitialize DataTable with the updated content
    }

    // Select All checkbox functionality
    $('#prepaid-table').on('change', '#select-all', function () {
        const isChecked = $(this).is(':checked');
        $('#prepaid-table tbody .row-select:visible').prop('checked', isChecked); // Only visible rows
    });

    // Handle Generate Selected button click
    $('#generate-prepaid-btn').on('click', function () {
        const selectedRows = [];
        $('#prepaid-table tbody .row-select:checked').each(function () {
            selectedRows.push($(this).data('id'));
        });

        const dateFrom = $('#date_from').val() || 'N/A';
        const dateTo = $('#date_to').val() || 'N/A';

        if (selectedRows.length === 0) {
            alert('Please select at least one row.');
            return;
        }

        // Send selected rows to the server via AJAX
        $.ajax({
            url: generatePrepaidParams.ajaxurl,
            type: 'POST',
            data: {
                action: 'generate_prepaid_report',
                selected_rows: selectedRows,
                date_from: dateFrom,
                date_to: dateTo,
            },
            success: function (response) {
                if (response.success) {
                    const pdfContent = response.data.pdf_content;
                    $('#pdf-preview-container').show();
                    $('#pdf-preview').attr('src', 'data:application/pdf;base64,' + pdfContent);
                } else {
                    alert(response.data.message || 'Error generating prepaid report.');
                }
            },
            error: function () {
                alert('Error processing request.');
            },
        });
    });

    // Initialize the DataTable on page load
    initializeDataTable();
});
