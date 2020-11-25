<?php

/* $Id: nc_search.class.php 8366 2012-11-07 16:30:14Z aix $ */

/**
 * Основной класс модуля поиска
 */
class nc_search {

    /**
     * Настройки модуля
     * @var nc_search_settings
     */
    static protected $settings;
    /**
     * It's a nc_search_ui singleton
     * @var nc_search_ui
     */
    static protected $instance;
    /**
     * Поставщик поиска
     * @var nc_search_provider
     */
    static protected $provider;
    /**
     * Текущий контекст
     * @var nc_search_context
     */
    static protected $current_context;
    /**
     *
     */
    static protected $max_log_level;
    
    static protected $fieldsets;

    /**
     * Константы
     */
    const LOG_ERROR = 1;
    const LOG_PHP_EXCEPTION = 2;
    const LOG_PHP_ERROR = 4;
    const LOG_PHP_WARNING = 8;
    const LOG_INDEXING_NO_SUB = 32;
    const LOG_INDEXING_BEGIN_END = 64;
    const LOG_CRAWLER_REQUEST = 128;
    const LOG_PARSER_DOCUMENT_BRIEF = 256;
    const LOG_PARSER_DOCUMENT_VERBOSE = 512;
    const LOG_PARSER_DOCUMENT_LINKS = 1024;
    const LOG_SCHEDULER_START = 2048;
    const LOG_INDEXING_CONTENT_ERROR = 4096;
    // NB: при добавлении константы обновить self::$log_strings

    const LOG_NOTHING = 0;
    const LOG_ALL = 69631;
    const LOG_CONSOLE = 68095; // self::LOG_ALL ^ self::LOG_PARSER_DOCUMENT_VERBOSE ^ self::LOG_PARSER_DOCUMENT_LINKS;
    const LOG_ALL_ERRORS = 4103;  // self::LOG_ERROR | self::LOG_PHP_ERROR | self::LOG_PHP_EXCEPTION

    const INDEXING_BROWSER = 1; // запуск в браузере
    const INDEXING_NC_CRON = 2; // то, что называется "кроном" в неткете
    const INDEXING_CONSOLE = 3;

 // запуск из консоли

    static protected $log_strings = array(
            self::LOG_ERROR => 'ERROR',
            self::LOG_PHP_EXCEPTION => 'PHP_EXCEPTION',
            self::LOG_PHP_ERROR => 'PHP_ERROR',
            self::LOG_PHP_WARNING => 'PHP_WARNING',
            self::LOG_SCHEDULER_START => 'SCHEDULER_START',
            self::LOG_INDEXING_BEGIN_END => 'INDEXING_BEGIN_END',
            self::LOG_CRAWLER_REQUEST => 'CRAWLER_REQUEST',
            self::LOG_INDEXING_NO_SUB => 'INDEXING_NO_SUB',
            self::LOG_PARSER_DOCUMENT_LINKS => 'PARSER_DOCUMENT_LINKS',
            self::LOG_PARSER_DOCUMENT_BRIEF => 'PARSER_DOCUMENT_BRIEF',
            self::LOG_PARSER_DOCUMENT_VERBOSE => 'PARSER_DOCUMENT_VERBOSE',
            self::LOG_INDEXING_CONTENT_ERROR => 'INDEXING_CONTENT_ERROR',
    );

    /**
     * Получить экземпляр класса nc_search_ui (который $nc_search)
     */
    static public function get_object() {
        if (!self::$instance) {
            self::$instance = new nc_search_ui();
        }
        return self::$instance;
    }

    /**
     * Первый запуск модуля
     */
    static protected function first_run() {
        // (1) IndexerSecretKey
        self::save_setting('IndexerSecretKey', sha1(mt_rand().time()));

        // (2) robots.txt sitemap link
        $robots = new nc_search_robots;
        $path = self::get_module_url();
        foreach (array_keys(nc_Core::get_object()->catalogue->get_all()) as $site_id) {
            $robots->add_directive($site_id, "Sitemap: $path/sitemap.php");
            $robots->save_robots_txt($site_id);
        }

        // (3) инициализация поисковой службы
        self::get_provider()->first_run();
    }

