<?php

if (!class_exists("nc_System")) die("Unable to load file.");

class nc_Page extends nc_System {

    protected $core;
    protected $metatags;
    // title, keywors, description
    // поля из таблицы Разделы, используемые для метаданных
    protected $title_field, $keywords_field, $description_field;
    protected $language_field;
    protected $field_usage = array();
    protected $h1 = null;

    public function __construct() {
        parent::__construct();
        $this->core = nc_Core::get_object();

        $fieldmap = $this->core->get_settings('FieldUsage');
        if ($fieldmap) $fieldmap = $this->field_usage = unserialize($fieldmap);

        $this->title_field = $fieldmap['title'];
        $this->keywords_field = $fieldmap['keywords'];
        $this->description_field = $fieldmap['description'];
        $this->language_field = $fieldmap['language'];




        $events = "updateSubdivision,checkSubdivision,uncheckSubdivision,";
        $events .= "addSubClass,updateSubClass,checkSubClass,uncheckSubClass,dropSubClass,";
        $events .= "addMessage,updateMessage,checkMessage,uncheckeMessage,dropMessage";
        $this->core->event->bind($this, array($events => "update_subdivision"));

        $events = "updateClass,updateClassTemplate";
        $this->core->event->bind($this, array($events => "update_class"));

        $this->core->event->bind($this, array('updateTemplate' => "update_template"));
    }

    /**
     * Функция получения title и мета-данных страниц
     * @see http://php.net/manual/en/function.get-meta-tags.php
     * @param string адрес страницы
     * @return array
     */
    public function get_meta_tags($url) {
        $result = array();
        $contents = @file_get_contents($url);
        if (!$contents) return false;

        nc_preg_match('/<title>([^>]*)<\/title>/si', $contents, $match);

        if (isset($match) && is_array($match) && count($match) > 0) {
            $result['title'] = strip_tags($match[1]);
        }

        nc_preg_match_all('/<[\s]*meta[\s]*name=["\']?'.'([^>\'"]*)["\']?[\s]*'.'content=["\']?([^>"\']*)["\']?[\s]*[\/]?[\s]*>/si', $contents, $match);

        if (isset($match) && is_array($match) && count($match) == 3) {
            $originals = $match[0];
            $names = $match[1];
            $values = $match[2];

            if (count($originals) == count($names) && count($names) == count($values)) {
                for ($i = 0, $limiti = count($names); $i < $limiti; $i++) {
                    $result[strtolower($names[$i])] = $values[$i];
                }
            }
        }

        return $result;
    }

    /**
     * Установить мета-тег для страницы
     * @param string title, keywords, description
     * @param string value
     */
    public function set_metatags($item, $value) {
        $this->metatags[$item] = $value;
    }

    /**
     * Получить title для страницы
     * @return string
     */
    public function get_title() {
        $r = false;
        if (is_array($this->metatags) && array_key_exists('title', $this->metatags)) {
            $r = $this->metatags['title'];
        }

        return $r;
    }

    /**
     * Получить keywords для страницы
     * @return string
     */
    public function get_keywords() {
        $r = false;
        if (is_array($this->metatags) && array_key_exists('keywords', $this->metatags)) {
            $r = $this->metatags['keywords'];
        }

        return $r;
    }

    /**
     *  Получить description для страницы
     * @return string
     */
    public function get_description() {
        $r = false;
        if (is_array($this->metatags) && array_key_exists('description', $this->metatags)) {
            $r = $this->metatags['description'];
        }

        return $r;
    }

    /**
     * Установить метаданные по данным текущего раздела
     * @param <type> $current_sub
     */
    public function set_current_metatags($current_sub) {
        if ($current_sub[$this->title_field])
                $this->set_metatags('title', $current_sub[$this->title_field]);
        if ($current_sub[$this->keywords_field])
                $this->set_metatags('keywords', $current_sub[$this->keywords_field]);
        if ($current_sub[$this->description_field])
                $this->set_metatags('description', $current_sub[$this->description_field]);
    }

    /**
     * �&#65533;мя поля, которое используется для языка
     */
    public function get_language_field() {
        return $this->language_field;
    }

