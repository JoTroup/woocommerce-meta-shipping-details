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

		$calc_height = $quantity_field_id_5 = isset($ccb_calculator['calc_data']['quantity_field_id_5']['value']) ? $ccb_calculator['calc_data']['quantity_field_id_5']['value'] : null;

		$calc_width = $quantity_field_id_4 = isset($ccb_calculator['calc_data']['quantity_field_id_4']['value']) ? $ccb_calculator['calc_data']['quantity_field_id_4']['value'] : null;

		$calc_length = $quantity_field_id_6 = isset($ccb_calculator['calc_data']['quantity_field_id_10']['value']) ? $ccb_calculator['calc_data']['quantity_field_id_10']['value'] : null;

		$calc_weight = $quantity_field_id_3 = isset($ccb_calculator['calc_data']['quantity_field_id_17']['value']) ? $ccb_calculator['calc_data']['quantity_field_id_17']['value'] : null;

		if ($calc_height !== null && $calc_width !== null && $calc_length !== null && $calc_weight !== null) {
			// Example modification: Log the calculated dimensions
			error_log(" - Calculated Height: " . $calc_height);
			error_log(" - Calculated Width: " . $calc_width);

			$cart_item['data']->set_height($calc_height);
        	$cart_item['data']->set_width($calc_width);
			$cart_item['data']->set_length($calc_length);
			$cart_item['data']->set_weight($calc_weight);


			if( !empty( $cart_item['data']->get_changes() ) )
				error_log( ' - Changes detected in cart item data: ' . print_r( $cart_item['data']->get_changes(), true ) );
           		$cart_item['data']->apply_changes();

			// You can add more logic here to modify the cart item based on these values
		} else {
			error_log(" - Calculated dimensions not found in cart item.");
		}
	}
}