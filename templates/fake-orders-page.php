<?php
/**
 * Fake Orders Page Template
 * Displays orders flagged as fake based on phone/email validation
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

global $wpdb;

// Get fake orders from database
$table_name = $wpdb->prefix . 'fraud_orders';
$fake_orders = $wpdb->get_results(
    $wpdb->prepare("SELECT * FROM %s WHERE fraud_type LIKE %s OR fraud_type LIKE %s", $table_name, '%fake_phone%', '%fake_email%'),
    ARRAY_A
);

// Handle bulk actions
if (isset($_POST['action']) && in_array($_POST['action'], ['recover', 'delete']) && !empty($_POST['order_ids'])) {
    check_admin_referer('woofraudguard_bulk_actions');
    
    foreach ($_POST['order_ids'] as $order_id) {
        if ('recover' === $_POST['action']) {
            $order = wc_get_order($order_id);
            if ($order) {
                $order->update_status('processing', __('Recovered from fake orders list', 'woofraudguard'));
                $order->save();
            }
        } else if ('delete' === $_POST['action']) {
            wp_delete_post($order_id, true);
        }
    }
    
    // Redirect to avoid resubmission
    wp_redirect(admin_url('admin.php?page=woofraudguard-fake-orders'));
    exit;
}

?>
<div class="wrap">
    <h1><?php _e('Fake Orders', 'woofraudguard'); ?></h1>
    
    <?php if (!empty($fake_orders)) : ?>
        <form method="post">
            <div class="tablenav top">
                <div class="alignleft actions">
                    <select name="action" id="bulk-action-fake">
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
                    <?php foreach ($fake_orders as $order) : ?>
                        <tr>
                            <th class="check-column">
                                <input type="checkbox" name="order_ids[]" value="<?php echo esc_attr($order['order_id']); ?>">
                            </th>
                            <td>
                                <a href="<?php echo esc_url(admin_url('post.php?post=' . $order['order_id'] . '&action=edit')); ?>">
                                    #<?php echo esc_html($order['order_id']); ?>
                                </a>
                            </td>
                            <td>
                                <?php 
                                    $fraud_types = explode(',', $order['fraud_type']);
                                    foreach ($fraud_types as $type) {
                                        echo '<div>' . esc_html($type) . '</div>';
                                    }
                                ?>
                            </td>
                            <td><?php echo esc_html($order['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
    <?php else : ?>
        <p><?php _e('No fake orders found.', 'woofraudguard'); ?></p>
    <?php endif; ?>
</div>
