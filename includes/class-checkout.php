<?php
if (!defined('ABSPATH')) exit;

class WC_Multidrop_Scheduler_Checkout {
    private static $instance = null;
    private $db;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->db = WC_Multidrop_Scheduler_Database::get_instance();

        // Get checkout position setting
        $position = get_option('wc_multidrop_scheduler_checkout_position', 'after_order_notes');

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
        add_filter('woocommerce_package_rates', array($this, 'add_pickup_shipping_method'), 10, 2);
    }

    private function is_pickup_enabled() {
        $enabled = get_option('wc_multidrop_scheduler_enabled', 'yes');
        if ($enabled !== 'yes') {
            return false;
        }

        $locations = $this->db->get_all_locations(true);
        return !empty($locations);
    }

    public function enqueue_frontend_assets() {
        if (is_checkout() && $this->is_pickup_enabled()) {
            wp_enqueue_style('flatpickr', WC_MULTIDROP_SCHEDULER_PLUGIN_URL . 'assets/lib/flatpickr/flatpickr.min.css', array(), '4.6.13');
            wp_enqueue_script('flatpickr', WC_MULTIDROP_SCHEDULER_PLUGIN_URL . 'assets/lib/flatpickr/flatpickr.min.js', array('jquery'), '4.6.13', true);
            wp_enqueue_style('wc-multidrop-scheduler-checkout', WC_MULTIDROP_SCHEDULER_PLUGIN_URL . 'assets/css/checkout.css', array(), WC_MULTIDROP_SCHEDULER_VERSION);
            wp_enqueue_script('wc-multidrop-scheduler-checkout', WC_MULTIDROP_SCHEDULER_PLUGIN_URL . 'assets/js/checkout.js', array('jquery', 'flatpickr'), WC_MULTIDROP_SCHEDULER_VERSION, true);
            wp_localize_script('wc-multidrop-scheduler-checkout', 'wcMultidropScheduler', array(
                'ajaxUrl' => admin_url('admin-ajax.php'), 
                'nonce' => wp_create_nonce('pickup_dates_nonce'),
                'viewMapText' => esc_html__('View on Map', 'multidrop-scheduler-for-woocommerce'),
                'locale' => get_locale(),
                'dateFormat' => get_option('date_format'),
                'startOfWeek' => get_option('start_of_week', 0),
                'placeholderSelectLocation' => esc_html__('Select a location first', 'multidrop-scheduler-for-woocommerce'),
                'placeholderSelectDate' => esc_html__('Click to select a date', 'multidrop-scheduler-for-woocommerce'),
                'errorLoadDates'    => esc_html__('Error loading available dates. Please try again.', 'multidrop-scheduler-for-woocommerce'),
            ));
        }
    }

    public function add_pickup_fields($checkout) {
        if (!$this->is_pickup_enabled()) {
            return;
        }

        $locations = $this->db->get_all_locations(true);
        if (empty($locations)) return;


        echo '<div id="pickup_location_fields"><h3>' . esc_html__('Pickup Information', 'multidrop-scheduler-for-woocommerce') . '</h3>';

        $location_options = array('' => esc_html__('Select a location', 'multidrop-scheduler-for-woocommerce'));
        foreach ($locations as $wc_multidrop_scheduler_location) {
            if ($wc_multidrop_scheduler_location->pickup_fee > 0) {
                $fee_text = ' (+' . wp_strip_all_tags( wc_price( $wc_multidrop_scheduler_location->pickup_fee ) ) . ')';
            } else {
                $fee_text = '';
            }

            $location_options[$wc_multidrop_scheduler_location->id] = $wc_multidrop_scheduler_location->name . $fee_text;
        }


        woocommerce_form_field('pickup_location_id', array(
                'type' => 'select',
                'class' => array('form-row-wide'),
                'label' => esc_html__('Pickup Location', 'multidrop-scheduler-for-woocommerce'),
                'required' => true,
                'options' => $location_options
            ), $checkout->get_value( 'pickup_location_id' )
        );  

        echo '<div id="pickup_location_details" style="display:none; margin: 15px 0; padding: 15px; background: #f9f9f9; border-left: 3px solid #2271b1;"></div>';

        woocommerce_form_field('pickup_date', array(
            'type' => 'text',
            'class' => array('form-row-wide'),
            'label' => esc_html__('Pickup Date', 'multidrop-scheduler-for-woocommerce'),
            'required' => true,
            'custom_attributes' => array(
                'readonly' => 'readonly',
                'placeholder' => esc_html__('Select a location first', 'multidrop-scheduler-for-woocommerce')
            )
        ), $checkout->get_value('pickup_date'));

        echo '<input type="hidden" id="pickup_locations_data" value="' . esc_attr(wp_json_encode($locations)) . '"></div>';
    }

    public function ajax_get_location_details() {
        check_ajax_referer('pickup_dates_nonce', 'nonce');
        $location_id = intval($_POST['location_id']);
        $wc_multidrop_scheduler_location = $this->db->get_location($location_id);

        if (!$wc_multidrop_scheduler_location) {
            wp_send_json_error('Invalid location');
        }

        $html = '<div>';
        $html .= '<p style="margin: 5px 0;"><strong>' . esc_html__('Address:', 'multidrop-scheduler-for-woocommerce') . '</strong><br>' . nl2br(esc_html($wc_multidrop_scheduler_location->address)) . '</p>';

        if (!empty($wc_multidrop_scheduler_location->map_link)) {
            $html .= '<p style="margin: 10px 0;"><a href="' . esc_url($wc_multidrop_scheduler_location->map_link) . '" target="_blank" class="button" style="font-size: 14px;">';
            $html .= '<span class="dashicons dashicons-location" style="vertical-align: middle;"></span> ';
            $html .= esc_html__('View on Map', 'multidrop-scheduler-for-woocommerce');
            $html .= '</a></p>';
        }
        $html .= '</div>';

        wp_send_json_success(array('html' => $html));
    }

    public function ajax_get_available_dates() {
        check_ajax_referer('pickup_dates_nonce', 'nonce');
        $location_id = intval($_POST['location_id']);
        $wc_multidrop_scheduler_location = $this->db->get_location($location_id);
        if (!$wc_multidrop_scheduler_location) wp_send_json_error('Invalid location');

        $start = new DateTime();
        $start->modify('+' . $wc_multidrop_scheduler_location->min_delay_hours . ' hours');
        $end = new DateTime();
        $end->modify('+' . $wc_multidrop_scheduler_location->max_advance_days . ' days');

        $available_dates = $this->db->get_available_dates($location_id, $start, $end);

        wp_send_json_success(array(
            'dates' => $available_dates,
            'minDate' => $start->format('Y-m-d'),
            'maxDate' => $end->format('Y-m-d')
        ));
    }

    public function validate_pickup_fields() {
        if (!$this->is_pickup_enabled()) {
            return;
        }

        if (empty($_POST['pickup_location_id'])) {
            wc_add_notice(esc_html__('Please select a pickup location.', 'multidrop-scheduler-for-woocommerce'), 'error');
            return;
        }

        

        if (empty($_POST['pickup_date'])) {
            wc_add_notice(esc_html__('Please select a pickup date.', 'multidrop-scheduler-for-woocommerce'), 'error');
            return;
        }

        $location_id = intval($_POST['pickup_location_id']);


        $wc_multidrop_scheduler_location = $this->db->get_active_location($location_id);
        if (!$wc_multidrop_scheduler_location) { 
            wc_add_notice(esc_html__('Invalid pickup location.', 'multidrop-scheduler-for-woocommerce'), 'error'); 
            return; 
        }


        $min_date = new DateTime(); 
        $min_date->modify('+' . $wc_multidrop_scheduler_location->min_delay_hours . ' hours');
        $max_date = new DateTime(); 
        $max_date->modify('+' . $wc_multidrop_scheduler_location->max_advance_days . ' days');
        
        try{
            $selected_date = new DateTime(sanitize_text_field($_POST['pickup_date']));
        } catch (Exception $e) {
            wc_add_notice(esc_html__('Invalid pickup date format.', 'multidrop-scheduler-for-woocommerce'), 'error');
            return;
        }

        if ($selected_date < $min_date) { 
            wc_add_notice(esc_html__('Selected pickup date is too soon.', 'multidrop-scheduler-for-woocommerce'), 'error'); 
            return; 
        }
        if ($selected_date > $max_date) { 
            wc_add_notice(esc_html__('Selected pickup date is too far in advance.', 'multidrop-scheduler-for-woocommerce'), 'error'); 
            return; 
        }

        $available = $this->db->get_available_dates($location_id, $selected_date, $selected_date);
        if (empty($available)) {
            wc_add_notice(esc_html__('Selected pickup date is not available.', 'multidrop-scheduler-for-woocommerce'), 'error');
        }

        WC()->session->set('multidrop_scheduler_validated_pickup_data', array(
            'location_id' => $location_id,
            'location_name' => $wc_multidrop_scheduler_location->name,
            'pickup_date' => $selected_date->format('Y-m-d'),
            'pickup_fee' => floatval($wc_multidrop_scheduler_location->pickup_fee),
            'validated_at' => current_time('mysql'),
            'is_valid' => true
        ));
    }

    public function save_pickup_fields($order_id) {
        $validated_data = WC()->session->get('multidrop_scheduler_validated_pickup_data');

        if (!$validated_data || !$validated_data['is_valid']) {
            wc_add_notice(esc_html__('Pickup information could not be verified. Please place your order again.', 'multidrop-scheduler-for-woocommerce'), 'error');
            update_post_meta($order_id, '_pickup_error', 'Pickup information validation failed at order save.');  
            error_log('Pickup Manager: Order ' . $order_id . ' rejected - invalid pickup session data');
            return;
        }


        $wc_multidrop_scheduler_location = $this->db->get_active_location($validated_data['location_id']);
        if ($wc_multidrop_scheduler_location) {
            update_post_meta($order_id, '_pickup_location_id', $validated_data['location_id']);
            update_post_meta($order_id, '_pickup_location_name', sanitize_text_field($wc_multidrop_scheduler_location->name));
            update_post_meta($order_id, '_pickup_location_address', sanitize_textarea_field($wc_multidrop_scheduler_location->address));
            update_post_meta($order_id, '_pickup_location_map_link', esc_url_raw($wc_multidrop_scheduler_location->map_link));
            update_post_meta($order_id, '_pickup_location_fee', floatval($wc_multidrop_scheduler_location->pickup_fee));
            update_post_meta($order_id, '_pickup_date', $validated_data['pickup_date']);    
        }

        // Clear session
        WC()->session->__unset('multidrop_scheduler_validated_pickup_data');
    }

    public function add_pickup_fee($cart) {
        if (is_admin() && !defined('DOING_AJAX')) return;
        if (!$this->is_pickup_enabled()) return;

        $post_data = isset($_POST['post_data']) ? $_POST : $_POST;
        if (isset($_POST['post_data'])) parse_str($_POST['post_data'], $post_data);

        if (!empty($post_data['pickup_location_id'])) {
            $wc_multidrop_scheduler_location = $this->db->get_location(intval($post_data['pickup_location_id']));
            if ($wc_multidrop_scheduler_location && $wc_multidrop_scheduler_location->is_active && $wc_multidrop_scheduler_location->pickup_fee > 0) {
                $cart->add_fee(
                    /* translators: %s: Location name */
                    sprintf(esc_html__('Pickup Fee - %s', 'multidrop-scheduler-for-woocommerce'), $wc_multidrop_scheduler_location->name), 
                    floatval($wc_multidrop_scheduler_location->pickup_fee)
                );
            }
        }
    }

    public function add_pickup_shipping_method($rates, $package) {
        if (!$this->is_pickup_enabled()) {
            return $rates;
        }

        $post_data = array();
        if (isset($_POST['post_data'])) {
            parse_str($_POST['post_data'], $post_data);
        } elseif (!empty($_POST['pickup_location_id'])) {
            $post_data = $_POST;
        }

        if (!empty($post_data['pickup_location_id'])) {
            $wc_multidrop_scheduler_location = $this->db->get_location(intval($post_data['pickup_location_id']));

            if ($wc_multidrop_scheduler_location && $wc_multidrop_scheduler_location->is_active) {
                $rates = array();

                $rate = new WC_Shipping_Rate(
                    'pickup_location_' . $wc_multidrop_scheduler_location->id,
                    /* translators: %s: Location name */
                    sprintf(esc_html__('Pickup at %s', 'multidrop-scheduler-for-woocommerce'), $wc_multidrop_scheduler_location->name),
                    floatval($wc_multidrop_scheduler_location->pickup_fee),
                    array(),
                    'pickup_location'
                );

                $rates['pickup_location_' . $wc_multidrop_scheduler_location->id] = $rate;
            }
        }

        return $rates;
    }

    public function display_pickup_info_admin($order) {
        $name = get_post_meta($order->get_id(), '_pickup_location_name', true);
        $address = get_post_meta($order->get_id(), '_pickup_location_address', true);
        $date = get_post_meta($order->get_id(), '_pickup_date', true);
        $map_link = get_post_meta($order->get_id(), '_pickup_location_map_link', true);
        $fee = get_post_meta($order->get_id(), '_pickup_location_fee', true);

        if ($name) {
            echo '<div style="margin-top:20px;padding:10px;background:#f9f9f9;border:1px solid #ddd;"><h3>' . esc_html__('Pickup Information', 'multidrop-scheduler-for-woocommerce') . '</h3>';
            echo '<p><strong>' . esc_html__('Location:', 'multidrop-scheduler-for-woocommerce') . '</strong> ' . esc_html($name) . '</p>';
            echo '<p><strong>' . esc_html__('Address:', 'multidrop-scheduler-for-woocommerce') . '</strong><br>' . nl2br(esc_html($address)) . '</p>';
            if (!empty($map_link)) {
                echo '<p><a href="' . esc_url($map_link) . '" target="_blank" class="button button-small">' . esc_html__('View on Map', 'multidrop-scheduler-for-woocommerce') . '</a></p>';
            }
            if ($fee > 0) {
                echo '<p><strong>' . esc_html__('Pickup Fee:', 'multidrop-scheduler-for-woocommerce') . '</strong> ' . wp_kses_post(wc_price($fee)) . '</p>';
            }
            $date_obj = new DateTime($date);
            $formatted_date = date_i18n(get_option('date_format'), $date_obj->getTimestamp());
            echo '<p><strong>' . esc_html__('Pickup Date:', 'multidrop-scheduler-for-woocommerce') . '</strong> ' . esc_html($formatted_date) . '</p></div>';
        }
    }

    public function display_pickup_info_email($order, $sent_to_admin, $plain_text, $email) {
        $name = get_post_meta($order->get_id(), '_pickup_location_name', true);
        $address = get_post_meta($order->get_id(), '_pickup_location_address', true);
        $date = get_post_meta($order->get_id(), '_pickup_date', true);
        $map_link = get_post_meta($order->get_id(), '_pickup_location_map_link', true);
        $fee = get_post_meta($order->get_id(), '_pickup_location_fee', true);

        if ($name) {
            $date_obj = new DateTime($date);
            $formatted_date = date_i18n(get_option('date_format'), $date_obj->getTimestamp());

            if ($plain_text) {
                echo "\n" . esc_html__('PICKUP INFORMATION', 'multidrop-scheduler-for-woocommerce') . "\n";
                echo esc_html__('Location:', 'multidrop-scheduler-for-woocommerce') . ' ' . esc_html($name) . "\n";
                echo esc_html__('Address:', 'multidrop-scheduler-for-woocommerce') . ' ' . esc_html(str_replace('<br>', ', ', $address)) . "\n";
                if (!empty($map_link)) {
                    echo esc_html__('Map:', 'multidrop-scheduler-for-woocommerce') . ' ' . esc_url($map_link) . "\n";
                }
                if ($fee > 0) {
                    echo esc_html__('Pickup Fee:', 'multidrop-scheduler-for-woocommerce') . ' ' . esc_html(wp_strip_all_tags(wc_price($fee))) . "\n";
                }
                echo esc_html__('Pickup Date:', 'multidrop-scheduler-for-woocommerce') . ' ' . esc_html($formatted_date) . "\n";
            } else {
                echo '<h2>' . esc_html__('Pickup Information', 'multidrop-scheduler-for-woocommerce') . '</h2>';
                echo '<p><strong>' . esc_html__('Location:', 'multidrop-scheduler-for-woocommerce') . '</strong> ' . esc_html($name) . '</p>';
                echo '<p><strong>' . esc_html__('Address:', 'multidrop-scheduler-for-woocommerce') . '</strong><br>' . nl2br(esc_html($address)) . '</p>';
                if (!empty($map_link)) {
                    echo '<p><a href="' . esc_url($map_link) . '" style="color:#2271b1;">' . esc_html__('View on Map', 'multidrop-scheduler-for-woocommerce') . '</a></p>';
                }
                if ($fee > 0) {
                    echo '<p><strong>' . esc_html__('Pickup Fee:', 'multidrop-scheduler-for-woocommerce') . '</strong> ' . wp_kses_post(wc_price($fee)) . '</p>';
                }
                echo '<p><strong>' . esc_html__('Pickup Date:', 'multidrop-scheduler-for-woocommerce') . '</strong> ' . esc_html($formatted_date) . '</p>';
            }
        }
    }
}
