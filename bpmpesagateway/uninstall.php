<?php

// if uninstall.php is not called by WordPress, die
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit; // Exit if accessed directly
}

// Remove custom post type on uninstall
$posts = get_posts(array(
    'post_type' => 'bpmg_payment',
    'numberposts' => -1,
    'post_status' => 'any'
));
    
foreach ($posts as $post) {
    wp_delete_post($post->ID, true); // get ID and delete permanently
}

// 2. Delete all plugin settings/options
delete_option('bpmpesagateway_options');