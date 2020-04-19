<?php
/**
 * Plugin Name:     Private Remote Update
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     Allows to remotely update Wordpress themes and plugins using the REST API.
 * Author:          Carlos Martínez P.
 * Author URI:
 * Text Domain:     cmtinz-pru
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         cmtinz/pru
 */

// Your code starts here  

require_once('UpdateProcess.php');
require_once('UpdateRequest.php');
require_once('ThemeUpdateProcess.php');
require_once('PluginUpdateProcess.php');

add_action( 'rest_api_init', function () {

  $updateRequest = new UpdateRequest();
  $updateRequest->registerRoutes();

});