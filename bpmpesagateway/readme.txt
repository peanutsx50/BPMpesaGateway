=== BPMpesa Gateway ===
Contributors: surgetech
Tags: mpesa, payment, gateway, buddypress, registration
Requires at least: 6.2.1
Tested up to: 6.9
Stable tag: 1.0.0
Requires PHP: 8.1
Requires Plugins: buddypress
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Accept M-Pesa payments on your WordPress site with BuddyPress integration. Enable paid registration and premium community access via Kenya's most popular mobile money solution.

== Description ==

**BPMpesa Gateway** is a WordPress plugin that turns your BuddyPress community into a paid membership site, requiring M-Pesa payment from visitors before they can join the community. Seamlessly integrate Safaricom's M-Pesa payment processing with BuddyPress member registration to control access and monetize your community.

This plugin provides a complete payment solution for WordPress sites powered by BuddyPress, allowing you to require payment before account creation, control member registration, and manage all payments directly from your WordPress dashboard.

= Key Features =

* **M-Pesa STK Push Integration** - Automatic payment prompts sent directly to customer phones via Safaricom's STK Push API
* **BuddyPress Registration Control** - Seamlessly integrate payments into BuddyPress registration workflow to require payment before account creation
* **Real-time Payment Verification** - Intelligent polling mechanism for instant payment confirmation with timeout handling
* **Secure REST API** - Securely handle M-Pesa callbacks with proper authentication and nonce verification
* **Transaction Management** - Comprehensive custom post type for logging, tracking, and auditing all payment transactions
* **Admin Dashboard** - User-friendly settings panel for M-Pesa credentials, payment amounts, and transaction monitoring
* **Payment Polling** - Client-side polling with server verification to track payment status in real-time
* **Phone Number Validation** - Automatic validation of Kenyan phone numbers (254 format) on the frontend and backend
* **Customizable Payment Amounts** - Set any amount between KES 1 and KES 70,000 per transaction (M-Pesa limit)
* **Full Audit Trail** - Complete transaction logging with status tracking, timestamps, and payment details
* **Responsive Design** - Mobile-friendly payment interface optimized for all devices

= Technical Specifications =

* **Architecture:** Modern PHP with namespaced classes and Composer autoloading
* **Payment API:** Safaricom M-Pesa Business API (STK Push v1)
* **Custom Post Type:** `mpesa` for transaction logging and management
* **REST Endpoints:** RESTful API for payment callbacks and status polling
* **Security:** Nonce verification, input sanitization, data escaping, and secure credential handling
* **Hooks & Filters:** Extensible architecture with WordPress action and filter hooks

= What's Included =

* Complete M-Pesa integration ready to use
* BuddyPress registration form integration with custom payment fields
* Transaction logging with custom post type and meta data
* Admin settings page with credential management
* Real-time payment status polling
* Callback webhook handling
* Both sandbox and production environment support

== Installation ==

= Minimum Requirements =

* WordPress 6.2.1 or higher
* PHP 8.1 or higher
* BuddyPress plugin (required)
* Active Safaricom M-Pesa Business API account with valid credentials
* HTTPS enabled on your website (required for security)

= Installation Steps =

1. Download and extract the BPMpesaGateway plugin
2. Upload the `BPMpesaGateway` folder to `/wp-content/plugins/` directory
3. Navigate to **Plugins** in WordPress Admin and activate **BPMpesa Gateway**
4. Go to **BPMpesa Gateway** menu in the admin sidebar
5. Enter your M-Pesa API credentials (obtained from Safaricom Developer Portal)
6. Configure payment amount and transaction details
7. Test with sandbox credentials first before going live
8. Payment form automatically appears on BuddyPress registration page

= Getting Your M-Pesa Credentials =

