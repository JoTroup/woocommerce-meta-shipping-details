<?php
/*
 * Plugin Name:       WooCommerce Auto Calculate Total
 * Plugin URI:        
 * Description:       Automatically recalculates taxes and totals when saving admin-created orders.
 * Version:           1.0.2
 * Author:            Josiah Troup
 * Author URI:        
 * Text Domain:       wact
 * Domain Path:       /languages
*/

// Prevent to access the file from outside of WordPress
if (!defined('ABSPATH')) {
	exit;
}

// Add functionality to recalculate taxes and totals for admin orders
add_action('woocommerce_process_shop_order_meta', function ($post_id, $post) {
	if (!is_admin()) return;

	$order = wc_get_order($post_id);
	if (!$order) return;

	// Only act if the order has line items
	$line_items = $order->get_items('line_item');
	if (empty($line_items)) return;

	/**
	 * IMPORTANT:
	 * - WooCommerce calculates taxes based on your settings:
	 *   WooCommerce > Settings > Tax > "Calculate tax based on" (Customer shipping address / Customer billing address / Shop base).
	 * - Ensure the order has the necessary address fields set before calculation,
	 *   otherwise tax may be calculated against the shop base.
	 */

	// Recalculate totals; passing true recalculates taxes too
	$order->calculate_totals(true);
	$order->save();
}, 20, 2);


add_action( 'woocommerce_process_shop_order_meta', 'validate_modified_order_field', 10, 2 );
function validate_modified_order_field( $order_id, $post ) {
    // Get the updated value of a specific field (e.g., billing phone)
	$payment_method = isset( $_POST['_payment_method'] ) ? sanitize_text_field( $_POST['_payment_method'] ) : '';
    $pi_system_delivery_date = isset( $_POST['pi_system_delivery_date'] ) ? sanitize_text_field( $_POST['pi_system_delivery_date'] ) : '';


	if (empty($payment_method)) {
		// Display an error and prevent saving if validation fails
		WC_Admin_Meta_Boxes::add_error( __( 'WARN: Enter payment method', 'textdomain' ) );
	}

    // Add your validation logic
    if (empty( $pi_system_delivery_date)) {
        // Display an error and prevent saving if validation fails
        WC_Admin_Meta_Boxes::add_error( __( 'WARN: You must enter a delivery/pickup date for an order - Otherwise the order will not be created', 'textdomain' ) );
    }
}