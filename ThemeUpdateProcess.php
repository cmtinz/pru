<?php

class ThemeUpdateProcess extends UpdateProcess
{

    public function process() {
        global $wp_filesystem;
        
        try {
            $this->unzip_component();
            /* Delete old theme folder */
            $theme_folder = WP_CONTENT_DIR . '/themes/' . $this->base_name;
            $delete_folder = $wp_filesystem->rmdir($theme_folder, true);
            if ($delete_folder === false) {
                throw new Exception('Unable to delete old theme folder');
            }

            /* Move new theme */
            $source_folder = $this->random_folder . $this->base_name;
            $destination_folder = WP_CONTENT_DIR . '/themes/' . $this->base_name;
            $move_folder = rename($source_folder, $destination_folder);
            if ($move_folder === false) {
                throw new Exception('Unable to move the new theme to destination folder');
            }


          } catch (Exception $e) {
            return new WP_Error( 'ThemeUpdateProcess error', __($e->getMessage()), array( 'status' => 500 ) );
          }
          
          return [
            'success' => true,
            'base_name' => $this->base_name,
            'random_folder' => $this->random_folder,
          ];
    }

}