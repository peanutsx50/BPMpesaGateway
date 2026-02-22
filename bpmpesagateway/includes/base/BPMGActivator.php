<?php

/**
 * Fired during plugin activation
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

class BPMGActivator {

    /**
     * Activation logic here
     *
     * @since    1.0.0
     */
    public static function activate() {
        // Activation code goes here.
        BPMGPostTypes::register_custom_post_type();
        flush_rewrite_rules();
    }
}