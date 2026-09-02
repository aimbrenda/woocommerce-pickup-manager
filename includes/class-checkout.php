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

        // Disable WooCommerce shipping cache (without debug mode)
		add_filter( 'woocommerce_cart_shipping_packages', function( $packages ) {
			foreach ( $packages as &$package ) {
				$package['rate_cache'] = wp_rand();
			}
			return $packages;
		}, 100 );

        $position = get_option('wc_multidrop_scheduler_checkout_position', 'after_order_notes');
        $hook_map = array(
            'before_customer_details' => 'woocommerce_before_checkout_billing_form',
            'after_customer_details'  => 'woocommerce_after_checkout_billing_form',
            'before_order_notes'      => 'woocommerce_before_order_notes',
            'after_order_notes'       => 'woocommerce_after_order_notes',
            'review_order_before_submit' => 'woocommerce_review_order_before_submit'
        );

        $hook = isset($hook_map[$position]) ? $hook_map[$position] : 'woocommerce_after_order_notes';

        add_action($hook, array($this, 'add_pickup_fields'));
        add_action('woocommerce_checkout_process', array($this, 'validate_pickup_fields'));
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_pickup_fields'));
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_pickup_info_admin'));
        add_action('woocommerce_email_after_order_table', array($this, 'display_pickup_info_email'), 10, 4);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('wp_ajax_get_available_pickup_dates', array($this, 'ajax_get_available_dates'));
        add_action('wp_ajax_nopriv_get_available_pickup_dates', array($this, 'ajax_get_available_dates'));
        add_action('wp_ajax_get_location_details', array($this, 'ajax_get_location_details'));
        add_action('wp_ajax_nopriv_get_location_details', array($this, 'ajax_get_location_details'));
        
        // Standard shipping filter (classic checkout)
        add_filter('woocommerce_package_rates', array($this, 'add_pickup_shipping_method'), 999, 2);
        
        // Store API filter (blocks checkout)
        add_filter('woocommerce_store_api_packages', array($this, 'modify_store_api_packages'), 999, 1);
    }

    // Modify packages for Store API (blocks checkout)
    public function modify_store_api_packages($packages) {
        $location_id = 0;
        
        if (!empty($_POST['pickup_location_id'])) {
            $location_id = absint($_POST['pickup_location_id']);
        } elseif (!empty($_POST['extension_data']['pickup_location_id'])) {
            $location_id = absint($_POST['extension_data']['pickup_location_id']);
        } else {
            return $packages;
        }

        $location = $this->db->get_location($location_id);
        if (!$location || empty($location->is_active)) {
            return $packages;
        }

        $fulfillment_type = $this->get_fulfillment_type($location);
        $label = $fulfillment_type === 'delivery'
            ? sprintf(esc_html__('Delivery via %s', 'multidrop-scheduler-for-woocommerce'), $location->name)
            : sprintf(esc_html__('Pickup at %s', 'multidrop-scheduler-for-woocommerce'), $location->name);

        $rate_id = $fulfillment_type . '_location_' . $location->id;
        $cost = floatval($location->pickup_fee);

        // Clear all existing rates and add only our custom rate
        foreach ($packages as &$package) {
            $package['rates'] = array(
                $rate_id => new WC_Shipping_Rate($rate_id, $label, $cost, array(), $fulfillment_type . '_location')
            );
        }

        return $packages;
    }

    private function is_pickup_enabled() {
        $enabled = get_option('wc_multidrop_scheduler_enabled', 'yes');
        if ($enabled !== 'yes') {
            return false;
        }

        $locations = $this->db->get_all_locations(true);
        return !empty($locations);
    }

    private function get_fulfillment_type($location) {
        return (isset($location->fulfillment_type) && $location->fulfillment_type === 'delivery') ? 'delivery' : 'pickup';
    }

    private function is_delivery_location($location) {
        return $this->get_fulfillment_type($location) === 'delivery';
    }

    private function get_next_available_date_for_location($location) {
        if (!$location) {
            return '';
        }

        $start = new DateTime();
        $start->modify('+' . intval($location->min_delay_hours) . ' hours');
        $end = new DateTime();
        $end->modify('+' . intval($location->max_advance_days) . ' days');

        $available_dates = $this->db->get_available_dates($location->id, $start, $end);
        return !empty($available_dates) ? $available_dates[0] : '';
    }

    private function format_localized_date($date_string) {
        if (empty($date_string)) {
            return '';
        }

        try {
            $date_obj = new DateTime($date_string);
        } catch (Exception $e) {
            return '';
        }

        return date_i18n(get_option('date_format'), $date_obj->getTimestamp());
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
                'placeholderSelectLocation' => esc_html__('Select an option first', 'multidrop-scheduler-for-woocommerce'),
                'placeholderSelectDate' => esc_html__('Click to select a date', 'multidrop-scheduler-for-woocommerce'),
                'errorLoadDates' => esc_html__('Error loading available dates. Please try again.', 'multidrop-scheduler-for-woocommerce'),
                'deliveryNoteTemplate' => esc_html__('Your order will be processed on %s based on preparation delay and working days.', 'multidrop-scheduler-for-woocommerce'),
                'deliveryNoDate' => esc_html__('No delivery day is currently available for this option.', 'multidrop-scheduler-for-woocommerce')
            ));
        }
    }

    public function add_pickup_fields($checkout) {
        if (!$this->is_pickup_enabled()) {
            return;
        }

        $locations = $this->db->get_all_locations(true);
        if (empty($locations)) {
            return;
        }

        echo '<div id="pickup_location_fields"><h3>' . esc_html__('Fulfillment Information', 'multidrop-scheduler-for-woocommerce') . '</h3>';

        $location_options = array('' => esc_html__('Select pickup or delivery option', 'multidrop-scheduler-for-woocommerce'));
        foreach ($locations as $wc_multidrop_scheduler_location) {
            $type_prefix = $this->is_delivery_location($wc_multidrop_scheduler_location)
                ? esc_html__('Delivery', 'multidrop-scheduler-for-woocommerce')
                : esc_html__('Pickup', 'multidrop-scheduler-for-woocommerce');

            $fee_text = $wc_multidrop_scheduler_location->pickup_fee > 0
                ? ' (+' . wp_strip_all_tags(wc_price($wc_multidrop_scheduler_location->pickup_fee)) . ')'
                : '';

            $location_options[$wc_multidrop_scheduler_location->id] = $type_prefix . ' - ' . $wc_multidrop_scheduler_location->name . $fee_text;
        }

        woocommerce_form_field('pickup_location_id', array(
            'type' => 'select',
            'class' => array('form-row-wide'),
            'label' => esc_html__('Pickup / Delivery Option', 'multidrop-scheduler-for-woocommerce'),
            'required' => true,
            'options' => $location_options
        ), $checkout->get_value('pickup_location_id'));

        echo '<div id="pickup_location_details" style="display:none; margin: 15px 0; padding: 15px; background: #f9f9f9; border-left: 3px solid #2271b1;"></div>';
        echo '<div id="delivery_note_details" style="display:none; margin: 15px 0; padding: 15px; background: #f9f9f9; border-left: 3px solid #2271b1;"></div>';

        woocommerce_form_field('pickup_date', array(
            'type' => 'text',
            'class' => array('form-row-wide'),
            'label' => esc_html__('Pickup Date', 'multidrop-scheduler-for-woocommerce'),
            'required' => true,
            'custom_attributes' => array(
                'readonly' => 'readonly',
                'placeholder' => esc_html__('Select an option first', 'multidrop-scheduler-for-woocommerce')
            )
        ), $checkout->get_value('pickup_date'));

        echo '<input type="hidden" id="pickup_locations_data" value="' . esc_attr(wp_json_encode($locations)) . '"></div>';
    }

    public function ajax_get_location_details() {
        check_ajax_referer('pickup_dates_nonce', 'nonce');
        $location_id = isset($_POST['location_id']) ? intval($_POST['location_id']) : 0;
        $wc_multidrop_scheduler_location = $this->db->get_location($location_id);

        if (!$wc_multidrop_scheduler_location) {
            wp_send_json_error('Invalid location');
        }

        $fulfillment_type = $this->get_fulfillment_type($wc_multidrop_scheduler_location);

        if ($fulfillment_type === 'delivery') {
            $processing_date = $this->get_next_available_date_for_location($wc_multidrop_scheduler_location);
            wp_send_json_success(array(
                'fulfillmentType' => 'delivery',
                'processingDate' => $processing_date,
                'processingDateFormatted' => $this->format_localized_date($processing_date),
                'html' => ''
            ));
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

        wp_send_json_success(array(
            'fulfillmentType' => 'pickup',
            'html' => $html
        ));
    }

    public function ajax_get_available_dates() {
        check_ajax_referer('pickup_dates_nonce', 'nonce');
        $location_id = isset($_POST['location_id']) ? intval($_POST['location_id']) : 0;
        $wc_multidrop_scheduler_location = $this->db->get_location($location_id);
        if (!$wc_multidrop_scheduler_location) {
            wp_send_json_error('Invalid location');
        }

        $start = new DateTime();
        $start->modify('+' . intval($wc_multidrop_scheduler_location->min_delay_hours) . ' hours');
        $end = new DateTime();
        $end->modify('+' . intval($wc_multidrop_scheduler_location->max_advance_days) . ' days');

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
            wc_add_notice(esc_html__('Please select a pickup or delivery option.', 'multidrop-scheduler-for-woocommerce'), 'error');
            return;
        }

        $location_id = intval($_POST['pickup_location_id']);
        $wc_multidrop_scheduler_location = $this->db->get_active_location($location_id);
        if (!$wc_multidrop_scheduler_location) {
            wc_add_notice(esc_html__('Invalid fulfillment option.', 'multidrop-scheduler-for-woocommerce'), 'error');
            return;
        }

        $fulfillment_type = $this->get_fulfillment_type($wc_multidrop_scheduler_location);

        if ($fulfillment_type === 'delivery') {
            $processing_date = $this->get_next_available_date_for_location($wc_multidrop_scheduler_location);
            if (empty($processing_date)) {
                wc_add_notice(esc_html__('No delivery day is currently available for this option.', 'multidrop-scheduler-for-woocommerce'), 'error');
                return;
            }

            WC()->session->set('multidrop_scheduler_validated_pickup_data', array(
                'location_id' => $location_id,
                'location_name' => $wc_multidrop_scheduler_location->name,
                'fulfillment_type' => 'delivery',
                'processing_date' => $processing_date,
                'pickup_fee' => floatval($wc_multidrop_scheduler_location->pickup_fee),
                'validated_at' => current_time('mysql'),
                'is_valid' => true
            ));

            return;
        }

        if (empty($_POST['pickup_date'])) {
            wc_add_notice(esc_html__('Please select a pickup date.', 'multidrop-scheduler-for-woocommerce'), 'error');
            return;
        }

        $min_date = new DateTime();
        $min_date->modify('+' . intval($wc_multidrop_scheduler_location->min_delay_hours) . ' hours');
        $max_date = new DateTime();
        $max_date->modify('+' . intval($wc_multidrop_scheduler_location->max_advance_days) . ' days');

        try {
            $selected_date = new DateTime(sanitize_text_field($_POST['pickup_date']));
        } catch (Exception $e) {
            wc_add_notice(esc_html__('Invalid pickup date format.', 'multidrop-scheduler-for-woocommerce'), 'error');
            return;
        }

        $selected_day = (clone $selected_date)->setTime(0, 0, 0);
        $earliest_day = (clone $min_date)->setTime(0, 0, 0);
        $latest_day = (clone $max_date)->setTime(0, 0, 0);

        if ($selected_day < $earliest_day) {
            wc_add_notice(esc_html__('Selected pickup date is too soon.', 'multidrop-scheduler-for-woocommerce'), 'error');
            return;
        }
        if ($selected_day > $latest_day) {
            wc_add_notice(esc_html__('Selected pickup date is too far in advance.', 'multidrop-scheduler-for-woocommerce'), 'error');
            return;
        }

        $available = $this->db->get_available_dates($location_id, $selected_date, $selected_date);
        if (empty($available)) {
            wc_add_notice(esc_html__('Selected pickup date is not available.', 'multidrop-scheduler-for-woocommerce'), 'error');
            return;
        }

        WC()->session->set('multidrop_scheduler_validated_pickup_data', array(
            'location_id' => $location_id,
            'location_name' => $wc_multidrop_scheduler_location->name,
            'fulfillment_type' => 'pickup',
            'pickup_date' => $selected_date->format('Y-m-d'),
            'pickup_fee' => floatval($wc_multidrop_scheduler_location->pickup_fee),
            'validated_at' => current_time('mysql'),
            'is_valid' => true
        ));
    }

    public function save_pickup_fields($order_id) {
        $validated_data = WC()->session->get('multidrop_scheduler_validated_pickup_data');

        if (!$validated_data || empty($validated_data['is_valid'])) {
            wc_add_notice(esc_html__('Fulfillment information could not be verified. Please place your order again.', 'multidrop-scheduler-for-woocommerce'), 'error');
            update_post_meta($order_id, '_pickup_error', 'Fulfillment information validation failed at order save.');
            error_log('Pickup Manager: Order ' . $order_id . ' rejected - invalid fulfillment session data');
            return;
        }

        $wc_multidrop_scheduler_location = $this->db->get_active_location($validated_data['location_id']);
        if (!$wc_multidrop_scheduler_location) {
            return;
        }

        $fulfillment_type = isset($validated_data['fulfillment_type']) ? $validated_data['fulfillment_type'] : 'pickup';

        update_post_meta($order_id, '_fulfillment_type', $fulfillment_type);
        update_post_meta($order_id, '_fulfillment_location_id', $validated_data['location_id']);
        update_post_meta($order_id, '_fulfillment_location_name', sanitize_text_field($wc_multidrop_scheduler_location->name));

        if ($fulfillment_type === 'delivery') {
            $processing_date = isset($validated_data['processing_date']) ? $validated_data['processing_date'] : '';
            $formatted_processing_date = $this->format_localized_date($processing_date);

            update_post_meta($order_id, '_delivery_location_id', $validated_data['location_id']);
            update_post_meta($order_id, '_delivery_location_name', sanitize_text_field($wc_multidrop_scheduler_location->name));
            update_post_meta($order_id, '_delivery_processing_date', $processing_date);
            update_post_meta($order_id, '_delivery_note', sprintf(esc_html__('Your order will be processed on %s based on preparation delay and working days.', 'multidrop-scheduler-for-woocommerce'), $formatted_processing_date));
            update_post_meta($order_id, '_delivery_fee', floatval($wc_multidrop_scheduler_location->pickup_fee));

            $order = wc_get_order($order_id);
            if ($order) {
                $order->add_order_note(sprintf(esc_html__('Fulfillment: delivery via %s. Processed on %s.', 'multidrop-scheduler-for-woocommerce'), sanitize_text_field($wc_multidrop_scheduler_location->name), $formatted_processing_date), false);
            }

            delete_post_meta($order_id, '_pickup_location_id');
            delete_post_meta($order_id, '_pickup_location_name');
            delete_post_meta($order_id, '_pickup_location_address');
            delete_post_meta($order_id, '_pickup_location_map_link');
            delete_post_meta($order_id, '_pickup_location_fee');
            delete_post_meta($order_id, '_pickup_date');
        } else {
            update_post_meta($order_id, '_pickup_location_id', $validated_data['location_id']);
            update_post_meta($order_id, '_pickup_location_name', sanitize_text_field($wc_multidrop_scheduler_location->name));
            update_post_meta($order_id, '_pickup_location_address', sanitize_textarea_field($wc_multidrop_scheduler_location->address));
            update_post_meta($order_id, '_pickup_location_map_link', esc_url_raw($wc_multidrop_scheduler_location->map_link));
            update_post_meta($order_id, '_pickup_location_fee', floatval($wc_multidrop_scheduler_location->pickup_fee));
            update_post_meta($order_id, '_pickup_date', $validated_data['pickup_date']);

            $formatted_pickup_date = $this->format_localized_date($validated_data['pickup_date']);
            $order = wc_get_order($order_id);
            if ($order) {
                $order->add_order_note(sprintf(esc_html__('Fulfillment: pickup at %s, pickup date %s.', 'multidrop-scheduler-for-woocommerce'), sanitize_text_field($wc_multidrop_scheduler_location->name), $formatted_pickup_date), false);
            }

            delete_post_meta($order_id, '_delivery_location_id');
            delete_post_meta($order_id, '_delivery_location_name');
            delete_post_meta($order_id, '_delivery_processing_date');
            delete_post_meta($order_id, '_delivery_note');
            delete_post_meta($order_id, '_delivery_fee');
        }

        WC()->session->__unset('multidrop_scheduler_validated_pickup_data');
    }

    public function add_pickup_shipping_method($rates, $package) {
        if (!$this->is_pickup_enabled()) {
            return $rates;
        }

        $location_id = 0;

        if (!empty($_POST['pickup_location_id'])) {
            $location_id = absint($_POST['pickup_location_id']);
        } elseif (!empty($_POST['post_data'])) {
            $post_data = array();
            parse_str(wp_unslash($_POST['post_data']), $post_data);
            if (!empty($post_data['pickup_location_id'])) {
                $location_id = absint($post_data['pickup_location_id']);
            }
        }

        if (!$location_id) {
            return $rates;
        }

        $location = $this->db->get_location($location_id);
        if (!$location || empty($location->is_active)) {
            return $rates;
        }

        $fulfillment_type = $this->get_fulfillment_type($location);
        $label = $fulfillment_type === 'delivery'
            ? sprintf(esc_html__('Delivery via %s', 'multidrop-scheduler-for-woocommerce'), $location->name)
            : sprintf(esc_html__('Pickup at %s', 'multidrop-scheduler-for-woocommerce'), $location->name);

        $rate_id = $fulfillment_type . '_location_' . $location->id;
        $cost = floatval($location->pickup_fee);

        // Return ONLY our custom rate
        return array(
            $rate_id => new WC_Shipping_Rate($rate_id, $label, $cost, array(), $fulfillment_type . '_location')
        );
    }

    public function display_pickup_info_admin($order) {
        $order_id = $order->get_id();
        $fulfillment_type = get_post_meta($order_id, '_fulfillment_type', true);

        if ($fulfillment_type === 'delivery') {
            $delivery_note = get_post_meta($order_id, '_delivery_note', true);
            $delivery_fee = get_post_meta($order_id, '_delivery_fee', true);

            if (!empty($delivery_note)) {
                echo '<div style="margin-top:20px;padding:10px;background:#f9f9f9;border:1px solid #ddd;"><h3>' . esc_html__('Delivery Information', 'multidrop-scheduler-for-woocommerce') . '</h3>';
                echo '<p>' . esc_html($delivery_note) . '</p>';
                if ($delivery_fee > 0) {
                    echo '<p><strong>' . esc_html__('Delivery Fee:', 'multidrop-scheduler-for-woocommerce') . '</strong> ' . wp_kses_post(wc_price($delivery_fee)) . '</p>';
                }
                echo '</div>';
            }
            return;
        }

        $name = get_post_meta($order_id, '_pickup_location_name', true);
        $address = get_post_meta($order_id, '_pickup_location_address', true);
        $date = get_post_meta($order_id, '_pickup_date', true);
        $map_link = get_post_meta($order_id, '_pickup_location_map_link', true);
        $fee = get_post_meta($order_id, '_pickup_location_fee', true);

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
            if (!empty($date)) {
                echo '<p><strong>' . esc_html__('Pickup Date:', 'multidrop-scheduler-for-woocommerce') . '</strong> ' . esc_html($this->format_localized_date($date)) . '</p>';
            }
            echo '</div>';
        }
    }

    public function display_pickup_info_email($order, $sent_to_admin, $plain_text, $email) {
        $order_id = $order->get_id();
        $fulfillment_type = get_post_meta($order_id, '_fulfillment_type', true);

        if ($fulfillment_type === 'delivery') {
            $delivery_note = get_post_meta($order_id, '_delivery_note', true);
            $delivery_fee = get_post_meta($order_id, '_delivery_fee', true);

            if (empty($delivery_note)) {
                return;
            }

            if ($plain_text) {
                echo "\n" . esc_html__('DELIVERY INFORMATION', 'multidrop-scheduler-for-woocommerce') . "\n";
                echo esc_html($delivery_note) . "\n";
                if ($delivery_fee > 0) {
                    echo esc_html__('Delivery Fee:', 'multidrop-scheduler-for-woocommerce') . ' ' . esc_html(wp_strip_all_tags(wc_price($delivery_fee))) . "\n";
                }
            } else {
                echo '<h2>' . esc_html__('Delivery Information', 'multidrop-scheduler-for-woocommerce') . '</h2>';
                echo '<p>' . esc_html($delivery_note) . '</p>';
                if ($delivery_fee > 0) {
                    echo '<p><strong>' . esc_html__('Delivery Fee:', 'multidrop-scheduler-for-woocommerce') . '</strong> ' . wp_kses_post(wc_price($delivery_fee)) . '</p>';
                }
            }
            return;
        }

        $name = get_post_meta($order_id, '_pickup_location_name', true);
        $address = get_post_meta($order_id, '_pickup_location_address', true);
        $date = get_post_meta($order_id, '_pickup_date', true);
        $map_link = get_post_meta($order_id, '_pickup_location_map_link', true);
        $fee = get_post_meta($order_id, '_pickup_location_fee', true);

        if ($name) {
            $formatted_date = $this->format_localized_date($date);

            if ($plain_text) {
                echo "\n" . esc_html__('PICKUP INFORMATION', 'multidrop-scheduler-for-woocommerce') . "\n";
                echo esc_html__('Location:', 'multidrop-scheduler-for-woocommerce') . ' ' . esc_html($name) . "\n";
                echo esc_html__('Address:', 'multidrop-scheduler-for-woocommerce') . ' ' . esc_html($address) . "\n";
                if (!empty($map_link)) {
                    echo esc_html__('Map:', 'multidrop-scheduler-for-woocommerce') . ' ' . esc_url($map_link) . "\n";
                }
                if ($fee > 0) {
                    echo esc_html__('Pickup Fee:', 'multidrop-scheduler-for-woocommerce') . ' ' . esc_html(wp_strip_all_tags(wc_price($fee))) . "\n";
                }
                if (!empty($formatted_date)) {
                    echo esc_html__('Pickup Date:', 'multidrop-scheduler-for-woocommerce') . ' ' . esc_html($formatted_date) . "\n";
                }
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
                if (!empty($formatted_date)) {
                    echo '<p><strong>' . esc_html__('Pickup Date:', 'multidrop-scheduler-for-woocommerce') . '</strong> ' . esc_html($formatted_date) . '</p>';
                }
            }
        }
    }
}