<?php

/**
 * Create custom fields in Buddypress resgistration form, validate them
 * @package    BPMpesaGateway
 * @subpackage BPMpesaGateway/includes
 */

namespace Inc\core;

class BPMG_Registration
{

    //constructor
    public function __construct()
    {
        $this->register();
    }

    // register hooks
    private function register()
    {
        add_action('bp_before_registration_submit_buttons', array($this, 'bpmg_add_custom_registration_fields'));
    }

    //add custom fields to registration form
    public function bpmg_add_custom_registration_fields()
    {
        $template_path = BPMG_PLUGIN_PATH . 'includes/templates/registration-fields.php';
        if (file_exists($template_path)) {
            include $template_path;
        }
    }

    //send mpesa request
    //check payment status
    //complete registration
}
