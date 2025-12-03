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

//add_action( 'woocommerce_before_calculate_totals', 'check_cart', 10, 1);

function modify_cart($cart_object) {

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
			// $cart_item['data']->set_height($calc_height);
        	// $cart_item['data']->set_width($calc_width);
			// $cart_item['data']->set_length($calc_length);
			// $cart_item['data']->set_weight($calc_weight);

			// fc_height meta data overwrite
			foreach ( $cart_object->get_cart() as $cart_item_key => $cart_item ) {
				// Overwrite fc_height meta data
				if (isset($cart_item['data']) && is_object($cart_item['data'])) {
					$cart_item['data']->update_meta_data('c_height', $calc_height);
					error_log("Overwrote fc_height for Cart Item [$cart_item_key]");
				}

				// Overwrite fc_width meta data
				if (isset($cart_item['data']) && is_object($cart_item['data'])) {
					$cart_item['data']->update_meta_data('c_width', $calc_width);
					error_log("Overwrote fc_width for Cart Item [$cart_item_key]");
				}

				// Overwrite fc_length meta data
				if (isset($cart_item['data']) && is_object($cart_item['data'])) {
					$cart_item['data']->update_meta_data('c_length', $calc_length);
					error_log("Overwrote fc_length for Cart Item [$cart_item_key]");
				}

				// Overwrite fc_weight meta data
				if (isset($cart_item['data']) && is_object($cart_item['data'])) {
					$cart_item['data']->update_meta_data('c_weight', $calc_weight);
					error_log("Overwrote fc_weight for Cart Item [$cart_item_key]");
				}
			}

			foreach ( $cart_object->get_cart() as $cart_item_key => $cart_item ) {
				// Overwrite fc_height meta data
				if (isset($cart_item['data']) && is_object($cart_item['data'])) {
					$cart_item['data']->update_meta_data('fc_height', $calc_height);
					error_log("Overwrote fc_height for Cart Item [$cart_item_key]");
				}

				// Overwrite fc_width meta data
				if (isset($cart_item['data']) && is_object($cart_item['data'])) {
					$cart_item['data']->update_meta_data('fc_width', $calc_width);
					error_log("Overwrote fc_width for Cart Item [$cart_item_key]");
				}

				// Overwrite fc_length meta data
				if (isset($cart_item['data']) && is_object($cart_item['data'])) {
					$cart_item['data']->update_meta_data('fc_length', $calc_length);
					error_log("Overwrote fc_length for Cart Item [$cart_item_key]");
				}

				// Overwrite fc_weight meta data
				if (isset($cart_item['data']) && is_object($cart_item['data'])) {
					$cart_item['data']->update_meta_data('fc_weight', $calc_weight);
					error_log("Overwrote fc_weight for Cart Item [$cart_item_key]");
				}
			}

			// Log the calculated dimensions
			error_log("Calculated dimensions: Height=$calc_height, Width=$calc_width, Length=$calc_length, Weight=$calc_weight");

			foreach ( $cart_object->get_cart() as $cart_item_key => $cart_item ) {
				$meta_width = $cart_item['data']->get_meta('c_width', true); // 'true' for single value
				error_log("Meta pm_width: " . $cart_item['data']->get_meta('c_width', true));
				error_log("Meta pm_length: " . $cart_item['data']->get_meta('c_length', true));
				error_log("Meta pm_height: " . $cart_item['data']->get_meta('c_height', true));
				error_log("Meta pm_weight: " . $cart_item['data']->get_meta('c_weight', true));

				error_log("Meta fc_width: " . $cart_item['data']->get_meta('fc_width', true));
				error_log("Meta fc_length: " . $cart_item['data']->get_meta('fc_length', true));
				error_log("Meta fc_height: " . $cart_item['data']->get_meta('fc_height', true));
				error_log("Meta fc_weight: " . $cart_item['data']->get_meta('fc_weight', true));
		
			
			}


			// Apply changes to ensure they take effect
			if( !empty( $cart_item['data']->get_changes() ) )
           		$cart_item['data']->apply_changes(); 

			
			// Check and log the values set on the product
			$height = $cart_item['data']->get_height();
			$width  = $cart_item['data']->get_width();
			$length = $cart_item['data']->get_length();
			$weight = $cart_item['data']->get_weight();
		
		} else {
			error_log(" - Calculated dimensions not found in cart item.");
		}
	}
}

function check_cart ( $cart_object ) {
	if ( (is_admin() && ! defined( 'DOING_AJAX' ) ) || $cart_object->is_empty() )
	return;


	error_log("Modifying cart contents: {total items} " . count($cart_object->get_cart()));
	foreach ( $cart_object->get_cart() as $cart_item_key => $cart_item ) {
		// Check and log the values set on the product
		$height = $cart_item['data']->get_height();
		$width  = $cart_item['data']->get_width();
		$length = $cart_item['data']->get_length();
		$weight = $cart_item['data']->get_weight();
	
		error_log("Set on product: Height=$height, Width=$width, Length=$length, Weight=$weight");
	}
}