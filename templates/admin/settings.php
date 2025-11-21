<?php
if (!defined('ABSPATH')) exit;

$wc_pickup_current_position = get_option('wc_pickup_manager_checkout_position', 'after_order_notes');
$wc_pickup_enabled  = get_option('wc_pickup_manager_enabled', 'yes');
?>

<div class="wrap">
    <h1><?php esc_html_e('Pickup Locations Settings', 'pickup-location-manager'); ?></h1>

    <?php if (isset($_GET['updated'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Settings saved successfully.', 'pickup-location-manager'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('save_checkout_position'); ?>
        <input type="hidden" name="action" value="save_checkout_position">

        <h2><?php esc_html_e('General Settings', 'pickup-location-manager'); ?></h2>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="pickup_enabled"><?php esc_html_e('Enable Pickup Locations', 'pickup-location-manager'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="pickup_enabled" id="pickup_enabled" value="yes" <?php checked($pickup_enabled, 'yes'); ?>>
                        <?php esc_html_e('Enable pickup location selection at checkout', 'pickup-location-manager'); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e('When disabled, pickup fields will not appear on checkout page. Individual locations can still be configured.', 'pickup-location-manager'); ?>
                    </p>

                    <?php if ($wc_pickup_enabled=== 'yes'): ?>
                        <div style="margin-top: 10px; padding: 10px; background: #d4edda; border-left: 3px solid #28a745;">
                            <strong style="color: #155724;">✓ <?php esc_html_e('Pickup is currently ENABLED', 'pickup-location-manager'); ?></strong>
                            <p style="margin: 5px 0 0 0; color: #155724;"><?php esc_html_e('Customers will see pickup options at checkout.', 'pickup-location-manager'); ?></p>
                        </div>
                    <?php else: ?>
                        <div style="margin-top: 10px; padding: 10px; background: #f8d7da; border-left: 3px solid #dc3545;">
                            <strong style="color: #721c24;">✗ <?php esc_html_e('Pickup is currently DISABLED', 'pickup-location-manager'); ?></strong>
                            <p style="margin: 5px 0 0 0; color: #721c24;"><?php esc_html_e('Customers will NOT see pickup options at checkout.', 'pickup-location-manager'); ?></p>
                        </div>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <hr>

        <h2><?php esc_html_e('Checkout Page Settings', 'pickup-location-manager'); ?></h2>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="checkout_position"><?php esc_html_e('Pickup Fields Position', 'pickup-location-manager'); ?></label>
                </th>
                <td>
                    <select name="checkout_position" id="checkout_position" class="regular-text">
                        <option value="before_customer_details" <?php selected($wc_pickup_current_position, 'before_customer_details'); ?>>
                            <?php esc_html_e('Before Customer Details (Top)', 'pickup-location-manager'); ?>
                        </option>
                        <option value="after_customer_details" <?php selected($wc_pickup_current_position, 'after_customer_details'); ?>>
                            <?php esc_html_e('After Customer Details', 'pickup-location-manager'); ?>
                        </option>
                        <option value="before_order_notes" <?php selected($wc_pickup_current_position, 'before_order_notes'); ?>>
                            <?php esc_html_e('Before Order Notes', 'pickup-location-manager'); ?>
                        </option>
                        <option value="after_order_notes" <?php selected($wc_pickup_current_position, 'after_order_notes'); ?>>
                            <?php esc_html_e('After Order Notes (Default)', 'pickup-location-manager'); ?>
                        </option>
                        <option value="review_order_before_submit" <?php selected($wc_pickup_current_position, 'review_order_before_submit'); ?>>
                            <?php esc_html_e('Before Submit Button', 'pickup-location-manager'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php esc_html_e('Choose where the pickup location and date fields appear on the checkout page.', 'pickup-location-manager'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" class="button button-primary" value="<?php esc_html_e('Save Settings', 'pickup-location-manager'); ?>">
        </p>
    </form>

    <hr>

    <h2><?php esc_html_e('Position Preview', 'pickup-location-manager'); ?></h2>
    <div style="background: #f9f9f9; padding: 20px; border: 1px solid #ddd;">
        <p><?php esc_html_e('Checkout page structure:', 'pickup-location-manager'); ?></p>
        <ol style="list-style: none; padding: 0; margin: 20px 0;">
            <li style="padding: 10px; margin: 5px 0; background: <?php echo $wc_pickup_current_position === 'before_customer_details' ? '#d4edda' : '#fff'; ?>; border-left: 3px solid <?php echo $wc_pickup_current_position === 'before_customer_details' ? '#28a745' : '#ddd'; ?>;">
                <strong><?php esc_html_e('1. Before Customer Details (Top)', 'pickup-location-manager'); ?></strong>
                <?php if ($wc_pickup_current_position === 'before_customer_details') echo ' <span style="color: #28a745;">← ' . esc_html__('Current', 'pickup-location-manager') . '</span>'; ?>
            </li>
            <li style="padding: 10px; margin: 5px 0; background: #f5f5f5; border-left: 3px solid #999;">
                <em><?php esc_html_e('Customer Details (Name, Email, etc.)', 'pickup-location-manager'); ?></em>
            </li>
            <li style="padding: 10px; margin: 5px 0; background: <?php echo $wc_pickup_current_position === 'after_customer_details' ? '#d4edda' : '#fff'; ?>; border-left: 3px solid <?php echo $wc_pickup_current_position === 'after_customer_details' ? '#28a745' : '#ddd'; ?>;">
                <strong><?php esc_html_e('2. After Customer Details', 'pickup-location-manager'); ?></strong>
                <?php if ($wc_pickup_current_position === 'after_customer_details') echo ' <span style="color: #28a745;">← ' . esc_html__('Current', 'pickup-location-manager') . '</span>'; ?>
            </li>
            <li style="padding: 10px; margin: 5px 0; background: <?php echo $wc_pickup_current_position === 'before_order_notes' ? '#d4edda' : '#fff'; ?>; border-left: 3px solid <?php echo $wc_pickup_current_position === 'before_order_notes' ? '#28a745' : '#ddd'; ?>;">
                <strong><?php esc_html_e('3. Before Order Notes', 'pickup-location-manager'); ?></strong>
                <?php if ($wc_pickup_current_position === 'before_order_notes') echo ' <span style="color: #28a745;">← ' . esc_html__('Current', 'pickup-location-manager') . '</span>'; ?>
            </li>
            <li style="padding: 10px; margin: 5px 0; background: #f5f5f5; border-left: 3px solid #999;">
                <em><?php esc_html_e('Order Notes (Optional message)', 'pickup-location-manager'); ?></em>
            </li>
            <li style="padding: 10px; margin: 5px 0; background: <?php echo $wc_pickup_current_position === 'after_order_notes' ? '#d4edda' : '#fff'; ?>; border-left: 3px solid <?php echo $wc_pickup_current_position === 'after_order_notes' ? '#28a745' : '#ddd'; ?>;">
                <strong><?php esc_html_e('4. After Order Notes (Default)', 'pickup-location-manager'); ?></strong>
                <?php if ($wc_pickup_current_position === 'after_order_notes') echo ' <span style="color: #28a745;">← ' . esc_html__('Current', 'pickup-location-manager') . '</span>'; ?>
            </li>
            <li style="padding: 10px; margin: 5px 0; background: #f5f5f5; border-left: 3px solid #999;">
                <em><?php esc_html_e('Order Review (Cart summary)', 'pickup-location-manager'); ?></em>
            </li>
            <li style="padding: 10px; margin: 5px 0; background: <?php echo $wc_pickup_current_position === 'review_order_before_submit' ? '#d4edda' : '#fff'; ?>; border-left: 3px solid <?php echo $wc_pickup_current_position === 'review_order_before_submit' ? '#28a745' : '#ddd'; ?>;">
                <strong><?php esc_html_e('5. Before Submit Button', 'pickup-location-manager'); ?></strong>
                <?php if ($wc_pickup_current_position === 'review_order_before_submit') echo ' <span style="color: #28a745;">← ' . esc_html__('Current', 'pickup-location-manager') . '</span>'; ?>
            </li>
            <li style="padding: 10px; margin: 5px 0; background: #f5f5f5; border-left: 3px solid #999;">
                <em><?php esc_html_e('Place Order Button', 'pickup-location-manager'); ?></em>
            </li>
        </ol>
    </div>

    <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin-top: 20px;">
        <h4 style="margin-top: 0; color: #856404;">💡 <?php esc_html_e('How It Works', 'pickup-location-manager'); ?></h4>
        <ul style="color: #856404; margin: 0;">
            <li><strong><?php esc_html_e('Global Enable/Disable:', 'pickup-location-manager'); ?></strong> <?php esc_html_e('Control if pickup is available at all', 'pickup-location-manager'); ?></li>
            <li><strong><?php esc_html_e('Individual Location Active:', 'pickup-location-manager'); ?></strong> <?php esc_html_e('Each location can be enabled/disabled separately', 'pickup-location-manager'); ?></li>
            <li><strong><?php esc_html_e('Result:', 'pickup-location-manager'); ?></strong> <?php esc_html_e('Pickup fields only show if globally enabled AND at least one location is active', 'pickup-location-manager'); ?></li>
        </ul>
    </div>
</div>
