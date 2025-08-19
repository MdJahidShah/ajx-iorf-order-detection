<?php
/**
 * Plugin Updater System
 * Handles WordPress update checks and plugin information
 */
class WooFraudGuard_Plugin_Updater {
    private $api_url;
    private $plugin_file;
    
    public function __construct($api_url, $plugin_file) {
        $this->api_url = $api_url;
        $this->plugin_file = $plugin_file;
        
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_updates'));
        add_filter('plugins_api', array($this, 'plugin_info'), 20, 3);
    }
    
    public function check_updates($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        $response = wp_remote_get($this->api_url, array(
            'timeout' => 15,
            'body' => array(
                'license' => WOO_FRAUD_GUARD_LICENSE_KEY,
                'domain' => home_url(),
                'version' => WOO_FRAUD_GUARD_VERSION
            )
        ));
        
        if (is_wp_error($response)) {
            return $transient;
        }
        
        $update_info = json_decode(wp_remote_retrieve_body($response));
        
        if (!empty($update_info) && version_compare(WOO_FRAUD_GUARD_VERSION, $update_info->version, '<')) {
            $transient->response[$this->plugin_file] = (object) array(
                'id' => $this->plugin_file,
                'slug' => 'woofraudguard',
                'new_version' => $update_info->version,
                'url' => 'https://yourdomain.com/woofraudguard/',
                'package' => $update_info->download_url . (WOO_FRAUD_GUARD_LICENSE_KEY ? '?license=' . WOO_FRAUD_GUARD_LICENSE_KEY : '')
            );
        }
        
        return $transient;
    }
    
    public function plugin_info($res, $action, $args) {
        if ('plugin_information' !== $action) {
            return $res;
        }
        
        if ('woofraudguard' !== $args->slug) {
            return $res;
        }
        
        $response = wp_remote_get($this->api_url);
        if (is_wp_error($response)) {
            return $res;
        }
        
        $info = json_decode(wp_remote_retrieve_body($response));
        
        if (empty($info)) {
            return $res;
        }
        
        return (object) array(
            'name' => 'WooFraudGuard',
            'slug' => 'woofraudguard',
            'version' => $info->version,
            'author' => '<a href="https://yourdomain.com">Your Name</a>',
            'author_profile' => 'https://yourdomain.com',
            'last_updated' => date('Y-m-d H:i:s'),
            'requires' => '5.6',
            'tested' => '8.0',
            'requires_php' => '7.4',
            'sections' => array(
                'description' => 'Smartly detect and manage fake or incomplete WooCommerce orders.',
                'changelog' => !empty($info->changelog) ? $info->changelog : 'No changelog available.'
            ),
            'download_link' => $info->download_url . (WOO_FRAUD_GUARD_LICENSE_KEY ? '?license=' . WOO_FRAUD_GUARD_LICENSE_KEY : '')
        );
    }
}
