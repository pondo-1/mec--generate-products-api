<?php

namespace MEC__CreateProducts\API;

use MEC__CreateProducts\Utils\Utils;

//Show products. products overview. variable products mit variant. simple products, the rest

class PrepareJsonLocal
{

  private $json_prefix;
  private $json_suffix;
  private $filePath_all;
  // $json_prefix = 'products';
  // $json_suffix = ['all', 'variable', 'variant', 'simple', 'extra', 'variable_variant'];


  public function __construct($json_prefix, $json_suffix)
  {
    $this->json_prefix = $json_prefix;
    $this->json_suffix =  $json_suffix;
    $this->filePath_all = MEC__CP_API_Data_DIR . $this->json_prefix . '_all.json';
  }

  function separate_data()
  {
    // Lädt die 'products_all.json'-Datei
    $data = json_decode(file_get_contents($this->filePath_all), true);
    $products = [];
    $report = [];
    foreach ($this->json_suffix as $product_type) {
      $products[$product_type] = [];
      $report[$product_type] = [];
    }
    // Initialisiert Arrays für die verschiedenen Produkttypen

    $i = 0;
    // Durchläuft die Produktdaten und sortiert sie nach Typ
    foreach ($data as $sku => $product) {
      $i++;
      // Fügt das Produkt basierend auf bestimmten Bedingungen zur entsprechenden Liste hinzu
      if (str_ends_with($sku, '-M') && isset($product['relation'][0]) && $product['relation'][0] == 'master') {
        $products['variable'][$sku] = $product;
      } elseif (strpos($product['relation'][0], '-M') !== false) {
        $products['variant'][$sku] = $product;
        $products['variant'][$sku]['relation'][1] = 2;
        // Match the last line after any line breaks (\r\n or \n)
        preg_match('/[^\r\n]+$/', trim($products['variant'][$sku]['info']['description']), $matches);
        $products['variant'][$sku]['relation'][2] = $matches[0];
      } elseif ($product['info']['image']) {
        $products['simple'][$sku] = $product;
      } else {
        $products['extra'][$sku] = $product;
      }
    }


    // Speichert die sortierten Daten in separaten JSON-Dateien
    foreach ($this->json_suffix as $product_type) {
      // Check if $products[$product_type] is empty before attempting to write
      file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'products_' . $product_type . '.json', json_encode($products[$product_type], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }



    // Prepare Daten in JSON-Dateien for variable products
    $variable_prepared = $products['variable'];
    foreach ($products['variant'] as $variant_sku => $variant_product) {
      $parent_sku = $variant_product['relation'][0];
      $attribute = [
        'option' => $variant_product['relation'][2],
        'sku' => $variant_sku,
        'price' => $variant_product['price'],
      ];
      if (isset($variable_prepared[$parent_sku])) {
        $variable_prepared[$parent_sku]['relation']['options'][] = $attribute;
      } else {
        Utils::cli_log("variant Product(sku:$variant_product) has no its variable/parent product(sku:$parent_sku)");
      }
    }
    file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'products_variable_variant.json', json_encode($variable_prepared, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    foreach ($this->json_suffix as $product_type) {
      $report[$product_type] = count($products[$product_type]);
    }
    $report["all"] = count($data);
    $report["variable_variant"] = count($variable_prepared);

    file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'products_report.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    // Protokolliert, dass die Produkte erfolgreich aufgeteilt wurden
    Utils::putLog('Produkte wurden erfolgreich in separate Dateien aufgeteilt.');
  }

  function delete_separated_data()
  {
    // Löscht die JSON-Dateien für jeden Produkttyp, falls sie existieren
    foreach ($this->json_suffix as $product_type) {
      $file_path = __DIR__ . DIRECTORY_SEPARATOR . 'products_' . $product_type . '.json';

      // Prüfen, ob die Datei existiert, und dann löschen
      if (file_exists($file_path)) {
        unlink($file_path);
      }
    }
  }
}
