<?php
/**
 * Plugin Name: MultiDrop Scheduler for WooCommerce
 * Text Domain: multidrop-scheduler-for-woocommerce
 * Domain Path: /languages
 * Plugin URI: https://github.com/aimbrenda/woocommerce-pickup-manager
 * Description: Manage multiple pickup locations with weekly schedules, date overrides, and advance booking limits
 * Version: 2.4.4
 * Author: Alessandro Imbrenda
 * Text Domain: multidrop-scheduler-for-woocommerce
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * WC requires at least: 6.0
 * WC tested up to: 9.0
 */

if (!defined('ABSPATH')) exit;

define('WC_MULTIDROP_SCHEDULER_VERSION', '2.4.4');
define('WC_MULTIDROP_SCHEDULER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_MULTIDROP_SCHEDULER_PLUGIN_URL', plugin_dir_url(__FILE__));

$wc_multidrop_scheduler_active_plugins = apply_filters(
    'wc_multidrop_scheduler_active_plugins',
    (array) get_option( 'wc_multidrop_scheduler_active_plugins', get_option( 'active_plugins', array() ) )
);

if ( ! in_array( 'woocommerce/woocommerce.php', $wc_multidrop_scheduler_active_plugins, true ) ) {
    add_action( 'admin_notices', function () {
        echo '<div class="error"><p>MultiDrop Scheduler for WooCommerce requires WooCommerce to be installed and active.</p></div>';
    } );
    return;
}

require_once WC_MULTIDROP_SCHEDULER_PLUGIN_DIR . 'includes/class-database.php';
require_once WC_MULTIDROP_SCHEDULER_PLUGIN_DIR . 'includes/class-admin.php';
require_once WC_MULTIDROP_SCHEDULER_PLUGIN_DIR . 'includes/class-checkout.php';
require_once WC_MULTIDROP_SCHEDULER_PLUGIN_DIR . 'includes/class-import-export.php';

class WC_Multidrop_Scheduler {
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
        WC_Multidrop_Scheduler_Database::get_instance();
        WC_Multidrop_Scheduler_Database::create_tables();

        if (is_admin()) {
            WC_Multidrop_Scheduler_Admin::get_instance();
            WC_Multidrop_Scheduler_Import_Export::get_instance();
        }

        WC_Multidrop_Scheduler_Checkout::get_instance();
    }

    public function activate() {
        WC_Multidrop_Scheduler_Database::create_tables();
    }
}

WC_Multidrop_Scheduler::get_instance();


function wc_multidrop_scheduler_show_pickup_on_thankyou( $order_id ) {
    if ( ! $order_id ) {
        return;
    }

    $fulfillment_type = get_post_meta( $order_id, '_fulfillment_type', true );

    if ( 'delivery' === $fulfillment_type ) {
        $delivery_note = get_post_meta( $order_id, '_delivery_note', true );
        $processing_date = get_post_meta( $order_id, '_delivery_processing_date', true );

        if ( ! $delivery_note && ! $processing_date ) {
            return;
        }

        echo '<section class="woocommerce-pickup-summary">';
        echo '<h2>' . esc_html__( 'Delivery information', 'multidrop-scheduler-for-woocommerce' ) . '</h2>';

        if ( $delivery_note ) {
            echo '<p>' . esc_html( $delivery_note ) . '</p>';
        }

        if ( $processing_date ) {
            try {
                $date_obj = new DateTime( $processing_date );
                $formatted_processing_date = date_i18n( get_option( 'date_format' ), $date_obj->getTimestamp() );
                echo '<p><strong>' . esc_html__( 'Next possible delivery day:', 'multidrop-scheduler-for-woocommerce' ) . '</strong> ' . esc_html( $formatted_processing_date ) . '</p>';
            } catch ( Exception $e ) {
                // Ignore malformed dates to avoid breaking thank-you rendering.
            }
        }

        echo '</section>';
        return;
    }

    $name    = get_post_meta( $order_id, '_pickup_location_name', true );
    $address = get_post_meta( $order_id, '_pickup_location_address', true );
    $date    = get_post_meta( $order_id, '_pickup_date', true );
    $map     = get_post_meta( $order_id, '_pickup_location_map_link', true );

    if ( ! $name || ! $date ) {
        return;
    }

    $date_obj       = new DateTime( $date );
    $formatted_date = date_i18n( get_option( 'date_format' ), $date_obj->getTimestamp() );

    echo '<section class="woocommerce-pickup-summary">';
    echo '<h2>' . esc_html__( 'Pickup information', 'multidrop-scheduler-for-woocommerce' ) . '</h2>';
    echo '<p><strong>' . esc_html__( 'Location:', 'multidrop-scheduler-for-woocommerce' ) . '</strong> ' . esc_html( $name ) . '</p>';

    if ( $address ) {
        echo '<p><strong>' . esc_html__( 'Address:', 'multidrop-scheduler-for-woocommerce' ) . '</strong><br>' . nl2br( esc_html( $address ) ) . '</p>';
    }

    echo '<p><strong>' . esc_html__( 'Pickup date:', 'multidrop-scheduler-for-woocommerce' ) . '</strong> ' . esc_html( $formatted_date ) . '</p>';

    if ( $map ) {
        echo '<p><a href="' . esc_url( $map ) . '" target="_blank" rel="noopener noreferrer">' .
             esc_html__( 'View on map', 'multidrop-scheduler-for-woocommerce' ) . '</a></p>';
    }

    echo '</section>';
}



add_action( 'woocommerce_thankyou', 'wc_multidrop_scheduler_show_pickup_on_thankyou', 20 );


add_filter( 'gettext', 'wc_multidrop_scheduler_change_shipping_to_text_on_cart', 9999, 3 );
function wc_multidrop_scheduler_change_shipping_to_text_on_cart( $translated, $text, $domain ) {
    // Only affect frontend & WooCommerce strings
    if ( is_admin() || 'woocommerce' !== $domain ) {
        return $translated;
    }

    // Only on cart ("winkelmand") page
    if ( ! is_cart() ) {
        return $translated;
    }

    
    if ( 'Shipping to %s.' === $text ) {
        // Your custom message
        $translated = esc_html__( 'Shipping to: next step.', 'multidrop-scheduler-for-woocommerce' );
    }

    return $translated;
}