    /**
     * �&#65533;нициализация модуля
     */
    static public function init() {
        // class autoload
        spl_autoload_register(array('nc_search', 'load_class'), false);

        // first run?
        if (!self::get_setting('IndexerSecretKey')) {
            self::first_run();
        }

        // logging:
        nc_Core::get_object()->event->register_event('moduleSearchEvent', NETCAT_MODULE_SEARCH_EVENT);
        self::register_logger(new nc_search_logger_database);

        // events for updatint the robots.txt
        $event_manager = nc_Core::get_object()->event;
        $robots = new nc_search_robots;
        $event_manager->bind($robots, array('addCatalogue,updateCatalogue' => 'update_site'));
        $event_manager->bind($robots, array('addSubdivision,updateSubdivision' => 'update_sub'));
        $event_manager->bind($robots, array('dropSubdivision' => 'delete_sub'));

        // global $nc_search variable
        $GLOBALS['nc_search'] = self::get_object();
    }

    /**
     * Class autoloader for nc_search_* classes
     * @param string $class_name
     */
    static public function load_class($class_name) {
        if (substr($class_name, 0, 10) == "nc_search_") {
            $class_path = str_replace("_", "/", strtolower(substr($class_name, 10)));
            include_once(dirname(__FILE__)."/lib/$class_path.php");
        }
    }

    /**
     * Load script from the 'lib/3rdparty' folder
     * @param string $path path to the script without the starting slash
     */
    static public function load_3rdparty_script($path) {
        $path = self::get_3rdparty_path()."/$path";
        require_once($path);
    }

    /**
     * Путь к модулю (без trailing slash)
     * @return string
     */
    static public function get_module_path() {
        return dirname(__FILE__);
    }

    /**
     * @return string
     */
    static public function get_3rdparty_path() {
        return dirname(__FILE__)."/lib/3rdparty";
    }

    /**
     * Path to the module folder on the site
     * NB, no trailing slash
     */
    static public function get_module_url() {
        $nc_core = nc_Core::get_object();
        $path = $nc_core->SUB_FOLDER.$nc_core->HTTP_ROOT_PATH."modules/search";
        return $path;
    }

    /**
     * Метод для передачи текущего контекста в глубины компонентов, работающих
     * внутри сторонних библиотек (фильтры Zend_Search_Lucene тому примером).
     * Некрасиво, но что поделаешь... [Можно будет избавиться, если будет
     * собственный парсер запросов]
     * @param nc_search_context|null $context
     */
    static public function set_current_context(nc_search_context $context = null) {
        self::$current_context = $context;
    }

    /**
     * @return nc_search_context
     */
    static public function get_current_context() {
        if (!isset(self::$current_context)) {
            throw new nc_search_exception("nc_search::get_current_context(): current context is unknown");
        }
        return self::$current_context;
    }

    /**
     * Возвращает объект, реализующий интерфейс nc_search_provider
     * @return nc_search_provider
     */
    static public function get_provider() {
        if (!self::$provider) {
            $provider_class = self::get_setting('SearchProvider');
            self::$provider = new $provider_class;
        }
        return self::$provider;
    }

    /**
     * Добавляет в расписания (scheduler_intent) переиндексацию указанной области
     * (или правила) в указанное время
     * @param string $area_string area OR rule_id
     *   (damn it, не нужно было следовать ТЗ)
     * @param string $when   Any string strtotime will understand, e.g. "now", "22:50", "2020-01-01 00:05", "next tuesday"
     * @param string|integer|nc_search_area $area
     */
    static public function index_area($area_string = "allsites", $when = "now") {
        if (self::should('EnableSearch')) {
            self::get_provider()->schedule_indexing($area_string, strtotime($when));
        } else {
            throw new nc_search_exception("Search module is disabled");
        }
    }

