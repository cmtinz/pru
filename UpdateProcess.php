<?php 

class UpdateProcess
{
    protected $file;
    protected $base_name;
    protected $random_folder;

    public function __construct($file, $base_name) {

      if (empty($base_name)) {
        throw new Exception('Empety base_name parameter');
      }
    
      if (!file_exists($file['tmp_name'])) {
        throw new Exception('Uploaded file does not exist.');
      }
    
      $this->file = $file;
      $this->base_name = $base_name;
    
    }

    protected function unzip_component() {

        if ( !function_exists( 'WP_Filesystem' ) ) {
          require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        WP_Filesystem();
      
        $random_string =  base64_encode(random_bytes(8));
        $random_string = str_replace(['+', '/', '='], '', $random_string);
        $this->random_folder = get_temp_dir() . "$random_string.pru/";
      
        $result = unzip_file($this->file['tmp_name'], $this->random_folder);
      
        if ($result !== true) {
          throw new Exception('Unable to expand file. ' . $result->get_error_message());
        }
      
    }

}