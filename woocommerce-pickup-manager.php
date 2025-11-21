<?php
/**
 * Plugin Name: WooCommerce Pickup Location Manager
 * Plugin URI: https://example.com
 * Description: Manage multiple pickup locations with weekly schedules, date overrides, and advance booking limits
 * Version: 2.4.1
 * Author: Alessandro Imbrenda
 * Text Domain: wc-pickup-manager
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 9.0
 */

if (!defined('ABSPATH')) exit;

define('WC_PICKUP_MANAGER_VERSION', '2.1.0');
define('WC_PICKUP_MANAGER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_PICKUP_MANAGER_PLUGIN_URL', plugin_dir_url(__FILE__));

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p>WooCommerce Pickup Location Manager requires WooCommerce to be installed and active.</p></div>';
    });
    return;
}

require_once WC_PICKUP_MANAGER_PLUGIN_DIR . 'includes/class-database.php';
require_once WC_PICKUP_MANAGER_PLUGIN_DIR . 'includes/class-admin.php';
require_once WC_PICKUP_MANAGER_PLUGIN_DIR . 'includes/class-checkout.php';
require_once WC_PICKUP_MANAGER_PLUGIN_DIR . 'includes/class-import-export.php';

class WC_Pickup_Manager {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('wc-pickup-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');
        WC_Pickup_Manager_Database::get_instance();

        if (is_admin()) {
            WC_Pickup_Manager_Admin::get_instance();
            WC_Pickup_Manager_Import_Export::get_instance();
        }

        WC_Pickup_Manager_Checkout::get_instance();
    }

    public function activate() {
        WC_Pickup_Manager_Database::create_tables();
    }
}

WC_Pickup_Manager::get_instance();
