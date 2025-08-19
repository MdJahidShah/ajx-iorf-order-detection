<?php
/**
 * Incomplete Orders Page Template
 * Displays orders flagged as incomplete based on checkout abandonment
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

global $wpdb;

// Get incomplete orders from database
$table_name = $wpdb->prefix . 'fraud_orders';
$incomplete_orders = $wpdb->get_results(
    $wpdb->prepare("SELECT * FROM %s WHERE fraud_type LIKE %s", $table_name, '%incomplete%'),
    ARRAY_A
);

// Handle bulk actions
if (isset($_POST['action']) && in_array($_POST['action'], ['recover', 'delete']) && !empty($_POST['order_ids'])) {
    check_admin_referer('woofraudguard_bulk_actions');
    
    foreach ($_POST['order_ids'] as $order_id) {
        if ('recover' === $_POST['action']) {
            $order = wc_get_order($order_id);
            if ($order) {
                $order->update_status('processing', __('Recovered from incomplete orders list', 'woofraudguard'));
                $order->save();
            }
        } else if ('delete' === $_POST['action']) {
            wp_delete_post($order_id, true);
        }
    }
    
    // Redirect to avoid resubmission
    wp_redirect(admin_url('admin.php?page=woofraudguard-incomplete-orders'));
    exit;
}

?>
<div class="wrap">
    <h1><?php _e('Incomplete Orders', 'woofraudguard'); ?></h1>
    
    <?php if (!empty($incomplete_orders)) : ?>
        <form method="post">
            <div class="tablenav top">
                <div class="alignleft actions">
                    <select name="action" id="bulk-action-incomplete">
                        <option value="">- <?php _e('Bulk Actions', 'woofraudguard'); ?> -</option>
                        <option value="recover"><?php _e('Recover', 'woofraudguard'); ?></option>
                        <option value="delete"><?php _e('Delete', 'woofraudguard'); ?></option>
                    </select>
                    <input type="submit" class="button" value="<?php _e('Apply', 'woofraudguard'); ?>">
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="manage-column column-cb check-column">
                            <input type="checkbox">
                        </th>
                        <th><?php _e('Order ID', 'woofraudguard'); ?></th>
                        <th><?php _e('Fraud Type', 'woofraudguard'); ?></th>
                        <th><?php _e('Date', 'woofraudguard'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($incomplete_orders as $order) : ?>
                        <tr>
                            <th class="check-column">
                                <input type="checkbox" name="order_ids[]" value="<?php echo esc_attr($order['order_id']); ?>">
                            </th>
                            <td>
                                <a href="<?php echo esc_url(admin_url('post.php?post=' . $order['order_id'] . '&action=edit')); ?>">
                                    #<?php echo esc_html($order['order_id']); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html($order['fraud_type']); ?></td>
                            <td><?php echo esc_html($order['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
    <?php else : ?>
        <p><?php _e('No incomplete orders found.', 'woofraudguard'); ?></p>
    <?php endif; ?>
</div>
