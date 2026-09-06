jQuery(document).ready(function($) {
    const ajaxUrl = ajaxurl.url || ajaxurl;  // Handle structure variations

    function pollUncollectedStatus() {
        console.log("Polling for uncollected status...");
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: { action: 'poll_uncollected_status_updates' },
            success: function(response) {
                console.log("Polling response:", response);
                response.forEach(function(waybill) {
                    let row = $(".waybill-row[data-id='" + waybill.id + "']");
                    row.find(".status-cell").text("Uncollected").css("background-color", "#e82f1a");
                });
            },
            error: function(error) {
                console.error("Polling error:", error);
            }
        });
    }
    
// Poll every 2 minutes (120 seconds) for testing
setInterval(pollUncollectedStatus, 120000); // 120000 ms = 2 minutes

    

});
