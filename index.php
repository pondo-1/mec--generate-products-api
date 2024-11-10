<?php
/*
Plugin Name: Product JSON API wp-json/mec-api/v1/products-json/
Description: Fetches product JSON from an external WooCommerce API with pagination, processes it, and returns the result via a custom API endpoint.
Version: 2.0
*/


define('MEC__CP_DIR', dirname(__FILE__)); // ..../public/wp-content/plugins/MEC__CreateProducts
define('MEC__CP_URL', plugins_url('', __FILE__));
define('MEC__CP_PLUGIN_SLUG', plugin_basename(__FILE__));
define('MEC__CP_APIURL', '/wp-json/mec-api/v1/products/');
define('MEC__CP_API_Data_DIR', dirname(__FILE__) . '/includes/API/');  // ../public/wp-content/plugins/MEC__CreateProducts/API/
global $MEC__CP_log;
global $MEC__CP_json_products_all;

// Autoload classes
spl_autoload_register(function ($class_name) {
  $namespace = 'MEC__CreateProducts\\';
  if (strpos($class_name, $namespace) !== false) {
    $class_name = str_replace($namespace, '', $class_name);
    $file = plugin_dir_path(__FILE__) . 'includes/' . str_replace('\\', '/', $class_name) . '.php';
    if (file_exists($file)) {
      require_once $file;
    } else {
      error_log("Failed to load file: " . $file);
    }
  }
});

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
  $saved = 0;

  [$products_data, $saved, $total_products] = mec_products_json($per_page, $page);

  // Return the processed product data as a JSON response
  return rest_ensure_response(array(
    'total_products_processed' => $total_products,
    'saved_data' => $saved,
    'products_data' => $products_data
  ));
}

function mec_products_json($per_page = 100, $page = 1)
{
  $variant_buffer = array();
  $products_data = array();
  $total_products = 0;

  do {
    $products = wc_get_products(array(
      'limit' => $per_page,
      'page' => $page,
      'status' => 'publish'
    ));

    if (empty($products)) {
      break;
    }

    foreach ($products as $product) {
      $product_id = $product->get_id();
      $sku = $product->get_sku();

      if (!$sku) {
        continue; // Skip products without SKU
      }

      $excerpt = has_excerpt($product_id) ? wp_strip_all_tags(get_the_excerpt($product_id)) : '';
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

    // Optional: Log counts after each batch
    MEC__CreateProducts\Utils\Utils::cli_log("Batch processed. Total products so far: $total_products, Saved products: " . count($products_data));
    error_log("Batch processed. Total products so far: $total_products, Saved products: " . count($products_data));

    // Clear memory after each batch
    wp_cache_flush();
  } while (count($products) === $per_page);

  $saved = count($products_data);
  return [$products_data, $saved, $total_products];
}
