<?php

namespace Inc\base;

class BPMG_Enqueue_Admin
{
    // Enqueue admin styles
    public static function bpmg_enqueue_admin()
    {
        wp_enqueue_style(
            'bpmpesagateway-admin-style',
            BPMG_PLUGIN_URL . 'admin/BPMG-admin.css'
        );
    }

    // Enqueue admin scripts
    public static function bpmg_enqueue_admin_scripts()
    {
        wp_enqueue_script(
            'bpmpesagateway-admin-script',
            BPMG_PLUGIN_URL . 'admin/BPMG-admin.js'
        );
    }
}
