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


            <!-- Show Paybill on failure -->
            <tr>
                <th scope="row">Show Paybill on Failure</th>
                <td>
                    <label>
                        <input type="checkbox" name="bpmpesa_show_paybill" value="1"
                            <?php checked(1, get_option('bpmpesa_show_paybill')); ?>>
                        Show Paybill and Account Number if stk push fails
                    </label>
                </td>
            </tr>

            <!-- Paybill number -->
            <tr>
                <th scope="row">Paybill Number</th>
                <td>
                    <input type="text"
                        name="bpmpesa_paybill"
                        value="<?php echo esc_attr(get_option('bpmpesa_paybill')); ?>"
                        class="regular-text">
                </td>
            </tr>

            <!-- Account number -->
            <tr>
                <th scope="row">Account Number</th>
                <td>
                    <input type="text"
                        name="bpmpesa_account"
                        value="<?php echo esc_attr(get_option('bpmpesa_account')); ?>"
                        class="regular-text">
                </td>
            </tr>

            <!-- Amount -->
            <tr>
                <th scope="row">Payment Amount</th>
                <td>
                    <input type="number"
                        name="bpmpesa_amount"
                        value="<?php echo esc_attr(get_option('bpmpesa_amount')); ?>"
                        class="small-text">
                    <p class="description">Amount required to complete registration.</p>
                </td>
            </tr>

        </table>

        <?php submit_button(); ?>
    </form>
</div>