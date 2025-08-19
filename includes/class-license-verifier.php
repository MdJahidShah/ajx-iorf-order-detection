<?php
/**
 * License Verification System
 * Handles communication with self-hosted license server
 */
class WooFraudGuard_License_Verifier {
    private $api_url = 'https://jahidshah.com/woofraudguard/license-api.php';
    private $license_key;
    private $valid_until;
    private $detection_count;
    private $max_detections = 100;
    
    public function __construct() {
        $this->license_key = get_option('woofraudguard_license_key', '');
        $this->valid_until = get_option('woofraudguard_license_valid_until', '');
        $this->detection_count = (int) get_option('woofraudguard_detection_count', 0);
    }
    
    public function init() {
        // Register cron event for license validation
        add_action('woofraudguard_license_check_event', array($this, 'validate_license'));
        
        // Register AJAX handlers
        add_action('wp_ajax_woofraudguard_activate_license', array($this, 'ajax_activate_license'));
        add_action('wp_ajax_woofraudguard_deactivate_license', array($this, 'ajax_deactivate_license'));
        
        // Check license status on admin pages
        add_action('admin_init', array($this, 'check_license_status'));
    }
    
    public function check_license_status() {
        // Skip checks on license page itself
        if (isset($_GET['page']) && $_GET['page'] === 'woofraudguard-license') {
            return;
        }
        
        // Check if license is valid
        if (!$this->is_valid()) {
            add_action('admin_notices', array($this, 'license_invalid_notice'));
        }
        
        // Check detection limit for free users
        if ($this->is_free_user() && $this->detection_count >= $this->max_detections) {
            add_action('admin_notices', array($this, 'detection_limit_notice'));
        }
    }
    
    public function is_valid() {
        // Free users with detection count under limit are valid
        if ($this->is_free_user() && $this->detection_count < $this->max_detections) {
            return true;
        }
        
        // Check if license key exists and is not expired
        return !empty($this->license_key) && 
               (empty($this->valid_until) || strtotime($this->valid_until) > time());
    }
    
    public function is_free_user() {
        return empty($this->license_key);
    }
    
    public function validate_license() {
        if (empty($this->license_key)) {
            return false;
        }
        
        $response = wp_remote_post($this->api_url, array(
            'timeout' => 15,
            'body' => array(
                'action' => 'validate',
                'license' => $this->license_key,
                'domain' => home_url(),
                'version' => WOO_FRAUD_GUARD_VERSION
            )
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!empty($body['success'])) {
            update_option('woofraudguard_license_valid_until', $body['valid_until']);
            return true;
        }
        
        // License invalid - clear stored data
        if ($body['error'] === 'invalid' || $body['error'] === 'expired') {
            $this->clear_license_data();
        }
        
        return false;
    }
    
    public function ajax_activate_license() {
        check_ajax_referer('woofraudguard_nonce', 'security');
        
        $license = sanitize_text_field($_POST['license_key']);
        
        $response = wp_remote_post($this->api_url, array(
            'timeout' => 15,
            'body' => array(
                'action' => 'activate',
                'license' => $license,
                'domain' => home_url(),
                'version' => WOO_FRAUD_GUARD_VERSION
            )
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => __('Failed to connect to license server', 'woofraudguard')));
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!empty($body['success'])) {
            update_option('woofraudguard_license_key', $license);
            update_option('woofraudguard_license_valid_until', $body['valid_until']);
            update_option('woofraudguard_detection_count', 0);
            
            wp_send_json_success(array(
                'message' => __('License activated successfully', 'woofraudguard'),
                'status' => 'valid'
            ));
        }
        
        wp_send_json_error(array(
            'message' => isset($body['message']) ? $body['message'] : __('Invalid license key', 'woofraudguard')
        ));
    }
    
    public function ajax_deactivate_license() {
        check_ajax_referer('woofraudguard_nonce', 'security');
        
        if (empty($this->license_key)) {
            wp_send_json_error(array('message' => __('No license to deactivate', 'woofraudguard')));
        }
        
        $response = wp_remote_post($this->api_url, array(
            'timeout' => 15,
            'body' => array(
                'action' => 'deactivate',
                'license' => $this->license_key,
                'domain' => home_url()
            )
        ));
        
        // Regardless of server response, clear local license data
        $this->clear_license_data();
        
        wp_send_json_success(array(
            'message' => __('License deactivated successfully', 'woofraudguard'),
            'status' => 'invalid'
        ));
    }
    
    private function clear_license_data() {
        delete_option('woofraudguard_license_key');
        delete_option('woofraudguard_license_valid_until');
        delete_option('woofraudguard_detection_count');
        $this->license_key = '';
    }
    
    public function increment_detection_count() {
        if ($this->is_free_user()) {
            $count = (int) get_option('woofraudguard_detection_count', 0);
            update_option('woofraudguard_detection_count', $count + 1);
            $this->detection_count = $count + 1;
        }
    }
    
    public function license_invalid_notice() {
        $message = __('WooFraudGuard license is invalid or expired. Some features may be limited.', 'woofraudguard');
        $message .= ' <a href="' . admin_url('admin.php?page=woofraudguard-license') . '">' . 
                   __('Manage License', 'woofraudguard') . '</a>';
        
        echo '<div class="notice notice-error"><p>' . $message . '</p></div>';
    }
    
    public function detection_limit_notice() {
        $message = sprintf(
            __('Free version limit reached (100 detections). %sUpgrade to continue using all features.%s', 'woofraudguard'),
            '<a href="' . admin_url('admin.php?page=woofraudguard-license') . '">',
            '</a>'
        );
        
        echo '<div class="notice notice-warning"><p>' . $message . '</p></div>';
    }
}
