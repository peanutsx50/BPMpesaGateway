<?php

/**
 * Admin settings template
 *
 * @package BPMpesaGateway
 */
if (!defined('ABSPATH')) {
    exit;
}

use BPMpesaGateway\Core\BPMGOptions;
$bpmg_options = BPMGOptions::get_options(); // retrieve options to pre-fill form fields

?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <p class="description">
        <?php esc_html_e('BPMpesaGateway allows you to control Mpesa payment requirements during BuddyPress registration.', 'bpmpesagateway'); ?>
    </p>
    <p style="color: red;">
        <strong><?php esc_html_e('Important:', 'bpmpesagateway'); ?></strong> <?php echo esc_html_e('Your M-Pesa credentials are strictly confidential. Never share them with anyone, including staff or third parties. Exposure of these credentials can result in unauthorized transactions and irreversible financial loss.', 'bpmpesagateway'); ?><br>
    </p>

    <form method="post" action="options.php">
        <?php

        // Output security fields for the registered setting "bpmpesagateway_options"
        settings_fields('bpmpesagateway_settings_group');
        ?>
        <table class="form-table" role="presentation">
            <!-- Consumer Key -->
            <tr>
                <th scope="row"><?php esc_html_e('Consumer Key', 'bpmpesagateway'); ?></th>
                <td>
                    <input type="password"
                        id="consumer_key"
                        name="bpmpesagateway_options[consumer_key]"
                        value="<?php echo esc_attr($bpmg_options['consumer_key'] ?? ''); ?>"
                        class="regular-text noCopyPaste">
                </td>
            </tr>

            <!-- Consumer Secret -->
            <tr>
                <th scope="row"><?php esc_html_e('Consumer Secret', 'bpmpesagateway'); ?></th>
                <td>
                    <input type="password"
                        id="consumer_secret"
                        name="bpmpesagateway_options[consumer_secret]"
                        value="<?php echo esc_attr($bpmg_options['consumer_secret'] ?? ''); ?>"
                        class="regular-text noCopyPaste">
                </td>
            </tr>

            <!-- Shortcode -->
            <tr>
                <th scope="row"><?php esc_html_e('Shortcode', 'bpmpesagateway'); ?></th>
                <td>
                    <input type="password"
                        id="shortcode"
                        name="bpmpesagateway_options[shortcode]"
                        value="<?php echo esc_attr($bpmg_options['shortcode'] ?? ''); ?>"
                        class="regular-text noCopyPaste">
                </td>
            </tr>

            <!-- Passkey -->
            <tr>
                <th scope="row"><?php esc_html_e('Passkey', 'bpmpesagateway'); ?></th>
                <td>
                    <input type="password"
                        id="passkey"
                        name="bpmpesagateway_options[passkey]"
                        value="<?php echo esc_attr($bpmg_options['passkey'] ?? ''); ?>"
                        class="regular-text noCopyPaste">
                </td>
            </tr>

            <!-- M-Pesa Account Reference -->
            <tr>
                <th scope="row"><?php esc_html_e('Account Reference Prefix', 'bpmpesagateway'); ?></th>
                <td>
                    <input type="text"
                        id="account_reference"
                        name="bpmpesagateway_options[account_reference]"
                        value="<?php echo esc_attr($bpmg_options['account_reference'] ?? ''); ?>"
                        class="regular-text">
                    <p style="margin-top: 5px; font-style: italic;">
                        Text shown as the Account Reference in M-Pesa. Appears on the user transaction details to help identify payments.Example: <code>Axios Tech Payment</code>
                    </p>
                </td>
            </tr>

            <!-- Transaction Reference -->
            <tr>
                <th scope="row"><?php esc_html_e('Transaction Reference Label', 'bpmpesagateway'); ?></th>
                <td>
                    <input type="text"
                        id="transaction_reference"
                        name="bpmpesagateway_options[transaction_reference]"
                        value="<?php echo esc_attr($bpmg_options['transaction_reference'] ?? ''); ?>"
                        class="regular-text">
                    <p style="margin-top: 5px; font-style: italic;">
                        Text shown on the customer’s M-Pesa statement to identify your business or payment purpose.
                        Example: <code>Community registration</code> or <code>Online Order</code>.
                    </p>
                </td>
            </tr>

            <!-- Amount -->
            <tr>
                <th scope="row"><?php esc_html_e('Payment Amount', 'bpmpesagateway'); ?></th>
                <td>
                    <input type="number"
                        id="amount"
                        name="bpmpesagateway_options[amount]"
                        min="1"
                        max="150000"
                        value="<?php echo esc_attr($bpmg_options['amount'] ?? ''); ?>"
                        class="small-text">
                    <p style="font-style: italic; margin-top: 5px;"><?php esc_html_e('Amount required to complete registration.', 'bpmpesagateway'); ?></p>
                </td>
            </tr>

        </table>

        <?php submit_button(); ?>
    </form>
</div>