<?php
/**
 * @package Span Bottom Banner
 * @version 1.1
 */
/*
Plugin Name: Span Bottom Banner
Description: Message at the bottom of the screen until clicked away.
Author: Alan Wills
Version: 1.1
*/
function span_nqbannerscript() {
    wp_enqueue_script("spanbottombanner", "/wp-content/plugins/span-bottom-banner/span-bottom-banner.js", array("jquery"), null, true);
}
add_action( 'wp_enqueue_scripts', 'span_nqbannerscript' );

