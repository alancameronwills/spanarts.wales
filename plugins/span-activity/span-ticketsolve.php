<?php

class Span_TicketSolve
{
  private $source;
  private $tag;
  private $category;
  private $count;
  private $interval;
  private $cache_name = 'span_ts_cache';
  private $cache_entry = 'shows';

  /**
   * @param {string} account TicketSolve URL prefix
   * @param {string} tag Space-separated list of show tags to filter TS query
   * @param {string} category Space-separated list of show categories to filter TS query
   * @param {int} interval Seconds max age to cache TS query results
   * @param {bool} cymraeg Link to CY version of TS show
   */
  function __construct($account, $tag, $category, $count, $interval, $cymraeg)
  {
    $this->tag = $tag;
    $this->category = $category;
	  $this->count = $count;
    $this->interval = $interval;
    $this->cymraeg = $cymraeg;

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

  /** Retrieve shows from TicketSolve 
   * @param {int} $maxcount 0 or max length of array to return
   * @param {int} $paras max number of paragraphs to show in extract 
   * @return {Array()} Shows
   */
  public function get_shows($maxcount=0, $paras=2)
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

      $shows = []; // result
      $limit = $showlist->length ; //> $this->count ? $this->count : $showlist->length;
      for ($i = 0; $i < $limit; $i++) {
        $item = $this->extract_show($xpath, $showlist->item($i), $paras);

		    // If same show in multiple venues, just omit venue:
		    if (isset($shows[$item['id']])) {
			    if ($shows[$item['id']]['venue'] != $item['venue']) {
				    $item['venue'] = "";
        		$shows[$item['id']] = $item;
			    }
		    } else {
        	$shows[$item['id']] = $item;
		    }
	    }
	    // sort by start date
	    usort($shows, "span_cmpByDate");
		
		  // Try to borrow cached English descriptions where Welsh unavailable
		  if ($this->cymraeg && count($cache['shows'])>0) {
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

  /** Get show object from XML */
  private function extract_show($xpath, $show, $paras=2) {
    // result:
    $item = [
      "id" => "",
      "title" => "",
      "description" => "",
      "url" => "",
      "eventLocation" => "",
      "images" => [],
      "tags" => [],
      "category" => "",
      "dtstart" => 0,
      "dtend" => 0
    ];

    $item['id'] = trim($xpath->query(".", $show)->item(0)->getAttribute("id"));
    $item['title'] = trim($xpath->query("./name", $show)->item(0)->nodeValue);
    $item['description'] = span_strip_the_description(
      $xpath->query("./description", $show)->item(0)->nodeValue, 
      $paras
    );
    $item['link'] = trim($xpath->query("./url", $show)->item(0)->nodeValue);
    $item['venue'] = trim($xpath->query("../../name", $show)->item(0)->nodeValue);

    foreach ($xpath->query("./images/image", $show) as $img_group) {
      $img = [];
      foreach ($xpath->query("./url", $img_group) as $img_ele) {
        $img[$img_ele->getAttribute("size")] = $img_ele->nodeValue;
      }
      $item['images'][] = $img;
    }

    foreach ($xpath->query("./tags/tag", $show) as $tag_ele) {
      $item['tags'][] = trim($tag_ele->nodeValue);
    }
    $item['category'] = trim($xpath->query("./event_category", $show)->item(0)->nodeValue);

    $eventList = $xpath->query("./events/event", $show);
    $eventPath = $eventList->item(0);
    //$event['id'] = trim($eventPath->query("@id", $eventPath)->item(0)->nodeValue);
    if ($eventList->length > 1) {
      // Not guaranteed in time order:
      foreach ($eventList as $eventPath) {
        $eventTime = span_timeFromString(
              $xpath->query("./date_time_iso", $eventPath)->item(0)->nodeValue);
        if ($item['dtstart']==0 || $eventTime < $item['dtstart']) {
          $item['dtstart'] = $eventTime;
        }
        if ($item['dtend']==0 || $eventTime > $item['dtend']) {
          $item['dtend'] = $eventTime;
        }
      }
    } else {
      $item['dtstart'] = span_timeFromString(
          $xpath->query("./date_time_iso", $eventPath)->item(0)->nodeValue);
      $item['dtend'] = null;
    }
    return $item;
  }

  public function get_from_XML($url)
  {
    $doc = new DOMDocument();
    $doc->load($url, LIBXML_NOCDATA);
    return $doc;
  }

  /*
  public function get_event_from_XML($event_xml_url)
  {
    $doc = $this->get_from_XML($event_xml_url);

    $xpath = new DOMXpath($doc);

    $event['id'] = trim($xpath->query("@id")->item(0)->nodeValue);
    $event['dtstart'] = span_timeFromString($xpath->query("//date_time")->item(0)->nodeValue);
    return $event;
  }
  */
}

function span_timeFromString($s)
{
  return strtotime(str_replace("/", "-", substr(trim($s), 0, 19)));
}

function span_cmpByDate($a, $b)
{
  $an = ($a['dtstart'] ? $a['dtstart'] : 0);
  $bn = ($b['dtstart'] ? $b['dtstart'] : 0);
  return $an == $bn ? 0 : $an < $bn ? -1 : 1;
}
