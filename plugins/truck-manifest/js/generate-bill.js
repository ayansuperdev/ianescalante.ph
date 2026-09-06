jQuery(document).ready(function ($) {
    const generateBillBtn = $('#generate-bill-btn');
    const roleFilter = $('#role_filter');
    const userFilter = $('#user_name_filter');

        // Collect all selected checkboxes
        $('#select-all').on('change', function() {
            $('.waybill-checkbox').prop('checked', $(this).is(':checked'));
            toggleGenerateBillButton();
        });

    // Function to toggle button state
    function toggleGenerateBillButton() {
        const anyChecked = $('.waybill-checkbox:checked').length > 0;
        generateBillBtn.prop('disabled', !anyChecked);
    }

    // Attach event listener to all checkboxes
    $('.waybill-checkbox').on('change', toggleGenerateBillButton);

    // Perform initial check on page load
    toggleGenerateBillButton();

    // Delegate the change event to the document for dynamically added checkboxes
    $(document).on('change', '.waybill-checkbox', function () {
        const anyChecked = $('.waybill-checkbox:checked').length > 0;
        generateBillBtn.prop('disabled', !anyChecked);
    });

    // Handle button click to generate PDF
    generateBillBtn.on('click', function (e) {
        e.preventDefault();

        const selectedIds = $('.waybill-checkbox:checked').map(function () {
            return $(this).val();
        }).get();

        if (selectedIds.length === 0) {
            alert('Please select at least one waybill.');
            return;
        }

        $.ajax({
            url: generateBillParams.ajaxurl,
            method: 'POST',
            data: {
                action: 'generate_bill_pdf',
                waybill_ids: selectedIds
            },
            xhrFields: { responseType: 'blob' }, // Expect PDF response
            success: function (blob) {
                const url = URL.createObjectURL(blob);
                $('#pdf-preview').attr('src', url); // Preview PDF
                $('#pdf-preview-container').show();
            },
            error: function (xhr, status, error) {
                console.error('Error generating PDF:', xhr.responseText);
                alert('Failed to generate the PDF. Please try again.');
            }
        });
    });

    roleFilter.on('change', function () {
        const selectedRole = $(this).val();

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'get_users_by_role',
                role: selectedRole
            },
            success: function (response) {
                userFilter.empty().append('<option value="">All Users</option>');

                if (response.length > 0) {
                    $.each(response, function (index, userName) {
                        userFilter.append(`<option value="${userName}">${userName}</option>`);
                    });
                }
            },
            error: function () {
                console.error("Error fetching users.");
            }
        });
    });
});
