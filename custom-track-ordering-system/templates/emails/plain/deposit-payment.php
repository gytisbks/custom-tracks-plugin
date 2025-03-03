<?php
/**
 * Deposit Payment Email Template (Plain Text)
 */

if (!defined('ABSPATH')) {
    exit;
}

echo "= " . $email_heading . " =\n\n";

echo __('A new custom track order has been placed. The customer has made the initial deposit payment.', 'custom-track-ordering-system') . "\n\n";

echo "= " . __('Order Details', 'custom-track-ordering-system') . " =\n\n";

echo __('Order Number:', 'custom-track-ordering-system') . ' ' . $order->get_order_number() . "\n";
echo __('Date:', 'custom-track-ordering-system') . ' ' . wc_format_datetime($order->get_date_created()) . "\n\n";

echo "= " . __('Customer Information', 'custom-track-ordering-system') . " =\n\n";

echo __('Name:', 'custom-track-ordering-system') . ' ' . $order->get_formatted_billing_full_name() . "\n";
echo __('Email:', 'custom-track-ordering-system') . ' ' . $order->get_billing_email() . "\n\n";

echo "= " . __('Track Details', 'custom-track-ordering-system') . " =\n\n";

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

echo __('Service Type:', 'custom-track-ordering-system') . ' ' . ucfirst(str_replace('_', ' ', $service_type)) . "\n";
if (!empty($genres)) {
    echo __('Genres:', 'custom-track-ordering-system') . ' ' . $genres . "\n";
}
if (!empty($reference_tracks)) {
    echo __('Reference Tracks:', 'custom-track-ordering-system') . ' ' . $reference_tracks . "\n";
}
if (!empty($notes)) {
    echo __('Customer Notes:', 'custom-track-ordering-system') . ' ' . $notes . "\n";
}
echo __('Total Price:', 'custom-track-ordering-system') . ' ' . strip_tags(wc_price($total_price)) . "\n";
echo __('Deposit Paid:', 'custom-track-ordering-system') . ' ' . strip_tags(wc_price($deposit_amount)) . ' (30%)' . "\n";
echo __('Remaining Balance:', 'custom-track-ordering-system') . ' ' . strip_tags(wc_price($total_price - $deposit_amount)) . ' (70%)' . "\n\n";

echo __('Please begin working on the demo track for this order. You\'ll need to deliver a demo for the customer to review before receiving the final payment.', 'custom-track-ordering-system') . "\n\n";

echo __('You can log in to your account to view the complete order details and upload the demo track.', 'custom-track-ordering-system') . "\n\n";

echo apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text'));
