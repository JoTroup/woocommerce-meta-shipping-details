<?php
/*
 * Plugin Name:       WooCommerce Meta Shipping Details
 * Plugin URI:        https://example.com
 * Description:       Automatically validates and processes shipping metadata for WooCommerce orders.
 * Version:           1.0.0
 * Author:            Josiah Troup
 * Author URI:        https://example.com
 * Text Domain:       wmsd
 * Domain Path:       /languages
 */

// Prevent direct access to the file
if (!defined('ABSPATH')) {
	exit;
}

add_action( 'woocommerce_before_calculate_totals', 'modify_cart', 10, 1);

function modify_cart( $cart_object ) {

    if ( (is_admin() && ! defined( 'DOING_AJAX' ) ) || $cart_object->is_empty() )
        return;


	error_log("Modifying cart contents: {total items} " . count($cart_object->get_cart()));
	foreach ( $cart_object->get_cart() as $cart_item_key => $cart_item ) {
		// Log the entire cart item array
		error_log("Cart Item [$cart_item_key]: ");
		$ccb_calculator = isset($cart_item['ccb_calculator']) ? $cart_item['ccb_calculator'] : null;
		$calc_height = $quantity_field_id_5 = isset($ccb_calculator['calc_data']['quantity_field_id_5']) ? $ccb_calculator['calc_data']['quantity_field_id_5'] : null;
		$calc_width = $quantity_field_id_4 = isset($ccb_calculator['calc_data']['quantity_field_id_4']) ? $ccb_calculator['calc_data']['quantity_field_id_4'] : null;

		error_log(" - calc_height (quantity_field_id_5): " . print_r($calc_height, true));
		error_log(" - calc_width (quantity_field_id_4): " . print_r($calc_width, true));


		// Log cart item data object meta (if needed)
		// if (isset($cart_item['data']) && is_object($cart_item['data'])) {
		// 	error_log("Cart Item Data [$cart_item_key]: " . print_r($cart_item['data']->get_data(), true));
		// }
	}
}