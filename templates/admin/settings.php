<?php
if (!defined('ABSPATH')) exit;

$wc_multidrop_scheduler_current_position = get_option('wc_multidrop_scheduler_checkout_position', 'after_order_notes');
$wc_multidrop_scheduler_enabled  = get_option('wc_multidrop_scheduler_enabled', 'yes');

$wc_multidrop_email_enabled         = get_option('wc_multidrop_email_enabled', 'no');
$wc_multidrop_email_recipients      = get_option('wc_multidrop_email_recipients', get_option('admin_email'));
$wc_multidrop_email_time            = get_option('wc_multidrop_email_time', '08:00');
$wc_multidrop_email_include_pickup  = get_option('wc_multidrop_email_include_pickup', 'yes');
$wc_multidrop_email_include_delivery = get_option('wc_multidrop_email_include_delivery', 'yes');
$wc_multidrop_email_subject         = get_option('wc_multidrop_email_subject', 'Fulfillment summary for {date}');
?>

<div class="wrap">
    <h1><?php esc_html_e('Pickup Locations Settings', 'multidrop-scheduler-for-woocommerce'); ?></h1>

    <?php if (isset($_GET['updated'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Settings saved successfully.', 'multidrop-scheduler-for-woocommerce'); ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['email_sent'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Test email sent successfully.', 'multidrop-scheduler-for-woocommerce'); ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['email_error'])): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php esc_html_e('Could not send test email. Please check recipient settings.', 'multidrop-scheduler-for-woocommerce'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('save_checkout_position'); ?>
        <input type="hidden" name="action" value="save_checkout_position">

        <h2><?php esc_html_e('General Settings', 'multidrop-scheduler-for-woocommerce'); ?></h2>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="pickup_enabled"><?php esc_html_e('Enable Pickup Locations', 'multidrop-scheduler-for-woocommerce'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="pickup_enabled" id="pickup_enabled" value="yes" <?php checked($wc_multidrop_scheduler_enabled, 'yes'); ?>>
                        <?php esc_html_e('Enable pickup location selection at checkout', 'multidrop-scheduler-for-woocommerce'); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e('When disabled, pickup fields will not appear on checkout page. Individual locations can still be configured.', 'multidrop-scheduler-for-woocommerce'); ?>
                    </p>

                    <?php if ($wc_multidrop_scheduler_enabled=== 'yes'): ?>
                        <div style="margin-top: 10px; padding: 10px; background: #d4edda; border-left: 3px solid #28a745;">
                            <strong style="color: #155724;">✓ <?php esc_html_e('Pickup is currently ENABLED', 'multidrop-scheduler-for-woocommerce'); ?></strong>
                            <p style="margin: 5px 0 0 0; color: #155724;"><?php esc_html_e('Customers will see pickup options at checkout.', 'multidrop-scheduler-for-woocommerce'); ?></p>
                        </div>
                    <?php else: ?>
                        <div style="margin-top: 10px; padding: 10px; background: #f8d7da; border-left: 3px solid #dc3545;">
                            <strong style="color: #721c24;">✗ <?php esc_html_e('Pickup is currently DISABLED', 'multidrop-scheduler-for-woocommerce'); ?></strong>
                            <p style="margin: 5px 0 0 0; color: #721c24;"><?php esc_html_e('Customers will NOT see pickup options at checkout.', 'multidrop-scheduler-for-woocommerce'); ?></p>
                        </div>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <hr>

        <h2><?php esc_html_e('Checkout Page Settings', 'multidrop-scheduler-for-woocommerce'); ?></h2>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="checkout_position"><?php esc_html_e('Pickup Fields Position', 'multidrop-scheduler-for-woocommerce'); ?></label>
                </th>
                <td>
                    <select name="checkout_position" id="checkout_position" class="regular-text">
                        <option value="before_customer_details" <?php selected($wc_multidrop_scheduler_current_position, 'before_customer_details'); ?>>
                            <?php esc_html_e('Before Customer Details (Top)', 'multidrop-scheduler-for-woocommerce'); ?>
                        </option>
                        <option value="after_customer_details" <?php selected($wc_multidrop_scheduler_current_position, 'after_customer_details'); ?>>
                            <?php esc_html_e('After Customer Details', 'multidrop-scheduler-for-woocommerce'); ?>
                        </option>
                        <option value="before_order_notes" <?php selected($wc_multidrop_scheduler_current_position, 'before_order_notes'); ?>>
                            <?php esc_html_e('Before Order Notes', 'multidrop-scheduler-for-woocommerce'); ?>
                        </option>
                        <option value="after_order_notes" <?php selected($wc_multidrop_scheduler_current_position, 'after_order_notes'); ?>>
                            <?php esc_html_e('After Order Notes (Default)', 'multidrop-scheduler-for-woocommerce'); ?>
                        </option>
                        <option value="review_order_before_submit" <?php selected($wc_multidrop_scheduler_current_position, 'review_order_before_submit'); ?>>
                            <?php esc_html_e('Before Submit Button', 'multidrop-scheduler-for-woocommerce'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php esc_html_e('Choose where the pickup location and date fields appear on the checkout page.', 'multidrop-scheduler-for-woocommerce'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <hr>

        <h2><?php esc_html_e('Daily Email Notifications', 'multidrop-scheduler-for-woocommerce'); ?></h2>
        <p><?php esc_html_e('Send a daily summary of all completed pickup and delivery orders for the current day, based on their fulfillment dates.', 'multidrop-scheduler-for-woocommerce'); ?></p>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="wc_multidrop_email_enabled"><?php esc_html_e('Enable Daily Summary Email', 'multidrop-scheduler-for-woocommerce'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="wc_multidrop_email_enabled" id="wc_multidrop_email_enabled" value="yes" <?php checked($wc_multidrop_email_enabled, 'yes'); ?>>
                        <?php esc_html_e('Send a daily email with today’s pickup and delivery orders', 'multidrop-scheduler-for-woocommerce'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wc_multidrop_email_recipients"><?php esc_html_e('Recipient Email Addresses', 'multidrop-scheduler-for-woocommerce'); ?></label>
                </th>
                <td>
                    <input type="text" class="regular-text" name="wc_multidrop_email_recipients" id="wc_multidrop_email_recipients" value="<?php echo esc_attr($wc_multidrop_email_recipients); ?>" />
                    <p class="description"><?php esc_html_e('Comma-separated list of email addresses.', 'multidrop-scheduler-for-woocommerce'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wc_multidrop_email_time"><?php esc_html_e('Send Time', 'multidrop-scheduler-for-woocommerce'); ?></label>
                </th>
                <td>
                    <input type="text" class="regular-text" name="wc_multidrop_email_time" id="wc_multidrop_email_time" value="<?php echo esc_attr($wc_multidrop_email_time); ?>" />
                    <p class="description"><?php esc_html_e('Time in HH:MM (store timezone) when the daily summary should be sent.', 'multidrop-scheduler-for-woocommerce'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Include in Summary', 'multidrop-scheduler-for-woocommerce'); ?></th>
                <td>
                    <label style="display:block;">
                        <input type="checkbox" name="wc_multidrop_email_include_pickup" value="yes" <?php checked($wc_multidrop_email_include_pickup, 'yes'); ?> />
                        <?php esc_html_e('Include pickup orders', 'multidrop-scheduler-for-woocommerce'); ?>
                    </label>
                    <label style="display:block;">
                        <input type="checkbox" name="wc_multidrop_email_include_delivery" value="yes" <?php checked($wc_multidrop_email_include_delivery, 'yes'); ?> />
                        <?php esc_html_e('Include delivery orders', 'multidrop-scheduler-for-woocommerce'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wc_multidrop_email_subject"><?php esc_html_e('Email Subject Template', 'multidrop-scheduler-for-woocommerce'); ?></label>
                </th>
                <td>
                    <input type="text" class="regular-text" name="wc_multidrop_email_subject" id="wc_multidrop_email_subject" value="<?php echo esc_attr($wc_multidrop_email_subject); ?>" />
                    <p class="description"><?php esc_html_e('Use {date} as placeholder for the date.', 'multidrop-scheduler-for-woocommerce'); ?></p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" class="button button-primary" value="<?php esc_html_e('Save Settings', 'multidrop-scheduler-for-woocommerce'); ?>">
        </p>
    </form>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top: 10px;">
        <?php wp_nonce_field('wc_multidrop_send_test_email'); ?>
        <input type="hidden" name="action" value="wc_multidrop_send_test_email" />
        <p>
            <button type="submit" class="button"><?php esc_html_e('Send Test Email', 'multidrop-scheduler-for-woocommerce'); ?></button>
        </p>
    </form>

    <hr>

    <h2><?php esc_html_e('Position Preview', 'multidrop-scheduler-for-woocommerce'); ?></h2>
    <div style="background: #f9f9f9; padding: 20px; border: 1px solid #ddd;">
        <p><?php esc_html_e('Checkout page structure:', 'multidrop-scheduler-for-woocommerce'); ?></p>
        <ol style="list-style: none; padding: 0; margin: 20px 0;">
            <li style="padding: 10px; margin: 5px 0; background: <?php echo $wc_multidrop_scheduler_current_position === 'before_customer_details' ? '#d4edda' : '#fff'; ?>; border-left: 3px solid <?php echo $wc_multidrop_scheduler_current_position === 'before_customer_details' ? '#28a745' : '#ddd'; ?>;">
                <strong><?php esc_html_e('1. Before Customer Details (Top)', 'multidrop-scheduler-for-woocommerce'); ?></strong>
                <?php if ($wc_multidrop_scheduler_current_position === 'before_customer_details') echo ' <span style="color: #28a745;">← ' . esc_html__('Current', 'multidrop-scheduler-for-woocommerce') . '</span>'; ?>
            </li>
            <li style="padding: 10px; margin: 5px 0; background: #f5f5f5; border-left: 3px solid #999;">
                <em><?php esc_html_e('Customer Details (Name, Email, etc.)', 'multidrop-scheduler-for-woocommerce'); ?></em>
            </li>
            <li style="padding: 10px; margin: 5px 0; background: <?php echo $wc_multidrop_scheduler_current_position === 'after_customer_details' ? '#d4edda' : '#fff'; ?>; border-left: 3px solid <?php echo $wc_multidrop_scheduler_current_position === 'after_customer_details' ? '#28a745' : '#ddd'; ?>;">
                <strong><?php esc_html_e('2. After Customer Details', 'multidrop-scheduler-for-woocommerce'); ?></strong>
                <?php if ($wc_multidrop_scheduler_current_position === 'after_customer_details') echo ' <span style="color: #28a745;">← ' . esc_html__('Current', 'multidrop-scheduler-for-woocommerce') . '</span>'; ?>
            </li>
            <li style="padding: 10px; margin: 5px 0; background: <?php echo $wc_multidrop_scheduler_current_position === 'before_order_notes' ? '#d4edda' : '#fff'; ?>; border-left: 3px solid <?php echo $wc_multidrop_scheduler_current_position === 'before_order_notes' ? '#28a745' : '#ddd'; ?>;">
                <strong><?php esc_html_e('3. Before Order Notes', 'multidrop-scheduler-for-woocommerce'); ?></strong>
                <?php if ($wc_multidrop_scheduler_current_position === 'before_order_notes') echo ' <span style="color: #28a745;">← ' . esc_html__('Current', 'multidrop-scheduler-for-woocommerce') . '</span>'; ?>
            </li>
            <li style="padding: 10px; margin: 5px 0; background: #f5f5f5; border-left: 3px solid #999;">
                <em><?php esc_html_e('Order Notes (Optional message)', 'multidrop-scheduler-for-woocommerce'); ?></em>
            </li>
            <li style="padding: 10px; margin: 5px 0; background: <?php echo $wc_multidrop_scheduler_current_position === 'after_order_notes' ? '#d4edda' : '#fff'; ?>; border-left: 3px solid <?php echo $wc_multidrop_scheduler_current_position === 'after_order_notes' ? '#28a745' : '#ddd'; ?>;">
                <strong><?php esc_html_e('4. After Order Notes (Default)', 'multidrop-scheduler-for-woocommerce'); ?></strong>
                <?php if ($wc_multidrop_scheduler_current_position === 'after_order_notes') echo ' <span style="color: #28a745;">← ' . esc_html__('Current', 'multidrop-scheduler-for-woocommerce') . '</span>'; ?>
            </li>
            <li style="padding: 10px; margin: 5px 0; background: #f5f5f5; border-left: 3px solid #999;">
                <em><?php esc_html_e('Order Review (Cart summary)', 'multidrop-scheduler-for-woocommerce'); ?></em>
            </li>
            <li style="padding: 10px; margin: 5px 0; background: <?php echo $wc_multidrop_scheduler_current_position === 'review_order_before_submit' ? '#d4edda' : '#fff'; ?>; border-left: 3px solid <?php echo $wc_multidrop_scheduler_current_position === 'review_order_before_submit' ? '#28a745' : '#ddd'; ?>;">
                <strong><?php esc_html_e('5. Before Submit Button', 'multidrop-scheduler-for-woocommerce'); ?></strong>
                <?php if ($wc_multidrop_scheduler_current_position === 'review_order_before_submit') echo ' <span style="color: #28a745;">← ' . esc_html__('Current', 'multidrop-scheduler-for-woocommerce') . '</span>'; ?>
            </li>
            <li style="padding: 10px; margin: 5px 0; background: #f5f5f5; border-left: 3px solid #999;">
                <em><?php esc_html_e('Place Order Button', 'multidrop-scheduler-for-woocommerce'); ?></em>
            </li>
        </ol>
    </div>

    <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin-top: 20px;">
        <h4 style="margin-top: 0; color: #856404;">💡 <?php esc_html_e('How It Works', 'multidrop-scheduler-for-woocommerce'); ?></h4>
        <ul style="color: #856404; margin: 0;">
            <li><strong><?php esc_html_e('Global Enable/Disable:', 'multidrop-scheduler-for-woocommerce'); ?></strong> <?php esc_html_e('Control if pickup is available at all', 'multidrop-scheduler-for-woocommerce'); ?></li>
            <li><strong><?php esc_html_e('Individual Location Active:', 'multidrop-scheduler-for-woocommerce'); ?></strong> <?php esc_html_e('Each location can be enabled/disabled separately', 'multidrop-scheduler-for-woocommerce'); ?></li>
            <li><strong><?php esc_html_e('Result:', 'multidrop-scheduler-for-woocommerce'); ?></strong> <?php esc_html_e('Pickup fields only show if globally enabled AND at least one location is active', 'multidrop-scheduler-for-woocommerce'); ?></li>
        </ul>
    </div>
</div>
