<?php
/*
 * Plugin Name:       WooCommerce Meta Shipping Details
 * Plugin URI:        https://example.com
 * Description:       Automatically maps Cost Calculator Builder fields onto WooCommerce product meta during cart calculations.
 * Version:           1.1.0
 * Author:            Josiah Troup
 * Author URI:        https://example.com
 * Text Domain:       wmsd
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WMSD_OPTION_MAPPINGS', 'wmsd_calculator_mappings' );
define( 'WMSD_ADMIN_SLUG', 'wmsd-field-mappings' );

if ( ! defined( 'WMSD_DEBUG' ) ) {
	define( 'WMSD_DEBUG', true );
}

add_action( 'woocommerce_before_calculate_totals', 'wmsd_modify_cart', 10, 1 );
add_action( 'admin_menu', 'wmsd_register_admin_page' );
add_action( 'admin_post_wmsd_save_mappings', 'wmsd_handle_save_mappings' );
add_filter( 'woocommerce_cart_shipping_packages', 'wmsd_attach_shipping_meta_overrides', 20, 1 );
add_action( 'woocommerce_before_get_rates_for_package', 'wmsd_enable_shipping_override_context', 10, 2 );
add_action( 'woocommerce_after_get_rates_for_package', 'wmsd_disable_shipping_override_context', 10, 2 );
add_filter( 'get_post_metadata', 'wmsd_filter_post_metadata_for_shipping', 10, 4 );

function wmsd_modify_cart( $cart_object ) {
	if ( ( is_admin() && ! defined( 'DOING_AJAX' ) ) || ! is_object( $cart_object ) || $cart_object->is_empty() ) {
		return;
	}

	$mappings = wmsd_get_saved_mappings();

	if ( empty( $mappings ) ) {
		return;
	}

	foreach ( $cart_object->get_cart() as $cart_item ) {
		if ( empty( $cart_item['ccb_calculator'] ) || empty( $cart_item['data'] ) || ! is_object( $cart_item['data'] ) ) {
			continue;
		}

		$calculator_data = $cart_item['ccb_calculator'];
		$calculator_id   = wmsd_get_calculator_id_from_cart_item( $calculator_data );
		$cart_item_key   = isset( $cart_item['key'] ) ? (string) $cart_item['key'] : '';

		if ( ! $calculator_id || empty( $mappings[ $calculator_id ] ) || empty( $calculator_data['calc_data'] ) || ! is_array( $calculator_data['calc_data'] ) ) {
			continue;
		}

		$product = $cart_item['data'];
		$product_ids = array_filter(
			array(
				method_exists( $product, 'get_id' ) ? absint( $product->get_id() ) : 0,
				method_exists( $product, 'get_parent_id' ) ? absint( $product->get_parent_id() ) : 0,
			)
		);

		foreach ( $mappings[ $calculator_id ] as $mapping ) {
			$field_alias = $mapping['field_alias'];
			$product_id  = isset( $mapping['product_id'] ) ? absint( $mapping['product_id'] ) : 0;
			$meta_key    = $mapping['meta_key'];

			if ( $product_id && ! in_array( $product_id, $product_ids, true ) ) {
				continue;
			}

			if ( empty( $calculator_data['calc_data'][ $field_alias ] ) || '' === $meta_key ) {
				continue;
			}

			$field_data = $calculator_data['calc_data'][ $field_alias ];
			$value      = wmsd_extract_mapped_value( $field_data );

			if ( null === $value ) {
				continue;
			}

			$product->update_meta_data( $meta_key, $value );

			wmsd_log(
				'Mapped cart value onto product meta',
				array(
					'cart_item_key' => $cart_item_key,
					'calculator_id' => $calculator_id,
					'field_alias'   => $field_alias,
					'meta_key'      => $meta_key,
					'product_ids'   => $product_ids,
					'value'         => $value,
				)
			);
		}

		if ( method_exists( $product, 'get_changes' ) && method_exists( $product, 'apply_changes' ) && ! empty( $product->get_changes() ) ) {
			$product->apply_changes();
		}
	}
}

function wmsd_extract_mapped_value( $field_data ) {
	if ( ! is_array( $field_data ) || ! array_key_exists( 'value', $field_data ) ) {
		return null;
	}

	$value = $field_data['value'];

	if ( is_array( $value ) ) {
		$value = wp_json_encode( $value );
	}

	if ( is_string( $value ) ) {
		$value = trim( $value );
	}

	if ( '' === $value && '0' !== $value && 0 !== $value ) {
		return null;
	}

	return $value;
}

function wmsd_attach_shipping_meta_overrides( $packages ) {
	if ( ! is_array( $packages ) ) {
		return $packages;
	}

	$mappings = wmsd_get_saved_mappings();

	if ( empty( $mappings ) ) {
		return $packages;
	}

	foreach ( $packages as $package_index => $package ) {
		if ( empty( $package['contents'] ) || ! is_array( $package['contents'] ) ) {
			continue;
		}

		$overrides = array();

		foreach ( $package['contents'] as $item ) {
			if ( empty( $item['ccb_calculator'] ) || empty( $item['data'] ) || ! is_object( $item['data'] ) ) {
				continue;
			}

			$product = $item['data'];

			if ( ! method_exists( $product, 'get_id' ) ) {
				continue;
			}

			$product_ids = array_filter(
				array(
					absint( $product->get_id() ),
					method_exists( $product, 'get_parent_id' ) ? absint( $product->get_parent_id() ) : 0,
				)
			);

			if ( empty( $product_ids ) ) {
				continue;
			}

			$calculator_id = wmsd_get_calculator_id_from_cart_item( $item['ccb_calculator'] );
			$cart_item_key = isset( $item['key'] ) ? (string) $item['key'] : '';

			if ( ! $calculator_id || empty( $mappings[ $calculator_id ] ) ) {
				continue;
			}

			foreach ( $mappings[ $calculator_id ] as $mapping ) {
				$meta_key          = $mapping['meta_key'];
				$target_product_id = isset( $mapping['product_id'] ) ? absint( $mapping['product_id'] ) : 0;

				if ( '' === $meta_key || ( $target_product_id && ! in_array( $target_product_id, $product_ids, true ) ) ) {
					continue;
				}

				$value = $product->get_meta( $meta_key, true );

				if ( '' === $value && '0' !== (string) $value ) {
					continue;
				}

				foreach ( $product_ids as $product_id ) {
					if ( ! isset( $overrides[ $product_id ] ) ) {
						$overrides[ $product_id ] = array();
					}

					$overrides[ $product_id ][ $meta_key ] = $value;

					wmsd_log(
						'Prepared shipping meta override from cart product',
						array(
							'package_index'      => $package_index,
							'cart_item_key'      => $cart_item_key,
							'calculator_id'      => $calculator_id,
							'product_id'         => $product_id,
							'target_product_id'  => $target_product_id,
							'meta_key'           => $meta_key,
							'value'              => $value,
						)
					);
				}
			}
		}

		$packages[ $package_index ]['wmsd_meta_overrides'] = $overrides;

		// Persist overrides into the request-level map so the get_post_metadata filter
		// can intercept reads regardless of when fast-courier calls get_post_meta.
		if ( ! isset( $GLOBALS['wmsd_all_overrides'] ) || ! is_array( $GLOBALS['wmsd_all_overrides'] ) ) {
			$GLOBALS['wmsd_all_overrides'] = array();
		}
		foreach ( $overrides as $pid => $meta_map ) {
			if ( ! isset( $GLOBALS['wmsd_all_overrides'][ $pid ] ) ) {
				$GLOBALS['wmsd_all_overrides'][ $pid ] = array();
			}
			$GLOBALS['wmsd_all_overrides'][ $pid ] = array_merge( $GLOBALS['wmsd_all_overrides'][ $pid ], $meta_map );
		}

		wmsd_log(
			'Built package shipping overrides',
			array(
				'package_index' => $package_index,
				'overrides'     => $overrides,
			)
		);
	}

	return $packages;
}

function wmsd_enable_shipping_override_context( $package, $shipping_method ) {
	$shipping_method_id = is_object( $shipping_method ) && method_exists( $shipping_method, 'get_method_id' ) ? $shipping_method->get_method_id() : '';

	$GLOBALS['wmsd_shipping_override_active'] = true;
	$GLOBALS['wmsd_shipping_override_map']    = ( ! empty( $package['wmsd_meta_overrides'] ) && is_array( $package['wmsd_meta_overrides'] ) ) ? $package['wmsd_meta_overrides'] : array();

	wmsd_log(
		'Enabled shipping override context',
		array(
			'shipping_method_id' => $shipping_method_id,
			'override_products'  => array_keys( $GLOBALS['wmsd_shipping_override_map'] ),
		)
	);
}

function wmsd_disable_shipping_override_context( $package, $shipping_method ) {
	unset( $package, $shipping_method );

	wmsd_log( 'Disabled shipping override context' );

	unset( $GLOBALS['wmsd_shipping_override_active'] );
	unset( $GLOBALS['wmsd_shipping_override_map'] );
}

function wmsd_filter_post_metadata_for_shipping( $value, $object_id, $meta_key, $single ) {
	if ( empty( $GLOBALS['wmsd_all_overrides'] ) ) {
		return $value;
	}

	$object_id = absint( $object_id );

	if ( ! $object_id || empty( $GLOBALS['wmsd_all_overrides'][ $object_id ] ) ) {
		return $value;
	}

	$overrides = $GLOBALS['wmsd_all_overrides'][ $object_id ];

	if ( '' !== $meta_key ) {
		if ( ! array_key_exists( $meta_key, $overrides ) ) {
			return $value;
		}

		wmsd_log(
			'Overrode get_post_meta value during shipping',
			array(
				'product_id' => $object_id,
				'meta_key'   => $meta_key,
				'value'      => $overrides[ $meta_key ],
			)
		);

		return $single ? $overrides[ $meta_key ] : array( $overrides[ $meta_key ] );
	}

	static $running = false;

	if ( $running ) {
		return $value;
	}

	$running = true;
	remove_filter( 'get_post_metadata', 'wmsd_filter_post_metadata_for_shipping', 10 );
	$meta_data = get_post_meta( $object_id );
	add_filter( 'get_post_metadata', 'wmsd_filter_post_metadata_for_shipping', 10, 4 );
	$running = false;

	if ( ! is_array( $meta_data ) ) {
		$meta_data = array();
	}

	foreach ( $overrides as $override_key => $override_value ) {
		$meta_data[ $override_key ] = array( $override_value );
	}

	wmsd_log(
		'Overrode full product meta array during shipping',
		array(
			'product_id'    => $object_id,
			'override_keys' => array_keys( $overrides ),
		)
	);

	return $meta_data;
}

function wmsd_log( $message, $context = array() ) {
	if ( ! WMSD_DEBUG ) {
		return;
	}

	$context = is_array( $context ) ? $context : array( 'context' => $context );

	if ( function_exists( 'wc_get_logger' ) ) {
		$logger = wc_get_logger();
		$logger->debug( $message . ' ' . wp_json_encode( $context ), array( 'source' => 'wmsd' ) );
		return;
	}

	if ( function_exists( 'error_log' ) ) {
		error_log( '[wmsd] ' . $message . ' ' . wp_json_encode( $context ) );
	}
}

function wmsd_get_calculator_id_from_cart_item( $calculator_data ) {
	if ( ! empty( $calculator_data['calc_id'] ) ) {
		return absint( $calculator_data['calc_id'] );
	}

	if ( empty( $calculator_data['order_id'] ) ) {
		return 0;
	}

	static $calculator_ids_by_order = array();

	$order_id = absint( $calculator_data['order_id'] );

	if ( isset( $calculator_ids_by_order[ $order_id ] ) ) {
		return $calculator_ids_by_order[ $order_id ];
	}

	$calculator_ids_by_order[ $order_id ] = 0;

	if ( class_exists( '\\cBuilder\\Classes\\Database\\CalcOrders' ) && method_exists( '\\cBuilder\\Classes\\Database\\CalcOrders', 'get_order_full_data_by_id' ) ) {
		$order = \cBuilder\Classes\Database\CalcOrders::get_order_full_data_by_id( $order_id );

		if ( is_array( $order ) && ! empty( $order['calc_id'] ) ) {
			$calculator_ids_by_order[ $order_id ] = absint( $order['calc_id'] );
		}
	}

	return $calculator_ids_by_order[ $order_id ];
}

function wmsd_register_admin_page() {
	add_submenu_page(
		'woocommerce',
		__( 'Calculator Field Mapping', 'wmsd' ),
		__( 'Calculator Mapping', 'wmsd' ),
		'manage_woocommerce',
		WMSD_ADMIN_SLUG,
		'wmsd_render_admin_page'
	);
}

function wmsd_render_admin_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'wmsd' ) );
	}

	$calculators      = wmsd_get_calculators_with_fields();
	$calculator_map   = wmsd_build_calculator_map( $calculators );
	$products         = wmsd_get_products_with_meta_keys();
	$product_map      = wmsd_build_product_map( $products );
	$saved_mappings   = wmsd_get_raw_mappings();
	$rows             = empty( $saved_mappings ) ? array( array( 'calculator_id' => '', 'field_alias' => '', 'product_id' => '', 'meta_key' => '' ) ) : $saved_mappings;
	$settings_updated = ! empty( $_GET['settings-updated'] );
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'Cost Calculator Builder Field Mapping', 'wmsd' ); ?></h1>
		<p><?php echo esc_html__( 'Map Cost Calculator Builder fields to WooCommerce product meta keys. Each mapping can target a specific product, and these mappings are applied during cart calculation without modifying the Cost Calculator Builder plugin.', 'wmsd' ); ?></p>

		<?php if ( $settings_updated ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html__( 'Mappings saved.', 'wmsd' ); ?></p></div>
		<?php endif; ?>

		<?php if ( empty( $calculators ) ) : ?>
			<div class="notice notice-warning"><p><?php echo esc_html__( 'No published Cost Calculator Builder calculators were found.', 'wmsd' ); ?></p></div>
		<?php endif; ?>

		<?php if ( empty( $products ) ) : ?>
			<div class="notice notice-warning"><p><?php echo esc_html__( 'No published WooCommerce products were found.', 'wmsd' ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="wmsd_save_mappings">
			<?php wp_nonce_field( 'wmsd_save_mappings' ); ?>

			<table class="widefat striped" id="wmsd-mapping-table">
				<thead>
					<tr>
						<th style="width: 22%;"><?php echo esc_html__( 'Calculator', 'wmsd' ); ?></th>
						<th style="width: 24%;"><?php echo esc_html__( 'Source Field', 'wmsd' ); ?></th>
						<th style="width: 22%;"><?php echo esc_html__( 'Product', 'wmsd' ); ?></th>
						<th style="width: 24%;"><?php echo esc_html__( 'Target Product Meta Key', 'wmsd' ); ?></th>
						<th style="width: 8%;"></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( array_values( $rows ) as $index => $row ) : ?>
						<?php wmsd_render_mapping_row( $index, $row, $calculator_map, $product_map ); ?>
					<?php endforeach; ?>
				</tbody>
			</table>

			<p>
				<button type="button" class="button" id="wmsd-add-row"><?php echo esc_html__( 'Add Mapping', 'wmsd' ); ?></button>
			</p>

			<?php submit_button( __( 'Save Mappings', 'wmsd' ) ); ?>
		</form>
	</div>

	<script>
		(function() {
			const calculators = <?php echo wp_json_encode( $calculator_map ); ?>;
			const products = <?php echo wp_json_encode( $product_map ); ?>;
			const tableBody = document.querySelector('#wmsd-mapping-table tbody');
			const addRowButton = document.getElementById('wmsd-add-row');
			let nextIndex = tableBody.querySelectorAll('tr').length;

			function escapeHtml(value) {
				return String(value)
					.replace(/&/g, '&amp;')
					.replace(/</g, '&lt;')
					.replace(/>/g, '&gt;')
					.replace(/"/g, '&quot;')
					.replace(/'/g, '&#039;');
			}

			function calculatorOptions(selectedValue) {
				let options = '<option value="">Select calculator</option>';

				Object.keys(calculators).forEach(function(id) {
					const selected = String(selectedValue) === String(id) ? ' selected' : '';
					options += '<option value="' + escapeHtml(id) + '"' + selected + '>' + escapeHtml(calculators[id].label) + '</option>';
				});

				return options;
			}

			function fieldOptions(calculatorId, selectedValue) {
				let options = '<option value="">Select field</option>';
				const calculator = calculators[String(calculatorId)];

				if (!calculator || !calculator.fields) {
					return options;
				}

				calculator.fields.forEach(function(field) {
					const selected = String(selectedValue) === String(field.alias) ? ' selected' : '';
					const label = field.label + ' [' + field.alias + ']';
					options += '<option value="' + escapeHtml(field.alias) + '"' + selected + '>' + escapeHtml(label) + '</option>';
				});

				return options;
			}

			function productOptions(selectedValue) {
				let options = '<option value="">Select product</option>';

				Object.keys(products).forEach(function(id) {
					const selected = String(selectedValue) === String(id) ? ' selected' : '';
					options += '<option value="' + escapeHtml(id) + '"' + selected + '>' + escapeHtml(products[id].label) + '</option>';
				});

				return options;
			}

			function metaKeyOptions(productId, selectedValue) {
				let options = '<option value="">Select meta key</option>';
				const product = products[String(productId)];
				const metaKeys = product && Array.isArray(product.meta_keys) ? product.meta_keys : [];

				metaKeys.forEach(function(metaKey) {
					const selected = String(selectedValue) === String(metaKey) ? ' selected' : '';
					options += '<option value="' + escapeHtml(metaKey) + '"' + selected + '>' + escapeHtml(metaKey) + '</option>';
				});

				if (selectedValue && !metaKeys.includes(String(selectedValue))) {
					options += '<option value="' + escapeHtml(selectedValue) + '" selected>' + escapeHtml(selectedValue) + '</option>';
				}

				return options;
			}

			function bindRow(row) {
				const calculatorSelect = row.querySelector('.wmsd-calculator-select');
				const fieldSelect = row.querySelector('.wmsd-field-select');
				const productSelect = row.querySelector('.wmsd-product-select');
				const metaKeySelect = row.querySelector('.wmsd-meta-key-select');

				calculatorSelect.addEventListener('change', function() {
					fieldSelect.innerHTML = fieldOptions(calculatorSelect.value, '');
				});

				productSelect.addEventListener('change', function() {
					metaKeySelect.innerHTML = metaKeyOptions(productSelect.value, '');
				});

				row.querySelector('.wmsd-remove-row').addEventListener('click', function() {
					if (tableBody.querySelectorAll('tr').length === 1) {
						calculatorSelect.value = '';
						fieldSelect.innerHTML = fieldOptions('', '');
						productSelect.value = '';
						metaKeySelect.innerHTML = metaKeyOptions('', '');
						return;
					}

					row.remove();
				});
			}

			function createRow(index) {
				const row = document.createElement('tr');
				row.innerHTML = ''
					+ '<td><select class="wmsd-calculator-select" name="mappings[' + index + '][calculator_id]">' + calculatorOptions('') + '</select></td>'
					+ '<td><select class="wmsd-field-select" name="mappings[' + index + '][field_alias]">' + fieldOptions('', '') + '</select></td>'
					+ '<td><select class="wmsd-product-select" name="mappings[' + index + '][product_id]">' + productOptions('') + '</select></td>'
					+ '<td><select class="wmsd-meta-key-select" name="mappings[' + index + '][meta_key]">' + metaKeyOptions('', '') + '</select></td>'
					+ '<td><button type="button" class="button-link-delete wmsd-remove-row">Remove</button></td>';

				bindRow(row);
				return row;
			}

			addRowButton.addEventListener('click', function() {
				tableBody.appendChild(createRow(nextIndex));
				nextIndex += 1;
			});

			tableBody.querySelectorAll('tr').forEach(bindRow);
		})();
	</script>

	<style>
		#wmsd-mapping-table select,
		#wmsd-mapping-table input {
			width: 100%;
		}
	</style>
	<?php
}


function wmsd_render_mapping_row( $index, $row, $calculator_map, $product_map ) {
	$calculator_id = isset( $row['calculator_id'] ) ? (string) $row['calculator_id'] : '';
	$field_alias   = isset( $row['field_alias'] ) ? (string) $row['field_alias'] : '';
	$product_id    = isset( $row['product_id'] ) ? (string) $row['product_id'] : '';
	$meta_key      = isset( $row['meta_key'] ) ? (string) $row['meta_key'] : '';
	$fields        = isset( $calculator_map[ $calculator_id ]['fields'] ) ? $calculator_map[ $calculator_id ]['fields'] : array();
	$meta_keys     = isset( $product_map[ $product_id ]['meta_keys'] ) ? $product_map[ $product_id ]['meta_keys'] : array();

	if ( '' !== $meta_key && ! in_array( $meta_key, $meta_keys, true ) ) {
		$meta_keys[] = $meta_key;
	}
	?>
	<tr>
		<td>
			<select class="wmsd-calculator-select" name="mappings[<?php echo esc_attr( $index ); ?>][calculator_id]">
				<option value=""><?php echo esc_html__( 'Select calculator', 'wmsd' ); ?></option>
				<?php foreach ( $calculator_map as $id => $calculator ) : ?>
					<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $calculator_id, (string) $id ); ?>><?php echo esc_html( $calculator['label'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</td>
		<td>
			<select class="wmsd-field-select" name="mappings[<?php echo esc_attr( $index ); ?>][field_alias]">
				<option value=""><?php echo esc_html__( 'Select field', 'wmsd' ); ?></option>
				<?php foreach ( $fields as $field ) : ?>
					<option value="<?php echo esc_attr( $field['alias'] ); ?>" <?php selected( $field_alias, $field['alias'] ); ?>><?php echo esc_html( $field['label'] . ' [' . $field['alias'] . ']' ); ?></option>
				<?php endforeach; ?>
			</select>
		</td>
		<td>
			<select class="wmsd-product-select" name="mappings[<?php echo esc_attr( $index ); ?>][product_id]">
				<option value=""><?php echo esc_html__( 'Select product', 'wmsd' ); ?></option>
				<?php foreach ( $product_map as $id => $product ) : ?>
					<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $product_id, (string) $id ); ?>><?php echo esc_html( $product['label'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</td>
		<td>
			<select class="wmsd-meta-key-select" name="mappings[<?php echo esc_attr( $index ); ?>][meta_key]">
				<option value=""><?php echo esc_html__( 'Select meta key', 'wmsd' ); ?></option>
				<?php foreach ( $meta_keys as $available_meta_key ) : ?>
					<option value="<?php echo esc_attr( $available_meta_key ); ?>" <?php selected( $meta_key, $available_meta_key ); ?>><?php echo esc_html( $available_meta_key ); ?></option>
				<?php endforeach; ?>
			</select>
		</td>
		<td>
			<button type="button" class="button-link-delete wmsd-remove-row"><?php echo esc_html__( 'Remove', 'wmsd' ); ?></button>
		</td>
	</tr>
	<?php
}

function wmsd_handle_save_mappings() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'You do not have permission to perform this action.', 'wmsd' ) );
	}

	check_admin_referer( 'wmsd_save_mappings' );

	$raw_mappings = isset( $_POST['mappings'] ) ? wp_unslash( $_POST['mappings'] ) : array();
	$mappings     = array();

	if ( is_array( $raw_mappings ) ) {
		foreach ( $raw_mappings as $mapping ) {
			$calculator_id = isset( $mapping['calculator_id'] ) ? absint( $mapping['calculator_id'] ) : 0;
			$field_alias   = isset( $mapping['field_alias'] ) ? sanitize_text_field( $mapping['field_alias'] ) : '';
			$product_id    = isset( $mapping['product_id'] ) ? absint( $mapping['product_id'] ) : 0;
			$meta_key      = isset( $mapping['meta_key'] ) ? sanitize_key( $mapping['meta_key'] ) : '';

			if ( ! $calculator_id || ! $product_id || '' === $field_alias || '' === $meta_key ) {
				continue;
			}

			$mappings[] = array(
				'calculator_id' => $calculator_id,
				'field_alias'   => $field_alias,
				'product_id'    => $product_id,
				'meta_key'      => $meta_key,
			);
		}
	}

	update_option( WMSD_OPTION_MAPPINGS, $mappings, false );

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'             => WMSD_ADMIN_SLUG,
				'settings-updated' => '1',
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}

function wmsd_get_saved_mappings() {
	$raw_mappings = wmsd_get_raw_mappings();
	$grouped      = array();

	foreach ( $raw_mappings as $mapping ) {
		$calculator_id = absint( $mapping['calculator_id'] );

		if ( ! $calculator_id || empty( $mapping['field_alias'] ) || empty( $mapping['meta_key'] ) ) {
			continue;
		}

		if ( ! isset( $grouped[ $calculator_id ] ) ) {
			$grouped[ $calculator_id ] = array();
		}

		$grouped[ $calculator_id ][] = array(
			'field_alias' => $mapping['field_alias'],
			'product_id'  => isset( $mapping['product_id'] ) ? absint( $mapping['product_id'] ) : 0,
			'meta_key'    => $mapping['meta_key'],
		);
	}

	return $grouped;
}

function wmsd_get_raw_mappings() {
	$mappings = get_option( WMSD_OPTION_MAPPINGS, array() );

	return is_array( $mappings ) ? $mappings : array();
}

function wmsd_get_calculators_with_fields() {
	$calculators = array();
	$posts       = get_posts(
		array(
			'post_type'      => 'cost-calc',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
		)
	);

	foreach ( $posts as $calculator_id ) {
		$label  = get_post_meta( $calculator_id, 'stm-name', true );
		$fields = get_post_meta( $calculator_id, 'stm-fields', true );

		$calculators[] = array(
			'id'     => (int) $calculator_id,
			'label'  => $label ? $label : get_the_title( $calculator_id ),
			'fields' => wmsd_extract_calculator_fields( is_array( $fields ) ? $fields : array() ),
		);
	}

	return $calculators;
}

function wmsd_build_calculator_map( $calculators ) {
	$map = array();

	foreach ( $calculators as $calculator ) {
		$map[ (string) $calculator['id'] ] = array(
			'label'  => $calculator['label'],
			'fields' => $calculator['fields'],
		);
	}

	return $map;
}

function wmsd_get_products_with_meta_keys() {
	$products = array();
	$posts    = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => array( 'publish', 'private' ),
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
		)
	);

	foreach ( $posts as $product_id ) {
		$products[] = array(
			'id'        => (int) $product_id,
			'label'     => wmsd_get_product_label( $product_id ),
			'meta_keys' => wmsd_get_product_meta_keys( $product_id ),
		);
	}

	return $products;
}

function wmsd_build_product_map( $products ) {
	$map = array();

	foreach ( $products as $product ) {
		$map[ (string) $product['id'] ] = array(
			'label'     => $product['label'],
			'meta_keys' => $product['meta_keys'],
		);
	}

	return $map;
}

function wmsd_get_product_label( $product_id ) {
	$title = get_the_title( $product_id );
	$type  = 'product_variation' === get_post_type( $product_id ) ? __( 'Variation', 'wmsd' ) : __( 'Product', 'wmsd' );

	if ( '' === $title ) {
		$title = sprintf( __( 'Untitled #%d', 'wmsd' ), $product_id );
	}

	return sprintf( '%s #%d (%s)', $title, $product_id, $type );
}

function wmsd_get_product_meta_keys( $product_id ) {
	$meta       = get_post_meta( $product_id );
	$meta_keys  = array();
	$skip_keys  = array( '_edit_last', '_edit_lock' );

	if ( ! is_array( $meta ) ) {
		return $meta_keys;
	}

	foreach ( array_keys( $meta ) as $meta_key ) {
		if ( ! is_string( $meta_key ) || '' === $meta_key || in_array( $meta_key, $skip_keys, true ) ) {
			continue;
		}

		if ( 0 === strpos( $meta_key, '_' ) ) {
			continue;
		}

		$meta_keys[] = $meta_key;
	}

	$meta_keys = array_values( array_unique( $meta_keys ) );
	natcasesort( $meta_keys );

	return array_values( $meta_keys );
}

function wmsd_extract_calculator_fields( $fields ) {
	$extracted = array();
	$seen      = array();

	$walk = function( $items ) use ( &$walk, &$extracted, &$seen ) {
		foreach ( $items as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			if ( ! empty( $field['alias'] ) && ! isset( $seen[ $field['alias'] ] ) ) {
				$seen[ $field['alias'] ] = true;
				$extracted[]             = array(
					'alias' => $field['alias'],
					'label' => ! empty( $field['label'] ) ? $field['label'] : ( ! empty( $field['text'] ) ? $field['text'] : $field['alias'] ),
				);
			}

			if ( ! empty( $field['groupElements'] ) && is_array( $field['groupElements'] ) ) {
				$walk( $field['groupElements'] );
			}

			if ( ! empty( $field['fields'] ) && is_array( $field['fields'] ) ) {
				$walk( $field['fields'] );
			}
		}
	};

	$walk( $fields );

	return $extracted;
}