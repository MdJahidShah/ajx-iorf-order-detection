<?php
/**
 * Plugin Name: AJx WooFraudGuard
 * Plugin URI: https://jahidshah.com/woofraudguard/
 * Description: Smartly detect and manage fake or incomplete WooCommerce orders. Stay protected with live reporting, IP monitoring, and license-based updates.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://jahidshah.com
 * License: GPL-2.0+
 * Text Domain: woofraudguard
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

defined('ABSPATH') || exit;

// Declare compatibility with WooCommerce High-Performance Order Storage (HPOS)
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Plugin Constants
define('WOO_FRAUD_GUARD_VERSION', '1.0.0');
define('WOO_FRAUD_GUARD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WOO_FRAUD_GUARD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WOO_FRAUD_GUARD_LICENSE_KEY', get_option('woofraudguard_license_key', ''));

// Load plugin text domain
add_action('init', function() {
    load_plugin_textdomain('woofraudguard', false, dirname(plugin_basename(__FILE__)) . '/languages/');
});

// Include core classes
require_once WOO_FRAUD_GUARD_PLUGIN_DIR . 'includes/class-admin-menu.php';
require_once WOO_FRAUD_GUARD_PLUGIN_DIR . 'includes/class-detector.php';
require_once WOO_FRAUD_GUARD_PLUGIN_DIR . 'includes/class-reports.php';
require_once WOO_FRAUD_GUARD_PLUGIN_DIR . 'includes/class-license-verifier.php';
require_once WOO_FRAUD_GUARD_PLUGIN_DIR . 'includes/class-plugin-updater.php';

// Initialize core functionality
add_action('plugins_loaded', function() {
    new WooFraudGuard_Admin_Menu();
    new WooFraudGuard_Detector();
    new WooFraudGuard_Reports();
    
    // Initialize license system
    $license = new WooFraudGuard_License_Verifier();
    $license->init();
    
    // Initialize update system
    new WooFraudGuard_Plugin_Updater(
        'https://yourserver.com/woofraudguard/version.json',
        plugin_basename(__FILE__)
    );
});

// Activation hook
register_activation_hook(__FILE__, function() {
    // Schedule daily license check
    if (!wp_next_scheduled('woofraudguard_license_check_event')) {
        wp_schedule_event(time(), 'daily', 'woofraudguard_license_check_event');
    }
    
    // Create database tables if needed
    global $wpdb;
    $table_name = $wpdb->prefix . 'fraud_orders';
    $charset_collate = $wpdb->get_charset_collate();
    
    // Create or update fraud orders table
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        order_id bigint(20) DEFAULT NULL,
        fraud_type varchar(50) NOT NULL,
        detection_data longtext NOT NULL,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
    
    // Add abandoned checkout tracking
    add_action('wp_footer', array($this, 'add_abandoned_checkout_tracking'));
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('woofraudguard_license_check_event');
});
