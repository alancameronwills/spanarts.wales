<?php

/** Date string in Welsh or English */
function span_showDateToString($date, $cymraeg = false, $omitTime = false)
{
  $dateString = "";
  if ($cymraeg) {
    $dayOfWeek = intval(date("w", $date));
    $dayOfMonth = intval(date("j", $date));
    $monthIndex = date("n", $date);
    $dayName = /*"Dydd " .*/ ["Sul", "Llun", "Mawrth", "Mercher", "Iau", "Gwener", "Sadwrn"][$dayOfWeek];
    $monthName = ["", "Ion", "Chw", "Maw", "Ebr", "Mai", "Meh", "Gor", "Aws", "Med", "Hyd", "Tac", "Rha"][$monthIndex];
    $dateString = $dayName . " " . $dayOfMonth . " " . $monthName 
        . ($omitTime ? "" : " " . date('Y H:i', $date));
  } else {
    if ($omitTime) {
      $dateString = date('D jS M', $date);
    } else {
      $dateString = date('D jS M Y H:i', $date);
    }
  }
  return $dateString;
}

/** Remove links and formatting for dropdown */
function span_strip_the_description($d, $paras, $debug = false)
{
  $d = preg_replace(
    '/\[iframe.*?\]/',
    "",
    preg_replace('/<iframe .*<\/iframe>/', "", $d)
  );
  $d = preg_replace("~\[.*?\]~s", '', $d); // strip_shortcodes is no good, removes all the content
  $d = preg_replace('#</div>|</h.>|</p>|<br.*?>#', "¬¬¬", $d);
  $d = preg_replace("/<.*?>/", "", $d);
  $d = preg_replace('/&nbsp;/', " ", $d);
  $d = preg_replace('/^[\s¬]+/', '', $d);
  $d = preg_replace('/[\s¬]+$/', '', $d);
  $d = preg_replace('#http.?://[^\'"\s]*#', '', $d);
  if (strlen($d) > 1000) {
    $d = substr($d, 0, 1000);
  }
  // return preg_replace('/(¬¬¬\s*)+/', "<br/>", $d );
  return implode(
    "<br/>",
    array_slice(preg_split('/(¬¬¬\s*)+/', $d), 0, $paras)
  );
}


function span_get_activities(
  $category,
  $imgsize = 'medium',
  $count = 20,
  $order = '',
  $paras = 2,
  $nolink = false,
  $debug = false
) {
  $activities = [];
  $args = [
    'category_name' => $category,
    'tax_query' => [
      [
        'taxonomy' => 'category',
        'field' => 'slug',
        'terms' => 'cymraeg',
        'operator' => 'NOT IN',
      ],
    ],
  ];
  if (!empty($order)) {
    $args['orderby'] = $order;
    if ($order == "date" || $order == "modified" || $order == "menu_order") {
      $args['order'] = "DESC";
    } else {
      $args['order'] = "ASC";
    }
  }
  if ($count) {
    $args['posts_per_page'] = $count;
  }

  // Check expiry date
  $meta_query = [
    'relation' => 'OR',
    [
      'key' => 'expires',
      'compare' => 'NOT EXISTS',
    ],
    [
      'key' => 'expires',
      'compare' => '=',
      'value' => '',
    ],
    [
      'key' => 'expires',
      'value' => date('Y-m-d'),
      'compare' => '>=',
      'type' => 'DATE',
    ],
  ];
  $args['meta_query'] = $meta_query;

  $args["suppress_filters"] = true;

  $query = new WP_Query($args);
  if ($query->have_posts()):
    while ($query->have_posts()):
      $query->the_post();
      $item = [];
      $item['title'] = get_the_title();
      $item['description'] = get_field("synopsis");
      $item['shortDescription'] = preg_replace(
        '/\s/',
        '',
        preg_replace('/<[^>]*>/', '', $item['description'])
      );
      if ($debug) {
        echo "== " .
          $item['title'] .
          " synopsis " .
          strlen($item['description']);
      }
      if (empty($item['shortDescription'])) {
        $rawcontent = get_the_content();
        if ($debug) {
          echo " rawcontent " . strlen($rawcontent);
        }
        $item['description'] = span_strip_the_description(
          $rawcontent,
          $paras,
          $debug
        );
      } else {
        $item['description'] = preg_replace(
          '/\n+/',
          "<br/>",
          $item['description']
        );
      }
      if (!empty($item['description'])) {
        $item['description'] = $item['description'] . "<p>&nbsp;</p>";
      }

      $item['link'] = get_page_link();
      if (get_post_format() === "link") {
        if (
          preg_match("@https?\://[^'\" ]+@", $item['description'], $matches)
        ) {
          $item['link'] = $matches[0];
        }
      }

      $item['altTitle'] = get_field("activity_title");
      if ($item['altTitle']) {
        $item['title'] = $item['altTitle'];
      }

      $item['subtitle'] = get_field("subtitle");
      $item['thumbnail_image'] = get_the_post_thumbnail(null, $imgsize);
      if (
        empty($item['subtitle']) &&
        $item['thumbnail_image'] &&
        isset($item['thumbnail_image'][0])
      ) {
        $item['image_id'] = get_post_thumbnail_id();
        $item['image_alt'] = get_post_meta(
          $item['image_id'],
          '_wp_attachment_image_alt',
          true
        );
        $item['image_title'] = get_the_title($item['image_id']);
        $item['subtitle'] = $item['image_alt'];
      }

      $item['when'] = get_field("when");
      $item['dtstart'] = span_timeFromString(get_field("dtstart"));
      $item['expires'] = get_field("expires");
      $item['postDate'] = get_the_date("j M Y");
      if (ICL_LANGUAGE_CODE == "cy") {
        $item['postDate'] = str_replace(
          [
            "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"
          ],
          [
            "Ion", "Chw", "Maw", "Ebr", "Mai", "Meh", "Gor", "Aws", "Med", "Hyd", "Tac", "Rha"
          ],
          $item['postDate']
        );
      }

      $item['eventLocation'] = get_field("eventLocation");
      $activities[] = $item;
    endwhile;
  endif;
  wp_reset_postdata();
  return $activities;
}

