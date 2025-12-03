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

    foreach ( $cart_object->get_cart() as $cart_item ) {

        $product_id = $cart_item['product_id'];

        $data = get_post_meta( $product_id, 'ccb_calculator'); 

		error_log('$data: ' . print_r( $data, true ) );
        // $cart_item['data']->set_width( $data['shipping_width'] );
        // $cart_item['data']->set_height( $data['shipping_width'] );
        // $cart_item['data']->set_length( $data['shipping_length'] );

        // if( !empty( $cart_item['data']->get_changes() ) )
        //    $cart_item['data']->apply_changes();

    }
}