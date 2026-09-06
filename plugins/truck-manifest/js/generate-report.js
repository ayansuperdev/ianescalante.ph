jQuery(document).ready(function($) {
    let selected = [];

    // Collect all selected checkboxes
    $('#select-all').on('change', function() {
        $('.waybill-checkbox').prop('checked', $(this).is(':checked'));
        collectSelected();
    });

    $('.widefat tbody').on('change', '.waybill-checkbox', collectSelected);

    function collectSelected() {
        selected = $('.waybill-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        $('#generate-pdf-btn').prop('disabled', selected.length === 0);
    }

    // Handle form submit for all statuses
    $('#filter-form').on('submit', function(e) {
        e.preventDefault();
        let status = $('[name="status"]').val();
        let role = $('[name="role"]').val();
        let dateFrom = $('[name="date_from"]').val();
        let dateTo = $('[name="date_to"]').val();

        if (status === 'Collected') {
            fetchCollectedReports(role, dateFrom, dateTo); // Custom behavior for Collected
        } else {
            this.submit(); // Default behavior for other statuses
        }
    });

    // Fetch Collected Reports
    function fetchCollectedReports(role, dateFrom, dateTo) {
        $.ajax({
            url: generateReportParams.ajaxurl,
            type: 'POST',
            data: {
                action: 'fetch_collected_reports',
                role: role,
                date_from: dateFrom,
                date_to: dateTo
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    populateTable(response.data);
                } else {
                    alert('No collected reports found.');
                    $('.widefat tbody').html('<tr><td colspan="11">No records found.</td></tr>');
                }
            },
            error: function() {
                alert('Error fetching collected reports.');
            }
        });
    }


    function populateTable(data) {
    let html = '';
        data.forEach(row => {
            html += `<tr>
                <td>${row.delivery_date || ''}</td>
                <td>${row.collected_date || ''}</td>
                <td>${row.waybill_no || ''}</td>
                <td>${row.consignee || ''}</td>
                <td>${row.consignor || ''}</td>
                <td>${row.descriptions || ''}</td>
                <td>${parseFloat(row.amount || 0).toFixed(2)}</td>
                <td>${parseFloat(row.discount || 0).toFixed(2)}</td>
                <td>${parseFloat(row.discount_rate || 0).toFixed(2)}</td>
                <td>${parseFloat(row.net_amount || 0).toFixed(2)}</td>
                <td><input type="checkbox" class="waybill-checkbox" value="${row.id || ''}"></td>
            </tr>`;
        });

        $('.widefat tbody').html(html);
        collectSelected();
    }


    // Generate PDF Button
    $('#generate-pdf-btn').on('click', function() {
    let status = $('[name="status"]').val();
    let dateFrom = $('[name="date_from"]').val();
    let dateTo = $('[name="date_to"]').val();
    let selectedWaybills = $('.waybill-checkbox:checked').map(function() {
        return $(this).val();
    }).get();

    // Client-side validation


    if (selectedWaybills.length === 0) {
        alert('Please select at least one waybill to generate the PDF.');
        return;
    }


    // Proceed with the AJAX request
    let formData = new FormData();
    formData.append('action', 'generate_selected_waybills_pdf');
    formData.append('status', status);
    formData.append('waybill_ids', selectedWaybills.join(','));
    formData.append('date_from', dateFrom);
    formData.append('date_to', dateTo);

    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        xhrFields: { responseType: 'blob' },
        success: function(response) {
            if (response && response.size > 0) {
                let blob = new Blob([response], { type: 'application/pdf' });
                let url = URL.createObjectURL(blob);
                $('#pdf-preview').attr('src', url).show();
            } else {
                alert('Error generating PDF. No valid data found.');
            }
        },
        error: function(xhr) {
            if (xhr.responseJSON && xhr.responseJSON.data) {
                alert(xhr.responseJSON.data); // Server error message
            } else {
                alert('Error generating PDF. Please try again.');
            }
        }
    });
});



});
