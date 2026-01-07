<?php

namespace Inc\core;

class BPMG_Mpesa
{
    // Mpesa properties
    private $consumer_key;
    private $consumer_secret;
    private $shortcode;
    private $initiator;
    private $password;
    private $passkey;
    private $access_token;
    private $timestamp;
    private $environment = 'sandbox'; //sandbox or production
    private $err = '';
    private $url;


    // Mpesa related functions can be added here in the future
    public function __construct()
    {
        // Initialize Mpesa properties from settings
        $this->consumer_key        = getenv('bpmpesa_consumer_key');
        $this->consumer_secret     = getenv('bpmpesa_consumer_secret');
        $this->shortcode           = getenv('bpmpesa_paybill');
        $this->initiator           = getenv('bpmpesa_initiator');
        $this->passkey             = getenv('bpmpesa_passkey');
        $this->access_token = $this->generate_access_token($this->consumer_key, $this->consumer_secret);
        $this->timestamp            = date('YmdHis');
        $this->url = $this->environment === 'production' ?
            'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest' :
            'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    }

    // Mpesa STK push request function
    public function send_stk_push_request($phone_number)
    {
        $data = [
            "BusinessShortCode" => $this->shortcode, // paybill number
            "Password" => $this->password, // generated password
            "Timestamp" => $this->timestamp, // current timestamp
            "TransactionType" => "CustomerPayBillOnline", // transaction type
            "Amount" => get_option('bpmpesa_amount'), // get amount from settings
            "PartyA" => $phone_number, // phone number making payment
            "PartyB" => $this->shortcode, // paybill number
            "PhoneNumber" => $phone_number,
            "AccountReference" => get_option('bpmpesa_account_reference'),
            "TransactionDesc" => get_option('bpmpesa_transaction_reference'),
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
        // return ['status' => 'success'];
    }

    private function generate_access_token($consumer_key, $consumer_secret)
    {
        // generate security credential logic
        $credentails = base64_encode($consumer_key . ':' . $consumer_secret);
        return $credentails;
    }
}
