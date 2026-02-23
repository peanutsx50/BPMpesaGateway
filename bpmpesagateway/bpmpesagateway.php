<?php

/**
 * The main plugin file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://surgetech.co.ke/bpmpesagateway
 * @since             1.0.0
 * @package           BPMpesaGateway
 *
 * @wordpress-plugin
 * Plugin Name:       BPMpesaGateway
 * Plugin URI:        https://surgetech.co.ke/bpmpesagateway
 * Description:       BPMpesaGateway is a WordPress plugin that turns your BuddyPress community into a paid membership site, requiring M-Pesa payment from visitors before they can join the community.
 * Version:           1.0.0
 * Author:            SurgeTech
 * Author URI:        https://surgetech.co.ke/
 * License:           GPLv2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 6.2.1
 * Requires PHP:      8.1
 * Tested up to:      6.9
 * Text Domain:       bpmpesagateway
 * Domain Path:       /languages
 * Requires Plugins:  buddypress
 */


// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

// Autoload dependencies using Composer
$autoload = __DIR__ . '/vendor/autoload.php';
if (! file_exists($autoload)) {
    wp_die(
        'BPMpesaGateway requires Composer dependencies. Please run <code>composer install</code> in the plugin directory.',
        'BPMpesaGateway — Missing Dependencies',
        array('exit_status' => 1)
    );
}
require_once $autoload;

// namespace Inc after autoload;
use BPMpesaGateway\Base\BPMG;
use BPMpesaGateway\Base\BPMGActivator;
use BPMpesaGateway\Base\BPMGDeactivator;


// Define plugin constants
define('BPMG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BPMG_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('BPMG_BASENAME', plugin_basename(__FILE__));

// Paths for PHP includes
define('BPMG_ADMIN_PARTIALS', BPMG_PLUGIN_PATH . 'admin/partials/');
define('BPMG_PUBLIC_PARTIALS', BPMG_PLUGIN_PATH . 'public/partials/');

// URLs for enqueued assets
define('BPMG_ADMIN_CSS_URL', BPMG_PLUGIN_URL . 'admin/css/');
define('BPMG_PUBLIC_CSS_URL', BPMG_PLUGIN_URL . 'public/css/');
define('BPMG_ADMIN_JS_URL', BPMG_PLUGIN_URL . 'admin/js/dist/');
define('BPMG_PUBLIC_JS_URL', BPMG_PLUGIN_URL . 'public/js/dist/');

define('BPMG_TEXT_DOMAIN', 'bpmpesagateway');
define('BPMG_VERSION', '1.0.0');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-plugin-name-activator.php
 */
function bpmg_activate_plugin()
{
    BPMGActivator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-plugin-name-deactivator.php
 */
function bpmg_deactivate_plugin()
{
    BPMGDeactivator::deactivate();
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'bpmg_activate_plugin');
register_deactivation_hook(__FILE__, 'bpmg_deactivate_plugin');

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function bpmg_run_plugin()
{
    $BPMG = new BPMG();
    $BPMG->run();
}

bpmg_run_plugin();
