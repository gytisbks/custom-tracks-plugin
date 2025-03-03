<?php
/**
 * Deposit Payment Email Template
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action('woocommerce_email_header', $email_heading, $email);
?>

<p><?php _e('A new custom track order has been placed. The customer has made the initial deposit payment.', 'custom-track-ordering-system'); ?></p>

<h2><?php _e('Order Details', 'custom-track-ordering-system'); ?></h2>

<p>
    <strong><?php _e('Order Number:', 'custom-track-ordering-system'); ?></strong> <?php echo $order->get_order_number(); ?><br>
    <strong><?php _e('Date:', 'custom-track-ordering-system'); ?></strong> <?php echo wc_format_datetime($order->get_date_created()); ?>
</p>

<h3><?php _e('Customer Information', 'custom-track-ordering-system'); ?></h3>

<p>
    <strong><?php _e('Name:', 'custom-track-ordering-system'); ?></strong> <?php echo $order->get_formatted_billing_full_name(); ?><br>
    <strong><?php _e('Email:', 'custom-track-ordering-system'); ?></strong> <?php echo $order->get_billing_email(); ?>
</p>

<h3><?php _e('Track Details', 'custom-track-ordering-system'); ?></h3>

<?php
// Display custom track details
foreach ($order->get_items() as $item) {
    if ($item->get_meta('_ctos_producer_id')) {
        $service_type = $item->get_meta('_ctos_service_type');
        $genres = $item->get_meta('_ctos_genres');
        $reference_tracks = $item->get_meta('_ctos_reference_tracks');
        $notes = $item->get_meta('_ctos_notes');
        $total_price = $item->get_meta('_ctos_total_price');
        $deposit_amount = $item->get_meta('_ctos_deposit_amount');
    }
}
?>

<p>
    <strong><?php _e('Service Type:', 'custom-track-ordering-system'); ?></strong> <?php echo ucfirst(str_replace('_', ' ', $service_type)); ?><br>
    <?php if (!empty($genres)): ?>
        <strong><?php _e('Genres:', 'custom-track-ordering-system'); ?></strong> <?php echo $genres; ?><br>
    <?php endif; ?>
    <?php if (!empty($reference_tracks)): ?>
        <strong><?php _e('Reference Tracks:', 'custom-track-ordering-system'); ?></strong> <?php echo $reference_tracks; ?><br>
    <?php endif; ?>
    <?php if (!empty($notes)): ?>
        <strong><?php _e('Customer Notes:', 'custom-track-ordering-system'); ?></strong> <?php echo $notes; ?><br>
    <?php endif; ?>
    <strong><?php _e('Total Price:', 'custom-track-ordering-system'); ?></strong> <?php echo wc_price($total_price); ?><br>
    <strong><?php _e('Deposit Paid:', 'custom-track-ordering-system'); ?></strong> <?php echo wc_price($deposit_amount); ?> (30%)<br>
    <strong><?php _e('Remaining Balance:', 'custom-track-ordering-system'); ?></strong> <?php echo wc_price($total_price - $deposit_amount); ?> (70%)
</p>

<p><?php _e('Please begin working on the demo track for this order. You\'ll need to deliver a demo for the customer to review before receiving the final payment.', 'custom-track-ordering-system'); ?></p>

<p><?php _e('You can log in to your account to view the complete order details and upload the demo track.', 'custom-track-ordering-system'); ?></p>

<?php
/**
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action('woocommerce_email_footer', $email);
?>
