<?php
if (!defined('ABSPATH')) exit;

class WC_Multidrop_Scheduler_Email_Notifications {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'schedule_daily_email'));
        add_action('wc_multidrop_scheduler_daily_summary', array($this, 'send_daily_summary'));
        add_action('admin_post_wc_multidrop_send_test_email', array($this, 'handle_manual_send'));
    }

    private function is_enabled() {
        return get_option('wc_multidrop_email_enabled', 'no') === 'yes';
    }

    public function schedule_daily_email() {
        $hook = 'wc_multidrop_scheduler_daily_summary';

        if (!$this->is_enabled()) {
            $timestamp = wp_next_scheduled($hook);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $hook);
            }
            return;
        }

        if (wp_next_scheduled($hook)) {
            return;
        }

        $time = get_option('wc_multidrop_email_time', '08:00');
        list($hour, $minute) = array_map('intval', explode(':', $time));

        $store_timestamp = current_time('timestamp');
        $next = mktime($hour, $minute, 0, date('n', $store_timestamp), date('j', $store_timestamp), date('Y', $store_timestamp));

        if ($next <= $store_timestamp) {
            $next = strtotime('+1 day', $next);
        }

        wp_schedule_single_event($next, $hook);
    }

    public function send_daily_summary() {
        if (!$this->is_enabled()) {
            return;
        }

        $today = date('Y-m-d', current_time('timestamp'));
        $recipient_option = get_option('wc_multidrop_email_recipients', get_option('admin_email'));
        if (!$recipient_option) {
            return;
        }

        $recipients = array_filter(array_map('trim', explode(',', $recipient_option)));
        if (empty($recipients)) {
            return;
        }

        $include_pickup = get_option('wc_multidrop_email_include_pickup', 'yes') === 'yes';
        $include_delivery = get_option('wc_multidrop_email_include_delivery', 'yes') === 'yes';

        $pickup_orders = $include_pickup ? $this->get_orders_by_fulfillment_type('pickup', $today) : array();
        $delivery_orders = $include_delivery ? $this->get_orders_by_fulfillment_type('delivery', $today) : array();

        if (empty($pickup_orders) && empty($delivery_orders)) {
            $this->schedule_daily_email();
            return;
        }

        $subject_template = get_option('wc_multidrop_email_subject', 'Fulfillment summary for {date}');
        $subject = str_replace('{date}', date_i18n(get_option('date_format'), strtotime($today)), $subject_template);

        $headers = array('Content-Type: text/html; charset=UTF-8');
        $message = $this->build_email_body($pickup_orders, $delivery_orders, $today);

        foreach ($recipients as $to) {
            wp_mail($to, $subject, $message, $headers);
        }

        $this->schedule_daily_email();
    }

/**
 * Get orders by fulfillment type for a specific date.
 * Uses a date range query + light PHP filter.
 */
