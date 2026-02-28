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
        wp_enqueue_script($this->bpmpesagateway . '-admin-script', BPMG_ADMIN_JS_URL . 'BPMG-admin.min.js', array('jquery'), $this->version, true);
    }

    public function enqueue_styles()
    {
        wp_enqueue_style($this->bpmpesagateway . '-admin-style', BPMG_ADMIN_CSS_URL . 'BPMG-admin.css', array(), $this->version, 'all');
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
        // check if nonce is set in POST request
        if (!isset($_POST['bpmpesagateway_settings_group_nonce'])) {
            return;  // Prevent processing without nonce
        }

        // verify nonce for security
        if (!wp_verify_nonce($_POST['bpmpesagateway_settings_group_nonce'], 'bpmpesagateway_settings_group')) {
            wp_die('Security check failed');
        }

        // check if user has permission to manage options
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        // register settings from options[array_key]

        register_setting(
            'bpmpesagateway_settings_group', // option group
            'bpmpesagateway_options', // option name
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'santize_fields'],
                'default' => [
                    'consumer_key' => '',
                    'consumer_secret' => '',
                    'shortcode' => '',
                    'passkey' => '',
                    'account_reference' => '',
                    'transaction_reference' => '',
                    'amount' => 1,
                ],
            ],
        );
    }

    public function santize_fields($options)
    {
        // return sanitized options array
        $options = is_array($options) ? $options : [];

        // Get existing options from database
        $existing_options = BPMGOptions::get_options();

        // Merge new options with existing ones (new values override existing)
        $options = array_merge($existing_options, $options);

        // check if amount is less than 1 or greater than 150000
        if (isset($options['amount']) && (absint($options['amount']) < 1 || absint($options['amount']) > 150000)) {
            add_settings_error(
                'bpmpesagateway_options',
                'invalid_amount',
                __('Amount must be between 1 and 150,000.', 'bpmpesagateway'),
                'error'
            );
        }

        // check if fields are empty
        $required_fields = [
            'consumer_key',
            'consumer_secret',
            'shortcode',
            'passkey',
        ];

        $is_empty = false;

        foreach ($required_fields as $field) {
            if (empty($options[$field])) {
                $is_empty = true;
                add_settings_error(
                    'bpmpesagateway_options',
                    "empty_{$field}",
                    sprintf(
                        /* translators: %s: field Name */
                        esc_html__('Required field %s is empty.', 'bpmpesagateway'),
                        str_replace('_', ' ', $field)
                    ),
                    'error'
                );
            }
        }

        if ($is_empty) {
            // If any required field is empty, return existing options to prevent saving invalid data
            return $existing_options;
        }

        // sanitize options
        $fields = ['consumer_key', 'consumer_secret', 'shortcode', 'passkey', 'account_reference', 'transaction_reference'];
        foreach ($fields as $field) {
            if (isset($options[$field])) {
                $options[$field] = sanitize_text_field($options[$field]);
            }
        }

        // check if consumer key, consumer secret and passkey are encrypted, if not encrypt them before saving
        $credentials = ['consumer_key', 'consumer_secret', 'passkey'];
        foreach ($credentials as $credential) {
            if (!BPMGUtils::is_encrypted($options[$credential])) {
                $options[$credential] = BPMGUtils::encrypt_credential($options[$credential]);
            }
        }

        // Sanitize and return the options array
        return [
            'consumer_key' => $options['consumer_key'],
            'consumer_secret' => $options['consumer_secret'],
            'shortcode' => $options['shortcode'],
            'passkey' => $options['passkey'],
            'account_reference' => $options['account_reference'],
            'transaction_reference' => $options['transaction_reference'],
            'amount' => absint($options['amount']),
        ];
    }

    public function check_ssl()
    {
        if (!is_ssl()) {

            add_action('admin_notices', function () {
?>
                <div class="notice notice-error is-dismissible">
                    <p style="color: black;"><?php esc_html_e('Warning: Your site is not using SSL. For secure M-Pesa transactions, please enable HTTPS on your website.', 'bpmpesagateway'); ?></p>
                </div>
<?php
            });
        }
    }
}