    /**
     *
     * @param string|nc_search_query $query
     * @param boolean $highlight_matches
     * @return nc_search_result
     */
    static public function find($query, $highlight_matches = true) {
        if (self::should('EnableSearch')) {
            nc_search_util::set_utf_locale($language);
            if (is_string($query)) {
                $query = new nc_search_query($query);
            }
            $result = self::get_provider()->find($query, $highlight_matches);
            nc_search_util::restore_locale();
            return $result;
        } else {
            throw new nc_search_exception("Search module is disabled");
        }
    }

    //----------------- РАБОТА С НАСТРОЙКАМ�&#65533; МОДУЛЯ ---------------------------

    /**
     * �&#65533;нициализация, получение объекта настроек (which is a singleton)
     * @return nc_search_settings
     */
    protected static function get_settings_object() {
        if (!self::$settings) {
            $s = self::$settings = new nc_search_settings();
            $settings = nc_Core::get_object()->get_settings(null, 'search');

            $module_editor = new nc_module_editor();
            $module_editor->load('search')->fill();
            $settings_templates = array();
            // >_< приведение массива с темплейтами к названиям полей settings_object
            foreach ($module_editor->get_all_fields() AS $type => $templates) {
                foreach ($templates as $key => $template) {
                    $settings_templates[$type.'_'.$key] = $template;
                }
            }
            $settings = array_merge($settings, $settings_templates);

            if ($settings) {
                foreach ($settings as $k => $v) {
                    $s->set($k, $v);
                }
            }
        }
        return self::$settings;
    }

    static public function reload_settings_object() {
        self::$settings = null;
        self::get_settings_object();
    }

    /**
     * Получение значения параметра настроек
     * 
     * @param string $option_name
     * @return mixed
     */
    public static function get_setting($option_name) {
        return self::get_settings_object()->get($option_name);
    }
    
    /**
     * Сокращение для проверки значения параметра в настроек на правдивость.
     * Возвращает true, если значение опции равно истине.
     *
     * Нестандартное название обусловлено тем, что оно позволяет составлять короткие
     * условия, относительно правильные с точки зрения грамматики английского
     * языка:
     *    if (nc_search::should('AllowTermBoost')) { do_something()); }
     *    // ≈ "Should we allow the term boost?"
     *
     * @param string $option_name
     * @return boolean
     */
    public static function should($option_name) {
        return self::get_setting($option_name) == true;
    }

    /**
     * Установка параметра
     * Значение не сохраняется в БД, если не вызван метод nc_search::save_settings()
     *
     * @param string $option_name
     * @return mixed
     */
    public static function set_setting($option_name, $value) {
        self::get_settings_object()->set($option_name, $value);
    }

    /**
     *
     */
    public static function save_setting($option_name, $value) {
        self::set_setting($option_name, $value);

        $module_editor = new nc_module_editor();
        $module_editor->load('search')->save($_POST);
        
        nc_Core::get_object()->set_settings($option_name, $value, 'search');
    }

    /**
     * shortcut for nc_search_data_persistent_collection::load_all()
     * @param string $data_class
     * @param boolean $force_reload
     * @param string $index_by присвоить ключам элементов коллекции значение опции $index_by
     * @return nc_search_data_persistent_collection
     */
    static public function load_all($data_class, $force_reload = false, $index_by = null) {
        return nc_search_data_persistent_collection::load_all($data_class, $force_reload, $index_by);
    }

    /**
     * shortcut for nc_search_data_persistent_collection::load()
     * @param string $data_class
     * @param string $query SQL query
     * @param string $index_by присвоить ключам элементов коллекции значение опции $index_by
     * @return nc_search_data_persistent_collection
     */
    static public function load($data_class, $query, $index_by = null) {
        return nc_search_data_persistent_collection::load($data_class, $query, $index_by);
    }

    //-------------------------- ОБРАБОТКА ОШ�&#65533;БОК --------------------------------

    /**
     *
     * @param nc_search_logger $instance
     */
    static public function register_logger(nc_search_logger $logger) {
        nc_Core::get_object()->event->bind($logger, array('moduleSearchEvent' => 'notify'));
        self::$max_log_level |= $logger->get_level();
    }

