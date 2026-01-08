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
        $this->access_token = $this->generate_access_token($this->consumer_key, $this->consumer_secret);
        $this->password            = $this->generate_password();
        $this->account_reference   = get_option('bpmpesa_account_reference');
        $this->transaction_description = get_option('bpmpesa_transaction_reference');
        $this->timestamp            = date('YmdHis');
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
                "TransactionType" => "CustomerPayBillOnline", // transaction type
                "Amount" => get_option('bpmpesa_amount'), // get amount from settings, do not allow zero or negative amounts
                "PartyA" => $phone_number, // phone number making paymentd
                "PartyB" => $this->shortcode, // paybill number
                "PhoneNumber" => $phone_number,
                "AccountReference" => $this->account_reference,
                "TransactionDesc" => $this->transaction_description,
                "CallBackURL" => home_url('/callback'),
            ];
            // send request to mpesa api
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $this->url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'Authorization: Basic ' . $this->access_token,
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
            return ['status' => 'error', 'message' => 'Exception: ' . $e->getMessage()];
        }

        // return ['status' => 'success'];
    }

    // generate access token for mpesa api
    private function generate_access_token($consumer_key, $consumer_secret)
    {
        $credentails = base64_encode($consumer_key . ':' . $consumer_secret);
        return $credentails;
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
}
