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
        $this->timestamp           = date('YmdHis');
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
                "CallBackURL" => 'https://webhook.site/314181b3-7a95-47ad-af61-1acbfad8e42d', // test endpoing, will use ngrok later
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
            return ['status' => 'success', 'message' => 'Mpesa request sent successfully', 'response' => $response];
        } catch (\Exception $e) {
            $this->err = $e->getMessage();
            return ['status' => 'error', 'message' => 'Exception: ' . $this->err];
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
        return rest_ensure_response(array('message' => 'Hello World'));
    }
}
