<?php

/**
 * Fired during plugin deactivation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Test_Plugin
 * @subpackage Test_Plugin/includes
 */

namespace BPMpesaGateway\Base;

class BPMG_Deactivator {

    /**
     * Deactivation logic here
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Deactivation code goes here.
        flush_rewrite_rules();
    }
}