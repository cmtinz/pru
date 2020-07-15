<?php 

class PluginUpdateProcess extends UpdateProcess {

private $old_plugin_metadata;
private $new_plugin_metadata;
private $is_new_plugin_a_file;
private $is_old_plugin_a_file;

public function process() {
  try {
    $this->unzip_component();
    $this->is_old_plugin_a_file = $this->is_file_plugin(ABSPATH . 'wp-content/plugins/', $this->base_name);
    $this->is_new_plugin_a_file = $this->is_file_plugin($this->random_folder, $this->base_name);
    $this->update_process();
  } catch (Exception $e) {
    return new WP_Error( 'Plugin_Update_Process error', __($e->getMessage()), array( 'status' => 500 ) );
  }
  
  return [
    'success' => true,
    'base_name' => $this->base_name,
    'random_folder' => $this->random_folder,
    'old_plugin_metadata' => $this->old_plugin_metadata,
    'new_plugin_metadata' => $this->new_plugin_metadata,
  ];
}

private function update_process() {

  WP_Filesystem();
  global $wp_filesystem;

  /* Delete old plugin */
  switch ($this->is_old_plugin_a_file) {

    case true:
      $plugin_file = ABSPATH . 'wp-content/plugins/' . $this->base_name . '.php';
      $this->old_plugin_metadata = get_plugin_data($plugin_file, false, false);
      $delete_file = unlink($plugin_file);
      if ($delete_file === false) {
        throw new Exception('Unable to delete old plugin file');
      }
    break;

    case false:
      $plugin_folder = ABSPATH . 'wp-content/plugins/' . $this->base_name;
      $this->old_plugin_metadata = get_plugin_data($plugin_folder . '/' . $this->base_name . '.php', false, false);
      $delete_folder = $wp_filesystem->rmdir($plugin_folder, true);
      if ($delete_folder === false) {
        throw new Exception('Unable to delete old plugin folder');
      }
    break;

  }

  /* Move the new plugin */
  switch ($this->is_new_plugin_a_file) {
    
    case true:
      $plugin_file_source = $this->random_folder . $this->base_name . '.php';
      $plugin_file_destination = ABSPATH . 'wp-content/plugins/'. $this->base_name . '.php';
      $move_file = rename($plugin_file_source, $plugin_file_destination);
      if ($move_folder === false) {
        throw new Exception('Unable to move the new plugin to destination folder');
      }
      $this->new_plugin_metadata = get_plugin_data($plugin_file_destination, false, $false);
    break;
    
    case false:
      $plugin_folder_source = $this->random_folder . $this->base_name;
      $plugin_folder_destination = ABSPATH . 'wp-content/plugins/' . $this->base_name;
      $plugin_file = $plugin_folder_destination . '/' . $this->base_name . '.php';
      $move_folder = rename($plugin_folder_source, $plugin_folder_destination);
      if ($move_folder === false) {
        throw new Exception('Unable to move the new plugin to destination folder');
      }
      $this->new_plugin_metadata = get_plugin_data($plugin_file, false, $false);
    break;

  }

}

  private function is_file_plugin($folder, $slug) {
    
    if (file_exists($folder . $slug . ".php")) {
      return true;
    }
    if (file_exists($folder . $slug)) {
      return false;
    }

    throw new Exception('Invalid plugin');

  }

}