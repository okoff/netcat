<?php

/* $Id: nc_sub_class.class.php 8455 2012-11-23 09:28:35Z aix $ */
if (!class_exists("nc_System")) die("Unable to load file.");

class nc_Sub_Class extends nc_Essence {

    protected $db;
    private $_current_id;

    /**
     * Constructor function
     */
    public function __construct() {
        // load parent constructor
        parent::__construct();

        // system superior object
        $nc_core = nc_Core::get_object();
        // system db object
        if (is_object($nc_core->db)) $this->db = $nc_core->db;
    }

    /**
     * Get subclass data with system table flag from `Class` table
     *
     * @param Subdivision_ID, if not setted - using current value in query
     * @param bool use returned value as current subclass data
     * @param bool reset stored data in the static variable
     *
     * @return subclass data associative array
     */
    public function get_by_subdivision_id($id = 0, $reset = false) {
        // system superior object
        $nc_core = nc_Core::get_object();

        // call as static
        static $storage = array();

        // validate parameters
        $id = intval($id);
        if (!$id && is_object($nc_core->subdivision)) {
            $id = $nc_core->subdivision->get_current("Subdivision_ID");
        }

        if (!$id) return false;

        // check inited object
        if (empty($storage[$id]) || $reset) {

            $storage[$id] = $this->db->get_results("SELECT b.*, c.`System_Table_ID` AS sysTbl
      FROM `Sub_Class` AS b
      LEFT JOIN `Class` AS c ON b.`Class_ID` = c.`Class_ID`
      WHERE b.`Subdivision_ID` = '".$id."'
      ORDER BY b.`Checked`, b.`Priority`", ARRAY_A);

            if (!empty($storage[$id]))
                    foreach ($storage[$id] as $v) {
                    $this->data[$v['Sub_Class_ID']] = $v;
                    $this->data[$v['Sub_Class_ID']]['_nc_final'] = 0;
                }
        }

        return $storage[$id];
    }

    /**
     * Set current subclass data by the id
     *
     * @param int subclass id
     * @param bool reset stored data in the static variable
     *
     * @return setted current cc id
     */

    public function set_current_by_id($id, $reset = false) {

        // validate
        $id = intval($id);
        if (!$id) return ($this->current = array());
        try {
            //if ($id) {
            $this->current = $this->get_by_id($id, "");
            // set additional data
            $this->_current_id = $id;
            // return result
            return $this->current;
            //}
        } catch (Exception $e) {
            nc_print_status($e->getMessage(), 'error');
         }

        // reject
        return false;
    }

    public function get_by_id($id, $item = "", $nc_ctpl = 0, $reset = 0) {
        // call as static
        static $storage = array();
        // validate parameters
        $id = intval($id);
        //$nc_ctpl = intval($nc_ctpl);
        // check inited object
        if (empty($storage[$id][$nc_ctpl]) || $reset) {
            if (!empty($this->data[$id])) {
                $storage[$id][$nc_ctpl] = $this->data[$id];
            } else {

                $this->data[$id] = $this->db->get_row("SELECT * FROM `".$this->essence."` WHERE `".$this->essence."_ID` = '".$id."'", ARRAY_A);
                $real_value = array('Read_Access_ID', 'Write_Access_ID', 'Edit_Access_ID', 'Delete_Access_ID', 'Checked_Access_ID', 'Moderation_ID', 'Cache_Access_ID', 'Cache_Lifetime');
                foreach ($real_value as $v) {
                    $this->data[$id]['_db_'.$v] = $this->data[$id][$v];
                }
                $storage[$id][$nc_ctpl] = $this->data[$id];
            }

            $storage[$id][$nc_ctpl] = $this->inherit($storage[$id][$nc_ctpl], $nc_ctpl);
        }

        // if item requested return item value
        if ($item && is_array($storage[$id][$nc_ctpl])) {
            return array_key_exists($item, $storage[$id][$nc_ctpl]) ? $storage[$id][$nc_ctpl][$item] : "";
        }
        
        return $storage[$id][$nc_ctpl];
    }

    protected function inherit($cc_env, $nc_ctpl = 0) {
        // system superior object
        $nc_core = nc_Core::get_object();

        if (empty($cc_env)) {
            global $perm;
            // error message
            if (is_object($perm) && $perm->isSupervisor()) {
                // backtrace info
                $debug_backtrace_info = debug_backtrace();
                // choose error
                if (isset($debug_backtrace_info[2]['function']) && $debug_backtrace_info[2]['function'] == "nc_objects_list") {
                    // error info for the supervisor
                    trigger_error(sprintf(NETCAT_FUNCTION_OBJECTS_LIST_CC_ERROR, $debug_backtrace_info[2]['args'][1]), E_USER_WARNING);
                } else {
                    // error info for the supervisor
                    trigger_error(sprintf(NETCAT_FUNCTION_LISTCLASSVARS_ERROR_SUPERVISOR, $cc), E_USER_WARNING);
                }
            }

            return NULL;
        }

        if ($cc_env['Class_Template_ID'] && !$nc_ctpl) {
            $nc_ctpl = $cc_env['Class_Template_ID'];
        }

        $class_env = $nc_core->component->get_for_cc($cc_env['Sub_Class_ID'], $cc_env['Class_ID'], $nc_ctpl);

        foreach ($class_env AS $key => $val) {
            if (!array_key_exists($key, $cc_env) || $cc_env[$key] == "") {
                $cc_env[$key] = $val;
            }
        }

        if ($cc_env["NL2BR"] == -1) {
            $cc_env["NL2BR"] = $class_env["NL2BR"];
        }

        if ($cc_env["AllowTags"] == -1) {
            $cc_env["AllowTags"] = $class_env["AllowTags"];
        }

        if ($cc_env["UseCaptcha"] == -1) {
            $cc_env["UseCaptcha"] = $class_env["UseCaptcha"];
        }

        if ($nc_core->modules->get_by_keyword("cache")) {
            if ($cc_env["CacheForUser"] == -1) {
                $cc_env["CacheForUser"] = $class_env["CacheForUser"];
            }
        }

        if ($class_env['CustomSettingsTemplate']) {
            require_once ($nc_core->ADMIN_FOLDER."array_to_form.inc.php");
            $a2f = new nc_a2f($class_env['CustomSettingsTemplate'], 'CustomSettings');
            $a2f->set_value($cc_env['CustomSettings']);
            $cc_env["Sub_Class_Settings"] = $a2f->get_values_as_array();
        }

        $cc_env['sysTbl'] = intval($class_env['System_Table_ID']);

        $sub_env = $nc_core->subdivision->get_by_id($cc_env["Subdivision_ID"]);

        $inherited_params = array('Read_Access_ID', 'Write_Access_ID', 'Edit_Access_ID', 'Checked_Access_ID',
                'Delete_Access_ID', 'Subscribe_Access_ID', 'Moderation_ID');
        if ($nc_core->modules->get_by_keyword("cache")) {
            $inherited_params[] = 'Cache_Access_ID';
            $inherited_params[] = 'Cache_Lifetime';
        }

        foreach ($inherited_params as $v) {
            if (!$cc_env[$v]) $cc_env[$v] = $sub_env[$v];
        }

        $cc_env['Subdivision_Name'] = $sub_env['Subdivision_Name'];
        $cc_env['Hidden_URL'] = $sub_env['Hidden_URL'];

        $Domain = $nc_core->catalogue->get_by_id($cc_env['Catalogue_ID'], 'Domain');
        $cc_env['Hidden_Host'] = $Domain ? $Domain : $nc_core->DOMAIN_NAME;

        return $cc_env;
    }

    public function validate_english_name($str) {
	// Check string length: database scheme stores up to 64 characters.    
	if (mb_strlen($str)>64) return 0;
        // validate Hidden_URL
        return nc_preg_match("/^[a-z".NETCAT_RUALPHABET."0-9-]+$/i", $str);
    }

    public function get_mirror ($id, $item = '') {
        $res = array();
        foreach ($this->data as $v) {
            if ($v['SrcMirror'] == $id ) {
                if ($item) return array_key_exists($item, $v) ? $v[$item] : "";
                $res = $v;
            }
        }

        return $res;
    }

}


?>
