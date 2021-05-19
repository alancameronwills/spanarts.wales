<?php
/**
 * @package Activities
 * @version 1.1
 */
/*
Plugin Name: Span activities
Description: Retrieves the list of posts of a given category.
Author: Alan Wills
Version: 1.1
*/

function get_activity() {

        return wptexturize(  );
}

function strip_the_description ($d, $debug=false, $paras) {
    $d = preg_replace('/\[iframe.*?\]/', "", preg_replace('/<iframe .*<\/iframe>/', "", $d));
    $d = preg_replace("~\[.*?\]~s", '', $d); // strip_shortcodes is no good, removes all the content
    $d = preg_replace('#</div>|</h.>|</p>|<br.*?>#', "¬¬¬", $d);
    $d = preg_replace("/<.*?>/", "", $d);
    $d = preg_replace('/&nbsp;/', " ", $d);
    $d = preg_replace('/^[\s¬]+/', '', $d);
    $d = preg_replace('/[\s¬]+$/', '', $d);
    $d = preg_replace('#http.?://[^\'"\s]*#', '', $d);
    if (strlen($d)>1000) $d = substr($d, 0, 1000);
    // return preg_replace('/(¬¬¬\s*)+/', "<br/>", $d ); 
    return implode("<br/>", array_slice(preg_split('/(¬¬¬\s*)+/', $d), 0, $paras));
}


/** Generate HTML for a list of posts
 * @param $category Get posts in or under this category
 * @param $topclass Class for the outer div
 * @param $imgsize 'thumbnail' | 'medium' | 'full'
 * @param $count Number of items to generate
 * @param $order title | menu_order | date | modified
 * @param $paras Limit count of paragraphs to show in synopsis
 * @param $nolink Don't provide a link
 * @param $template Sequence of words chosen from: title subtitle img where when posted text. { } nests in a div.
 */
function span_list_activities($category, $topclass='eventList', $imgsize='medium', $count=20, $order = '', $paras = 2, $nolink=false, $template="", $debug=false) {
    if ($debug) { echo "yyyy"; }
    $args = array('category_name' => $category,
        'tax_query' => array(
                array(
                    'taxonomy' => 'category',
                    'field' => 'slug',
                    'terms' => 'cymraeg',
                    'operator' => 'NOT IN'
                )  
        ) 
    );
    if (!empty($order)) {
        $args['orderby'] = $order;
        if ($order == "date" || $order == "modified" || $order == "menu_order") {
            $args['order'] = "DESC";
        } else {
            $args['order'] = "ASC";
        }
    }
    if ($count) $args['posts_per_page'] = $count;

    
    // Check expiry date
    $meta_query = array (
        'relation' => 'OR',
        array (
            'key' => 'expires',
            'compare' => 'NOT EXISTS'
        ),
        array (
            'key' => 'expires',
            'compare' => '=',
            'value' => ''
        ),
        array (
            'key' => 'expires',
            'value' => date('Y-m-d'),
            'compare' => '>=',
            'type' => 'DATE'
        )
    );
    $args['meta_query'] = $meta_query;

    $args["suppress_filters"] = TRUE;

    $query = new WP_Query($args);
    if ( $query->have_posts() ) :
        ?>
        <ul class='<?= $topclass ?>'>
        <?php
        while ( $query->have_posts() ) : $query->the_post();      
            $title = get_the_title();
            $description = get_field("synopsis");
            $shortDescription = preg_replace('/\s/', '', preg_replace('/<[^>]*>/', '', $description));
            if ($debug) { echo "== " . $title . " synopsis " . strlen($description) ; }
            if (empty($shortDescription)) {
                $rawcontent = get_the_content();
                if ($debug) { echo " rawcontent " . strlen($rawcontent); }
                $description = strip_the_description($rawcontent, $debug, $paras);
            } else {
                $description = preg_replace('/\n+/', "<br/>", $description ); 
            }
            if (!empty($description)) {
                $description = $description . "<p>&nbsp;</p>";
            }

            $link = get_page_link();
            if (get_post_format() === "link") {
                if(preg_match("@https?\://[^'\" ]+@", $description, $matches)) {
                    $link = $matches[0];
                } 
            }

            $altTitle = get_field("activity_title");
            if ($altTitle) {
                $title = $altTitle;
            }

            $subtitle = get_field("subtitle");
            $thumbnail_image = get_the_post_thumbnail(null, $imgsize);
            if (empty($subtitle) && $thumbnail_image && isset($thumbnail_image[0])) {
                $image_id = get_post_thumbnail_id();
                $image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', TRUE);
                $image_title = get_the_title($image_id);
                $subtitle = $image_alt;
            }

            $when = get_field("when");
            $expires = get_field("expires");
            $postDate = get_the_date("j M Y");
            if (ICL_LANGUAGE_CODE=="cy") {
                $postDate = str_replace(
                    ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
                    ["Ion", "Chw", "Maw", "Ebr", "Mai", "Meh", "Gor", "Aws", "Med", "Hyd", "Tac", "Rha"],
                    $postDate);
            }
            
            $eventLocation = get_field("eventLocation");

            $linkAttr = $nolink || empty($link) ? "" : "data-url='". $link . "'";
        ?>
            <li class="eventItem" <?= $linkAttr ?> data-posted="<?= $postDate ?>">
            <?php
            $nesting = 0;
            $parts = explode(" ", $template);
            for ($pi = 0; $pi<count($parts); $pi++) {
                $part = $parts[$pi];
                if (strlen($part)>0 && substr($part, 0, 1)==="{") {
                    $part = substr($part, 1);
                    echo ("<div class='eventContent'>");
                    $nesting++;
                }
                $endDiv = false;
                if (strlen($part)>0 && substr($part, -1)==="}") {
                    $part = substr($part, 0, -1);
                    $nesting--;
                    $endDiv = true;
                }
                switch ($part) {
                    case "img" : 
                        ?>
                        <?= $thumbnail_image ?>
                        <?php
                    break;
                    case "title" : ?>
                        <div class="eventTitle"><?= $title ?></div>
                        <?php
                        break;
                    case "subtitle": if ($subtitle) { ?>
                        <div class="subtitle"><?= $subtitle ?></div>
                        <?php }
                        break;
                    case "when": 
                        if ($when) {
                            ?>
                            <div class="eventDate"><?= $when ?></div>
                            <?php
                        }
                        break;
                    case "posted" :
                        if ($postDate) {
                            ?>
                            <div class="postDate"><?= $postDate ?></div>
                            <?php
                        }
                    break;
                    case "where":
                        if ($eventLocation) {
                            ?>
                            <div class="eventLocation"><?= $eventLocation ?></div>
                            <?php
                        }
                    break;
                    case "text":
                        if (!empty($description)) {
                            ?>
                            <div class="eventDescription"><div><?= $description ?></div> </div>
                            <?php 
                        }
                        break;
                }
                if ($endDiv) {
                    echo ("</div>");
                }
            }
            while ($nesting-- > 0) {
                echo ("</div>");
            }
            ?>
            </li>
        <?php
        endwhile;
        ?>
        </ul>
        <?php
    endif;
    wp_reset_postdata();
}

