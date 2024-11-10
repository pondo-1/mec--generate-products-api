<?php

namespace MEC__CreateProducts\Utils;

use WC_Product_Simple;
use WC_Product_Variable;
use WC_Product_Attribute;
use WC_Product_Variation;
use WP_CLI;

// use WP_CLI;

class WCHandler
{
  public static function create_products($wp_CLI_exist = 1, $products_type = 'simple', $num = -1, $start = 0)
  {

    if ($products_type == 'simple') {
      self::create_simple_product($wp_CLI_exist, $num, $start);
    } else if ($products_type == 'variable') {
      self::create_variable_product($num, $start);
    }
  }

  public static function create_variable_product($num, $start)
  {

    $filePath = MEC__CP_API_Data_DIR . 'products_variable_variant.json';
    if (file_exists($filePath)) {
      $products_data = json_decode(file_get_contents($filePath), true);

      Utils::cli_log("$num of variable products will be created:");
      $startpoint = 0;
      $counts = 0;
      foreach ($products_data as $variable_sku => $product_data) {
        $startpoint++;

        // start only 
        if ($start > $startpoint + 1) {
          continue;
        }
        // check if sku already oppcupied
        $productID = wc_get_product_id_by_sku($variable_sku);
        if (!$productID) {

          // Step 1: Create the variable product
          $product = new WC_Product_Variable();
          $product_id = self::set_product_data($variable_sku, $product, $product_data);

          if (isset($product_data['relation']['options'])) {
            // Step 2: Define and set attribute for the variable product
            $attribute = new WC_Product_Attribute();
            $attribute_name = $product_data['relation'][2]; // Attribute name, e.g., "Kolbenmaß (mm)"
            $attribute_options = array_column($product_data['relation']['options'], 'option');

            // WooCommerce expects attribute names to be lowercase, no spaces
            $attribute_slug = sanitize_title($attribute_name);
            $attribute->set_name($attribute_name);
            $attribute->set_options($attribute_options);
            $attribute->set_position(0);
            $attribute->set_visible(true);
            $attribute->set_variation(true);
            $product->set_attributes([$attribute]);

            // Step 3: Set default attribute
            // $default_attributes = [];
            // foreach ($product_data['relation']['options'] as $variant_data) {
            //   if (strpos($variant_data['option'], '(Standard)') !== false) {
            //     $default_attributes[$product_data['relation'][2]] = $variant_data['option'];
            //   }
            // }
            // $product->set_default_attributes($default_attributes);

            // Step 4: Add variations to the variable product

            foreach ($product_data['relation']['options'] as $variant_data) {
              $variation = new WC_Product_Variation();
              $variation->set_parent_id($product_id);

              // Use the attribute slug to link the variation option correctly
              $variation->set_attributes([
                $attribute_slug => $variant_data['option']
              ]);

              $variation->set_sku($variant_data['sku']);
              $variation->set_price($variant_data['price']);
              $variation->set_regular_price($variant_data['price']);
              $variation->set_status('publish');

              $variation->save(); // Save each variation
            }
          } else {
            Utils::cli_log("this variable product has no variant. sku:$variable_sku");
          }
          $counts++;
          Utils::cli_log($counts . "th product created, sku:" . $variable_sku);
          // Final Save for the variable product to update WooCommerce with variations
          $product->save();

          if (($num != -1) && ($counts + 1 > $num)) {
            exit;
          }
        } else {
          Utils::putLog("sku already exist: " . $variable_sku);
        }
      }
    }
  }

  public static function create_simple_product($wp_CLI_exist, $num, $start)
  {
    $counts = 0;
    $filePath = MEC__CP_API_Data_DIR . 'products_simple.json';
    if (file_exists($filePath)) {
      $products_data = json_decode(file_get_contents($filePath), true);
      Utils::cli_log("$num of simple products will be created:");
      foreach ($products_data as $sku => $product_data) {
        $counts++;

        // manage start point
        if ($start > $counts) {
          continue;
        }
        // check if the sku already exist 
        $productID = wc_get_product_id_by_sku($sku);
        if (!$productID) {

          // that's CRUD object
          $product = new WC_Product_Simple();
          $product->set_price($product_data['price']);
          self::set_product_data($sku, $product, $product_data);
          Utils::cli_log($counts . "th product created, sku:" . $sku);
          if (($num != -1) && ($counts + 1 > $num)) {
            exit;
          }
        } else {
          Utils::cli_log("sku already exist: " . $sku);
        }
      }
    } else {
      // Log an error or handle the missing file case
      Utils::cli_log("Error: 'products_simple.json' file not found at $filePath");
    }
  }

  public static function set_product_data($sku, $product, $product_data)
  {
    $product->set_name($product_data['name']);
    $product->set_sku($sku);
    $product->set_description($product_data['info']['description']);
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');
    // Set the image using the URL
    $image_url = $product_data['info']['image']; // Assuming you have the URL
    self::set_product_image_from_url($product, $image_url);

    // Save the product to get its ID
    $product_id = $product->save();

    // Set custom taxonomy terms

    // Define the taxonomies and their keys
    $taxonomy_keys = [
      'Typ' => 'typ',
      'Marke' => 'marke',
      'Modell' => 'modell',
      'Hubraum' => 'hubraum',
      'Baujahr' => 'baujahr'
    ];

    // Loop through each key and set terms if the key exists
    foreach ($taxonomy_keys as $key => $taxonomy) {
      if (isset($product_data['compatible'][$key])) {
        $terms = $product_data['compatible'][$key];

        // Convert 'Baujahr' terms to strings
        if ($key === 'Baujahr') {
          $terms = array_map('strval', $terms);
        }

        wp_set_object_terms($product_id, $terms, $taxonomy);
      }
    }

    Utils::putLog('set the product: ' . $sku);
    return $product_id;
  }
  // Function to download the image from a URL and attach it to the product
  public static function set_product_image_from_url($product, $image_url)
  {
    // Check if the URL is valid and download the image
    $image_id = media_sideload_image($image_url, 0, null, 'id');

    if (is_wp_error($image_id)) {
      // Handle the error, maybe log it for debugging
      error_log('Failed to download image: ' . $image_url);
      return false;
    }

    // Set the downloaded image as the product's featured image
    $product->set_image_id($image_id);
    return true;
  }
}
