<?php

/**
 * Handles admin pages for Test Plugin
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    BPMpesaGateway
 * @subpackage BPMpesaGateway/includes
 */

namespace BPMpesaGateway\Base;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class BPMGAdminPages
{
    public static function add_admin_pages()
    {
        add_menu_page('BPMpesaGateway', 'BPMpesaGateway', 'manage_options', 'bpmpesagateway', [self::class, 'admin_index'], 'dashicons-admin-generic', 110);
        add_action('admin_init', [self::class, 'register_settings']);
    }

    public static function admin_index()
    {
        // Admin page content goes here
        $template_path = BPMG_PLUGIN_PATH . 'includes/templates/admin-template.php';
        if (file_exists($template_path)) {
            include $template_path;
        }
    }

    // save settings
    public static function register_settings()
    {
        // NOW it's safe to save settings
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        // consumer key: (string)
        register_setting(
            'bpmpesa_settings_group',
            'bpmg_consumer_key',
            [
                'type' => 'string',
                'default' => '',
            ]
        );

        // consumer secret: (string)
        register_setting(
            'bpmpesa_settings_group',
            'bpmg_consumer_secret',
            [
                'type'    => 'string',
                'default' => '',
            ]
        );

        // shortcode: (string)
        register_setting(
            'bpmpesa_settings_group',
            'bpmg_shortcode',
            [
                'type'    => 'string',
                'default' => '',
            ]
        );

        // passkey: (string)
        register_setting(
            'bpmpesa_settings_group',
            'bpmg_passkey',
            [
                'type'    => 'string',
                'default' => '',
            ]
        );

        //account reference: (string)
        register_setting(
            'bpmpesa_settings_group',
            'bpmpesa_account_reference',
            [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            ]
        );

        //transaction reference: (string)
        register_setting(
            'bpmpesa_settings_group',
            'bpmpesa_transaction_reference',
            [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            ]
        );

        // Payment amount
        register_setting(
            'bpmpesa_settings_group',
            'bpmpesa_amount',
            [
                'type'              => 'number',
                'sanitize_callback' => function ($value) {
                    return max(0, (int) $value);
                }, //(int) $values cast anything other than number to 0
                'default'           => 0,
            ]
        );
    }
}
