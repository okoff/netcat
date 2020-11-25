<?php

/* $Id: nc_catalogue.class.php 7901 2012-07-31 18:07:06Z alive $ */
if (!class_exists("nc_System")) die("Unable to load file.");

class nc_Catalogue extends nc_Essence {

    protected $db;

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

        $this->load_all();
    }

    public function load_all() {
        // load all catalogues
        $this->data = array();
        $res = $this->db->get_results("SELECT * FROM `Catalogue` ORDER BY `Priority`", ARRAY_A);

        if (!empty($res))
                foreach ($res as $v) {
                $this->data[$v['Catalogue_ID']] = $v;
                $this->data[$v['Catalogue_ID']]['_nc_final'] = 0;
            }
    }

    /**
     *
     */
    public function get_by_id($id, $item = "", $reset = false) {
        if (!$id) {
            return null;
        }
        if ($reset) $this->load_all();

        if (!$this->data[$id]) {
            throw new Exception("Catalog with id  ".$id." does not exist");
        }

        if (!$this->data[$id]['_nc_final']) {
            $this->data[$id] = $this->convert_system_vars($this->data[$id], $reset);
            $this->data[$id]['_nc_final'] = 1;
        }

        if ($item) {
            return array_key_exists($item, $this->data[$id]) ? $this->data[$id][$item] : "";
        }

        return $this->data[$id];
    }

    public function get_all() {
        return $this->data;
    }

    /**
     * Get catalogue data by the hostname
     *
     * @param string $host
     * @param bool use returned value as current catalogue data
     * @param bool reset stored data in the static variable
     *
     * @return catalogue data associative array
     */
    public function get_by_host_name($host, $current = false, $reset = false) {

        $catalogue_id = 0;

        // поиск по доменом и зеркалам
        foreach ($this->data as $catalog) {
            $domain = $catalog['Domain'];
            $mirrors = $catalog['Mirrors'] ? str_replace(array('http://', '/'), '', $catalog['Mirrors']) : "";
            if (nc_preg_match("/^(?:".$domain."|".nc_preg_replace("/\r\n/", "|", preg_quote($mirrors)).")$/", $host)) {
                $catalogue_id = $catalog['Catalogue_ID'];
            }

            if ($catalog['Checked']) {
                $chn = isset($expected_id) ? $catalog['Priority'] < $this->data[$expected_id]['Priority'] : 0;
                $expected_id = isset($expected_id) ? ( $chn ? $catalog['Catalogue_ID'] : $expected_id ) : $catalog['Catalogue_ID'];
            }
        }

        // поиск по приоритету
        if (!$catalogue_id && ($expected_id || !empty($this->data) )) {
            $catalogue_id = $expected_id ? $expected_id : $this->data[min(array_keys($this->data))]['Catalogue_ID'];
        }

        $res = $this->get_by_id($catalogue_id);

        if ($current) {
            // set current catalogue data
            $this->current = $res;
        }

        // return result
        return $res;
    }
    
    
    public function get_mobile($id = 0, $current = false, $item = '') {
        $id = $current ? $this->current['Catalogue_ID'] : intval($id);
        
        foreach ($this->data as $catalog) {
            if ($catalog['ncMobileSrc'] == $id) {
                $mobile_data = $catalog;
            }
        }

        if ($item) {
            return array_key_exists($item, $mobile_data) ? $mobile_data[$item] : "";
        }

        return $mobile_data;
    }
}
?>
