<?php

namespace MEC__CreateProducts\Admin;

use MEC__CreateProducts\API\SaveToLocal;
use MEC__CreateProducts\API\PrepareJsonLocal;
use MEC__CreateProducts\Utils\Utils;
use MEC__CreateProducts\Utils\AdminButton;
use MEC__CreateProducts\Utils\WCHandler;
use MEC__CreateProducts\Utils\SQLscript;

class AdminPage
{

  public function __construct()
  {
    add_action('admin_init', [$this, 'prepare_data_actions_html']);
    add_action('admin_init', [$this, 'create_products_actions_html']);
    add_action('admin_menu', [$this, 'addAdminMenu']);
  }

  public function addAdminMenu()
  {

    add_menu_page(
      'MEC_dev',
      'MEC_dev',
      'manage_options',
      'MEC_dev',
      [$this, 'renderAdminPage'],    // Callback to render the page content
      '',                           // Icon
      65                            // Position
    );
  }


  public function renderAdminPage()
  {
    $return_html = null;
    // Start output buffering
    ob_start();
?>
    <div class="wrap">
      <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
      <?php echo $this->optionPageSection('Prepare the Data', $this->prepare_data_actions_html()); ?>
      <?php echo $this->optionPageSection('Create Products', $this->create_products_actions_html()); ?>
    </div>
  <?php
    $return_html .= ob_get_clean();

    echo $return_html;
  }

  public function optionPageSection($section_title, $callback)
  {

    $return_html = null;
    // Start output buffering
    ob_start();
  ?>
    <div class="section" style="border: solid; padding: 20px;">
      <h2><?php echo $section_title; ?></h2>
      <table class="form-table">
        <tbody>
          <?php echo $callback ?>
        </tbody>
      </table>
    </div>
  <?php
    $return_html .= ob_get_clean();

    echo $return_html;
  }


  // Method that registers actions
  public function prepare_data_actions_html()
  {
    $html = null;

    // Save Json to as Local file
    $target = 'https://mec.pe-dev.de/wp-json/mec-api/v1/products/';
    $filePath =  MEC__CP_API_Data_DIR . 'products';

    $saveToLocal = new SaveToLocal($target, $filePath);
    if (isset($_POST['save_to_local'])) {
      Utils::putLog("Button Clicked: 'save_to_local'");
      call_user_func([$saveToLocal, 'saveJsonToFile']);
    }

    // Save to local button. this generate local file products_all.json 
    $saveToLocal_button = new AdminButton('save_to_local');
    $file_exist = $saveToLocal->getFilePath();
    ob_start();
  ?>
    <div>
      Save the json( <?php echo $target; ?>) to local directory <br><br><?php echo $filePath; ?>
      <br>
      <?php if (file_exists($filePath . '_raw.json')): ?>
        <a href="<?php echo MEC__CP_APIURL . 'all/'; ?>" target="_blank">See Products in raw data by API</a> Last modified: <?php echo $file_exist; ?><br>
      <?php endif; ?>
    </div>
    <?php
    $description = ob_get_clean();
    $html .= $saveToLocal_button->returnTableButtonHtml('get Json', '', $description);

    // -----------------------------------------------------------------------------------------------------

    // Seperate data all -> all, simple, variable, variant, variableWvariant?
    $json_prefix = 'products';
    $json_suffix = ['variable', 'variant', 'simple', 'extra', 'variable_variant'];
    $LocalJsonProcess = new PrepareJsonLocal($json_prefix, $json_suffix);
    if (isset($_POST['separate_data'])) {
      Utils::putLog("Button Clicked: 'separate_data'");
      call_user_func([$LocalJsonProcess, 'separate_data']);
    }
    $LocalJsonProcess_button = new AdminButton('separate_data');
    ob_start();
    ?>
    <div>
      if the all the processes from the upper buttons are succesfully finished, the endpoints automatically set
      <br><br>
      <?php foreach ($json_suffix as $type): ?>
        <?php if (file_exists($filePath . '_' . $type . '.json')): ?>
          <a href="<?php echo MEC__CP_APIURL . $type . '/'; ?>" target="_blank">See <?php echo $type; ?> Products by API</a><br>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
<?php
    $description = ob_get_clean();
    $html .= $LocalJsonProcess_button->returnTableButtonHtml('seperate data', '', $description);


    // Seperates data and save it local products. products overview. variable products mit variant. simple products, the rest
    if (isset($_POST['delete_separated_data'])) {
      Utils::putLog("Button Clicked: 'delete_separated_data'");
      call_user_func([$LocalJsonProcess, 'delete_separated_data']);
    }
    $LocalJsonProcess_delete_button = new AdminButton('delete_separated_data');

    $html .= $LocalJsonProcess_delete_button->returnTableButtonHtml('delete separated data', '', '');
    return $html;
  }

  public function create_products_actions_html()
  {
    $html = null;
    $WC_Handler = new WCHandler();

    //  TEST!! Create 6 Simple products 
    if (isset($_POST['create_products_simple6'])) {
      $num = 6;
      $start = 0;
      // Call the create_products method with arguments
      call_user_func_array([$WC_Handler, 'create_products'], ['wp_admin', 'simple',  $num, $start]);
    }
    $create_products_simple6_button = new AdminButton('create_products_simple6');
    $html .= $create_products_simple6_button->returnTableButtonHtml('create 6 simple products', '', 'or $wp create_products --num=6 --type=simple');


    // Create all simple Products 
    if (isset($_POST['create_products_simple'])) {
      call_user_func_array([$WC_Handler, 'create_products'], ['wp_admin', 'simple', null, null]);
    }
    // Save to local button. this generate local file products_all.json 
    $create_products_simple_button = new AdminButton('create_products_simple');
    $html .= $create_products_simple_button->returnTableButtonHtml('create all Simple products', '', 'or $wp create_products --num=-1 --type=simple');


    //  TEST!! Create 6 Varaible products mit variant
    if (isset($_POST['create_products_variable6'])) {
      $num = 6;
      $start = 0;
      // Call the create_products method with arguments
      call_user_func_array([$WC_Handler, 'create_products'], ['wp_admin', 'variable',  $num, $start]);
      // Redirect to avoid re-processing the form on refresh
      wp_redirect(admin_url('admin.php?page=your_page_slug')); // Replace with your desired page
      exit;
    }

    $create_products_variable6_button = new AdminButton('create_products_variable6');
    $html .= $create_products_variable6_button->returnTableButtonHtml('create 6 variable products', '', 'or $wp create_products --num=6 --type=variable');

    //  Create all Varaible mit variant
    if (isset($_POST['create_products_variable'])) {
      call_user_func_array([$WC_Handler, 'create_products'], ['wp_admin', 'variable', null, null]);
    }
    // Save to local button. this generate local file products_all.json 
    $create_products_variable_button = new AdminButton('create_products_variable');
    $html .= $create_products_variable_button->returnTableButtonHtml('create all variable products with variants', '', 'or $wp create_products --num=-1 --type=variable');


    //  Delete all Button 
    $sqlHandler = new SQLscript();
    if (isset($_POST['delete_all_products'])) {
      call_user_func([$sqlHandler, 'delete_all_products']);
    }
    // Save to local button. this generate local file products_all.json 
    $delete_all_products_button = new AdminButton('delete_all_products');
    $html .= $delete_all_products_button->returnTableButtonHtml('delete all products', '', 'click button or $wp delete_all_products');




    return $html;
  }
}
