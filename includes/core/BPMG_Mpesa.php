<?php

/**
 * Mpesa related functions
 * @package    BPMpesaGateway
 * @subpackage BPMpesaGateway/includes/core
 */

namespace Inc\core;

class BPMG_Mpesa
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
    private $phone; // declare global so we can store value in db
    private $amount;
    private $transactionType = 'CustomerPayBillOnline';


    // Mpesa related functions can be added here in the future
    public function __construct()
    {
        // Initialize Mpesa properties from settings
        $this->consumer_key        = get_option('bpmg_consumer_key');
        $this->consumer_secret     = get_option('bpmg_consumer_secret');
        $this->shortcode           = get_option('bpmg_shortcode');
        $this->passkey             = get_option('bpmg_passkey');
        $this->access_token        = $this->generate_access_token();
        $this->timestamp           = date('YmdHis'); // should always come first before generate password so its not empty
        $this->password            = $this->generate_password();
        $this->account_reference   = get_option('bpmpesa_account_reference'); // figure out how to make it incremental
        $this->transaction_description = get_option('bpmpesa_transaction_reference');
        $this->amount              = get_option('bpmpesa_amount');
        $this->callbackurl         = home_url('/wp-json/bpmpesa/v1/callback', 'https');
        $this->url = $this->environment === 'production' ?
            'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest' :
            'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    }

    // Mpesa STK push request function
    public function send_stk_push_request($phone_number)
    {
        // check if consumer_key, consumer_secret, shortcode, passkey is empty
        $validation_result = $this->validate_config();
        if ($validation_result['status'] === 'error') {
            return $validation_result;
        }
        $this->phone = $phone_number;
        try {
            $data = [
                "BusinessShortCode" => $this->shortcode, // paybill number
                "Password" => $this->password, // generated password
                "Timestamp" => $this->timestamp, // current timestamp
                "TransactionType" => $this->transactionType, // transaction type (CustomerBuyGoodsOnline or CustomerPayBillOnline)
                "Amount" => $this->amount, // get amount from settings, do not allow zero or negative amounts
                "PartyA" => $this->phone, // phone number making payment
                "PartyB" => $this->shortcode, // paybill number
                "PhoneNumber" => $this->phone, // similar to pary A
                "AccountReference" => $this->account_reference, // transaction id
                "TransactionDesc" => $this->transaction_description, // description of transaction
                "CallBackURL" => $this->callbackurl, // webhook callback
            ];
            // send request to mpesa api
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $this->url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->access_token,
                'Content-Type: application/json',
            ]);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            $response = curl_exec($curl);

            return [
                'status' => 'success',
                'message' => 'Payment request sent. Enter your M-Pesa PIN.',
                'response' => json_decode($response, true), // decode the JSON response
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
        $auth_url = $this->environment === 'production' ?
            'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials' :
            'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

        $credentials = base64_encode($this->consumer_key . ':' . $this->consumer_secret);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $auth_url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $credentials
        ]);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);

        $result = json_decode($response, true);

        return isset($result['access_token']) ? $result['access_token'] : '';
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
        $required_fields = ['consumer_key', 'consumer_secret', 'shortcode', 'passkey', 'account_reference', 'transaction_description'];

        foreach ($required_fields as $field) {
            if (empty($this->$field)) {
                return [
                    'status' => 'error',
                    'message' => 'Missing required Mpesa configuration details for ' . $field,
                    'data' => [
                        'missing_field' => $field,
                        'field_value' => $this->$field
                    ]
                ];
            }
        }
        return ['status' => 'success', 'message' => 'Mpesa configuration is valid'];
    }

    // handle callback
    public function handle_callback($request)
    {
        error_log('BPMG Callback hit - Method: ' . $request->get_method());

        /*
     * ======================================================
     * 1. SAFARICOM CALLBACK (POST)
     * ======================================================
     */
        if ($request->get_method() === 'POST') {

            $raw_body = $request->get_body();
            $body = json_decode($raw_body, true);

            $stk = $body['Body']['stkCallback'] ?? null;

            if (!$stk) {
                return rest_ensure_response(['status' => 'ignored']);
            }

            $checkoutId = sanitize_text_field($stk['CheckoutRequestID']);
            $resultCode = (int) $stk['ResultCode'];
            $resultDesc = sanitize_text_field($stk['ResultDesc'] ?? '');

            $status = ($resultCode === 0) ? 'success' : 'failed';

            /*
         * Prevent duplicates (Safaricom retries callbacks)
         */
            $existing = get_posts([
                'post_type'   => 'mpesa',
                'meta_key'    => 'checkout_id',
                'meta_value'  => $checkoutId,
                'fields'      => 'ids',
                'numberposts' => 1,
            ]);

            if ($existing) {
                $post_id = $existing[0];
            } else {
                $post_id = wp_insert_post([
                    'post_type'   => 'mpesa',
                    'post_status' => 'publish',
                    'post_title'  => 'Mpesa STK ' . $checkoutId,
                ]);
            }

            if (is_wp_error($post_id)) {
                error_log('BPMG: Failed to create mpesa post');
                return rest_ensure_response(['status' => 'error']);
            }

            /*
         * Store callback data
         */
            update_post_meta($post_id, 'checkout_id', $checkoutId);
            update_post_meta($post_id, 'status', $status);
            update_post_meta($post_id, 'amount', $this->amount);
            update_post_meta($post_id, 'result_code', $resultCode);
            update_post_meta($post_id, 'result_desc', $resultDesc);
            update_post_meta($post_id, 'phone_number', $this->phone ?? '');
            update_post_meta($post_id, 'account_ref', $this->account_reference ?? '');
            update_post_meta($post_id, 'timestamp', current_time('mysql'));

            return rest_ensure_response(['status' => 'ok']);
        }

        /*
     * ======================================================
     * 2. JS POLLING (GET)
     * ======================================================
     */
        $checkoutId = sanitize_text_field($request->get_param('checkout_id'));

        if (!$checkoutId) {
            return rest_ensure_response([
                'status'  => 'error',
                'message' => 'No checkout_id provided',
            ]);
        }

        $posts = get_posts([
            'post_type'   => 'mpesa',
            'meta_key'    => 'checkout_id',
            'meta_value'  => $checkoutId,
            'numberposts' => 1,
        ]);

        if (!$posts) {
            return rest_ensure_response([
                'status'  => 'pending',
                'message' => 'Waiting for payment confirmation',
            ]);
        }

        $post_id = $posts[0]->ID;

        return rest_ensure_response([
            'status'    => get_post_meta($post_id, 'status', true),
            'message'   => get_post_meta($post_id, 'result_desc', true),
            'timestamp' => get_post_meta($post_id, 'timestamp', true),
        ]);
    }
    // save data if successful and allow user to continue registration
}
