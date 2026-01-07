<?php

/**
 * Create custom fields in Buddypress resgistration form, validate them
 * @package    BPMpesaGateway
 * @subpackage BPMpesaGateway/includes
 */

namespace Inc\core;

class BPMG_Registration
{

    //constructor
    public function __construct()
    {
        $this->register();
    }

    // register hooks
    private function register()
    {
        add_action('bp_before_registration_submit_buttons', array($this, 'bpmg_add_custom_registration_fields'));
        //ajax hooks
        add_action('wp_ajax_bpmg_send_mpesa_request', array($this, 'handle_mpesa_request')); // logged in users
        add_action('wp_ajax_nopriv_bpmg_send_mpesa_request', array($this, 'handle_mpesa_request')); // non-logged in users
    }

    //add custom fields to registration form
    public function bpmg_add_custom_registration_fields()
    {
        $template_path = BPMG_PLUGIN_PATH . 'includes/templates/registration-fields.php';
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
        $payment_response = $this->bpmg_send_mpesa_payment_request($phone);
        // handle the response
        if ($payment_response['status'] === 'success') {
            wp_send_json_success(['message' => 'Payment request sent successfully. Please complete the payment on your phone.']);
        } else {
            wp_send_json_error(['message' => 'Failed to send payment request. Please try again.']);
        }
        wp_die();
    }

    //send mpesa payment request
    private function bpmg_send_mpesa_payment_request($phone)
    {
        //code to send payment request to mpesa api
        //return response
        return ['status' => 'success'];
    }
    //check payment status
    private function bpmg_check_payment_status()
    {
        //code to check payment status
    }
    //save transaction details
    private function bpmg_save_transaction_details()
    {
        //code to save transaction details
    }
    //complete registration
    private function bpmg_complete_registration()
    {
        //code to complete registration after payment confirmation
    }
}
