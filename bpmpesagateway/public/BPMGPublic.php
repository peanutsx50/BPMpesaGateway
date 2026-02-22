<?php

namespace BPMpesaGateway\Public;

use BPMpesaGateway\Core\BPMGMpesa;
use BPMpesaGateway\Core\BPMGUtils;
use WP_Error;
use WP_REST_Request;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class BPMGPublic
{
    private $bpmpesagateway;
    private $version;

    public function __construct($bpmpesagateway, $version)
    {

        $this->bpmpesagateway = $bpmpesagateway;
        $this->version = $version;
    }

    public function enqueue_styles()
    {
        wp_enqueue_style($this->bpmpesagateway . '-public-style', BPMG_PUBLIC_CSS_URL . 'BPMG-public.css', array(), $this->version, 'all');
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script($this->bpmpesagateway . '-public-script', BPMG_PUBLIC_JS_URL . 'BPMG-public.min.js', array('jquery'), $this->version, true);
    }

    public function localize_scripts()
    {
        //wp localize script to pass ajax url
        wp_localize_script(
            $this->bpmpesagateway . '-public-script',
            'bpmpesa_ajax',
            [ // script : matches the handle used in wp_enqueue_script
                'ajax_url' => admin_url('admin-ajax.php'), // core wordpress ajax handler
                'nonce'    => wp_create_nonce('bpmg_mpesa_nonce'), // security nonce
                'callback_url' => rest_url('bpmpesa/v1/callback'), // callback url
                'process_payment_url' => rest_url('bpmpesa/v1/process-payment'), // endpoint to initiate payment from frontend
            ]
        );
    }

    public function register_endpoints()
    {
        register_rest_route('bpmpesa/v1', '/callback', [
            'methods' => ['POST', 'GET'],
            'callback' => [BPMGMpesa::class, 'handle_callback'],
            'permission_callback' => [$this, 'validate_safaricom_IP'],
            'show_in_index' => false, // Hide from REST API index
            'args'                => [
                'bpmg_auth' => [
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        register_rest_route('bpmpesa/v1', '/process-payment', [
            'methods' => ['POST', 'GET'],
            'callback' => [$this, 'handle_mpesa_request'],
            'permission_callback' => [$this, 'validate_mpesa_request'],
            'show_in_index' => false, // Hide from REST API index
            'args' => [
                'phone_number' => [
                    'required'          => true,
                    'type'              => 'string',
                    'validate_callback' => [$this, 'validate_phone_number'], // check if phone number is valid for M-Pesa
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
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

    public function validate_phone_number($phone, $request, $key)
    {

        $results = BPMGUtils::check_phone_number($phone);

        // Kenyan phone number validation
        if (!$results) {
            return new WP_Error(
                'invalid_phone',
                'Invalid phone number. Use format: 254XXXXXXXXX',
                ['status' => 400]
            );
        }

        return true;
    }

    public function validate_mpesa_request(WP_REST_Request $request)
    {
        //1. check for ssl and return error if not enabled
        if (!is_ssl()) {
            return new WP_Error(
                'ssl_required',
                'SSL is not enabled on this site, transactions cannot be processed securely',
                ['status' => 403]
            );
        }

        //2. verify nonce
        $nonce = $request->get_header('X-WP-Nonce');
        $raw_ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ip = filter_var($raw_ip, FILTER_VALIDATE_IP) ? sanitize_text_field($raw_ip) : 'UNKNOWN';
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error(
                'invalid_nonce',
                'Invalid request',
                ['status' => 403]
            );
        }

        //3. rate limit check
        $phone_number = $request->get_param('phone_number');
        if (BPMGUtils::rate_limit_exceeded($ip, $phone_number)) {
            return new WP_Error(
                'rate_limit_exceeded',
                'Too many requests. Please try again later.',
                ['status' => 429]
            );
        }

        return true;
    }

    public function bpmg_add_custom_registration_fields()
    {
        $template_path = BPMG_PUBLIC_PARTIALS . 'registration-fields.php';
        if (file_exists($template_path)) {
            include $template_path;
        }
    }

    /**
     * Handle AJAX request to initiate an M-Pesa payment.
     *
     * This method is called via WordPress AJAX when a user submits their phone number
     * on the BuddyPress registration form. It validates the request using a nonce,
     * sanitizes the input, sends the payment request to the M-Pesa API, and returns
     * a JSON response indicating success or failure.
     *
     * Hooks:
     * - wp_ajax_bpmg_send_mpesa_request       (for logged-in users)
     * - wp_ajax_nopriv_bpmg_send_mpesa_request (for guests)
     *
     * @return void Outputs JSON response and terminates execution.
     */

    public function handle_mpesa_request()
    {
        // Check nonce for security
        if (!isset($_POST['bpmg_nonce']) || !wp_verify_nonce($_POST['bpmg_nonce'], 'bpmg_mpesa_nonce')) {
            wp_send_json_error(['message' => 'Invalid request']); // deny request if nonce is invalid
            wp_die();
        }
        $phone = sanitize_text_field($_POST['phone']); // this code receives phone number from ajax request via post
        // send the request to mpesa api
        $BPMG_Mpesa = new BPMGMpesa();
        $payment_response = $BPMG_Mpesa->send_stk_push_request($phone);
        // handle the response
        if ($payment_response['status'] === 'success') {
            // send message saying we sent request 
            wp_send_json_success(['message' => $payment_response['message'], 'response' => $payment_response['response']]); // send response back to ajax
        } else {
            wp_send_json_error(['message' => $payment_response['message']]); // send error response back to ajax
        }
        wp_die();
    }
}
