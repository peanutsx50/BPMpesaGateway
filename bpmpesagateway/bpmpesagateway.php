<?php

/**
 * The main plugin file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://surgetech.co.ke/bpmepesagateway
 * @since             1.0.0
 * @package           BPMpesaGateway
 *
 * @wordpress-plugin
 * Plugin Name:       BPMpesaGateway
 * Plugin URI:        https://surgetech.co.ke/bpmepesagateway
 * Description:       BPMpesaGateway is a WordPress plugin that turns your BuddyPress community into a paid membership site, requiring M-Pesa payment from visitors before they can join the community.
 * Version:           1.0.0
 * Author:            SurgeTech
 * Author URI:        https://surgetech.co.ke/
 * License:           GNU General Public License v2
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bpmepesagateway
 * Domain Path:       /languages
 * Requires Plugins:  buddypress
 */


// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

// Autoload dependencies using Composer
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Define plugin constants
define('BPMG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BPMG_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('BPMG_BASENAME', plugin_basename(__FILE__));
define('BPMG_VERSION', '1.0.0');

// namespace Inc;
use Inc\base\BPMG;
use Inc\base\BPMG_Activator;
use Inc\base\BPMG_Deactivator;


/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-plugin-name-activator.php
 */
function activate_bpmg_plugin()
{
    BPMG_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-plugin-name-deactivator.php
 */
function deactivate_bpmg_plugin()
{
    BPMG_Deactivator::deactivate();
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'activate_bpmg_plugin');
register_deactivation_hook(__FILE__, 'deactivate_bpmg_plugin');

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_bpmg_plugin()
{
    $BPMG = new BPMG();
    return $BPMG;
}

run_bpmg_plugin();
