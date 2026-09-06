jQuery(document).ready(function($) {
    $.ajaxSetup({ cache: false });

    var isSaved = false; // Flag to prevent multiple alerts
    
    $('#live-search').on('input', function () {
        var searchText = $(this).val().toLowerCase().trim();
    
        // Check if the search query is empty
        if (!searchText) {
            fetchDefaultRows(); // Fetch and display default rows
            return;
        }
    
        // Perform AJAX search
        $.ajax({
            url: liveSearchParams.ajax_url,
            type: 'POST',
            data: {
                action: 'search_waybills',
                search_query: searchText
            },
            success: function (response) {
                console.log("AJAX Response:", response); // Log the full response
    
                if (response.success) {
                    updateTableRows(response.data.results); // Reuse function to update table rows
                } else {
                    console.error("Error:", response.data.message);
                    displayNoRecordsMessage();
                }
            },
            error: function (xhr, status, error) {
                console.error("Error in AJAX request:", error);
            }
        });
    });
    
    // Fetch default rows
    function fetchDefaultRows() {
        $.ajax({
            url: liveSearchParams.ajax_url, // Ensure this URL is properly localized in WordPress
            type: 'POST',
            data: {
                action: 'get_default_rows' // Must match the PHP handler action
            },
            success: function (response) {
                console.log("Default Rows Response:", response);
    
                if (response.success) {
                    updateTableRows(response.data.results); // Update table with default rows
                } else {
                    displayNoRecordsMessage();
                }
            },
            error: function (xhr, status, error) {
                console.error("Error fetching default rows:", error);
            }
        });
    }
    
    
    // Update table rows dynamically
    function updateTableRows(rows) {
        var tableBody = $('#manifests-table tbody');
        tableBody.empty(); // Clear existing rows
    
        if (rows.length > 0) {
            rows.forEach(function (row) {
                console.log("Row Data:", row); // Log each row for debugging
    
                // Determine the status color
                var statusColor = '#FA8072'; // Default color
                if (row.status === 'Delivered') {
                    statusColor = '#2E8B57';
                } else if (row.status === 'Collected') {
                    statusColor = '#669999';
                } else if (row.status === 'Uncollected') {
                    statusColor = '#e82f1a';
                }
    
                // Format collect and prepaid amounts
                var collectAmount = row.collect ? '₱' + parseFloat(row.collect).toFixed(2) : '-';
                var prepaidAmount = row.prepaid ? '₱' + parseFloat(row.prepaid).toFixed(2) : '-';
    
                // Format delivery date
                var deliveryDate = row.delivery_date ? row.delivery_date : '-';
    
                // Calculate aging text
                var agingText = "-";
                const currentDate = new Date();
    
                if (row.status === 'Delivered') {
                    agingText = "For Collection";
                } else if (row.status === 'Collected') {
                    agingText = "Complete";
                } else if (row.status === 'Uncollected' && row.delivery_date) {
                    const deliveryDateObj = new Date(row.delivery_date);
                    if (!isNaN(deliveryDateObj)) {
                        const diffTime = Math.abs(currentDate - deliveryDateObj);
                        const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
                        agingText = `${diffDays} days`;
                    } else {
                        agingText = "Invalid date";
                    }
                } else if (row.status === 'Undelivered' && row.arrival_date) {
                    const arrivalDateObj = new Date(row.arrival_date);
                    if (!isNaN(arrivalDateObj)) {
                        const diffTime = Math.abs(currentDate - arrivalDateObj);
                        const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
                        agingText = `${diffDays} days`;
                    } else {
                        agingText = "Invalid date";
                    }
                }
    
                // Create the new row
                var newRow = $(`<tr class="waybill-row" 
                    data-id="${row.id}" 
                    data-status="${row.status}" 
                    data-arrival-date="${row.arrival_date}" 
                    data-delivery-date="${row.delivery_date}">
                    <td>${row.waybill_no}</td>
                    <td>${row.consignee}</td>
                    <td>${row.consignor}</td>
                    <td>${row.descriptions}</td>
                    <td>${collectAmount}</td>
                    <td>${prepaidAmount}</td>
                    <td class="status-cell">
                        <span class="status-button" style="background-color: ${statusColor};">${row.status}</span>
                    </td>
                    <td class="aging-cell">${agingText}</td>
                    <td>${deliveryDate}</td>
                </tr>`);
    
                tableBody.append(newRow); // Add the new row to the table
    
                // Add click handler for toggling status
                newRow.find('.status-button').on('click', function () {
                    let deliveryDate = null;
                    const currentRow = $(this).closest('.waybill-row');
                    const currentStatus = $(this).text().trim();
                    const newStatus = currentStatus === 'Undelivered' ? 'Delivered' : 'Undelivered';
    
                    // Apply date and time for 'Delivered' status with timezone adjustment
                    if (newStatus === 'Delivered') {
                        const options = {
                            timeZone: 'Asia/Manila',
                            year: 'numeric',
                            month: '2-digit',
                            day: '2-digit',
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit'
                        };
                        const nowInManila = new Date().toLocaleString('en-US', options).replace(',', '');
                        const parts = nowInManila.split(' ');
                        const dateParts = parts[0].split('/');
                        deliveryDate = `${dateParts[2]}-${dateParts[0]}-${dateParts[1]} ${parts[1]}`;
                    }
    
                    console.log('Delivery Date:', deliveryDate);
    
                    // Update status and delivery date via AJAX
                    $.ajax({
                        url: liveSearchParams.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'update_status',
                            waybill_id: row.id,
                            status: newStatus,
                            delivery_date: deliveryDate
                        },
                        success: function (response) {
                            if (response.success) {
                                $('#live-search').trigger('input'); // Reapply search filter after update
    
                                // Update UI with new delivery date
                                if (newStatus === 'Delivered' && deliveryDate) {
                                    currentRow.find('.delivery-date-cell').text(deliveryDate);
                                } else {
                                    currentRow.find('.delivery-date-cell').text('-');
                                }
                            } else {
                                console.error("Status update failed:", response.data.message);
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error("AJAX request failed:", status, error);
                        }
                    });
                });
    
                // Add double-click handler for showing popup
                newRow.on('dblclick', function () {
                    var waybillId = $(this).data('id');
                    fetchWaybillDetails(waybillId); // Call function to fetch and display popup details
                });
            });
        } else {
            displayNoRecordsMessage();
        }
    }
    
    // Display "No matching records" message
    function displayNoRecordsMessage() {
        $('#manifests-table tbody').html('<tr><td colspan="9" style="text-align: center;">No matching records found.</td></tr>');
    }
    
    
        // Handle row double-click to show details
        $('.waybill-row').on('dblclick', function() {
            var waybillId = $(this).data('id');
            fetchWaybillDetails(waybillId);
    
            // Show the overlay and popup
            $('#popup-overlay').show();
            $('#waybill-details-popup').show();
        });
    
        // Hide the popup and overlay when clicking the close button or overlay
        $(document).on('click', '#popup-overlay, #waybill-details-popup .close-button', function() {
            // Hide the popup and overlay
            $('#waybill-details-popup').hide();
            $('#popup-overlay').hide();
    
            // Only show the alert if changes were saved and ensure it shows only once
            if (isSaved) {
                alert('Details saved successfully.');
                //isSaved = false; // Reset the flag
                saveWaybillDetails();
            }
        });

    // Click event handler for the status button to toggle "Delivered" / "Undelivered" status
    $(document).on('click', '.status-button', function() {
        var $button = $(this);
        var waybillId = $button.closest('.waybill-row').data('id');
        var currentStatus = $button.text().trim();
        var newStatus = currentStatus === 'Delivered' ? 'Undelivered' : 'Delivered';

        // Update the status button's text and color on the client side for immediate feedback
        $button.text(newStatus);
        $button.css('background-color', newStatus === 'Delivered' ? '#2E8B57' : '#FA8072');

        // AJAX call to update the status in the database
        $.ajax({
            url: liveSearchParams.ajax_url,
            type: 'POST',
            data: {
                action: 'update_status',
                waybill_id: waybillId,
                status: newStatus
            },
            success: function(response) {
                if (!response.success) {
                    console.error("Failed to update status:", response);
                }
            },
            error: function(xhr, status, error) {
                console.error("Error in AJAX status update:", status, error);
            }
        });
    });

    // Popup functionality

    // Fetch and show waybill details in popup
    // Function to fetch waybill details via AJAX
    function fetchWaybillDetails(waybillId) {
        $.post(liveSearchParams.ajax_url, {
            action: 'get_waybill_details',
            waybill_id: waybillId
        }, function (response) {
            console.log("AJAX response:", response);
    
            if (response.success) {
                // Update popup content
                $("#waybill-details-popup").html(response.data.html).show();
                $('#popup-overlay').show();
                $('#waybill-details-popup').show();
    
                const fetchedStatus = response.data.data.status.trim();
                console.log("Fetched Status:", fetchedStatus);
    
                // Update popup fields
                $('#status').text(fetchedStatus);
                $('#waybill-details-popup').data('waybill-id', waybillId);
                $('#waybill-details-popup').append('<button class="close-button">Close</button>');
    
                // Ensure buttons are present
                if (!$('#collectedBtn').length) {
                    $('#waybill-details-popup').append('<button id="collectedBtn" style="position: absolute; bottom: 20px; left: 20px;">Collected</button>');
                }
    
                if (!$('#resetBtn').length) {
                    $('#waybill-details-popup').append('<button id="resetBtn" style="position: absolute; bottom: 575px; right: 18px;">Reset</button>');
                }
    
                // Reinitialize Select2 properly after the popup content is updated
                if ($('#assign-select').length) {
                    initializeSelect2(); // Reinitialize Select2 only if the element exists
                } else {
                    console.warn("Assign select dropdown not found in popup.");
                }
    
                // Call existing setup functions
                setupEditableFields();
                setupCollectedButton();
                setupResetButton();
            } else {
                console.error("Error fetching waybill details:", response.data);
                alert("Failed to fetch waybill details. Please try again.");
            }
        });
    }

    // Separate function for 'Collected' button logic
// Setup 'Collected' button functionality
function setupCollectedButton() {
    $('#collectedBtn').off('click').on('click', function () {
        const status = $('#status').text().trim();
        const prepaid = parseFloat($('#prepaid').text()) || 0;
        const collect = parseFloat($('#collect').text()) || 0;
        const waybillId = $('#waybill-details-popup').data('waybill-id');
        const currentDateTime = getCurrentDateTimeMySQL();

        console.log("Collected Button Clicked:");
        console.log(" - Current Status:", status);
        console.log(" - Prepaid Value:", prepaid);
        console.log(" - Collect Value:", collect);
        console.log(" - Current DateTime (Asia/Manila):", currentDateTime);

        // Special Condition: Collect = 0 and Prepaid > 0
        if ((status === 'Undelivered' || status === 'Delivered') && collect === 0 && prepaid > 0) {
            alert("Marking as 'Collected' in the popup only. Status in the table will remain unchanged.");

            // Update the "Collected Date" in the popup
            $('#collected-date').text(currentDateTime); // Update the collected date field in the popup
            console.log(`Collected Date updated in the popup: ${currentDateTime}`);

            // Save the collected date to the server
            $.post(liveSearchParams.ajax_url, {
                action: 'update_collected_date_popup_only',
                waybill_id: waybillId,
                collected_date: currentDateTime,
                _: new Date().getTime() // Prevent caching
            }, function (response) {
                if (response.success) {
                    console.log('Collected Date saved successfully for popup.');
                } else {
                    console.error('Error saving Collected Date for popup:', response);
                }
            }).fail(function (xhr, status, error) {
                console.error("AJAX Error Occurred:", error);
            });

            return; // Do not proceed with status update
        }

        if (status === 'Undelivered' && prepaid === 0) {
            alert("Status is Undelivered, and Prepaid value is 0. Change to Delivered first.");
            return;
        }

        // Normal Condition: Collect > 0 and Prepaid = 0
        const isSimpleStatus = status === 'Delivered';
        const isSpecialCondition = (status === 'Undelivered' && prepaid > 0 && collect === 0) || status === 'Uncollected';

        if (isSimpleStatus || isSpecialCondition) {
            const newStatus = 'Collected';

            // Prepare the data payload
            const data = {
                action: 'update_waybill_status',
                waybill_id: waybillId,
                status: newStatus,
                delivery_date: $('#delivery-date').text(), // Keep existing delivery_date
                collected_date: currentDateTime, // Always update collected_date
                _: new Date().getTime() // Prevent caching
            };

            console.log("Sending Data Payload to Server:");
            console.log(data);

            // AJAX request to update status
            $.post(liveSearchParams.ajax_url, data, function (response) {
                console.log("AJAX Response from Server:", response);

                if (response.success) {
                    $('#status').text(newStatus);
                    alert('Status updated to Collected');

                    // Update UI dynamically in the table
                    const row = $(`.waybill-row[data-id="${waybillId}"]`);
                    const agingText = 'Complete';
                    const backgroundColor = '#669999';

                    row.find('.status-cell').html(
                        `<span class="status-button" style="background-color: ${backgroundColor};">${newStatus}</span>`
                    );
                    row.find('.aging-cell').text(agingText);
                    row.find('.collected-date-cell').text(currentDateTime); // Update collected date only

                    console.log(
                        `Row UI Updated: Waybill ID - ${waybillId}, New Status - ${newStatus}, Collected Date - ${currentDateTime}`
                    );
                } else {
                    alert('Error updating status. Please check the server logs.');
                }
            }).fail(function (xhr, status, error) {
                console.error("AJAX Error Occurred:", error);
                console.error(" - Status:", status);
                console.error(" - Response Text:", xhr.responseText);
            });
        } else {
            console.log("Status change to 'Collected' not allowed due to unmet conditions.");
        }
    });
}


// Setup "Reset" button functionality
function setupResetButton() {
    $('#resetBtn').off('click').on('click', function () {
        var waybillId = $('#waybill-details-popup').data('waybill-id');

        // Confirmation alert
        if (confirm("Are you sure you want to RESET?")) {
            // AJAX request to reset status, delivery_date, and collected_date
            $.post(liveSearchParams.ajax_url, {
                action: 'reset_waybill_status',
                waybill_id: waybillId
            }, function (response) {
                if (response.success) {
                    alert('Waybill reset successfully.');

                    // Update the popup UI dynamically
                    $('#status').text('Undelivered');
                    $('#delivery-date').text('-');
                    $('#collected-date').text('-');

                    // Update the main table row dynamically
                    const row = $(`.waybill-row[data-id="${waybillId}"]`);
                    if (row.length) {
                        row.data('status', 'Undelivered');
                        row.find('.status-cell').html('<span class="status-button" style="background-color: #FA8072;">Undelivered</span>');
                        row.find('.delivery-date-cell').text('-');
                        row.find('.aging-cell').text('-'); // Reset aging
                    }
                } else {
                    alert('Error resetting waybill. Please try again.');
                }
            }).fail(function (xhr, status, error) {
                console.error("Error resetting waybill:", error);
            });
        }
    });
}



// Function to get current date and time in MySQL format for Asia/Manila timezone
function getCurrentDateTimeMySQL() {
    const now = new Date();
    const options = {
        timeZone: 'Asia/Manila',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true,
    };
    const dateTimeString = new Intl.DateTimeFormat('en-US', options).format(now).replace(',', '');
    return dateTimeString.replace(/(\d+)\/(\d+)\/(\d+)/, '$3-$1-$2'); // Convert to YYYY-MM-DD HH:MM:SS
}

     // Save waybill details function
     function saveWaybillDetails() {
        var waybillId = $('#waybill-details-popup').data('waybill-id');
        var totalAmount = parseFloat($('#total-amount').text()) || 0;
        var discount = parseFloat($('#discount').text()) || 0;
        var netAmount = totalAmount - discount; // Calculate net amount
        var discountRate = (totalAmount > 0) ? (discount / totalAmount) * 100 : 0;
        var remarks = $('#remarks').text().trim();

        // Fetch manually edited dates from input fields
        var deliveryDate = $('#ddate').val();
        var collectedDate = $('#cdate').val();

        console.log("Saving Waybill Details:");
        console.log("Delivery Date:", deliveryDate);
        console.log("Collected Date:", collectedDate);

        // Do not include status in the AJAX request since it's not editable in the popup
        $.post(liveSearchParams.ajax_url, {
            action: 'update_waybill_details',
            waybill_id: waybillId,
            delivery_date: deliveryDate,
            collected_date: collectedDate,
            discount: discount,
            discount_rate: discountRate, // Include the updated discount rate
            net_amount: netAmount,
            remarks: remarks
        }, function (response) {
            if (response.success && !isSaved) {
                isSaved = true;
                setTimeout(function () {
                    alert('Details saved successfully.');
                    isSaved = false;
                }, 100);
            } else if (!response.success) {
                alert('Error saving details.');
            }
        });
    }

  // Function to set up inline edit behavior for Discount and Remarks
  function setupEditableFields() {
    // Double-click to edit Discount and Remarks fields
    $('#discount, #remarks').off('dblclick').on('dblclick', function() {
        $(this).attr('contenteditable', 'true').focus();
    });

    // Save changes when clicking outside the editable fields
    $('#popup-overlay, #waybill-details-popup').off('click').on('click', function(event) {
        if (!$(event.target).hasClass('editable')) {
            $('.editable').attr('contenteditable', 'false'); // Disable editing

            // Update the net amount based on discount
            var totalAmount = parseFloat($('#total-amount').text()) || 0;
            var discount = parseFloat($('#discount').text()) || 0;
            var netAmount = totalAmount - discount;
            // Calculate Discount Rate %
            var discountRate = (totalAmount > 0) ? (discount / totalAmount) * 100 : 0;

            // Fetch manually edited dates from input fields
            var deliveryDate = $('#ddate').val();
            var collectedDate = $('#cdate').val();

            console.log("Saving Waybill Details:");
            console.log("Delivery Date:", deliveryDate);
            console.log("Collected Date:", collectedDate);

            $('#net-amount').text(netAmount.toFixed(2)); // Update the net amount field

            
            $('#discount-rate').text(discountRate.toFixed(2) + '%'); // Update Discount Rate %


           // Prepare data for AJAX call to save changes
            var waybillId = $('#waybill-details-popup').data('waybill-id');
            var remarks = $('#remarks').text().trim();

           // Send AJAX request to save discount and remarks to the database
            $.post(liveSearchParams.ajax_url, {
                action: 'update_waybill_details',
                waybill_id: waybillId,
                delivery_date: deliveryDate,
                collected_date: collectedDate,
                discount: discount,
                discount_rate: discountRate, // Include the updated discount rate
                net_amount: netAmount,
                remarks: remarks // Ensure remarks is included in the request
            }, function(response) {
                if (response.success) {
                    // Update the remarks field in the popup with the saved value
                    $('#remarks').text(remarks);
                    console.log('Details updated successfully');
                } else {
                    alert('Error saving details.');
                }
            });
        }
    });
    // Prevent popup closing when clicking inside editable fields
    $('#discount, #remarks').off('click').on('click', function(event) {
        event.stopPropagation(); // Prevents triggering popup close event
    });
}

// Timer function to check after a delay and update status if conditions are met
function startUncollectedTimer(waybillId) {
    let countdownTime = 1; // Set to 1 min for testing; change to 1440 for 24 hours

    const countdownInterval = setInterval(() => {
        console.log(`Countdown: ${countdownTime} minute(s) remaining with waybill ID: ${waybillId}`);

        if (countdownTime <= 0) {
            clearInterval(countdownInterval);

            // Check the conditions to update status to 'Uncollected'
            checkAndApplyUncollectedStatus(waybillId);
            // Re-fetch waybill details if necessary, or use current stored values
            $.ajax({
                url: liveSearchParams.ajax_url,
                method: 'POST',
                data: {
                    action: 'get_waybill_details',
                    waybill_id: waybillId
                },
                success: function(response) {
                    if (response.success) {
                        const waybillData = response.data.data;
                        const status = waybillData.status;
                        const prepaid = parseInt(waybillData.prepaid, 10);
                        const collect = parseInt(waybillData.collect, 10);

                        // Check conditions again before changing to 'Uncollected'
                        if (status === 'Delivered' && prepaid === 0 && collect > 0) {
                            updateWaybillStatus(waybillId, 'Uncollected', () => {
                                applyStatusUpdate(waybillId, 'Uncollected', '#e82f1a');
                            });
                            console.log(`Waybill ${waybillId} status changed to 'Uncollected'.`);
                        }
                    } else {
                        console.log('Error fetching final waybill details.');
                    }
                },
                error: function() {
                    console.log('Error re-fetching waybill details for final check.');
                }
            });
        } else {
            countdownTime--;
        }
    }, 60000); // Countdown interval set to 1 minute (60000 ms) for testing
}

// Update status function, triggers UI update on success
function updateWaybillStatus(waybillId, newStatus, callback) {
    const currentDateTime = getCurrentDateTimeMySQL();

    const data = {
        action: 'update_waybill_status',
        waybill_id: waybillId,
        status: newStatus,
    };

    // Set timestamps based on status
    if (newStatus === 'Delivered') {
        data.delivery_date = currentDateTime; // Set delivery_date
    } else if (newStatus === 'Collected') {
        data.collected_date = currentDateTime; // Set collected_date
    }

    $.ajax({
        url: liveSearchParams.ajax_url,
        method: 'POST',
        data: data,
        success: function (response) {
            console.log(`AJAX response for waybill ID ${waybillId}:`, response);
            if (response.success) {
                console.log(`Successfully updated waybill ID: ${waybillId} to status: '${newStatus}'`);
                if (typeof callback === 'function') {
                    callback();
                }
            }
        },
        error: function (xhr, status, error) {
            console.error(`Error updating waybill ID ${waybillId}:`, error);
        }
    });
}


// Apply UI update to set the 'Uncollected' status immediately without refresh
function applyStatusUpdate(waybillId, newStatus, backgroundColor) {
    console.log("Attempting to update UI status for waybill ID:", waybillId);

    // Select the waybill row and status button
// Ensure accessibility by adding ARIA labels or roles
    const waybillRow = document.querySelector(`tr[data-id="${waybillId}"]`);
    if (!waybillRow) {
        console.log(`No waybill row found for ID: ${waybillId}`);
        return;
    }

// Ensure accessibility by adding ARIA labels or roles
    const statusCell = waybillRow.querySelector('.status-cell');
    if (statusCell) {
// Ensure accessibility by adding ARIA labels or roles
        const statusButton = statusCell.querySelector('.status-button');
        if (statusButton) {
            // Update the status text and background color
            statusButton.textContent = newStatus;
            statusButton.style.backgroundColor = backgroundColor;
            console.log(`Status button updated to '${newStatus}' with color: ${backgroundColor}`);

            // Trigger re-render
            statusButton.style.display = 'none';
            setTimeout(() => {
                statusButton.style.display = 'inline';
            }, 10);

            // Update the data-status attribute
            waybillRow.setAttribute('data-status', newStatus);
        }
    }
}

// Timer function for checking and updating status dynamically
function checkAndApplyUncollectedStatus() {
// Ensure accessibility by adding ARIA labels or roles
    const rows = document.querySelectorAll('.waybill-row');
    rows.forEach(row => {
        const waybillId = row.getAttribute('data-id');
        const status = row.getAttribute('data-status');
        const prepaid = parseFloat(row.getAttribute('data-prepaid')) || 0;
        const collect = parseFloat(row.getAttribute('data-collect')) || 0;

        // Check conditions and apply 'Uncollected' status
        if (status === 'Delivered' && prepaid === 0 && collect > 0) {
            console.log(`Setting status to 'Uncollected' for row ID ${waybillId}`);
            
            updateWaybillStatus(waybillId, 'Uncollected', function() {
                // Dynamically update the status and background in UI after successful AJAX call
                applyStatusUpdate(waybillId, 'Uncollected', '#e82f1a');
            });
        }
    });
}

setInterval(checkAndApplyUncollectedStatus, 60000);

    // Helper: Parse Delivery Date
    function parseDeliveryDate(dateString) {
        const parsedDate = new Date(dateString + ' UTC'); // Treat the input as UTC
        if (!isNaN(parsedDate)) {
            // Convert UTC to Asia/Manila timezone
            return parsedDate.toLocaleString('en-US', { 
                timeZone: 'Asia/Manila',
                year: 'numeric',
                month: 'short',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true // Enable 12-hour time format
             });
        }
        console.error('Invalid date string:', dateString);
        return null;
    }
    

    // Prevent the same event from firing twice by checking the target
    $('#popup-overlay').on('click', function(event) {
        if ($(event.target).is('#popup-overlay')) {
            // Hide popup only if the overlay itself is clicked
            $('#waybill-details-popup').hide();
            $('#popup-overlay').hide();
        }
    });

       // Initialize Select2 for user assignment
       function initializeSelect2() {
        const $assignSelect = $("#assign-select");
    
        // Destroy any existing Select2 instance to avoid duplication
        if ($assignSelect.data('select2')) {
            $assignSelect.select2('destroy');
        }
    
        // Reinitialize Select2
        $assignSelect.select2({
            placeholder: "-- Select User --",
            width: "resolve",
            ajax: {
                url: ajax_assign.ajax_url,
                type: "POST",
                dataType: "json",
                delay: 250, // Debounce to reduce requests
                data: function (params) {
                    return {
                        action: "search_assign_roles",
                        search: params.term || "", // Search term
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.success ? data.results : [],
                    };
                },
                cache: true, // Enable caching for better performance
            }
        }).on("select2:open", function () {
            console.log("Dropdown opened for user assignment.");
        }).on("select2:select", function (e) {
            const selectedUser = e.params.data;
    
            if (!selectedUser.id || selectedUser.id === 'no-matches') {
                alert("No valid user selected.");
                return;
            }
    
            console.log("Selected Role ID:", selectedUser.id);
            console.log("Waybill ID:", $("#waybill-id").val());
    
            // AJAX request to assign the user
            $.ajax({
                url: ajax_assign.ajax_url,
                type: "POST",
                data: {
                    action: "update_role_for_waybill",
                    role_id: selectedUser.id,
                    waybill_id: $("#waybill-id").val(),
                },
                success: function (response) {
                    if (response.success) {
                        alert("User assigned successfully!");
                    } else {
                        alert("Failed to assign user. Check server logs.");
                    }
                },
                error: function (xhr, status, error) {
                    console.error("Error assigning user:", error);
                },
            });
        }).on("select2:close", function () {
            console.log("Dropdown closed for user assignment.");
        });
    }
    
    

        // Initialize Select2 on page load
        initializeSelect2();

});