// Shortcode for getting activities list
function span_activities($attributes = array() ) {
    extract(shortcode_atts(array(
     'sort' => 'news', // category of activity to get
     'category' => '', // preferred to sort
     'horizontal' => 'no',
     'style' => '', // preferred to topclass
     'topclass' => '',
     'imgsize' => 'medium',
     'count' => 20,
     'order' => 'date',
     'paras' => '2',
     'nolink' => 'no',
     'parts' => 'img {title subtitle when where text}',
     'debug' => ''
    ), $attributes));

    ob_start();
    //echo "xxxx" . $debug;
    if ($horizontal != 'no') {
        echo "<div class='sa_horizontal_activities'>";
    }
    span_list_activities(($category != '' ? $category : $sort),
     "boxlist " 
     . (!empty($style) ? $style : (!empty($topclass) ? $topclass : "eventList")) 
     . ($horizontal != 'no' ? " horizontal" : ""), 
     $imgsize, $count, $order, $paras, $nolink != 'no', $parts, $debug);
     if ($horizontal != 'no') {
        echo "<div class='sa_scrollButton sa_scrollerLeft'>&nbsp;❱</div>";
        echo "<div class='sa_scrollButton sa_scrollerRight'>❰&nbsp;</div>";
        echo "</div>";
    }     
	return ob_get_clean();
}

add_shortcode("activities", "span_activities");

function span_nqscripts() {
    wp_enqueue_script("spanjs", "/wp-content/plugins/span-activities/includes/js/spanarts.js", array("jquery"), null, true);
}
//add_action( 'wp_enqueue_scripts', 'span_nqscripts' );

/* Expose menu_order for posts
 * https://wordpress.stackexchange.com/questions/91866/how-to-use-menu-order-field-for-posts
 */
add_action( 'init', 'wpse31629_init' );
function wpse31629_init()
{
	add_post_type_support( 'post', 'page-attributes' );
}

/* Allow editor to handle menu_order https://core.trac.wordpress.org/ticket/46264
*/
add_action(
    'rest_api_init',
    function() {
        register_rest_field(
            'post',
            'menu_order',
            [
                'get_callback' => function($object) {
                    if (! isset($object['menu_order'])) {
                        return 0;
                    }
                    return (int) $object['menu_order'];
                },
                'schema' => [
                    'type' => 'integer',
                ]
            ]
        );
    }
);

function custom_excerpt_length( $length ) {
    return 200;
}
add_filter( 'excerpt_length', 'custom_excerpt_length', 1999 );

  
?>