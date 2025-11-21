<?php
if (!defined('ABSPATH')) exit;

class WC_Pickup_Manager_Checkout {
    private static $instance = null;
    private $db;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->db = WC_Pickup_Manager_Database::get_instance();

        // Get checkout position setting
        $position = get_option('wc_pickup_manager_checkout_position', 'after_order_notes');

        // Map position to correct WooCommerce hook
        $hook_map = array(
            'before_customer_details' => 'woocommerce_before_checkout_billing_form',
            'after_customer_details' => 'woocommerce_after_checkout_billing_form',
            'before_order_notes' => 'woocommerce_before_order_notes',
            'after_order_notes' => 'woocommerce_after_order_notes',
            'review_order_before_submit' => 'woocommerce_review_order_before_submit'
        );

        $hook = isset($hook_map[$position]) ? $hook_map[$position] : 'woocommerce_after_order_notes';

        add_action($hook, array($this, 'add_pickup_fields'));
        add_action('woocommerce_checkout_process', array($this, 'validate_pickup_fields'));
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_pickup_fields'));
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_pickup_info_admin'));
        add_action('woocommerce_email_after_order_table', array($this, 'display_pickup_info_email'), 10, 4);
        add_action('woocommerce_cart_calculate_fees', array($this, 'add_pickup_fee'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('wp_ajax_get_available_pickup_dates', array($this, 'ajax_get_available_dates'));
        add_action('wp_ajax_nopriv_get_available_pickup_dates', array($this, 'ajax_get_available_dates'));
        add_action('wp_ajax_get_location_details', array($this, 'ajax_get_location_details'));
        add_action('wp_ajax_nopriv_get_location_details', array($this, 'ajax_get_location_details'));
    }

    private function is_pickup_enabled() {
        // Check if pickup is globally enabled
        $enabled = get_option('wc_pickup_manager_enabled', 'yes');
        if ($enabled !== 'yes') {
            return false;
        }

        // Check if there are any active locations
        $locations = $this->db->get_all_locations(true);
        return !empty($locations);
    }

    public function enqueue_frontend_assets() {
        if (is_checkout() && $this->is_pickup_enabled()) {
            wp_enqueue_style('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
            wp_enqueue_script('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr');
            wp_enqueue_style('wc-pickup-manager-checkout', WC_PICKUP_MANAGER_PLUGIN_URL . 'assets/css/checkout.css');
            wp_enqueue_script('wc-pickup-manager-checkout', WC_PICKUP_MANAGER_PLUGIN_URL . 'assets/js/checkout.js', array('jquery', 'flatpickr'), false, true);
            wp_localize_script('wc-pickup-manager-checkout', 'wcPickupManager', array(
                'ajaxUrl' => admin_url('admin-ajax.php'), 
                'nonce' => wp_create_nonce('pickup_dates_nonce'),
                'viewMapText' => __('View on Map', 'wc-pickup-manager')
            ));
        }
    }

    public function add_pickup_fields($checkout) {
        // Check if pickup is enabled
        if (!$this->is_pickup_enabled()) {
            return;
        }

        $locations = $this->db->get_all_locations(true);
        if (empty($locations)) return;

        echo '<div id="pickup_location_fields"><h3>' . __('Pickup Information', 'wc-pickup-manager') . '</h3>';

        $location_options = array('' => __('Select a location', 'wc-pickup-manager'));
        foreach ($locations as $location) {
            // Show fee in dropdown only if > 0
            if ($location->pickup_fee > 0) {
                $fee_text = ' (+' . wc_price($location->pickup_fee) . ')';
            } else {
                $fee_text = '';
            }
            $location_options[$location->id] = $location->name . $fee_text;
        }

        woocommerce_form_field('pickup_location_id', array(
            'type' => 'select',
            'class' => array('form-row-wide'),
            'label' => __('Pickup Location', 'wc-pickup-manager'),
            'required' => true,
            'options' => $location_options
        ), $checkout->get_value('pickup_location_id'));

        echo '<div id="pickup_location_details" style="display:none; margin: 15px 0; padding: 15px; background: #f9f9f9; border-left: 3px solid #2271b1;"></div>';

        woocommerce_form_field('pickup_date', array(
            'type' => 'text',
            'class' => array('form-row-wide'),
            'label' => __('Pickup Date', 'wc-pickup-manager'),
            'required' => true,
            'custom_attributes' => array(
                'readonly' => 'readonly',
                'placeholder' => __('Select a location first', 'wc-pickup-manager')
            )
        ), $checkout->get_value('pickup_date'));

        echo '<input type="hidden" id="pickup_locations_data" value=\'' . esc_attr(wp_json_encode($locations)) . '\'></div>';
    }

    public function ajax_get_location_details() {
        check_ajax_referer('pickup_dates_nonce', 'nonce');
        $location_id = intval($_POST['location_id']);
        $location = $this->db->get_location($location_id);

        if (!$location) {
            wp_send_json_error('Invalid location');
        }

        $html = '<div>';
        $html .= '<p style="margin: 5px 0;"><strong>' . __('Address:', 'wc-pickup-manager') . '</strong><br>' . nl2br(esc_html($location->address)) . '</p>';

        if (!empty($location->map_link)) {
            $html .= '<p style="margin: 10px 0;"><a href="' . esc_url($location->map_link) . '" target="_blank" class="button" style="font-size: 14px;">';
            $html .= '<span class="dashicons dashicons-location" style="vertical-align: middle;"></span> ';
            $html .= __('View on Map', 'wc-pickup-manager');
            $html .= '</a></p>';
        }
        $html .= '</div>';

        wp_send_json_success(array('html' => $html));
    }

    public function ajax_get_available_dates() {
        check_ajax_referer('pickup_dates_nonce', 'nonce');
        $location_id = intval($_POST['location_id']);
        $location = $this->db->get_location($location_id);
        if (!$location) wp_send_json_error('Invalid location');

        $start = new DateTime();
        $start->modify('+' . $location->min_delay_hours . ' hours');
        $end = new DateTime();
        $end->modify('+' . $location->max_advance_days . ' days');

        $available_dates = $this->db->get_available_dates($location_id, $start->format('Y-m-d'), $end->format('Y-m-d'));

        wp_send_json_success(array(
            'dates' => $available_dates,
            'minDate' => $start->format('Y-m-d'),
            'maxDate' => $end->format('Y-m-d')
        ));
    }

    public function validate_pickup_fields() {
        // Only validate if pickup is enabled
        if (!$this->is_pickup_enabled()) {
            return;
        }

        if (empty($_POST['pickup_location_id'])) {
            wc_add_notice(__('Please select a pickup location.', 'wc-pickup-manager'), 'error');
        }
        if (empty($_POST['pickup_date'])) {
            wc_add_notice(__('Please select a pickup date.', 'wc-pickup-manager'), 'error');
        }

        if (!empty($_POST['pickup_location_id']) && !empty($_POST['pickup_date'])) {
            $location = $this->db->get_location(intval($_POST['pickup_location_id']));
            if (!$location) { 
                wc_add_notice(__('Invalid pickup location.', 'wc-pickup-manager'), 'error'); 
                return; 
            }

            // Check if location is active
            if (!$location->is_active) {
                wc_add_notice(__('Selected location is not available.', 'wc-pickup-manager'), 'error');
                return;
            }

            $min_date = new DateTime(); 
            $min_date->modify('+' . $location->min_delay_hours . ' hours');
            $max_date = new DateTime(); 
            $max_date->modify('+' . $location->max_advance_days . ' days');
            $selected_date = new DateTime(sanitize_text_field($_POST['pickup_date']));

            if ($selected_date < $min_date) { 
                wc_add_notice(__('Selected pickup date is too soon.', 'wc-pickup-manager'), 'error'); 
                return; 
            }
            if ($selected_date > $max_date) { 
                wc_add_notice(__('Selected pickup date is too far in advance.', 'wc-pickup-manager'), 'error'); 
                return; 
            }

            $available = $this->db->get_available_dates(intval($_POST['pickup_location_id']), $_POST['pickup_date'], $_POST['pickup_date']);
            if (empty($available)) {
                wc_add_notice(__('Selected pickup date is not available.', 'wc-pickup-manager'), 'error');
            }
        }
    }

    public function save_pickup_fields($order_id) {
        if (!empty($_POST['pickup_location_id'])) {
            $location = $this->db->get_location(intval($_POST['pickup_location_id']));
            if ($location) {
                update_post_meta($order_id, '_pickup_location_id', intval($_POST['pickup_location_id']));
                update_post_meta($order_id, '_pickup_location_name', sanitize_text_field($location->name));
                update_post_meta($order_id, '_pickup_location_address', sanitize_textarea_field($location->address));
                update_post_meta($order_id, '_pickup_location_map_link', esc_url_raw($location->map_link));
                update_post_meta($order_id, '_pickup_location_fee', floatval($location->pickup_fee));
            }
        }
        if (!empty($_POST['pickup_date'])) {
            update_post_meta($order_id, '_pickup_date', sanitize_text_field($_POST['pickup_date']));
        }
    }

    public function add_pickup_fee($cart) {
        if (is_admin() && !defined('DOING_AJAX')) return;
        if (!$this->is_pickup_enabled()) return;

        $post_data = isset($_POST['post_data']) ? $_POST : $_POST;
        if (isset($_POST['post_data'])) parse_str($_POST['post_data'], $post_data);

        if (!empty($post_data['pickup_location_id'])) {
            $location = $this->db->get_location(intval($post_data['pickup_location_id']));
            if ($location && $location->is_active && $location->pickup_fee > 0) {
                // Use raw fee value, not formatted HTML
                $cart->add_fee(
                    sprintf(__('Pickup Fee - %s', 'wc-pickup-manager'), $location->name), 
                    floatval($location->pickup_fee)
                );
            }
        }
    }

    public function display_pickup_info_admin($order) {
        $name = get_post_meta($order->get_id(), '_pickup_location_name', true);
        $address = get_post_meta($order->get_id(), '_pickup_location_address', true);
        $date = get_post_meta($order->get_id(), '_pickup_date', true);
        $map_link = get_post_meta($order->get_id(), '_pickup_location_map_link', true);
        $fee = get_post_meta($order->get_id(), '_pickup_location_fee', true);

        if ($name) {
            echo '<div style="margin-top:20px;padding:10px;background:#f9f9f9;border:1px solid #ddd;"><h3>' . __('Pickup Information', 'wc-pickup-manager') . '</h3>';
            echo '<p><strong>' . __('Location:', 'wc-pickup-manager') . '</strong> ' . esc_html($name) . '</p>';
            echo '<p><strong>' . __('Address:', 'wc-pickup-manager') . '</strong><br>' . nl2br(esc_html($address)) . '</p>';
            if (!empty($map_link)) {
                echo '<p><a href="' . esc_url($map_link) . '" target="_blank" class="button button-small">' . __('View on Map', 'wc-pickup-manager') . '</a></p>';
            }
            if ($fee > 0) {
                echo '<p><strong>' . __('Pickup Fee:', 'wc-pickup-manager') . '</strong> ' . wc_price($fee) . '</p>';
            }
            echo '<p><strong>' . __('Pickup Date:', 'wc-pickup-manager') . '</strong> ' . esc_html(date_i18n(get_option('date_format'), strtotime($date))) . '</p></div>';
        }
    }

    public function display_pickup_info_email($order, $sent_to_admin, $plain_text, $email) {
        $name = get_post_meta($order->get_id(), '_pickup_location_name', true);
        $address = get_post_meta($order->get_id(), '_pickup_location_address', true);
        $date = get_post_meta($order->get_id(), '_pickup_date', true);
        $map_link = get_post_meta($order->get_id(), '_pickup_location_map_link', true);
        $fee = get_post_meta($order->get_id(), '_pickup_location_fee', true);

        if ($name) {
            if ($plain_text) {
                echo "\nPICKUP INFORMATION\nLocation: $name\nAddress: " . str_replace('<br>', ', ', $address) . "\n";
                if (!empty($map_link)) echo "Map: " . $map_link . "\n";
                if ($fee > 0) echo "Pickup Fee: " . strip_tags(wc_price($fee)) . "\n";
                echo "Pickup Date: " . date_i18n(get_option('date_format'), strtotime($date)) . "\n";
            } else {
                echo '<h2>' . __('Pickup Information', 'wc-pickup-manager') . '</h2>';
                echo '<p><strong>' . __('Location:', 'wc-pickup-manager') . '</strong> ' . esc_html($name) . '</p>';
                echo '<p><strong>' . __('Address:', 'wc-pickup-manager') . '</strong><br>' . nl2br(esc_html($address)) . '</p>';
                if (!empty($map_link)) {
                    echo '<p><a href="' . esc_url($map_link) . '" style="color:#2271b1;">' . __('View on Map', 'wc-pickup-manager') . '</a></p>';
                }
                if ($fee > 0) {
                    echo '<p><strong>' . __('Pickup Fee:', 'wc-pickup-manager') . '</strong> ' . wc_price($fee) . '</p>';
                }
                echo '<p><strong>' . __('Pickup Date:', 'wc-pickup-manager') . '</strong> ' . esc_html(date_i18n(get_option('date_format'), strtotime($date))) . '</p>';
            }
        }
    }
}
