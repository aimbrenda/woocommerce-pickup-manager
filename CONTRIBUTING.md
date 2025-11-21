# Contributing to WooCommerce Pickup Location Manager

First off, thank you for considering contributing to WooCommerce Pickup Location Manager! It's people like you that make this plugin better for everyone.

## 📋 Table of Contents

- [Code of Conduct](#code-of-conduct)
- [How Can I Contribute?](#how-can-i-contribute)
- [Development Setup](#development-setup)
- [Coding Standards](#coding-standards)
- [Pull Request Process](#pull-request-process)
- [Reporting Bugs](#reporting-bugs)
- [Suggesting Features](#suggesting-features)
- [Translation](#translation)

## 🤝 Code of Conduct

This project and everyone participating in it is governed by our Code of Conduct. By participating, you are expected to uphold this code.

### Our Pledge

We pledge to make participation in our project a harassment-free experience for everyone, regardless of age, body size, disability, ethnicity, gender identity and expression, level of experience, nationality, personal appearance, race, religion, or sexual identity and orientation.

### Our Standards

**Examples of behavior that contributes to a positive environment:**
- Using welcoming and inclusive language
- Being respectful of differing viewpoints and experiences
- Gracefully accepting constructive criticism
- Focusing on what is best for the community
- Showing empathy towards other community members

**Examples of unacceptable behavior:**
- The use of sexualized language or imagery
- Trolling, insulting/derogatory comments, and personal or political attacks
- Public or private harassment
- Publishing others' private information without permission
- Other conduct which could reasonably be considered inappropriate

## 🎯 How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check existing issues as you might find that the problem has already been reported. When creating a bug report, include as many details as possible:

**Bug Report Template:**
```markdown
**Describe the bug**
A clear and concise description of what the bug is.

**To Reproduce**
Steps to reproduce the behavior:
1. Go to '...'
2. Click on '...'
3. Scroll down to '...'
4. See error

**Expected behavior**
A clear description of what you expected to happen.

**Screenshots**
If applicable, add screenshots to help explain your problem.

**Environment:**
 - WordPress Version: [e.g., 6.4]
 - WooCommerce Version: [e.g., 8.3]
 - Plugin Version: [e.g., 2.1.0]
 - PHP Version: [e.g., 8.1]
 - Browser: [e.g., Chrome 120]

**Additional context**
Add any other context about the problem here.
```

### Suggesting Features

Feature requests are welcome! Before creating a feature request, please check if it has already been suggested. When creating a feature request:

**Feature Request Template:**
```markdown
**Is your feature request related to a problem?**
A clear description of the problem. Ex. I'm always frustrated when [...]

**Describe the solution you'd like**
A clear and concise description of what you want to happen.

**Describe alternatives you've considered**
A clear description of any alternative solutions or features you've considered.

**Additional context**
Add any other context or screenshots about the feature request here.

**Would you be willing to help implement this?**
- [ ] Yes, I can submit a PR
- [ ] Yes, I can help test
- [ ] No, just suggesting
```

### Pull Requests

Pull requests are the best way to propose changes. We actively welcome your pull requests:

1. Fork the repo and create your branch from `main`
2. Make your changes
3. If you've added code, add tests
4. Ensure the test suite passes
5. Make sure your code follows our coding standards
6. Issue the pull request

## 💻 Development Setup

### Prerequisites

- PHP 7.4 or higher
- WordPress 5.8 or higher (use [Local](https://localwp.com/) for local development)
- WooCommerce 6.0 or higher
- Node.js and npm (for asset compilation, if needed)
- Git
- Code editor (VS Code recommended with WordPress extensions)

### Local Development Environment

We recommend using [Local by Flywheel](https://localwp.com/) for WordPress development:

1. **Install Local**
   - Download and install Local
   - Create a new WordPress site
   - Install WooCommerce plugin

2. **Clone the Repository**
   ```bash
   cd ~/Local Sites/your-site/app/public/wp-content/plugins/
   git clone https://github.com/yourusername/woocommerce-pickup-manager.git
   cd woocommerce-pickup-manager
   ```

3. **Activate the Plugin**
   - Go to WordPress admin → Plugins
   - Activate "WooCommerce Pickup Location Manager"

4. **Create a Development Branch**
   ```bash
   git checkout -b feature/your-feature-name
   ```

### Development Workflow

1. **Make Changes**
   - Edit PHP files in `/includes/`
   - Edit templates in `/templates/`
   - Edit styles in `/assets/css/`
   - Edit scripts in `/assets/js/`

2. **Test Your Changes**
   - Test in multiple browsers
   - Test on mobile devices
   - Test with different WooCommerce settings
   - Test with different themes

3. **Commit Your Changes**
   ```bash
   git add .
   git commit -m "Add feature: brief description"
   ```

4. **Push to GitHub**
   ```bash
   git push origin feature/your-feature-name
   ```

5. **Open Pull Request**
   - Go to GitHub
   - Click "New Pull Request"
   - Fill in the PR template

## 📝 Coding Standards

### PHP Coding Standards

We follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/):

**Key Points:**
- Use tabs for indentation
- Use single quotes for strings (unless interpolating)
- Space after control structures: `if ( condition ) {`
- Yoda conditions: `if ( 'value' === $variable ) {`
- No shorthand PHP tags: use `<?php` not `<?`
- Proper DocBlocks for functions and classes

**Example:**
```php
<?php
/**
 * Get all active pickup locations
 *
 * @param bool $include_inactive Whether to include inactive locations
 * @return array Array of location objects
 */
function get_pickup_locations( $include_inactive = false ) {
    if ( true === $include_inactive ) {
        // Get all locations
    } else {
        // Get only active locations
    }
}
```

### JavaScript Coding Standards

We follow [WordPress JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/):

**Key Points:**
- Use camelCase for variable names
- Use tabs for indentation
- Always use semicolons
- Use `===` instead of `==`
- Declare variables with `const` or `let`, not `var`

**Example:**
```javascript
jQuery( document ).ready( function( $ ) {
    const locationSelect = $( '#pickup_location_id' );
    let currentLocationId = null;

    locationSelect.on( 'change', function() {
        currentLocationId = $( this ).val();
        loadAvailableDates( currentLocationId );
    });
});
```

### CSS Coding Standards

**Key Points:**
- Use tabs for indentation
- One selector per line
- Properties on separate lines
- Use shorthand where possible
- Order properties logically

**Example:**
```css
.pickup-location-details {
    display: none;
    margin: 15px 0;
    padding: 15px;
    background: #f9f9f9;
    border-left: 3px solid #2271b1;
}
```

### Naming Conventions

**PHP:**
- Classes: `Class_Name_With_Underscores`
- Functions: `function_name_with_underscores()`
- Variables: `$variable_name_with_underscores`
- Constants: `CONSTANT_NAME_UPPERCASE`

**JavaScript:**
- Variables/Functions: `camelCase`
- Constants: `UPPER_CASE`
- Classes: `PascalCase`

**CSS:**
- Classes: `kebab-case`
- IDs: `kebab-case`

### Database Queries

Always use prepared statements:

```php
// Good
$wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}pickup_locations WHERE id = %d",
    $location_id
) );

// Bad
$wpdb->get_results( "SELECT * FROM {$wpdb->prefix}pickup_locations WHERE id = " . $location_id );
```

### Security Best Practices

1. **Sanitize Input**
   ```php
   $location_name = sanitize_text_field( $_POST['name'] );
   $location_address = sanitize_textarea_field( $_POST['address'] );
   $map_link = esc_url_raw( $_POST['map_link'] );
   ```

2. **Escape Output**
   ```php
   echo esc_html( $location_name );
   echo esc_url( $map_link );
   echo esc_attr( $location_id );
   ```

3. **Nonce Verification**
   ```php
   wp_nonce_field( 'save_pickup_location' );
   check_admin_referer( 'save_pickup_location' );
   ```

4. **Capability Checks**
   ```php
   if ( ! current_user_can( 'manage_woocommerce' ) ) {
       wp_die( 'No permission' );
   }
   ```

## 🔄 Pull Request Process

### Before Submitting

- [ ] Code follows WordPress coding standards
- [ ] All functions have proper DocBlocks
- [ ] Code is properly sanitized and escaped
- [ ] Tested in multiple browsers
- [ ] No PHP errors or warnings
- [ ] No JavaScript console errors
- [ ] Compatible with latest WordPress/WooCommerce

### PR Title Format

Use conventional commits format:
- `feat: Add time slot selection feature`
- `fix: Correct date picker position on mobile`
- `docs: Update installation instructions`
- `style: Format code according to standards`
- `refactor: Reorganize database class`
- `test: Add unit tests for date validation`

### PR Description Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update

## Testing
Describe how you tested your changes

## Screenshots (if applicable)
Add screenshots to help explain your changes

## Checklist
- [ ] My code follows the style guidelines of this project
- [ ] I have performed a self-review of my own code
- [ ] I have commented my code, particularly in hard-to-understand areas
- [ ] I have made corresponding changes to the documentation
- [ ] My changes generate no new warnings
- [ ] I have tested on multiple browsers
- [ ] I have tested on mobile devices
```

### Review Process

1. Maintainer reviews code
2. Automated tests run (if applicable)
3. Feedback provided
4. Changes requested if needed
5. Once approved, PR is merged
6. Changes included in next release

## 🐛 Reporting Bugs

### Security Issues

**DO NOT** create a public issue for security vulnerabilities. Instead:
1. Email security@yourdomain.com
2. Include "SECURITY" in the subject line
3. Provide detailed description
4. Wait for response before public disclosure

### Regular Bugs

Create an issue on GitHub with:
- Clear, descriptive title
- Steps to reproduce
- Expected vs actual behavior
- Environment details
- Screenshots if applicable

## 💡 Suggesting Features

We love new ideas! When suggesting features:

1. Check if it already exists
2. Explain the use case
3. Describe the proposed solution
4. Consider backwards compatibility
5. Be open to discussion

## 🌍 Translation

Help translate the plugin into your language:

1. **Using Poedit:**
   - Download [Poedit](https://poedit.net/)
   - Open `languages/pickup-location-manager.pot`
   - Translate strings
   - Save as `pickup-location-manager-{locale}.po`
   - Generate `.mo` file

2. **Using Loco Translate:**
   - Install [Loco Translate](https://wordpress.org/plugins/loco-translate/) plugin
   - Go to Loco Translate → Plugins
   - Select WooCommerce Pickup Location Manager
   - Create new translation
   - Translate and save

3. **Submit Translation:**
   - Fork repository
   - Add `.po` and `.mo` files to `/languages/`
   - Create pull request

## 📧 Communication

- **GitHub Issues**: For bugs and feature requests
- **GitHub Discussions**: For questions and general discussion
- **Email**: contact@yourdomain.com for private inquiries

## 📜 License

By contributing, you agree that your contributions will be licensed under the GPL-3.0 License.

## 🎉 Recognition

Contributors will be:
- Listed in the CONTRIBUTORS.md file
- Mentioned in release notes
- Added to the WordPress.org plugin page (if applicable)

## ❓ Questions?

Don't hesitate to ask! Open a discussion on GitHub or reach out via email.

---

Thank you for contributing! 🙏
