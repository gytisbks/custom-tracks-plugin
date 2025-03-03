<?php
/**
 * Template for displaying track orders in the vendor dashboard.
 */
defined('ABSPATH') || exit;

$producer_id = get_current_user_id();
$orders = CTOS_MarketKing_Integration::get_producer_orders($producer_id);
?>

<div class="ctos-orders-container">
    <h2><?php _e('Your Custom Track Orders', 'custom-track-ordering-system'); ?></h2>
    
    <?php if (empty($orders)) : ?>
        <p><?php _e('You have no custom track orders yet.', 'custom-track-ordering-system'); ?></p>
    <?php else : ?>
        <table class="ctos-orders-table">
            <thead>
                <tr>
                    <th><?php _e('Order', 'custom-track-ordering-system'); ?></th>
                    <th><?php _e('Customer', 'custom-track-ordering-system'); ?></th>
                    <th><?php _e('Service', 'custom-track-ordering-system'); ?></th>
                    <th><?php _e('Status', 'custom-track-ordering-system'); ?></th>
                    <th><?php _e('Date', 'custom-track-ordering-system'); ?></th>
                    <th><?php _e('Actions', 'custom-track-ordering-system'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order) : 
                    $customer = get_user_by('id', $order->customer_id);
                    $customer_name = $customer ? $customer->display_name : __('Unknown Customer', 'custom-track-ordering-system');
                    $status_label = CTOS_MarketKing_Integration::get_status_label($order->status);
                    $status_class = CTOS_MarketKing_Integration::get_status_class($order->status);
                    
                    // Get WooCommerce order
                    $wc_order = wc_get_order($order->order_id);
                    ?>
                    <tr>
                        <td>#<?php echo $order->order_id; ?></td>
                        <td><?php echo esc_html($customer_name); ?></td>
                        <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $order->service_type))); ?></td>
                        <td><span class="ctos-order-status <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_label); ?></span></td>
                        <td><?php echo date_i18n(get_option('date_format'), strtotime($order->created_at)); ?></td>
                        <td>
                            <a href="<?php echo esc_url(add_query_arg('order_id', $order->order_id, wc_get_account_endpoint_url('orders'))); ?>" class="ctos-button ctos-button-secondary"><?php _e('View', 'custom-track-ordering-system'); ?></a>
                            
                            <?php if ($order->status === 'pending_demo_submission' && $order->deposit_paid) : ?>
                                <button type="button" class="ctos-button" onclick="document.getElementById('ctos-demo-upload-<?php echo $order->order_id; ?>').click();"><?php _e('Upload Demo', 'custom-track-ordering-system'); ?></button>
                                <input type="file" id="ctos-demo-upload-<?php echo $order->order_id; ?>" class="ctos-demo-upload" data-order-id="<?php echo $order->order_id; ?>" accept=".mp3" style="display: none;">
                            <?php endif; ?>
                            
                            <?php if ($order->status === 'awaiting_final_delivery' && $order->final_paid) : ?>
                                <button type="button" class="ctos-button" onclick="document.getElementById('ctos-final-files-upload-<?php echo $order->order_id; ?>').click();"><?php _e('Upload Final Files', 'custom-track-ordering-system'); ?></button>
                                <input type="file" id="ctos-final-files-upload-<?php echo $order->order_id; ?>" class="ctos-final-files-upload" data-order-id="<?php echo $order->order_id; ?>" multiple accept=".mp3,.wav,.zip" style="display: none;">
                            <?php endif; ?>
                            
                            <?php
                            // Show link to conversation if MarketKing Messages is active
                            $thread_id = get_post_meta($order->order_id, '_ctos_message_thread_id', true);
                            if ($thread_id && function_exists('marketking_get_message_url')) {
                                $message_url = marketking_get_message_url($thread_id);
                                ?>
                                <a href="<?php echo esc_url($message_url); ?>" class="ctos-button ctos-button-secondary"><?php _e('Messages', 'custom-track-ordering-system'); ?></a>
                                <?php
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
