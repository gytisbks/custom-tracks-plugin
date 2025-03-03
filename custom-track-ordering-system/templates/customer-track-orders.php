<?php
/**
 * Template for displaying track orders in the customer dashboard.
 */
defined('ABSPATH') || exit;

$customer_id = get_current_user_id();
$orders = CTOS_MarketKing_Integration::get_customer_orders($customer_id);
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
                    <th><?php _e('Producer', 'custom-track-ordering-system'); ?></th>
                    <th><?php _e('Service', 'custom-track-ordering-system'); ?></th>
                    <th><?php _e('Status', 'custom-track-ordering-system'); ?></th>
                    <th><?php _e('Date', 'custom-track-ordering-system'); ?></th>
                    <th><?php _e('Actions', 'custom-track-ordering-system'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order) : 
                    $producer = get_user_by('id', $order->producer_id);
                    $producer_name = $producer ? $producer->display_name : __('Unknown Producer', 'custom-track-ordering-system');
                    $status_label = CTOS_MarketKing_Integration::get_status_label($order->status);
                    $status_class = CTOS_MarketKing_Integration::get_status_class($order->status);
                    
                    // Get WooCommerce order
                    $wc_order = wc_get_order($order->order_id);
                    ?>
                    <tr>
                        <td>#<?php echo $order->order_id; ?></td>
                        <td><?php echo esc_html($producer_name); ?></td>
                        <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $order->service_type))); ?></td>
                        <td><span class="ctos-order-status <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_label); ?></span></td>
                        <td><?php echo date_i18n(get_option('date_format'), strtotime($order->created_at)); ?></td>
                        <td>
                            <a href="<?php echo esc_url(add_query_arg('order_id', $order->order_id, wc_get_account_endpoint_url('orders'))); ?>" class="ctos-button ctos-button-secondary"><?php _e('View', 'custom-track-ordering-system'); ?></a>
                            
                            <?php if ($order->status === 'awaiting_customer_approval') : ?>
                                <a href="#" class="ctos-button ctos-approve-demo" data-order-id="<?php echo $order->order_id; ?>"><?php _e('Approve Demo', 'custom-track-ordering-system'); ?></a>
                                <a href="#" class="ctos-button ctos-button-secondary ctos-request-revision" data-order-id="<?php echo $order->order_id; ?>"><?php _e('Request Revision', 'custom-track-ordering-system'); ?></a>
                            <?php endif; ?>
                            
                            <?php if ($order->status === 'awaiting_final_payment' && $wc_order) : 
                                // Find final payment order
                                $args = array(
                                    'meta_key' => '_ctos_original_order_id',
                                    'meta_value' => $order->order_id,
                                    'post_type' => 'shop_order',
                                    'post_status' => 'any',
                                    'posts_per_page' => 1,
                                );
                                
                                $orders_query = new WP_Query($args);
                                if ($orders_query->have_posts()) {
                                    $final_order_id = $orders_query->posts[0]->ID;
                                    $final_order = wc_get_order($final_order_id);
                                    
                                    if ($final_order && $final_order->needs_payment()) {
                                        ?>
                                        <a href="<?php echo esc_url($final_order->get_checkout_payment_url()); ?>" class="ctos-button"><?php _e('Pay Balance', 'custom-track-ordering-system'); ?></a>
                                        <?php
                                    }
                                }
                            endif; ?>
                            
                            <?php if ($order->status === 'completed') : ?>
                                <a href="<?php echo esc_url(add_query_arg(array('order_id' => $order->order_id), get_permalink(get_option('woocommerce_myaccount_page_id')) . 'my-track-orders/download/')); ?>" class="ctos-button"><?php _e('Download Files', 'custom-track-ordering-system'); ?></a>
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
        
        <div id="ctos-revision-form-container" style="display: none; margin-top: 30px;">
            <h3><?php _e('Request Revision', 'custom-track-ordering-system'); ?></h3>
            <form id="ctos-revision-form" class="ctos-form">
                <div class="ctos-form-row">
                    <label for="ctos-revision-notes" class="ctos-form-label"><?php _e('Revision Notes', 'custom-track-ordering-system'); ?></label>
                    <textarea id="ctos-revision-notes" class="ctos-textarea" required></textarea>
                    <p class="ctos-form-help"><?php _e('Describe in detail what changes you would like the producer to make.', 'custom-track-ordering-system'); ?></p>
                </div>
                <div class="ctos-form-row">
                    <button type="submit" class="ctos-button"><?php _e('Submit Revision Request', 'custom-track-ordering-system'); ?></button>
                    <button type="button" class="ctos-button ctos-button-secondary" onclick="document.getElementById('ctos-revision-form-container').style.display='none';"><?php _e('Cancel', 'custom-track-ordering-system'); ?></button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>
