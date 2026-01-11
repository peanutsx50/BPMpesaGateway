<?php
/*
 * 
 * 
 * @link              http://example.com
 * @since             1.0.0
 * @package           BPMpesaGateway
 * 
 * 
 * Plugin Name:       BPMpesaGateway
 * Plugin URI:        https://example.com/plugins/the-basics/
 * Description:       BPMpesaGateway enables site administrators to require payment before users can register or join a BuddyPress-powered community.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Festus Murimi
 * Author URI:        https://www.linkedin.com/in/festus-murimi-b41aa2251/
 * License:           EULA
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bpmpesagateway
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
define('BPMG_VERSION', '1.0.0');
define('BPMG_LICENSE_SERVER', 'https://bp-mpesa-gateway-license.vercel.app/');

// namespace Inc;
use Inc\base\BPMG;
use Inc\base\BPMG_Activator;
use Inc\base\BPMG_Deactivator;
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;


/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-plugin-name-activator.php
 */
function activate_test_plugin()
{
    BPMG_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-plugin-name-deactivator.php
 */
function deactivate_test_plugin()
{
    BPMG_Deactivator::deactivate();
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'activate_test_plugin');
register_deactivation_hook(__FILE__, 'deactivate_test_plugin');

// Setup GitHub updates
if (class_exists('YahnisElsts\PluginUpdateChecker\v5\PucFactory')) {
    $updateChecker = PucFactory::buildUpdateChecker(
        BPMG_LICENSE_SERVER, // Your server URL 
        __FILE__,
        'bpmpesagateway'
    );

    $updateChecker->setBranch('main');

    // Correctly pass the license key into the filter scope
    $updateChecker->addQueryArgFilter(function ($queryArgs) {
        $licenseKey = get_option('BPMG_license_key', '');
        if (!empty($licenseKey)) {
            $queryArgs['license_key'] = $licenseKey;
        }
        return $queryArgs;
    });
}


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_test_plugin()
{
    $BPMG = new BPMG();
    return $BPMG;
}

run_test_plugin();
