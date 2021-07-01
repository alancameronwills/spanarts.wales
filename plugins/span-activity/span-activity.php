<?php
/**
 * @package Activity
 * @version 1.1
 */
/*
Plugin Name: Span activity
Description: Retrieves the list of posts of a given category and/or TicketSolve events.
Author: Alan Wills
Version: 1.1
*/

include 'span-ticketsolve.php';
include 'span-activities-shortcode.php';


/* Expose menu_order for posts
 * https://wordpress.stackexchange.com/questions/91866/how-to-use-menu-order-field-for-posts
 */
add_action('init', 'wpse31629_a_init');
function wpse31629_a_init()
{
  add_post_type_support('post', 'page-attributes');
}

/* Allow editor to handle menu_order https://core.trac.wordpress.org/ticket/46264
 */
add_action('rest_api_init', function () {
  register_rest_field('post', 'menu_order', [
    'get_callback' => function ($object) {
      if (!isset($object['menu_order'])) {
        return 0;
      }
      return (int) $object['menu_order'];
    },
    'schema' => [
      'type' => 'integer',
    ],
  ]);
});

function span_custom_excerpt_length($length)
{
  return 200;
}
add_filter('excerpt_length', 'span_custom_excerpt_length', 1999);

?>