function span_print_activities(
  $activities,
  $topclass,
  $horizontal,
  $cymraeg = false,
  $id = "",
  $imgsize = "medium",
  $template = "img {title subtitle when where text}",
  $debug = false
) {
    $locale = "?locale=" . ($cymraeg ? "cy-CY" : "en-GB");
    
  ob_start();
  //echo "xxxx" . $debug;
  if ($horizontal) {
    echo "<div class='sa_horizontal_activities'>";
  }
  ?>  <ul <?= ($id ? 'id="' . $id . '" ': '') ?>  class='<?= "boxlist " . $topclass . ($horizontal ? " horizontal" : "") ?>'> <?php
    if (count($activities) > 0):
    foreach ($activities as $item) {
        $tags = count($item['tags']) > 0 ? " " . implode(' ', $item['tags']) : "";
        $dataUrl =
            $nolink || empty($item['link'])
            ? ""
            : "data-url='" . $item['link'] . $locale . "'";
        ?>
            <li class="eventItem <?= htmlentities($tags)?>" 
             <?= $dataUrl ?>
             data-posted="<?= $item['postDate'] ?>"
            >
        <?php
        $nesting = 0;
        $parts = explode(" ", $template);
        for ($pi = 0; $pi < count($parts); $pi++) {
            $part = $parts[$pi];
            if (strlen($part) > 0 && substr($part, 0, 1) === "{") {
                $part = substr($part, 1);
                echo "<div class='eventContent'>";
                $nesting++;
              }
              $endDiv = false;
              if (strlen($part) > 0 && substr($part, -1) === "}") {
                $part = substr($part, 0, -1);
                $nesting--;
                $endDiv = true;
              }
              switch ($part) { 
                case "img": 
                    if ($item['images']) {
                    ?>
                        <img class="eventImage" src="<?= $item['images'][0][$imgsize] ?>" alt="<?= htmlentities($item['title']) ?>"/>
                    <?php } elseif ($item['thumbnail_image']) { ?>
                        <?= $item['thumbnail_image'] ?>
                    <?php 
                    }
                break;
                case "title":
                    ?>
                        <div class="eventTitle"><?= $item['title'] ?></div>
                    <?php 
                break;
                case "subtitle":
                    if ($item['subtitle']) { 
                        ?>
                            <div class="subtitle"><?= $item['subtitle'] ?></div>
                        <?php 
                    }
                break;
                case "when":
                    if ($item['when']) { 
                        ?>
                            <div class="eventDate"><?= $item['when'] ?></div>
                        <?php 
                    } elseif (isset($item['dtstart']) && $item['dtstart']) {
                        $date = "";
                        if (isset($item['dtend'])) {
                            $date =
                                span_showDateToString($item['dtstart'], $cymraeg, true) .
                                " - " .
                                span_showDateToString($item['dtend'], $cymraeg, true);
                        } else {
                            $date = span_showDateToString($item['dtstart'], $cymraeg);
                        }
                        ?>
                            <div class="eventDate"><?= $date ?></div>
                        <?php
                    }
                break;
                case "posted":
                    if ($item['postDate']) { 
                        ?>
                            <div class="postDate"><?= $item['postDate'] ?></div>
                        <?php 
                    }
                break;
                case "where":
                    if ($item['eventLocation']) { 
                        ?>
                            <div class="eventLocation"><?= $item['eventLocation'] ?></div>
                        <?php 
                    } elseif ($item['venue']) { 
                        ?>
                            <div class="eventLocation"><?= $item['venue'] ?></div>
                        <?php 
                    }
                break;
                case "text":
                    if (!empty($item['description'])) { 
                        ?>
                            <div class="eventDescription"><div><?= $item['description'] ?></div> </div>
                        <?php 
                    }
                break;
              }
              if ($endDiv) {
                echo "</div>";
              }
            }
            while ($nesting-- > 0) {
              echo "</div>";
            }
            ?>
            </li>
        <?php 
        }
  endif;
  
if ($horizontal) {
    echo "<div class='sa_scrollButton sa_scrollerLeft'>&nbsp;❱</div>";
    echo "<div class='sa_scrollButton sa_scrollerRight'>❰&nbsp;</div>";
    echo "</div>";
  }
  ?></ul><?php 
  return ob_get_clean();
}


