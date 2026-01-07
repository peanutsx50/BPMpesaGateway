<?php

namespace Inc\base;

class BPMG_Enqueue_Public
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
    }
}
