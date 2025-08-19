<?php
/**
 * Fraud Detection System
 * Analyzes orders for potential fraud indicators
 */
class WooFraudGuard_Detector {
    public function __construct() {
        // Hook into WooCommerce order processing
        add_action('woocommerce_checkout_order_processed', array($this, 'process_order'), 10, 3);
        add_action('woocommerce_new_order', array($this, 'process_order'), 10, 2);
        
        // Register fraud detection hooks
        add_filter('woofraudguard_detect_fraud', array($this, 'detect_fake_phone'), 10, 2);
        add_filter('woofraudguard_detect_fraud', array($this, 'detect_fake_email'), 10, 2);
        add_filter('woofraudguard_detect_fraud', array($this, 'detect_same_ip_limit'), 10, 2);
        
        // Add checkout abandonment tracking
        add_action('wp_enqueue_scripts', array($this, 'enqueue_checkout_tracking'));
    }
    
    public function process_order($order_id, $posted_data = null, $order = null) {
        // Get order object
        if (!$order) {
            $order = wc_get_order($order_id);
        }
        
        // Skip if order is already processed
        if ($order->get_meta('_woofraudguard_processed')) {
            return;
        }
        
        // Initialize license verifier
        $license_verifier = new WooFraudGuard_License_Verifier();
        
        // Check if free user has reached detection limit
        if ($license_verifier->is_free_user() && $license_verifier->detection_count >= 100) {
            return;
        }
        
        // Increment detection count
        $license_verifier->increment_detection_count();
        
        // Analyze order for fraud
        $fraud_types = array();
        $detection_data = array();
        
        // Apply fraud detection filters
        $is_fraud = apply_filters('woofraudguard_detect_fraud', false, $order);
        
        if ($is_fraud) {
            // Log fraud detection
            $this->log_fraud($order_id, $fraud_types, $detection_data);
            
        // Mark order as processed
        $order->update_meta_data('_woofraudguard_processed', 'yes');
        $order->save();
            
            // Add admin note
            $order->add_order_note(__('Potential fraud detected by WooFraudGuard', 'woofraudguard'));
        }
    }
    
    public function detect_fake_phone($is_fraud, $order) {
        $phone = $order->get_billing_phone();
        
        // Check for common fake phone patterns
        if (empty($phone) || preg_match('/^(1234567890|0000000000|1111111111|9999999999|5555555555)$/', $phone)) {
            $is_fraud = true;
            $fraud_types[] = 'fake_phone';
            $detection_data['phone'] = $phone;
        }
        
        return $is_fraud;
    }
    
    public function detect_fake_email($is_fraud, $order) {
        $email = $order->get_billing_email();
        
        // Check for disposable email domains
        $disposable_domains = array(
            'mailinator.com', 'guerrillamail.com', 'tempmail.com', '10minutemail.com',
            'yopmail.com', 'throwawaymail.com', 'dispostable.com', 'maildrop.cc'
        );
        
        $domain = substr(strrchr($email, '@'), 1);
        
        if (in_array($domain, $disposable_domains) || preg_match('/^(admin|support|webmaster|info)@/', $email)) {
            $is_fraud = true;
            $fraud_types[] = 'fake_email';
            $detection_data['email'] = $email;
        }
        
        return $is_fraud;
    }
    
    public function detect_same_ip_limit($is_fraud, $order) {
        $ip_address = $order->get_customer_ip_address();
        $order_date = $order->get_date_created()->date('Y-m-d');
        
        // Get orders from same IP in last 24 hours using WC HPOS compatible method
        $orders = wc_get_orders(array(
            'customer_ip_address' => $ip_address,
            'date_created' => '>=' . (current_time('timestamp') - DAY_IN_SECONDS),
            'limit' => -1,
            'status' => 'any'
        ));
        
        // If more than 5 orders from same IP in 24 hours, flag as potential fraud
        if (count($orders) > 5) {
            $is_fraud = true;
            $fraud_types[] = 'same_ip_limit';
            $detection_data['ip_address'] = $ip_address;
            $detection_data['order_count'] = count($orders);
        }
        
        return $is_fraud;
    }
    
    private function log_fraud($order_id, $fraud_types, $detection_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fraud_orders';
        
        $wpdb->insert(
            $table_name,
            array(
                'order_id' => $order_id ?: null,
                'fraud_type' => implode(',', $fraud_types),
                'detection_data' => maybe_serialize($detection_data),
                'created_at' => current_time('mysql')
            ),
            array(
                '%d',
                '%s',
                '%s',
                '%s'
            )
        );
    }
    
    public function enqueue_checkout_tracking() {
        if (is_checkout()) {
            wp_enqueue_script(
                'checkout-tracking',
                WOO_FRAUD_GUARD_PLUGIN_URL . 'assets/js/checkout-tracking.js',
                array('jquery'),
                WOO_FRAUD_GUARD_VERSION,
                true
            );
            
            wp_localize_script('checkout-tracking', 'woofraudguard', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'is_checkout' => is_checkout()
            ));
        }
    }
    
    public function add_abandoned_checkout_tracking() {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $session_data = WC()->session->get('checkout_data', array());
            
            if (!empty($session_data)) {
                $fraud_types = array('incomplete');
                
                $detection_data = array(
                    'session_data' => $session_data,
                    'abandoned_at' => current_time('mysql')
                );
                
                global $wpdb;
                $table_name = $wpdb->prefix . 'fraud_orders';
                
                $wpdb->insert(
                    $table_name,
                    array(
                        'order_id' => null,
                        'fraud_type' => implode(',', $fraud_types),
                        'detection_data' => maybe_serialize($detection_data),
                        'created_at' => current_time('mysql')
                    ),
                    array(
                        '%d',
                        '%s',
                        '%s',
                        '%s'
                    )
                );
                
                // Clear session data
                WC()->session->set('checkout_data', array());
                
                wp_send_json_success();
            }
        }
        
        wp_send_json_error();
    }
}
