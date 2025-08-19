<?php
/**
 * License Management Page
 * Allows users to activate, deactivate, and view license status
 */
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'woofraudguard'));
}

$license_verifier = new WooFraudGuard_License_Verifier();
$license_key = get_option('woofraudguard_license_key', '');
$valid_until = get_option('woofraudguard_license_valid_until', '');
$detection_count = (int) get_option('woofraudguard_detection_count', 0);
$max_detections = 100;
$is_free_user = empty($license_key);
$is_valid = $license_verifier->is_valid();
?>

<div class="wrap woofraudguard-license-page">
    <h1><?php esc_html_e('WooFraudGuard License Management', 'woofraudguard'); ?></h1>
    
    <?php if ($is_valid && !$is_free_user) : ?>
        <div class="license-status valid">
            <h2><?php esc_html_e('License Status: Active', 'woofraudguard'); ?></h2>
            <p><?php printf(__('Your license is valid until %s.', 'woofraudguard'), date_i18n(get_option('date_format'), strtotime($valid_until))); ?></p>
            <?php if (!empty($valid_until) && strtotime($valid_until) < strtotime('+30 days')) : ?>
                <div class="notice notice-warning">
                    <p><?php esc_html_e('Your license will expire soon. Please renew to continue receiving updates and support.', 'woofraudguard'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    <?php elseif ($is_valid && $is_free_user) : ?>
        <div class="license-status free">
            <h2><?php esc_html_e('Free Version', 'woofraudguard'); ?></h2>
            <p><?php printf(__('You are using the free version with limited features. %d out of %d detections used.', 'woofraudguard'), $detection_count, $max_detections); ?></p>
            <?php if ($detection_count >= $max_detections) : ?>
                <div class="notice notice-error">
                    <p><?php esc_html_e('Your free version limit has been reached. Upgrade to continue using all features.', 'woofraudguard'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    <?php else : ?>
        <div class="license-status invalid">
            <h2><?php esc_html_e('License Status: Inactive', 'woofraudguard'); ?></h2>
            <p><?php esc_html_e('Your license is not active. Please enter a valid license key to unlock all features.', 'woofraudguard'); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="license-form-container">
        <h2><?php esc_html_e('Manage Your License', 'woofraudguard'); ?></h2>
        
        <?php if ($is_free_user || !$is_valid) : ?>
            <form id="activate-license-form" method="post">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="license_key"><?php esc_html_e('License Key', 'woofraudguard'); ?></label>
                        </th>
                        <td>
                            <input name="license_key" type="text" id="license_key" class="regular-text" value="" required>
                            <p class="description"><?php esc_html_e('Enter your license key to activate premium features.', 'woofraudguard'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary" id="activate-license"><?php esc_html_e('Activate License', 'woofraudguard'); ?></button>
                </p>
            </form>
        <?php endif; ?>
        
        <?php if (!$is_free_user) : ?>
            <form id="deactivate-license-form" method="post">
                <p class="submit">
                    <button type="submit" class="button" id="deactivate-license"><?php esc_html_e('Deactivate License', 'woofraudguard'); ?></button>
                </p>
            </form>
        <?php endif; ?>
        
        <div id="license-message" class="notice hidden"></div>
    </div>
    
    <div class="license-info">
        <h2><?php esc_html_e('Upgrade Options', 'woofraudguard'); ?></h2>
        <p><?php esc_html_e('Choose the plan that best fits your needs:', 'woofraudguard'); ?></p>
        
        <div class="pricing-table">
            <div class="pricing-plan free">
                <h3><?php esc_html_e('Free', 'woofraudguard'); ?></h3>
                <div class="price"><?php esc_html_e('Free', 'woofraudguard'); ?></div>
                <ul>
                    <li><?php esc_html_e('100 fraud detections', 'woofraudguard'); ?></li>
                    <li><?php esc_html_e('Basic reporting', 'woofraudguard'); ?></li>
                    <li><?php esc_html_e('Community support', 'woofraudguard'); ?></li>
                </ul>
            </div>
            
            <div class="pricing-plan mini">
                <h3><?php esc_html_e('Mini', 'woofraudguard'); ?></h3>
                <div class="price">$29 <span><?php esc_html_e('one-time', 'woofraudguard'); ?></span></div>
                <ul>
                    <li><?php esc_html_e('Unlimited fraud detections', 'woofraudguard'); ?></li>
                    <li><?php esc_html_e('Advanced reporting', 'woofraudguard'); ?></li>
                    <li><?php esc_html_e('3 months support', 'woofraudguard'); ?></li>
                    <li><?php esc_html_e('Basic updates', 'woofraudguard'); ?></li>
                </ul>
                <a href="https://yourdomain.com/woofraudguard/mini" class="button button-primary"><?php esc_html_e('Get Mini', 'woofraudguard'); ?></a>
            </div>
            
            <div class="pricing-plan pro">
                <h3><?php esc_html_e('Pro', 'woofraudguard'); ?></h3>
                <div class="price">$49 <span><?php esc_html_e('yearly subscription', 'woofraudguard'); ?></span></div>
                <ul>
                    <li><?php esc_html_e('Unlimited fraud detections', 'woofraudguard'); ?></li>
                    <li><?php esc_html_e('Advanced reporting & analytics', 'woofraudguard'); ?></li>
                    <li><?php esc_html_e('Priority support', 'woofraudguard'); ?></li>
                    <li><?php esc_html_e('All updates & new features', 'woofraudguard'); ?></li>
                    <li><?php esc_html_e('Custom rule configuration', 'woofraudguard'); ?></li>
                </ul>
                <a href="https://yourdomain.com/woofraudguard/pro" class="button button-primary"><?php esc_html_e('Get Pro', 'woofraudguard'); ?></a>
            </div>
        </div>
    </div>
</div>
