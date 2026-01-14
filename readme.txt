=== BPMpesa Gateway ===
Contributors: Festus murimi
Donate link: https://bpmesagateway.com/
Tags: mpesa, payment, gateway, buddypress, registration, e-commerce
Requires at least: 5.0
Tested up to: 6.2.1
Stable tag: 1.0.0
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept M-Pesa payments on your WordPress site with BuddyPress integration for seamless registration and checkout experiences.

== Description ==

**BPMpesa Gateway** is a powerful WordPress plugin that integrates M-Pesa payment processing with BuddyPress member registration. Accept payments directly from your customers using Kenya's most popular mobile money solution.

= Features =

* **M-Pesa Integration** - Direct integration with Safaricom's M-Pesa API
* **BuddyPress Compatible** - Seamless integration with BuddyPress profile fields and registration workflow
* **STK Push** - Automatic payment prompts sent directly to customer phones
* **Real-time Payment Verification** - Polling mechanism for instant payment confirmation
* **Secure Transactions** - Industry-standard encryption and security protocols
* **Admin Dashboard** - Comprehensive settings and transaction monitoring
* **Callback Handling** - Automatic processing of M-Pesa callbacks and webhooks
* **Transaction Logging** - Complete audit trail of all payment transactions

= Requirements =

* WordPress 5.0+
* PHP 7.4+
* BuddyPress (optional, for member registration features)
* Active M-Pesa Business API credentials from Safaricom

= Getting Started =

1. Install and activate the plugin
2. Navigate to **BPMpesa Gateway Settings** in WordPress Admin
3. Enter your M-Pesa API credentials (Consumer Key, Consumer Secret, Shortcode, Passkey)
4. Configure payment amounts and account references
5. Test with sandbox credentials before going live

== Installation ==

1. Upload the `BPMpesaGateway` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the **Plugins** menu in WordPress
3. Go to the plugin settings and configure your M-Pesa API credentials
4. Customize payment amounts and descriptions
5. Add the payment form to your pages using shortcodes or widgets

== Frequently Asked Questions ==

= Do I need BuddyPress? =

Byes BuddyPress is required for the plugin to work. The plugin works by integrating seamlessly with BuddyPress for member registration workflows.

= What payment amounts can I accept? =

You can configure any amount in your plugin settings. M-Pesa typically supports amounts between KES 1 and KES 150,000 per transaction.

= Is this plugin secure? =

Yes. The plugin uses SSL/TLS encryption, nonce verification, and sanitized data handling. Always use production credentials only on HTTPS sites.

= How do I get M-Pesa API credentials? =

Visit the [Safaricom Developer Portal](https://developer.safaricom.co.ke/) to register for Business API access and obtain your credentials.

= Can I customize the payment form? =

Yes, the plugin supports hooks and filters for customizing the payment form and integrating with custom fields.

= What happens after payment? =

After successful payment, the customer can complete registration or proceed with their purchase. Payment data is automatically logged for your records.

== Screenshots ==

1. M-Pesa Payment Form - Clean, user-friendly payment interface
2. Admin Settings - Configure API credentials and payment parameters
3. Transaction Log - View all payment transactions and details
4. BuddyPress Integration - Seamless member registration with payment

== Changelog ==

= 1.0.0 =
* Initial release
* M-Pesa STK Push integration
* BuddyPress member registration support
* Real-time payment polling
* Admin dashboard and settings
* Transaction logging and audit trails
* Callback handling and verification

== Upgrade Notice ==

= 1.0.0 =
Initial stable release. Install now to start accepting M-Pesa payments on your WordPress site.