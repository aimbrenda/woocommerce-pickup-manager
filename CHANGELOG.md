# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


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

- **2.1.0**: Global controls, maps, position settings, max advance booking
- **2.0.0**: Weekly schedules, date overrides, advanced features
- **1.0.0**: Initial release with basic functionality
