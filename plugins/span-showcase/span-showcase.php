<?php
/**
 * @package SpanShowcase
 * @version 1.1
 */
/*
Plugin Name: Span showcase
Description: Retrieves old blogs and shows in categories.
Author: Alan Wills
Version: 1.1
*/


function myScriptContent($f) {
    return file_get_contents(str_replace("/", "\\", plugin_dir_path(__FILE__) . $f));
}

function spanShowCasePage() {
    $spanShowcaseDiv = "showcaseDiv";
?>

<!-- ---- showcase.htm ---- -->
<!-- I prefer styles in the doc as it localizes the style names to the page body
    and avoids loading unused stuff on other pages -->

<style>
<?= myScriptContent("css/span-showcase.css") ?>
</style>

<script>
<?= myScriptContent("js/span-showcase.js") ?>


jQuery(function () {
    createSpanShowcase("<?= $spanShowcaseDiv ?>", "<?= plugins_url("",  __FILE__) ?>", "https://www.span-arts.org.uk/news/");
});
</script>


<section>
    <div id="<?= $spanShowcaseDiv ?>"></div>
    <div class="topBanner">
        <img id="defaultCollapse" src="<?= plugins_url('img/collapseall.png', __FILE__) ?>" style="height:40px;width: auto;" onclick="collapseAll()">
    </div>
</section>

<?php 
}

/*
function span_showcase_queue_scripts () {
    wp_register_script("span_showcase", plugins_url("js/span-showcase.js", __FILE__), array("jquery"));
    wp_enqueue_script("span_showcase");
    wp_register_style("span_showcase", plugins_url("css/span-showcase.css", __FILE__));
    wp_enqueue_style("span_showcase");
}
add_action("wp_enqueue_scripts", "span_showcase_queue_scripts");
*/

// Shortcode for getting activities list
function span_showcase($attributes = array() ) {
	ob_start();

    spanShowCasePage();

	return ob_get_clean();
}
add_shortcode("spanshowcase", "span_showcase");

?>