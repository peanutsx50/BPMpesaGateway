# BPMpesaGateway

A WordPress plugin that enables site administrators to require M-Pesa payment before users can register or join a BuddyPress-powered community.

## Features

- M-Pesa payment gateway integration
- Payment requirement for user registration
- BuddyPress community integration
- Secure payment processing
- Customizable payment settings

## Requirements

- WordPress 5.2+
- PHP 7.2+
- BuddyPress plugin
- Composer (for dependency management)

## Installation

1. Download or clone this plugin to your WordPress plugins directory:
   ```
   /wp-content/plugins/BPMpesaGateway/
   ```

2. Install Composer dependencies (if needed):
   ```
   composer install
   ```

3. Activate the plugin from the WordPress admin dashboard

## Usage

1. Navigate to the plugin settings in the WordPress admin panel
2. Configure your M-Pesa payment credentials
3. Set payment amount and terms for user registration
4. Users will be prompted to complete payment before accessing the community

## File Structure

```
BPMpesaGateway/
├── includes/
│   └── base/          # Core plugin classes
├── admin/             # Admin interface files
├── public/            # Public-facing files
├── vendor/            # Composer dependencies
├── composer.json      # Project dependencies
└── BPMpesaGateway.php # Main plugin file
```

## Author

Festus Murimi

## License

GPL 2.0 or later

## Support

For issues or questions, please contact the plugin author.