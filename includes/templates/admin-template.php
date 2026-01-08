<?php

/**
 * Admin settings template
 *
 * @package BPMpesaGateway
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <p class="description">
        BPMpesaGateway allows you to control Mpesa payment requirements during BuddyPress registration.
    </p>
    <p style="color: red;">
        <strong>Important:</strong> Your M-Pesa credentials are strictly confidential. Never share them with anyone, including staff or third parties. Exposure of these credentials can result in unauthorized transactions and irreversible financial loss.<br>
    </p>

    <form method="post" action="options.php">
        <?php
        settings_fields('bpmpesa_settings_group');
        do_settings_sections('bpmpesa_settings_group');
        ?>
        <table class="form-table" role="presentation">

            <!-- Allow payments -->
            <tr>
                <th scope="row">Allow Payments</th>
                <td>
                    <label>
                        <input type="checkbox" name="bpmpesa_allow_payments" value="1"
                            <?php checked(1, get_option('bpmpesa_allow_payments')); ?>>
                        Require Mpesa payment before registration
                    </label>
                </td>
            </tr>

            <!-- Save transactions -->
            <tr>
                <th scope="row">Save Transactions</th>
                <td>
                    <label>
                        <input type="checkbox" name="bpmpesa_save_transactions" value="1"
                            <?php checked(1, get_option('bpmpesa_save_transactions')); ?>>
                        Store all Mpesa transactions in the database
                    </label>
                </td>
            </tr>

            <!-- Consumer Key -->
            <tr>
                <th scope="row">Consumer Key</th>
                <td>
                    <input type="password"
                        name="bpmg_consumer_key"
                        value="<?php echo esc_attr(get_option('bpmg_consumer_key')); ?>"
                        class="regular-text">
                </td>
            </tr>

            <!-- Consumer Secret -->
            <tr>
                <th scope="row">Consumer Secret</th>
                <td>
                    <input type="password"
                        name="bpmg_consumer_secret"
                        value="<?php echo esc_attr(get_option('bpmg_consumer_secret')); ?>"
                        class="regular-text">
                </td>
            </tr>

            <!-- Shortcode -->
            <tr>
                <th scope="row">Shortcode</th>
                <td>
                    <input type="password"
                        name="bpmg_shortcode"
                        value="<?php echo esc_attr(get_option('bpmg_shortcode')); ?>"
                        class="regular-text">
                </td>
            </tr>

            <!-- Passkey -->
            <tr>
                <th scope="row">Passkey</th>
                <td>
                    <input type="password"
                        name="bpmg_passkey"
                        value="<?php echo esc_attr(get_option('bpmg_passkey')); ?>"
                        class="regular-text">
                </td>
            </tr>

            <!-- M-Pesa Account Reference -->
            <tr>
                <th scope="row">Account Reference Prefix</th>
                <td>
                    <input type="text"
                        name="bpmpesa_account_reference"
                        value="<?php echo esc_attr(get_option('bpmpesa_account_reference')); ?>"
                        class="regular-text">
                    <p style="margin-top: 5px; font-style: italic;">
                        Prefix used to generate M-Pesa payment references.
                        Example: <code>INV</code> will produce <code>INV001</code>, <code>INV002</code>.
                        This helps identify and match incoming payments.
                    </p>
                </td>
            </tr>

            <!-- Transaction Reference -->
            <tr>
                <th scope="row">Transaction Reference Label</th>
                <td>
                    <input type="text"
                        name="bpmpesa_transaction_reference"
                        value="<?php echo esc_attr(get_option('bpmpesa_transaction_reference')); ?>"
                        class="regular-text">
                    <p style="margin-top: 5px; font-style: italic;">
                        Text shown on the customer’s M-Pesa statement to identify your business or payment purpose.
                        Example: <code>Axios Tech Payment</code> or <code>Online Order</code>.
                    </p>
                </td>
            </tr>


            <!-- Show Paybill or Till on STK failure -->
            <tr>
                <th scope="row">Display Paybill / Till on Failure</th>
                <td>
                    <label>
                        <input type="checkbox" name="bpmpesa_show_paybill" value="1"
                            <?php checked(1, get_option('bpmpesa_show_paybill')); ?>>
                        Display the Paybill or Till Number, allowing the user to complete payment manually.
                    </label>
                </td>
            </tr>

            <!-- Payment Type -->
            <tr>
                <th scope="row">Payment Type</th>
                <td>
                    <select name="bpmpesa_payment_type" id="bpmpesa_payment_type">
                        <option value="paybill" <?php selected(get_option('bpmpesa_payment_type'), 'paybill'); ?>>
                            Paybill
                        </option>
                        <option value="till" <?php selected(get_option('bpmpesa_payment_type'), 'till'); ?>>
                            Till Number
                        </option>
                    </select>
                    <p style="font-style: italic;">
                        Select whether you are using a Paybill or a Till number.
                    </p>
                </td>
            </tr>

            <!-- Paybill / Till Number -->
            <tr>
                <th scope="row">Paybill / Till Number</th>
                <td>
                    <input type="text"
                        name="bpmpesa_paybill"
                        value="<?php echo esc_attr(get_option('bpmpesa_paybill')); ?>"
                        class="regular-text">
                </td>
            </tr>

            <!-- Account Number -->
            <tr id="bpmpesa_account_row">
                <th scope="row">Account Number</th>
                <td>
                    <input type="text"
                        name="bpmpesa_account"
                        value="<?php echo esc_attr(get_option('bpmpesa_account')); ?>"
                        class="regular-text">
                    <p class="description">
                        Required for Paybill payments only. Not applicable when using a Till number.
                    </p>
                </td>
            </tr>

            <!-- Amount -->
            <tr>
                <th scope="row">Payment Amount</th>
                <td>
                    <input type="number"
                        name="bpmpesa_amount"
                        min="1"
                        value="<?php echo esc_attr(get_option('bpmpesa_amount')); ?>"
                        class="small-text">
                    <p style="font-style: italic; margin-top: 5px;">Amount required to complete registration.</p>
                </td>
            </tr>

        </table>

        <?php submit_button(); ?>
    </form>
</div>