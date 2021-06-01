<?php
/**
 * @package Span Reduce CSS
 * @version 1.1
 */
/*
Plugin Name: Span Reduce CSS
Description: Discover all CSS classes required.
Author: Alan Wills
Version: 1.1
*/
function span_reduce_css() {
    wp_enqueue_script("span_reduce_css", "/wp-content/plugins/span-reduce-css/span-reduce-css.js", array("jquery"), null, true);
}
add_action( 'wp_enqueue_scripts', 'span_reduce_css' );

