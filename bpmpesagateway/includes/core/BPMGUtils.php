<?php

namespace BPMpesaGateway\Core;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class BPMGUtils{
    public static function debug_to_console($data, $context = 'Debug in Console') {
    // Buffering to solve problems with frameworks that may use header()
    ob_start(); 
    $output = 'console.info(\'' . $context . ':\');';
    // Use json_encode to convert PHP variables to a JavaScript-compatible JSON string
    $output .= 'console.log(' . json_encode($data) . ');';
    $output = sprintf('<script>%s</script>', $output);
    echo $output;
    // End buffering and flush output
    ob_end_flush(); 
}

}

