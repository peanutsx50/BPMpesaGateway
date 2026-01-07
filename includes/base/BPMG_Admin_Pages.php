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

namespace Inc\base;

class BPMG_Admin_Pages
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
        // Behaviour toggles (checkboxes)
        register_setting(
            'bpmpesa_settings_group',
            'bpmpesa_allow_payments',
            [
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default'           => true,
            ]
        );

        register_setting(
            'bpmpesa_settings_group',
            'bpmpesa_save_transactions',
            [
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default'           => true,
            ]
        );

        register_setting(
            'bpmpesa_settings_group',
            'bpmpesa_show_paybill',
            [
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default'           => false,
            ]
        );

        // Mpesa details
        register_setting(
            'bpmpesa_settings_group',
            'bpmpesa_paybill',
            [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            ]
        );

        register_setting(
            'bpmpesa_settings_group',
            'bpmpesa_account',
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
                },
                'default'           => 0,
            ]
        );
    }
}
