<?php

/**
 * Fired during plugin deactivation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    BPMpesaGateway
 * @subpackage BPMpesaGateway/includes
 */

namespace BPMpesaGateway\Base;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class BPMGDeactivator {

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