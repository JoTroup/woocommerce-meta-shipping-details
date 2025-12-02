<?php
/**
 * This file is executed automatically when the plugin is deleted.
 * It removes all plugin-related options and database tables.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

// Remove plugin options
delete_option('{plugin_prefix}_fields');
delete_option('{plugin_prefix}_options');
delete_option('{plugin_prefix}_debug_log');
delete_option('{plugin_prefix}_custom_field_1');
delete_option('{plugin_prefix}_custom_field_2');

// Drop custom database tables if created
// global $wpdb;
// $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{plugin_prefix}_custom_table");

