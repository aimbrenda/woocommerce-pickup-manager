<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
    <h1 class="wp-heading-inline">Pickup &amp; Delivery Options</h1>
    <a href="<?php echo esc_url( admin_url('admin.php?page=pickup-location-add')); ?>" class="page-title-action">Add New</a>
    <?php if (isset($_GET['added'])): ?><div class="notice notice-success is-dismissible"><p>Location added successfully.</p></div><?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?><div class="notice notice-success is-dismissible"><p>Location deleted successfully.</p></div><?php endif; ?>
    <?php if (empty($locations)): ?>
        <p>No pickup or delivery options found. Add your first option to get started.</p>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Name</th><th>Type</th><th>Address</th><th>Fee</th><th>Min. Delay</th><th>Max Advance</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($locations as $wc_multidrop_scheduler_location): ?>
                    <tr>
                        <td><strong><?php echo esc_html($wc_multidrop_scheduler_location->name); ?></strong></td>
                        <td><?php echo esc_html(ucfirst(isset($wc_multidrop_scheduler_location->fulfillment_type) ? $wc_multidrop_scheduler_location->fulfillment_type : 'pickup')); ?></td>
                        <td><?php echo esc_html(substr($wc_multidrop_scheduler_location->address, 0, 50)) . (strlen($wc_multidrop_scheduler_location->address) > 50 ? '...' : ''); ?></td>
                        <td><?php echo wp_kses_post( wc_price($wc_multidrop_scheduler_location->pickup_fee)); ?></td>
                        <td><?php echo esc_html($wc_multidrop_scheduler_location->min_delay_hours); ?>h</td>
                        <td><?php echo esc_html($wc_multidrop_scheduler_location->max_advance_days); ?>d</td>
                        <td><?php if ($wc_multidrop_scheduler_location->is_active): ?><span style="color:green;">● Active</span><?php else: ?><span style="color:#999;">● Inactive</span><?php endif; ?></td>
                        <td>
                            <a href="<?php echo esc_url( admin_url('admin.php?page=pickup-location-add&id=' . $wc_multidrop_scheduler_location->id)); ?>" class="button button-small">Edit</a>
                            <a href="<?php echo esc_url( wp_nonce_url(admin_url('admin-post.php?action=delete_pickup_location&id=' . $wc_multidrop_scheduler_location->id), 'delete_pickup_location_' . $wc_multidrop_scheduler_location->id)); ?>" 
                               class="button button-small" onclick="return confirm('Are you sure?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
