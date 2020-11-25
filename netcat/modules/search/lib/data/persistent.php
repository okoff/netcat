<?php

/* $Id: persistent.php 8366 2012-11-07 16:30:14Z aix $ */

/**
 * A primitive Active Record
 */
abstract class nc_search_data_persistent extends nc_search_data {

    protected static $io_encode = "utf-8";
 // входная/выходная кодировка "по умолчанию"
    protected $primary_key = "id";
    /**
     * Key: option name
     * К сожалению, подобное свойство необходимо из-за того, что в Netcat разные
     * соглашения о правиле наименований в PHP (small_underscored) и БД (CamelCase)
     * Value: sql column name
     *
     * Можно так:  $mapping = array("id" => "Smth_ID", "_generate"=>true);
     * остальные поля будут сгенерированы автоматически из опций
     *
     * @var array
     */
    protected $mapping = array();
    /**
     * Исключить из генерируемого mapping указанные опции
     * @var array
     */
    protected $mapping_exclude = array();
    /**
     * Имя таблицы в БД. Должно быть обязательно определено!
     * @var string
     */
    protected $table_name = "";
    /**
     * @see self::generate_mapping()
     * @var array
     */
    static protected $mapping_cache = array();
    /**
     * Опции, которые перед сохранением в БД должны быть сериализованы
     * @var array
     */
    protected $serialized_options = array();


    //////////////////////////////////////////////////////////////////////////////

    /**
     * Получение/установка кодировки
     */
    static public function io_encode($encode = null) {
        if ($encode) self::$io_encode = $encode;

        return self::$io_encode;
    }

    //////////////////////////////////////////////////////////////////////////////

    public function __construct(array $values = null) {
        if ($values) {
            $this->set_values($values);
        }
        if (isset($this->mapping["_generate"]) && $this->mapping["_generate"]) {
            $this->generate_mapping();
        }
    }

    /**
     * Used in data_persistent_collection
     * @return string
     */
    public function get_table_name() {
        return $this->table_name;
    }

    /**
     * Получить название колонки в таблице по названию опции
     * @param string $option_name
     * @return string table column name
     * @throws nc_search_data_exception
     */
    protected function option_to_column($option_name) {
        // NB. При изменении этого метода также следует изменить get_all_column_names
        if (isset($this->mapping[$option_name])) {
            return $this->mapping[$option_name];
        }
        throw new nc_search_data_exception(get_class($this).": no mapping for the option '$option_name'");
    }

    /**
     * Получить имя опции по названию колонки
     * @param string $column_name
     * @return mixed FALSE, если такой опции нет
     */
    protected function column_to_option($column_name) {
        return array_search($column_name, $this->mapping);
    }

    /**
     * Имена всех полей в виде строки для SQL запроса
     */
    protected function get_all_column_names() {
        return "`".join("`, `", array_values($this->mapping))."`";
    }

    /**
     * Получить имена колонок, соответствующие указанным опциям
     * @param array $option_names  если пустой массив или null - возвращает все имена полей
     * @return string
     */
    public function get_column_names(array $options = null) {
        if (!$options) {
            return $this->get_all_column_names();
        }
        $result = array();
        foreach ($options as $option) {
            $result[] = "`{$this->option_to_column($option)}`";
        }
        return join(", ", $result);
    }

    /**
     * Преобразовать массив вида ('key', 'value', 'key2', 'value2') в условие для
     * WHERE-части SQL-запроса
     * @return string
     * @throws nc_search_data_exception Если указано поле, отсутствующее в $mapping
     */
    protected function make_condition() {
        $args = func_get_args();
        if (is_array($args[0])) {
            $args = $args[0];
        }
        $option_conditions = nc_search_util::make_assoc_array($args);
        $column_conditions = array();
        $db = $this->get_db();

        foreach ($option_conditions as $option_name => $option_value) {
            $condition_column = $this->option_to_column($option_name);

            if (is_null($option_value)) {
                $condition_value = "NULL";
            } elseif (is_numeric($option_value)) {
                $condition_value = $option_value;
            } else {
                $condition_value = "'".$db->escape($option_value)."'";
            }

            $column_conditions[] = "`$condition_column` = $condition_value";
        }
        return join(' AND ', $column_conditions);
    }

    /**
     * Вспомогательный метод, чтобы не печатать большой $mapping руками (неинтересно ведь)
     * @return nc_search_data_persistent
     */
    protected function generate_mapping() {
        $class_name = get_class($this);
        if (!isset(self::$mapping_cache[$class_name])) {
            $result = $this->mapping;
            if (isset($result["_generate"])) {
                unset($result["_generate"]);
            }
            foreach (array_keys($this->options) as $option_name) {
                if (in_array($option_name, $this->mapping_exclude)) {
                    continue;
                } // excluded from the mapping
                if (isset($this->mapping[$option_name])) {
                    continue;
                } // already set
                // convert to CamelCase
                // actually, this is a little bit redundant because MySQL column names are case-insensitive
                $n = ucwords(str_replace("_", " ", $option_name));
                $n = preg_replace("/ Id$/", "_ID", $n); // "option_id" => "Option_ID", not "OptionId"
                $n = str_replace(" ", "", $n);
                $result[$option_name] = $n;
            }
            self::$mapping_cache[$class_name] = $result;
        }
        $this->mapping = self::$mapping_cache[$class_name];
        return $this;
    }

