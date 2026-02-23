<?php

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

use BPMpesaGateway\Admin\BPMGAdmin;
use BPMpesaGateway\Public\BPMGPublic;

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
        $this->define_post_types();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies()
    {

        $this->loader = new BPMGLoader();
    }


    public function define_post_types()
    {
        $postTypes = new BPMGPostTypes();

        // Loader ($hook, $component, $callback, $priority = 10, $accepted_args = 1)
        $this->loader->add_action('init', $postTypes, 'register_custom_post_type');
        $this->loader->add_filter('manage_mpesa_posts_columns', $postTypes, 'set_custom_edit_mpesacolumns');
        $this->loader->add_action('manage_mpesa_posts_custom_column', $postTypes, 'custom_mpesacolumns', 10, 2);
        $this->loader->add_filter('manage_edit-mpesa_sortable_columns', $postTypes, 'sortable_columns');
        $this->loader->add_action('pre_get_posts', $postTypes, 'handle_sorting_by_meta_value');
    }

    public function define_admin_hooks()
    {
        $admin = new BPMGAdmin($this->bpmpesagateway, $this->version);

        // Loader ($hook, $component, $callback, $priority = 10, $accepted_args = 1)
        $this->loader->add_action('admin_menu', $admin, 'add_admin_pages');
        $this->loader->add_action('admin_init', $admin, 'register_settings');
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_scripts');
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_styles');
        $this->loader->add_action('plugins_loaded', $admin, 'check_ssl');
        $this->loader->add_action('plugins_loaded', $admin, 'load_textdomain');
    }

    public function define_public_hooks() {
        $public = new BPMGPublic($this->bpmpesagateway, $this->version);

        // Loader ($hook, $component, $callback, $priority = 10, $accepted_args = 1)
        $this->loader->add_action('wp_enqueue_scripts', $public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $public, 'enqueue_scripts');
        $this->loader->add_action('wp_enqueue_scripts', $public, 'localize_scripts');
        $this->loader->add_action('bp_before_registration_submit_buttons', $public, 'bpmg_add_custom_registration_fields');
        $this->loader->add_action('rest_api_init', $public, 'register_endpoints');

    }

    // Loop through all registered actions and filters and register them with WordPress.
    public function run()
    {
        $this->loader->run();
    }

    // internationalization
    public function load_textdomain(){
        load_plugin_textdomain(
            BPMG_TEXT_DOMAIN,
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }
}
