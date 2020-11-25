<?php

/* $Id: nc_core.class.php 8345 2012-11-06 13:35:03Z vadim $ */
if (!class_exists("nc_System")) die("Unable to load file.");

class nc_Core extends nc_System {

    public $db;
    public $SYSTEM_FOLDER;
    public $inside_admin = false;
    public $admin_mode = false;
    public $developer_mode = false;
    public $is_trial = false;
    // trial version?
    public $beta = false;
    protected $settings;
    // значение настроек
    public $page, $widget, $trash, $event, $files, $gzip, $input, $lang, $modules, $token, $url, $utf8;
    public $catalogue, $subdivision, $sub_class, $component, $template, $user, $message;
    public $NC_UNICODE, $PHP_AUTH_LANG;
    public $TEMPLATE_FOLDER, $CLASS_TEMPLATE_FOLDER, $WIDGET_TEMPLATE_FOLDER;
    protected $page_type;
    // тип страницы (html, rss, xml)
    protected $macrofuncs;

    protected function __construct() {
        global $SYSTEM_FOLDER, $TEMPLATE_FOLDER, $CLASS_TEMPLATE_FOLDER, $WIDGET_TEMPLATE_FOLDER, $JQUERY_FOLDER, $MODULE_TEMPLATE_FOLDER;
        global $SECURITY_XSS_CLEAN; 

        $this->set_variable("SYSTEM_FOLDER", $SYSTEM_FOLDER);
        $this->set_variable("TEMPLATE_FOLDER", $TEMPLATE_FOLDER);
        $this->set_variable("CLASS_TEMPLATE_FOLDER", $CLASS_TEMPLATE_FOLDER);
        $this->set_variable("WIDGET_TEMPLATE_FOLDER", $WIDGET_TEMPLATE_FOLDER);
        $this->set_variable("JQUERY_FOLDER", $JQUERY_FOLDER);
        $this->set_variable("MODULE_TEMPLATE_FOLDER", $MODULE_TEMPLATE_FOLDER);
        $this->set_variable("SECURITY_XSS_CLEAN", $SECURITY_XSS_CLEAN);
        // load parent constructor
        parent::__construct();

        //$this->macrofuncs['NC_OBJECTS_LIST'] = array('func' => 'nc_objects_list');
        //$this->beta = true;
    }

    /**
     * Set object variable
     *
     * @param string variable name
     * @param mixed variable value
     */
    public function set_variable($name, $value) {
        // set variable
        $this->$name = $value;
    }

    /**
     * Get object variable
     *
     * @param string variable name
     *
     * @return mixed variable value
     */
    public function get_variable($name) {
        // return value
        return isset($this->$name) ? $this->$name : NULL;
    }

    /**
     * Load system extension
     *
     * @param class name for loading
     * @param mixed arguments for class __construct function
     *
     * @return instanteated object
     */
    public function load() {

        $args = func_get_args();

        $object = array_shift($args);
        $path = '';
        if (strstr($object, "/")) {
            $object_arr = explode("/", trim($object, "/"));
            $object = array_pop($object_arr);
            if (!empty($object_arr)) {
                $path = join("/", $object_arr)."/";
            }
        }

        if (is_object($this->$object)) {
            $this->debugMessage("System class \"".$object."\" already loaded", __FILE__, __LINE__, "info");
            return $this->$object;
        }

        $file_name = $this->SYSTEM_FOLDER.$path."nc_".$object.".class.php";
        if (file_exists($file_name)) {
            include_once ($file_name);
        }

        $class_name = "nc_".ucfirst($object);

        if (class_exists($class_name)) {
            $this->$object = call_user_func_array(array(new ReflectionClass($class_name), 'newInstance'), $args);
        }

        if (!$this->$object)
                throw new Exception("Unable load system class \"".$class_name."\"!");

        return $this->$object;
    }

    /**
     * Получить значение параметра из настроек
     * @param string ключ
     * @param string имя модуля ( system - ядро )
     * @return mixed значение параметра
     */
    public function get_settings($item = '', $module = '', $reset = false) {
        if (empty($this->settings) || $reset) {
            $res = $this->db->get_results("SELECT `Key`, `Module`, `Value` FROM `Settings`", ARRAY_A);
            // обработка ошибок
            if ($this->db->is_error) {
                // таблица не существует
                if ($this->db->errno == 1146 || strpos($this->db->last_error, 'exist')) {
                    if ( $this->check_system_install() ) {
						// DB error
						print "<p><b>".NETCAT_ERROR_DB_CONNECT."</b></p>";
						exit;
					}
                }
                die("Table `Settings`");
            }

            for ($i = 0; $i < $this->db->num_rows; $i++) {
                $this->settings[$res[$i]['Module']][$res[$i]['Key']] = $res[$i]['Value'];
            }

            if (!$this->get_settings('nc_default_settings_filled_500')) {
                $this->check_default_settings();
                $this->set_settings('nc_default_settings_filled_500', 1);
                $this->get_settings(null, null, true);
            }
        }

        // по умолчанию - ядро ( 1 и true нужно для обратной совместимости )
        if (!$module || $module === 1 || $module === true) {
            $module = 'system';
        }

        // if item requested return item value
        if ($item && is_array($this->settings[$module])) {
            return array_key_exists($item, $this->settings[$module]) ? $this->settings[$module][$item] : false;
        }

        // return all settings
        return $this->settings[$module];
    }

