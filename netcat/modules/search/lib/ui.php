<?php

/* $Id: ui.php 8366 2012-11-07 16:30:14Z aix $ */

/**
 * Формы поиска, формирование результатов и т.п. - объект $nc_search
 */
class nc_search_ui {

    /**
     * Путь до раздела поиска на текущем сайте, @see nc_search_ui::get_path()
     * @var string
     */
    protected $path;
    /**
     *
     */
    protected $paths = array();

    /**
     * Вывод формы поиска
     *
     * @param string $params Параметры — передаются в традиционном для NetCat формате
     *  через амперсанд.
     *   — submitname – содержание кнопки сабмита, по умолчанию языковая константа, «Найти»
     *   — inputsize – ширина поля ввода, по умолчанию 20
     *   — suggest – 1 или on — применять ли автозаполнение, если оно разрешено, по умолчанию 1
     *   — areastype – индекс массива, аналогичного $browse_sub, отвечающего за вывод списка
     *     областей поиска; по умолчанию варианты checkbox, links, radio, select, empty,
     *     по умолчанию если areas пусто — empty, если нет — select.
     *   — showavdancedlink – выводить ли ссылку на форму расширенного поиска,
     *     если он разрешен в настройках; по умолчанию 0
     *   — inputvalue – значение в поле ввода, по умолчанию равен GET-параметру query
     *   — selectedarea – выбранная область; может быть перечислено несколько областей
     *     через запятую. По умолчанию равен GET-параметру area
     *   — actionurl – путь до раздела поиска; определяется автоматически
     *
     * @param array $template альтернативный шаблон отображения формы
     *  Элементы: prefix, suffix; вывод областей поиска: unactive, active, divider
     *  Можно подать на вход шаблон не полностью, а только его часть; недостающие
     *  элементы будут взяты из описания формы поиска по умолчанию в настройках модуля
     *
     * @param array $areas массив пар значений «область — подпись».
     *   Каждая area может иметь несколько указаний на области поиска, которые должны
     *   быть логически сложены. Варианты указаний:
     *     sub99 — только раздел 99 (все страницы, относящиеся к нему)
     *     sub99* — раздел и подразделы
     *     /about/ — только раздел с указанным путём до него, включая прямых потомков
     *     /about/* — раздел и подразделы
     *     all – весь сайт (аналогично пустоте)
     *     -sub99, -sub99*, -/about/ и пр. — исключение из области
     *     thissub — только текущий раздел
     *     thissub* — текущий раздел и его подразделы
     *     parentsub* — все в корневном разделе (то есть для урла /about/news/ это будет аналогично /about/*)
     *   Несколько областей могут быть указаны через пробел: «sub1* sub2* -sub3».
     *   В отличие от областей индексации область поиска «конкретная страница с объектом»
     *   не работает (игнорируется), также как и поиск по разделу «только этот путь»
     *   (трактуется как «раздел и объекты в этом разделе»).
     *
     * @return string
     */
    public function show_form($params = "", array $template = null, array $areas = array()) {
        if (!nc_search::should('EnableSearch')) {
            return "";
        }

        $input = nc_Core::get_object()->input;
        $default_params = array(
                "actionurl" => $this->get_path(),
                "submitname" => NETCAT_MODULE_SEARCH_SUBMIT_BUTTON_TEXT,
                "inputsize" => 20,
                "suggest" => nc_search::should("EnableQuerySuggest"),
                "areastype" => ($areas ? "select" : "empty"),
                "showadvancedlink" => nc_search::should("EnableAdvancedSearchForm"),
                "inputvalue" => $this->make_query_string($input->fetch_get("search_query")),
                "selectedarea" => (array) $input->fetch_get("area"),
        );
        parse_str($params, $params);
        $params = array_merge($default_params, $params);

        if (is_string($params["selectedarea"])) {
            $params["selectedarea"] = preg_split("/\s*,\s*/u", $params["selectedarea"]);
        }

        $nc_core = nc_Core::get_object();
        $File_Mode = $nc_core->template->get_current('File_Mode');
        $interface = $nc_core->get_interface();

        if ($File_Mode) {
             $default_templates = $this->evaluate(nc_search::get_setting($interface.'_SearchFormTemplate'), 'searchform', $params);
        } else {
             $default_templates = $this->evaluate(nc_search::get_setting('SearchFormTemplate'), 'searchform', $params);
        }

        if (!isset($default_templates[$params["areastype"]])) {
            trigger_error("\$nc_search->show_form(): Incorrect areastype value ('$params[areastype]')", E_USER_WARNING);
            $params["areastype"] = "empty";
        }

        $template = array_merge($default_templates[$params["areastype"]], (array) $template);

        $link = "?search_query=".urlencode($params["inputvalue"]);
        $params["inputvalue"] = htmlspecialchars($params["inputvalue"], ENT_QUOTES, MAIN_ENCODING);

        $result = array();
        $result[] = $this->tpl($template['prefix'], $params);

        $area_count = count($areas);
        $area_num = 0;
        foreach ($areas as $area => $name) {
            $area = $this->normalize_area($area);
            $index = in_array($area, $params["selectedarea"]) ? 'active' : 'unactive';
            $result[] = $this->tpl($template[$index], $params, array(
                            '%AREA' => htmlspecialchars($area, ENT_QUOTES, MAIN_ENCODING, false),
                            '%AREANUM' => $area_num++,
                            '%NAME' => htmlspecialchars($name, ENT_QUOTES, MAIN_ENCODING, false),
                            '%URL' => $link,
                    ));
            if ($area_num != $area_count) {
                $result[] = $this->tpl($template['divider'], $params);
            }
        }

        $result[] = $this->tpl($template['suffix'], $params);

        // "выпадающие" подсказки
        if ($params["suggest"]) {
            $result[] = $this->get_suggest_script();
        }

        return join('', $result);
    }

