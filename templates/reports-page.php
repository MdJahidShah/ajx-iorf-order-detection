<?php
/**
 * Reports Page
 * Displays fraud detection analytics and reports
 */
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'woofraudguard'));
}

// Get report data
$reports = new WooFraudGuard_Reports();
$summary = $reports->get_fraud_summary();
$trends = $reports->get_fraud_trends('week');

// Handle order actions
if (isset($_POST['woofraudguard_action_nonce']) && wp_verify_nonce($_POST['woofraudguard_action_nonce'], 'woofraudguard_order_action')) {
    if (isset($_POST['action']) && isset($_POST['order_id'])) {
        $order_id = intval($_POST['order_id']);
        
        switch ($_POST['action']) {
            case 'recover':
                // Mark order as not fraud (remove processed flag)
                delete_post_meta($order_id, '_woofraudguard_processed');
                $message = __('Order recovered successfully.', 'woofraudguard');
                break;
                
            case 'delete':
                // Delete the order
                wp_delete_post($order_id, true);
                $message = __('Order deleted successfully.', 'woofraudguard');
                break;
                
            default:
                $message = __('Invalid action.', 'woofraudguard');
        }
        
        if (!empty($message)) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
        }
    }
}
?>

<div class="wrap woofraudguard-reports-page">
    <h1><?php esc_html_e('WooFraudGuard Reports', 'woofraudguard'); ?></h1>
    
    <!-- Summary Cards -->
    <div class="report-summary">
        <div class="summary-card total">
            <h3><?php esc_html_e('Total Detections', 'woofraudguard'); ?></h3>
            <div class="value"><?php echo esc_html($summary['total']); ?></div>
        </div>
        
        <div class="summary-card phone">
            <h3><?php esc_html_e('Fake Phone', 'woofraudguard'); ?></h3>
            <div class="value"><?php echo esc_html(isset($summary['by_type']['fake_phone']) ? $summary['by_type']['fake_phone'] : 0); ?></div>
        </div>
        
        <div class="summary-card email">
            <h3><?php esc_html_e('Fake Email', 'woofraudguard'); ?></h3>
            <div class="value"><?php echo esc_html(isset($summary['by_type']['fake_email']) ? $summary['by_type']['fake_email'] : 0); ?></div>
        </div>
        
        <div class="summary-card ip">
            <h3><?php esc_html_e('Same IP Limit', 'woofraudguard'); ?></h3>
            <div class="value"><?php echo esc_html(isset($summary['by_type']['same_ip_limit']) ? $summary['by_type']['same_ip_limit'] : 0); ?></div>
        </div>
    </div>
    
    <!-- Charts -->
    <div class="report-charts">
        <div class="chart-container">
            <h2><?php esc_html_e('Fraud Trends (Last 30 Days)', 'woofraudguard'); ?></h2>
            <canvas id="fraudTrendsChart" width="400" height="150"></canvas>
        </div>
    </div>
    
    <!-- Recent Fraud Detections -->
    <div class="report-table">
        <div class="table-header">
            <h2><?php esc_html_e('Recent Fraud Detections', 'woofraudguard'); ?></h2>
            <a href="<?php echo esc_url(add_query_arg(array('export' => 'csv', '_wpnonce' => wp_create_nonce('woofraudguard_export')), admin_url('admin.php?page=woofraudguard-reports'))); ?>" 
               class="button button-secondary">
                <?php esc_html_e('Export to CSV', 'woofraudguard'); ?>
            </a>
        </div>
        
        <?php if (!empty($summary['recent'])) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Order', 'woofraudguard'); ?></th>
                        <th><?php esc_html_e('Date', 'woofraudguard'); ?></th>
                        <th><?php esc_html_e('Fraud Type', 'woofraudguard'); ?></th>
                        <th><?php esc_html_e('Details', 'woofraudguard'); ?></th>
                        <th><?php esc_html_e('Actions', 'woofraudguard'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($summary['recent'] as $fraud) : 
                        $order = wc_get_order($fraud['order_id']);
                        $detection_data = maybe_unserialize($fraud['detection_data']);
                    ?>
                    <tr>
                        <td>
                            <?php if ($order) : ?>
                                <a href="<?php echo esc_url(admin_url('post.php?post=' . $order->get_id() . '&action=edit')); ?>">
                                    #<?php echo esc_html($order->get_order_number()); ?>
                                </a>
                            <?php else : ?>
                                #<?php echo esc_html($fraud['order_id']); ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($fraud['created_at']))); ?></td>
                        <td>
                            <?php 
                            $types = explode(',', $fraud['fraud_type']);
                            foreach ($types as $type) {
                                echo '<span class="fraud-type ' . esc_attr($type) . '">' . 
                                     esc_html(ucwords(str_replace('_', ' ', $type))) . 
                                     '</span> ';
                            }
                            ?>
                        </td>
                        <td>
                            <ul class="fraud-details">
                                <?php if (!empty($detection_data['phone'])) : ?>
                                    <li><strong><?php esc_html_e('Phone:', 'woofraudguard'); ?></strong> <?php echo esc_html($detection_data['phone']); ?></li>
                                <?php endif; ?>
                                
                                <?php if (!empty($detection_data['email'])) : ?>
                                    <li><strong><?php esc_html_e('Email:', 'woofraudguard'); ?></strong> <?php echo esc_html($detection_data['email']); ?></li>
                                <?php endif; ?>
                                
                                <?php if (!empty($detection_data['ip_address'])) : ?>
                                    <li><strong><?php esc_html_e('IP Address:', 'woofraudguard'); ?></strong> <?php echo esc_html($detection_data['ip_address']); ?></li>
                                    <li><strong><?php esc_html_e('Order Count:', 'woofraudguard'); ?></strong> <?php echo esc_html($detection_data['order_count']); ?></li>
                                <?php endif; ?>
                            </ul>
                        </td>
                        <td>
                            <form method="post" style="display: inline-block;">
                                <?php wp_nonce_field('woofraudguard_order_action', 'woofraudguard_action_nonce'); ?>
                                <input type="hidden" name="order_id" value="<?php echo esc_attr($fraud['order_id']); ?>">
                                
                                <button type="submit" name="action" value="recover" class="button button-small">
                                    <?php esc_html_e('Recover', 'woofraudguard'); ?>
                                </button>
                                
                                <button type="submit" name="action" value="delete" class="button button-small button-delete" 
                                        onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this order?', 'woofraudguard'); ?>')">
                                    <?php esc_html_e('Delete', 'woofraudguard'); ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="no-data">
                <?php esc_html_e('No fraud detections found.', 'woofraudguard'); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prepare data for Chart.js
    var chartData = {
        labels: [],
        datasets: [{
            label: '<?php esc_attr_e('Fraud Detections', 'woofraudguard'); ?>',
            data: [],
            backgroundColor: 'rgba(255, 99, 132, 0.5)',
            borderColor: 'rgba(255, 99, 132, 1)',
            borderWidth: 1
        }]
    };
    
    // Process trend data
    <?php foreach ($trends as $trend) : ?>
        chartData.labels.push('<?php echo esc_js(date_i18n('M j', strtotime($trend['period']))); ?>');
        chartData.datasets[0].data.push(<?php echo intval($trend['count']); ?>);
    <?php endforeach; ?>
    
    // Create chart
    var ctx = document.getElementById('fraudTrendsChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    precision: 0
                }
            }
        }
    });
    
    // Toggle IP threshold field based on checkbox
    var ipCheck = document.querySelector('input[name="enable_ip_check"]');
    var ipThreshold = document.querySelector('.ip-threshold');
    
    if (ipCheck && ipThreshold) {
        ipCheck.addEventListener('change', function() {
            ipThreshold.style.display = this.checked ? '' : 'none';
        });
    }
    
    // Toggle delete settings based on checkbox
    var autoDelete = document.querySelector('input[name="auto_delete"]');
    var deleteSettings = document.querySelector('.delete-settings');
    
    if (autoDelete && deleteSettings) {
        autoDelete.addEventListener('change', function() {
            deleteSettings.style.display = this.checked ? '' : 'none';
        });
    }
});
</script>
