<?php
if (!defined('ABSPATH')) exit;

class WC_Pickup_Manager_Database {
    private static $instance = null;
    private $locations_table;
    private $overrides_table;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->locations_table = $wpdb->prefix . 'pickup_locations';
        $this->overrides_table = $wpdb->prefix . 'pickup_date_overrides';
    }

    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $locations_sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}pickup_locations (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            address text NOT NULL,
            map_link text,
            pickup_fee decimal(10,2) NOT NULL DEFAULT 0,
            min_delay_hours int NOT NULL DEFAULT 24,
            max_advance_days int NOT NULL DEFAULT 30,
            weekly_schedule text NOT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        $overrides_sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}pickup_date_overrides (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            location_id mediumint(9) NOT NULL,
            override_date date NOT NULL,
            is_open tinyint(1) NOT NULL DEFAULT 0,
            note varchar(255),
            PRIMARY KEY (id),
            KEY location_id (location_id),
            KEY override_date (override_date)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($locations_sql);
        dbDelta($overrides_sql);
    }

    public function get_all_locations($active_only = false) {
        global $wpdb;

        if ($active_only) {
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM %i WHERE is_active = %d ORDER BY name ASC",
                    $this->locations_table,
                    1
                )
            );
        } else {
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM %i ORDER BY name ASC",
                    $this->locations_table
                )
            );
        }

        foreach ($results as &$location) {
            $location->weekly_schedule = json_decode($location->weekly_schedule, true);
        }
        return $results;
    }

    public function get_location($id) {
        global $wpdb;
        $location = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM %i WHERE id = %d",
                $this->locations_table,
                $id
            )
        );
        if ($location) {
            $location->weekly_schedule = json_decode($location->weekly_schedule, true);
        }
        return $location;
    }

    public function add_location($data) {
        global $wpdb;
        $weekly_schedule = isset($data['weekly_schedule']) ? $data['weekly_schedule'] : array_fill(0, 7, false);
        return $wpdb->insert(
            $this->locations_table,
            array(
                'name' => sanitize_text_field($data['name']),
                'address' => sanitize_textarea_field($data['address']),
                'map_link' => isset($data['map_link']) ? esc_url_raw($data['map_link']) : '',
                'pickup_fee' => floatval($data['pickup_fee']),
                'min_delay_hours' => intval($data['min_delay_hours']),
                'max_advance_days' => intval($data['max_advance_days']),
                'weekly_schedule' => wp_json_encode($weekly_schedule),
                'is_active' => $data['is_active'] ? 1 : 0
            ),
            array('%s', '%s', '%s', '%f', '%d', '%d', '%s', '%d')
        );
    }

    public function update_location($id, $data) {
        global $wpdb;
        $weekly_schedule = isset($data['weekly_schedule']) ? $data['weekly_schedule'] : array_fill(0, 7, false);
        return $wpdb->update(
            $this->locations_table,
            array(
                'name' => sanitize_text_field($data['name']),
                'address' => sanitize_textarea_field($data['address']),
                'map_link' => isset($data['map_link']) ? esc_url_raw($data['map_link']) : '',
                'pickup_fee' => floatval($data['pickup_fee']),
                'min_delay_hours' => intval($data['min_delay_hours']),
                'max_advance_days' => intval($data['max_advance_days']),
                'weekly_schedule' => wp_json_encode($weekly_schedule),
                'is_active' => $data['is_active'] ? 1 : 0
            ),
            array('id' => $id),
            array('%s', '%s', '%s', '%f', '%d', '%d', '%s', '%d'),
            array('%d')
        );
    }

    public function delete_location($id) {
        global $wpdb;
        $wpdb->delete($this->overrides_table, array('location_id' => $id), array('%d'));
        return $wpdb->delete($this->locations_table, array('id' => $id), array('%d'));
    }

    public function get_location_overrides($location_id) {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM %i WHERE location_id = %d ORDER BY override_date ASC",
                $this->overrides_table,
                $location_id
            )
        );
    }

    public function add_override($location_id, $date, $is_open, $note = '') {
        global $wpdb;
        return $wpdb->insert(
            $this->overrides_table,
            array(
                'location_id' => intval($location_id),
                'override_date' => sanitize_text_field($date),
                'is_open' => $is_open ? 1 : 0,
                'note' => sanitize_text_field($note)
            ),
            array('%d', '%s', '%d', '%s')
        );
    }

    public function delete_override($id) {
        global $wpdb;
        return $wpdb->delete($this->overrides_table, array('id' => $id), array('%d'));
    }

    public function get_available_dates($location_id, $start_date, $end_date) {
        $location = $this->get_location($location_id);
        if (!$location) return array();

        $available_dates = array();
        $overrides = $this->get_location_overrides($location_id);
        $override_map = array();

        foreach ($overrides as $override) {
            $override_map[$override->override_date] = $override->is_open;
        }

        $current = new DateTime($start_date);
        $end = new DateTime($end_date);

        while ($current <= $end) {
            $date_str = $current->format('Y-m-d');
            $day_of_week = (int)$current->format('w');

            if (isset($override_map[$date_str])) {
                if ($override_map[$date_str]) {
                    $available_dates[] = $date_str;
                }
            } else {
                if (isset($location->weekly_schedule[$day_of_week]) && $location->weekly_schedule[$day_of_week]) {
                    $available_dates[] = $date_str;
                }
            }

            $current->modify('+1 day');
        }

        return $available_dates;
    }
}