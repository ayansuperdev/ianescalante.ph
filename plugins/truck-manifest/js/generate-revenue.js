jQuery(document).ready(function($) {
    let selectedRows = [];

    // Function to collect selected checkboxes
    function collectSelected() {
        selectedRows = $('.waybill-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        // Enable/Disable the Generate PDF button based on selection
        $('#generate-pdf').prop('disabled', selectedRows.length === 0);
        console.log("Selected Rows:", selectedRows); // Debugging: Check selected rows
    }

    // Function to load data (with optional filters)
    function loadManifestDetails(dateFrom = '', dateTo = '', truckNumber = '') {
        $.ajax({
            url: ajaxurl,
            method: 'GET',
            data: {
                action: 'fetch_manifest_details',
                date_from: dateFrom,
                date_to: dateTo,
                truck_number: truckNumber,
            },
            success: function(response) {
                const tbody = $('#revenue-table-body');
                tbody.empty();

                if (response.success && response.data.length > 0) {
                    $.each(response.data, function(index, row) {
                        console.log("Processing Row:", row); // Debugging: Check row data
                        tbody.append(`
                            <tr>
                                <td>${row.manifest_number}</td>
                                <td>${row.loading_date}</td>
                                <td>${row.truck_number}</td>
                                <td>${row.driver}</td>
                                <td>${row.arrival_date}</td>
                                <td>₱${parseFloat(row.revenue).toLocaleString()}</td>
                                <td><input type="checkbox" class="waybill-checkbox" value="${row.manifest_number || 'default_value'}"></td>
                            </tr>
                        `);
                    });

                    $('#select-all').prop('checked', false); // Reset select-all checkbox
                } else {
                    tbody.append('<tr><td colspan="7">No records found.</td></tr>');
                    $('#select-all').prop('checked', false);
                }

                collectSelected(); // Recalculate selected rows after loading
            },
            error: function() {
                alert('Error fetching data. Please try again.');
            },
        });
    }

    // Load all records on page load
    loadManifestDetails();

    // Filter button click event
    $('#filter-reports').on('click', function() {
        const dateFrom = $('#date-from').val();
        const dateTo = $('#date-to').val();
        const truckNumber = $('#truck-number').val();

        if (!dateFrom || !dateTo) {
            alert('Please select a valid date range.');
            return;
        }

        loadManifestDetails(dateFrom, dateTo, truckNumber);
    });

    // Select/Deselect all rows
    $('#select-all').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('.waybill-checkbox').prop('checked', isChecked);
        collectSelected();
    });

    // Delegated event for dynamically loaded checkboxes
    $('#revenue-table-body').on('change', '.waybill-checkbox', function() {
        const allCheckboxes = $('.waybill-checkbox');
        const checkedCheckboxes = $('.waybill-checkbox:checked');

        // Update the select-all checkbox state
        $('#select-all').prop('checked', allCheckboxes.length === checkedCheckboxes.length);

        collectSelected();
    });

    // Collect selected rows and preview PDF
    $('#generate-pdf').on('click', function(e) {
        e.preventDefault();

        if (selectedRows.length === 0) {
            alert('Please select at least one row to generate the PDF.');
            return;
        }

        const dateFrom = $('#date-from').val();
        const dateTo = $('#date-to').val();
        const truckNumber = $('#truck-number').val();

        // Send AJAX request
        $.ajax({
            url: generateRevenueParams.ajaxurl,
            method: 'POST',
            data: {
                action: 'generate_revenue_report_pdf',
                selected_rows: selectedRows,
                date_from: dateFrom,
                date_to: dateTo,
                truck_number: truckNumber,
            },
            success: function(response) {
                if (response.success && response.data.pdf_base64) {
                    const pdfDataUri = 'data:application/pdf;base64,' + response.data.pdf_base64;

                    // Display PDF in iframe
                    $('#pdf-preview-container').html(`
                        <iframe src="${pdfDataUri}" width="100%" height="600px" style="border: none;"></iframe>
                    `);
                } else {
                    alert('Error: ' + (response.data.message || 'Unknown error.'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText || error);
                alert('An error occurred while generating the PDF. Check console for details.');
            },
        });
    });
});
