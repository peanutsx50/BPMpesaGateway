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
    <h2><?php echo esc_html__('Complete Registration with M-Pesa', 'bpmpesagateway'); ?></h2>
    <p><?php echo esc_html__('Enter your phone number to receive a payment request', 'bpmpesagateway'); ?></p>
    <div class="bpmg_mpesa_amount">
        <strong><?php echo esc_html__('Amount to pay:', 'bpmpesagateway'); ?></strong> <?php echo esc_attr(get_option('bpmpesa_amount') ?: 0); ?>
    </div>
    <div class="bpmg_mpesa_form">
        <label for="bpmg_mpesa_phone"><?php echo esc_html__('Enter M-Pesa Phone Number:', 'bpmpesagateway'); ?></label>
        <input type="tel" id="bpmg_mpesa_phone" name="bpmg_mpesa_phone" value="" placeholder="e.g., 254 712 345 678" />
    </div>
    <div class="bpmg_mpesa_note">
        <?php echo esc_html__('Payment request will be sent. Enter your M-Pesa PIN to complete registration.', 'bpmpesagateway'); ?>
    </div>
    <div id="bpmg_error_message" style="display:none; color: red; margin-top: 10px;font-style: italic;"></div>
    <div class="bpmg_mpesa_button">
        <button type="button" id="bpmg_send_mpesa_request"><?php echo esc_html__('Send M-Pesa Payment Request', 'bpmpesagateway'); ?></button>
    </div>
</div>