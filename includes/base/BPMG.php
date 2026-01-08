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

namespace Inc\base;

use Inc\core\BPMG_Registration;
use Inc\core\BPMG_Mpesa;

class BPMG
{
    // Constructor to set up hooks
    public function __construct()
    {
        $this->register();
        $this->load_core_classes();
    }

    // register hooks
    public function register()
    {
        // enqueue hooks: admin and public, loads CSS and js files
        add_action('init', [BPMG_Post_Types::class, 'register_custom_post_type']);
        // admin hooks: manage mpesa posts columns, custom columns, sorting
        add_filter('manage_mpesa_posts_columns', [BPMG_Post_Types::class, 'set_custom_edit_mpesacolumns']);
        add_action('manage_mpesa_posts_custom_column', [BPMG_Post_Types::class, 'custom_mpesacolumns'], 10, 2);
        add_filter('manage_edit-mpesa_sortable_columns', [BPMG_Post_Types::class, 'sortable_columns']);
        add_action('pre_get_posts', [BPMG_Post_Types::class, 'handle_sorting_by_meta_value']);
        // admin hooks: add admin pages
        add_action('admin_menu', [BPMG_Admin_Pages::class, 'add_admin_pages']);
        // admin hooks: load CSS and JS files
        add_action('admin_enqueue_scripts', [BPMG_Enqueue_Admin::class, 'bpmg_enqueue_admin']); // loads CSS file
        add_action('admin_enqueue_scripts', [BPMG_Enqueue_Admin::class, 'bpmg_enqueue_admin_scripts']); // loads JS file
        add_action('wp_enqueue_scripts', [BPMG_Enqueue_Public::class, 'bpmg_enqueue_public']);
        // register REST endpoint
        add_action('rest_api_init', function () {
            register_rest_route('bpmpesa/v1', '/callback', [
                'methods' => 'POST',
                'callback' => [new BPMG_Mpesa(), 'handle_callback'],
                'permission_callback' => '__return_true',
            ]);
        });
    }

    // Load core classes
    private function load_core_classes()
    {
        $registration = new BPMG_Registration();
    }
}
