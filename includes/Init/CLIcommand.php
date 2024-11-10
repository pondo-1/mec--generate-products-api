<?php

namespace MEC__CreateProducts\Init;

use MEC__CreateProducts\Utils\WCHandler;
use MEC__CreateProducts\Utils\SQLscript;
use MEC__CreateProducts\Utils\Utils;
use WP_CLI;

class CLIcommand
{

  public function __construct()
  {
    if (defined('WP_CLI') && WP_CLI) {
      WP_CLI::add_command('create_products', [$this, 'create_products_CLI']);
      WP_CLI::add_command('delete_all_products', [SQLscript::class, 'delete_all_products']);
      WP_CLI::add_command('compare', [$this, 'compare']);
      WP_CLI::add_command('check', [$this, 'check']);
    }
  }

  function check()
  {
    $filePath = MEC__CP_API_Data_DIR . 'products_all.json';
    if (file_exists($filePath)) {
      $all = json_decode(file_get_contents($filePath), true);
      Utils::cli_log("count:" . count($all));
    }

    $filePath = MEC__CP_API_Data_DIR . 'products_raw.json';
    if (file_exists($filePath)) {
      $raw = json_decode(file_get_contents($filePath), true);
      $raw = $raw['products_data'];
      $raw_keys = array_keys($raw);
      Utils::cli_log("count:" . count($raw));
    }
    $filePath = MEC__CP_API_Data_DIR . '14774.csv';
    if (file_exists($filePath)) {
      $csv = array_map('str_getcsv', file($filePath));
      Utils::cli_log("count:" . count($csv));
    }


    // $new = [];
    // foreach ($csv as $key => $value) {
    //   array_unshift($new, $key . "::" . $value[0] . "//" . (14772 - $key) . "::" . $raw_keys[14772 - $key]);
    // }
    // file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'check.json', json_encode($new, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    // Utils::cli_log(count($new));

    // $correct = 0;
    // $change = 0;
    // for ($i = 14772; $i > -1; $i--) {
    //   if ($csv[$i][0] != $raw_keys[14772 - $i]) {
    //     if ($change == 0 && ($csv[$i][0] == $raw_keys[14772 - $i + 1 + $correct])) $change = 1;
    //     elseif ($change == 1 && ($csv[$i][0] == $raw_keys[14772 - $i - 1 + $correct])) $change = 0;
    //     else {
    //       Utils::cli_log($i . "::" . $csv[$i][0] . "//" . $raw_keys[14772 - $i + $correct]);
    //       $correct++;
    //     }
    //   }
    // }
    // Utils::cli_log(print_r($csv, true));

    $i = 0;
    do {
      // if ($i == 0) { 
      //   // Utils::cli_log("8034D-M");
      //   // Utils::cli_log($csv[$i][0]);
      //   Utils::cli_log(isset($raw["8034D-M"]));
      //   Utils::cli_log((isset($raw[$csv[$i][0]])));
      //   Utils::cli_log("Value of csv[\$i][0]: " . $csv[$i][0]);

      //   $cleanedKey = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $csv[$i][0]);
      //   Utils::cli_log("Cleaned value of csv[\$i][0]: " . $cleanedKey);
      //   Utils::cli_log(isset($raw[$cleanedKey]));
      // }

      $cleanedKey = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $csv[$i][0]);

      if (isset($raw[$cleanedKey])) {
        // Utils::cli_log($csv[$i][0]);
      } else {
        Utils::cli_log($i . "::" . $csv[$i][0]);
      }
      $i++;
    } while (isset($csv[$i][0]));
    Utils::cli_log($i);
  }
  function compare()
  {
    $filePath = MEC__CP_API_Data_DIR . 'products_raw.json';
    if (file_exists($filePath)) {
      $raw = json_decode(file_get_contents($filePath), true);
      $raw = $raw['products_data'];
      $raw_keys = array_keys($raw);
    }
    $filePath = MEC__CP_API_Data_DIR . '14774.csv';
    if (file_exists($filePath)) {
      $csv = array_map('str_getcsv', file($filePath));
    }

    $i = 0;
    do {
      if ($i == 0) {
        Utils::cli_log($csv[$i][0]);
        Utils::cli_log(isset($raw["8034D-M"]));
        Utils::cli_log(isset($raw[$csv[$i][0]]));
      }
      if (isset($raw[$csv[$i][0]])) {
        // Utils::cli_log($csv[$i][0]);
      } else {
        Utils::cli_log($i . "::" . $csv[$i][0]);
      }
      $i++;
    } while (isset($csv[$i][0]) || $i < 5);
  }


  function create_products_CLI($arg, $assoc_args)
  {
    $wp_CLI_exist = null;
    if (!isset($assoc_args['where'])) {
      $wp_CLI_exist = 1;
    }

    if (isset($assoc_args['num'])) {
      $number_to_generate =  $assoc_args['num'];
    } else $number_to_generate =  0;

    if (isset($assoc_args['type'])) {
      $type = $assoc_args['type'];
    } else {
      $type = 'simple';
    }

    if (isset($assoc_args['start'])) {
      $start = $assoc_args['start'];
    } else {
      $start = 0;
    }
    WCHandler::create_products(1, $type, $number_to_generate, $start);
  }
}
