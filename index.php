<?php
/*
Plugin Name: Product JSON API Fetch and Process with Pagination
Description: Fetches product JSON from an external WooCommerce API with pagination, processes it, and returns the result via a custom API endpoint.
Version: 1.0
*/

add_action('rest_api_init', function () {
  register_rest_route('custom-api/v1', '/products-json/', array(
    'methods' => 'GET',
    'callback' => 'fetch_and_generate_product_json_paginated',
    'permission_callback' => '__return_true', // Open for everyone, customize as needed
  ));
});

function fetch_and_generate_product_json_paginated()
{
  // Load WooCommerce products
  $args = array(
    'post_type' => 'product',
    'posts_per_page' => -1,
  );
  $products = get_posts($args);

  $product_data = array();

  foreach ($products as $product_post) {
    $product_id = $product_post->ID;
    $sku = get_post_meta($product_id, '_sku', true);
    $name = get_the_title($product_id);
    $description = get_post_field('post_content', $product_id);
    $image = wp_get_attachment_url(get_post_thumbnail_id($product_id));

    // Check if product has the custom field 'Artikel_Freifeld6'
    $meta_field_6 = get_post_meta($product_id, 'Artikel_Freifeld6', true);

    // Variable Product (master;1)
    if (strpos($meta_field_6, 'master;1') !== false) {
      // Assuming 'Kolbenmaß (mm)' is the attribute, customize this if needed
      $attribute_name = "Kolbenmaß (mm)";
      $product_data[$sku] = array(
        'sku' => $sku,
        'products_name' => $name,
        'products_description' => $description,
        'products_type' => 'Variable',
        'products_image' => $image,
        'attribute' => array(
          $attribute_name => array() // Variants will be added here
        )
      );
    }
    // Variant Product (-m)
    elseif (strpos($meta_field_6, '-M') !== false) {
      // Get parent SKU from 'Artikel_Freifeld6'
      $parent_sku = $meta_field_6;

      // Assuming the last line of description contains the variant details
      $description_lines = explode("\n", $description);
      $variant_name = end($description_lines);

      // Add variant to the parent product's attribute (Kolbenmaß)
      if (isset($product_data[$parent_sku])) {
        $attribute_name = "Kolbenmaß (mm)";
        $variant_count = count($product_data[$parent_sku]['attribute'][$attribute_name]) + 1;
        $product_data[$parent_sku]['attribute'][$attribute_name][$variant_count] = array(
          'variant' => $variant_name,
          'sku' => $sku,
          'price' => 0 // Assuming price is 0 as default
        );
      }
    }
  }
  // Clear memory after each batch
  wp_cache_flush();
  // } while (count($products) === $per_page); // Loop until less than $per_page products are returned

  // Return the processed product data as a JSON response
  return rest_ensure_response($product_data);
}
