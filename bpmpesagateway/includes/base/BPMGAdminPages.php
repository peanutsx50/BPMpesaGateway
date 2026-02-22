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

use BPMpesaGateway\Core\BPMGOptions;
use BPMpesaGateway\Core\BPMGUtils;

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

        // register settings from options[array_key]

        register_setting(
            'bpmpesagateway_settings_group', // option group
            'bpmpesagateway_options', // option name
            [
                'type' => 'array',
                'sanitize_callback' => function ($options) {
                    // return sanitized options array
                    $options = is_array($options) ? $options : [];

                    // Get existing options from database
                    $existing_options = BPMGOptions::get_options();

                    // Merge new options with existing ones (new values override existing)
                    $options = array_merge($existing_options, $options);

                    // encrypt consumer_key, consumer_secret, passkey before saving
                    $consumer_key =  BPMGUtils::encrypt_credential(sanitize_text_field($options['consumer_key'] ?? ''));
                    $consumer_secret = BPMGUtils::encrypt_credential(sanitize_text_field($options['consumer_secret'] ?? ''));
                    $passkey = BPMGUtils::encrypt_credential(sanitize_text_field($options['passkey'] ?? ''));

                    return [
                        'consumer_key' => $consumer_key,
                        'consumer_secret' => $consumer_secret,
                        'shortcode' => sanitize_text_field($options['shortcode']),
                        'passkey' => $passkey,
                        'account_reference' => sanitize_text_field($options['account_reference']),
                        'transaction_reference' => sanitize_text_field($options['transaction_reference']),
                        'amount' => floatval($options['amount']),
                    ];
                },
                'default' => [
                    'consumer_key' => '',
                    'consumer_secret' => '',
                    'shortcode' => '',
                    'passkey' => '',
                    'account_reference' => '',
                    'transaction_reference' => '',
                    'amount' => 0,
                ],
            ],
        );
    }
}
