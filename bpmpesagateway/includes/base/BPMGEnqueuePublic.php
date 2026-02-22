<?php

namespace BPMpesaGateway\Base;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class BPMGEnqueuePublic
{
    // Enqueue public styles and scripts
    public static function bpmg_enqueue_public()
    {
        wp_enqueue_style(
            'bpmpesagateway-public-style',
            BPMG_PLUGIN_URL . 'public/BPMG-public.css'
        );
        wp_enqueue_script(
            'bpmpesagateway-public-script',
            BPMG_PLUGIN_URL . 'public/BPMG-public.js',
            array('jquery'),
            null,
            true
        );

        //wp localize script to pass ajax url
        wp_localize_script('bpmpesagateway-public-script', 'bpmpesa_ajax', [ // script : matches the handle used in wp_enqueue_script
            'ajax_url' => admin_url('admin-ajax.php'), // core wordpress ajax handler
            'nonce'    => wp_create_nonce('bpmg_mpesa_nonce'), // security nonce
            'callback_url' => rest_url('bpmpesa/v1/callback'), // callback url
        ]);
    }
}
