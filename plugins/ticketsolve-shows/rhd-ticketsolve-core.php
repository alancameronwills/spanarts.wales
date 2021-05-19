<?php
// https://span-arts.ticketsolve.com/shows.xml



function rhdt_strip_the_description ($d, $paras=2) 
{
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

class RHD_TS_UpcomingShows
{
  private $source;
  private $tag;
  private $category;
  private $count;
  private $interval;
  private $cache_name = 'rhd_ts_cache';
  private $cache_entry = 'shows';

  function __construct($account, $tag, $category, $count, $interval, $cymraeg, $compact)
  {
    $this->tag = $tag;
    $this->category = $category;
	  $this->count = $count;
    $this->interval = $interval;
    $this->cymraeg = $cymraeg;
    $this->compact = $compact;

    $params = [];

    if ($tag != '') {
      $params['tag'] = $tag;
    }
    if ($category != '') {
      $params['category'] = $category;
    }
    if ($cymraeg) {
      $params["locale"] = "cy-CY";
      $this->cache_entry = 'showscy';
    } 

    $qs = count($params) > 0 ? '?' . http_build_query($params, '', '&') : '';

    $this->source = 'https://' . $account . '.ticketsolve.com/shows.xml' . $qs;
  }

  public function get_shows($maxcount=0)
  {
    // get cached version from wp_options to avoid pulling & parsing large XML file every time
    $cache = json_decode(get_option($this->cache_name), true);
    /*if (time() < $cache['timestamp'] + $this->interval && count($cache[$this->cache_entry])>0) {
		  $shows = $cache[$this->cache_entry];
      	// echo ("\n<!-- TS cache " . $this->cache_entry . " -->\n");
	  } else */ { 
      // no good cache available, so create from scratch
      // echo ("\n<!-- TS source ".$this->source." -->\n");
      $doc = $this->get_from_XML($this->source);

      $xpath = new DOMXpath($doc);

      // find all shows for all venues
      $showlist = $xpath->query('//venues/venue/shows/show');
      //echo ("[". $showlist->length . "]" );

      // make an array with simple data for output
      $shows = [];

      // use the smaller of [total show count] or [requested limit]
      $limit = $showlist->length ; //> $this->count ? $this->count : $showlist->length;


      // only process the first n shows according to limit above, NB this is in document or date order?
      for ($i = 0; $i < $limit; $i++) {
        $show = $showlist->item($i);

        // basic template
        $thisshow = [
          "id" => "",
          "name" => "",
          "description" => "",
          "url" => "",
          "venue" => "",
          "images" => [],
          "tags" => [],
          "category" => "",
          "dtstart" => 0,
			"dtend" => 0
        ];

        // show ID
        $thisshow['id'] = trim(
          $xpath
            ->query(".", $show)
            ->item(0)
            ->getAttribute("id")
        );

        // show title
        $thisshow['name'] = trim(
          $xpath->query("./name", $show)->item(0)->nodeValue
        );

        // show description
        $thisshow['description'] = trim(
          $xpath->query("./description", $show)->item(0)->nodeValue
        );

        // booking page
        $thisshow['url'] = trim(
          $xpath->query("./url", $show)->item(0)->nodeValue
        );

        // show venue
        $thisshow['venue'] = trim(
          $xpath->query("../../name", $show)->item(0)->nodeValue
        );

        // image URLs
        foreach ($xpath->query("./images/image", $show) as $img_group) {
          $img = [];
          foreach ($xpath->query("./url", $img_group) as $img_ele) {
            $img[$img_ele->getAttribute("size")] = $img_ele->nodeValue;
          }
          $thisshow['images'][] = $img;
        }

        // show tags
        foreach ($xpath->query("./tags/tag", $show) as $tag_ele) {
          $thisshow['tags'][] = trim($tag_ele->nodeValue);
        }

        // show category
        $thisshow['category'] = trim(
          $xpath->query("./event_category", $show)->item(0)->nodeValue
        );

        $eventList = $xpath->query("./events/event", $show);
        $eventPath = $eventList->item(0);
        //$event['id'] = trim($eventPath->query("@id", $eventPath)->item(0)->nodeValue);
        if ($eventList->length > 1) {
			// Not guaranteed in time order:
			foreach ($eventList as $eventPath) {
				$eventTime = strtotime(
           			 trim($xpath->query("./date_time_iso", $eventPath)->item(0)->nodeValue));
				if ($thisshow['dtstart']==0 || $eventTime < $thisshow['dtstart']) {
					$thisshow['dtstart'] = $eventTime;
				}
				if ($thisshow['dtend']==0 || $eventTime > $thisshow['dtend']) {
					$thisshow['dtend'] = $eventTime;
				}
			}
        } else {
        	$thisshow['dtstart'] = strtotime(
          		trim($xpath->query("./date_time_iso", $eventPath)->item(0)->nodeValue));
          	$thisshow['dtend'] = null;
        }

        /*
        // get event data only for first event of first 10 shows max
        // this requires pulling a separate XML file per show, so keep it reasonable
        if ($i < 10) {
          $thisshow['event'] = $this->get_event_from_XML(
            trim(
              $xpath->query("./events/event/feed/url", $show)->item(0)->nodeValue
            )
          );
        }
        */
		// If same show in multiple venues, just omit venue:
		if (isset($shows[$thisshow['id']])) {
			if ($shows[$thisshow['id']]['venue'] != $thisshow['venue']) {
				$thisshow['venue'] = "";
        		$shows[$thisshow['id']] = $thisshow;
			}
		} else {
        	$shows[$thisshow['id']] = $thisshow;
		}
	  }
	  // sort by start date
	  usort($shows, "cmpByDate");
		
		// Try to borrow cached English descriptions where Welsh unavailable
		if (cymraeg && count($cache['shows'])>0) {
			// echo ("\n<!-- TS also English -->\n");
			$showsEN = $cache['shows'];
			for ($i = 0; $i < count($shows); $i++) {
        		if(strlen($shows[$i]["description"])==0 && $i<count($showsEN) 
					&& $shows[$i]["id"] == $showsEN[$i]["id"]) {
					$shows[$i]["description"] = $showsEN[$i]["description"];
				};
			}
		}
	  

      // cache results
      update_option(
        $this->cache_name,
        json_encode(['timestamp' => time(), $this->cache_entry => $shows])
      ); 
	} 
	
	if (count($shows) > $maxcount && $maxcount > 0) {
		return array_slice($shows, 0, $maxcount);
	} else {
		return $shows;
	}
  }

  public function get_from_XML($url)
  {
    $doc = new DOMDocument();
    $doc->load($url, LIBXML_NOCDATA);

    return $doc;
  }

  public function get_event_from_XML($event_xml_url)
  {
    $doc = $this->get_from_XML($event_xml_url);

    $xpath = new DOMXpath($doc);

    $event['id'] = trim($xpath->query("@id")->item(0)->nodeValue);
    $event['dtstart'] = strtotime(
      trim($xpath->query("//date_time")->item(0)->nodeValue)
    );
    return $event;
  }
}



function cmpByDate($a,$b) {
	if ($a['dtstart'] == $b['dtstart']) return 0;
	return $a['dtstart'] < $b['dtstart'] ? -1 : 1;
}


function rhd_upcomingshows(
  $topclass = "eventList",
  $imgsize = "medium",
  $maxcount = 0,
  $cymraeg = false,
  $paras = 2,
  $compact = false
) {
  $params = get_option("rhd_ts_options");

  $list = new RHD_TS_UpcomingShows(
    "span-arts", //$params['subdomain'],
    $params['tag'],
	  $params['category'],
	  $params['count'],
    $params['interval'],
    $cymraeg,
    $compact
  );
  $ishorizontal = strpos($topclass, 'horizontal') !== false;

  $showlist = $list->get_shows($maxcount);

  if (count($showlist) > 0) {
    if ($ishorizontal) {
      ?>
      <div class='sa_horizontal_activities'>
      <?php
    }
	?>
		<ul id="upcoming" class="<?= $topclass ?>">
  <?php 
  $locale = "?locale=" . ($cymraeg ? "cy-CY" : "en-GB");
	foreach ($showlist as $show) {

    	$tags = count($show['tags']) > 0 ? " " . implode(' ', $show['tags']) : "";
      $date = "";
      if (isset($show['dtstart'])) {
       if (isset($show['dtend'])) {
         $date = showDateToString($show['dtstart'], $cymraeg, true) 
          . " - " .  showDateToString($show['dtend'], $cymraeg, true) ;
        } else {
          $date = showDateToString($show['dtstart'], $cymraeg);
        }
      }
    ?>
		<li id="show_<?= $show['id'] ?>" class="eventItem <?= htmlentities($tags) ?>" data-url="<?= $show['url'] . $locale?>">
				<img class="eventImage" src="<?= $show['images'][0][$imgsize] ?>" alt="<?= htmlentities($show['name']) ?>"/>
        <div class="eventContent">
          <div class="eventTitle"><?= htmlentities($show['name']) ?></div>
				  <div class="eventDate"><?= $date ?></div> 
          <div class="eventLocation "><?= htmlentities($show['venue']) ?></div>
				  <div class="eventDescription"><?= rhdt_strip_the_description($show['description'], $paras) ?></div>
          <!-- <div class="eventBooking"><a href="/about/ticket-information/">Booking info</a></div> -->
        </div>
		</li>
		<?php
 	} ?>
		</ul>
    <?php
       if ($ishorizontal) { ?>
        <div class='sa_scrollButton sa_scrollerLeft'>&nbsp;❱</div>
        <div class='sa_scrollButton sa_scrollerRight'>❰&nbsp;</div>
        </div>
      <?php
    } 
  } else { ?>
<p>More shows coming soon...</p><p><a href="https://span-arts.ticketsolve.com/sign-up">Sign up for our Newsletter</a></p>
		<?php }
}

function showDateToString($date, $cymraeg=false, $short=false) {        
  $dateString = "";
    if ($cymraeg) {
      $dayOfWeek = intval(date("w", $date));
      $dayOfMonth = intval(date("j", $date));
      $monthIndex= date("n", $date);
      $dayName = /*"Dydd " .*/ ["Sul", "Llun", "Mawrth", "Mercher",  "Iau", "Gwener", "Sadwrn"][$dayOfWeek];
      $monthName = ["", "Ion", "Chw", "Maw", "Ebr", "Mai", "Meh", "Gor", "Aws", "Med", "Hyd", "Tac", "Rha"][$monthIndex];
      $dateString = $dayName . " " . $dayOfMonth . " " . $monthName . 
        ($short ? "" : " " . date('Y H:i', $date));
    }
    else {
      if ($short) $dateString = date('D jS M', $date);
      else $dateString = date('D jS M Y H:i', $date);
    }
  return $dateString;
}

// Shortcode for getting TicketSolve events list
function rhd_display($attributes = [])
{
  extract(
    shortcode_atts(
      [
        'topclass' => 'eventList',
        'imgsize' => 'medium',
        'cymraeg' => 'no',
        'compact' => 'no',
        'count' => 0, // 0 == return max number
        'paras' => 2
      ],
      $attributes
    )
  );

  ob_start();
  ?>
	<?php
      rhd_upcomingshows("boxlist " . $topclass, $imgsize, $count, $cymraeg!='no', $paras, $compact!='no');
	return ob_get_clean();
}

add_shortcode("ticketsolve", "rhd_display");

?>
