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
}
