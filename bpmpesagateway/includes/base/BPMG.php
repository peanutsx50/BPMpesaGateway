<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    BPMpesaGateway
 * @subpackage BPMpesaGateway/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    BPMpesaGateway
 * @subpackage BPMpesaGateway/includes
 * @author     Festus Murimi <murimifestus09@gmail.com>
 */

namespace BPMpesaGateway\Base;

use BPMpesaGateway\Core\BPMGRegistration;
use BPMpesaGateway\Core\BPMGMpesa;
use BPMpesaGateway\Core\BPMGUtils;
use WP_Error;
use WP_REST_Request;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class BPMG
{
    protected $loader;
    protected $version;
    protected $bpmpesagateway;

    // Constructor to set up hooks
    public function __construct()
    {
        if (defined('BPMG_VERSION')) {
            $this->version = BPMG_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->bpmpesagateway = 'BPMpesaGateway';

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies()
    {

        $this->loader = new BPMGLoader();
    }


    public function define_public_hooks() {}

    public function define_admin_hooks() {}

    // register hooks
    public function register()
    {
        // enqueue hooks: admin and public, loads CSS and js files
        add_action('init', [BPMGPostTypes::class, 'register_custom_post_type']);
        // admin hooks: manage mpesa posts columns, custom columns, sorting
        add_filter('manage_mpesa_posts_columns', [BPMGPostTypes::class, 'set_custom_edit_mpesacolumns']);
        add_action('manage_mpesa_posts_custom_column', [BPMGPostTypes::class, 'custom_mpesacolumns'], 10, 2);
        add_filter('manage_edit-mpesa_sortable_columns', [BPMGPostTypes::class, 'sortable_columns']);
        add_action('pre_get_posts', [BPMGPostTypes::class, 'handle_sorting_by_meta_value']);
        // admin hooks: add admin pages
        add_action('admin_menu', [BPMGAdminPages::class, 'add_admin_pages']);
        // admin hooks: load CSS and JS files
        add_action('admin_enqueue_scripts', [BPMGEnqueueAdmin::class, 'bpmg_enqueue_admin']); // loads CSS file
        add_action('admin_enqueue_scripts', [BPMGEnqueueAdmin::class, 'bpmg_enqueue_admin_scripts']); // loads JS file
        add_action('wp_enqueue_scripts', [BPMGEnqueuePublic::class, 'bpmg_enqueue_public']);
        // register REST endpoint
        add_action('rest_api_init', function () {
            register_rest_route('bpmpesa/v1', '/callback', [
                'methods' => ['POST', 'GET'],
                'callback' => [new BPMGMpesa(), 'handle_callback'],
                'permission_callback' => [$this, 'validate_safaricom_IP'],
                'show_in_index' => false, // Hide from REST API index
                'args'                => [
                    'bpmg_auth' => [
                        'required' => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ]);
        });
    }

    // load core classes
    public function run()
    {
        $this->loader->run();
    }
    // Load core classes
    private function load_core_classes()
    {
        $registration = new BPMGRegistration();
    }

    public function validate_safaricom_IP(WP_REST_Request $request)
    {
        //check for ssl
        if (!is_ssl()) {
            return new WP_Error('ssl_required', 'SSL is required for this endpoint', ['status' => 403]);
        }

        // check request IP address from server
        $raw_ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $client_ip = filter_var($raw_ip, FILTER_VALIDATE_IP) ? $raw_ip : 'UNKNOWN';

        // compare with expected IP addressess
        if (!BPMGUtils::is_safaricom_ip($client_ip)) {
            return new WP_Error('unauthorized_ip', 'Access denied', ['status' => 403]);
        }

        // obtain auth token passed as url param
        $url_token = $request->get_param('bpmg_auth');

        // We use a hash of your NONCE_SALT to create a unique-to-you key
        $secret_key = wp_hash(wp_salt('nonce'), 'nonce');

        // compare received against expected
        if (!hash_equals($secret_key, $url_token)) {
            return new WP_Error('invalid_token', 'Access denied', ['status' => 403]);
        }

        return true;
    }
}
