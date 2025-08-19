(function($) {
    $(document).ready(function() {
        // Track checkout abandonment
        if (woofraudguard.is_checkout) {
            let checkoutData = {};
            
            // Capture checkout form data
            $(window).on('beforeunload', function() {
                checkoutData = {
                    billing_email: $('#billing_email').val(),
                    billing_phone: $('#billing_phone').val(),
                    billing_city: $('#billing_city').val(),
                    billing_state: $('#billing_state').val(),
                    billing_postcode: $('#billing_postcode').val(),
                    timestamp: new Date().toISOString()
                };
                
                // Send data to server via AJAX
                $.ajax({
                    url: woofraudguard.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'track_abandoned_checkout',
                        checkout_data: checkoutData
                    },
                    async: false // Ensure data is sent before page unloads
                });
            });
        }
    });
})(jQuery);
