<?php

namespace BPMpesaGateway\Public;

use BPMpesaGateway\Core\BPMGMpesa;

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
        wp_enqueue_style($this->bpmpesagateway . '-public-style', BPMG_PUBLIC_CSS_URL . 'BPMG-public.css', array(), $this->version, 'all');
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script($this->bpmpesagateway . '-public-script', BPMG_PUBLIC_JS_URL . 'BPMG-public.min.js', array('jquery'), $this->version, true);
    }

    public function localize_scripts()
    {
        //wp localize script to pass ajax url
        wp_localize_script(
            $this->bpmpesagateway . '-public-script',
            'bpmpesa_ajax',
            [ // script : matches the handle used in wp_enqueue_script
                'ajax_url' => admin_url('admin-ajax.php'), // core wordpress ajax handler
                'nonce'    => wp_create_nonce('bpmg_mpesa_nonce'), // security nonce
                'callback_url' => rest_url('bpmpesa/v1/callback'), // callback url
                'process_payment_url' => rest_url('bpmpesa/v1/process-payment'), // endpoint to initiate payment from frontend
            ]
        );
    }

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

    public function handle_mpesa_request()
    {
        // Check nonce for security
        if (!isset($_POST['bpmg_nonce']) || !wp_verify_nonce($_POST['bpmg_nonce'], 'bpmg_mpesa_nonce')) {
            wp_send_json_error(['message' => 'Invalid request']); // deny request if nonce is invalid
            wp_die();
        }
        $phone = sanitize_text_field($_POST['phone']); // this code receives phone number from ajax request via post
        // send the request to mpesa api
        $BPMG_Mpesa = new BPMGMpesa();
        $payment_response = $BPMG_Mpesa->send_stk_push_request($phone);
        // handle the response
        if ($payment_response['status'] === 'success') {
            // send message saying we sent request 
            wp_send_json_success(['message' => $payment_response['message'], 'response' => $payment_response['response']]); // send response back to ajax
        } else {
            wp_send_json_error(['message' => $payment_response['message']]); // send error response back to ajax
        }
        wp_die();
    }
}
