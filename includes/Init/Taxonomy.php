<?php

namespace MEC__CreateProducts\Init;

class Taxonomy
{

  // Constructor to set up actions
  public function __construct()
  {
    add_action('admin_menu', [$this, 'remove_product_taxonomies_from_sidebar'], 99);
    add_action('init', [$this, 'disable_default_taxonomies'], 20);
    add_action('init', [$this, 'register_custom_taxonomies'], 30);
  }

  // Removes categories and tags from the sidebar
  public function remove_product_taxonomies_from_sidebar()
  {
    remove_submenu_page('edit.php?post_type=product', 'edit-tags.php?taxonomy=product_cat&post_type=product'); // Remove Categories
    remove_submenu_page('edit.php?post_type=product', 'edit-tags.php?taxonomy=product_tag&post_type=product'); // Remove Tags
  }

  // Unregister default WooCommerce categories and tags for products
  public function disable_default_taxonomies()
  {
    if (post_type_exists('product')) { // Ensures WooCommerce is loaded
      unregister_taxonomy_for_object_type('product_cat', 'product'); // Remove WooCommerce product categories
      unregister_taxonomy_for_object_type('product_tag', 'product'); // Remove WooCommerce product tags
    }
  }
  // Register custom taxonomies for product data
  function register_custom_taxonomies()
  {

    // Register 'Typ' taxonomy
    register_taxonomy('typ', 'product', array(
      'labels' => array(
        'name' => 'Typen',
        'singular_name' => 'Typ',
      ),
      'hierarchical' => true,
      'show_ui' => true,
      'show_in_rest' => true, // To make it available in REST API
      'public' => true,
    ));

    // Register 'Marke' taxonomy
    register_taxonomy('marke', 'product', array(
      'labels' => array(
        'name' => 'Marken',
        'singular_name' => 'Marke',
      ),
      'hierarchical' => true,
      'show_ui' => true,
      'show_in_rest' => true, // To make it available in REST API
      'public' => true,
    ));

    // Register 'Modell' taxonomy
    register_taxonomy('modell', 'product', array(
      'labels' => array(
        'name' => 'Modelle',
        'singular_name' => 'Modell',
      ),
      'hierarchical' => true,
      'show_ui' => true,
      'show_in_rest' => true, // To make it available in REST API
      'public' => true,
    ));

    // Register 'Hubraum' taxonomy
    register_taxonomy('hubraum', 'product', array(
      'labels' => array(
        'name' => 'Hubraume',
        'singular_name' => 'hubraum',
      ),
      'hierarchical' => true,
      'show_ui' => true,
      'show_in_rest' => true, // To make it available in REST API
      'public' => true,
    ));

    // Register 'Baujahr' taxonomy
    register_taxonomy('baujahr', 'product', array(
      'labels' => array(
        'name' => 'Baujahre',
        'singular_name' => 'Baujahr',
      ),
      'hierarchical' => true,
      'show_ui' => true,
      'show_in_rest' => true, // To make it available in REST API
      'public' => true,
    ));
  }
}
