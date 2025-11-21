<?php
if (!defined('ABSPATH')) exit;
$is_edit = $location !== null;
$page_title = $is_edit ? 'Edit Pickup Location' : 'Add Pickup Location';
$default_schedule = array(0 => false, 1 => true, 2 => true, 3 => true, 4 => true, 5 => true, 6 => false);
$weekly_schedule = $is_edit && isset($location->weekly_schedule) ? $location->weekly_schedule : $default_schedule;
$day_names = array(0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday');
?>
<div class="wrap">
    <h1><?php echo esc_html($page_title); ?></h1>
    <?php if (isset($_GET['updated'])): ?><div class="notice notice-success is-dismissible"><p>Location updated successfully.</p></div><?php endif; ?>
    <?php if (isset($_GET['override_added'])): ?><div class="notice notice-success is-dismissible"><p>Date override added successfully.</p></div><?php endif; ?>
    <?php if (isset($_GET['override_deleted'])): ?><div class="notice notice-success is-dismissible"><p>Date override deleted successfully.</p></div><?php endif; ?>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <?php wp_nonce_field('save_pickup_location'); ?>
        <input type="hidden" name="action" value="save_pickup_location">
        <?php if ($is_edit): ?><input type="hidden" name="location_id" value="<?php echo esc_attr($location->id); ?>"><?php endif; ?>

        <table class="form-table">
            <tr>
                <th><label for="name">Location Name *</label></th>
                <td>
                    <input type="text" name="name" id="name" class="regular-text" value="<?php echo $is_edit ? esc_attr($location->name) : ''; ?>" required>
                    <p class="description">E.g., "Amsterdam Central", "Utrecht Store"</p>
                </td>
            </tr>
            <tr>
                <th><label for="address">Address *</label></th>
                <td>
                    <textarea name="address" id="address" rows="3" class="large-text" required><?php echo $is_edit ? esc_textarea($location->address) : ''; ?></textarea>
                    <p class="description">Full address shown to customers</p>
                </td>
            </tr>
            <tr>
                <th><label for="map_link">Map Link 🗺️</label></th>
                <td>
                    <input type="url" name="map_link" id="map_link" class="large-text" value="<?php echo $is_edit && isset($location->map_link) ? esc_url($location->map_link) : ''; ?>" placeholder="https://maps.google.com/?q=Your+Address">
                    <p class="description">
                        Optional: Add Google Maps or any map service link. Customers will see a "View on Map" button.<br>
                        <strong>Examples:</strong><br>
                        • Google Maps: <code>https://maps.google.com/?q=Damrak+123+Amsterdam</code><br>
                        • Short link: <code>https://goo.gl/maps/abc123</code><br>
                        • Apple Maps: <code>https://maps.apple.com/?q=Your+Address</code>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label for="pickup_fee">Pickup Fee (<?php echo get_woocommerce_currency_symbol(); ?>)</label></th>
                <td>
                    <input type="number" name="pickup_fee" id="pickup_fee" step="0.01" min="0" value="<?php echo $is_edit ? esc_attr($location->pickup_fee) : '0.00'; ?>">
                    <p class="description">Additional fee for pickup at this location (0 for free)</p>
                </td>
            </tr>
            <tr>
                <th><label for="min_delay_hours">Minimum Preparation Time</label></th>
                <td>
                    <input type="number" name="min_delay_hours" id="min_delay_hours" min="0" value="<?php echo $is_edit ? esc_attr($location->min_delay_hours) : '24'; ?>"> hours
                    <p class="description">Minimum time needed before order can be picked up (e.g., 24 = next day, 48 = in 2 days)</p>
                </td>
            </tr>
            <tr>
                <th><label for="max_advance_days">Maximum Advance Booking ⭐</label></th>
                <td>
                    <input type="number" name="max_advance_days" id="max_advance_days" min="1" max="365" value="<?php echo $is_edit ? esc_attr($location->max_advance_days) : '30'; ?>"> days
                    <p class="description">Maximum number of days in advance customers can book a pickup (e.g., 7 = one week, 14 = two weeks, 30 = one month)</p>
                </td>
            </tr>
            <tr>
                <th>Weekly Availability</th>
                <td>
                    <fieldset>
                        <div class="weekly-schedule">
                            <?php foreach ($day_names as $day_num => $day_name): ?>
                                <label style="display:block;margin-bottom:8px;">
                                    <input type="checkbox" name="weekly_schedule[<?php echo $day_num; ?>]" value="1" <?php checked(isset($weekly_schedule[$day_num]) && $weekly_schedule[$day_num]); ?>>
                                    <strong><?php echo esc_html($day_name); ?></strong>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="description">Select which days of the week this location is normally open for pickup. You can override specific dates below.</p>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th><label for="is_active">Active</label></th>
                <td>
                    <label>
                        <input type="checkbox" name="is_active" id="is_active" value="1" <?php checked($is_edit ? $location->is_active : true); ?>>
                        Location is active and available for selection
                    </label>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" class="button button-primary" value="<?php echo $is_edit ? 'Update Location' : 'Add Location'; ?>">
            <a href="<?php echo admin_url('admin.php?page=pickup-locations'); ?>" class="button">Cancel</a>
        </p>
    </form>

    <?php if ($is_edit): ?>
        <hr>
        <h2>Date Overrides</h2>
        <p>Override the weekly schedule for specific dates. Use this to close on holidays or open on normally closed days.</p>

        <div class="override-form-container">
            <h3>Add Date Override</h3>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('add_date_override'); ?>
                <input type="hidden" name="action" value="add_date_override">
                <input type="hidden" name="location_id" value="<?php echo esc_attr($location->id); ?>">
                <table class="form-table">
                    <tr>
                        <th style="width:150px;"><label for="override_date">Date *</label></th>
                        <td><input type="date" name="override_date" id="override_date" required></td>
                    </tr>
                    <tr>
                        <th><label for="is_open">Status</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="is_open" id="is_open" value="1" checked> Open for pickup
                            </label>
                            <p class="description">Check = open, Uncheck = closed</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="note">Note</label></th>
                        <td><input type="text" name="note" id="note" class="regular-text" placeholder="E.g., Christmas, Special opening"></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" class="button" value="Add Override"></p>
            </form>
        </div>

        <?php if (!empty($overrides)): ?>
            <h3>Existing Overrides</h3>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Date</th><th>Status</th><th>Note</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($overrides as $override): ?>
                        <tr>
                            <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($override->override_date))); ?></td>
                            <td><?php if ($override->is_open): ?><span style="color:green;">● Open</span><?php else: ?><span style="color:red;">● Closed</span><?php endif; ?></td>
                            <td><?php echo esc_html($override->note); ?></td>
                            <td>
                                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=delete_date_override&override_id=' . $override->id . '&location_id=' . $location->id), 'delete_override_' . $override->id); ?>" 
                                   class="button button-small" onclick="return confirm('Are you sure?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p><em>No date overrides set. The weekly schedule will be used for all dates.</em></p>
        <?php endif; ?>
    <?php endif; ?>
</div>
