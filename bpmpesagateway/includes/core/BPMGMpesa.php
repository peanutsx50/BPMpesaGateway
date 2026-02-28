<?php

/**
 * Mpesa related functions
 * @package    BPMpesaGateway
 * @subpackage BPMpesaGateway/includes/core
 */

namespace BPMpesaGateway\Core;

use WP_REST_Request;
use DateTimeZone;
use WP_Error;
use WP_REST_Response;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class BPMGMpesa
{
    // Mpesa properties
    private $consumer_key;
    private $consumer_secret;
    private $shortcode;
    private $password;
    private $passkey;
    private $access_token;
    private $timestamp;
    private $environment = 'production'; //sandbox or production
    private $callbackurl;
    private $account_reference;
    private $transaction_description;
    private $err;
    private $url;
    private $amount;
    private $transactionType = 'CustomerPayBillOnline';
    private const MPESA_PRODUCTION_URL = 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    private const MPESA_SANDBOX_URL = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';



    // Mpesa related functions can be added here in the future
    public function __construct()
    {
        // retrive all the options from the database using BPMGOptions class
        $options = BPMGOptions::get_options();

        // Decrypt credentials if they are encrypted, otherwise use as is
        $credentials = ['consumer_key', 'consumer_secret', 'passkey'];
        foreach ($credentials as $credential) {
            if (BPMGUtils::is_encrypted($options[$credential])) {
                $options[$credential] = BPMGUtils::decrypt_credential($options[$credential]);
            }
        }

        // Initialize Mpesa properties from settings
        $this->consumer_key        = $options['consumer_key'];
        $this->consumer_secret     = $options['consumer_secret'];
        $this->shortcode           = $options['shortcode'];
        $this->passkey             = $options['passkey'];

        // Generate access token immediately upon initialization
        $this->access_token        = $this->generate_access_token();

        // get timestamp using Africa/Nairobi timezone and format it as YmdHis
        $this->timestamp           = wp_date('YmdHis', null, new DateTimeZone('Africa/Nairobi')); // should always come first before generate password so its not empty

        $this->password            = $this->generate_password();
        $this->account_reference   = $options['account_reference']; // figure out how to make it incremental
        $this->transaction_description = $options['transaction_reference'];
        $this->amount              = $options['amount'];

        // protected callback URL with a unique secret hash to prevent callback spoofing
        $secret_key = wp_hash(wp_salt('nonce'), 'nonce'); // unique auth
        $this->callbackurl = add_query_arg(
            'bpmg_auth',
            $secret_key,
            rest_url('bpmpesa/v1/callback')
        );

        // Set the appropriate M-Pesa API endpoint URL based on environment
        $this->url          = $this->environment === 'production' ?
            self::MPESA_PRODUCTION_URL :
            self::MPESA_SANDBOX_URL;
    }

    // Mpesa STK push request function
    public function send_stk_push_request($phone_number)
    {
        // check if consumer_key, consumer_secret, shortcode, passkey is empty
        $validation_result = $this->validate_config();
        if (is_wp_error($validation_result)) {
            return $validation_result;
        }

        try {
            $data = [
                "BusinessShortCode" => $this->shortcode, // paybill number
                "Password" => $this->password, // generated password
                "Timestamp" => $this->timestamp, // current timestamp
                "TransactionType" => $this->transactionType, // transaction type (CustomerBuyGoodsOnline or CustomerPayBillOnline)
                "Amount" => $this->amount, // get amount from settings, do not allow zero or negative amounts
                "PartyA" => $phone_number, // phone number making payment
                "PartyB" => $this->shortcode, // paybill number
                "PhoneNumber" => $phone_number, // similar to pary A
                "AccountReference" => $this->account_reference, // transaction id
                "TransactionDesc" => $this->transaction_description, // description of transaction
                "CallBackURL" => $this->callbackurl, // webhook callback
            ];
            $response = wp_remote_post($this->url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->access_token,
                    'Content-Type'  => 'application/json',
                    'Expect'        => '',
                    'Connection'    => 'Keep-Alive', // Request the server to stay connected
                ],
                'body'    => json_encode($data),
                'timeout' => 30, // Reduced from 60 - M-Pesa responds quickly
                'httpversion' => '1.1', // Force HTTP/1.1 for better compatibility
                'sslverify' => true, // Explicit for security
            ]);

            if (is_wp_error($response)) {
                return new WP_Error(
                    'http_request_failed',
                    'Connection to M-Pesa failed: ' . $response->get_error_message(),
                    ['status' => 503] // 503 Service Unavailable is often used for API timeouts
                );
            }
            $body = wp_remote_retrieve_body($response);
            $decoded_response = json_decode($body, true);

            // 1. Check if the JSON is empty or invalid
            if (empty($decoded_response)) {
                return new WP_Error(
                    'mpesa_invalid_response',
                    'Received an empty or invalid response from M-Pesa.',
                    ['status' => 502] // Bad Gateway
                );
            }

            // 2. Check for M-Pesa Global Error Codes (e.g., Auth failure, Invalid request)
            if (isset($decoded_response['errorCode'])) {
                return new WP_Error(
                    'mpesa_api_error',
                    $decoded_response['errorMessage'] ?? 'M-Pesa API Error',
                    [
                        'status'     => 400,
                        'error_code' => $decoded_response['errorCode'],
                        'raw'        => $decoded_response
                    ]
                );
            }

            // Return success response with payment details for client-side tracking
            return new WP_REST_Response([
                'status'   => 'success',
                'message'  => 'Payment request sent. Enter your M-Pesa PIN.',
                'response' => $decoded_response,
            ]);
        } catch (\Exception $e) {
            return new WP_Error(
                'payment_exception',
                'message: ' . $e->getMessage(),
                ['status' => 500]
            );
        }

        // return ['status' => 'success'];
    }

    // generate access token for mpesa api
    private function generate_access_token()
    {
        // Check for cache first (early return for best performance)
        $cached_token = get_transient('bpmg_access_token');
        if ($cached_token) {
            return $cached_token;
        }

        // Use class constants for URLs (defined once, reused always)
        static $auth_urls = [
            'production' => 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials',
            'sandbox'    => 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
        ];

        $auth_url = $auth_urls[$this->environment];

        // Pre-build authorization header (avoid string concatenation in array)
        $credentials = base64_encode($this->consumer_key . ':' . $this->consumer_secret);

        // Optimized HTTP request with minimal timeout
        $response = wp_remote_get($auth_url, [
            'headers' => [
                'Authorization' => 'Basic ' . $credentials,
            ],
            'timeout' => 30, // Reduced from 60 - M-Pesa typically responds in <5s
            'sslverify' => true, // Explicit for security
        ]);

        // Early error handling
        if (is_wp_error($response)) {
            $error_msg = $response->get_error_message();
            return '';
        }

        // Parse response
        $result = json_decode(wp_remote_retrieve_body($response), true);


        // Validate token exists
        if (empty($result['access_token'])) {
            return '';
        }

        $access_token = $result['access_token'];

        // Cache for 50 minutes
        set_transient('bpmg_access_token', $access_token, 50 * MINUTE_IN_SECONDS);

        return $access_token;
    }

    // generate password for stk push
    private function generate_password()
    {
        $data_to_encode = $this->shortcode . $this->passkey . $this->timestamp;
        $password = base64_encode($data_to_encode);
        return $password;
    }

    //validate all the field values are not empty
    private function validate_config()
    {
        $required_fields = ['consumer_key', 'consumer_secret', 'shortcode', 'passkey', 'account_reference', 'access_token'];

        foreach ($required_fields as $field) {
            if (empty($this->$field)) {
                return new WP_Error(
                    'rest_mpesa_config_error',
                    'Missing required M-Pesa configuration: ' . $field,
                    ['status' => 500] // Triggers !response.ok in your JS fetch
                );
            }
        }
        return true;
    }

    public static function handle_callback(WP_REST_Request $request)
    {
        $params = $request->get_params();
        $stk    = $params['Body']['stkCallback'] ?? null;

        if (!$stk) {
            return new WP_Error(
                'stk_failed',                 // Unique error code
                'M-Pesa request was ignored', // Message for the user
                ['status' => 400]             // This sets the HTTP status code
            );
        }


        return self::store_details_meta($stk);
    }

    public static function store_details_meta($stk)
    {
        // Extract basic callback data
        $checkoutId = sanitize_text_field($stk['CheckoutRequestID'] ?? '');
        $merchantRequestId = sanitize_text_field($stk['MerchantRequestID'] ?? '');
        $resultCode = (int) ($stk['ResultCode'] ?? -1);
        $resultDesc = sanitize_text_field($stk['ResultDesc'] ?? '');


        // This should never happen if M-Pesa is working correctly, but we check just in case to prevent processing invalid callbacks
        if (empty($checkoutId)) {
            return new WP_Error(
                'missing_checkout_id',
                'Missing checkout ID',
                ['status' => 400] // This forces the HTTP 400 code
            );
        }

        // Extract transaction metadata
        $amount = 0;
        $phoneNumber = '';
        $mpesaReceipt = '';
        $transactionDate = '';

        if ($resultCode === 0 && isset($stk['CallbackMetadata']['Item'])) {
            foreach ($stk['CallbackMetadata']['Item'] as $item) {
                switch ($item['Name']) {
                    case 'Amount':
                        $amount = floatval($item['Value'] ?? 0);
                        break;
                    case 'MpesaReceiptNumber':
                        $mpesaReceipt = sanitize_text_field($item['Value'] ?? '');
                        break;
                    case 'PhoneNumber':
                        $phoneNumber = sanitize_text_field($item['Value'] ?? '');
                        break;
                    case 'TransactionDate':
                        $transactionDate = sanitize_text_field($item['Value'] ?? '');
                        break;
                }
            }
        }
        $status = ($resultCode === 0) ? 'success' : 'failed';

        // Duplicate check for exisiting checkoutId to prevent replay attacks
        $existing_post = get_page_by_path($checkoutId, OBJECT, 'bpmg_payment');

        // prevent duplicate entries for the same checkoutId which can happen if M-Pesa retries the callback.
        if (! empty($existing_post)) {
            return new WP_REST_Response([
                'status'    => 'ok',
                'post_id'   => $existing_post->ID,
                'duplicate' => true,
            ]);
        }

        // Insert post using wp_insert_post
        $post_id = wp_insert_post(array(
            'post_type'   => 'bpmg_payment',
            'post_status' => 'publish',
            'post_title'  => 'Mpesa STK ' . $checkoutId,
            'post_name'   => sanitize_title($checkoutId), // use checkoutId as unique slug
        ), true);

        if (is_wp_error($post_id)) {
            return new WP_Error(
                'insert_post_failed',
                $post_id->get_error_message(),
                ['status' => 500]
            );
        }

        // Store core meta
        update_post_meta($post_id, 'checkout_id', $checkoutId);
        update_post_meta($post_id, 'merchant_request_id', $merchantRequestId);
        update_post_meta($post_id, 'status', $status);
        update_post_meta($post_id, 'result_code', (string) $resultCode);
        update_post_meta($post_id, 'result_desc', $resultDesc);
        update_post_meta($post_id, 'date', current_time('mysql'));

        // Store additional meta for successful transactions
        if ($resultCode === 0) {
            update_post_meta($post_id, 'amount', (string) $amount);
            update_post_meta($post_id, 'mpesa_receipt_number', $mpesaReceipt);
            update_post_meta($post_id, 'phone_number', $phoneNumber);
            update_post_meta($post_id, 'transaction_date', $transactionDate);
        }

        return new WP_REST_Response([
            'status' => 'ok',
            'post_id' => $post_id
        ]);
    }
}
