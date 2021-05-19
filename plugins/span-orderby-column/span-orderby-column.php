<?php
/*
Plugin Name: Span Orderby Column
Description: Displays custom field Order in the admin Posts list
Version: 1.1
Author: Alan Cameron Wills
License: GPL2

Copyright 2020  Alan Cameron Wills  (email : alan@cameronwills.org)

*/
// Add the custom column to the post type
//add_filter( 'manage_pages_columns', 'span_add_custom_column' );
add_filter( 'manage_posts_columns', 'span_add_custom_column' );
function span_add_custom_column( $columns ) {
    $columns['menu_order'] = 'Menu Order';

    return $columns;
}

// Add the data to the custom column
//add_action( 'manage_pages_custom_column' , 'span_add_custom_column_data', 10, 2 );
add_action( 'manage_posts_custom_column' , 'span_add_custom_column_data', 10, 2 );
function span_add_custom_column_data( $column, $post_id ) {
    switch ( $column ) {
        case 'menu_order' :
			$post = get_post( $post_id );
			echo span_getOrder( $post ); // the data that is displayed in the column
            break;
    }
}

// Make the custom column sortable
//add_filter( 'manage_edit-page_sortable_columns', 'span_add_custom_column_make_sortable' );
add_filter( 'manage_edit-post_sortable_columns', 'span_add_custom_column_make_sortable' );
function span_add_custom_column_make_sortable( $columns ) {
	$columns['menu_order'] = 'menu_order';

	return $columns;
}

// Add custom column sort request to post list page
add_action( 'load-edit.php', 'span_add_custom_column_sort_request' );
function itsg_add_custom_column_sort_request() {
	add_filter( 'request', 'span_add_custom_column_do_sortable' );
}

// Handle the custom column sorting
function span_add_custom_column_do_sortable( $vars ) {
	// check if sorting has been applied
	if ( isset( $vars['orderby'] ) && 'modified' == $vars['orderby'] ) {

		// apply the sorting to the post list
		$vars = array_merge(
			$vars,
			array(
				'orderby' => 'menu_order'
			)
		);
	}

	return $vars;
}

function span_getOrder($post) {
  $thispost = get_post($post);
  if (!$thispost) return 0;
  if (!isset($thispost->menu_order)) return 0;
  return $thispost->menu_order;
}

?>
