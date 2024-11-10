<?php

namespace MEC__CreateProducts\Utils;

class SQLscript
{
  // Delete all products and related lookup datas
  public static function delete_all_products()
  {
    global $wpdb;

    Utils::cli_log('delete all products by sql script');

    // Step 1: Get product IDs and variation IDs
    $product_ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type IN ('product', 'product_variation')");
    Utils::cli_log('found: ' . count($product_ids) . ' products');
    if (!empty($product_ids)) {
      $product_ids_str = implode(',', array_map('intval', $product_ids));

      // Step 2: Delete from wp_posts (products and variations)
      $wpdb->query("DELETE FROM {$wpdb->posts} WHERE ID IN ($product_ids_str)");

      // Step 3: Delete related post meta
      $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE post_id IN ($product_ids_str)");

      // Step 4: Delete from WooCommerce specific tables

      // Delete from WooCommerce product lookup table
      $wpdb->query("DELETE FROM {$wpdb->prefix}wc_product_meta_lookup WHERE product_id IN ($product_ids_str)");

      // Delete term relationships (categories, tags, attributes, etc.)
      $wpdb->query("DELETE FROM {$wpdb->term_relationships} WHERE object_id IN ($product_ids_str)");

      // Delete from custom tables related to WooCommerce (replace with actual table names if different)
      // Example: delete from product-related tables you may have added

      // Step 5: Delete associated media attachments (thumbnails, featured images, etc.)
      // Get IDs of media attachments related to products
      $attachment_ids = $wpdb->get_col("
        SELECT ID FROM {$wpdb->posts} 
        WHERE post_type = 'attachment' AND post_parent IN ($product_ids_str)
    ");

      if (!empty($attachment_ids)) {
        foreach ($attachment_ids as $attachment_id) {
          // Delete the attachment and its files using WordPress function
          wp_delete_attachment($attachment_id, true);
        }
      }
    }

    // To delete all media files in WordPress that are not attached to any posts 
    // (i.e., unused or orphaned images), you can query for unattached media files 
    // and then delete them using wp_delete_attachment. Here’s a code snippet for this specific task:

    Utils::cli_log("Delete all unattached media files");
    // Get IDs of unattached media files (attachments with no parent post)
    $unattached_media_ids = $wpdb->get_col("
      SELECT ID FROM {$wpdb->posts} 
      WHERE post_type = 'attachment' 
      AND post_parent = 0
      ");

    Utils::cli_log("Loop through and delete each unattached media file");
    if (!empty($unattached_media_ids)) {
      foreach ($unattached_media_ids as $media_id) {
        wp_delete_attachment($media_id, true);
      }
    }
  }
}