/** Generate HTML for a list of posts
 * @param $category Get posts in or under this category
 * 
 * @param $topclass Class for the outer div
 * @param $imgsize 'thumbnail' | 'medium' | 'full'
 * @param $count Number of items to generate
 * @param $order title | menu_order | date | modified
 * @param $paras Limit count of paragraphs to show in synopsis
 * @param $nolink Don't provide a link
 * @param $template Sequence of words chosen from: title subtitle img where when posted text. { } nests in a div.
 */


// Shortcode for getting activities list
function span_activities_shortcode($attributes = [])
{
  extract(
    shortcode_atts(
      [
        'sort' => 'news', // category of activity to get
        'category' => '', // preferred to sort
        'horizontal' => 'no',
        'style' => 'eventList', // preferred to topclass
        'topclass' => '',
        'imgsize' => 'medium',
        'count' => 20,
        'order' => 'date',
        'paras' => '2',
        'nolink' => 'no',
        'parts' => 'img {title subtitle when where text}',
        'debug' => '',
      ],
      $attributes
    )
  );
  $activities = span_get_activities((!empty($category) ? $category : $sort), $imgsize, $count, $order, $paras, $nolink != 'no', $debug);
  return span_print_activities(
    $activities, 
    (!empty($topclass) ? $topclass : $style), 
    $horizontal != 'no', 
    $cymraeg, 
    "",
    $imgsize,
    $parts, 
    $debug);
}

add_shortcode("span-activities", "span_activities_shortcode");

/** Shortcode for getting TicketSolve events list.
 */
function span_ticketsolve_display($attributes = [])
{
  extract(
    shortcode_atts(
      [
        'style' => 'eventList',
        'topclass' => '', // Alternative to style
        'horizontal' => 'no', // Single sliding row
        'cymraeg' => 'no',
        'book' => 'yes', // merge in posts in the "book" category
        'count' => 0, // 0 == return all available
        'paras' => 2, // length of excerpt extracted from description
        'parts' => "img {title subtitle when where text}",
        'imgsize' => 'medium'
      ],
      $attributes
    )
  );

  $list = new Span_TicketSolve("span-arts", "", "", ($count ? $count : 20), 120, ($cymraeg != 'no'));
  $showList = $list->get_shows($count, $paras);

  if ($book) {
    $eventList = span_get_activities("book");
    $showList = array_merge($eventList, $showList);
    usort($showList, "span_cmpByDate");
  }

  if (count($showList) > 0) {
    return span_print_activities(
        $showList, 
        (!empty($topclass) ? $topclass : $style), 
        $horizontal != 'no', 
        $cymraeg != 'no', 
        "upcoming",
        $imgsize,
        $parts);
  } else {    
    return "<p>More shows coming soon...</p><p><a href='https://span-arts.ticketsolve.com/sign-up'>Sign up for our Newsletter</a></p>";
  }
}

add_shortcode("span-ticketsolve", "span_ticketsolve_display");
?>
