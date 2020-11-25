<?php

if (!class_exists("nc_System")) die("Unable to load file.");

class nc_Files extends nc_System {

    protected $core;

    public function __construct() {
        // load parent constructor
        parent::__construct();
        $this->core = nc_Core::get_object();
    }

    public function create_dir($fullpath) {
        $nc_core = nc_Core::get_object();

        if (is_dir($fullpath)) return true;

        if (($res = mkdir($fullpath, $nc_core->DIRCHMOD, 1))) {
            chmod($fullpath, $nc_core->DIRCHMOD);
        }

        return $res;
    }

    public function delete_dir($path) {
        if (!file_exists($path) || !is_dir($path)) return false;

        if (!is_writable($path)) throw new nc_Exception_Files_Not_Rights($path);

        foreach (scandir($path) as $v) {
            if ($v == "." || $v == "..") continue;

            if (filetype($path."/".$v) == "dir") {
                $this->delete_dir($path."/".$v);
            } else {
                if (!is_writable($path."/".$v))
                        throw new nc_Exception_Files_Not_Rights($path."/".$v);
                unlink($path."/".$v);
            }
        }

        rmdir($path);

        return true;
    }

    public function save_file($class_id, $field, $message_id, $file) {
        $component = new nc_Component($class_id, 3);
        $fields = $component->get_fields(NC_FIELDTYPE_FILE);
        $message_id = intval($message_id);
        if (!empty($fields))
                foreach ($fields as $v) {
                if ($v['id'] == $field || $v['name'] == $field) {
                    $format = $v['format'];
                    $field_id = $v['id'];
                    $field_name = $v['name'];
                }
            }

        $format = nc_field_parse_format($format, NC_FIELDTYPE_FILE);

        $filename = $file['name'];
        $filetype = "image/jpeg";
        $message_id = intval($message_id);




        switch ($format['fs']) {
            case NC_FS_PROTECTED: // hash
                // имя файла
                $put_file_name = md5($filename.date("H:i:s d.m.Y").uniqid("netcat"));
                // путь
                $file_path = "u/";
                break;
            case NC_FS_ORIGINAL:
                $put_file_name = nc_transliterate($filename);
                $file_path = "u/";
                $put_file_name = nc_get_filename_for_original_fs($put_file_name, $this->core->FILES_FOLDER.$file_path, array());
                // то, что пойдет в БД
                $value = $file_path.$put_file_name;
                break;
            case NC_FS_SIMPLE: // FieldID_MessageID.ext
                $put_file_name = $field_id."_".$message_id.".jpg";
                $file_path = ''; // в папку netcat_files
                break;
        }

        $buf = file_get_contents($file['path']);

        $full_path = $this->core->FILES_FOLDER.$file_path.$put_file_name;
        file_put_contents($full_path, $buf);

        $value = $this->core->db->escape($filename.":".$filetype.":".filesize($full_path).( $value ? ":".$value : ""));

        $this->core->db->query("UPDATE `User` SET `".$field_name."` = '".$value."' WHERE `User_ID` = '".$message_id."' ");

        if ($format['fs'] == NC_FS_PROTECTED) {
            $this->core->db->query("INSERT INTO `Filetable` SET
        `Real_Name` = '".$this->core->db->escape($filename)."',
        `Virt_Name` = '".$put_file_name."',
        `File_Path` = '/u/',
        `File_Type` = '".$filetype."',
        `File_Size` = '".filesize($full_path)."',
        `Message_ID` = '".$message_id."',
        `Field_ID` = '".$field_id."' ");
        }
    }

}