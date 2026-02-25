<?php

namespace BPMpesaGateway\Public;

use BPMpesaGateway\Core\BPMGMpesa;
use BPMpesaGateway\Core\BPMGUtils;
use WP_Error;
use WP_REST_Request;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

//TODO: WHEN SENDING WP_ERROR RESPONSE, THE JS DOSENT SEEM TO RECEIVE THE ERROR MESSAGE, CHECK IF THIS IS A PROBLEM WITH THE JS
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
        // load style only on registration page to avoid unnecessary css files
        /** @disregard */
    if ( ! function_exists( 'bp_is_register_page' ) || ! bp_is_register_page() ) {
        return;
    }
        wp_enqueue_style($this->bpmpesagateway . '-public-style', BPMG_PUBLIC_CSS_URL . 'BPMG-public.css', array(), $this->version, 'all');
    }

    public function enqueue_scripts()
    {
        //load script only on registration page to avoid uncessary js files
        /** @disregard */
    if ( ! function_exists( 'bp_is_register_page' ) || ! bp_is_register_page() ) {
        return;
    }
        wp_enqueue_script($this->bpmpesagateway . '-public-script', BPMG_PUBLIC_JS_URL . 'BPMG-public.min.js', array('jquery'), $this->version, array(
            'strategy'  => 'defer',
            'in_footer' => true,
        ));
    }

    /**
     * Localize scripts with AJAX and REST API data.
     *
     * Passes essential configuration data to the frontend JavaScript via wp_localize_script(),
     * including the WordPress AJAX handler URL, security nonce, and REST API endpoints for
     * M-Pesa payment processing and callback handling.
     *
     * The localized data is accessible in JavaScript via the `bpmpesa_ajax` object.
     *
     * @return void
     *
     * @uses wp_localize_script()
     * @uses admin_url()
     * @uses wp_create_nonce()
     * @uses rest_url()
     */
    public function localize_scripts()
    {
        //wp localize script to pass ajax url
        wp_localize_script(
            $this->bpmpesagateway . '-public-script',
            'bpmpesa_ajax',
            [ // script : matches the handle used in wp_enqueue_script
                'ajax_url' => admin_url('admin-ajax.php'), // core wordpress ajax handler
                'nonce'    => wp_create_nonce('wp_rest'), // security nonce
                'callback_url' => rest_url('bpmpesa/v1/callback'), // callback url
                'confirm_payment_url' => rest_url('bpmpesa/v1/confirm-payment'), // endpoint to confirm payment from frontend
                'process_payment_url' => rest_url('bpmpesa/v1/process-payment'), // endpoint to initiate payment from frontend
                'phone_pattern' => '/^254(7(?:[0129][0-9]|4[0-3568]|5[7-9]|6[89])|11[0-5])\d{6}$/', // regex pattern for validating Kenyan phone numbers in the format 254XXXXXXXXX
            ]
        );
    }

    /**
     * Register M-Pesa REST API endpoints.
     *
     * Registers two custom REST API routes for M-Pesa payment processing:
     * - `/bpmpesa/v1/callback`: Receives M-Pesa transaction callbacks from Safaricom servers.
     *   Requires IP validation and authentication token. Handled by BPMGMpesa::handle_callback().
     * - `/bpmpesa/v1/process-payment`: Initiates payment requests from the frontend.
     *   Requires phone number validation, SSL, nonce verification, and rate limiting.
     *   Handled by BPMGPublic::handle_mpesa_request().
     *
     * Both endpoints are hidden from the REST API index for security purposes.
     *
     * @return void
     *
     * @uses register_rest_route()
     * @uses BPMGMpesa::handle_callback()
     * @uses BPMGPublic::handle_mpesa_request()
     * @uses BPMGPublic::validate_safaricom_IP()
     * @uses BPMGPublic::validate_mpesa_request()
     * @uses BPMGPublic::validate_phone_number()
     */
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

        register_rest_route('bpmpesa/v1', '/confirm-payment', [
            'methods' => 'POST',
            'callback' => [$this, 'confirm_payment'],
            'permission_callback' => [$this, 'validate_confirm_payment'], // validate nonce and SSL
            'args'                => [
                'checkout_id' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ]
        ]);
    }

    /**
     * Validate Safaricom IP address and authentication token.
     *
     * Verifies that incoming callback requests are from Safaricom servers by performing three checks:
     * 1. SSL/HTTPS connection is active
     * 2. Request IP is from known Safaricom IP ranges
     * 3. Authentication token matches the site's NONCE_SALT hash
     *
     * @param WP_REST_Request $request The REST request object containing the callback data.
     *
     * @return true|WP_Error True if all validation checks pass, WP_Error with 403 status if validation fails.
     *
     * @uses is_ssl()
     * @uses sanitize_text_field()
     * @uses wp_unslash()
     * @uses filter_var()
     * @uses BPMGUtils::is_safaricom_ip()
     * @uses wp_hash()
     * @uses wp_salt()
     * @uses hash_equals()
     */
    public function validate_safaricom_IP(WP_REST_Request $request)
    {
        //check for ssl
        if (!is_ssl()) {
            return new WP_Error('ssl_required', 'SSL is required for this endpoint', ['status' => 403]);
        }

        // check request IP address from server
        $raw_ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']) ?? 'UNKOWN'); // sanitize and validate IP address, default to UNKNOWN if not valid
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

    /**
     * Validate phone number format for M-Pesa transactions.
     *
     * Validates that the provided phone number is in the correct format for M-Pesa payment processing.
     * Expects Kenyan phone numbers in the format: 254XXXXXXXXX (country code + 9 digits).
     *
     * @param string          $phone   The phone number to validate.
     * @param WP_REST_Request $request The REST request object (required by REST validation callback signature).
     * @param string          $key     The parameter key (required by REST validation callback signature).
     *
     * @return true|WP_Error True if phone number is valid, WP_Error with 400 status if invalid.
     *
     * @uses BPMGUtils::check_phone_number()
     */
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

    /**
     * Validate M-Pesa payment request from the frontend.
     *
     * Performs comprehensive security checks before processing payment requests:
     * 1. Verifies SSL/HTTPS connection is active for secure transactions
     * 2. Checks if client IP has exceeded rate limits for the given phone number
     *
     * @param WP_REST_Request $request The REST request object containing payment details.
     *
     * @return true|WP_Error True if all validations pass, WP_Error otherwise.
     *                        Returns 403 status for SSL or nonce failures,
     *                        429 status for rate limit exceeded.
     *
     * @uses is_ssl()
     * @uses sanitize_text_field()
     * @uses wp_unslash()
     * @uses filter_var()
     * @uses wp_verify_nonce()
     * @uses BPMGUtils::rate_limit_exceeded()
     */
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

        //2. rate limit check
        $phone_number = $request->get_param('phone_number');
        $raw_ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']) ?? 'UNKOWN'); // sanitize and validate IP address, default to UNKNOWN if not valid
        $ip = filter_var($raw_ip, FILTER_VALIDATE_IP) ? sanitize_text_field($raw_ip) : 'UNKNOWN';
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

    public function handle_mpesa_request($request)
    {
        $phone = $request->get_param('phone_number');
        // send the request to mpesa api
        $BPMG_Mpesa = new BPMGMpesa();
        $payment_response = $BPMG_Mpesa->send_stk_push_request($phone);
        $checkout_request_id = $payment_response['response']['CheckoutRequestID'] ?? null;
        // handle the response
        if ($payment_response['status'] === 'success') {
            // send message saying we sent request 
            $this->store_pending_transaction($checkout_request_id); // store pending transaction for later verification in callback
            return wp_send_json_success(['message' => $payment_response['message']]); // send response back to ajax
        } else {
            return wp_send_json_error(['message' => $payment_response['message']]); // send error response back to ajax
        }
    }

    private function store_pending_transaction($checkout_request_id)
    {
        // store pending transaction in custom post type for later verification in callback
        $transaction = get_transient('bpmg_pending_' . $checkout_request_id);
        if ($transaction !== false) {
            return;
        }

        return set_transient('bpmg_pending_' . $checkout_request_id, 1, 15 * MINUTE_IN_SECONDS); // 15 minutes timeout
    }
}
