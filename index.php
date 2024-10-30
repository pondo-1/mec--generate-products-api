<?php
/*
Plugin Name: Product JSON API wp-json/mec-api/v1/products-json/
Description: Fetches product JSON from an external WooCommerce API with pagination, processes it, and returns the result via a custom API endpoint.
Version: 1.0
*/

add_action('rest_api_init', function () {

  register_rest_route('mec-api/v1', '/products/', array(
    'methods' => 'GET',
    'callback' => 'mec_fetch_and_generate_product_json_paginated',
    'permission_callback' => '__return_true', // Open for everyone, customize as needed
  ));
});



function mec_fetch_and_generate_product_json_paginated()
{
  $per_page = 100; // Fetch products in batches of 100
  $page = 1;
  $products_data = array();
  $total_products = 0;

  [$products_data, $total_products] = mec_products_json();

  // Return the processed product data as a JSON response
  return rest_ensure_response(array(
    'total_products_processed' => $total_products,
    'products_data' => $products_data
  ));
}

function mec_products_json($per_page = 100, $page = 1)
{
  $variant_buffer = array(); // Buffer to temporarily store variant products
  $products_data = array();
  $total_products = 0;
  do {
    // Use WooCommerce API to get products in batches (100 per request)
    $products = wc_get_products(array(
      'limit' => $per_page,
      'page' => $page,
      'status' => 'publish'
    ));

    if (empty($products)) {
      break; // Exit loop if no more products
    }

    foreach ($products as $product) {
      $product_id = $product->get_id();
      $sku = $product->get_sku();
      $excerpt = '';
      if (has_excerpt($product_id)) {
        $excerpt = wp_strip_all_tags(get_the_excerpt($product_id));
      }
      $meta_field_6 = get_post_meta($product_id, 'Artikel_Freifeld6', true);
      $products_data[$sku] = [
        'name' => $product->get_name(),
        'price' => $product->get_price(),
        'freifeld6' => $meta_field_6,
        'taxonomyField' => $excerpt,
        'image' => wp_get_attachment_url($product->get_image_id()),
        'description' => $product->get_description(),
      ];
    }

    // Move to the next page
    $page++;
    $total_products += count($products);

    // Clear memory after each batch
    wp_cache_flush();
  } while (count($products) === $per_page); // Loop until less than $per_page products are returned

  return [$products_data, $total_products];
}
