(function($) {
    'use strict';
    
    $(document).ready(function() {
        // License activation form
        $('#activate-license-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $button = $('#activate-license');
            var $message = $('#license-message');
            
            // Disable button and show loading state
            $button.prop('disabled', true).text(woofraudguard_ajax.activating_text || 'Activating...');
            
            $.ajax({
                url: woofraudguard_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'woofraudguard_activate_license',
                    security: woofraudguard_ajax.nonce,
                    license_key: $('#license_key').val()
                },
                success: function(response) {
                    if (response.success) {
                        $message.removeClass('notice-error hidden').addClass('notice-success').text(response.data.message);
                        
                        // Reload page after successful activation
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        $message.removeClass('notice-success hidden').addClass('notice-error').text(response.data.message);
                        $button.prop('disabled', false).text(woofraudguard_ajax.activate_text || 'Activate License');
                    }
                },
                error: function() {
                    $message.removeClass('notice-success hidden').addClass('notice-error')
                        .text(woofraudguard_ajax.connection_error || 'Failed to connect to license server');
                    $button.prop('disabled', false).text(woofraudguard_ajax.activate_text || 'Activate License');
                }
            });
        });
        
        // License deactivation form
        $('#deactivate-license-form').on('submit', function(e) {
            e.preventDefault();
            
            if (!confirm(woofraudguard_ajax.deactivate_confirm || 'Are you sure you want to deactivate your license?')) {
                return;
            }
            
            var $form = $(this);
            var $button = $('#deactivate-license');
            var $message = $('#license-message');
            
            // Disable button and show loading state
            $button.prop('disabled', true).text(woofraudguard_ajax.deactivating_text || 'Deactivating...');
            
            $.ajax({
                url: woofraudguard_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'woofraudguard_deactivate_license',
                    security: woofraudguard_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $message.removeClass('notice-error hidden').addClass('notice-success').text(response.data.message);
                        
                        // Reload page after successful deactivation
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        $message.removeClass('notice-success hidden').addClass('notice-error').text(response.data.message);
                        $button.prop('disabled', false).text(woofraudguard_ajax.deactivate_text || 'Deactivate License');
                    }
                },
                error: function() {
                    $message.removeClass('notice-success hidden').addClass('notice-error')
                        .text(woofraudguard_ajax.connection_error || 'Failed to connect to license server');
                    $button.prop('disabled', false).text(woofraudguard_ajax.deactivate_text || 'Deactivate License');
                }
            });
        });
        
        // Toggle IP threshold field based on checkbox
        var $ipCheck = $('input[name="enable_ip_check"]');
        var $ipThreshold = $('.ip-threshold');
        
        if ($ipCheck.length && $ipThreshold.length) {
            $ipCheck.on('change', function() {
                $ipThreshold.toggle(this.checked);
            });
        }
        
        // Toggle delete settings based on checkbox
        var $autoDelete = $('input[name="auto_delete"]');
        var $deleteSettings = $('.delete-settings');
        
        if ($autoDelete.length && $deleteSettings.length) {
            $autoDelete.on('change', function() {
                $deleteSettings.toggle(this.checked);
            });
        }
    });
})(jQuery);
