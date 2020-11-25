<?php

class nc_tpl {

    public $fields = null;
    public $relative_path = null;
    public $absolute_path = null;
    public $id = null;
    public $count_parent = null;
    public $extension = '.html';
    public $child = null;
    public $type = null;
    public $db = null;
    public $path_to_root_folder = null;

    public function __construct($path, nc_Db $db, $type = null) {
        $this->path_to_root_folder = $path;
        $this->db = $db;
        $this->type = $type;
    }

    public function load($id, $type, $relative_path = null) {
        // check path
        #if (!$id) {
		#	nc_print_status(NETCAT_TEMPLATE_FILE_NOT_FOUND, 'error');
		#	exit;
		#}
        $this->id = $id;
        $this->type = $type;
        $this->relative_path = $relative_path ? $relative_path : $this->select_relative_path();
        // check path
        #if (!$this->relative_path) {
		#	nc_print_status(NETCAT_TEMPLATE_FILE_NOT_FOUND, 'error');
		#	exit;
		#}
        $this->absolute_path = $this->get_absolute_path_to_template();
        $this->count_parent = $this->count_parent_template();
        $this->fields = new nc_fields($this);
    }

    private function select_relative_path() {
        if ( !($this->type && $this->id) ) return false;
        $SQL = "SELECT File_Path
                    FROM {$this->type}
                        WHERE {$this->type}_ID = {$this->id}";
        return $this->db->get_var($SQL);
    }

    private function get_absolute_path_to_template() {
        return nc_standardize_path_to_folder($this->path_to_root_folder.'/'.$this->relative_path);
    }

    private function count_parent_template() {
        return count(array_diff(explode('/', trim($this->relative_path, '/')), array('')));
    }

    public function load_child($id) {
        $this->child = new nc_tpl(nc_standardize_path_to_folder($this->path_to_root_folder), $this->db);
        $this->child->load($id, $this->type, nc_standardize_path_to_folder($this->relative_path.'/'.$id));
    }

    public function update_file_path_and_mode() {
        if ( !($this->type && $this->id && $this->relative_path) ) return false;
        $SQL = "UPDATE {$this->type}
                    SET File_Path = '{$this->relative_path}',
                        File_Mode = 1
                        WHERE {$this->type}_ID = {$this->id}";
        return $this->db->query($SQL);
    }
	
	public function get_id() {
		return $this->id;
	}
	
	public function delete_template_file_and_folder($path = '') {
		// path
		$path = ($path ? $path : $this->absolute_path);
		// check path
		if ( !is_dir($path) ) return false;
		
		// error when trying to delete template folder
        if ( nc_standardize_path_to_folder($this->path_to_root_folder) == $path ) {
			// warning message
			nc_print_status( sprintf(NETCAT_TEMPLATE_DIR_DELETE_ERROR, $path), 'error');
			return false;
		}

		// variables
		$files = nc_double_array_shift( scandir($path) );
		$directory = nc_standardize_path_to_folder($path);

		foreach ($files as $file) {
			$full_path = $directory . $file;
			// check file existance
			if ( !file_exists($full_path) ) continue;
			// file / dir
			if (is_dir($full_path)) {
				$this->delete_template_file_and_folder($full_path);
			} else {
				// delete file
				unlink($full_path);
			}
		}
		// delete dir
		if ( is_dir($directory) ) rmdir($directory);
	}
}