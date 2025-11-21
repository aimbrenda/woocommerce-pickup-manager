<?php
/**
 * Uninstall WooCommerce Pickup Location Manager
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
$wpdb->query("DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key IN ('_pickup_location_id', '_pickup_location_name', '_pickup_location_address', '_pickup_date')");

// Optional: Clear any cached data
wp_cache_flush();

// Log uninstallation (optional - remove if you don't want logging)
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Pickup Location Manager: Plugin data removed successfully');
}
