<?php
/**
 * Settings Page
 * Allows users to configure fraud detection parameters
 */
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'woofraudguard'));
}

// Handle form submission
if (isset($_POST['woofraudguard_settings_nonce']) && wp_verify_nonce($_POST['woofraudguard_settings_nonce'], 'woofraudguard_save_settings')) {
    // Save settings
    $settings = array(
        'enable_phone_check' => isset($_POST['enable_phone_check']) ? '1' : '0',
        'enable_email_check' => isset($_POST['enable_email_check']) ? '1' : '0',
        'enable_ip_check' => isset($_POST['enable_ip_check']) ? '1' : '0',
        'ip_threshold' => max(1, min(20, intval($_POST['ip_threshold']))),
        'notification_email' => sanitize_email($_POST['notification_email']),
        'auto_delete' => isset($_POST['auto_delete']) ? '1' : '0',
        'delete_after_days' => max(1, min(30, intval($_POST['delete_after_days'])))
    );
    
    update_option('woofraudguard_settings', $settings);
    
    // Show success message
    echo '<div class="notice notice-success is-dismissible"><p>' . 
         __('Settings saved successfully.', 'woofraudguard') . 
         '</p></div>';
}

// Get current settings
$settings = get_option('woofraudguard_settings', array(
    'enable_phone_check' => '1',
    'enable_email_check' => '1',
    'enable_ip_check' => '1',
    'ip_threshold' => '5',
    'notification_email' => get_option('admin_email'),
    'auto_delete' => '0',
    'delete_after_days' => '7'
));
?>

<div class="wrap woofraudguard-settings-page">
    <h1><?php esc_html_e('WooFraudGuard Settings', 'woofraudguard'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('woofraudguard_save_settings', 'woofraudguard_settings_nonce'); ?>
        
        <div class="settings-section">
            <h2><?php esc_html_e('Fraud Detection Rules', 'woofraudguard'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Phone Validation', 'woofraudguard'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="enable_phone_check" value="1" <?php checked($settings['enable_phone_check'], '1'); ?>>
                            <?php esc_html_e('Enable fake phone number detection', 'woofraudguard'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Checks for common fake phone patterns and empty phone numbers.', 'woofraudguard'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e('Email Validation', 'woofraudguard'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="enable_email_check" value="1" <?php checked($settings['enable_email_check'], '1'); ?>>
                            <?php esc_html_e('Enable disposable email detection', 'woofraudguard'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Checks for disposable email domains and suspicious email patterns.', 'woofraudguard'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e('IP Address Monitoring', 'woofraudguard'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="enable_ip_check" value="1" <?php checked($settings['enable_ip_check'], '1'); ?>>
                            <?php esc_html_e('Enable same IP address limit', 'woofraudguard'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Detects when too many orders come from the same IP address.', 'woofraudguard'); ?></p>
                        
                        <div class="ip-threshold" style="margin-top: 10px; <?php echo $settings['enable_ip_check'] == '1' ? '' : 'display:none;'; ?>">
                            <label for="ip_threshold"><?php esc_html_e('Maximum orders from same IP (24h):', 'woofraudguard'); ?></label>
                            <input type="number" name="ip_threshold" id="ip_threshold" min="1" max="20" value="<?php echo esc_attr($settings['ip_threshold']); ?>" class="small-text">
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="settings-section">
            <h2><?php esc_html_e('Notifications & Actions', 'woofraudguard'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Notification Email', 'woofraudguard'); ?></th>
                    <td>
                        <input type="email" name="notification_email" value="<?php echo esc_attr($settings['notification_email']); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('Email address to notify when fraud is detected.', 'woofraudguard'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php esc_html_e('Auto Delete', 'woofraudguard'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="auto_delete" value="1" <?php checked($settings['auto_delete'], '1'); ?>>
                            <?php esc_html_e('Automatically delete fraudulent orders', 'woofraudguard'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Fraudulent orders will be automatically deleted after a specified period.', 'woofraudguard'); ?></p>
                        
                        <div class="delete-settings" style="margin-top: 10px; <?php echo $settings['auto_delete'] == '1' ? '' : 'display:none;'; ?>">
                            <label for="delete_after_days"><?php esc_html_e('Delete after (days):', 'woofraudguard'); ?></label>
                            <input type="number" name="delete_after_days" id="delete_after_days" min="1" max="30" value="<?php echo esc_attr($settings['delete_after_days']); ?>" class="small-text">
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        
        <p class="submit">
            <button type="submit" class="button button-primary"><?php esc_html_e('Save Settings', 'woofraudguard'); ?></button>
        </p>
    </form>
</div>
