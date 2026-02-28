<?php

namespace BPMpesaGateway\Public;

use BPMpesaGateway\Core\BPMGMpesa;
use BPMpesaGateway\Core\BPMGUtils;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

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
        // load style only on registration page to avoid unnecessary css files
        /** @disregard */
        if (! function_exists('bp_is_register_page') || ! bp_is_register_page()) {
            return;
        }
        wp_enqueue_style($this->bpmpesagateway . '-public-style', BPMG_PUBLIC_CSS_URL . 'BPMG-public.css', array(), $this->version, 'all');
    }

    public function enqueue_scripts()
    {
        //load script only on registration page to avoid uncessary js files
        /** @disregard */
        if (! function_exists('bp_is_register_page') || ! bp_is_register_page()) {
            return;
        }
        wp_enqueue_script($this->bpmpesagateway . '-public-script', BPMG_PUBLIC_JS_URL . 'BPMG-public.min.js', array('jquery'), $this->version, array(
            'strategy'  => 'defer',
            'in_footer' => true,
        ));
    }

    /**
     * Localizes the public script with REST API endpoints.
     *
     * This function passes necessary server-side data to the localized JavaScript object
     * for making REST API calls from the frontend. It includes the
     * WordPress security nonce, and various M-Pesa payment endpoints.
     *
     * The localized data is attached to the 'bpmpesa_ajax' JavaScript object and includes:
     * - Nonce for REST API security verification
     * - Callback URL for M-Pesa payment notifications
     * - Payment confirmation endpoint
     * - Payment initiation endpoint
     *
     * @since 1.0.0
     * @access public
     *
     * @return void
     */
    public function localize_scripts()
    {
        //wp localize script to pass ajax url
        wp_localize_script(
            $this->bpmpesagateway . '-public-script',
            'bpmpesa_ajax',
            [ // script : matches the handle used in wp_enqueue_script
                'nonce'    => wp_create_nonce('wp_rest'), // security nonce
                'callback_url' => rest_url('bpmpesa/v1/callback'), // callback url
                'confirm_payment_url' => rest_url('bpmpesa/v1/confirm-payment'), // endpoint to confirm payment from frontend
                'process_payment_url' => rest_url('bpmpesa/v1/process-payment'), // endpoint to initiate payment from frontend
            ]
        );
    }

    /**
     * Registers REST API endpoints for M-Pesa payment processing.
     *
     * This method registers three custom REST API endpoints for handling M-Pesa
     * payment operations:
     * 
     * - /callback: Endpoint for Safaricom to send payment callbacks
     * - /process-payment: Endpoint to initiate M-Pesa STK push requests
     * - /confirm-payment: Endpoint to verify payment status from frontend
     *
     * Each endpoint includes appropriate permission callbacks, argument validation,
     * and sanitization to ensure secure payment processing.
     *
     * @since 1.0.0
     * @access public
     *
     * @uses register_rest_route() To register the custom endpoints with WordPress REST API
     *
     * @return void
     */
    public function register_endpoints()
    {
        register_rest_route('bpmpesa/v1', '/callback', [
            'methods' => ['POST', 'GET'],
            'callback' => [BPMGMpesa::class, 'handle_callback'],
            'permission_callback' => [$this, 'validate_safaricom_IP'],
            //'permission_callback' => '__return_true', // Temporarily allow all request for testing
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
            'permission_callback' => [$this, 'validate_confirm_payment'], // validate SSL
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
     * Validates incoming callback requests from Safaricom M-Pesa API.
     *
     * This permission callback performs three layers of security validation:
     * 1. SSL/TLS verification - Ensures the request is encrypted
     * 2. IP whitelist validation - Confirms the request originates from Safaricom's IP ranges
     * 3. Token authentication - Verifies a unique URL parameter matches the expected hash
     *
     * These security measures ensure that only legitimate Safaricom callback requests
     * are processed, preventing unauthorized access to the callback endpoint.
     *
     * @since 1.0.0
     * @access public
     *
     * @param WP_REST_Request $request The REST API request object containing parameters and headers.
     *
     * @uses is_ssl() To check if the request is using SSL/TLS.
     * @uses wp_salt() To generate the nonce salt for the authentication token.
     * @uses wp_hash() To create a unique hash based on the nonce salt.
     * @uses hash_equals() For timing-safe string comparison of the tokens.
     *
     * @return bool|WP_Error True if validation passes, WP_Error with appropriate message and status code on failure.
     *                      - Returns 403 error if SSL is not enabled
     *                      - Returns 403 error if IP is not from Safaricom's ranges
     *                      - Returns 403 error if authentication token is invalid
     */
    public function validate_safaricom_IP(WP_REST_Request $request)
    {
        //check for ssl
        if (!is_ssl()) {
            return new WP_Error('ssl_required', 'SSL is required for this endpoint', ['status' => 403]);
        }

        // check request IP address from server
        $raw_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : ''; // sanitize and validate IP address, default to UNKNOWN if not valid
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
     * @since 1.0.0
     * @access public
     *
     * @param string          $phone   The phone number to validate.
     * @param WP_REST_Request $request The REST request object (required by REST validation callback signature).
     * @param string          $key     The parameter key (required by REST validation callback signature).
     *
     * @uses BPMGUtils::check_phone_number()
     * 
     * @return true|WP_Error True if phone number is valid, WP_Error with 400 status if invalid.
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
     * @since 1.0.0
     * @access public
     *
     * @param WP_REST_Request $request The REST request object containing payment details.
     * 
     * 
     * @uses is_ssl()
     * @uses sanitize_text_field()
     * @uses wp_unslash()
     * @uses filter_var()
     * @uses wp_verify_nonce()
     * @uses BPMGUtils::rate_limit_exceeded()
     *
     * @return true|WP_Error True if all validations pass, WP_Error otherwise.
     *                        Returns 403 status for SSL or nonce failures,
     *                        429 status for rate limit exceeded.
     *
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

        /** @disregard */
        // 2. check if user is on registration page to prevent abuse of this endpoint outside of intended context
        if (! function_exists('bp_is_register_page') || ! bp_is_register_page()) {
            return new WP_Error(
                'invalid_context',
                'This endpoint is only available during registration',
                ['status' => 403]
            );
        }

        //3. rate limit check
        $phone_number = $request->get_param('phone_number');
        $raw_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : ''; // sanitize and validate IP address, default to UNKNOWN if not valid
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

    /**
     * Validates SSL requirement for payment confirmation requests.
     *
     * This permission callback ensures that payment confirmation requests are made
     * over a secure HTTPS connection.
     *
     * @since 1.0.0
     * @access public
     *
     * @uses is_ssl() To verify the request is using SSL/TLS encryption.
     *
     * @return bool|WP_Error Returns true if SSL is enabled, WP_Error with 403 status
     *                       and descriptive message if SSL is not enabled.
     */
    public function validate_confirm_payment()
    {
        //1. check for ssl and return error if not enabled
        if (!is_ssl()) {
            return new WP_Error(
                'ssl_required',
                'SSL is not enabled on this site, transactions cannot be processed securely',
                ['status' => 403]
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
     * Handle REST API request to initiate an M-Pesa payment.
     *
     * This method is called when a user submits their phone number
     * on the BuddyPress registration form. It sends the payment request to the M-Pesa API, and returns
     * a JSON response indicating success or failure.
     *
     * Hooks:
     * - wp_ajax_bpmg_send_mpesa_request       (for logged-in users)
     * - wp_ajax_nopriv_bpmg_send_mpesa_request (for guests)
     * 
     * @since 1.0.0
     * @access public
     *
     * @return void Outputs JSON response and terminates execution.
     */

    public function handle_mpesa_request(WP_REST_Request $request)
    {
        $phone = $request->get_param('phone_number');
        // send the request to mpesa api
        $BPMG_Mpesa = new BPMGMpesa();
        $payment_response = $BPMG_Mpesa->send_stk_push_request($phone);

        // 1. Handle WP_Error response from the payment processing method
        if (is_wp_error($payment_response)) {
            return $payment_response;
        }

        $payment_response = $payment_response->get_data(); // Extract data from WP_REST_Response if it's a success response
        // 2. Handle failure based on your custom 'status' key
        if (isset($payment_response['status']) && $payment_response['status'] !== 'success') {
            return new WP_Error(
                'mpesa_request_failed',
                $payment_response['message'] ?? 'Payment request failed',
                ['status' => 400] // Triggers !response.ok in your JS fetch
            );
        }

        // 3. Handle Success
        $checkout_request_id = $payment_response['response']['CheckoutRequestID'] ?? null;
        return new WP_REST_Response([
            'message'     => $payment_response['message'],
            'checkout_id' => $checkout_request_id,
        ]);
    }

    /**
     * Confirms the status of an M-Pesa payment by checking the stored payment record.
     *
     * This endpoint handler is called by frontend JavaScript to poll the status of a
     * payment after the STK push has been initiated. It queries the custom post type
     * 'bpmg_payment' using the checkout ID to determine if the payment has been
     * completed, failed, or is still pending.
     *
     * The method returns standardized responses that the frontend can use to update
     * the user interface accordingly:
     * - 'pending': Payment is still being processed by M-Pesa
     * - 'success': Payment was completed successfully
     * - 'failed': Payment failed or was cancelled by the user
     *
     * @since 1.0.0
     * @access public
     *
     * @param WP_REST_Request $request The REST API request object containing the checkout_id parameter.
     *
     * @uses get_page_by_path() To find the payment record by its checkout ID slug.
     * @uses get_post_meta() To retrieve the payment status and result description.
     *
     * @return WP_REST_Response Response object containing:
     *                          - 'status': string (pending|success|failed)
     *                          - 'message': Human-readable status description
     */
    public function confirm_payment(WP_REST_Request $request)
    {
        // get the checkout id from the request
        $checkoutId = $request->get_param('checkout_id');

        // Find the payment record
        $existing_record = get_page_by_path($checkoutId, OBJECT, 'bpmg_payment');

        if (!$existing_record) {
            return new WP_REST_Response([
                'status'  => 'pending',
                'message' => 'Waiting for payment confirmation',
            ]);
        }

        // Get payment status
        $post_id = $existing_record->ID;
        $status = get_post_meta($post_id, 'status', true);
        $result_desc = get_post_meta($post_id, 'result_desc', true);

        // Handle failed payment
        if ($status === 'failed') {
            return new WP_REST_Response([
                'status'  => 'failed',
                'message' => $result_desc ?: 'Payment was cancelled or failed',
            ]);
        }

        // Handle successful payment
        if ($status === 'success') {
            $token = wp_generate_password(32, false); // random token
            set_transient('bpmg_paid_' . $token, $checkoutId, 30 * MINUTE_IN_SECONDS); // store token with checkoutId for 30 minutes
            return new WP_REST_Response([
                'status'          => 'success',
                'message'         => $result_desc ?: 'Payment successful',
                'token'           => $token
            ]);
        }

        // Still pending
        return new WP_REST_Response([
            'status'  => 'pending',
            'message' => 'Waiting for payment confirmation',
        ]);
    }

    public function validate_token_before_signup()
    {
        global $bp;
        // get token
        $token = isset($_COOKIE['bpmg_payment']) ? sanitize_text_field(wp_unslash($_COOKIE['bpmg_payment'])) : '';
        $valid_checkout_id = get_transient('bpmg_paid_' . $token);
        // validate if real
        if (!$valid_checkout_id) {
            $bp->signup->errors['bpmg_payment'] = 'Payment confirmation is required to complete registration.';
            return;
        }
    }

    public function cleanup_payment_token_after_signup($user_id)
    {
        if (isset($_COOKIE['bpmg_payment'])) {
            $token = sanitize_text_field(wp_unslash($_COOKIE['bpmg_payment']));
            delete_transient('bpmg_paid_' . $token);
            setcookie('bpmg_payment', '', time() - 3600, '/');
        }
    }
}