private function get_orders_by_fulfillment_type($type, $date) {
    $meta_key_date = $type === 'delivery' ? '_delivery_processing_date' : '_pickup_date';

    // Ensure $date is a plain Y-m-d string
    $date = is_string($date) ? $date : date('Y-m-d', strtotime($date));

    // Build a date range for the whole day (covers any datetime variants)
    $start_of_day = $date . ' 00:00:00';
    $end_of_day   = $date . ' 23:59:59';

    // Try to use meta_query with a range; if HPOS breaks it, we still filter in PHP.
    $query_args = array(
        'status' => array('wc-completed'),
        'limit'  => -1, // reasonable cap; adjust if needed
        'type'   => 'shop_order',
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key'   => '_fulfillment_type',
                'value' => $type,
            ),
            array(
                'key'     => $meta_key_date,
                'value'   => array($start_of_day, $end_of_day),
                'type'    => 'DATETIME',
                'compare' => 'BETWEEN',
            ),
        ),
        'orderby' => 'ID',
        'order'   => 'ASC',
    );


    $orders = wc_get_orders($query_args);

    // Safety filter in PHP (in case meta_query is ignored)
    $filtered = array();
    foreach ($orders as $order) {
        $fulfillment_type = $order->get_meta('_fulfillment_type', true);
        $stored_date      = $order->get_meta($meta_key_date, true);

        // Normalize stored date to Y-m-d
        if (!empty($stored_date)) {
            try {
                $dt = new DateTime($stored_date);
                $stored_date = $dt->format('Y-m-d');
            } catch (Exception $e) {
                $stored_date = '';
            }
        }

        if ($fulfillment_type === $type && $stored_date === $date) {
            $filtered[] = $order;
        }
    }

    $order_ids_matched = wp_list_pluck($filtered, 'get_id');

    if (empty($filtered)) {
        return array();
    }


    return $filtered;
}

    private function build_email_body($pickup_orders, $delivery_orders, $date) {
        $all_orders = array_merge($pickup_orders, $delivery_orders);
        $summary_items = $this->build_summary_items($all_orders);

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 900px; margin: 0 auto; padding: 20px; }
                h1 { color: #2271b1; border-bottom: 2px solid #2271b1; padding-bottom: 10px; }
                h2 { color: #2271b1; margin-top: 30px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #ddd; font-size: 13px; }
                th { background-color: #f0f0f0; font-weight: 600; }
                tr:hover { background-color: #f9f9f9; }
                .no-orders { color: #999; font-style: italic; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
        <div class="container">
            <h1><?php echo esc_html__('Daily Fulfillment Summary', 'multidrop-scheduler-for-woocommerce'); ?></h1>
            <p><strong><?php echo esc_html__('Date:', 'multidrop-scheduler-for-woocommerce'); ?></strong> <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($date))); ?></p>

            <?php echo $this->render_summary_items_table($summary_items); ?>

            <?php echo $this->render_orders_table($pickup_orders, 'pickup'); ?>
            <?php echo $this->render_orders_table($delivery_orders, 'delivery'); ?>

            <div class="footer">
                <p><?php echo esc_html__('This is an automated email from MultiDrop Scheduler for WooCommerce.', 'multidrop-scheduler-for-woocommerce'); ?></p>
            </div>
        </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    private function build_summary_items($orders) {
        $summary = array();

        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                $product_id = $product ? $product->get_id() : 0;
                $variation_id = $product && $product->is_type('variation') ? $product->get_id() : 0;
                $key = $product_id . ':' . $variation_id . ':' . $item->get_name();

                if (!isset($summary[$key])) {
                    $summary[$key] = array(
                        'name' => $item->get_name(),
                        'sku'  => $product ? $product->get_sku() : '',
                        'qty'  => 0,
                    );
                }

                $summary[$key]['qty'] += $item->get_quantity();
            }
        }

        return $summary;
    }

    private function render_summary_items_table($summary_items) {
        if (empty($summary_items)) {
            return '';
        }

        ob_start();
        ?>
        <h2><?php echo esc_html__('Preparation Summary (All Orders)', 'multidrop-scheduler-for-woocommerce'); ?></h2>
        <table>
            <thead>
            <tr>
                <th><?php echo esc_html__('Product', 'multidrop-scheduler-for-woocommerce'); ?></th>
                <th><?php echo esc_html__('SKU', 'multidrop-scheduler-for-woocommerce'); ?></th>
                <th><?php echo esc_html__('Total Quantity', 'multidrop-scheduler-for-woocommerce'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($summary_items as $item) : ?>
                <tr>
                    <td><?php echo esc_html($item['name']); ?></td>
                    <td><?php echo esc_html($item['sku']); ?></td>
                    <td><?php echo esc_html($item['qty']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        return ob_get_clean();
    }

    private function render_orders_table($orders, $type) {
        $title = $type === 'delivery'
            ? esc_html__('Delivery Orders', 'multidrop-scheduler-for-woocommerce')
            : esc_html__('Pickup Orders', 'multidrop-scheduler-for-woocommerce');

        if (empty($orders)) {
            ob_start();
            ?>
            <h2><?php echo $title; ?></h2>
            <p class="no-orders"><?php echo esc_html__('No orders for this type today.', 'multidrop-scheduler-for-woocommerce'); ?></p>
            <?php
            return ob_get_clean();
        }

        ob_start();
        ?>
        <h2><?php echo $title; ?> (<?php echo count($orders); ?>)</h2>
        <table>
            <thead>
            <tr>
                <th><?php echo esc_html__('Order #', 'multidrop-scheduler-for-woocommerce'); ?></th>
                <th><?php echo esc_html__('Customer', 'multidrop-scheduler-for-woocommerce'); ?></th>
                <th><?php echo esc_html__('Phone', 'multidrop-scheduler-for-woocommerce'); ?></th>
                <th><?php echo esc_html__('Email', 'multidrop-scheduler-for-woocommerce'); ?></th>
                <?php if ($type === 'delivery') : ?>
                    <th><?php echo esc_html__('Delivery Address', 'multidrop-scheduler-for-woocommerce'); ?></th>
                <?php endif; ?>
                <th><?php echo esc_html__('Items', 'multidrop-scheduler-for-woocommerce'); ?></th>
                <th><?php echo esc_html__('Total', 'multidrop-scheduler-for-woocommerce'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $order) :
                $customer_name = $order->get_formatted_billing_full_name() ?: '—';
                $phone         = $order->get_billing_phone() ?: '—';
                $email         = $order->get_billing_email() ?: '—';
                $items_count   = $order->get_item_count();
                $total         = $order->get_formatted_order_total();
                $address       = '';

                if ($type === 'delivery') {
                    $address = $order->get_formatted_shipping_address();
                    if (!$address) {
                        $address = $order->get_formatted_billing_address();
                    }
                }
                ?>
                <tr>
                    <td><a href="<?php echo esc_url(get_edit_post_link($order->get_id())); ?>">#<?php echo esc_html($order->get_id()); ?></a></td>
                    <td><?php echo esc_html($customer_name); ?></td>
                    <td><?php echo esc_html($phone); ?></td>
                    <td><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></td>
                    <?php if ($type === 'delivery') : ?>
                        <td><?php echo nl2br(esc_html($address)); ?></td>
                    <?php endif; ?>
                    <td><?php echo esc_html($items_count); ?></td>
                    <td><?php echo wp_kses_post($total); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        return ob_get_clean();
    }

    public function handle_manual_send() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Permission denied', 'multidrop-scheduler-for-woocommerce'));
        }

        check_admin_referer('wc_multidrop_send_test_email');

        $today = date('Y-m-d', current_time('timestamp'));
        $recipient_option = get_option('wc_multidrop_email_recipients', get_option('admin_email'));
        $recipients = array_filter(array_map('trim', explode(',', $recipient_option)));

        if (empty($recipients)) {
            wp_safe_redirect(add_query_arg(array('page' => 'pickup-locations-settings', 'email_error' => '1'), admin_url('admin.php')));
            exit;
        }

        $pickup_orders = $this->get_orders_by_fulfillment_type('pickup', $today);
        $delivery_orders = $this->get_orders_by_fulfillment_type('delivery', $today);

        $subject_template = get_option('wc_multidrop_email_subject', '[TEST] Fulfillment summary for {date}');
        $subject = str_replace('{date}', date_i18n(get_option('date_format'), strtotime($today)), $subject_template);

        $headers = array('Content-Type: text/html; charset=UTF-8', 'From: GlutenvrijeWinkel  <info@glutenvrijewinkelamsterdam.nl>',);
        $message = $this->build_email_body($pickup_orders, $delivery_orders, $today);

        foreach ($recipients as $to) {
            wp_mail($to, $subject, $message, $headers);
        }

        wp_safe_redirect(add_query_arg(array('page' => 'pickup-locations-settings', 'email_sent' => '1'), admin_url('admin.php')));
        exit;
    }
}
