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
        $this->account_reference   = get_option('bpmpesa_account_reference');
        $this->transaction_description = get_option('bpmpesa_transaction_reference');
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

        try {
            $data = [
                "BusinessShortCode" => $this->shortcode, // paybill number
                "Password" => $this->password, // generated password
                "Timestamp" => $this->timestamp, // current timestamp
                "TransactionType" => "CustomerPayBillOnline", // transaction type (CustomerBuyGoodsOnline or CustomerPayBillOnline)
                "Amount" => get_option('bpmpesa_amount'), // get amount from settings, do not allow zero or negative amounts
                "PartyA" => $phone_number, // phone number making paymentd
                "PartyB" => $this->shortcode, // paybill number
                "PhoneNumber" => $phone_number,
                "AccountReference" => $this->account_reference,
                "TransactionDesc" => $this->transaction_description,
                "CallBackURL" => home_url('/wp-json/bpmpesa/v1/callback', 'https'), // webhook callback
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
        // Log all requests for debugging
        error_log('BPMG Callback hit - Method: ' . $request->get_method());

        // Log the raw body first
        $raw_body = $request->get_body();
        $body = json_decode($raw_body, true);
        $stk = $body['Body']['stkCallback'] ?? null; // will be empty if called by JS

        // If called by Safaricom (POST request with callback data)
        if ($stk) {
            error_log('BPMG: Result Code : ' . $stk['ResultCode']);
            error_log('BPMG: Result Description : ' . $stk['ResultDesc']);
            $checkoutId = $stk['CheckoutRequestID'];
            $resultCode = $stk['ResultCode'];
            $resultDesc = $stk['ResultDesc'] ?? 'No description';

            $status = ($resultCode == 0) ? 'success' : 'failed';

            // Store the status and additional data
            return update_option('bpmg_stk_' . $checkoutId, [
                'status' => $status,
                'result_code' => $resultCode,
                'result_desc' => $resultDesc,
                'timestamp' => current_time('mysql')
            ]);
        }

        // If polled via JS (GET request with checkout_id parameter)
        $checkout_id = $request->get_param('checkout_id');

        if ($checkout_id) {
            $checkout_id = sanitize_text_field($checkout_id);
            $stored_data = get_option('bpmg_stk_' . $checkout_id, null);

            error_log('BPMG: Polling for ' . $checkout_id . ' - Found: ' . print_r($stored_data, true));

            // If data exists and is an array (new format)
            if (is_array($stored_data)) {
                return rest_ensure_response([
                    'status' => $stored_data['status'],
                    'message' => $stored_data['result_desc'] ?? '',
                    'timestamp' => $stored_data['timestamp'] ?? ''
                ]);
            }

            // No data found yet - still pending
            return rest_ensure_response([
                'status' => 'pending',
                'message' => 'Waiting for payment confirmation',
                'data' => $stored_data,
            ]);
        }

        // No checkout_id provided
        return rest_ensure_response([
            'status' => 'error',
            'message' => 'No checkout ID provided'
        ]);
    }
}
