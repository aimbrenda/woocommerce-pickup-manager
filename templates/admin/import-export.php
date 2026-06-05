<?php
if (!defined('ABSPATH')) exit;

$wc_multidrop_scheduler_total_locations = count($locations);
?>

<div class="wrap">
    <h1><?php esc_html_e('Import / Export Pickup Locations', 'multidrop-scheduler-for-woocommerce'); ?></h1>

    <?php if (isset($_GET['imported'])): ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php 
                sanitize_text_field($mode = $_GET['mode']) === 'replace' ? esc_html__('Replaced all locations and imported', 'multidrop-scheduler-for-woocommerce') : esc_html__('Imported', 'multidrop-scheduler-for-woocommerce');
                /* translators: 1: Import mode (Add/Replace), 2: Number of locations */
                printf(esc_html__('%1$s %2$d location(s) successfully!', 'multidrop-scheduler-for-woocommerce'), esc_html($mode), intval($_GET['imported'])); 
                ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php
                switch(sanitize_text_field($_GET['error'])) {
                    case 'upload_failed':
                        esc_html_e('File upload failed. Please try again.', 'multidrop-scheduler-for-woocommerce');
                        break;
                    case 'invalid_json':
                        esc_html_e('Invalid JSON file. Please check the file format.', 'multidrop-scheduler-for-woocommerce');
                        break;
                    case 'invalid_format':
                        esc_html_e('Invalid file format. Please use a valid export file.', 'multidrop-scheduler-for-woocommerce');
                        break;
                    default:
                        esc_html_e('An error occurred during import.', 'multidrop-scheduler-for-woocommerce');
                }
                ?>
            </p>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">

        <!-- EXPORT SECTION -->
        <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
            <h2 style="margin-top: 0;">
                <span class="dashicons dashicons-download" style="color: #2271b1;"></span>
                <?php esc_html_e('Export Locations', 'multidrop-scheduler-for-woocommerce'); ?>
            </h2>

            <p><?php esc_html_e('Export all pickup locations, their settings, and date overrides to a JSON file.', 'multidrop-scheduler-for-woocommerce'); ?></p>

            <div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #2271b1; margin: 15px 0;">
                <h3 style="margin-top: 0; font-size: 14px;"><?php esc_html_e('What gets exported:', 'multidrop-scheduler-for-woocommerce'); ?></h3>
                <ul style="margin: 10px 0;">
                    <li>✅ <?php 
                                /* translators: %d: Number of locations */
                                printf(esc_html__('%d location(s)', 'multidrop-scheduler-for-woocommerce'), esc_html($wc_multidrop_scheduler_total_locations)); 
                            ?>
                    </li>
                    <li>✅ <?php esc_html_e('All location settings (name, address, fees, delays)', 'multidrop-scheduler-for-woocommerce'); ?></li>
                    <li>✅ <?php esc_html_e('Weekly schedules', 'multidrop-scheduler-for-woocommerce'); ?></li>
                    <li>✅ <?php esc_html_e('Date overrides', 'multidrop-scheduler-for-woocommerce'); ?></li>
                    <li>✅ <?php esc_html_e('Active/inactive status', 'multidrop-scheduler-for-woocommerce'); ?></li>
                </ul>
            </div>

            <?php if ($wc_multidrop_scheduler_total_locations > 0): ?>
                <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                    <?php wp_nonce_field('export_pickup_locations'); ?>
                    <input type="hidden" name="action" value="export_pickup_locations">
                    <p>
                        <button type="submit" class="button button-primary button-large">
                            <span class="dashicons dashicons-download" style="vertical-align: middle;"></span>
                            <?php esc_html_e('Export All Locations', 'multidrop-scheduler-for-woocommerce'); ?>
                        </button>
                    </p>
                </form>

                <p class="description">
                    <?php esc_html_e('Filename format: pickup-locations-export-YYYY-MM-DD-HHMMSS.json', 'multidrop-scheduler-for-woocommerce'); ?>
                </p>
            <?php else: ?>
                <div class="notice notice-warning inline">
                    <p><?php esc_html_e('No locations to export. Please add locations first.', 'multidrop-scheduler-for-woocommerce'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- IMPORT SECTION -->
        <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
            <h2 style="margin-top: 0;">
                <span class="dashicons dashicons-upload" style="color: #2271b1;"></span>
                <?php esc_html_e('Import Locations', 'multidrop-scheduler-for-woocommerce'); ?>
            </h2>

            <p><?php esc_html_e('Import pickup locations from a previously exported JSON file.', 'multidrop-scheduler-for-woocommerce'); ?></p>

            <form method="post" action="<?php echo esc_url( admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                <?php wp_nonce_field('import_pickup_locations'); ?>
                <input type="hidden" name="action" value="import_pickup_locations">

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="import_file"><?php esc_html_e('Select File', 'multidrop-scheduler-for-woocommerce'); ?> *</label>
                        </th>
                        <td>
                            <input type="file" name="import_file" id="import_file" accept=".json" required>
                            <p class="description"><?php esc_html_e('Choose a JSON file exported from this plugin', 'multidrop-scheduler-for-woocommerce'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="import_mode"><?php esc_html_e('Import Mode', 'multidrop-scheduler-for-woocommerce'); ?></label>
                        </th>
                        <td>
                            <fieldset>
                                <label style="display: block; margin-bottom: 10px;">
                                    <input type="radio" name="import_mode" value="add" checked>
                                    <strong><?php esc_html_e('Add to existing', 'multidrop-scheduler-for-woocommerce'); ?></strong>
                                    <br>
                                    <span class="description"><?php esc_html_e('Keep current locations and add imported ones', 'multidrop-scheduler-for-woocommerce'); ?></span>
                                </label>

                                <label style="display: block;">
                                    <input type="radio" name="import_mode" value="replace">
                                    <strong style="color: #d63638;"><?php esc_html_e('Replace all', 'multidrop-scheduler-for-woocommerce'); ?></strong>
                                    <br>
                                    <span class="description"><?php esc_html_e('⚠️ Delete all current locations and import new ones', 'multidrop-scheduler-for-woocommerce'); ?></span>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary button-large" onclick="return confirm('<?php esc_html_e('Are you sure you want to import? This action cannot be undone.', 'multidrop-scheduler-for-woocommerce'); ?>');">
                        <span class="dashicons dashicons-upload" style="vertical-align: middle;"></span>
                        <?php esc_html_e('Import Locations', 'multidrop-scheduler-for-woocommerce'); ?>
                    </button>
                </p>
            </form>

            <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin-top: 20px;">
                <h4 style="margin-top: 0; color: #856404;">⚠️ <?php esc_html_e('Important Notes', 'multidrop-scheduler-for-woocommerce'); ?></h4>
                <ul style="margin: 0; color: #856404;">
                    <li><?php esc_html_e('Always backup your data before importing', 'multidrop-scheduler-for-woocommerce'); ?></li>
                    <li><?php esc_html_e('"Replace all" mode will delete ALL existing locations', 'multidrop-scheduler-for-woocommerce'); ?></li>
                    <li><?php esc_html_e('Duplicate names are allowed (locations will have different IDs)', 'multidrop-scheduler-for-woocommerce'); ?></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- USAGE GUIDE -->
    <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px; margin-top: 20px;">
        <h2><?php esc_html_e('Usage Guide', 'multidrop-scheduler-for-woocommerce'); ?></h2>

        <h3><?php esc_html_e('🔄 Migration Between Sites', 'multidrop-scheduler-for-woocommerce'); ?></h3>
        <ol>
            <li><?php esc_html_e('On OLD site: Go to Pickup Locations → Import/Export', 'multidrop-scheduler-for-woocommerce'); ?></li>
            <li><?php esc_html_e('Click "Export All Locations" and save the JSON file', 'multidrop-scheduler-for-woocommerce'); ?></li>
            <li><?php esc_html_e('On NEW site: Install and activate the plugin', 'multidrop-scheduler-for-woocommerce'); ?></li>
            <li><?php esc_html_e('Go to Pickup Locations → Import/Export', 'multidrop-scheduler-for-woocommerce'); ?></li>
            <li><?php esc_html_e('Upload the JSON file and click "Import Locations"', 'multidrop-scheduler-for-woocommerce'); ?></li>
        </ol>

        <h3><?php esc_html_e('💾 Backup & Restore', 'multidrop-scheduler-for-woocommerce'); ?></h3>
        <ol>
            <li><?php esc_html_e('Export your locations regularly as backup', 'multidrop-scheduler-for-woocommerce'); ?></li>
            <li><?php esc_html_e('Store the JSON file in a safe location', 'multidrop-scheduler-for-woocommerce'); ?></li>
            <li><?php esc_html_e('To restore: Import using "Replace all" mode', 'multidrop-scheduler-for-woocommerce'); ?></li>
        </ol>

        <h3><?php esc_html_e('📋 File Format Example', 'multidrop-scheduler-for-woocommerce'); ?></h3>
        <pre style="background: #f5f5f5; padding: 15px; overflow-x: auto; font-size: 12px;">{
  "version": "2.1",
  "export_date": "2025-11-19 14:16:00",
  "site_url": "https://yoursite.com",
  "locations": [
    {
      "name": "Amsterdam Store",
      "address": "Damrak 123, Amsterdam",
      "pickup_fee": 2.50,
      "min_delay_hours": 24,
      "max_advance_days": 14,
      "weekly_schedule": {
        "0": false,
        "1": true,
        "2": true,
        "3": true,
        "4": true,
        "5": true,
        "6": false
      },
      "is_active": 1,
      "overrides": [
        {
          "date": "2025-12-25",
          "is_open": 0,
          "note": "Christmas"
        }
      ]
    }
  ]
}</pre>
    </div>
</div>

<style>
.notice.inline {
    padding: 10px;
    margin: 15px 0;
}
</style>
