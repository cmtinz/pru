<?php

class UpdateRequest extends WP_REST_Controller
{
    public function registerRoutes()
    {
        $version = '1';
        $namespace = 'cmtinz/v' . $version;
        $args = array(
            'name' => [
                'required' => true,
                "type" => "string"
            ],
            'baseName' => [
                'required' => true,
                'type' => 'string'
            ],
            'signature' => [
                'required' => true,
                "type" => "string"
            ]
        );
        register_rest_route($namespace, '/pru/plugin', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'pluginUpdateRequet' ),
            'args'                => $args,
        ));
        register_rest_route($namespace, '/pru/theme', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'themeUpdateRequet' ),
            'args'                => $args,
        ));
    }

    public function pluginUpdateRequet(WP_REST_Request $request)
    {
        return $this->requestValidation($request, 'plugin');
    }
    
    public function themeUpdateRequet(WP_REST_Request $request)
    {
        return $this->requestValidation($request, 'theme');
    }

    private function requestValidation(WP_REST_Request $request, $type) {
  
        $files = $request->get_file_params();
        $name = $request->get_param('name');
        $baseName = $request->get_param('baseName');
        $signature = $request->get_param('signature');
      
        if (!$this->component_exists($type, $name, $baseName)) {
          return new WP_Error( 'component_not_found', __('The plugin or theme was not found.'), array( 'status' => 400 ) );
        }
      
        if (count($files) != 1) {
          return new WP_Error( 'file_not_provided', __('The plugin file was not provided.'), array( 'status' => 400 ) );
        }
        
        $file = array_shift($files);
      
        if (!$this->verify_file_type($file)) {
          return new WP_Error( 'invalid_mime_type', __('The plugin file provided does not match the required MIME type.'), array( 'status' => 400 ) );
        }
      
        $public_key = get_option('_pru_public_key');
        if (!$public_key) {
          return new WP_Error( 'public_key_unset', __('The public key has been not set in the server.'), array( 'status' => 500 ) );
        }
      
        if ($this->verify_sigurature($file, $signature, $public_key) === 0) {
          return new WP_Error( 'bad_signtature', __('The signature can not be verified.'), array( 'status' => 400 ) );
        }
      
        switch ($type) {
          case "plugin":
            $update_process = new PluginUpdateProcess($file, $baseName);
            return $update_process->process();
          break;
          case "theme":
            $update_process = new ThemeUpdateProcess($file, $baseName);
            return $update_process->process();
          break;
        }
        
      }

    public function component_exists($type, $name, $slug) {

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
      
    public function verify_file_type($file) {
    
        if ($file['type'] === 'application/zip') {
        return true;
        }
    
        return false;
    
    }
      
    public function verify_sigurature($file, $signature, $public_key) {
    
        $file_data = file_get_contents($file['tmp_name']);
        $decoded_signature = base64_decode($signature);
        $verifaction = openssl_verify($file_data, $decoded_signature, $public_key, "SHA256");
        
        if ($verifaction === -1) {
            return new WP_Error( 'signature_verification_error', __('Signature verification error.'), array( 'status' => 400 ) );
        }
        
        return $verifaction;
    
    }
}