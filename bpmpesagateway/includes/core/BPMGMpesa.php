<?php

/**
 * Mpesa related functions
 * @package    BPMpesaGateway
 * @subpackage BPMpesaGateway/includes/core
 */

namespace BPMpesaGateway\Core;

use WP_REST_Request;
use DateTimeZone;

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

        // decrypt consumer key, consumer secret and passkey
        $decrypted_consumer_key = BPMGUtils::decrypt_credential($options['consumer_key']);
        $decrypted_consumer_secret = BPMGUtils::decrypt_credential($options['consumer_secret']);
        $decrypted_passkey = BPMGUtils::decrypt_credential($options['passkey']);


        // Initialize Mpesa properties from settings
        $this->consumer_key        = $decrypted_consumer_key;
        $this->consumer_secret     = $decrypted_consumer_secret;
        $this->shortcode           = $options['shortcode'];
        $this->passkey             = $decrypted_passkey;

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
            rest_url('bpmpesagateway/v1/callback')
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
        if ($validation_result['status'] === 'error') {
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
                return [
                    'status' => 'error',
                    'message' => 'HTTP Request failed: ' . $response->get_error_message(),
                ];
            }

            $body = wp_remote_retrieve_body($response);
            $decoded_response = json_decode($body, true);

            // Check if M-Pesa API returned an error code
            if (isset($decoded_response['errorCode'])) {
                return [
                    'status' => 'error',
                    'message' => $decoded_response['errorMessage'] ?? 'M-Pesa API Error',
                    'error_code' => $decoded_response['errorCode'],
                    'response' => $decoded_response
                ];
            }

            // Return success response with payment details for client-side tracking
            return [
                'status' => 'success',
                'message' => 'Payment request sent. Enter your M-Pesa PIN.',
                'response' => $decoded_response,
            ];
        } catch (\Exception $e) {
            $this->err = $e->getMessage();
            return [
                'status' => 'error',
                'message' => 'Exception: ' . $this->err
            ];
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
                return [
                    'status' => 'error',
                    'message' => 'Missing required Mpesa configuration details',
                ];
            }
        }
        return ['status' => 'success', 'message' => 'Mpesa configuration is valid'];
    }

    public static function handle_callback(WP_REST_Request $request)
    {
        $params = $request->get_params();
        $stk    = $params['Body']['stkCallback'] ?? null;

        if (!$stk) {
            return rest_ensure_response(['status' => 'ignored']);
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
            return rest_ensure_response(['status' => 'error', 'message' => 'Missing checkout ID'], 400);
        }
        // check for pending transaction
        $content_post_id = get_transient('bpmg_pending_' . $checkoutId);

        // Security: If no pending transaction found, reject the callback attempted callback spoofing
        if (!$content_post_id) {
            return rest_ensure_response(['status' => 'error', 'message' => 'Unknown transaction'], 400);
        }

        // Clean up the transient
        delete_transient('bpmg_pending_' . $checkoutId);

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
        global $wpdb;
        $existing_post_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
         WHERE meta_key = 'checkout_id' 
         AND meta_value = %s 
         LIMIT 1",
            $checkoutId
        ));

        if ($existing_post_id) {
            return rest_ensure_response(['status' => 'ok', 'post_id' => $existing_post_id, 'duplicate' => true], 200);
        }

        // ULTRA-OPTIMIZED Single database transaction
        $current_time = current_time('mysql'); // get local time for post
        $gmt_time = get_gmt_from_date($current_time); // gets UTC time for consistency in storage

        // Start transaction for atomic insert
        $wpdb->query('START TRANSACTION');

        // Insert post
        $wpdb->query($wpdb->prepare(
            "INSERT INTO {$wpdb->posts} 
        (post_type, post_status, post_title, post_date, post_date_gmt, post_modified, post_modified_gmt) 
        VALUES (%s, %s, %s, %s, %s, %s, %s)",
            'mpesa', // custom post type for M-Pesa transactions
            'publish', // publish immediately for visibility in admin
            'Mpesa STK ' . $checkoutId, // title with checkout ID for easy identification
            $current_time, // local time for post_date
            $gmt_time, // UTC time for post_date_gmt
            $current_time, // local time for post_modified
            $gmt_time // UTC time for post_modified_gmt
        ));

        // get id of newly inserted post
        $post_id = $wpdb->insert_id;

        if (!$post_id) {
            $wpdb->query('ROLLBACK');
            return rest_ensure_response(['status' => 'error', 'message' => 'Database error'], 500);
        }

        // Build meta values
        $meta_rows = [
            [$post_id, 'checkout_id', $checkoutId],
            [$post_id, 'merchant_request_id', $merchantRequestId],
            [$post_id, 'status', $status],
            [$post_id, 'result_code', (string)$resultCode],
            [$post_id, 'result_desc', $resultDesc],
            [$post_id, 'date', $current_time],
            [$post_id, 'content_post_id', $content_post_id],
        ];

        // add more meta if transaction was successful
        if ($resultCode === 0) {
            $meta_rows[] = [$post_id, 'amount', (string)$amount];
            $meta_rows[] = [$post_id, 'mpesa_receipt_number', $mpesaReceipt];
            $meta_rows[] = [$post_id, 'phone_number', $phoneNumber];
            $meta_rows[] = [$post_id, 'transaction_date', $transactionDate];
        }

        // Single bulk insert for all meta
        $placeholders = implode(', ', array_fill(0, count($meta_rows), '(%d, %s, %s)')); // build placeholders for prepared statement
        $values = [];
        foreach ($meta_rows as $row) {
            $values = array_merge($values, $row);
        }

        $wpdb->query($wpdb->prepare(
            "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES {$placeholders}",
            ...$values
        ));

        // Commit transaction
        $wpdb->query('COMMIT');

        // Clean cache
        clean_post_cache($post_id);

        return rest_ensure_response(['status' => 'ok', 'post_id' => $post_id], 200);
    }
}
