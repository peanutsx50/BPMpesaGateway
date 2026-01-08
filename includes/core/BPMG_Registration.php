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

    /**
     * Add custom M-Pesa registration fields to the BuddyPress registration form.
     *
     * This method is hooked to 'bp_before_registration_submit_buttons' and is responsible
     * for displaying additional input fields on the registration form. The fields are 
     * included from a separate template file to keep markup organized.
     *
     * The template file path is constructed from the plugin's base path:
     * BPMG_PLUGIN_PATH . 'includes/templates/registration-fields.php'
     *
     * If the template file exists, it is included and its contents (HTML inputs, labels, etc.)
     * are rendered on the registration form. If the file does not exist, nothing is output.
     *
     * Usage:
     * This function is automatically triggered via the BuddyPress hook when rendering 
     * the registration form.
     *
     * @return void Outputs HTML content from the template file if it exists.
     */

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
        $BPMG_Mpesa = new BPMG_Mpesa();
        $payment_response = $BPMG_Mpesa->send_stk_push_request($phone);
        // handle the response
        if ($payment_response['status'] === 'success') {
            // send back message and data
            wp_send_json_success(['message' => $payment_response['message'], 'data' => $payment_response['data']]); // send response back to ajax
        } else {
            wp_send_json_error(['message' => $payment_response['message'], 'data' => $payment_response['data']]); // send error response back to ajax
        }
        wp_die();
    }
}