    public function get_field_name($usage) {
        return $this->field_usage[$usage];
    }

    /**
     * Обновление Last-Modified у разделов
     * @param mixed номер раздела или массив с номерами, если 0 - то все
     */
    public function update_lastmodified($sub_ids = 0) {
        if (is_int($sub_ids) && $sub_ids === 0) {
            $where = '';
        } else {
            if (!is_array($sub_ids)) $sub_ids = array($sub_ids);
            $sub_ids = array_unique(array_map('intval', $sub_ids));
            if (empty($sub_ids)) return false;
            $where = " WHERE Subdivision_ID IN (".join(',', $sub_ids).") ";
        }

        $this->core->db->query("UPDATE `Subdivision` SET `".$this->get_field_name('last_modified')."` = NOW() ".$where);
    }

    /**
     * Перехват события "изменние раздела" для обновления Last-Modified
     * @param <type> $catalogue_id
     * @param <type> $sub_id
     */
    public function update_subdivision($catalogue_id, $sub_id) {
        $res = array();
        if (is_array($sub_id)) {
            foreach ($sub_id as $v) {
                $res = array_merge($res, nc_get_sub_children($v));
            }
        } else {
            $res = nc_get_sub_children($sub_id);
        }

        $this->update_lastmodified($res);
    }

    public function update_class($class_id) {
        $db = $this->core->db;
        if (!is_array($class_id)) $class_id = array($class_id);
        $class_id = array_map('intval', $class_id);
        $subs = $db->get_col("SELECT sc.Subdivision_ID FROM `Sub_Class` as `sc`, `Class` as `c`
      WHERE sc.Class_ID = c.Class_ID AND ( c.Class_ID IN (".join(',', $class_id).") OR c.ClassTemplate IN (".join(',', $class_id).") ) ");

        $this->update_lastmodified($subs);
    }

    public function update_template($id) {
        $db = $this->core->db;
        $id = intval($id);

        $childs = $this->core->template->get_childs($id);

        $t = array_merge(array($id), $childs);

        $cat = $db->get_var("SELECT `Catalogue_ID` FROM `Catalogue` WHERE `Template_ID` IN (".join(',', $t).") ");

        if ($cat) {
            $this->update_lastmodified();
        } else {
            $subs = $db->get_col("SELECT `Subdivision_ID` FROM `Subdivision` WHERE `Template_ID` IN (".join(',', $t).") ");
            if ($subs) $this->update_subdivision(0, $subs);
        }
    }

    /**
     * Метод посылает заголовок Last-Modified для текущего раздела
     * В зависимости от ncLastModifiedType заголовок может не посылаться,
     * посылаться текущее время или акутальное
     * Для титульной страницы посылается текущее время
     */
    public function send_lastmodified() {
        $current_sub = $this->core->subdivision->get_current();
        $title_sub_id = $this->core->catalogue->get_current('Title_Sub_ID');

        $lm = $this->get_field_name('last_modified');
        $lm_type = $this->get_field_name('last_modified_type');

        if ($current_sub[$lm_type] <= NC_LASTMODIFIED_NONE) return 0;

        $last_mod = false;
        if ($current_sub[$lm_type] == NC_LASTMODIFIED_CURRENT || $current_sub['Subdivision_ID'] == $title_sub_id) {
            $last_mod = time();
        } else if ($current_sub[$lm_type] == NC_LASTMODIFIED_YESTERDAY) {
            $last_mod = time() - 86400;
        } else if ($current_sub[$lm_type] == NC_LASTMODIFIED_HOUR) {
            $last_mod = time() - 3600;
        } else if ($current_sub[$lm_type] == NC_LASTMODIFIED_ACTUAL && $current_sub[$lm]) {
            $last_mod = strtotime($current_sub[$lm]);
        }

        if ($last_mod) {
            header("Last-Modified: ".nc_timestamp_to_gmt($last_mod));
        }
    }
    
    public function get_h1() {
        return $this->h1;
    }
    
    public function set_h1($h1) {
        $this->h1 = $h1;
    }
}


?>