1. Visit [Safaricom Developer Portal](https://developer.safaricom.co.ke/)
2. Create a developer account and register your application
3. Generate Consumer Key and Consumer Secret
4. Obtain your Business Shortcode and Passkey
5. Generate access tokens for API authentication
6. Configure Callback URL: `https://yoursite.com/wp-json/bpmpesa/v1/callback`

== Configuration ==

1. **Consumer Key** - OAuth consumer key from Safaricom portal
2. **Consumer Secret** - OAuth consumer secret from Safaricom portal
3. **Business Shortcode** - Your M-Pesa till/paybill number
4. **Passkey** - Security passkey from Safaricom (not your actual M-Pesa PIN)
5. **Account Reference Prefix** - Text to display on M-Pesa transactions (e.g., "Axios Tech Payment")
6. **Transaction Reference Label** - Description shown on user's M-Pesa statement
7. **Payment Amount** - Amount in KES required for registration (1-70,000)

**Security Note:** Store all credentials securely. Never share Consumer Secret, Passkey, or account details with untrusted parties. Use production credentials only on HTTPS-enabled sites with proper backups.

== Frequently Asked Questions ==

= Is BuddyPress required? =

Yes, BuddyPress is required for this plugin to function. The plugin integrates with BuddyPress registration workflows to require payment before account creation.

= What phone numbers are supported? =

The plugin validates Kenyan phone numbers in the format: 254712345678 (with country code 254). Phone numbers must be in international E.164 format.

= What payment amounts can I accept? =

M-Pesa supports amounts from KES 1 to KES 70,000 per transaction. You can set any amount within this range in the plugin settings.

= Is this plugin secure? =

The plugin implements multiple security measures:
* HTTPS/SSL encryption for all API communications
* Nonce verification for admin and AJAX requests
* Input sanitization and output escaping
* Secure credential storage with proper handling
* Server-side payment verification
* Callback authentication
* XSS and CSRF protection

Always use HTTPS on your production site and store credentials in secure environment variables when possible.

= How does payment verification work? =

1. User enters phone number and clicks "Send M-Pesa Request"
2. Plugin sends STK Push request to Safaricom's M-Pesa API
3. User receives payment prompt on their phone and enters PIN
4. M-Pesa sends callback webhook to your site confirming payment
5. Plugin updates transaction status in real-time
6. Frontend polling detects successful payment
7. User can complete registration after payment confirmation

= How are transactions logged? =

All payment transactions are logged as custom post type `mpesa` with detailed meta data including:
* Checkout Request ID
* Phone number used
* Amount paid
* Payment status (success/failed)
* M-Pesa result description
* Account reference
* Timestamp of transaction
* All data is searchable and sortable in admin dashboard

= What happens after failed payments? =

If payment fails, users can retry sending the payment request. The plugin tracks all attempts in the transaction log. Users must complete payment before they can finish registration.

= Can I customize the payment form? =

Yes, the plugin uses WordPress hooks and filters for customization:
* `bp_before_registration_submit_buttons` - Add custom fields
* `wp_ajax_bpmg_send_mpesa_request` - Customize payment request handling
* Additional hooks available for payment processing and verification

= What about refunds? =

Refunds must be processed directly through M-Pesa or the Safaricom portal. The plugin records all transactions but does not handle refunds automatically. Track refunds in your admin panel's transaction log.

= How do I test before going live? =

1. Safaricom provides a sandbox environment for testing
2. Use sandbox credentials in plugin settings
3. Make test payments with your phone number
4. Verify transactions appear in admin panel
5. Test the complete registration flow
6. Switch to production credentials once testing is complete

= Can I see payment history? =

Yes, all payment transactions are displayed in the **Mpesa Payments** admin section. You can:
* View all transactions with sortable columns
* Search by phone number or transaction reference
* Filter by payment status (success/failed)
* Export transaction data for accounting/reconciliation
* View detailed meta information for each transaction

= How do I get support? =

For technical support and documentation:
* Check the plugin FAQs and documentation
* Review the CODE_ANALYSIS.md file for technical details
* Contact the plugin author via LinkedIn

== Screenshots ==

1. `screenshot-1.png` - Clean M-Pesa payment form on BuddyPress registration page
2. `screenshot-2.png` - Admin settings page for M-Pesa credentials configuration
3. `screenshot-3.png` - Transaction log with sortable payment history
4. `screenshot-4.png` - Real-time payment status polling and confirmation

== Changelog ==

= 1.0.0 - February 21, 2026 =
* **Initial Release**
* Complete M-Pesa STK Push integration with Safaricom API
* BuddyPress member registration payment requirement
* Real-time payment status polling with automatic confirmation
* Secure callback webhook handling with verification
* Admin dashboard with settings and transaction management
* Custom post type for transaction logging and audit trails
* Phone number validation for Kenyan numbers
* Responsive design for mobile and desktop
* Full error handling and logging
* Security features: nonce verification, input sanitization, CSRF protection
* Support for both sandbox and production environments
* REST API endpoints for modern integration

== Upgrade Notice ==

= 1.0.0 =
Initial stable release. Install and activate to enable paid registration on your BuddyPress community. See the Configuration section for setup instructions.

== Additional Resources ==

* [Safaricom Developer Portal](https://developer.safaricom.co.ke/) - Get M-Pesa API credentials
* [BuddyPress Documentation](https://buddypress.org/documentation/) - Learn more about BuddyPress
* [WordPress Plugin Development Handbook](https://developer.wordpress.org/plugins/) - Plugin development best practices
* [M-Pesa API Documentation](https://developer.safaricom.co.ke/mpesa-apis) - STK Push API reference

== Security Recommendations ==

* Always use HTTPS on your production website
* Store sensitive credentials in secure environment variables, not in the database
* Regularly backup your WordPress database containing transaction records
* Keep WordPress, PHP, and all plugins updated to latest versions
* Enable rate limiting on your webhook endpoints to prevent DOS attacks
* Monitor transaction logs regularly for suspicious activity
* Use strong passwords for Safaricom Developer Portal account
* Implement two-factor authentication where available
* Never expose Consumer Secret or other credentials in code repositories

== License ==

This plugin is licensed under the GNU General Public License v2 or later. See LICENSE.txt for complete terms.

Copyright © 2024-2026 SurgeTech

== Support ==

For bug reports, features requests, or security issues:
* Website: https://surgetech.co.ke/
* Email: Visit the website for contact information

---

**Made with ❤️ by SurgeTech for the WordPress and BuddyPress communities**