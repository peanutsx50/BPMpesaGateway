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