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

  $per_page = 100; // Fetch products in batches of 100
  $page = 1;
  $product_data = array();
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

    // Process the products in this batch
    foreach ($products as $product) {
      $product_id = $product->get_id();
      $sku = $product->get_sku();
      $name = $product->get_name();
      $description = $product->get_description();
      $image = wp_get_attachment_url($product->get_image_id());

      // Get custom field 'Meta: Artikel_Freifeld6'
      $meta_field_6 = get_post_meta($product_id, 'Meta: Artikel_Freifeld6', true);

      // Variable Product (master;1 in Meta: Artikel_Freifeld6)
      if (strpos($meta_field_6, 'master;1') !== false) {
        // Assuming 'Kolbenmaß (mm)' is the attribute, customize as needed
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
      // Variant Product (-m in Meta: Artikel_Freifeld6)
      elseif (strpos($meta_field_6, '-m') !== false) {
        // Get parent SKU from 'Meta: Artikel_Freifeld6'
        $parent_sku = explode(';', $meta_field_6)[0];

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
            'price' => $product->get_price()
          );
        }
      }
    }

    // Move to the next page
    $page++;
    $total_products += count($products);

    // Clear memory after each batch
    wp_cache_flush();
  } while (count($products) === $per_page); // Loop until less than $per_page products are returned

  // Return the processed product data as a JSON response
  return rest_ensure_response(array(
    'total_products_processed' => $total_products,
    'product_data' => $product_data
  ));
}
