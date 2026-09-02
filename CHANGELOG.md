# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.1.1] - 2026-09-02
### Fixed
- Refined upgrade hook wiring to run as a global plugins_loaded callback instead of from within the main class constructor, ensuring cleaner and more predictable initialization after updates.

## [3.1.0] - 2026-09-02
### Added
- Daily fulfillment summary email for completed pickup and delivery orders based on fulfillment dates.
- Aggregated preparation summary table with total quantities per product across all orders.
- Configurable email notifications (enabled flag, recipients, send time, subject template, pickup/delivery toggles).
- Test email button in settings to verify configuration.

### Changed
- Initialization now includes a version-based upgrade routine to ensure database tables are created on update.

## [3.0.0] - 2026-06-09
### Added
- Added full delivery fulfillment support in addition to pickup.
- Added admin location option type selection for `pickup` / `delivery`.
- Added delivery processing date calculation and delivery note rendering.
- Added thank-you page summary for pickup and delivery orders.
- Added checkout AJAX handling for option details and available dates.
- Added shipping-like fulfillment labels and checkout fee integration for selected options.
- Added cart text override for WooCommerce shipping step.

### Fixed
- Prevented malformed delivery dates from breaking thank-you rendering.
- Improved pickup/delivery order meta handling and cleanup.
- Ensured map link and address output only when present.
- Improved import/export handling for `fulfillment_type` and overrides.

### Changed
- Refactored checkout integration to support both pickup and delivery flows.
- Updated database schema and location storage to include `fulfillment_type`.

## [2.4.3] - 2025-11-21
- Implemented main plugin file with initialization and activation hooks.
- Added classes for database management, admin interface, checkout integration, and import/export functionality.
- Created readme file detailing plugin features, requirements, and usage.

## [2.4.2] - 2025-11-21
- Plugin renaming

## [2.4.1] - 2025-11-21

### Added
- Global enable/disable toggle for pickup system
- Map link integration (Google Maps, Apple Maps, etc.)
- Configurable checkout field position (5 options)
- Maximum advance booking limit per location
- Import/export functionality for location backup and migration
- Visual status indicators in admin
- Complete documentation and guides

### Fixed
- Position setting not applying correctly to checkout page
- Location active/inactive toggle not working
- Pickup fee displaying as HTML instead of numeric value
- Date validation for min/max advance booking

### Changed
- Improved admin interface with better visual feedback
- Enhanced settings page with position preview
- Better error handling and validation messages

## [2.0.0] - 2025-11-19

### Added
- Weekly schedule with day-of-week selection
- Date override system for holidays and special days
- Minimum preparation time (min_delay_hours)
- Location-specific pickup fees
- Interactive date picker with Flatpickr
- Order integration (admin, emails, order details)
- Complete architecture rewrite

### Changed
- Moved from simple date list to complex scheduling system
- Improved database schema for better performance
- Better code organization with separate classes

### Deprecated
- Old simple date picker interface

## [1.0.0] - 2025-11-15

### Added
- Initial release
- Basic pickup location management
- Simple date selection
- Location dropdown at checkout
- Basic order integration

---

## Version History Summary

- **3.1.1**: Upgrade hook wiring refinement
- **3.1.0**: Daily email notifications and upgrade-safe initialization
- **3.0.0**: Delivery fulfillment support
- **2.4.3**: Refactoring
- **2.4.2**: Renaming
- **2.4.1**: Global controls, maps, position settings, max advance booking
- **2.0.0**: Weekly schedules, date overrides, advanced features
- **1.0.0**: Initial release with basic functionality
