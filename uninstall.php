<?php
/**
 * This file is run automatically when the user deletes the plugin.
 * It removes all elements added by the plugin (e.g., custom options, tables, etc.).
 *
 * More information: https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

// Remove plugin options
delete_option('wact_fields'); // Updated option name
delete_option('wact_options'); // Updated option name
delete_option('wact_debug_log'); // Updated option name

// Remove any other custom options added by the plugin
delete_option('wact_custom_field_1'); // Updated option name
delete_option('wact_custom_field_2'); // Updated option name

// If the plugin created custom database tables, drop them here
// global $wpdb;
// $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wact_custom_table"); // Updated table prefix

