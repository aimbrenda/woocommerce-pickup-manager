<?php
/**
 * Uninstall WooCommerce MultiDrop Scheduler for WooCommerce
 * 
 * This file runs when the plugin is DELETED (not just deactivated)
 * It removes all plugin data from the database
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Delete database tables
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pickup_date_overrides");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pickup_locations");

// Delete all order meta data related to pickup
$wpdb->query("DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key IN ('_pickup_location_id', '_pickup_location_name', '_pickup_location_address', '_pickup_location_map_link', '_pickup_location_fee', '_pickup_date', '_pickup_error', '_fulfillment_type', '_fulfillment_location_id', '_fulfillment_location_name', '_delivery_location_id', '_delivery_location_name', '_delivery_processing_date', '_delivery_note', '_delivery_fee')");

// Optional: Clear any cached data
wp_cache_flush();

// Log uninstallation (optional - remove if you don't want logging)
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('MultiDrop Scheduler for WooCommerce: Plugin data removed successfully');
}
