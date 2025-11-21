<?php
if (!defined('ABSPATH')) exit;

$current_position = get_option('wc_pickup_manager_checkout_position', 'after_order_notes');
$pickup_enabled = get_option('wc_pickup_manager_enabled', 'yes');
?>

<div class="wrap">
    <h1><?php _e('Pickup Locations Settings', 'wc-pickup-manager'); ?></h1>

    <?php if (isset($_GET['updated'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Settings saved successfully.', 'wc-pickup-manager'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <?php wp_nonce_field('save_checkout_position'); ?>
        <input type="hidden" name="action" value="save_checkout_position">

        <h2><?php _e('General Settings', 'wc-pickup-manager'); ?></h2>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="pickup_enabled"><?php _e('Enable Pickup Locations', 'wc-pickup-manager'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="pickup_enabled" id="pickup_enabled" value="yes" <?php checked($pickup_enabled, 'yes'); ?>>
                        <?php _e('Enable pickup location selection at checkout', 'wc-pickup-manager'); ?>
                    </label>
                    <p class="description">
                        <?php _e('When disabled, pickup fields will not appear on checkout page. Individual locations can still be configured.', 'wc-pickup-manager'); ?>
                    </p>

                    <?php if ($pickup_enabled === 'yes'): ?>
                        <div style="margin-top: 10px; padding: 10px; background: #d4edda; border-left: 3px solid #28a745;">
                            <strong style="color: #155724;">✓ <?php _e('Pickup is currently ENABLED', 'wc-pickup-manager'); ?></strong>
                            <p style="margin: 5px 0 0 0; color: #155724;"><?php _e('Customers will see pickup options at checkout.', 'wc-pickup-manager'); ?></p>
                        </div>
                    <?php else: ?>
                        <div style="margin-top: 10px; padding: 10px; background: #f8d7da; border-left: 3px solid #dc3545;">
                            <strong style="color: #721c24;">✗ <?php _e('Pickup is currently DISABLED', 'wc-pickup-manager'); ?></strong>
                            <p style="margin: 5px 0 0 0; color: #721c24;"><?php _e('Customers will NOT see pickup options at checkout.', 'wc-pickup-manager'); ?></p>
                        </div>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <hr>

        <h2><?php _e('Checkout Page Settings', 'wc-pickup-manager'); ?></h2>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="checkout_position"><?php _e('Pickup Fields Position', 'wc-pickup-manager'); ?></label>
                </th>
                <td>
                    <select name="checkout_position" id="checkout_position" class="regular-text">
                        <option value="before_customer_details" <?php selected($current_position, 'before_customer_details'); ?>>
                            <?php _e('Before Customer Details (Top)', 'wc-pickup-manager'); ?>
                        </option>
                        <option value="after_customer_details" <?php selected($current_position, 'after_customer_details'); ?>>
                            <?php _e('After Customer Details', 'wc-pickup-manager'); ?>
                        </option>
                        <option value="before_order_notes" <?php selected($current_position, 'before_order_notes'); ?>>
                            <?php _e('Before Order Notes', 'wc-pickup-manager'); ?>
                        </option>
                        <option value="after_order_notes" <?php selected($current_position, 'after_order_notes'); ?>>
                            <?php _e('After Order Notes (Default)', 'wc-pickup-manager'); ?>
                        </option>
                        <option value="review_order_before_submit" <?php selected($current_position, 'review_order_before_submit'); ?>>
                            <?php _e('Before Submit Button', 'wc-pickup-manager'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php _e('Choose where the pickup location and date fields appear on the checkout page.', 'wc-pickup-manager'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" class="button button-primary" value="<?php _e('Save Settings', 'wc-pickup-manager'); ?>">
        </p>
    </form>

    <hr>

    <h2><?php _e('Position Preview', 'wc-pickup-manager'); ?></h2>
    <div style="background: #f9f9f9; padding: 20px; border: 1px solid #ddd;">
        <p><?php _e('Checkout page structure:', 'wc-pickup-manager'); ?></p>
        <ol style="list-style: none; padding: 0; margin: 20px 0;">
            <li style="padding: 10px; margin: 5px 0; background: <?php echo $current_position === 'before_customer_details' ? '#d4edda' : '#fff'; ?>; border-left: 3px solid <?php echo $current_position === 'before_customer_details' ? '#28a745' : '#ddd'; ?>;">
                <strong><?php _e('1. Before Customer Details (Top)', 'wc-pickup-manager'); ?></strong>
                <?php if ($current_position === 'before_customer_details') echo ' <span style="color: #28a745;">← ' . __('Current', 'wc-pickup-manager') . '</span>'; ?>
            </li>
            <li style="padding: 10px; margin: 5px 0; background: #f5f5f5; border-left: 3px solid #999;">
                <em><?php _e('Customer Details (Name, Email, etc.)', 'wc-pickup-manager'); ?></em>
            </li>
            <li style="padding: 10px; margin: 5px 0; background: <?php echo $current_position === 'after_customer_details' ? '#d4edda' : '#fff'; ?>; border-left: 3px solid <?php echo $current_position === 'after_customer_details' ? '#28a745' : '#ddd'; ?>;">
                <strong><?php _e('2. After Customer Details', 'wc-pickup-manager'); ?></strong>
                <?php if ($current_position === 'after_customer_details') echo ' <span style="color: #28a745;">← ' . __('Current', 'wc-pickup-manager') . '</span>'; ?>
            </li>
            <li style="padding: 10px; margin: 5px 0; background: <?php echo $current_position === 'before_order_notes' ? '#d4edda' : '#fff'; ?>; border-left: 3px solid <?php echo $current_position === 'before_order_notes' ? '#28a745' : '#ddd'; ?>;">
                <strong><?php _e('3. Before Order Notes', 'wc-pickup-manager'); ?></strong>
                <?php if ($current_position === 'before_order_notes') echo ' <span style="color: #28a745;">← ' . __('Current', 'wc-pickup-manager') . '</span>'; ?>
            </li>
            <li style="padding: 10px; margin: 5px 0; background: #f5f5f5; border-left: 3px solid #999;">
                <em><?php _e('Order Notes (Optional message)', 'wc-pickup-manager'); ?></em>
            </li>
            <li style="padding: 10px; margin: 5px 0; background: <?php echo $current_position === 'after_order_notes' ? '#d4edda' : '#fff'; ?>; border-left: 3px solid <?php echo $current_position === 'after_order_notes' ? '#28a745' : '#ddd'; ?>;">
                <strong><?php _e('4. After Order Notes (Default)', 'wc-pickup-manager'); ?></strong>
                <?php if ($current_position === 'after_order_notes') echo ' <span style="color: #28a745;">← ' . __('Current', 'wc-pickup-manager') . '</span>'; ?>
            </li>
            <li style="padding: 10px; margin: 5px 0; background: #f5f5f5; border-left: 3px solid #999;">
                <em><?php _e('Order Review (Cart summary)', 'wc-pickup-manager'); ?></em>
            </li>
            <li style="padding: 10px; margin: 5px 0; background: <?php echo $current_position === 'review_order_before_submit' ? '#d4edda' : '#fff'; ?>; border-left: 3px solid <?php echo $current_position === 'review_order_before_submit' ? '#28a745' : '#ddd'; ?>;">
                <strong><?php _e('5. Before Submit Button', 'wc-pickup-manager'); ?></strong>
                <?php if ($current_position === 'review_order_before_submit') echo ' <span style="color: #28a745;">← ' . __('Current', 'wc-pickup-manager') . '</span>'; ?>
            </li>
            <li style="padding: 10px; margin: 5px 0; background: #f5f5f5; border-left: 3px solid #999;">
                <em><?php _e('Place Order Button', 'wc-pickup-manager'); ?></em>
            </li>
        </ol>
    </div>

    <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin-top: 20px;">
        <h4 style="margin-top: 0; color: #856404;">💡 <?php _e('How It Works', 'wc-pickup-manager'); ?></h4>
        <ul style="color: #856404; margin: 0;">
            <li><strong><?php _e('Global Enable/Disable:', 'wc-pickup-manager'); ?></strong> <?php _e('Control if pickup is available at all', 'wc-pickup-manager'); ?></li>
            <li><strong><?php _e('Individual Location Active:', 'wc-pickup-manager'); ?></strong> <?php _e('Each location can be enabled/disabled separately', 'wc-pickup-manager'); ?></li>
            <li><strong><?php _e('Result:', 'wc-pickup-manager'); ?></strong> <?php _e('Pickup fields only show if globally enabled AND at least one location is active', 'wc-pickup-manager'); ?></li>
        </ul>
    </div>
</div>
