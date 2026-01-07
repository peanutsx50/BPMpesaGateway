<?php

/**
 * Custom registration fields for BuddyPress registration form
 * @package    BPMpesaGateway
 * @subpackage BPMpesaGateway/includes/templates
 * */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}
?>

<!-- Custom Registration Fields Start -->
<div class="bpmg_custom_registration_container">
    <h2>Complete Registration with Mpesa</h2>
    <p>Enter your phone number to receive a payment request</p>
    <div class="bpmg_mpesa_amount">
        <strong>Amount to pay:</strong> <?php echo esc_attr(get_option('bpmpesa_amount') ?: 0); ?>
    </div>
    <div class="bpmg_mpesa_form">
        <label for="bpmg_mpesa_phone">Enter M-Pesa Phone Number:</label>
        <input type="tel" id="bpmg_mpesa_phone" name="bpmg_mpesa_phone" value="" placeholder="e.g., +254 712 345 678" />
    </div>
    <div class="bpmg_mpesa_note">
        A payment request will be sent to this number. Use the PIN prompt on your phone to complete payment.
    </div>
    <div id="bpmg_error_message" style="display:none; color: red; margin-top: 10px;font-style: italic;"></div>
    <div class="bpmg_mpesa_button">
        <button type="button" id="bpmg_send_mpesa_request">Send M-Pesa Payment Request</button>
    </div>
</div>