<?php
/**
 * Admin Menu System
 * Creates the backend menu structure for WooFraudGuard
 */
class WooFraudGuard_Admin_Menu {
    public function __construct() {
        add_action('admin_menu', array($this, 'create_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    public function create_menu() {
        // Main menu
        add_menu_page(
            __('WooFraudGuard Dashboard', 'woofraudguard'),
            'WooFraudGuard',
            'manage_options',
            'woofraudguard-reports',
            array($this, 'reports_page'),
            'dashicons-shield',
            55
        );
        
        // Submenus
        add_submenu_page(
            'woofraudguard-reports',
            __('Reports', 'woofraudguard'),
            __('Reports', 'woofraudguard'),
            'manage_options',
            'woofraudguard-reports',
            array($this, 'reports_page')
        );
        
        add_submenu_page(
            'woofraudguard-reports',
            __('Fake Orders', 'woofraudguard'),
            __('Fake Orders', 'woofraudguard'),
            'manage_options',
            'woofraudguard-fake-orders',
            array($this, 'fake_orders_page')
        );
        
        add_submenu_page(
            'woofraudguard-reports',
            __('Incomplete Orders', 'woofraudguard'),
            __('Incomplete Orders', 'woofraudguard'),
            'manage_options',
            'woofraudguard-incomplete-orders',
            array($this, 'incomplete_orders_page')
        );
        
        add_submenu_page(
            'woofraudguard-reports',
            __('Settings', 'woofraudguard'),
            __('Settings', 'woofraudguard'),
            'manage_options',
            'woofraudguard-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'woofraudguard-reports',
            __('License', 'woofraudguard'),
            __('License', 'woofraudguard'),
            'manage_options',
            'woofraudguard-license',
            array($this, 'license_page')
        );
    }
    
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'woofraudguard') === false) {
            return;
        }
        
        // Enqueue styles
        wp_enqueue_style(
            'woofraudguard-admin-styles',
            WOO_FRAUD_GUARD_PLUGIN_URL . 'assets/css/admin-styles.css',
            array(),
            WOO_FRAUD_GUARD_VERSION
        );
        
        // Enqueue scripts
        wp_enqueue_script(
            'woofraudguard-admin-scripts',
            WOO_FRAUD_GUARD_PLUGIN_URL . 'assets/js/admin-scripts.js',
            array('jquery'),
            WOO_FRAUD_GUARD_VERSION,
            true
        );
        
        // Localize script with AJAX URL and nonce
        wp_localize_script('woofraudguard-admin-scripts', 'woofraudguard_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('woofraudguard_nonce')
        ));
    }
    
    public function reports_page() {
        require_once WOO_FRAUD_GUARD_PLUGIN_DIR . 'templates/reports-page.php';
    }
    
    public function settings_page() {
        require_once WOO_FRAUD_GUARD_PLUGIN_DIR . 'templates/settings-page.php';
    }
    
    public function license_page() {
        require_once WOO_FRAUD_GUARD_PLUGIN_DIR . 'templates/license-page.php';
    }
    
    public function fake_orders_page() {
        require_once WOO_FRAUD_GUARD_PLUGIN_DIR . 'templates/fake-orders-page.php';
    }
    
    public function incomplete_orders_page() {
        require_once WOO_FRAUD_GUARD_PLUGIN_DIR . 'templates/incomplete-orders-page.php';
    }
}
