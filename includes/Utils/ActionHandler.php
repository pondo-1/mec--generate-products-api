<?php

namespace MEC__CreateProducts\Utils;

use MEC__CreateProducts\Utils\Utils;
use \WP_CLI;

class ActionHandler
{
  private $log;
  private $button_label;
  private $action_name;
  private $cli_command;
  private $callback;

  public function __construct($button_label, $action_name, $cli_command, $callback)
  {
    $this->log = Utils::getLogger(); // Get logger instance
    $this->button_label = $button_label;
    $this->action_name = $action_name;
    $this->cli_command = $cli_command;
    $this->callback = $callback;

    // Register the handler for the button in the admin
    add_action('admin_init', [$this, 'registerButtonHandler']);
  }

  // Generates the button HTML in the admin page
  public function renderButton()
  {
?>
    <form method="post" action="">
      <?php \submit_button($this->button_label, 'primary', $this->action_name); ?>
    </form>
<?php
  }

  // Registers the action handler for the button in the admin area
  public function registerButtonHandler()
  {
    if (isset($_POST[$this->action_name])) {
      call_user_func($this->callback, "wp_admin");

      // Admin notice to confirm the action
      add_action('admin_notices', function () {
        echo '<div class="notice notice-success is-dismissible"><p>Action completed: ' . esc_html($this->button_label) . '</p></div>';
      });
    }
  }
}
