<?php
/*
Plugin Name: Span Category Menu
Description: In a top menu item Description, put "autofill/project" to automatically populate submenu. Can use other category names and optionally append cymraeg. Or in a submenu label, put [category-name]
Version: 1.1
Author: Alan Cameron Wills
License: GPL2

Copyright 2020  Alan Cameron Wills  (email : alan@cameronwills.org)

*/

/**
 * Add our menu items to existing WP menu.
 * Replace an existing menu with same name, or add to end if there isn't one.
 * @pre Our first item's title is the name of the menu we're replacing.
 * @pre The existing item we're replacing doesn't have more than one level of submenu.
 * @pre Language of replacement menu is that of replacement
 * @pre Existing and new items are in the order required in the menu
 *
 * @param $items The items already in the menu
 * @param $menu The slug of the menu we're talking about. (Not its location.)
 *
 * @return mixed Items in the revised menu
 */
function spanarts_wp_get_nav_menu_items($items, $menu)
{
  if (is_admin()) {
    return $items;
  }

  
  $revisedMenu = [];

  foreach ($items as $item) {
    echo "<!-- Span category menu: " . $item->title . "-->";
    if (substr($item->title, 0, 1) == '*') {
      $category = trim(substr($item->title, 1));
      $parentId = $item->menu_item_parent;
      echo "<!-- Span category menu: category " . $item->title . "-->";
      spanarts_insertCategoryItems($category, $parentId, $revisedMenu, "");
    } else {
      $revisedMenu[] = $item;
      /*
      $description = trim($item->description ?? "");
      //$description = preg_replace('/\s\s+/g', " ", $description);
      if ($description && strpos($description, "autofill ") == 0) {
        echo "<!-- Span Category Menu: in menu '" . $item->title . "' found Description: " . $description . " -->";
        $parentId = $item->ID;
        $bits = explode("/", $description);
        $category = trim($bits[1]) ?? "project";
        $cymraeg = strtolower(trim($bits[2])) ?? "en";

        //spanarts_insertCategoryItems($category, $parentId, $revisedMenu);
        */
      }
      
   }
  

  $revisedMenu = spanarts_fix_menu_orders($revisedMenu);
  return $revisedMenu;
}

function spanarts_insertCategoryItems($category, $parentId, &$revisedMenu, $cymraeg)
{
  
  $newItems = wp_list_sort(spanarts_list_items($category, $cymraeg), "title");

  foreach ($newItems as $new_item) {
    $new_object = spanarts_make_item_obj($new_item, $menu, $parentId);
    echo "<!-- Span category menu: new item " . $new_object->title . "-->";
    $revisedMenu[] = $new_object;
  }
  
}

function spanarts_list_items($category, $cymraeg = false, $count = 20)
{
  $operator = $cymraeg && strpos($cymraeg, "cy") === 0 ? 'IN' : 'NOT IN';

  $args = [
    'category_name' => $category,
    'orderby' => "DESC",
    'tax_query' => [
      [
        'taxonomy' => 'category',
        'field' => 'slug',
        'terms' => 'cymraeg',
        'operator' => $operator,
      ],
    ],
  ];
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

  $query = new WP_Query($args);
  $items = [];
  if ($query->have_posts()) {
    $i = 0;
    while ($query->have_posts()) {
      $query->the_post();
      $title = get_the_title();
      $altTitle = get_field("activity_title");
      if ($altTitle) {
        $title = $altTitle;
      }
      $link = get_permalink(); // get_page_link();
      $items[$i++] = [
        "title" => $title,
        "link" => $link,
        "ID" => get_the_ID(),
        "type" => get_post_type(),
      ];
    }
  }
  return $items;
}

$spanarts_menuItemCount = 0;

/**
 * Create a menu item for a post
 *
 * @param array $item
 *
 * @return mixed
 */
function spanarts_make_item_obj($item, $menu_slug, $parent_id)
{
  // generic object made to look like a post object
  $item_obj = new stdClass();
  $item_obj->ID = 1000000 + $parent_id + $GLOBALS['spanarts_menuItemCount']++;
  $item_obj->title = $item['title'];
  $item_obj->url = $item['link'];
  $item_obj->menu_order = 0;
  $item_obj->menu_item_parent = $parent_id;
  $item_obj->post_parent = '';

  // menu specific properties
  $item_obj->db_id = $item_obj->ID;
  $item_obj->type = '';
  $item_obj->object = '';
  $item_obj->object_id = '';

  // output attributes
  $item_obj->classes = [
    "menu-item menu-item-type-post_type",
    "menu-item-object-" . $item['type'],
  ];
  $item_obj->target = '';
  $item_obj->attr_title = '';
  $item_obj->description = '';
  $item_obj->xfn = '';
  $item_obj->status = '';

  return $item_obj;
}

/**
 * Menu items with the same menu_order property cause a conflict. This
 * method attempts to provide each menu item with its own unique order value.
 * Thanks @codepuncher
 *
 * @param $items
 *
 * @return mixed
 */
function spanarts_fix_menu_orders($items)
{
  //$items = wp_list_sort($items, 'menu_order');

  for ($i = 0; $i < count($items); $i++) {
    $items[$i]->menu_order = $i;
  }

  return $items;
}

add_filter('wp_get_nav_menu_items', 'spanarts_wp_get_nav_menu_items', 20, 2);

?>
