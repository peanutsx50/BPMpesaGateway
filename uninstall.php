<?php

// if uninstall.php is not called by WordPress, die
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit; // Exit if accessed directly
}

// Remove custom post type on uninstall
$posts = get_posts(array(
    'post_type' => 'mpesa',
    'numberposts' => -1,
    'post_status' => 'any'
));
    
foreach ($posts as $post) {
    wp_delete_post($post->ID, true); // get ID and delete permanently
}

// 2. Delete all plugin settings/options
$settings_keys = [
    'bpmpesa_allow_payments',
    'bpmpesa_save_transactions',
    'bpmg_consumer_key',
    'bpmg_consumer_secret',
    'bpmg_shortcode',
    'bpmg_passkey',
    'bpmpesa_account_reference',
    'bpmpesa_transaction_reference',
    'bpmpesa_show_paybill',
    'bpmpesa_payment_type',
    'bpmpesa_paybill',
    'bpmpesa_account',
    'bpmpesa_amount'
];

foreach ($settings_keys as $key) {
    delete_option($key); // if option dosent exist it returns false
}