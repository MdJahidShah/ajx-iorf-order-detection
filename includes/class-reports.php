<?php
/**
 * Reports System
 * Generates fraud detection reports and analytics
 */
class WooFraudGuard_Reports {
    public function __construct() {
        add_action('admin_init', array($this, 'handle_export'));
    }
    
    public function get_fraud_summary() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fraud_orders';
        
        // Get total fraud detections
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        // Get fraud by type
        $by_type = $wpdb->get_results(
            "SELECT fraud_type, COUNT(*) as count 
             FROM $table_name 
             GROUP BY fraud_type",
            ARRAY_A
        );
        
        // Get recent fraud detections
        $recent = $wpdb->get_results(
            "SELECT fo.*, o.post_title as order_title 
             FROM $table_name fo
             LEFT JOIN {$wpdb->posts} o ON fo.order_id = o.ID
             ORDER BY fo.created_at DESC 
             LIMIT 10",
            ARRAY_A
        );
        
        // Process by_type data
        $type_counts = array();
        foreach ($by_type as $row) {
            $types = explode(',', $row['fraud_type']);
            foreach ($types as $type) {
                $type = trim($type);
                if (!isset($type_counts[$type])) {
                    $type_counts[$type] = 0;
                }
                $type_counts[$type] += (int) $row['count'];
            }
        }
        
        return array(
            'total' => $total,
            'by_type' => $type_counts,
            'recent' => $recent
        );
    }
    
    public function get_fraud_trends($period = 'week') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fraud_orders';
        
        $group_by = '';
        switch ($period) {
            case 'day':
                $group_by = "DATE_FORMAT(created_at, '%Y-%m-%d')";
                break;
            case 'week':
                $group_by = "YEAR(created_at), WEEK(created_at)";
                break;
            case 'month':
                $group_by = "YEAR(created_at), MONTH(created_at)";
                break;
            default:
                $group_by = "DATE_FORMAT(created_at, '%Y-%m-%d')";
        }
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    $group_by AS period,
                    COUNT(*) AS count 
                 FROM $table_name 
                 WHERE created_at >= %s
                 GROUP BY period
                 ORDER BY period ASC",
                date('Y-m-d', strtotime('-30 days'))
            ),
            ARRAY_A
        );
        
        return $results;
    }
    
    public function get_fraud_details($order_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fraud_orders';
        
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE order_id = %d",
                $order_id
            ),
            ARRAY_A
        );
        
        if ($result) {
            $result['detection_data'] = maybe_unserialize($result['detection_data']);
        }
        
        return $result;
    }
    
    public function handle_export() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'woofraudguard-reports') {
            return;
        }
        
        if (isset($_GET['export']) && $_GET['export'] === 'csv' && 
            current_user_can('manage_options') && 
            check_admin_referer('woofraudguard_export')) {
            
            $this->export_to_csv();
            exit;
        }
    }
    
    private function export_to_csv() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fraud_orders';
        
        $results = $wpdb->get_results(
            "SELECT fo.*, o.post_title as order_title 
             FROM $table_name fo
             LEFT JOIN {$wpdb->posts} o ON fo.order_id = o.ID
             ORDER BY fo.created_at DESC",
            ARRAY_A
        );
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="woofraudguard-reports-' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Add CSV headers
        fputcsv($output, array(
            'Order ID',
            'Order Title',
            'Fraud Type',
            'Detection Data',
            'Date'
        ));
        
        // Add data rows
        foreach ($results as $row) {
            fputcsv($output, array(
                $row['order_id'],
                $row['order_title'],
                $row['fraud_type'],
                json_encode(maybe_unserialize($row['detection_data'])),
                $row['created_at']
            ));
        }
        
        fclose($output);
    }
}
