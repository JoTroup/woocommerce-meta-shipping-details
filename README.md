# WooCommerce Meta Shipping Details

## Description

The WooCommerce Meta Shipping Details plugin maps Cost Calculator Builder field values onto WooCommerce product meta during cart calculation.

## Features

- Adds an admin page under WooCommerce for calculator-to-product-meta mapping.
- Reads available calculators and field aliases from Cost Calculator Builder without modifying that plugin.
- Lets you choose a WooCommerce product and then select from that product's existing meta keys.
- Supports multiple mappings across multiple calculators and products.
- Updates WooCommerce product meta on in-cart product objects during cart calculation.
- Overrides product meta reads during shipping-rate calculation so shipping plugins that query DB meta still receive mapped cart values.

## Installation

1. Download the plugin ZIP file.
2. Go to your WordPress admin dashboard.
3. Navigate to **Plugins > Add New**.
4. Click **Upload Plugin** and select the downloaded ZIP file.
5. Click **Install Now** and then **Activate** the plugin.

## Usage

1. Go to WooCommerce > Calculator Mapping.
2. Add one or more mappings that connect a calculator field to a WooCommerce product and one of that product's meta keys.
3. Save the mappings.
4. When a mapped calculator adds a product to the cart, the selected field values are copied to the configured product meta keys for that cart item.
5. During shipping-rate calculation, mapped values are injected into product meta lookups for the current shipping package.

## Support

For support, visit the plugin's support page.

## License

This plugin is licensed under the [GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