    /**
     * Для оптимизации в тех местах, где для логирования выполняются затратные вычисления
     * @param integer $type  log level (self::LOG_* constant)
     * @return boolean       whether this log level is enabled
     */
    static public function will_log($type) {
        return (bool) ($type & self::$max_log_level);
    }

    /**
     *
     * @param integer $type
     * @param string $message
     */
    static public function log($type, $message) {
        nc_Core::get_object()->event->execute('moduleSearchEvent', $type, self::$log_strings[$type], $message);
    }

    /**
     * 
     * @return array;
     */
    static public function get_log_types() {
        return self::$log_strings;
    }

    /**
     * Включить запись ошибок и исключений в лог при выполнении скрипта в "кроне"
     */
    static public function enable_error_logging() {
        set_error_handler(array('nc_search', 'error_handler'), error_reporting());
        set_exception_handler(array('nc_search', 'exception_handler'));
    }

    /**
     * Обработчик ошибок для записи ошибок в лог при выполнении скрипта в "кроне"
     */
    static public function error_handler($errno, $errstr) {
        if (error_reporting() == 0) {
            return false;
        } // error messages suppressed with an @
        if ($errno == E_WARNING || $errno == E_USER_WARNING) {
            $type = self::LOG_PHP_WARNING;
        } else if ($errno == E_ERROR || $errno = E_USER_ERROR) {
            $type = self::LOG_PHP_ERROR;
        } else {
            return false;
        }
        try {
            self::log($type, $errstr);
        } catch (Exception $e) {
            print $errstr;
            print "\nEXCEPTION WHILE TRYING TO LOG THE ERROR: {$e->getMessage()}";
        }
        return false;
    }

    /**
     * Обработчик исключений для записи исключений в лог при выполнении скрипта в "кроне"
     */
    static public function exception_handler($exception) {
        // copied from PHP.NET
        // these are our templates
        $traceline = "#%s %s(%s): %s()";
        $msg = "PHP Fatal error:  Uncaught exception '%s' with message '%s' in %s:%s\nStack trace:\n%s\n  thrown in %s on line %s";

        // alter your trace as you please, here
        $trace = $exception->getTrace();

        // build your tracelines
        $key = 0;
        $result = array();
        foreach ($trace as $key => $stackPoint) {
            $result[] = sprintf(
                            $traceline,
                            $key,
                            $stackPoint['file'],
                            $stackPoint['line'],
                            $stackPoint['function']
            );
        }
        // trace always ends with {main}
        $result[] = '#'.++$key.' {main}';

        // write tracelines into main template
        $msg = sprintf(
                        $msg,
                        get_class($exception),
                        $exception->getMessage(),
                        $exception->getFile(),
                        $exception->getLine(),
                        implode("\n", $result),
                        $exception->getFile(),
                        $exception->getLine()
        );

        try {
            self::log(self::LOG_PHP_EXCEPTION, $msg);
        } catch (Exception $e) {
            print $msg;
            print "\nEXCEPTION WHILE TRYING TO LOG THE ORIGINAL EXCEPTION: {$e->getMessage()}";
        }
    }

    // ----------------------------- ПРОЧЕЕ --------------------------------------

    /**
     *
     * @param integer $interval
     * @param string $unit   'hours', 'days', 'months'
     */
    static public function purge_history($interval = null, $unit = null) {
        if (!$interval) {
            if (!self::should('AutoPurgeHistory')) {
                return;
            }
            $interval = self::get_setting('AutoPurgeHistoryIntervalValue');
            $unit = self::get_setting('AutoPurgeHistoryIntervalUnit');
        }
        if (!$interval || !$unit) {
            return;
        }

        $time = nc_search_util::sql_datetime(strtotime("-$interval $unit"));
        nc_Core::get_object()->db->query("DELETE FROM `Search_Query` WHERE `Timestamp` < '$time'");
    }

    /**
     * 
     */
    static public function purge_log() {
        $days_to_keep = self::get_setting('DaysToKeepEventLog');
        $time = nc_search_util::sql_datetime(strtotime("-$days_to_keep days"));
        nc_Core::get_object()->db->query("DELETE FROM `Search_Log` WHERE `Timestamp` < '$time'");
    }


}

