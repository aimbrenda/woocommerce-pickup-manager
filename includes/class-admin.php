<?php
if (!defined('ABSPATH')) exit;

class WC_Multidrop_Scheduler_Admin {
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_post_save_pickup_location', array($this, 'save_location'));
        add_action('admin_post_delete_pickup_location', array($this, 'delete_location'));
        add_action('admin_post_add_date_override', array($this, 'add_date_override'));
        add_action('admin_post_delete_date_override', array($this, 'delete_date_override'));
        add_action('admin_post_save_checkout_position', array($this, 'save_checkout_position'));
    }

    public function add_admin_menu() {
        add_menu_page('Pickup Locations', 'Pickup Locations', 'manage_woocommerce', 'pickup-locations',
            array($this, 'render_locations_page'), 'dashicons-location', 56);
        add_submenu_page('pickup-locations', 'Add Location', 'Add Location', 'manage_woocommerce',
            'pickup-location-add', array($this, 'render_add_edit_page'));
        add_submenu_page('pickup-locations', 'Settings', 'Settings', 'manage_woocommerce',
            'pickup-locations-settings', array($this, 'render_settings_page'));
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'pickup-location') === false) return;
        wp_enqueue_style('multidrop-scheduler-for-woocommerce-admin', WC_MULTIDROP_SCHEDULER_PLUGIN_URL . 'assets/css/admin.css', array(), WC_MULTIDROP_SCHEDULER_VERSION);
        wp_enqueue_script('multidrop-scheduler-for-woocommerce-admin', WC_MULTIDROP_SCHEDULER_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), WC_MULTIDROP_SCHEDULER_VERSION, true);
    }

    public function render_locations_page() {
        $locations = $this->db->get_all_locations();
        include WC_MULTIDROP_SCHEDULER_PLUGIN_DIR . 'templates/admin/locations-list.php';
    }

    public function render_add_edit_page() {
        $location_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $wc_multidrop_scheduler_location = $location_id ? $this->db->get_location($location_id) : null;
        $wc_multidrop_scheduler_overrides = $location_id ? $this->db->get_location_overrides($location_id) : array();
        include WC_MULTIDROP_SCHEDULER_PLUGIN_DIR . 'templates/admin/location-form.php';
    }

    public function render_settings_page() {
        include WC_MULTIDROP_SCHEDULER_PLUGIN_DIR . 'templates/admin/settings.php';
    }

    public function save_location() {
        check_admin_referer('save_pickup_location');
        if (!current_user_can('manage_woocommerce')) wp_die('No permission');

        $location_id = isset($_POST['location_id']) ? intval($_POST['location_id']) : 0;
        $weekly_schedule = array();
        for ($i = 0; $i < 7; $i++) {
            $weekly_schedule[$i] = isset($_POST['weekly_schedule'][$i]);
        }

        $data = array(
            'name' => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '',
            'fulfillment_type' => isset($_POST['fulfillment_type']) ? sanitize_key($_POST['fulfillment_type']) : 'pickup',
            'address' => isset($_POST['address']) ? sanitize_textarea_field(wp_unslash($_POST['address'])) : '',
            'map_link' => isset($_POST['map_link']) ? esc_url_raw(wp_unslash($_POST['map_link'])) : '',
            'pickup_fee' => isset($_POST['pickup_fee']) ? floatval($_POST['pickup_fee']) : 0,
            'min_delay_hours' => isset($_POST['min_delay_hours']) ? intval($_POST['min_delay_hours']) : 0,
            'max_advance_days' => isset($_POST['max_advance_days']) ? intval($_POST['max_advance_days']) : 0,
            'weekly_schedule' => $weekly_schedule,
            'is_active' => isset($_POST['is_active']) ? true : false
        );

        if ($location_id) {
            $this->db->update_location($location_id, $data);
            $redirect = add_query_arg(array('page' => 'pickup-location-add', 'id' => $location_id, 'updated' => '1'), admin_url('admin.php'));
        } else {
            $this->db->add_location($data);
            $redirect = add_query_arg(array('page' => 'pickup-locations', 'added' => '1'), admin_url('admin.php'));
        }
        wp_safe_redirect($redirect);
        exit;
    }

    public function delete_location() {
        check_admin_referer('delete_pickup_location_' . intval($_GET['id']));
        if (!current_user_can('manage_woocommerce')) wp_die('No permission');
        $this->db->delete_location(intval($_GET['id']));
        wp_safe_redirect(add_query_arg(array('page' => 'pickup-locations', 'deleted' => '1'), admin_url('admin.php')));
        exit;
    }

    public function add_date_override() {
        check_admin_referer('add_date_override');
        if (!current_user_can('manage_woocommerce')) wp_die('No permission');
        $this->db->add_override(intval($_POST['location_id']), sanitize_text_field($_POST['override_date']), 
            isset($_POST['is_open']), sanitize_text_field($_POST['note']));
        wp_safe_redirect(add_query_arg(array('page' => 'pickup-location-add', 'id' => intval($_POST['location_id']), 
            'override_added' => '1'), admin_url('admin.php')));
        exit;
    }

    public function delete_date_override() {
        check_admin_referer('delete_override_' . intval($_GET['override_id']));
        if (!current_user_can('manage_woocommerce')) wp_die('No permission');
        $this->db->delete_override(intval($_GET['override_id']));
        wp_safe_redirect(add_query_arg(array('page' => 'pickup-location-add', 'id' => intval($_GET['location_id']), 
            'override_deleted' => '1'), admin_url('admin.php')));
        exit;
    }

    public function save_checkout_position() {
        check_admin_referer('save_checkout_position');
        if (!current_user_can('manage_woocommerce')) wp_die('No permission');

        update_option('wc_multidrop_scheduler_checkout_position', sanitize_text_field($_POST['checkout_position']));
        update_option('wc_multidrop_scheduler_enabled', isset($_POST['pickup_enabled']) && sanitize_text_field($_POST['pickup_enabled']) === 'yes' ? 'yes' : 'no');

        wp_safe_redirect(add_query_arg(array('page' => 'pickup-locations-settings', 'updated' => '1'), admin_url('admin.php')));
        exit;
    }
}
