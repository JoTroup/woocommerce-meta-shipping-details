<?php
/**
 * This file is executed automatically when the plugin is deleted.
 * It removes all plugin-related options and database tables.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

// Remove plugin options
delete_option('wmsd_fields');
delete_option('wmsd_options');
delete_option('wmsd_debug_log');
delete_option('wmsd_custom_field_1');
delete_option('wmsd_custom_field_2');

// Drop custom database tables if created
// global $wpdb;
// $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wmsd_custom_table");

