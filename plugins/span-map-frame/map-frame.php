<?php
/**
 * @package Map Frame
 * @version 1.1
 */
/*
Plugin Name: Span map frame
Description: Display a page alongside a Span Deep Map. Put shortcode [[mapframe]] at top.
Author: Alan Wills
Version: 1.1
*/


// Shortcode for getting activities list
function span_map_frame($attributes = array() ) {
    extract(shortcode_atts(array(
        'project' => 'folio', 
        'outer' => 'article',
        'inner' => 'article\x3Ediv'
       ), $attributes));
    ob_start();
?><script>smf_project="<?=$project?>";smf_outer="<?=$outer?>";smf_inner="<?=$inner?>";</script>
<script src="/wp-content/plugins/span-map-frame/includes/js/span-map-frame.js"></script>
<?php
	return ob_get_clean();
}

add_shortcode("mapframe", "span_map_frame");

?>