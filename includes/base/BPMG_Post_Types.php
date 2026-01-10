<?php

/**
 * Handles custom post types for Test Plugin
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    BPMpesaGateway
 * @subpackage BPMpesaGateway/includes
 */

namespace Inc\base;

class BPMG_Post_Types
{
    public static function register_custom_post_type()
    {
        register_post_type('mpesa', [
            'labels' => [
                'name'               => 'Mpesa Payments',
                'singular_name'      => 'Mpesa Payment',
                'add_new_item'       => 'New Transaction', // changes title of the page
                'edit_item'          => 'Edit Transaction',
                'new_item'           => 'New Transaction',
                'view_item'          => 'View Transaction',
                'search_items'       => 'Search Transactions',
                'not_found'          => 'No transactions found',
                'not_found_in_trash' => 'No transactions found in Trash',
            ],
            'public'        => true,
            'show_ui'       => true,
            'show_in_rest'  => true,
            'menu_icon'     => 'dashicons-money-alt',
            'can_export'    => true,
            'supports'      => ['custom-fields'],
        ]);
    }

    // custom columns for Mpesa post type
    public static function set_custom_edit_mpesacolumns($columns)
    {
        unset($columns['date']);
        unset($columns['title']);
        $columns['account_ref'] = 'Transaction Ref';
        $columns['phone_number'] = 'Phone Number';
        $columns['amount'] = 'Amount';
        $columns['status'] = 'Status';
        $columns['timestamp'] = 'Date';
        return $columns;
    }

    //populate custom columns with data
    public static function custom_mpesacolumns($column, $post_id)
    {
        switch ($column) {
            case 'account_ref':
                echo esc_html(get_post_meta($post_id, 'account_ref', true));
                break;
            case 'phone_number':
                echo esc_html(get_post_meta($post_id, 'phone_number', true));
                break;
            case 'amount':
                echo esc_html(get_post_meta($post_id, 'amount', true));
                break;
            case 'status':
                echo esc_html(get_post_meta($post_id, 'status', true));
                break;
            case 'timestamp':
                echo esc_html(get_the_date('', $post_id));
                break;
        }
    }

    //make columns sortable
    public static function sortable_columns($columns)
    {
        $columns['amount'] = 'amount';
        $columns['status'] = 'status';
        $columns['date'] = 'date';
        return $columns;
    }

    //handle sorting by meta value
    public static function handle_sorting_by_meta_value($query)
    {
        if (!is_admin() || !$query->is_main_query()) return;

        if ($query->get('orderby') == 'amount') {
            $query->set('meta_key', 'amount');
            $query->set('orderby', 'meta_value_num');
        }

        if ($query->get('orderby') == 'status') {
            $query->set('meta_key', 'status');
            $query->set('orderby', 'meta_value');
        }

        if ($query->get('orderby') == 'date') {
            $query->set('meta_key', 'date');
            $query->set('orderby', 'date');
        }
    }

    // populate the post page with transaction data
    
}
