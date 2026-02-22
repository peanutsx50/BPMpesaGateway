<?php

/**
 * Enqueue admin styles and scripts for BPMpesaGateway plugin.
 *
 * @package    BPMpesaGateway
 * @subpackage BPMpesaGateway/includes
 */


namespace BPMpesaGateway\Base;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class BPMGEnqueueAdmin
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
