<?php

namespace MEC__CreateProducts\Utils;


class AdminButton
{
  private $log;
  private $action_name;

  public function __construct($action_name)
  {
    $this->action_name = $action_name;
  }

  public function returnButtonHtml($button_text = '', $button_type = '', $description_html = '')
  {
    $html = null;
    if ($button_text == '') {
      $button_text = $this->action_name;
    }
    if ($button_type == '') {
      $button_type = 'primary';
    }
    if ($description_html == '') {
      $description_html = 'This button for the follwoing action: ' . $this->action_name;
    }
    ob_start();
?>
    <form method="post" action="">
      <?php submit_button($button_text, $button_type, $this->action_name); ?>
      <?php echo $description_html; ?>
    </form>
  <?php

    $html .= ob_get_clean();
    return $html;
  }



  public function returnTableButtonHtml($button_text = '', $button_type = '', $description_html = '')
  {
    $html = null;
    $html = null;
    if ($button_text == '') {
      $button_text = $this->action_name;
    }
    if ($button_type == '') {
      $button_type = 'primary';
    }
    if ($description_html == '') {
      $description_html = 'This button for the follwoing action: ' . $this->action_name;
    }
    ob_start();
  ?>
    <tr style="border-top: gray dotted;">
      <th><?php echo $this->action_name; ?></th>
      <td>
        <form method="post" action="">
          <?php submit_button($button_text, $button_type, $this->action_name); ?>
        </form>
        <?php echo $description_html; ?>
      </td>
    </tr>
<?php
    $html .= ob_get_clean();
    return $html;
  }
}
