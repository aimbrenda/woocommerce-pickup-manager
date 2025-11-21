<?php
if (!defined('ABSPATH')) exit;

$total_locations = count($locations);
?>

<div class="wrap">
    <h1><?php _e('Import / Export Pickup Locations', 'wc-pickup-manager'); ?></h1>

    <?php if (isset($_GET['imported'])): ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php 
                $mode = $_GET['mode'] === 'replace' ? __('Replaced all locations and imported', 'wc-pickup-manager') : __('Imported', 'wc-pickup-manager');
                printf(__('%s %d location(s) successfully!', 'wc-pickup-manager'), $mode, intval($_GET['imported'])); 
                ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php
                switch($_GET['error']) {
                    case 'upload_failed':
                        _e('File upload failed. Please try again.', 'wc-pickup-manager');
                        break;
                    case 'invalid_json':
                        _e('Invalid JSON file. Please check the file format.', 'wc-pickup-manager');
                        break;
                    case 'invalid_format':
                        _e('Invalid file format. Please use a valid export file.', 'wc-pickup-manager');
                        break;
                    default:
                        _e('An error occurred during import.', 'wc-pickup-manager');
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
                <?php _e('Export Locations', 'wc-pickup-manager'); ?>
            </h2>

            <p><?php _e('Export all pickup locations, their settings, and date overrides to a JSON file.', 'wc-pickup-manager'); ?></p>

            <div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #2271b1; margin: 15px 0;">
                <h3 style="margin-top: 0; font-size: 14px;"><?php _e('What gets exported:', 'wc-pickup-manager'); ?></h3>
                <ul style="margin: 10px 0;">
                    <li>✅ <?php printf(__('%d location(s)', 'wc-pickup-manager'), $total_locations); ?></li>
                    <li>✅ <?php _e('All location settings (name, address, fees, delays)', 'wc-pickup-manager'); ?></li>
                    <li>✅ <?php _e('Weekly schedules', 'wc-pickup-manager'); ?></li>
                    <li>✅ <?php _e('Date overrides', 'wc-pickup-manager'); ?></li>
                    <li>✅ <?php _e('Active/inactive status', 'wc-pickup-manager'); ?></li>
                </ul>
            </div>

            <?php if ($total_locations > 0): ?>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <?php wp_nonce_field('export_pickup_locations'); ?>
                    <input type="hidden" name="action" value="export_pickup_locations">
                    <p>
                        <button type="submit" class="button button-primary button-large">
                            <span class="dashicons dashicons-download" style="vertical-align: middle;"></span>
                            <?php _e('Export All Locations', 'wc-pickup-manager'); ?>
                        </button>
                    </p>
                </form>

                <p class="description">
                    <?php _e('Filename format: pickup-locations-export-YYYY-MM-DD-HHMMSS.json', 'wc-pickup-manager'); ?>
                </p>
            <?php else: ?>
                <div class="notice notice-warning inline">
                    <p><?php _e('No locations to export. Please add locations first.', 'wc-pickup-manager'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- IMPORT SECTION -->
        <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
            <h2 style="margin-top: 0;">
                <span class="dashicons dashicons-upload" style="color: #2271b1;"></span>
                <?php _e('Import Locations', 'wc-pickup-manager'); ?>
            </h2>

            <p><?php _e('Import pickup locations from a previously exported JSON file.', 'wc-pickup-manager'); ?></p>

            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                <?php wp_nonce_field('import_pickup_locations'); ?>
                <input type="hidden" name="action" value="import_pickup_locations">

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="import_file"><?php _e('Select File', 'wc-pickup-manager'); ?> *</label>
                        </th>
                        <td>
                            <input type="file" name="import_file" id="import_file" accept=".json" required>
                            <p class="description"><?php _e('Choose a JSON file exported from this plugin', 'wc-pickup-manager'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="import_mode"><?php _e('Import Mode', 'wc-pickup-manager'); ?></label>
                        </th>
                        <td>
                            <fieldset>
                                <label style="display: block; margin-bottom: 10px;">
                                    <input type="radio" name="import_mode" value="add" checked>
                                    <strong><?php _e('Add to existing', 'wc-pickup-manager'); ?></strong>
                                    <br>
                                    <span class="description"><?php _e('Keep current locations and add imported ones', 'wc-pickup-manager'); ?></span>
                                </label>

                                <label style="display: block;">
                                    <input type="radio" name="import_mode" value="replace">
                                    <strong style="color: #d63638;"><?php _e('Replace all', 'wc-pickup-manager'); ?></strong>
                                    <br>
                                    <span class="description"><?php _e('⚠️ Delete all current locations and import new ones', 'wc-pickup-manager'); ?></span>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary button-large" onclick="return confirm('<?php esc_attr_e('Are you sure you want to import? This action cannot be undone.', 'wc-pickup-manager'); ?>');">
                        <span class="dashicons dashicons-upload" style="vertical-align: middle;"></span>
                        <?php _e('Import Locations', 'wc-pickup-manager'); ?>
                    </button>
                </p>
            </form>

            <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin-top: 20px;">
                <h4 style="margin-top: 0; color: #856404;">⚠️ <?php _e('Important Notes', 'wc-pickup-manager'); ?></h4>
                <ul style="margin: 0; color: #856404;">
                    <li><?php _e('Always backup your data before importing', 'wc-pickup-manager'); ?></li>
                    <li><?php _e('"Replace all" mode will delete ALL existing locations', 'wc-pickup-manager'); ?></li>
                    <li><?php _e('Duplicate names are allowed (locations will have different IDs)', 'wc-pickup-manager'); ?></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- USAGE GUIDE -->
    <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px; margin-top: 20px;">
        <h2><?php _e('Usage Guide', 'wc-pickup-manager'); ?></h2>

        <h3><?php _e('🔄 Migration Between Sites', 'wc-pickup-manager'); ?></h3>
        <ol>
            <li><?php _e('On OLD site: Go to Pickup Locations → Import/Export', 'wc-pickup-manager'); ?></li>
            <li><?php _e('Click "Export All Locations" and save the JSON file', 'wc-pickup-manager'); ?></li>
            <li><?php _e('On NEW site: Install and activate the plugin', 'wc-pickup-manager'); ?></li>
            <li><?php _e('Go to Pickup Locations → Import/Export', 'wc-pickup-manager'); ?></li>
            <li><?php _e('Upload the JSON file and click "Import Locations"', 'wc-pickup-manager'); ?></li>
        </ol>

        <h3><?php _e('💾 Backup & Restore', 'wc-pickup-manager'); ?></h3>
        <ol>
            <li><?php _e('Export your locations regularly as backup', 'wc-pickup-manager'); ?></li>
            <li><?php _e('Store the JSON file in a safe location', 'wc-pickup-manager'); ?></li>
            <li><?php _e('To restore: Import using "Replace all" mode', 'wc-pickup-manager'); ?></li>
        </ol>

        <h3><?php _e('📋 File Format Example', 'wc-pickup-manager'); ?></h3>
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