    /**
     * Возвращает <script> для подключения выпадающих подсказок
     */
    public function get_suggest_script() {
        $path = nc_search::get_module_url()."/suggest";
        $script = "$path/jquery-ui.custom.min.js";
        $language = nc_Core::get_object()->lang->detect_lang(1);
        $input_selector = "#nc_search_query, input.nc_search_query";

        switch (nc_search::get_setting('SuggestMode')) {
            case 'titles':
                $suggest_source = "$path/title.php?language=$language";
                $suggest_action = "window.location = ui.item.url;";
                break;
            case 'queries':
                $suggest_source = "$path/query.php?language=$language";
                $suggest_action = "jQuery(this).val(ui.item.label).parents('form').submit();";
                break;
            default:
                throw new nc_search_exception("Search module configuration error: wrong value for 'SuggestMode'");
        }
        $min_length = (int) nc_search::get_setting('SuggestionsMinInputLength');


        $result = <<<END_JS

      <script type='text/javascript'>
      (function ($) {
        $(document).ready(function() {
          var init = function() {
            $('$input_selector').autocomplete({
              minLength: $min_length,
              source: '$suggest_source',
              select: function(event, ui) { $suggest_action },
              search: function(event, ui) {
                if (/(\w+:|\()/.test($(this).val())) { return false; }
              }
            });
          };
          if (!$.ui || !$.ui.autocomplete) { 
            $.getScript('$script', init); 
          }
          else { init(); }
        });
      })(jQuery)
      </script>

END_JS;


        return preg_replace("/\s{2,}/", "", $result); // убрать лишние пробелы
    }

    /**
     * «Расширенная» форма поиска
     *
     * @param array $template  Шаблон
     *  Элементы:
     *   prefix   — начало формы
     *   input    — основное поле ввода
     *   exclude  — поле для ввода слов, которые нужно исключить
     *   field    — искать во всем документе или в заголовках
     *   interval — время изменения документа
     *   suffix   — окончание формы
     */
    public function show_advanced_form(array $template = null) {
        if (!nc_search::should('EnableSearch') || !nc_search::should("EnableAdvancedSearchForm")) {
            return "";
        }

        $params = array(
                "actionurl" => $this->get_path(),
        );

        $nc_core = nc_Core::get_object();
        $File_Mode = $nc_core->template->get_current('File_Mode');
        $interface = $nc_core->get_interface();
          
        if ($File_Mode) {
             $default_template = $this->evaluate(nc_search::get_setting($interface.'_AdvancedSearchFormTemplate'), 'advsearchform', $params);
        } else {
             $default_template = $this->evaluate(nc_search::get_setting('AdvancedSearchFormTemplate'), 'advsearchform', $params);
        }           
       
        $template = array_merge($default_template, (array) $template);

        $tpl = $template["prefix"].
                $template["input"].
                (nc_search::should('ShowAdvancedFormExcludeField') ? $template["exclude"] : "").
                (nc_search::should('ShowAdvancedFormFieldSearch') && nc_search::should('AllowFieldSearch') ? $template["field"] : "").
                (nc_search::should('ShowAdvancedFormTimeIntervals') ? $template["interval"] : "").
                $template["suffix"];

        return $this->tpl($tpl, $params);
    }

    /**
     *
     * @param string $query
     * @param string|array $area
     * @param string $params Параметры, через амперсанд
     *   - field - поле поиска. Допустимые значения: 'title'
     *   - interval - непустое значение, если включена фильтрация по дате
     *   - intervalvalue - значение интервала
     *   - intervalunit - тип интервала (hour, day, week, month)
     *   - sortby - сортировка. Если пустое значение - сортировка по релевантности.
     *     Допустимые значения: lastupdated
     *   - sortdirection - desc (по умолчанию), asc
     *   - language - язык результатов, по умолчанию определяется автоматически
     *   - curPos - текущая позиция (номер первого результата)
     *   - recNum - количество результатов на странице, по умолчанию 10 (берется из
     *     настроек компонента в разделе)
     *   - correct - пытаться исправить запросы, не давшие результатов (по умолчанию
     *     равно соответствующей настройки модуля)
     *   - nologging - не записывать запрос в журнал запросов (при просмотре
     *     результатов из админки, чтобы не искажать картину запросов)
     * @return nc_search_data_persistent_collection
     */
    public function get_results($query_string, $area = "", $params = "") {
        if (!nc_search::should('EnableSearch')) {
            return new nc_search_result();
        } // return empty collection
        $start_time = microtime(true);

        global $nc_core;
        parse_str($params, $params);

        if (isset($params["field"]) && $params["field"] && nc_search::should('AllowFieldSearch')) {
            $query_string = "$params[field]:($query_string)";
        }

        $query = new nc_search_query($query_string);

        $has_interval = isset($params["interval"]) && isset($params["intervalvalue"]) && isset($params["intervalunit"]) &&
                $params["interval"] && $params["intervalvalue"] && $params["intervalunit"];

        if ($has_interval) {
            $timestamp = strtotime("-$params[intervalvalue] $params[intervalunit]");
            $query->set('modified_after', strftime("%Y%m%d%H%M%S", $timestamp));
        }

        $allow_sort = isset($params["sortby"]) && $params["sortby"] && nc_search::should('AllowFieldSearch');
        if ($allow_sort) {
            $sortable_fields = $nc_core->db->get_col("SELECT `Name` FROM `Search_Field` WHERE `IsSortable` = 1");
            $allow_sort = in_array($params["sortby"], $sortable_fields);
        }

        if ($allow_sort) {
            $query->set('sort_by', $params["sortby"]);
            if (isset($params["sortdirection"]) && strtoupper($params["sortdirection"]) == 'ASC') {
                $query->set('sort_direction', SORT_ASC);
            }
        }

        if (isset($params["curPos"]) && $params["curPos"]) {
            $query->set('offset', (int) $params["curPos"]);
        }
        if (isset($params["recNum"]) && $params["recNum"]) {
            $query->set('limit', (int) $params["recNum"]);
        }

        if ($area) {
            if (is_array($area)) {
                $area = join(" ", $area);
            }
            $query->set('area', $area);
        }

        $language = (isset($params["language"]) && $params["language"] ? $params["language"] : $nc_core->lang->detect_lang(1));
        $query->set('language', $language);
        
        register_shutdown_function('nc_search_shutdown', $nc_core->subdivision->get_current('Hidden_URL'), $query_string);

        $query_error = false;
        try {
            $results = nc_search::find($query);
        } catch (Exception $e) {
            $query_error = true;
            $results = new nc_search_result();
            $results->set_query($query)
                    ->set_error_message($e->getMessage());
        }

        // попробуем исправить, если не было результатов?
        $try_to_correct = ($results->get_total_count() == 0 && !$query_error &&
                ((isset($params["correct"]) && $params["correct"]) ||
                nc_search::should('TryToCorrectQueries')) &&
                preg_match_all('/\s+|[\)\"][\(\"]/u', $query_string, $tmp) <= nc_search::get_setting('MaxQueryLengthForCorrection'));
        if ($try_to_correct) {
            $context = new nc_search_context(array("language" => $language, "action" => "searching"));
            $correctors = nc_search_extension_manager::get('nc_search_language_corrector', $context)
                            ->get_all();
            if (sizeof($correctors)) {
                $phrase = new nc_search_language_corrector_phrase($query_string);
                $rewritten_query = clone $query;
                foreach ($correctors as $corrector) {
                    if ($corrector->correct($phrase)) { // что-то подправили
                        // попробуем поискать!
                        $rewritten_query->set('query_string', $phrase->to_string());
                        try {
                            $corrected_results = nc_search::find($rewritten_query);
                            if (sizeof($corrected_results)) {
                                $results = $corrected_results;
                                $results->set_correction_suggestion($phrase->get_suggestion());
                                break; // exit "foreach corector"
                            }
                        } catch (Exception $e) { // может упасть, например, если у изменённого слова есть несколько базовых форм...
                        }
                    } // of "something changed"
                } // of "foreach corrector"
            } // end of "has correctors"
        } // end of "if ($try_to_correct)"

        $will_log = true;
        if (isset($params['nologging']) && $params['nologging'] && strlen($query_string)) {
            // только очень крутым чувакам разрешается не оставлять следов
            if (isset($GLOBALS['AUTH_USER_ID']) && isset($GLOBALS['perm']) && $GLOBALS["perm"]->isAccess(NC_PERM_MODULE)) {
                $will_log = false;
            }
        }

        if ($will_log && nc_search::should('SaveQueryHistory') && $query->get('offset') == 0) {
            $query->set('results_count', $results->get_total_count())
                    ->set('user_ip', ip2long($_SERVER['REMOTE_ADDR'])) // achtung! не будет работать с IPv6!
                    ->set('user_id', $GLOBALS['AUTH_USER_ID'])
                    ->set('site_id', $GLOBALS['catalogue'])
                    ->save();
        }

        $results->set_search_time(microtime(true) - $start_time);

        return $results;
    }

    /**
     * Alias for nc_search::index_area()
     * @param string $area_string
     * @param string $when
     */
    public function index_area($area_string = "allsites", $when = "now") {
        return nc_search::index_area($area_string, $when);
    }

    /**
     * @return nc_search_result
     */
    protected function make_empty_result(nc_search_query $query) {
        return $result;
    }

    /**
     * Вспомогательный метод для составления запроса из нескольких полей
     * (поле «исключить страницы, на которых встречаются слова...»)
     */
    public function make_query_string($query, $exclude = false) {
        if ($exclude && $exclude = trim($exclude)) {
            $not_op = nc_search_util::is_boolean_query($query) || nc_search_util::is_boolean_query($exclude) ? "AND NOT " : "-";
            if (strpos($exclude, " ")) {
                $exclude = "($exclude)";
            }
            if (strpos($query, " ")) {
                $query = "($query)";
            }
            $query = "$query $not_op$exclude";
        }
        return $query;
    }

    //////////////////////// Служебные методы /////////////////////////////

    /**
     * Заменить "all", "thissub", "parentsub"
     */
    protected function normalize_area($area) {
        // а может у них есть раздел /thisrootsub/? ху ноуз? пипл а стрейндж
        if (strpos($area, "/") !== false && $area != "all" && $area != "thissub" && $area != "parentsub") {
            return $area;
        }

        global $parent_sub_tree, $catalogue;
        list($current, $parent) = $parent_sub_tree;

        return strtr($area, array(
                "all" => "site$catalogue",
                "thissub" => $this->get_area_string($current),
                "parentsub" => $this->get_area_string($parent),
        ));
    }

    /**
     * @see self::normalize_area()
     */
    protected function get_area_string($row) {
        if (isset($row["Subdivision_ID"])) {
            return "sub$row[Subdivision_ID]";
        }
        if (isset($row["Catalogue_ID"])) {
            return "site$row[Catalogue_ID]";
        }
        return "";
    }

    /**
     * Путь до раздела поиска на текущем сайте
     */
    protected function get_path() {
        if (!$this->path) {
            $this->path = $this->get_search_url($GLOBALS['catalogue'], false);
        }
        return $this->path;
    }

    /**
     * Получить путь до раздела поиска на сайте с указанным идентификатором.
     * @global nc_db $db
     * @throws Exception @see nc_catalogue::get_by_id()
     * @param integer $site_id
     * @param boolean $with_host
     * @return string
     */
    public function get_search_url($site_id, $with_host = true) {
        if ($with_host && isset($this->paths[$site_id])) {
            return $this->paths[$site_id];
        }

        global $db, $nc_core;
        $path = $nc_core->SUB_FOLDER.
                $db->get_var("SELECT sub.`Hidden_URL`
                            FROM `Subdivision` AS `sub`, `Sub_Class` AS `c`
                           WHERE c.`Class_ID` = ".(int) nc_search::get_setting("ComponentID")."
                             AND c.`Subdivision_ID` = sub.`Subdivision_ID`
                             AND sub.`Catalogue_ID` = ".(int) $site_id."
                           LIMIT 1");
        if ($with_host) {
            $host = $nc_core->catalogue->get_by_id($site_id, 'Domain');
            if ($host) {
                $path = "http://$host$path";
            }
            $this->paths[$site_id] = $path;
        }

        return $path;
    }

    /**
     * Strange netcat way
     */
    protected function evaluate($_code, $_return_var, array $_vars = array()) {
        foreach ($_vars as $key => $value) {
            $_vars[$key] = str_replace('$', '', $_vars[$key]);
        }
        extract($_vars);
        eval($_code);
        return $$_return_var;
    }

    /**
     *
     * @param string $template_code
     * @param array $vars
     * @param array $replace
     * @return string
     */
    protected function tpl($template_code, array $vars = array(), array $replace = null) {
        $eval_code = '$ret = "'.$template_code.'";';
        $result = $this->evaluate($eval_code, 'ret', $vars);
        if ($replace) {
            $result = strtr($result, $replace);
        }
        return $result;
    }

}

function nc_search_shutdown($url, $query) {
    $last_error = error_get_last();
    if ($last_error['type'] === E_ERROR) {
        ob_clean();
        header("Location: ".$url."?error_query=".urlencode($query));
    }
}