    /**
     * Аналог для set_values для загрузки результатов из БД
     * @param array $values
     * @return nc_search_data_persistent
     */
    public function set_values_from_database_result(array $values) {
        $nc_core = nc_Core::get_object();
        foreach ($values as $column_name => $value) {
            $option_name = $this->column_to_option($column_name);
            if ($option_name) {
                if (in_array($option_name, $this->serialized_options)) {
                    $value = unserialize($value);
                }
                $value = $nc_core->utf8->conv('utf-8', self::$io_encode, $value);
                $this->set($option_name, $value);
            }
        }

        return $this;
    }

    /**
     *
     * @return nc_Db
     */
    protected function get_db() {
        return nc_Core::get_object()->db;
    }

    /**
     * @return int|string
     */
    public function get_id() {
        return $this->get($this->primary_key);
    }

    /**
     *
     * @param int|string $value
     * @return nc_search_data_persistent
     */
    public function set_id($value) {
        return $this->set($this->primary_key, $value);
    }

    /**
     * @throws nc_search_data_exception
     */
    protected function check_mapping_settings() {
        if (!$this->table_name) {
            throw new nc_search_data_exception(get_class($this).": no \$table_name");
        }
        if (!$this->mapping) {
            throw new nc_search_data_exception(get_class($this).": no \$mapping");
        }
    }

    /**
     * Get the body of the SET statement for INSERT/REPLACE
     * @return string
     */
    protected function prepare_set_clause() {
        $set = array();
        foreach ($this->options as $k => $v) {
            if ($command == "INSERT" && $k == $this->primary_key) {
                continue;
            }
            if (!isset($this->mapping[$k])) {
                continue;
            }

            // в базе всегда utf-8
            //$v = $nc_core->utf8->conv(self::$io_encode, 'utf-8' , $v);

            if (in_array($k, $this->serialized_options)) {
                $v = serialize($v);
            }

            if (is_null($v)) {
                $v = 'NULL';
            } elseif (is_bool($v)) {
                $v = (int) $v;
            } elseif (!is_int($v)) {
                $v = "'".nc_search_util::db_escape(addslashes($v))."'";
            }

            $set[] = "`{$this->option_to_column($k)}` = $v";
        }
        $set = join(", ", $set);

        return $set;
    }

    /**
     * Сохранение в БД
     * @throws nc_search_data_exception
     */
    public function save() {
        $nc_core = nc_Core::get_object();
        $this->check_mapping_settings();
        $db = $this->get_db();
        $command = strlen($this->get_id()) > 0 ? "REPLACE" : "INSERT";

        // база вся в utf-8, вне зависимости от кодировки системы, для правильной записи надо проставить names
        if ($nc_core->MYSQL_CHARSET != 'utf8') {
            $db->query("SET NAMES 'utf8'");
        }
        $db->query("$command INTO `$this->table_name` SET ".$this->prepare_set_clause());
        if ($db->is_error) {
            throw new nc_search_data_exception(get_class($this).": cannot save to the database (computer says no: '$db->last_error')");
        }
        // и не забыть вернуть кодировку соединения
        if ($nc_core->MYSQL_CHARSET != 'utf8') {
            $db->query("SET NAMES '".$nc_core->MYSQL_CHARSET."'");
        }

        if ($command == "INSERT") {
            $this->set_id($db->insert_id);
        }

        return $this;
    }

    /**
     * Удаление из БД
     * @throws nc_search_data_exception
     */
    public function delete() {
        $this->check_mapping_settings();
        $pk = $this->get_id();
        $db = $this->get_db();

        if (!strlen($pk)) {
            return $this;
        }

        $query = "DELETE FROM `$this->table_name`".
                " WHERE ".$this->make_condition($this->primary_key, $pk);

        $db->query($query);
        if ($db->is_error) {
            throw new nc_search_data_exception(get_class($this).": cannot delete record in the database ('$db->last_error')");
        }

        return $this;
    }

    /**
     * Загрузка из БД
     * @param mixed $id
     * @return self
     */
    public function load($id) {
        $result = $this->load_where($this->primary_key, $id);
        if (!$result) {
            throw new nc_search_data_exception(get_class($this).": object with $this->primary_key=$id not found");
        }
        return $this;
    }

    /**
     * как всё неуклюже до 5.3 получается :(
     * @return self|FALSE
     */
    protected function load_from_query($query) {
        $this->check_mapping_settings();
        $nc_core = nc_Core::get_object();
        $db = $this->get_db();

        if ($nc_core->MYSQL_CHARSET != 'utf8') {
            $db->query("SET NAMES 'utf8'");
        }
        $result = $db->get_row($query, ARRAY_A);
        if ($nc_core->MYSQL_CHARSET != 'utf8') {
            $db->query("SET NAMES '".$nc_core->MYSQL_CHARSET."'");
        }

        if ($db->is_error) {
            throw new nc_search_data_exception("Cannot load data object. Error: '$db->last_error'");
        }

        if (!$result) {
            return false;
        }

        foreach ($result as $column => $value) {
            $option_name = $this->column_to_option($column);
            if (in_array($option_name, $this->serialized_options)) {
                $value = unserialize($value);
            }
            $value = $nc_core->utf8->conv('utf-8', self::$io_encode, $value);
            $this->set($option_name, $value);
        }

        return $this;
    }

    /**
     * как всё неуклюже до 5.3 получается :(
     * @return self|FALSE
     */
    public function load_where() {
        $args = func_get_args();
        $query = "SELECT ".$this->get_all_column_names().
                " FROM `$this->table_name`".
                " WHERE ".$this->make_condition($args).
                " LIMIT 1";

        return $this->load_from_query($query);
    }

}