    private function check_default_settings() {
        $this->load('modules');
        $catalogue_ids = (array) $this->db->get_col("select Catalogue_ID from Catalogue");

        $shop_mode = 0;
        switch (true) {
            case $this->modules->get_by_keyword('netshop'): ++$shop_mode;
            case $this->modules->get_by_keyword('minishop'): ++$shop_mode;
        }

        foreach ($catalogue_ids as $id) {
            $this->set_settings('nc_shop_mode_' . $id, $shop_mode);
        }
    }

    /**
     * Установить значние параметра
     * @param string ключ
     * @param string значние параметра
     * @param string модуль
     * @return bool
     */
    public function set_settings($key, $value, $module = 'system') {
        // по умолчанию - ядро системы
        if (!$module) $module = 'system';
        // обновляем состояние
        $this->settings[$module][$key] = $value;
        // подготовка записи в БД
        $key = $this->db->escape($key);
        $value = $this->db->prepare($value);
        $module = $this->db->escape($module);

        $id = $this->db->get_var("SELECT `Settings_ID` FROM `Settings` WHERE `Key` = '".$key."' AND `Module` = '".$module."' ");
        if ($id) {
            $this->db->query("UPDATE `Settings` SET `Value` = '".$value."' WHERE `Settings_ID` = '".$id."' ");
        } else {
            $this->db->query("INSERT INTO `Settings`(`Key`, `Module`, `Value`)
                        VALUES('".$key."','".$module."','".$value."') ");
        }

        return true;
    }

    /**
     * Удаление параметра
     * @param string ключ
     * @param string модуль
     * @return int
     */
    public function drop_settings($key, $module = 'system') {
        // по умолчанию - ядро системы
        if (!$module) $module = 'system';
        // обновляем состояние
        unset($this->settings[$module][$key]);
        // подготовка запроса к БД
        $key = $this->db->escape($key);
        $module = $this->db->escape($module);

        $this->db->get_query("DELETE FROM `Settings` WHERE `Key` = '".$key."' AND `Module` = '".$module."' ");

        return $this->db->affected_rows;
    }

    /**
     * Load default extensions
     *
     * @return settings data array
     */
    public function load_default_extensions() {
        // call as static
        static $loaded = false;

        // check inited object
        if (!$loaded) {
            // load default extensions
			$this->load("security");
            $this->load("files");
            $this->load("token");
            $this->load("event");
            $this->load("gzip");
            $this->load("utf8");
            $this->load("input");
            $this->load("url");
            $this->load("db");
            $this->load("page");
            $this->load("lang");
            $this->load("modules");
            $this->load("widget");
            // essences
            $this->load("essences/catalogue");
            $this->load("essences/subdivision");
            $this->load("essences/sub_class");
            $this->load("essences/component");
            $this->load("essences/user");
            $this->load("essences/template");
            $this->load("essences/message");
            $this->load("essences/trash");
            $loaded = true;
        }
    }

    public function load_files($in_admin = 0) {
        // файлы из /netcat/require/
        $include_files = array(
                'unicode.inc.php',
                's_e404.inc.php',
                's_auth.inc.php',
                's_browse.inc.php',
                's_list.inc.php',
                's_class.inc.php',
                's_common.inc.php',
                'classes/nc_multifield.class.php',
                'classes/nc_multifield_settings.class.php',
                'classes/nc_multifield_template.class.php');
        // deprecated functions
        if (!$this->NC_DEPRECATED_DISABLED)
                $include_files[] = 'deprecated.inc.php';

        // файлы из /netcat/admin/
        $admin_files = array(
                'CheckUserFunctions.inc.php',
                'consts.inc.php',
                'user.inc.php',
                'sub_class.inc.php',
                'class.inc.php',
                'subdivision.inc.php',
                'mail.inc.php',
                'permission.class.php');

        if ($in_admin) {
            $admin_files = array_merge($admin_files, array(
                            'nc_adminnotice.class.php',
                            'catalog.inc.php',
                            'class.inc.php',
                            'template.inc.php',
                            'field.inc.php',
                            'system_table.inc.php',
                            'module.inc.php',
                            'admin.inc.php'));
        }

        $templating_files = array(
                'tpl_function.inc',
                'class_editor.class',
                'class_view.class',
                'fields.class',
                'template_editor.class',
                'template_view.class',
                'widget_editor.class',
                'widget_view.class',
                'tpl_parser.class',
                'tpl.class',
                'module_editor.class',
                'module_view.class',
                'module_tpl_editor.class',
                'module_tpl_view.class'
        );

        foreach ($templating_files as $file) {
            require_once $this->SYSTEM_FOLDER.'templating/nc_'.$file.'.php';
        }

        foreach ($include_files as $file) {
            require_once( $this->INCLUDE_FOLDER.$file);
        }

        foreach ($admin_files as $file) {
            require_once( $this->ADMIN_FOLDER.$file);
        }

        return 0;
    }

    public function get_system_table_fields($item = "") {
        // call as static
        static $storage = array();

        // check inited object
        if (empty($storage)) {
            // Load system table field info
            $res = $this->db->get_results("SELECT b.`System_Table_Name` AS system_table_name,
        a.`Field_ID` as id,
        a.`Field_Name` as name,
        a.`TypeOfData_ID` as type,
        a.`Inheritance` as inheritance,
        a.`Format` as format
        FROM `Field` AS a, `System_Table` AS b
        WHERE a.`System_Table_ID` = b.`System_Table_ID`", ARRAY_A);
            //ORDER BY a.`System_Table_ID`, a.`Priority`"
            // compile system table fields array
            if (!empty($res)) {
                foreach ($res AS $row) {
                    $storage[$row['system_table_name']][] = $row;
                }
            }
        }

        // if item requested return item value
        if ($item) {
            return array_key_exists($item, $storage) ? $storage[$item] : array();
        }

        // return data associative array
        return $storage;
    }

    public function load_env($catalogue, $sub, $cc) {
        global $admin_mode;
        global $catalogue, $sub, $cc;
        global $current_catalogue, $cc;
        global $current_sub;
        global $current_cc;
        global $cc_array;
        global $use_multi_sub_class;
        global $system_table_fields, $user_table_mode;
        global $parent_sub_tree, $sub_level_count;

        // load catalogue
        if (!$catalogue) {
            try {
                $current_catalogue = $this->catalogue->get_by_host_name($this->HTTP_HOST, true);
                $catalogue = $current_catalogue['Catalogue_ID'];
            } catch (Exception $e) {
                die("No site in project");
            }
        } else {
            $current_catalogue = $this->catalogue->set_current_by_id($catalogue);
        }

        // load sub
        if (!$sub) {
            $sub = $this->catalogue->get_by_id($catalogue, "Title_Sub_ID");
            if (!$sub)
                    throw new Exception("Unable to find the index page for catalog");
        }

        $this->subdivision->set_current_by_id($sub);


        // load cc
        if (!$cc) {
            $checked_only = $admin_mode ? "" : " AND `Checked` = 1";
            $cc = $this->db->get_var("SELECT `Sub_Class_ID` FROM `Sub_Class` WHERE `Subdivision_ID` = '".intval($sub)."'".$checked_only." ORDER BY `Priority` LIMIT 1");
        }
        if ($cc) {
            try {
                $this->sub_class->set_current_by_id($cc);
            } catch (Exception $e) {
                // todo
            }
        }

        // Load all sub_class id's into array, may be exist in
        if (!is_array($cc_array)) {
            $cc_array = array();
            // get cc(s) data
            $res = $this->sub_class->get_by_subdivision_id($sub);
            if (!empty($res)) {
                foreach ($res as $row) {
                    if ($row['Checked']) $cc_array[] = $row['Sub_Class_ID'];
                }
            }
        }

        // load system table fields
        $system_table_fields = $this->get_system_table_fields();
        // set global variables
        $current_catalogue = $this->catalogue->get_current();
        $current_sub = $this->subdivision->get_current();
        $current_cc = $this->sub_class->get_current();

        if ($current_cc['System_Table_ID'] == 3 || in_array($current_sub['Subdivision_ID'], nc_preg_split("/\s*,\s*/", $this->get_settings('modify_sub', 'auth')))) {
            $action = "message";
            $user_table_mode = true;
        } else {
            $user_table_mode = false;
        }

        $parent_sub_tree[$sub_level_count]["Subdivision_Name"] = $current_catalogue["Catalogue_Name"];
        $parent_sub_tree[$sub_level_count]["Hidden_URL"] = "/";

        return;
    }

    /**
     * Get or instance self object
     *
     * @return self object
     */
    public static function get_object() {
        // call as static
        static $storage;
        // check inited object
        if (!isset($storage)) {
            // init object
            $storage = new self();
        }
        // return object
        return is_object($storage) ? $storage : false;
    }

    public function set_page_type($type) {
        if (!in_array($type, array('html', 'rss', 'xml'))) $type = 'html';
        $this->page_type = $type;
    }

    public function get_page_type() {
        return $this->page_type ? $this->page_type : 'html';
    }

    public function get_content_type() {
        $type = $this->get_page_type();
        if ($type == 'rss') $type = 'xml';

        return "text/".$type."; charset=".$this->NC_CHARSET;
    }

    public function replace_macrofunc($str) {
        global $action;
        if ($this->inside_admin || $action == 'change' || $action == 'add')
                return $str;
        preg_match_all("/%([a-z0-9_]+)\(([a-z0-9,'_]+)\)%/i", $str, $matches, PREG_SET_ORDER);

        foreach ($matches as $v) {
            if (empty($this->macrofuncs[$v[1]])) continue;
            $func = $this->macrofuncs[$v[1]]['func'];
            $obj = $this->macrofuncs[$v[1]]['object'];
            eval("\$args = \$this->_parse_func_arg(".$v[2].");");
            $res = call_user_func_array($obj ? array($obj, $func) : $func, $args);
            $str = str_replace($v[0], $res, $str);
        }

        return $str;
    }

    /**
     * Включение или выключение поля
     * @param string check - включение, uncheck - выключение
     * @param mixed номер компонента или имя системной таблицы
     * @param mixed номер поля или его имя
     * @return bool изменено поле или нет
     */
    public function edit_field($action, $class_id = 0, $field = '') {
        $system_tables = array("Catalogue" => 1, "Subdivision" => 2, "User" => 3, "Template" => 4);
        if (is_string($class_id)) {
            $sysem_table_id = $system_tables[$class_id];
        } else {
            $class_id = intval($class_id);
        }

        $this->db->query("UPDATE `Field`
                      SET `Checked` = '".($action == 'check' ? 1 : 0)."'
                      WHERE
                      ".( is_int($class_id) ? "`Class_ID` = '".$class_id."' AND " : "" )."
                      ".( $sysem_table_id ? "`System_Table_ID` = '".$sysem_table_id."' AND " : "" )."
                      ".( is_int($field) ? "`Field_ID` = '".$field."' " :
                        " `Field_Name` = '".$this->db->escape($field)."' "));
        return $this->db->rows_affected;
    }

    /**
     * Включение поля в компоненте или ситемной таблицы
     * @param mixed номер компонента или имя системной таблицы
     * @param mixed номер поля или его имя
     * @return bool изменено поле или нет
     */
    public function check_field($class_id = 0, $field = '') {
        return $this->edit_field('check', $class_id, $field);
    }

    /**
     * Выключение поля в компоненте или ситемной таблицы
     * @param mixed номер компонента или имя системной таблицы
     * @param mixed номер поля или его имя
     * @return bool изменено поле или нет
     */
    public function uncheck_field($class_id = 0, $field = '') {
        return $this->edit_field('uncheck', $class_id, $field);
    }

    /**
     * Метод провреят, установлено ли расширение php
     * @param string имя расширения
     * @return bool
     */
    public function php_ext($name) {
        static $ext = array();
        if (!array_key_exists($name, $ext)) {
            $ext[$name] = extension_loaded($name);
        }

        return $ext[$name];
    }

    /**
     * Строка парсится в аргументы функции
     * @param string
     * @return <type>
     */
    protected function _parse_func_arg($str) {
        return func_get_args();
    }

    /**
     * Регистрация макрофункции
     * @param string имя макрофункции
     * @param string имя функции или метода, результат которой заменяет макрофункцию
     * @param object  ссылка на объект, если второй аргумент - метод
     */
    public function register_macrofunc($macroname, $func, &$object = null) {
        $this->macrofuncs[$macroname] = array('func' => $func);
        if ($object) $this->macrofuncs[$macroname]['object'] = $object;
    }

    public function return_device() {
        require_once $this->INCLUDE_FOLDER.'lib/mobile_detect.php';
        $detect = new Mobile_Detect();
        return ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'mobile') : 'desktop');
    }

    public function mobile_screen() {
        return empty($_COOKIE['mobile']);
    }

    public function InsideAdminAccess() {
        global $current_user, $AUTH_USER_ID;
        return ($this->modules->get_by_keyword('auth') && $current_user['InsideAdminAccess']) || !$this->modules->get_by_keyword('auth') && $this->db->get_var("SELECT `InsideAdminAccess` FROM `User` WHERE `User_ID`='" . intval($AUTH_USER_ID) . "'");
    }

    public function get_interface() {
        return $this->catalogue->get_current('ncMobile') ? 'mobile' : ($this->catalogue->get_current('ncResponsive') ? 'responsive' : 'web');
    }
    
    public function __get($name) {
        $result = null;
        if (isset($this->$name)) {
            $result = $this->$name;
        } else if (method_exists('nc_Core', $name)) {
            $result = $this->$name = $this->$name();
        }
        return $result;
    }
}
