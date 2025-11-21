<?php
if (!defined('ABSPATH')) exit;

class WC_Pickup_Manager_Import_Export {
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

        // Add submenu page
        add_action('admin_menu', array($this, 'add_import_export_page'), 20);

        // Handle export action
        add_action('admin_post_export_pickup_locations', array($this, 'handle_export'));

        // Handle import action
        add_action('admin_post_import_pickup_locations', array($this, 'handle_import'));
    }

    public function add_import_export_page() {
        add_submenu_page(
            'pickup-locations',
            __('Import/Export', 'wc-pickup-manager'),
            __('Import/Export', 'wc-pickup-manager'),
            'manage_woocommerce',
            'pickup-locations-import-export',
            array($this, 'render_import_export_page')
        );
    }

    public function render_import_export_page() {
        $locations = $this->db->get_all_locations();
        include WC_PICKUP_MANAGER_PLUGIN_DIR . 'templates/admin/import-export.php';
    }

    public function handle_export() {
        check_admin_referer('export_pickup_locations');

        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to do this.', 'wc-pickup-manager'));
        }

        // Get all locations with their overrides
        $locations = $this->db->get_all_locations();
        $export_data = array(
            'version' => '2.1',
            'export_date' => current_time('mysql'),
            'site_url' => get_site_url(),
            'locations' => array()
        );

        foreach ($locations as $location) {
            $overrides = $this->db->get_location_overrides($location->id);

            $export_data['locations'][] = array(
                'name' => $location->name,
                'address' => $location->address,
                'pickup_fee' => $location->pickup_fee,
                'min_delay_hours' => $location->min_delay_hours,
                'max_advance_days' => $location->max_advance_days,
                'weekly_schedule' => $location->weekly_schedule,
                'is_active' => $location->is_active,
                'overrides' => array_map(function($override) {
                    return array(
                        'date' => $override->override_date,
                        'is_open' => $override->is_open,
                        'note' => $override->note
                    );
                }, $overrides)
            );
        }

        // Generate filename
        $filename = 'pickup-locations-export-' . date('Y-m-d-His') . '.json';

        // Set headers for download
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Output JSON
        echo wp_json_encode($export_data, JSON_PRETTY_PRINT);
        exit;
    }

    public function handle_import() {
        check_admin_referer('import_pickup_locations');

        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to do this.', 'wc-pickup-manager'));
        }

        // Check if file was uploaded
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_redirect(add_query_arg(array(
                'page' => 'pickup-locations-import-export',
                'error' => 'upload_failed'
            ), admin_url('admin.php')));
            exit;
        }

        // Read file content
        $file_content = file_get_contents($_FILES['import_file']['tmp_name']);
        $import_data = json_decode($file_content, true);

        // Validate JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_redirect(add_query_arg(array(
                'page' => 'pickup-locations-import-export',
                'error' => 'invalid_json'
            ), admin_url('admin.php')));
            exit;
        }

        // Validate structure
        if (!isset($import_data['locations']) || !is_array($import_data['locations'])) {
            wp_redirect(add_query_arg(array(
                'page' => 'pickup-locations-import-export',
                'error' => 'invalid_format'
            ), admin_url('admin.php')));
            exit;
        }

        // Get import mode
        $import_mode = isset($_POST['import_mode']) ? $_POST['import_mode'] : 'add';

        // If replace mode, delete existing locations
        if ($import_mode === 'replace') {
            $existing_locations = $this->db->get_all_locations();
            foreach ($existing_locations as $location) {
                $this->db->delete_location($location->id);
            }
        }

        // Import locations
        $imported_count = 0;
        foreach ($import_data['locations'] as $location_data) {
            // Add location
            $location_id = $this->db->add_location(array(
                'name' => sanitize_text_field($location_data['name']),
                'address' => sanitize_textarea_field($location_data['address']),
                'pickup_fee' => floatval($location_data['pickup_fee']),
                'min_delay_hours' => intval($location_data['min_delay_hours']),
                'max_advance_days' => intval($location_data['max_advance_days']),
                'weekly_schedule' => $location_data['weekly_schedule'],
                'is_active' => isset($location_data['is_active']) ? $location_data['is_active'] : 1
            ));

            // Add overrides if they exist
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

        // Redirect with success message
        wp_redirect(add_query_arg(array(
            'page' => 'pickup-locations-import-export',
            'imported' => $imported_count,
            'mode' => $import_mode
        ), admin_url('admin.php')));
        exit;
    }
}
