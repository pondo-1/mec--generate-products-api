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

  global $wpdb;
  $per_page = 100; // Products per page
  $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
  $offset = ($page - 1) * $per_page;

  // Query to fetch product IDs, SKUs, names, descriptions, image IDs, and the custom field6 value
  $query = "
        SELECT 
            p.ID as product_id, 
            p.post_title as name, 
            p.post_content as description, 
            pm_sku.meta_value as sku, 
            pm_image.meta_value as image_id, 
            pm_meta_field_6.meta_value as field6
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm_sku ON pm_sku.post_id = p.ID AND pm_sku.meta_key = '_sku'
        LEFT JOIN {$wpdb->postmeta} pm_image ON pm_image.post_id = p.ID AND pm_image.meta_key = '_thumbnail_id'
        LEFT JOIN {$wpdb->postmeta} pm_meta_field_6 ON pm_meta_field_6.post_id = p.ID AND pm_meta_field_6.meta_key = 'Artikel_Freifeld6'
        WHERE p.post_type = 'product'
        AND p.post_status = 'publish'
        AND (
            pm_meta_field_6.meta_value LIKE '%master;1%' 
            OR pm_meta_field_6.meta_value LIKE '%-M%'
        )
        ORDER BY pm_meta_field_6.meta_value DESC
        LIMIT %d OFFSET %d
    ";

  // Prepare and execute the SQL query
  $products = $wpdb->get_results($wpdb->prepare($query, $per_page, $offset));

  // Initialize the array for the product data
  $product_data = array();

  // Process each product
    // if (strpos($product->field6, 'master;1') !== false) {
    // Get product image URL
    $image_url = wp_get_attachment_url($product->image_id);

    // Build the product data array
    $product_data[$product->sku] = array(
      'sku' => $product->sku,
      'products_name' => $product->name,
      'products_description' => $product->description,
      'products_type' => 'Variable',
      'products_image' => $image_url,
      'field_6' => $product->field6,
      'attribute' => array(
        'Kolbenmaß (mm)' => array() // Placeholder for variants
      )
    );
    // }

    // // Process each variant
    // elseif (strpos($product->field6, '-M') !== false) {
    //   $parent_sku = $product->field6;

    //   // Assuming the last line of description contains the variant details
    //   $description_lines = explode("\n", $product->description);
    //   $variant_name = end($description_lines);
    //   // $product_data[$product->sku] = array('variant' => $variant_name);

    //   // Add variant to the parent product's attribute (Kolbenmaß)
    //   if (isset($product_data[$parent_sku])) {
    //     $attribute_name = "Kolbenmaß (mm)";
    //     $variant_count = count($product_data[$parent_sku]['attribute'][$attribute_name]) + 1;
    //     $product_data[$parent_sku]['attribute'][$attribute_name][$variant_count] = array(
    //       'variant' => $variant_name,
    //       'sku' => $product->sku,
    //       'price' => $product->get_price()
    //     );
    //   }
    // }
  }

  // // Move to the next page
  // $page++;
  // $total_products += count($products);


  // Return the processed product data as a JSON response
  return rest_ensure_response(array(
    // 'total_products_processed' => $total_products,
    'product_data' => $product_data
  ));
}
