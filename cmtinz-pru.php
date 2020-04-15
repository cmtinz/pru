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
require_once('ThemeUpdateProcess.php');
require_once('PluginUpdateProcess.php');

add_action( 'rest_api_init', function () {
    register_rest_route( 'cmtinz/v1', '/update-request', array(
      'methods' => 'POST',
      'callback' => 'update_request',
      'args' => [
        'type' => [
          'required' => true,
          "type" => "string"
        ],
        'name' => [
          'required' => true,
          "type" => "string"
        ],
        'slug' => [
          'required' => true,
          'type' => 'string'
        ],
        'signature' => [
          'required' => true,
          "type" => "string"
        ],
      ]
    ) );
  } );


function update_request( WP_REST_Request $request ) {
  
  $files = $request->get_file_params();
  $type = $request->get_param('type');
  $name = $request->get_param('name');
  $slug = $request->get_param('slug');
  $checksum = $request->get_param('checksum');
  $signature = $request->get_param('signature');

  if (!component_exists($type, $name, $slug)) {
    return new WP_Error( 'component_not_found', __('The plugin or theme was not found.'), array( 'status' => 400 ) );
  }

  if (count($files) != 1) {
    return new WP_Error( 'file_not_provided', __('The plugin file was not provided.'), array( 'status' => 400 ) );
  }
  
  $file = array_shift($files);

  if (!verify_file_type($file)) {
    return new WP_Error( 'invalid_mime_type', __('The plugin file provided does not match the required MIME type.'), array( 'status' => 400 ) );
  }

  $public_key = get_option('_pru_public_key');
  if (!$public_key) {
    return new WP_Error( 'public_key_unset', __('The public key has been not set in the server.'), array( 'status' => 500 ) );
  }

  if (verify_sigurature($file, $signature, $public_key) === 0) {
    return new WP_Error( 'bad_signtature', __('The signature can not be verified.'), array( 'status' => 400 ) );
  }

  switch ($type) {
    case "plugin":
      $update_process = new PluginUpdateProcess($file, $slug);
      return $update_process->process();
    break;
    case "theme":
      $update_process = new ThemeUpdateProcess($file, $slug);
      return $update_process->process();
    break;
  }

  
}

function component_exists($type, $name, $slug) {

  switch ($type) {
    case "plugin":
      if ( !function_exists( 'get_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
      }
      $installed_plugins = get_plugins();
      return array_reduce($installed_plugins, function ($acc, $plugin) use ($name) {
        return $acc || $plugin['Name'] == $name;
      });
    break;
    case "theme":
      $theme = wp_get_theme($slug);
      return $theme->exists();
  }
}

function verify_file_type($file) {

  if ($file['type'] === 'application/zip') {
    return true;
  }

  return false;

}

function verify_sigurature($file, $signature, $public_key) {

  $file_data = file_get_contents($file['tmp_name']);
  $decoded_signature = base64_decode($signature);
  $verifaction = openssl_verify($file_data, $decoded_signature, $public_key, "SHA256");

  if ($verifaction === -1) {
    return new WP_Error( 'signature_verification_error', __('Signature verification error.'), array( 'status' => 400 ) );
  }
  
  return $verifaction;

}