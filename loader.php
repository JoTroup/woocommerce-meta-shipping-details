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
			$cart_item['data']->set_height($calc_height);
        	$cart_item['data']->set_width($calc_width);
			$cart_item['data']->set_length($calc_length);
			$cart_item['data']->set_weight($calc_weight);

			// fc_height meta data overwrite
			foreach ( $cart_object->get_cart() as $cart_item_key => $cart_item ) {
				if ( isset( $cart_item['data'] ) && is_object( $cart_item['data'] ) ) {
					$meta_data = $cart_item['data']->get_meta_data();
					foreach ( $meta_data as $meta_obj ) {
						if ( $meta_obj->key === 'fc_height' ) {
							// Overwrite the value
							$meta_obj->set_value( $calc_height ); // Set your new value here
							error_log("Overwrote fc_height for Cart Item [$cart_item_key]");
						}
					}
				}
			}

			// fc_width meta data overwrite
			foreach ( $cart_object->get_cart() as $cart_item_key => $cart_item ) {
				if ( isset( $cart_item['data'] ) && is_object( $cart_item['data'] ) ) {
					$meta_data = $cart_item['data']->get_meta_data();
					foreach ( $meta_data as $meta_obj ) {
						if ( $meta_obj->key === 'fc_width' ) {
							// Overwrite the value
							$meta_obj->set_value( $calc_width ); // Set your new value here
							error_log("Overwrote fc_width for Cart Item [$cart_item_key]");
						}
					}
				}
			}

			// fc_length meta data overwrite
			foreach ( $cart_object->get_cart() as $cart_item_key => $cart_item ) {
				if ( isset( $cart_item['data'] ) && is_object( $cart_item['data'] ) ) {
					$meta_data = $cart_item['data']->get_meta_data();
					foreach ( $meta_data as $meta_obj ) {
						if ( $meta_obj->key === 'fc_length' ) {
							// Overwrite the value
							$meta_obj->set_value( $calc_length ); // Set your new value here
							error_log("Overwrote fc_length for Cart Item [$cart_item_key]");
						}
					}
				}
			}

			// fc_weight meta data overwrite
			foreach ( $cart_object->get_cart() as $cart_item_key => $cart_item ) {
				if ( isset( $cart_item['data'] ) && is_object( $cart_item['data'] ) ) {
					$meta_data = $cart_item['data']->get_meta_data();
					foreach ( $meta_data as $meta_obj ) {
						if ( $meta_obj->key === 'fc_weight' ) {
							// Overwrite the value
							$meta_obj->set_value( $calc_weight ); // Set your new value here
							error_log("Overwrote fc_weight for Cart Item [$cart_item_key]");
						}
					}
				}
			}

			// Apply changes to ensure they take effect
			if( !empty( $cart_item['data']->get_changes() ) )
           		$cart_item['data']->apply_changes(); 

			
			// Check and log the values set on the product
			$height = $cart_item['data']->get_height();
			$width  = $cart_item['data']->get_width();
			$length = $cart_item['data']->get_length();
			$weight = $cart_item['data']->get_weight();
		
			error_log("Set on product: Height=$height, Width=$width, Length=$length, Weight=$weight");
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