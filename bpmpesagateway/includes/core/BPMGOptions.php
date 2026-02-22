<?php

/**
 * BPMpesaGateway Options Class
 * 
 * This class handles the retrieval and management of plugin options for the BPMpesaGateway plugin.
 * It provides methods to get and update plugin settings stored in the WordPress options table.
 * 
 * @package    BPMpesaGateway
 * @subpackage BPMpesaGateway/includes
 * @since      1.0.0
 */

namespace BPMpesaGateway\Core;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class BPMGOptions
{
    private static $options = null;

    /**
     * Get plugin options
     *
     * @return array
     */
    public static function get_options($key = null, $default = null)
    {
        if (self::$options === null) {
            self::$options = get_option('bpmpesagateway_options', []);
        }
        if ($key !== null) {
            $value = self::$options[$key] ?? null;

            // Return stored value if it exists and is not empty
            if ($value !== null && $value !== '') {
                return $value;
            }

            // Handle callable defaults
            return is_callable($default) ? $default() : $default;
        }

        return self::$options;
    }

    /**
     * Refresh plugin options
     *
     * @return void
     */
    public static function refresh(){
        self::$options = null;
    }


}
