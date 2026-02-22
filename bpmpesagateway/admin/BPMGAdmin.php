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

namespace BPMpesaGateway\Admin;

use BPMpesaGateway\Core\BPMGOptions;
use BPMpesaGateway\Core\BPMGUtils;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class BPMGAdmin
{
    private $bpmpesagateway;
    private $version;

    public function __construct($bpmpesagateway, $version)
    {

        $this->bpmpesagateway = $bpmpesagateway;
        $this->version = $version;
    }

    public function add_admin_pages()
    {
        add_menu_page(
            'BPMpesaGateway',
            'BPMpesaGateway',
            'manage_options',
            'bpmpesagateway',
            [$this, 'admin_page_content'],
            'dashicons-admin-generic',
            110
        );
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script($this->bpmpesagateway . '-admin-script', BPMG_PUBLIC_JS_URL . 'BPMG-admin.min.js', array('jquery'), $this->version, true);
    }

    public function enqueue_styles()
    {
        wp_enqueue_style($this->bpmpesagateway . '-admin-style', BPMG_PUBLIC_CSS_URL . 'BPMG-admin.css', array(), $this->version, 'all');
    }

    public function admin_page_content()
    {
        // Admin page content goes here
        $template_path = BPMG_ADMIN_PARTIALS . 'admin-template.php';
        if (file_exists($template_path)) {
            include $template_path;
        }
    }

    // save settings
    public function register_settings()
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
