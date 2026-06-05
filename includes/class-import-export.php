<?php
if (!defined('ABSPATH')) exit;

class WC_Multidrop_Scheduler_Import_Export {
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

        add_action('admin_menu', array($this, 'add_import_export_page'), 20);
        add_action('admin_post_export_pickup_locations', array($this, 'handle_export'));
        add_action('admin_post_import_pickup_locations', array($this, 'handle_import'));
    }

    public function add_import_export_page() {
        add_submenu_page(
            'pickup-locations',
            esc_html__('Import/Export', 'multidrop-scheduler-for-woocommerce'),
            esc_html__('Import/Export', 'multidrop-scheduler-for-woocommerce'),
            'manage_woocommerce',
            'pickup-locations-import-export',
            array($this, 'render_import_export_page')
        );
    }

    public function render_import_export_page() {
        $locations = $this->db->get_all_locations();
        include WC_MULTIDROP_SCHEDULER_PLUGIN_DIR . 'templates/admin/import-export.php';
    }

    public function handle_export() {
        check_admin_referer('export_pickup_locations');

        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have permission to do this.', 'multidrop-scheduler-for-woocommerce'));
        }

        $locations = $this->db->get_all_locations();
        $export_data = array(
            'version' => '2.4.4',
            'export_date' => current_time('mysql'),
            'site_url' => get_site_url(),
            'locations' => array()
        );

        foreach ($locations as $wc_multidrop_scheduler_location) {
            $overrides = $this->db->get_location_overrides($wc_multidrop_scheduler_location->id);

            $export_data['locations'][] = array(
                'name' => $wc_multidrop_scheduler_location->name,
                'address' => $wc_multidrop_scheduler_location->address,
                'map_link' => $wc_multidrop_scheduler_location->map_link,
                'pickup_fee' => $wc_multidrop_scheduler_location->pickup_fee,
                'min_delay_hours' => $wc_multidrop_scheduler_location->min_delay_hours,
                'max_advance_days' => $wc_multidrop_scheduler_location->max_advance_days,
                'weekly_schedule' => $wc_multidrop_scheduler_location->weekly_schedule,
                'is_active' => $wc_multidrop_scheduler_location->is_active,
                'overrides' => array_map(function($override) {
                    return array(
                        'date' => $override->override_date,
                        'is_open' => $override->is_open,
                        'note' => $override->note
                    );
                }, $overrides)
            );
        }

        $filename = 'pickup-locations-export-' . gmdate('Y-m-d-His') . '.json';

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo wp_json_encode($export_data, JSON_PRETTY_PRINT);
        exit;
    }

    public function handle_import() {
        check_admin_referer('import_pickup_locations');

        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have permission to do this.', 'multidrop-scheduler-for-woocommerce'));
        }

        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_safe_redirect(add_query_arg(array(
                'page' => 'pickup-locations-import-export',
                'error' => 'upload_failed'
            ), admin_url('admin.php')));
            exit;
        }

        $file_content = file_get_contents($_FILES['import_file']['tmp_name']);
        $import_data = json_decode($file_content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_safe_redirect(add_query_arg(array(
                'page' => 'pickup-locations-import-export',
                'error' => 'invalid_json'
            ), admin_url('admin.php')));
            exit;
        }

        if (!isset($import_data['locations']) || !is_array($import_data['locations'])) {
            wp_safe_redirect(add_query_arg(array(
                'page' => 'pickup-locations-import-export',
                'error' => 'invalid_format'
            ), admin_url('admin.php')));
            exit;
        }

        $import_mode = isset($_POST['import_mode']) ? sanitize_text_field($_POST['import_mode']) : 'add';

        if ($import_mode === 'replace') {
            $existing_locations = $this->db->get_all_locations();
            foreach ($existing_locations as $wc_multidrop_scheduler_location) {
                $this->db->delete_location($wc_multidrop_scheduler_location->id);
            }
        }

        $imported_count = 0;
        foreach ($import_data['locations'] as $location_data) {
            $location_id = $this->db->add_location(array(
                'name' => sanitize_text_field($location_data['name']),
                'address' => sanitize_textarea_field($location_data['address']),
                'map_link' => isset($location_data['map_link']) ? esc_url_raw($location_data['map_link']) : '',
                'pickup_fee' => floatval($location_data['pickup_fee']),
                'min_delay_hours' => intval($location_data['min_delay_hours']),
                'max_advance_days' => intval($location_data['max_advance_days']),
                'weekly_schedule' => $location_data['weekly_schedule'],
                'is_active' => isset($location_data['is_active']) ? $location_data['is_active'] : 1
            ));

            if (!empty($location_data['overrides'])) {
                global $wpdb;
                $location_id = $wpdb->insert_id;

                foreach ($location_data['overrides'] as $override) {
                    $this->db->add_override(
                        $location_id,
                        sanitize_text_field($override['date']),
                        $override['is_open'],
                        isset($override['note']) ? sanitize_text_field($override['note']) : ''
                    );
                }
            }

            $imported_count++;
        }

        wp_safe_redirect(add_query_arg(array(
            'page' => 'pickup-locations-import-export',
            'imported' => $imported_count,
            'mode' => $import_mode
        ), admin_url('admin.php')));
        exit;
    }
}
