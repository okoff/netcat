<?php

/* $Id: collection.php 8366 2012-11-07 16:30:14Z aix $ */

/**
 * 
 */
class nc_search_data_persistent_collection extends nc_search_data_collection {

    protected $data_class = '';
    protected static $table_name;
    /**
     * @see self::load_all()
     * @var array
     */
    static protected $cache_all = array();

    /**
     *
     * @return string
     */
    public function get_table_name() {
        $class = $this->data_class;
        if (!isset(self::$table_name[$class])) {
            // PHP 5.3 will make such things beautiful; I miss the 'static::' et cetera
            $dummy_item = new $class;
            self::$table_name[$class] = $dummy_item->get_table_name();
        }
        return self::$table_name[$class];
    }

    /**
     *
     * @return nc_Db
     */
    protected function get_db() {
        return nc_Core::get_object()->db;
    }

    /**
     *
     * @param string $data_class
     * @param boolean $force_reload
     * @param string $index_by присвоить ключам элементов коллекции значение опции $index_by
     * @return nc_search_data_persistent_collection
     * @throws nc_search_data_exception
     */
    static public function load_all($data_class, $force_reload = false, $index_by = null) {
        if (!class_exists($data_class) || !is_subclass_of($data_class, "nc_search_data_persistent")) {
            throw new nc_search_data_exception("Wrong data class '$data_class' passed to nc_search_data_persistent_collection::load_all()");
        }

        if ($force_reload || !isset(self::$cache_all[$data_class])) {
            self::$cache_all[$data_class] = $collection = new self();
            $collection->set_data_class($data_class)
                    ->set_index_option($index_by)
                    ->select_from_database("SELECT * FROM `%t%`");
        }
        return self::$cache_all[$data_class];
    }

    /**
     *
     * @param string $query  SQL query
     *   Вместо имени таблицы можно использовать '%t%'
     * @return nc_search_data_persistent_collection
     */
    public function select_from_database($query) {
        $nc_core = nc_Core::get_object();
        $data_class = $this->get_data_class();
        $query = str_replace("%t%", $this->get_table_name(), $query);
        $db = $this->get_db();
        if ($nc_core->MYSQL_CHARSET != 'utf8') $db->query("SET NAMES 'utf8'");
        $result = $db->get_results($query, ARRAY_A);
        if ($nc_core->MYSQL_CHARSET != 'utf8')
                $db->query("SET NAMES '".$nc_core->MYSQL_CHARSET."'");

        if ($db->is_error) {
            throw new nc_search_data_exception("Cannot load items from the database: '$db->last_error'");
        }
        if (sizeof($result)) {
            foreach ($result as $row) {
                $item = new $data_class();
                $item->set_values_from_database_result($row);
                $this->add($item);
            }
        }
        return $this;
    }

    /**
     *
     * @param string $data_class
     * @param string $query
     * @param string $index_by присвоить ключам элементов коллекции значение опции $index_by
     * @return nc_search_data_persistent_collection
     * @throws nc_search_data_exception
     */
    static public function load($data_class, $query, $index_by = null) {
        if (!class_exists($data_class) || !is_subclass_of($data_class, "nc_search_data_persistent")) {
            throw new nc_search_data_exception("Wrong data class '$data_class' passed to nc_search_data_persistent_collection::load()");
        }

        $collection = new self;
        $collection->set_data_class($data_class)
                ->set_index_option($index_by)
                ->select_from_database($query);
        return $collection;
    }

}
