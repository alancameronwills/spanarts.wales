<?php

define('SPAN_CUT_ADMIN_OPTIONS', true);

// Set background green when editing Welsh:
add_action('admin_head', function () {
  if (apply_filters('wpml_current_language', null) == "cy") { ?>
  <style>
  body.wp-admin {background-color:#b0ffc0;}
  </style>
  <?php } ?>
  <style>
  .block-editor .editor-post-title__block .editor-post-title__input {text-transform: initial !important;}
  input::placeholder {color:#c0c0c0;}
  </style>
  <?php
});

add_action('wp_enqueue_scripts', 'span_nqthemescript');
function span_nqthemescript()
{
  wp_enqueue_script(
    "spanjs",
    "/wp-content/themes/bridge-span/js/span-arts.js",
    ["jquery"],
    null,
    true
  );
}

add_action('wp_enqueue_scripts', 'bridge_span_enqueue_styles');
function bridge_span_enqueue_styles()
{
  wp_enqueue_style(
    'bridge-span',
    get_stylesheet_uri(),
    ['bridge'],
    wp_get_theme()->get('Version')
  );
  wp_enqueue_style(
    "spanarts",
    get_stylesheet_directory_uri() . "/css/spanarts.css",
    ["bridge-stylesheet"]
  );
}

// Shortcode for Read More button
add_shortcode("readmore", "span_readmore");
function span_readmore($attributes = [])
{
  extract(
    shortcode_atts(
      [
        'url' => '/news', // relative URL of page
        'text' => 'Read more',
        'right' => 'yes',
      ],
      $attributes
    )
  );

  ob_start();

  $target = stripos($url, "http") === 0 ? " target=_blank" : "";

  if (stripos($url, "http") !== 0 && stripos($url, "/") !== 0) {
    $url = "/" . $url;
  }

  echo "<a itemprop='url' href='" .
    $url .
    "'" .
    $target .
    " class='span-button " .
    ($right != 'no' ? "right" : "") .
    "' >" .
    $text .
    "</a>";

  return ob_get_clean();
}

if (SPAN_CUT_ADMIN_OPTIONS) {
  function bridge_qode_meta_boxes_map_init()
  {
    do_action('bridge_qode_action_before_meta_boxes_map');

    //require_once( QODE_FRAMEWORK_ROOT_DIR . "/admin/meta-boxes/slides/map.php");
    //require_once( QODE_FRAMEWORK_ROOT_DIR . "/admin/meta-boxes/testimonials/map.php");
    //require_once( QODE_FRAMEWORK_ROOT_DIR . "/admin/meta-boxes/carousels/map.php");
    //require_once( QODE_FRAMEWORK_ROOT_DIR . "/admin/meta-boxes/masonry_gallery/map.php");
    //require_once( QODE_FRAMEWORK_ROOT_DIR . "/admin/meta-boxes/general/map.php");
    //require_once( QODE_FRAMEWORK_ROOT_DIR . "/admin/meta-boxes/portfolio/map.php");
    //require_once( QODE_FRAMEWORK_ROOT_DIR . "/admin/meta-boxes/post/map.php");
    require_once QODE_FRAMEWORK_ROOT_DIR .
      "/admin/meta-boxes/post/post-format-audio/map.php";
    require_once QODE_FRAMEWORK_ROOT_DIR .
      "/admin/meta-boxes/post/post-format-gallery/map.php";
    require_once QODE_FRAMEWORK_ROOT_DIR .
      "/admin/meta-boxes/post/post-format-link/map.php";
    require_once QODE_FRAMEWORK_ROOT_DIR .
      "/admin/meta-boxes/post/post-format-quote/map.php";
    require_once QODE_FRAMEWORK_ROOT_DIR .
      "/admin/meta-boxes/post/post-format-video/map.php";
    //require_once( QODE_FRAMEWORK_ROOT_DIR . "/admin/meta-boxes/header/map.php");
    //require_once( QODE_FRAMEWORK_ROOT_DIR . "/admin/meta-boxes/left-menu/map.php");
    //require_once( QODE_FRAMEWORK_ROOT_DIR . "/admin/meta-boxes/footer/map.php");
    //require_once( QODE_FRAMEWORK_ROOT_DIR . "/admin/meta-boxes/title/map.php");
    //require_once( QODE_FRAMEWORK_ROOT_DIR . "/admin/meta-boxes/content-bottom/map.php");
    //require_once( QODE_FRAMEWORK_ROOT_DIR . "/admin/meta-boxes/blog/map.php"); // pages that are of blog type
    //require_once( QODE_FRAMEWORK_ROOT_DIR . "/admin/meta-boxes/sidebar/map.php");  // sidebar widget layout
    //require_once( QODE_FRAMEWORK_ROOT_DIR . "/admin/meta-boxes/seo/map.php");

    do_action('bridge_qode_action_meta_boxes_map');
  }

  add_action('init', 'bridge_qode_meta_boxes_map_init');

  function bridge_qode_map_page_meta_fields()
  {
  }

  if (!function_exists('bridge_span_theme_setup')) {
    /**
     * Function that adds various features to theme. Also defines image sizes that are used in a theme
     */
    function bridge_span_theme_setup()
    {
      //add post formats support
      add_theme_support('post-formats', ['project']); // array('gallery', 'link', 'quote', 'video', 'audio'));
    }
    add_action('after_setup_theme', 'bridge_qode_theme_setup');
  }
}

// To modify on save, add:
// if ($key == "video_format_link") $value = span_extract_youtube_id($_POST[$key]); else
// at or near line 429 of bridge/framework/qode-framework.php
function span_extract_youtube_id($share_url)
{
  if (!preg_match('#/#', $share_url)) {
    return preg_replace('/[?].*/', "", $share_url);
  }
  if (preg_match('#youtube.com/watch\?#', $share_url)) {
    $matches = [];
    preg_match('/v=([^&]+)/', $share_url, $matches);
    if (count($matches) > 1) {
      return $matches[1];
    } else {
      return $share_url;
    }
  } elseif (preg_match('/youtu/', $share_url)) {
    return preg_replace('/[?].*/', "", preg_replace('#^.*/#', "", $share_url));
  } elseif (preg_match('/vimeo/', $share_url)) {
    $matches = [];
    preg_match("#/([0-9]+)#", $share_url, $matches);
    if (count($matches) > 1) {
      return $matches[1];
    } else {
      return $share_url;
    }
  } else {
    return $share_url;
  }
}

/*
 * Filter all output to fix stage-span-arts to span-arts
 */

add_action("init", "span_process_post");

function span_process_post()
{
  ob_start();
}
add_action(
  "shutdown",
  function () {
    $final = "";
    $levels = ob_get_level();
    for ($i = 0; $i < $levels; $i++) {
      $final .= ob_get_clean();
    }
    echo apply_filters("final_output", $final);
  },
  0
);

add_filter("final_output", function ($output) {
	$fulltext = $output;
  return str_replace(["stage-span-arts.org.uk","STAGE-span-arts.org.uk"], ["span-arts.org.uk","span-arts.org.uk"], $fulltext);
});

?>
