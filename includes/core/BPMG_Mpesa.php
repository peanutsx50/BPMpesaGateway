<?php

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
    private $environment = 'sandbox'; //sandbox or production
    private $err;
    private $url;


    // Mpesa related functions can be added here in the future
    public function __construct()
    {
        // Initialize Mpesa properties from settings
        $this->consumer_key        = getenv('bpmg_consumer_key');
        $this->consumer_secret     = getenv('bpmg_consumer_secret');
        $this->shortcode           = getenv('bpmg_paybill');
        $this->passkey             = getenv('bpmg_passkey');
        $this->access_token = $this->generate_access_token($this->consumer_key, $this->consumer_secret);
        $this->password            = $this->generate_password();
        $this->timestamp            = date('YmdHis');
        $this->url = $this->environment === 'production' ?
            'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest' :
            'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    }

    // Mpesa STK push request function
    public function send_stk_push_request($phone_number)
    {
        try {
            $data = [
                "BusinessShortCode" => $this->shortcode, // paybill number
                "Password" => $this->password, // generated password
                "Timestamp" => $this->timestamp, // current timestamp
                "TransactionType" => "CustomerPayBillOnline", // transaction type
                "Amount" => get_option('bpmpesa_amount'), // get amount from settings, do not allow zero or negative amounts
                "PartyA" => $phone_number, // phone number making payment
                "PartyB" => $this->shortcode, // paybill number
                "PhoneNumber" => $phone_number,
                "AccountReference" => get_option('bpmpesa_account_reference'), // check if empty and provide default
                "TransactionDesc" => get_option('bpmpesa_transaction_reference'), // check if empty and provide default
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
}
