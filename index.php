<?php
/*
Plugin Name: Product JSON API Fetch and Process with Pagination
Description: Fetches product JSON from an external WooCommerce API with pagination, processes it, and returns the result via a custom API endpoint.
Version: 1.0
*/

add_action('rest_api_init', function () {
  register_rest_route('mec-api/v1', '/products-json/', array(
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
  $variant_buffer = array(); // Buffer to temporarily store variant products

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

    // First, process only the variable products (master;1)
    foreach ($products as $product) {
      $product_id = $product->get_id();
      $sku = $product->get_sku();
      $name = $product->get_name();
      $description = $product->get_description();
      $image = wp_get_attachment_url($product->get_image_id());

      // Get custom field 'Artikel_Freifeld6'
      $meta_field_6 = get_post_meta($product_id, 'Artikel_Freifeld6', true);

      // Variable Product (master;1 in Artikel_Freifeld6)
      if (strpos($meta_field_6, 'master;1') !== false) {
        // Get the attribute
        $attribute_name = explode(';', $meta_field_6)[2];
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
    }

    // Then, process the variant products (-M) and add them to their parent
    foreach ($products as $product) {
      $product_id = $product->get_id();
      $sku = $product->get_sku();
      $description = $product->get_description();
      $meta_field_6 = get_post_meta($product_id, 'Artikel_Freifeld6', true);

      // Variant Product (-M in Meta: Artikel_Freifeld6)
      if (strpos($meta_field_6, '-M') !== false) {
        // Get parent SKU from 'Meta: Artikel_Freifeld6'
        $parent_sku = explode(';', $meta_field_6)[0];

        // Assuming the last line of description contains the variant details
        $description_lines = explode("\n", $description);
        $variant_name = end($description_lines);

        // Add variant to the parent product's attribute (Kolbenmaß)
        if (isset($product_data[$parent_sku])) {
          $attribute_name = array_key_first($product_data[$parent_sku]['attribute']);
          $variant_count = count($product_data[$parent_sku]['attribute'][$attribute_name]) + 1;
          $product_data[$parent_sku]['attribute'][$attribute_name][$variant_count] = array(
            'variant' => $variant_name,
            'sku' => $sku,
            'price' => $product->get_price()
          );
        } else {
          // Store variants in buffer if parent doesn't exist yet
          $variant_buffer[$parent_sku][] = array(
            'variant' => $variant_name,
            'sku' => $sku,
            'price' => $product->get_price()
          );
        }
      }
    }

    // Now, add any buffered variants to their parent products (if they exist)
    foreach ($variant_buffer as $parent_sku => $variants) {
      if (isset($product_data[$parent_sku])) {
        foreach ($variants as $variant) {
          $attribute_name = array_key_first($product_data[$parent_sku]['attribute']);
          $variant_count = count($product_data[$parent_sku]['attribute'][$attribute_name]) + 1;
          $product_data[$parent_sku]['attribute'][$attribute_name][$variant_count] = $variant;
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
