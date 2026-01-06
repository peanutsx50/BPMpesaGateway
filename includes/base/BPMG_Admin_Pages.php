<?php

/**
 * Handles admin pages for Test Plugin
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Test_Plugin
 * @subpackage Test_Plugin/includes
 */

namespace Inc\base;

class BPMG_Admin_Pages{
    public static function add_admin_pages(){
        add_menu_page('BPMpesaGateway', 'BPMpesaGateway', 'manage_options', 'bpmpesagateway', [self::class, 'admin_index'], 'dashicons-admin-generic', 110);
    }
    
    public static function admin_index(){
        // Admin page content goes here
        echo '<h1>Welcome to the BPMpesaGateway Admin Page</h1>';
    }
}