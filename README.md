# Pickup Location Manager

![Version](https://img.shields.io/badge/version-2.4.2-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)
![WooCommerce](https://img.shields.io/badge/WooCommerce-6.0%2B-purple.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)
![License](https://img.shields.io/badge/license-GPL--3.0-green.svg)

A comprehensive WordPress plugin that adds pickup location functionality to WooCommerce, allowing customers to select pickup locations and dates during checkout with advanced scheduling options.

## ✨ Features

### Core Functionality
- 🗺️ **Multiple Pickup Locations** - Unlimited locations with individual settings
- 📅 **Date Picker Integration** - Interactive calendar with availability control
- ⏰ **Flexible Scheduling** - Weekly schedules with date-specific overrides
- 💰 **Location-Specific Fees** - Different pickup fees per location
- 🔗 **Map Integration** - Google Maps/Apple Maps links for each location
- 📧 **Order Integration** - Pickup info in orders, emails, and admin

### Advanced Features
- ⏱️ **Minimum Preparation Time** - Set minimum hours before pickup is available
- 📆 **Maximum Advance Booking** - Limit how far ahead customers can book
- 🔄 **Weekly Schedules** - Configure which days each location is open
- 📋 **Date Overrides** - Override weekly schedule for holidays/special days
- 🎛️ **Global Enable/Disable** - Master switch to control pickup system
- 📍 **Flexible Position** - Choose where pickup fields appear on checkout
- 💾 **Import/Export** - Backup and migrate location configurations
- 🌐 **Translation Ready** - Full i18n support

## 🚀 Installation


1. Download the latest release from [Releases](https://github.com/aimbrenda/woocommerce-pickup-manager/releases)
2. Upload the plugin 
3. Activate the plugin through WordPress admin
4. Navigate to **Pickup Locations** in the admin menu



### Requirements

- WordPress 5.8 or higher
- WooCommerce 6.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## 📖 Usage

### Quick Start

1. **Enable Pickup System**
   - Go to **Pickup Locations → Settings**
   - Check "Enable pickup location selection"
   - Save settings

2. **Add Your First Location**
   - Go to **Pickup Locations → Add New**
   - Fill in location details:
     - Name (e.g., "Amsterdam Central Store")
     - Address
     - Map Link (optional - Google Maps URL)
     - Pickup Fee (0 for free)
     - Min Delay: 24 hours (customers can't pickup same day)
     - Max Advance: 14 days (customers can book up to 2 weeks ahead)
   - Select weekly availability (e.g., Mon-Fri)
   - Check "Active"
   - Save

3. **Test on Checkout**
   - Add a product to cart
   - Go to checkout
   - Select your pickup location
   - Choose a pickup date

### Configuration Examples

#### Example 1: Basic Store Pickup
```
Name: Main Store
Address: 123 Main Street, City
Pickup Fee: €0.00
Min Delay: 24 hours
Max Advance: 30 days
Weekly: [✓] Mon [✓] Tue [✓] Wed [✓] Thu [✓] Fri
Active: [✓]
```

#### Example 2: Express Pickup Point
```
Name: Express Pickup (Premium)
Address: 456 Quick Street, City
Pickup Fee: €5.00
Min Delay: 2 hours
Max Advance: 7 days
Weekly: [✓] Mon-Sun (all days)
Active: [✓]
```

#### Example 3: Warehouse (Appointment Only)
```
Name: Warehouse Pickup
Address: 789 Industrial Ave, City
Pickup Fee: €0.00
Min Delay: 48 hours
Max Advance: 90 days
Weekly: [✓] Mon [✓] Wed [✓] Fri
Date Overrides:
  - Dec 25: CLOSED (Christmas)
  - Dec 31: CLOSED (New Year's Eve)
Active: [✓]
```

### Date Overrides

Use date overrides to handle holidays and special circumstances:

- **Close on a normally open day**: Uncheck "Open for pickup"
- **Open on a normally closed day**: Check "Open for pickup"
- **Add a note**: e.g., "Christmas Holiday", "Special Opening"

### Map Links

Supported map services:
- **Google Maps**: `https://maps.google.com/?q=Your+Address`
- **Apple Maps**: `https://maps.apple.com/?q=Your+Address`
- **OpenStreetMap**: `https://www.openstreetmap.org/?q=Your+Address`

### Import/Export

**Export locations:**
1. Go to **Pickup Locations → Import/Export**
2. Click "Export All Locations"
3. Save the JSON file

**Import locations:**
1. Go to **Pickup Locations → Import/Export**
2. Choose your JSON file
3. Select import mode:
   - **Add to existing**: Keeps current locations
   - **Replace all**: Deletes current and imports new
4. Click "Import Locations"

## 🛠️ Development

### File Structure

```
woocommerce-pickup-manager/
├── woocommerce-pickup-manager.php    # Main plugin file
├── uninstall.php                     # Cleanup on uninstall
├── includes/
│   ├── class-database.php            # Database operations
│   ├── class-admin.php               # Admin interface
│   ├── class-checkout.php            # Frontend checkout
│   └── class-import-export.php       # Import/export functionality
├── templates/
│   └── admin/
│       ├── locations-list.php        # Location list table
│       ├── location-form.php         # Add/edit form
│       ├── import-export.php         # Import/export UI
│       └── settings.php              # Settings page
├── assets/
│   ├── css/
│   │   ├── admin.css                 # Admin styles
│   │   └── checkout.css              # Frontend styles
│   └── js/
│       ├── admin.js                  # Admin scripts
│       └── checkout.js               # Flatpickr integration
├── languages/                         # Translation files
├── README.md
├── CONTRIBUTING.md
└── LICENSE
```

### Database Schema

**Table: `wp_pickup_locations`**
```sql
CREATE TABLE wp_pickup_locations (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL,
  address text NOT NULL,
  map_link text,
  pickup_fee decimal(10,2) DEFAULT 0,
  min_delay_hours int DEFAULT 24,
  max_advance_days int DEFAULT 30,
  weekly_schedule text NOT NULL,
  is_active tinyint(1) DEFAULT 1,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
);
```

**Table: `wp_pickup_date_overrides`**
```sql
CREATE TABLE wp_pickup_date_overrides (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  location_id mediumint(9) NOT NULL,
  override_date date NOT NULL,
  is_open tinyint(1) DEFAULT 0,
  note varchar(255),
  PRIMARY KEY (id),
  KEY location_id (location_id),
  KEY override_date (override_date)
);
```

### Hooks & Filters

#### Actions
- `wc_pickup_manager_location_saved` - After location is saved
- `wc_pickup_manager_location_deleted` - After location is deleted
- `wc_pickup_manager_override_added` - After date override is added

#### Filters
- `wc_pickup_manager_available_dates` - Modify available dates
- `wc_pickup_manager_location_fee` - Modify location fee
- `wc_pickup_manager_checkout_position` - Change default position

Example:
```php
// Change default checkout position
add_filter('wc_pickup_manager_checkout_position', function($position) {
    return 'before_customer_details';
});

// Modify location fee dynamically
add_filter('wc_pickup_manager_location_fee', function($fee, $location_id) {
    // Add 10% on weekends
    if (date('N') >= 6) {
        return $fee * 1.10;
    }
    return $fee;
}, 10, 2);
```

## 🌍 Translation

The plugin is translation-ready and uses WordPress i18n functions.

**Text Domain**: `wc-pickup-manager`

To translate:
1. Use [Poedit](https://poedit.net/) or [Loco Translate](https://wordpress.org/plugins/loco-translate/)
2. Create `.po` and `.mo` files
3. Place in `/languages/` directory

**Translations needed:**
- Spanish (es_ES)
- French (fr_FR)
- German (de_DE)
- Italian (it_IT)
- Dutch (nl_NL)

## 🧪 Testing

### Manual Testing Checklist

- [ ] Add/edit/delete locations
- [ ] Enable/disable locations individually
- [ ] Enable/disable pickup system globally
- [ ] Select location at checkout
- [ ] Date picker shows correct available dates
- [ ] Pickup fee added to cart correctly
- [ ] Order shows pickup info in admin
- [ ] Order emails include pickup details
- [ ] Map links work correctly
- [ ] Date overrides work
- [ ] Import/export functionality
- [ ] Position setting works

### Browser Testing
- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile browsers

## 🤝 Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

### Quick Contribution Guide

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This plugin is licensed under the GPL-3.0 License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- [Flatpickr](https://flatpickr.js.org/) - Date picker library
- [WooCommerce](https://woocommerce.com/) - E-commerce platform
- All contributors who have helped improve this plugin

## 💬 Support

- **Issues**: [GitHub Issues](https://github.com/aimbrenda/woocommerce-pickup-manager/issues)
- **Discussions**: [GitHub Discussions](https://github.com/aimbrenda/woocommerce-pickup-manager/discussions)
- **Documentation**: [Wiki](https://github.com/aimbrenda/woocommerce-pickup-manager/wiki)

## 🎯 Roadmap

### Planned Features

## 📊 Stats

![GitHub stars](https://img.shields.io/github/stars/aimbrenda/woocommerce-pickup-manager?style=social)
![GitHub forks](https://img.shields.io/github/forks/aimbrenda/woocommerce-pickup-manager?style=social)
![GitHub issues](https://img.shields.io/github/issues/aimbrenda/woocommerce-pickup-manager)
![GitHub pull requests](https://img.shields.io/github/issues-pr/aimbrenda/woocommerce-pickup-manager)

---

**Made with ❤️ for the WordPress community**
