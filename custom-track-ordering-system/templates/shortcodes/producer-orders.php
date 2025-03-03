<?php
/**
 * Template for displaying a producer's track orders
 */
defined('ABSPATH') || exit;

// Make sure we have orders
if (!isset($orders) || empty($orders)) {
    echo '<p>' . __('No track orders found.', 'custom-track-ordering-system') . '</p>';
    return;
}
?>

<div class="ctos-orders-wrapper">
    <h2><?php _e('My Track Orders', 'custom-track-ordering-system'); ?></h2>
    
    <table class="ctos-orders-table">
        <thead>
            <tr>
                <th><?php _e('Order ID', 'custom-track-ordering-system'); ?></th>
                <th><?php _e('Date', 'custom-track-ordering-system'); ?></th>
                <th><?php _e('Customer', 'custom-track-ordering-system'); ?></th>
                <th><?php _e('Track Title', 'custom-track-ordering-system'); ?></th>
                <th><?php _e('Status', 'custom-track-ordering-system'); ?></th>
                <th><?php _e('Actions', 'custom-track-ordering-system'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order) : 
                $customer = get_userdata($order->customer_id);
                $customer_name = $customer ? $customer->display_name : __('Unknown', 'custom-track-ordering-system');
                $status_label = CTOS_MarketKing_Integration::get_status_label($order->status);
                $status_class = CTOS_MarketKing_Integration::get_status_class($order->status);
                $date = date_i18n(get_option('date_format'), strtotime($order->created_at));
                $track_title = !empty($order->track_title) ? $order->track_title : __('Custom Track', 'custom-track-ordering-system');
            ?>
                <tr>
                    <td>#<?php echo esc_html($order->order_id); ?></td>
                    <td><?php echo esc_html($date); ?></td>
                    <td><?php echo esc_html($customer_name); ?></td>
                    <td><?php echo esc_html($track_title); ?></td>
                    <td><span class="<?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_label); ?></span></td>
                    <td>
                        <a href="<?php echo esc_url(add_query_arg('order_id', $order->order_id, get_permalink())); ?>" class="ctos-button ctos-button-small"><?php _e('View', 'custom-track-ordering-system'); ?></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
