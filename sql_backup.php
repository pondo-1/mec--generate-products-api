<?php
/*

*/

  // global $wpdb;
  // $per_page = 100; // Products per page
  // $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
  // $offset = ($page - 1) * $per_page;

  // // Query to fetch product IDs, SKUs, names, descriptions, image IDs, and the custom field6 value
  // $query = "
  //       SELECT 
  //           p.ID as product_id, 
  //           p.post_title as name, 
  //           p.post_content as description, 
  //           pm_sku.meta_value as sku, 
  //           pm_image.meta_value as image_id, 
  //           pm_meta_field_6.meta_value as field6
  //       FROM {$wpdb->posts} p
  //       LEFT JOIN {$wpdb->postmeta} pm_sku ON pm_sku.post_id = p.ID AND pm_sku.meta_key = '_sku'
  //       LEFT JOIN {$wpdb->postmeta} pm_image ON pm_image.post_id = p.ID AND pm_image.meta_key = '_thumbnail_id'
  //       LEFT JOIN {$wpdb->postmeta} pm_meta_field_6 ON pm_meta_field_6.post_id = p.ID AND pm_meta_field_6.meta_key = 'Artikel_Freifeld6'
  //       WHERE p.post_type = 'product'
  //       AND p.post_status = 'publish'
  //       AND (
  //           pm_meta_field_6.meta_value LIKE '%master;1%' 
  //           OR pm_meta_field_6.meta_value LIKE '%-M%'
  //       )
  //       ORDER BY pm_meta_field_6.meta_value DESC
  //       LIMIT %d OFFSET %d
  //   ";

  // // Prepare and execute the SQL query
  // $products = $wpdb->get_results($wpdb->prepare($query, $per_page, $offset));

  // // Initialize the array for the product data
  // $product_data = array();

  // // Process each product
  // // if (strpos($product->field6, 'master;1') !== false) {
  // // Get product image URL
  // $image_url = wp_get_attachment_url($product->image_id);

  // // Build the product data array
  // $product_data[$product->sku] = array(
  //   'sku' => $product->sku,
  //   'products_name' => $product->name,
  //   'products_description' => $product->description,
  //   'products_type' => 'Variable',
  //   'products_image' => $image_url,
  //   'field_6' => $product->field6,
  //   'attribute' => array(
  //     'Kolbenmaß (mm)' => array() // Placeholder for variants
  //   )
  // );
