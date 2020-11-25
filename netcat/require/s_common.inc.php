<?php

/* $Id: s_common.inc.php 8390 2012-11-09 14:03:32Z vadim $ */

/**
 * Функция выполнения запроса к БД, вывод результатов через $tempate
 * @param string MySQL запрос
 * @param string шаблон для повторения
 * @param string разделитель между строками
 * @return string
 */
function listQuery($query, $template = "", $divider = '') {
    global $db, $SHOW_MYSQL_ERRORS, $perm;

    # скроем ошибки в случае неправильного запроса, чтобы вывести свое сообщение об ошибке
    $db->hide_errors();

    $db->last_error = '';
    $db->num_rows = 0;
    # выполним запрос
    $res = $db->get_results($query, ARRAY_A);

    # покажем ошибку, если есть
    if ($db->last_error && is_object($perm) && $perm->isSupervisor()) {
        $num_error = sizeof($db->captured_errors) - 1; // нужно узнать номер последней ошибки
        $result = "<hr size='1' style='color:#CCCCCC' noshade><b>Query:</b> " . $db->captured_errors[$num_error]['query'] . "<br/><br/>\r\n<b>Error:</b> " . $db->captured_errors[$num_error]['error_str'] . "<hr size='1' style='color:#CCCCCC' noshade><br/>";
    }

    # если показ ошибок MySQL включен
    if ($SHOW_MYSQL_ERRORS == "on" && is_object($perm) && $perm->isSupervisor()) {
        $db->show_errors();
    }

    # количество записей
    $cnt = $db->num_rows;

    # основной цикл
    if ($cnt && $template) {
        for ($i = 0; $i < $cnt; $i++) {
            $data = $res[$i];
            eval("\$result.= \"$template\";");
            // для послднего элемента разделитель не нужен
            if ($i <> $cnt - 1)
                $result .= $divider;
        }
    }

    return $result;
}

/**
 * DEPRECATED - left for compatibility
 * Функция генерации формы добавления, редактирования, поиска, в зависимости от $action
 * @param string "add", "change", "search", "message"
 * @param array $fields массив с полями
 * @return string форма
 */
function nc_fields_form($action, $fields = null, $class_id = 0) {
    global $ROOT_FOLDER, $MODULE_VARS, $SUB_FOLDER, $HTTP_ROOT_PATH, $ADMIN_PATH;
    global $AUTHORIZE_BY, $systemTableID, $user_table_mode, $admin_mode, $nc_core;

    if (!in_array($action, array("add", "change", "search", "message")))
        return false;###|| ($systemTableID && !$user_table_mode)
    // необходимо записать в глобальные переменные, поскольку они используется
    // в функция вида nc_string_field

    if (!empty($fields)) {
        $GLOBALS['fldCount'] = count($fields);
        $GLOBALS['fldID'] = array();
        $GLOBALS['fld'] = array();
        $GLOBALS['fldName'] = array();
        $GLOBALS['fldType'] = array();
        $GLOBALS['fldFmt'] = array();
        $GLOBALS['fldNotNull'] = array();
        $GLOBALS['fldDefault'] = array();
        $GLOBALS['fldTypeOfEdit'] = array();
        $GLOBALS['fldDoSearch'] = array();
        foreach ($fields as $v) {
            $GLOBALS['fldID'][] = $v['id'];
            $GLOBALS['fld'][] = $v['name'];
            $GLOBALS['fldName'][] = $v['description'];
            $GLOBALS['fldType'][] = $v['type'];
            $GLOBALS['fldFmt'][] = $v['format'];
            $GLOBALS['fldNotNull'][] = $v['not_null'];
            $GLOBALS['fldDefault'][] = $v['default'];
            $GLOBALS['fldTypeOfEdit'][] = $v['edit_type'];
            $GLOBALS['fldDoSearch'][] = $v['search'];
        }
    }

    if (isset($GLOBALS['fld']) && is_array($GLOBALS['fld'])) {
        $fldCount = $GLOBALS['fldCount'];
        $fldID = $GLOBALS['fldID'];
        $fld = $GLOBALS['fld'];
        $fldName = $GLOBALS['fldName'];
        $fldValue = isset($GLOBALS['fldValue']) ? $GLOBALS['fldValue'] : '';
        $fldType = $GLOBALS['fldType'];
        $fldFmt = $GLOBALS['fldFmt'];
        $fldNotNull = $GLOBALS['fldNotNull'];
        $fldInheritance = isset($GLOBALS['fldInheritance']) ? $GLOBALS['fldInheritance'] : 0;
        $fldDefault = $GLOBALS['fldDefault'];
        $fldTypeOfEdit = $GLOBALS['fldTypeOfEdit'];
        $fldDoSearch = $GLOBALS['fldDoSearch'];
    }

    # тут будет храниться вывод
    $result = "";

    if ($action == 'add' && $systemTableID && $user_table_mode) {
        $nc_auth = nc_auth::get_object();
        return $nc_auth->add_form();
    }

    switch ($action) {
        # форма добавления или редактирования
        case "add":
        case "change":

            if (!$systemTableID || ($systemTableID && $user_table_mode)) {
                # начало вывода формы
                $result .= "\".( \$warnText ? \"<div class='warnText'>\$warnText</div>\" : NULL ).\"\r\n";
                $result .= "<form name='adminForm' id='adminForm' enctype='multipart/form-data' method='post' action='\".\$SUB_FOLDER.\$HTTP_ROOT_PATH.\"" . ( $action == "add" ? "add" : ($action == "change" ? "message" : "") ) . ".php'>\r\n";
                # основной префикс формы

                $result .= "<div id='nc_moderate_form'>\r\n<div class='nc_clear'></div>\r\n";

                $result .= "<input name='admin_mode' type='hidden' value='\$admin_mode' />\r\n";
                $result .= "\".\$nc_core->token->get_input().\" \r\n";
                $result .= "<input name='catalogue' type='hidden' value='\$catalogue' />\r\n";
                $result .= "<input name='cc' type='hidden' value='\$cc' />\r\n";
                $result .= "<input name='sub' type='hidden' value='\$sub' />\r\n";
                $result .= ( $action == "change" ? "<input name='message' type='hidden' value='\$message' />\r\n" : "");
                $result .= "<input name='posting' type='hidden' value='1' />\r\n";
                $result .= "<input name='curPos' type='hidden' value='\$curPos' />\r\n";
                $result .= "<input name='f_Parent_Message_ID' type='hidden' value='\$f_Parent_Message_ID' />\r\n";


                # префикс формы для админского режима
                $result .= "\".nc_form_moderate('" . $action . "', \$admin_mode, " . ($user_table_mode + 0) . ", \$systemTableID, \$current_cc, (isset(\$f_Checked) ? \$f_Checked  : null), \$f_Priority , \$f_Keyword, \$f_ncTitle, \$f_ncKeywords, \$f_ncDescription ).\"\r\n";
                $result .= "</div>\r\n\r\n";                 

            }

            # проходимся по полям
            for ($i = 0; $i < $fldCount; $i++) {
                # описание поля
                $fldNameTempl = $fldName[$i] . ($fldNotNull[$i] ? " (*)" : "") . ":<br />\r\n";

                # редактировать поле могут:
                $no_edit = ($fldTypeOfEdit[$i] == 2 && !nc_field_check_admin_perm() ) || $fldTypeOfEdit[$i] == 3;

                if ($user_table_mode && $fld[$i] == $AUTHORIZE_BY && $action == "change" && !$nc_core->get_settings('allow_change_login', 'auth'))
                    $no_edit = true;
                # если поле не для редактирования - хендовер
                if ($no_edit)
                    continue;

                # типы полей
                switch ($fldType[$i]) {
                    case 1:
                        // String
                        $result.= "\".nc_string_field(\"" . $fld[$i] . "\", \"maxlength='255' size='50'\", " . ( $class_id ? $class_id : "\$classID") . ", 1).\"<br />\r\n";
                        break;

                    case 2:
                        // Int
                        $result.= "\".nc_int_field(\"" . $fld[$i] . "\", \"maxlength='12' size='12'\", " . ( $class_id ? $class_id : "\$classID") . ", 1).\"<br />\r\n";
                        break;

                    case 3:
                        // Text
                        $result.= "\".nc_text_field(\"" . $fld[$i] . "\", \"\", " . ( $class_id ? $class_id : "\$classID") . ", 1).\"<br />\r\n";
                        break;

                    case 4:
                        // List
                        $result.= "\".nc_list_field(\"" . $fld[$i] . "\", \"\", " . ( $class_id ? $class_id : "\$classID") . ", 1).\"<br />\r\n";
                        break;

                    case 5:
                        // Bool
                        $result.= "\".nc_bool_field(\"" . $fld[$i] . "\", \"\", " . ( $class_id ? $class_id : "\$classID") . ", 1).\"<br />\r\n";
                        break;

                    case 6:
                        // File
                        $result.= "\".nc_file_field(\"" . $fld[$i] . "\", \"size='50'\", " . ( $class_id ? $class_id : "\$classID") . ", 1).\"<br />\r\n";
                        break;

                    case 7:
                        // Float
                        $result.= "\".nc_float_field(\"" . $fld[$i] . "\", \"maxlength='12' size='12'\", " . ( $class_id ? $class_id : "\$classID") . ", 1).\"<br />\r\n";
                        break;

                    case 8:
                        // DateTime
                        $result.= "\".nc_date_field(\"" . $fld[$i] . "\", \"\", " . ( $class_id ? $class_id : "\$classID") . ", 1).\"<br />\r\n";
                        break;

                    case 9:
                        // Relation
                        $result.= "\".nc_related_field(\"" . $fld[$i] . "\").\"<br />\r\n";
                        break;
                    case 10:
                        // Multiselect
                        $result.= "\".nc_multilist_field(\"" . $fld[$i] . "\", \"\", \"\", " . ( $class_id ? $class_id : "\$classID") . ", 1).\"<br />\r\n";
                        break;
                    case 11:
                        // Multifile
                        $result .= "\".\$f_{$fld[$i]}->form().\"<br />\r\n";
                        break;
                }
                $result.= "<br />\r\n";
            }

            if (!$systemTableID || ($systemTableID && $user_table_mode)) {
                # caption для кнопки
                switch ($action) {
                    case "add":
                        $submitBtnName = "NETCAT_MODERATION_BUTTON_ADD";
                        break;
                    case "change":
                        $submitBtnName = "NETCAT_MODERATION_BUTTON_CHANGE";
                        break;
                }
                $resetBtnName = "NETCAT_MODERATION_BUTTON_RESET";

                if ($user_table_mode && $posting == 0 && $action == "add") {
                    $result.= NETCAT_MODERATION_PASSWORD . ":<br/><input name='Password1' type='password' size='25' maxlength='32' value='' /><br/><br/>";
                    $result.= NETCAT_MODERATION_PASSWORDAGAIN . ":<br/><input name='Password2' type='password' size='25' maxlength='32' value='' /><br/><br/>";
                }

                # защита картинкой
                if ($action == "add" && $MODULE_VARS["captcha"] && function_exists("imagegif")) {
                    $result.= "\".(!\$AUTH_USER_ID && \$current_cc['UseCaptcha'] && \$MODULE_VARS['captcha'] ? nc_captcha_formfield().\"<br/><br/>\".NETCAT_MODERATION_CAPTCHA.\" (*):<br/><input type='text' name='nc_captcha_code' size='10'><br/><br/>\" : \"\").\"\r\n";
                }

                $result.= "<div>\".NETCAT_MODERATION_INFO_REQFIELDS.\"</div><br/>\r\n";
                $result.= "\".nc_submit_button(" . $submitBtnName . ").\"\r\n";
                $result.= "</form>";
            }

            break;
        # поиск
        case "search":
            # функция генерации формы поиска из файла "/require/s_list.inc.php"
            # для работы нужны данные из "message_fields.php"
            $srchFrm = showSearchForm($fldName, $fldType, $fldDoSearch, $fldFmt);
            // если нет полей для поиска
            if (!$srchFrm)
                return "";
            $form_action = "\".(\$admin_mode ? \"\".\$HTTP_ROOT_PATH.\"index.php?\" : \"\".\$SUB_FOLDER.\$current_sub['Hidden_URL'].\$current_cc['EnglishName'].\".html\" ).\"";

            //$form_action = "\".\$SUB_FOLDER.\$current_sub['Hidden_URL'].\$current_cc['EnglishName'].\".html";

            $result.= "<form action='" . $form_action . "' method='get'>\r\n";
            $result.= "<input type='hidden' name='action' value='index' />\r\n";
            $result.= "<input type='hidden' name='admin_mode' value='\".\$admin_mode.\"' />\r\n";
            $result.= " \".( \$inside_admin ? \"<input type='hidden' name='inside_admin' value='1' />\r\n<input type='hidden' name='cc' value='\".\$cc.\"' />\r\n\" : \"\").\" ";
            $result.= $srchFrm;
            $result.= "<input value='\".NETCAT_SEARCH_FIND_IT.\"' type='submit' />\r\n";
            $result.= "</form>";
            break;
        case "message":
            # Альтернативная форма удаления

            $result = "\";\r\n" .
                    "\$f_delete_true = \$admin_mode\r\n" .
                    "  ? \$admin_url_prefix.\"message.php?" . ( $nc_core->token->is_use('drop') ? "\".\$nc_core->token->get_url().\"&amp;" : "") . "catalogue=\".\$catalogue.\"&sub=\".\$sub.\"&cc=\".\$cc.\"&message=\".\$message.\"&delete=1&posting=1&curPos=\".\$curPos.\"&admin_mode=1\".\$system_env['AdminParameters']\r\n" .
                    "  : \$SUB_FOLDER.\$current_sub['Hidden_URL'].\"drop_\".\$current_cc['EnglishName'].\"_\".\$message.\".html" . ( $nc_core->token->is_use('drop') ? "?\".\$nc_core->token->get_url().\"" : "") . "\";\r\n" .
                    "\$result .= sprintf(NETCAT_MODERATION_WARN_COMMITDELETION, \$message).\"<br/><br/>\r\n";
            $result .= "<a href='\".\$f_delete_true.\"'>\".NETCAT_MODERATION_COMMON_KILLONE.\"</a> | <a href='\".\$goBackLink.\$system_env['AdminParameters'].\"'>\".NETCAT_MODERATION_BACKTOSECTION.\"</a>\r\n";
    }

    return $result;
}

/**
 * Функция генерации формы добавления, редактирования, поиска, в зависимости от $action
 * @param string "add", "change", "search", "message"
 * @param array $fields массив с полями
 * @return string форма
 */
function nc_fields_form_fs($action, $fields = null, $class_id = 0) {
    global $ROOT_FOLDER, $MODULE_VARS, $SUB_FOLDER, $HTTP_ROOT_PATH, $ADMIN_PATH;
    global $AUTHORIZE_BY, $systemTableID, $user_table_mode, $admin_mode, $nc_core;

    if (!in_array($action, array("add", "change", "search", "message")))
        return false;###|| ($systemTableID && !$user_table_mode)
    // необходимо записать в глобальные переменные, поскольку они используется
    // в функция вида nc_string_field

    if (!empty($fields)) {
        $GLOBALS['fldCount'] = count($fields);
        $GLOBALS['fldID'] = array();
        $GLOBALS['fld'] = array();
        $GLOBALS['fldName'] = array();
        $GLOBALS['fldType'] = array();
        $GLOBALS['fldFmt'] = array();
        $GLOBALS['fldNotNull'] = array();
        $GLOBALS['fldDefault'] = array();
        $GLOBALS['fldTypeOfEdit'] = array();
        $GLOBALS['fldDoSearch'] = array();
        foreach ($fields as $v) {
            $GLOBALS['fldID'][] = $v['id'];
            $GLOBALS['fld'][] = $v['name'];
            $GLOBALS['fldName'][] = $v['description'];
            $GLOBALS['fldType'][] = $v['type'];
            $GLOBALS['fldFmt'][] = $v['format'];
            $GLOBALS['fldNotNull'][] = $v['not_null'];
            $GLOBALS['fldDefault'][] = $v['default'];
            $GLOBALS['fldTypeOfEdit'][] = $v['edit_type'];
            $GLOBALS['fldDoSearch'][] = $v['search'];
        }
    }

    if (isset($GLOBALS['fld']) && is_array($GLOBALS['fld'])) {
        $fldCount = $GLOBALS['fldCount'];
        $fldID = $GLOBALS['fldID'];
        $fld = $GLOBALS['fld'];
        $fldName = $GLOBALS['fldName'];
        $fldValue = isset($GLOBALS['fldValue']) ? $GLOBALS['fldValue'] : '';
        $fldType = $GLOBALS['fldType'];
        $fldFmt = $GLOBALS['fldFmt'];
        $fldNotNull = $GLOBALS['fldNotNull'];
        $fldInheritance = isset($GLOBALS['fldInheritance']) ? $GLOBALS['fldInheritance'] : 0;
        $fldDefault = $GLOBALS['fldDefault'];
        $fldTypeOfEdit = $GLOBALS['fldTypeOfEdit'];
        $fldDoSearch = $GLOBALS['fldDoSearch'];
    }

    # тут будет храниться вывод
    $result = "";

    if ($action == 'add' && $systemTableID && $user_table_mode) {
        $nc_auth = nc_auth::get_object();
        return $nc_auth->add_form_fs();
    }

    switch ($action) {
        # форма добавления или редактирования
        case "add":
        case "change":

            if (!$systemTableID || ($systemTableID && $user_table_mode)) {
                $result = "<?=( \$warnText ? \"<div class='warnText'><?= \$warnText ?></div>\" : NULL )?>
<form name='adminForm' id='adminForm' enctype='multipart/form-data' method='post' action='<?= \$SUB_FOLDER ?><?= \$HTTP_ROOT_PATH ?>" . ( $action == "add" ? "add" : ($action == "change" ? "message" : "") ) . ".php'>
<div id='nc_moderate_form'>
<div class='nc_clear'></div>
<input name='admin_mode' type='hidden' value='<?= \$admin_mode ?>' />
<?= \$nc_core->token->get_input() ?>
<input name='catalogue' type='hidden' value='<?= \$catalogue ?>' />
<input name='cc' type='hidden' value='<?= \$cc ?>' />
<input name='sub' type='hidden' value='<?= \$sub ?>' />";
$result .= ( $action == "change" ? "<input name='message' type='hidden' value='<?= \$message ?>' />\r\n" : "");
$result .= "<input name='posting' type='hidden' value='1' />
<input name='curPos' type='hidden' value='<?= \$curPos ?>' />
<input name='f_Parent_Message_ID' type='hidden' value='<?= \$f_Parent_Message_ID ?>' />
<?= nc_form_moderate('" . $action . "', \$admin_mode, " . ($user_table_mode + 0) . ", \$systemTableID, \$current_cc, (isset(\$f_Checked) ? \$f_Checked  : null), \$f_Priority , \$f_Keyword, \$f_ncTitle, \$f_ncKeywords, \$f_ncDescription ) ?>
</div>
";          
            }            
            # проходимся по полям
            for ($i = 0; $i < $fldCount; $i++) {
                # описание поля
                $fldNameTempl = $fldName[$i] . ($fldNotNull[$i] ? " (*)" : "") . ":<br />\r\n";

                # редактировать поле могут:
                $no_edit = ($fldTypeOfEdit[$i] == 2 && !nc_field_check_admin_perm() ) || $fldTypeOfEdit[$i] == 3;

                if ($user_table_mode && $fld[$i] == $AUTHORIZE_BY && $action == "change" && !$nc_core->get_settings('allow_change_login', 'auth'))
                    $no_edit = true;
                # если поле не для редактирования - хендовер
                if ($no_edit)
                    continue;

                # типы полей
                switch ($fldType[$i]) {
                    case 1:
                        // String
                        $result.= "<?= nc_string_field('$fld[$i]', \"maxlength='255' size='50'\", (\$class_id ? \$class_id : \$classID), 1) ?><br />\r\n";
                        break;
                    case 2:
                        // Int
                        $result.= "<?= nc_int_field('$fld[$i]', \"maxlength='12' size='12'\", (\$class_id ? \$class_id : \$classID), 1) ?><br />\r\n";
                        break;
                    case 3:
                        // Text
                        $result.= "<?= nc_text_field('$fld[$i]', \"\", (\$class_id ? \$class_id : \$classID), 1) ?><br />\r\n";
                        break;
                    case 4:
                        // List
                        $result.= "<?= nc_list_field('$fld[$i]', \"\", (\$class_id ? \$class_id : \$classID), 1) ?><br />\r\n";
                        break;
                    case 5:
                        // Bool
                        $result.= "<?= nc_bool_field('$fld[$i]', \"\", (\$class_id ? \$class_id : \$classID ), 1) ?><br />\r\n";
                        break;
                    case 6:
                        // File
                        $result.= "<?= nc_file_field('$fld[$i]', \"size='50'\", (\$class_id ? \$class_id : \$classID), 1) ?><br />\r\n";
                        break;
                    case 7:
                        // Float
                        $result.= "<?= nc_float_field('$fld[$i]', \"maxlength='12' size='12'\", ( \$class_id ? \$class_id : \$classID), 1) ?><br />\r\n";
                        break;
                    case 8:
                        // DateTime
                        $result.= "<?= nc_date_field('$fld[$i]', \"\", (\$class_id ? \$class_id : \$classID), 1) ?><br />\r\n";
                        break;
                    case 9:
                        // Relation
                        $result.= "<?= nc_related_field('$fld[$i]', \"\") ?><br />\r\n";
                        break;
                    case 10:
                        // Multiselect
                        $result.= "<?= nc_multilist_field('$fld[$i]', \"\", \"\", (\$class_id ? \$class_id : \$classID), 1) ?><br />\r\n";
                        break;
                    case 11:
                        // Multifile
                        $result .= "<?= \$f_{$fld[$i]}->form() ?><br />\r\n";
                        break;
                }
                $result.= "<br />\r\n";
            }

            if (!$systemTableID || ($systemTableID && $user_table_mode)) {
				# caption для кнопки
                switch ($action) {
                    case "add":
                        $submitBtnName = "NETCAT_MODERATION_BUTTON_ADD";
                        break;
                    case "change":
                        $submitBtnName = "NETCAT_MODERATION_BUTTON_CHANGE";
                        break;
                }
                $resetBtnName = "NETCAT_MODERATION_BUTTON_RESET";

                if ($user_table_mode && $posting == 0 && $action == "add") {
                    $result.= NETCAT_MODERATION_PASSWORD . ":<br/><input name='Password1' type='password' size='25' maxlength='32' value='' /><br/><br/>";
                    $result.= NETCAT_MODERATION_PASSWORDAGAIN . ":<br/><input name='Password2' type='password' size='25' maxlength='32' value='' /><br/><br/>";
                }

                # защита картинкой
                if ($action == "add" && $MODULE_VARS["captcha"] && function_exists("imagegif")) {
                    $result.= "<? if (!\$AUTH_USER_ID && \$current_cc['UseCaptcha'] && \$MODULE_VARS['captcha']) { ?><?= nc_captcha_formfield() ?><br/><br/><?= NETCAT_MODERATION_CAPTCHA ?> (*):<br/><input type='text' name='nc_captcha_code' size='10'><br/><br/><? } ?>\r\n";
                }

                $result.= "<div><?= NETCAT_MODERATION_INFO_REQFIELDS ?></div><br/>\r\n";
                $result.= "<?= nc_submit_button($submitBtnName) ?>\r\n";
                $result.= "</form>";
            }

            break;
        # поиск
        case "search":
            # функция генерации формы поиска из файла "/require/s_list.inc.php"
            # для работы нужны данные из "message_fields.php"
            $srchFrm = showSearchForm($fldName, $fldType, $fldDoSearch, $fldFmt);
            // если нет полей для поиска
            if (!$srchFrm)
                return "";
            
            $form_action = "<?= (\$admin_mode ? \$HTTP_ROOT_PATH.'index.php?' : \$SUB_FOLDER.\$current_sub['Hidden_URL'].\$current_cc['EnglishName'].'.html' ) ?>";
            
            $result.= "<form action='$form_action' method='get'>
<input type='hidden' name='action' value='index' />
<input type='hidden' name='admin_mode' value='<?= \$admin_mode ?>' />
<? if (\$inside_admin) : ?> 
        <input type='hidden' name='inside_admin' value='1' />
        <input type='hidden' name='cc' value='<?= \$cc ?>' />
<? endif; ?>
$srchFrm
<input value='<?= NETCAT_SEARCH_FIND_IT ?>' type='submit' />
</form>";

            break;
        case "message":
            # Альтернативная форма удаления

            $result = "<? " .
                    "\$f_delete_true = \$admin_mode\r\n" .
                    "  ? \$admin_url_prefix.\"message.php?" . ( $nc_core->token->is_use('drop') ? "\".\$nc_core->token->get_url().\"&amp;" : "") . "catalogue=\".\$catalogue.\"&sub=\".\$sub.\"&cc=\".\$cc.\"&message=\".\$message.\"&delete=1&posting=1&curPos=\".\$curPos.\"&admin_mode=1\".\$system_env['AdminParameters']\r\n" .
                    "  : \$SUB_FOLDER.\$current_sub['Hidden_URL'].\"drop_\".\$current_cc['EnglishName'].\"_\".\$message.\".html" . ( $nc_core->token->is_use('drop') ? "?\".\$nc_core->token->get_url().\"" : "") . "\";?>\r\n" .
                    "<? sprintf(NETCAT_MODERATION_WARN_COMMITDELETION, \$message) ?><br/><br/>\r\n";
            $result .= "<a href='<?= \$f_delete_true ?>'><?= NETCAT_MODERATION_COMMON_KILLONE ?></a> | <a href='<?= \$goBackLink.\$system_env['AdminParameters'] ?>'><?= NETCAT_MODERATION_BACKTOSECTION ?></a>\r\n";
    }

    return $result;
}


function nc_form_moderate($action, $admin_mode, $user_table_mode, $sys_table_id, $current_cc, $f_Checked = null, $f_Priority = '', $f_Keyword = '', $f_ncTitle = '', $f_ncKeywords = '', $f_ncDescription = '') {
    if($admin_mode) {
        return null;
    } else {
        return "<input type='hidden' name='f_Checked' value='1' />";
    }
}

/**
 * Функция генерации условия добавления, редактирования от $action
 * @param string "addcond", "editcond"
 * @return string код условия
 */
function nc_fields_condition_code($action) {

    if (!in_array($action, array("addcond", "editcond")) || $systemTableID)
        return false;
    if (is_array($GLOBALS['fld'])) {
        $fldCount = $GLOBALS['fldCount'];
        $fldID = $GLOBALS['fldID'];
        $fld = $GLOBALS['fld'];
        $fldName = $GLOBALS['fldName'];
        $fldValue = $GLOBALS['fldValue'];
        $fldType = $GLOBALS['fldType'];
        $fldFmt = $GLOBALS['fldFmt'];
        $fldNotNull = $GLOBALS['fldNotNull'];
        $fldInheritance = $GLOBALS['fldInheritance'];
        $fldDefault = $GLOBALS['fldDefault'];
        $fldTypeOfEdit = $GLOBALS['fldTypeOfEdit'];
        $fldDoSearch = $GLOBALS['fldDoSearch'];
    }

    $if_res = array();
    $result = "";

    # проходимся по полям
    for ($i = 0; $i < $fldCount; $i++) {
        # если редактирование недоступно никому - пропускаем
        if ($fldTypeOfEdit[$i] == 3)
            continue;

        if ($fldType[$i] == 8) {
            $format = nc_field_parse_format($fldFmt[$i], 8);
            switch ($format['type']) {
                case "event":
                    $if_res[] = "!(\$f_" . $fld[$i] . "_day" . " && " . "\$f_" . $fld[$i] . "_month" . " && " . "\$f_" . $fld[$i] . "_year" . " && " . "\$f_" . $fld[$i] . "_hours" . " && " . "\$f_" . $fld[$i] . "_minutes" . " && " . "\$f_" . $fld[$i] . "_seconds)";
                    break;
                case "event_date":
                    $if_res[] = "!(\$f_" . $fld[$i] . "_day" . " && " . "\$f_" . $fld[$i] . "_month" . " && " . "\$f_" . $fld[$i] . "_year)";
                    break;
                case "event_time":
                    $if_res[] = "!(\$f_" . $fld[$i] . "_hours" . " && " . "\$f_" . $fld[$i] . "_minutes" . " && " . "\$f_" . $fld[$i] . "_seconds)";
                    break;
            }
        } else {
            if ($fldNotNull[$i] && $fldType[$i] != 5)
                $if_res[] = "!\$f_" . $fld[$i];
        }
    }

    if (!empty($if_res)) {
        $result.= "if(" . join(" || ", $if_res) . ") {\r\n";
        $result.= "\t\$posting = 0;\r\n";
        $result.= "\t#information text\r\n";
        $result.= "\t\$warnText = NETCAT_MODERATION_INFO_REQFIELDS;\r\n";
        $result.= "}\r\n";
    }

    return $result;
}

/**
 * Функция генерации действий от $action
 * @param string "addaction", "editaction", "checkaction", "deleteaction"
 * @return string код действия
 */
function nc_fields_action_code($action) {
    global $MODULE_VARS;

    if (!in_array($action, array("addaction", "editaction", "checkaction", "deleteaction")) || $systemTableID)
        return false;

    if (is_array($GLOBALS['fld'])) {
        $fldCount = $GLOBALS['fldCount'];
        $fld = $GLOBALS['fld'];
    }

    $result = "";


    switch ($action) {
        case "addaction":
            $msg_const = "NETCAT_MODERATION_MSG_OBJADD";
            break;
        case "editaction":
            $msg_const = "NETCAT_MODERATION_MSG_OBJCHANGED";
            break;
        case "checkaction":
            $msg_const = "(\$checked-1 ? ( count(\$messages) == 1 ? NETCAT_MODERATION_OBJISON : NETCAT_MODERATION_OBJSAREON) :\r\n \t\t\t (count(\$messages) == 1 ? NETCAT_MODERATION_OBJISOFF : NETCAT_MODERATION_OBJSAREOFF) )";
            break;
        case "deleteaction":
            $msg_const = "NETCAT_MODERATION_MSG_OBJDELETED";
            $msg_const2 = "NETCAT_MODERATION_MSG_OBJSDELETED";
            break;
    }



    $result.= "\";\r\n";
    $result.= "if(\$inside_admin) {\r\n";
    $result.= "\tob_end_clean();\r\n";
    $result.= "\theader(\"Location: \".\$goBackLink.\"&inside_admin=1\");\r\n";
    $result.= "\texit;\r\n";
    $result.= "}\r\n";
    $result.= "else {\r\n";
    if ($action == "deleteaction") {
        $result.= "\tif ( is_array(\$message) ){\r\n";
        $result.= "\t\techo " . $msg_const2 . ";\r\n";
        $result.= "\t} else {\r\n";
        $result.= "\t\techo " . $msg_const . ";\r\n";
        $result.= "\t}\r\n";
    } else if ($action == "addaction") {
        $result.= "\techo \$IsChecked ? NETCAT_MODERATION_MSG_OBJADD : NETCAT_MODERATION_MSG_OBJADDMOD;\r\n";
    } else {
        $result.= "\techo " . $msg_const . ";\r\n";
    }
    $result.= "\techo \"<br /><br />\".\$goBack;\r\n";
    $result.= "}\r\n";
    $result.= "echo \"";

    return $result;
}

/**
 * Функция генерации действий от $action
 * @param string "addaction", "editaction", "checkaction", "deleteaction"
 * @return string код действия
 */
function nc_fields_action_code_fs($action) {
    global $MODULE_VARS;

    if (!in_array($action, array("addaction", "editaction", "checkaction", "deleteaction")) || $systemTableID)
        return false;

    if (is_array($GLOBALS['fld'])) {
        $fldCount = $GLOBALS['fldCount'];
        $fld = $GLOBALS['fld'];
    }

    $result = "";


    switch ($action) {
        case "addaction":
            $msg_const = "NETCAT_MODERATION_MSG_OBJADD";
            break;
        case "editaction":
            $msg_const = "NETCAT_MODERATION_MSG_OBJCHANGED";
            break;
        case "checkaction":
            $msg_const = "(\$checked-1 ? ( count(\$messages) == 1 ? NETCAT_MODERATION_OBJISON : NETCAT_MODERATION_OBJSAREON) :\r\n \t\t\t (count(\$messages) == 1 ? NETCAT_MODERATION_OBJISOFF : NETCAT_MODERATION_OBJSAREOFF) )";
            break;
        case "deleteaction":
            $msg_const = "NETCAT_MODERATION_MSG_OBJDELETED";
            $msg_const2 = "NETCAT_MODERATION_MSG_OBJSDELETED";
            break;
    }



    $result.= "<?\r\nif(\$inside_admin) {
    ob_end_clean();
    header('Location: '.\$goBackLink.'&inside_admin=1');
    exit;
} else { \n";
    if ($action == "deleteaction") {
        $result.= "if (is_array(\$message)){";
        $result.= "echo " . $msg_const2 . ";";
        $result.= "} else {";
        $result.= "echo " . $msg_const . ";";
        $result.= "}";
    } else if ($action == "addaction") {
        $result.= "\techo (\$IsChecked ? NETCAT_MODERATION_MSG_OBJADD : NETCAT_MODERATION_MSG_OBJADDMOD);\r\n";
    } else {
        $result.= "\techo " . $msg_const . ";\r\n";
    }
    $result.= "\techo \"<br /><br />\".\$goBack;\r\n";
    $result.= "}\r\n?>";

    return $result;
}

/**
 * Функция рисует поле по $field_name
 * @param string имя поля
 * @param string дополнительные атрибуты
 * @param int идентификатор компонента, его стоит указывать при вызове функции т.к. в функции s_list_class() его глобальное значение будет иное
 * @param bool выводить описание поля или нет
 * @return string поле
 */
function nc_put_field($field_name, $style = "", $classID = "", $caption = false) {
    global $db, $systemTableID;

    if (!$classID)
        global $classID;

    if (!$classID)
        return false;
    $field_name = $db->escape($field_name);

    # данные о поле
    if ($systemTableID == 3) { // Поле из таблицы "Пользователи"
        $field_attr = $db->get_var("SELECT `TypeOfData_ID` FROM `Field` WHERE Class_ID = '0' AND `System_Table_ID` = '3' AND Field_Name = '" . $field_name . "'");
    } else { // Поле из компонента
        $field_attr = $db->get_var("SELECT `TypeOfData_ID` FROM `Field` WHERE Class_ID = '" . intval($classID) . "' AND Field_Name = '" . $field_name . "'");
    }

    if (!$field_attr) {
        trigger_error("<b>nc_put_field()</b>: Incorrect field name (" . $field_name . ")", E_USER_WARNING);
        return false;
    }

    switch ($field_attr) {
        # Тип поля "Строка"
        case 1:
            $result = nc_string_field($field_name, $style, $classID, $caption ? 1 : 0);
            break;
        # Тип поля "Целое число"
        case 2:
            $result = nc_int_field($field_name, $style, $classID, $caption ? 1 : 0);
            break;
        # Тип поля "Текстовый блок"
        case 3:
            $result = nc_text_field($field_name, $style, $classID, $caption ? 1 : 0);
            break;
        # Тип поля "Список"
        case 4:
            #$result = nc_list_select($field_attr['Format'], $field_name);
            $result = nc_list_field($field_name, $style, $classID, $caption ? 1 : 0, "", "");
            break;
        # Тип поля "Логическая переменная"
        case 5:
            $result = nc_bool_field($field_name, $style, $classID, $caption ? 1 : 0);
            break;
        # Тип поля "Файл"
        case 6:
            $result = nc_file_field($field_name, $style, $classID, $caption ? 1 : 0);
            break;
        # Тип поля "Число с плавающей запятой"
        case 7:
            $result = nc_float_field($field_name, $style, $classID, $caption ? 1 : 0);
            break;
        # Тип поля "Дата и время"
        case 8:
            $result = nc_date_field($field_name, $style, $classID, $caption ? 1 : 0);
            break;
        # Тип поля "Связь с другим объектом"
        case 9:
            $result = nc_related_field($field_name);
        # Тип поля "MultiSelect"
        case 10:
            $result = nc_multilist_field($field_name, $style, "", $classID, $caption ? 1 : 0, "", "");
            break;
    }

    return $result;
}

/**
 * Функция отдаёт массивы полей, для генерации альтернативных форм
 * @param int идентификатор компонента
 * @param string имя поля
 * @param bool принудительно вытащить из базы
 * @return array
 */
function nc_get_field_params($field_name, $classID, $getData = false) {
    global $db, $message, $UserID, $action, $posting, $HTTP_FILES_PATH, $SUB_FOLDER, $systemTableID, $systemMessageID, $user_table_mode, $AUTH_USER_ID; #, $cc
    # если "пользователи" то вот так вот
    if (!($classID || $systemTableID) || !$field_name)
        return false;

    $classID = (int) $classID;
    $field_name = $db->escape($field_name);
    $fileInfo = array();
    $field_index = 0;

    # если системные таблицы, $message другой
    switch ($systemTableID) {
        case 3:
            # если "пользователи" то вот так вот
            $message = $UserID ? $UserID : $message;
            break;
        case 2:
        case 4:
            # если другие системные таблицы
            $message = $systemMessageID;
            break;
    }

    # если был подключен message_fields.php или объявлен $GLOBALS['fld']
    if (is_array($GLOBALS['fld']) && !$getData) {
        $fldID = $GLOBALS['fldID'];
        $fld = $GLOBALS['fld'];
        $fldName = $GLOBALS['fldName'];
        $fldValue = $GLOBALS['fldValue'];
        $fldType = $GLOBALS['fldType'];
        $fldFmt = $GLOBALS['fldFmt'];
        $fldNotNull = $GLOBALS['fldNotNull'];
        $fldInheritance = $GLOBALS['fldInheritance'];
        $fldDefault = $GLOBALS['fldDefault'];
        $fldTypeOfEdit = $GLOBALS['fldTypeOfEdit'];
        $fldDoSearch = $GLOBALS['fldDoSearch'];
        # дополнительные значения для удобства
        $tmp_array = array_flip($fld);
        $field_index = $tmp_array[$field_name];
        $field_id = $fldID[$field_index];
        # для файла прописываем нужное в один массив
        if (!$systemTableID) {
            $fileInfo = array("f_" . $field_name . "_old" => $GLOBALS["f_" . $field_name . "_old"], "f_" . $field_name => $GLOBALS["f_" . $field_name], "f_" . $field_name . "_url" => $GLOBALS["f_" . $field_name . "_url"], "f_" . $field_name . "_name" => $GLOBALS["f_" . $field_name . "_name"], "f_" . $field_name . "_size" => $GLOBALS["f_" . $field_name . "_size"], "f_" . $field_name . "_type" => $GLOBALS["f_" . $field_name . "_type"]);
        }
    } else {
        # если вызываем не из альтернативных форм нужно выбрать данные о поле
        $FieldRes = $db->get_row("SELECT `Field_ID`, `Field_Name`, `Description`, `TypeOfData_ID`, `Format`, `NotNull`, `Inheritance`, `DefaultState`, `TypeOfEdit_ID`, " . ($systemTableID ? "1" : "`DoSearch`") . "
                  FROM `Field`
                  WHERE " . ($systemTableID ? "`System_Table_ID` = " . $systemTableID : "`Class_ID` = " . $classID) . "
                  AND `Field_Name` = '" . $field_name . "'
                  LIMIT 1", ARRAY_N);
        if (!empty($FieldRes))
            list($fldID[0], $fld[0], $fldName[0], $fldType[0], $fldFmt[0], $fldNotNull[0], $fldInheritance[0], $fldDefault[0], $fldTypeOfEdit[0], $fldDoSearch[0]) = $FieldRes;
        $field_id = $fldID[$field_index = 0];
    }

    # если тип поля файл, действие "изменение" и сообщение не добавлено из-за ошибки в заполнении
    if ($fldType[$field_index] == 6 && ( ($action == "change" && !$posting) || $systemTableID)) {
        # запрос к файлам
        $fileinfo = $db->get_row("SELECT * FROM `Filetable`
      WHERE `Field_ID` = " . $fldID[$field_index] . " AND `Message_ID` = '" . $message . "'", ARRAY_A);
        # информация о файле
        if ($fileinfo) {
            $file_old = $GLOBALS["f_" . $field_name . "_old"] ? $GLOBALS["f_" . $field_name . "_old"] : $fldValue[$field_index];
            $file_field = $SUB_FOLDER . rtrim($HTTP_FILES_PATH, "/") . $fileinfo['File_Path'] . "h_" . $fileinfo['Virt_Name'];
            $file_url = $SUB_FOLDER . rtrim($HTTP_FILES_PATH, "/") . $fileinfo['File_Path'] . $fileinfo['Virt_Name'];
            $file_name = $fileinfo['Real_Name'];
            $file_size = $fileinfo['File_Size'];
            $file_type = $fileinfo['File_Type'];
        } else {
            # old-style storage
            $file_old = $GLOBALS["f_" . $field_name . "_old"] ? $GLOBALS["f_" . $field_name . "_old"] : $fldValue[$field_index];
            $file_data = explode(':', $file_old);
            $file_name = $file_data[0];
            $ext = substr($file_name, strrpos($file_name, "."));
            $file_type = $file_data[1];
            $file_size = $file_data[2];
            $file_field = $SUB_FOLDER . $HTTP_FILES_PATH;
            $file_field .= ( $file_data[3]) ? $file_data[3] : $fldID[$field_index] . "_" . $message . $ext;
        }
        # массив с данными файла
        $fileInfo = array("f_" . $fld[$field_index] . "_old" => $file_old, "f_" . $fld[$field_index] . "" => $file_field, "f_" . $fld[$field_index] . "_url" => $file_url, "f_" . $fld[$field_index] . "_name" => $file_name, "f_" . $fld[$field_index] . "_size" => $file_size, "f_" . $fld[$field_index] . "_type" => $file_type);
    }

    # ассоциативный массив
    $result = array("field_id" => $field_id, "field_index" => $field_index, "fldID" => $fldID, "fld" => $fld, "fldName" => $fldName, "fldValue" => $fldValue, "fileInfo" => $fileInfo, "fldType" => $fldType, "fldFmt" => $fldFmt, "fldNotNull" => $fldNotNull, "fldInheritance" => $fldInheritance, "fldDefault" => $fldDefault, "fldTypeOfEdit" => $fldTypeOfEdit, "fldDoSearch" => $fldDoSearch);

    return $result;
}

/**
 * Функция проверки прав текущего пользователя на администарирование,
 * используется для определения доступности поля
 * @return bool
 */
function nc_field_check_admin_perm() {
    global $perm, $cc, $systemTableID;
    $AdmRights = false;
    # проверим админские права текущего пользователя
    if (class_exists("Permission") && isset($perm)) {
        if ($cc)
            $AdmRights = $perm->isSubClassAdmin($cc);# администратор компонента $cc
        if ($systemTableID)
            $AdmRights = $perm->isAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_EDIT);
    }

    return $AdmRights;
}

/**
 * Функция поиска в строке атрибутов формата "attribut=..."
 * @param string строка
 * @return array массив названий атрибутов
 */
function nc_reg_search_html_attr($string) {

    # проверим, есть ли в параметре атрибуты формата "attribut=..."
    $string_attr = array();
    $preg_str = $string;
    while (preg_match("/^.*?([[:alpha:]]+(?=[ =]+)){1}(.*)?$/im", $preg_str, $matches)) {
        $preg_str = $matches[2];
        $string_attr[] = $matches[1];
        if (!$matches[1])
            break;
        unset($matches);
    }

    return $string_attr;
}

/**
 * Вывод поля типа "Список" в альтернативных формах шаблона
 * @param string имя списка
 * @param string имя поля
 * @param int выбранный элемент списка
 * @param int поле сортировки (не указан – ID, 1 – имя, 2 - приоритет)
 * @param int порядок сортировки (не указан – восходящий, 1 - нисходящий)
 * @param string темплейт префикса списка
 * @param string темплейт элемента списка
 * @param string темплейт суффикса списка
 * @param string темпелейт для первого нулевого элемента списка
 * @param bool выводить описание поля или нет
 * @return string
 */
function nc_list_select($classificator_name, $field_name = "", $current_value = false, $sort_type = false, $sort_direction = false, $template_prefix = "", $template_object = "", $template_suffix = "", $template_any = "", $caption = false, $ignore_check = false) {
    global $db, $classID;

    if ($field_name) {
        $fields_params = nc_get_field_params($field_name, $classID);
        if (!empty($fields_params))
            extract($fields_params);

        # смотрим тип редактирования поля
        switch ($fldTypeOfEdit[$field_index]) {
            # "Доступно только администраторам"
            case 2:
                $AdmRights = nc_field_check_admin_perm();
                if (!$AdmRights)
                    return false;
                break;
            # "Недоступно никому"
            case 3:
                return false;
                break;
        }

        // if( is_array($fld) && !in_array($field_name, $fld) ) {
        //  trigger_error("<b>nc_list_select()</b>: Incorrect field name (".$field_name.")", E_USER_WARNING);
        //   return false;
        //  }
    }

    $classificator_name = $db->escape($classificator_name);
    if ($sort_type !== false && $sort_direction !== false) {
        $SortType = $sort_type;
        $SortDirection = $sort_direction;
    } else {
        $res = $db->get_row("SELECT `Classificator_Name`, `Sort_Type`, `Sort_Direction` FROM `Classificator` WHERE Table_Name='" . $classificator_name . "'", ARRAY_A);
        if (!empty($res)) {
            $ClassificatorName = $res['Classificator_Name'];
            $SortType = $res['Sort_Type'];
            $SortDirection = $res['Sort_Direction'];
        }
    }

    # сортировка по полю...
    switch ($SortType) {
        case 1:
            $sort = "`" . $classificator_name . "_Name`";
            break;
        case 2:
            $sort = "`" . $classificator_name . "_Priority`";
            break;
        default:
            $sort = "`" . $classificator_name . "_ID`";
    }

    # выбор данных о списке, цикл ниже
    $res = $db->get_results("SELECT `" . $classificator_name . "_ID`, `" . $classificator_name . "_Name`, `" . $classificator_name . "_Priority`
               FROM `Classificator_" . $classificator_name . "`
               " . ($ignore_check ? "" : "WHERE `Checked` = '1' ") . "
               ORDER BY " . $sort . " " . ($SortDirection == 1 ? "DESC" : "ASC") . "", ARRAY_A);

    # если нет данных о списке - ошибка
    if (empty($res)) {
        trigger_error("<b>nc_list_select()</b>: Incorrect classificator name (" . $classificator_name . ")", E_USER_WARNING);
        return false;
    }

    # вывод Caption, если нужно
    if ($caption) {
        # описание поля из "Field"
        if ($field_name)
            $result = $fldName[$field_index] . ($fldNotNull[$field_index] ? " (*)" : "") . ":<br />\r\n";
        # описание поля из "Classificator"
        elseif ($ClassificatorName)
            $result = $ClassificatorName . ":<br />\r\n";
    }

    # темплейт префикса списка
    if ($template_prefix) {
        eval("\$result.= \"" . $template_prefix . "\";");
    } else {
        $result.= ( $field_name ? "<select name='f_" . $field_name . "'>\r\n" : "<select>\r\n");
    }

    if (!$fldNotNull[$field_index]) {
        if (!$template_any) {
            $result.= "<option value=\"\">" . NETCAT_MODERATION_LISTS_CHOOSE . "</option>\r\n";
        } else {
            eval("\$result.= \"" . $template_any . "\";");
        }
    }

    # это значение нужно когда неправильно заполнили поля или когда значение есть в базе
    if ($current_value === false && $fldValue[$field_index])
        $current_value = $fldValue[$field_index];

    # темплейт элемента списка
    if ($template_object) {
        foreach ($res AS $data) {
            # идентификатор записи OPTION
            $value_id = $data[$classificator_name . "_ID"];
            # выбранный элемент списка
            if ($current_value !== false)
                $value_selected = ($current_value == $data[$classificator_name . "_ID"] ? " selected='selected'" : "");
            # описание записи OPTION
            $value_name = $data[$classificator_name . "_Name"];
            eval("\$result.= \"" . $template_object . "\";");
        }
    }
    else {
        foreach ($res AS $row) {
            $selected = ( $current_value !== false && $current_value == $row[$classificator_name . "_ID"] ? " selected='selected' " : "");
            $result .= "<option value='" . $row[$classificator_name . "_ID"] . "'" . $selected . ">" . $row[$classificator_name . "_Name"] . "</option>\r\n";
        }
    }

    # темплейт суффикса списка
    if ($template_suffix) {
        $result.= eval("\$result.= \"" . $template_suffix . "\";");
        ;
    } else {
        $result.= "</select>";
    }

    return $result;
}

/*
  ".nc_list_field("author", "", 2, "\"; if(\$value_id==2) {\$result.= \" disabled\";}; \$result.=\"")."
 */

/**
 * Вывод поля типа "Список" в альтернативных формах шаблона
 * @param string имя поля
 * @param int идентификатор компонента, его стоит указывать при вызове функции т.к. в функции s_list_class() его глобальное значение будет иное
 * @param mixed выбранный(ые) элемент(ы) списка
 * @param mixed выключенный(ые) элемент(ы) списка
 * @param string дополнительные атрибуты
 * @param bool выводить описание поля или нет
 * @param bool неиспользуется
 * @param bool игнорировать выборку только включенных
 * @param string тип элемента: select или радиокнопки
 * @return string
 */
function nc_list_field($field_name, $style = "", $classID = "", $caption = false, $selected = false, $disabled = false, $unused = null, $ignore_check = false, $type = null) {
    // для получения значения поля
    global $db, $fldValue, $fldID, $systemTableID;

    $nc_core = nc_Core::get_object();
    // текущее значение компонента
    if (!$classID)
        $classID = $nc_core->sub_class->get_current('Class_ID');

    $show_field_errors = ( $classID == $nc_core->sub_class->get_current('Class_ID'));
    $sysTable = $systemTableID ? $systemTableID : $nc_core->component->get_by_id($classID, 'System_Table_ID');

    $component = new nc_Component($classID, $sysTable);
    $fields = $component->get_fields(NC_FIELDTYPE_SELECT);
    // поиск поля
    $field = 0;
    foreach ($fields as $v)
        if ($v['name'] == $field_name)
            $field = $v;
    // поля не существует
    if (!$field) {
        if ($show_field_errors) {
            trigger_error("<b>nc_list_field()</b>: Incorrect field name (" . $field_name . ")", E_USER_WARNING);
        }
        return false;
    }

    // поле не доступно для редактирования
    if ($field['edit_type'] == 3 || ($field['edit_type'] == 2 && !nc_field_check_admin_perm())) {
        return false;
    }

    // значение поля
    if (is_array($fldID)) {
        $t = array_flip($fldID);
        $value = $fldValue[$t[$field['id']]];
    }
    $field_id = $field['id'];

    # если поле обязательно для заполнения
    if ($value == NULL && $field['default'] != NULL)
        $value = $field['default'];

    $format = explode(':', $field['format']);
    $clft_name = $db->escape($format[0]);
    if ($selected !== false)
        $selected = (array) $selected;
    if ($disabled !== false)
        $disabled = (array) $disabled;

    if (!$type && $format[1])
        $type = $format[1];
    if (!$type || !in_array($type, array('select', 'radio')))
        $type = 'select';


    $res = $db->get_row("SELECT * FROM `Classificator` WHERE Table_Name='" . $clft_name . "'", ARRAY_A);
    if (!empty($res)) {
        $ClassificatorName = $res['Classificator_Name'];
        $SortType = $res['Sort_Type'];
        $SortDirection = $res['Sort_Direction'];
    } else {
        if ($show_field_errors) {
            trigger_error("<b>nc_list_field()</b>: Classificator (" . $clft_name . ") not exist!", E_USER_WARNING);
        }
        return false;
    }

    # сортировка по полю...
    switch ($SortType) {
        case 1:
            $sort = "`" . $clft_name . "_Name`";
            break;
        case 2:
            $sort = "`" . $clft_name . "_Priority`";
            break;
        default:
            $sort = "`" . $clft_name . "_ID`";
    }

    # выбор данных о списке, цикл ниже
    $res = $db->get_results("SELECT `" . $clft_name . "_ID`, `" . $clft_name . "_Name`, `" . $clft_name . "_Priority`
               FROM `Classificator_" . $clft_name . "`
               " . ($ignore_check ? "" : "WHERE `Checked` = '1' ") . "
               ORDER BY " . $sort . " " . ($SortDirection == 1 ? "DESC" : "ASC") . "", ARRAY_A);

    # если нет данных о списке - ошибка
    if (empty($res)) {
        if ($show_field_errors) {
            trigger_error("<b>nc_list_field()</b>: Classificator without fields (" . $clft_name . ")", E_USER_WARNING);
        }
        return false;
    }

    // вывод функции
    $result = '';
    # вывод Caption, если нужно
    if ($caption)
        $result .= "<span style='$caption_style' id='nc_capfld_".$field_id."'>".$field['description'] . ( $field['not_null'] ? " (*)" : "") . ":</span>\r\n";

    # префикс списка
    if ($type == 'select')
        $result.= "<select name='f_" . $field_name . "'" . ($style ? " " . $style : "") . ">\r\n";

    # шапка полей
    if (!$field['not_null'] && $type == 'select') {
        $select0 = ( $value == "0" ? " selected" : "");
        $result.= "<option value='0' id='f" . $field_id . "0'" . $select0 . ">" . NETCAT_MODERATION_LISTS_CHOOSE . "</option>\r\n";
    }

    # вывод полей списка
    foreach ($res AS $row) {
        # для удобства
        $value_id = $row[$clft_name . "_ID"];
        $value_name = $row[$clft_name . "_Name"];

        # выбранные значения
        $selected_str = "";
        if ($value != "0") {
            $s = (($type == 'select') ? 'selected' : 'checked');
            if ($value) {
                $selected_str = ($value == $value_id ? " " . $s . "='" . $s . "' " : "");
            } elseif ($selected !== false && !empty($selected)) {
                $selected_str = ( in_array($value_id, $selected) ? " " . $s . "='" . $s . "' " : "");
            } elseif ($value == NULL && $field['default']) {
                $selected_str = ( $field['default'] == $value_id ? " " . $s . "='" . $s . "' " : "");
            }
        }

        # отключенные значения
        $disabled_str = "";
        if ($disabled !== false && !empty($disabled)) {
            $disabled_str = (in_array($value_id, $disabled) ? " disabled='disabled' " : "");
        }

        if ($type == 'select') {
            $result.= "<option value='" . $value_id . "' id='f" . $field_id . $value_id . "'" . $selected_str . $disabled_str . ">" . $value_name . "</option>\r\n";
        } else {
            $result.= "<input type='radio' name='f_" . $field_name . "'" . ($style ? " " . $style : "") . " value='" . $value_id . "' id='f" . $field_id . $value_id . "'" . $selected_str . $disabled_str . " />
                 <label for='f" . $field_id . $value_id . "'>" . $value_name . "</label><br/>\r\n";
        }
    }

    #  суффикс списка
    if ($type == 'select') {
        $result.= "</select>";
    }


    return $result;
}

/**
 * Вывод поля типа "Множественный список" в альтернативных формах шаблона
 * @param string имя списка
 * @param string имя поля
 * @param type формат поля
 * @param string выбранные элемент списка
 * @param int поле сортировки (не указан – ID, 1 – имя, 2 - приоритет)
 * @param int порядок сортировки (не указан – восходящий, 1 - нисходящий)
 * @param string темплейт префикса списка
 * @param string темплейт элемента списка
 * @param string темплейт суффикса списка
 * @param string темпелейт для первого нулевого элемента списка
 * @param bool выводить описание поля или нет
 * @param bool игнорировать выборку только включенных
 * @return string
 */
function nc_multilist_select($classificator_name, $field_name = "", $type = "", $current_value = false, $sort_type = false, $sort_direction = false, $template_prefix = "", $template_object = "", $template_suffix = "", $template_any = "", $caption = false, $ignore_check = false) {
    global $db, $classID;

    if ($field_name) {
        $fields_params = nc_get_field_params($field_name, $classID, 0);
        if (!empty($fields_params))
            extract($fields_params);

        list( $clft_name, $type_element, $type_size ) = explode(":", $fldFmt[$field_index]);

        # смотрим тип редактирования поля
        switch ($fldTypeOfEdit[$field_index]) {
            # "Доступно только администраторам"
            case 2:
                $AdmRights = nc_field_check_admin_perm();
                if (!$AdmRights)
                    return false;
                break;
            # "Недоступно никому"
            case 3:
                return false;
                break;
        }

        //if( is_array($fld) && !in_array($field_name, $fld) ) {
        //  trigger_error("<b>nc_multilist_select()</b>: Incorrect field name (".$field_name.")", E_USER_WARNING);
        //  return false;
        //}
    }

    if ($type) {
        list( $type_element, $type_size ) = explode(":", $type);
    } else {
        if (!$type_element)
            $type_element = "select";
    }
    if (!$type_size)
        $type_size = 3;


    $res = $db->get_row("SELECT `Classificator_Name`, `Sort_Type`, `Sort_Direction`
                       FROM `Classificator`
                       WHERE Table_Name='" . $db->escape($classificator_name) . "'", ARRAY_A);
    $ClassificatorName = $res['Classificator_Name'];

    if ($sort_type !== false && $sort_direction !== false) {
        $SortType = $sort_type;
        $SortDirection = $sort_direction;
    } else {
        if (!empty($res)) {
            $SortType = $res['Sort_Type'];
            $SortDirection = $res['Sort_Direction'];
        }
    }

    # сортировка по полю...
    switch ($SortType) {
        case 1:
            $sort = "`" . $classificator_name . "_Name`";
            break;
        case 2:
            $sort = "`" . $classificator_name . "_Priority`";
            break;
        default:
            $sort = "`" . $classificator_name . "_ID`";
    }

    # выбор данных о списке, цикл ниже
    $res = $db->get_results("SELECT `" . $classificator_name . "_ID`, `" . $classificator_name . "_Name`, `" . $classificator_name . "_Priority`
               FROM `Classificator_" . $classificator_name . "`
               " . ($ignore_check ? "" : "WHERE `Checked` = '1' ") . "
               ORDER BY " . $sort . " " . ($SortDirection == 1 ? "DESC" : "ASC") . "", ARRAY_A);

    # если нет данных о списке - ошибка
    if (empty($res)) {
        trigger_error("<b>nc_multilist_select()</b>: Incorrect classificator name (" . $classificator_name . ")", E_USER_WARNING);
        return false;
    }

    # вывод Caption, если нужно
    if ($caption) {
        # описание поля из "Field"
        if ($field_name)
            $result = $fldName[$field_index] . ($fldNotNull[$field_index] ? " (*)" : "") . ":<br />\r\n";
        # описание поля из "Classificator"
        elseif ($ClassificatorName)
            $result = $ClassificatorName . ":<br />\r\n";
    }

    # темплейт префикса списка
    if ($template_prefix) {
        eval("\$result.= \"" . $template_prefix . "\";");
    } else if ($type_element == 'select') { // тип элемента - select
        $result.= ( $field_name ? "<select size='" . $type_size . "' name='f_" . $field_name . "[]' multiple='multiple'>\r\n" : "<select  size='" . $type_size . "' multiple='multiple'>\r\n");
    }


    # элемент "ничего не выбранно"
    if (!$fldNotNull[$field_index] && $type_element == 'select') {
        if ($template_any) {
            eval("\$result.= \"" . $template_any . "\";");
        }
    }

    if ($current_value !== false) {
        if (!is_array($current_value)) {
            if ($current_value) {
                $current_value = explode(',', $current_value);
            }
        } else {
            $current_value = array($current_value);
        }
    }

    # это значение нужно когда неправильно заполнили поля или когда значение есть в базе
    if ($current_value === false && $fldValue[$field_index]) {
        if (is_array($fldValue[$field_index])) {
            $current_value = $fldValue[$field_index];
        } else {
            $temp = explode(',', $fldValue[$field_index]);
            if (!empty($temp))
                $current_value = $temp;
        }
    }

    if (!is_array($current_value) || empty($current_value))
        $current_value = array();

    # темплейт элемента списка
    if ($template_object) {
        foreach ($res AS $data) {
            # идентификатор записи OPTION
            $value_id = $data[$classificator_name . "_ID"];
            # выбранный элемент списка
            if ($current_value !== false) {
                $value_selected = ( in_array($data[$classificator_name . "_ID"], $current_value) ? ($type_element == 'select' ? " selected='selected'" : " checked='checked'") : '');
            }
            # описание записи OPTION
            $value_name = $data[$classificator_name . "_Name"];
            eval("\$result.= \"" . $template_object . "\";");
        }
    } else {
        foreach ($res AS $row) {
            $id = $row[$classificator_name . "_ID"];
            $selected = ( in_array($row[$classificator_name . "_ID"], $current_value) ? ( $type_element == 'select' ? " selected='selected'" : " checked='checked'") : "");
            if ($type_element == 'select') { //тип элемента select
                $result .= "<option value='" . $id . "'" . $selected . ">" . $row[$classificator_name . "_Name"] . "</option>\r\n";
            } else { // тип элемента checkbox
                $result .= "<input type='checkbox' value='" . $id . "'" . $selected . " 'name='f_" . $field_name . "[" . $id . "]' />" . $row[$classificator_name . "_Name"] . "<br />\r\n";
            }
        }
    }

    # темплейт суффикса списка
    if ($template_suffix) {
        $result.= eval("\$result.= \"" . $template_suffix . "\";");
        ;
    } else if ($type_element == 'select') { // тип элемента - select
        $result.= "</select>";
    }

    return $result;
}

/**
 * Вывод поля типа "Множественный выбор" в альтернативных формах шаблона
 * @param string имя поля
 * @param  дополнительные атрибуты
 * @param тип элемента (select or checkbox)
 * @param int идентификатор компонента, его стоит указывать при вызове функции т.к. в функции s_list_class() его глобальное значение будет иное
 * @param bool выводить описание поля или нет
 * @param mixed выбранный(ые) элемент(ы) списка
 * @param mixed выключенный(ые) элемент(ы) списка
 * @param bool принудительно вытащить из базы
 * @param bool игнорировать выборку только включенных
 * @return string
 */
function nc_multilist_field($field_name, $style = "", $type = "", $classID = "", $caption = false, $selected = false, $disabled = false, $getData = false, $ignore_check = false) {
    // для получения значения поля
    global $db, $fldValue, $fldID, $systemTableID;

    $nc_core = nc_Core::get_object();
    // текущее значение компонента
    if (!$classID)
        $classID = $nc_core->sub_class->get_current('Class_ID');

    $show_field_errors = ( $classID == $nc_core->sub_class->get_current('Class_ID'));
    $sysTable = $systemTableID ? $systemTableID : $nc_core->component->get_by_id($classID, 'System_Table_ID');

    $component = new nc_Component($classID, $sysTable);
    $fields = $component->get_fields(NC_FIELDTYPE_MULTISELECT);
    // поиск поля
    $field = 0;
    foreach ($fields as $v)
        if ($v['name'] == $field_name)
            $field = $v;
    // поля не существует
    if (!$field) {
        if ($show_field_errors) {
            trigger_error("<b>nc_multilist_field()</b>: Incorrect field name (" . $field_name . ")", E_USER_WARNING);
        }
        return false;
    }

    // поле не доступно для редактирования
    if ($field['edit_type'] == 3 || ($field['edit_type'] == 2 && !nc_field_check_admin_perm())) {
        return false;
    }

    // значение поля
    if (is_array($fldID)) {
        $t = array_flip($fldID);
        $value = $fldValue[$t[$field['id']]];
    }
    $field_id = $field['id'];

    list( $clft_name, $type_element, $type_size ) = explode(":", $field['format']);  //Сначала берем из формата
    if ($type) {// Если передано через параметр, то перезаписываем
        list( $type_element, $type_size ) = explode(":", $type);
    } else {
        if (!$type_element)
            $type_element = "select";
    }
    if (!$type_size)
        $type_size = 3;

    $clft_name = $db->escape($clft_name);


    $res = $db->get_row("SELECT * FROM `Classificator` WHERE Table_Name='" . $clft_name . "'", ARRAY_A);
    if (!empty($res)) {
        $ClassificatorName = $res['Classificator_Name'];
        $SortType = $res['Sort_Type'];
        $SortDirection = $res['Sort_Direction'];
    } else {
        if ($show_field_errors) {
            trigger_error("<b>nc_multilist_field()</b>: Classificator (" . $clft_name . ") not exist!", E_USER_WARNING);
        }
        return false;
    }

    # сортировка по полю...
    switch ($SortType) {
        case 1:
            $sort = "`" . $clft_name . "_Name`";
            break;
        case 2:
            $sort = "`" . $clft_name . "_Priority`";
            break;
        default:
            $sort = "`" . $clft_name . "_ID`";
    }

    # выбор данных о списке, цикл ниже
    $res = $db->get_results("SELECT `" . $clft_name . "_ID`, `" . $clft_name . "_Name`, `" . $clft_name . "_Priority`
               FROM `Classificator_" . $clft_name . "`
               " . ($ignore_check ? "" : "WHERE `Checked` = '1' ") . "
               ORDER BY " . $sort . " " . ($SortDirection == 1 ? "DESC" : "ASC") . "", ARRAY_A);

    # если нет данных о списке - ошибка
    if (empty($res)) {
        if ($show_field_errors) {
            trigger_error("<b>nc_multilist_field()</b>: Classificator without fields (" . $clft_name . ")", E_USER_WARNING);
        }
        return false;
    }


    # вывод Caption, если нужно
    if ($caption)
        $result .= $field['description'] . ( $field['not_null'] ? " (*)" : "") . ":<br />\r\n";

    # префикс списка
    $result.= ( $type_element == "select" ) ? ("<select name='f_" . $field_name . "[]'" . ($style ? " " . $style : "") . " multiple='multiple' size='" . $type_size . "'>\r\n" ) : "";

    #Oпределение массивов с выбранными и недоступными элементами
    $selected = str_replace(array(",", ".", " "), ";", $selected . ";" . join(';', (array) $value));
    $selectedArray = explode(";", $selected);
    $disabled = str_replace(array(",", ".", " "), ";", $disabled);
    $disabledArray = explode(";", $disabled);

    # вывод полей списка
    foreach ($res AS $row) {
        # для удобства
        $value_id = $row[$clft_name . "_ID"];
        $value_name = $row[$clft_name . "_Name"];

        $temp_str = "";
        if (in_array($value_id, $selectedArray))
            $temp_str .= ( $type_element == "select" ) ? " selected='selected' " : " checked='checked' ";
        if (in_array($value_id, $disabledArray))
            $temp_str .= " disabled";

        $result.= ( $type_element == "select" ) ? "<option value='" . $value_id . "' id='f" . $field_id . $value_id . "'" . $temp_str . ">" . $value_name . "</option>\r\n" :
                "<input " . ($style ? " " . $style : "") . " type='checkbox' value='" . $value_id . "' id='f_" . $field_name . "[" . $value_id . "]' name='f_" . $field_name . "[" . $value_id . "]' " . $temp_str . " /> \r\n" .
                "<label for='f_" . $field_name . "[" . $value_id . "]' /> " . $value_name . "</label>\r\n<br />\r\n";
    }

    #  суффикс списка
    $result.= ( $type_element == "select" ) ? "</select>" : "";

    return $result;
}

/**
 * Вывод поля типа Файл в альтернативных формах шаблона
 * @param string имя поля
 * @param string дополнительные свойства для <input type=file>
 * @param int идентификатор компонента, его стоит указывать при вызове функции т.к. в функции s_list_class() его глобальное значение будет иное
 * @param bool выводить описание поля или нет
 * @return string
 */
function nc_file_field($field_name, $style = "", $classID = "", $caption = false, $getData = false) {
    // для получения значения поля
    global $fldValue, $fldID;
    global $db, $action, $current_cc, $message, $DOMAIN_NAME, $user_table_mode, $systemTableID, $systemMessageID, $UserID;

    # если системные таблицы, $message другой
    switch ($systemTableID) {
        case 3:
            # если "пользователи" то вот так вот
            $message = $UserID ? $UserID : $message;
            break;
        case 2:
        case 4:
            # если другие системные таблицы
            $message = $systemMessageID;
            break;
    }

    $nc_core = nc_Core::get_object();
    // текущее значение компонента
    if (!$classID)
        $classID = $nc_core->sub_class->get_current('Class_ID');

    $show_field_errors = ( $classID == $nc_core->sub_class->get_current('Class_ID'));
    $sysTable = $systemTableID ? $systemTableID : $nc_core->component->get_by_id($classID, 'System_Table_ID');

    $component = new nc_Component($classID, $sysTable);
    $fields = $component->get_fields(NC_FIELDTYPE_FILE);
    // поиск поля
    $field = 0;
    foreach ($fields as $v)
        if ($v['name'] == $field_name)
            $field = $v;
    // поля не существует
    if (!$field) {
        if ($show_field_errors) {
            trigger_error("<b>nc_file_field()</b>: Incorrect field name (" . $field_name . ")", E_USER_WARNING);
        }
        return false;
    }

    // поле не доступно для редактирования
    if ($field['edit_type'] == 3 || ($field['edit_type'] == 2 && !nc_field_check_admin_perm())) {
        return false;
    }

    // вывод функции
    $result = '';
    if (is_array($fldID)) {
        $t = array_flip($fldID);
        $value = $fldValue[$t[$field['id']]];
    }
    $field_id = $field['id'];
    # вывод Caption, если нужно
    if ($caption)
        $result .= $field['description'] . ( $field['not_null'] ? " (*)" : "") . ":\r\n";
        //$result .= $field['description'] . ( $field['not_null'] ? " (*)" : "") . ":<br />\r\n";

    # проверим, есть ли в параметре "style", атрибуты
    $style_attr = nc_reg_search_html_attr($style);

    # прописываем параметры из $style
    $style_opt = "";
    if (!in_array("size", $style_attr))
        $style_opt.= "size='50'";
    if ($style_opt)
        $style_opt = " " . $style_opt;

    # само поле
    $result.= "<input name='f_" . $field_name . "'" . $style_opt . ($style ? " " . $style : "") . " type='file' />\r\n";

    # старое значение
    if (($systemTableID && $value) || ($action == 'change' && ($old = $GLOBALS["f_" . $field_name . "_old"] ))) {
        $system_tables = array(1 => "Catalogue", 2 => "Subdivision", 3 => "User", 4 => "Template");
        $filepath = nc_file_path($systemTableID ? $system_tables[$systemTableID] : $classID, $message, $field_id, 'h_');
        list ( $filename, $filetype, $filesize) = explode(':', $old ? $old : $value);

        $result.= "<input type='hidden' name='f_" . $field_name . "_old' value='" . ($old ? $old : $value) . "' /><br/>\r\n";
        $result.= NETCAT_MODERATION_FILES_UPLOADED . ": ";
        $result.= "<a target='_blank' href='http://" . $DOMAIN_NAME . $filepath . "'>" . htmlspecialchars_decode($filename) . "</a> (" . nc_bytes2size($filesize) . ")";
        # "удалить файл", если поле не обязательно для заполнения
        if (!$field['not_null'])
            $result.=" <input id='k" . $field_id . "' type='checkbox' name='f_KILL" . $field_id . "' value='1' /> <label for='k" . $field_id . "'>" . NETCAT_MODERATION_FILES_DELETE . "</label>\r\n";
    }

    return $result;
}

/**
 * Вывод поля типа "Логическая переменная" в альтернативных формах шаблона
 * @param string имя поля
 * @param array дополнительные свойства для <input ...>
 * @param int идентификатор компонента, его стоит указывать при вызове функции т.к. в функции s_list_class() его глобальное значение будет иное
 * @param bool выводить описание поля или нет
 * @return string
 */
function nc_bool_field($field_name, $style = "", $classID = "", $caption = false, $value = false) {
    // для получения значения поля
    global $fldValue, $fldID, $systemTableID;

    $nc_core = nc_Core::get_object();
    // текущее значение компонента
    if (!$classID)
        $classID = $nc_core->sub_class->get_current('Class_ID');

    $show_field_errors = ( $classID == $nc_core->sub_class->get_current('Class_ID'));
    $sysTable = $systemTableID ? $systemTableID : $nc_core->component->get_by_id($classID, 'System_Table_ID');

    $component = new nc_Component($classID, $sysTable);
    $fields = $component->get_fields(NC_FIELDTYPE_BOOLEAN);
    // поиск поля
    $field = 0;
    foreach ($fields as $v)
        if ($v['name'] == $field_name)
            $field = $v;
    // поля не существует
    if (!$field) {
        if ($show_field_errors) {
            trigger_error("<b>nc_bool_field()</b>: Incorrect field name (" . $field_name . ")", E_USER_WARNING);
        }
        return false;
    }

    // поле не доступно для редактирования
    if ($field['edit_type'] == 3 || ($field['edit_type'] == 2 && !nc_field_check_admin_perm())) {
        return false;
    }

    // значение поля
    if (!$value && is_array($fldID)) {
        $t = array_flip($fldID);
        $value = $fldValue[$t[$field['id']]];
    }
    $field_id = $field['id'];

    // вывод функции
    $result = '';
    # вывод Caption, если нужно
    if ($caption && !$field['not_null'])
        $result .= $field['description'] . ":<br />\r\n";

    # если пришла одна строка, то делаем её массивом
    if (!is_array($style)) {
        $style = array("checkbox" => $style, "radio1" => $style, "radio2" => $style, "radio3" => $style);
    }

    $checked = $checked1 = $checked2 = $checked3 = "";
    #-- CHECKBOX --#
    # если поле помечено обязательным для заполнения, типа "checkbox"
    if ($field['not_null']) {
        // помечаем, как checked
        if ($value || ($value == NULL && $field['default'] && $field['default'] != NULL ))
            $checked = " checked='checked'";
        # код
        $result.= "<input id='f" . $field_id . "' type='checkbox' name='f_" . $field_name . "' value='1'" . $checked . ($style['checkbox'] ? " " . $style['checkbox'] : "") . " />" . ($caption ? " <label for='f" . $field_id . "'>" . $field['description'] . "</label>" : "");
    }
    else {
        #-- RADIO --#
        # если логическая переменная с 3 значениями
        if (!is_null($value) && $value != "NULL") {
            # при редактировании выбираем значение из базы
            if ($value)
                $checked2 = " checked='checked'";
            elseif ($value == 0)
                $checked3 = " checked='checked'";
        }
        else {
            # при добавлении смотрим на умолчания
            if ($field['default'] == "")
                $checked1 = " checked='checked'";
            elseif ($field['default'])
                $checked2 = " checked='checked'";
            elseif ($field['default'] == 0)
                $checked3 = " checked='checked'";
        }

        # код
        $result.= "<input id='f" . $field_id . "1' type='radio' name='f_" . $field_name . "' value='NULL'" . $checked1 . ($style['radio1'] ? " " . $style['radio1'] : "") . " /> <label for='f" . $field_id . "1'>" . NETCAT_MODERATION_RADIO_EMPTY . "</label>";
        $result.= "<input id='f" . $field_id . "2' type='radio' name='f_" . $field_name . "' value='1'" . $checked2 . ($style['radio2'] ? " " . $style['radio2'] : "") . " /> <label for='f" . $field_id . "2'>" . CONTROL_CONTENT_SUBDIVISION_FUNCS_OBJ_YES . "</label> ";
        $result.= "<input id='f" . $field_id . "3' type='radio' name='f_" . $field_name . "' value='0'" . $checked3 . ($style['radio3'] ? " " . $style['radio3'] : "") . " /> <label for='f" . $field_id . "3'>" . CONTROL_CONTENT_SUBDIVISION_FUNCS_OBJ_NO . "</label>";
    }

    return $result;
}

/**
 * Вывод поля типа "Дата и время" в альтернативных формах шаблона
 * @param string имя поля
 * @param array дополнительные свойства для <input ...>. array("", "", "", "", "", "")
 * @param int идентификатор компонента, его стоит указывать при вызове функции т.к. в функции s_list_class() его глобальное значение будет иное
 * @param bool выводить описание поля или нет
 * @param string разделитель для даты
 * @param string разделитель для времени
 * @param bool вывести месяц выпадающим списком
 * @param bool использовать календарь
 * @param int шаблон вывода календаря
 * @param string альтернативный шаблон вывода кнопки "Показать календарь"
 * @return string
 */
function nc_date_field($field_name, $style = "", $classID = "", $caption = false, $dateDiv = "-", $timeDiv = ":", $select = false, $use_calendar = null, $calendar_theme = 0, $calendar_template = "") {
    // для получения значения поля
    global $fldValue, $fldID, $systemTableID;

    $nc_core = nc_Core::get_object();

    // текущее значение компонента
    if (!$classID)
        $classID = $nc_core->sub_class->get_current('Class_ID');

    $show_field_errors = ( $classID == $nc_core->sub_class->get_current('Class_ID'));
    $sysTable = $systemTableID ? $systemTableID : $nc_core->component->get_by_id($classID, 'System_Table_ID');
    $component = new nc_Component($classID, $sysTable);
    $fields = $component->get_fields(NC_FIELDTYPE_DATETIME);
    // поиск поля
    $field = 0;
    foreach ($fields as $v)
        if ($v['name'] == $field_name)
            $field = $v;
    // поля не существует
    if (!$field) {
        if ($show_field_errors) {
            trigger_error("<b>nc_date_field()</b>: Incorrect field name (" . $field_name . ")", E_USER_WARNING);
        }
        return false;
    }

    // поле не доступно для редактирования
    if ($field['edit_type'] == 3 || ($field['edit_type'] == 2 && !nc_field_check_admin_perm())) {
        return false;
    }

    // значение поля
    if (is_array($fldID)) {
        $t = array_flip($fldID);
        $value = $fldValue[$t[$field['id']]];
    }
    $format = nc_field_parse_format($field['format'], 8);
    if ($use_calendar === null)
        $use_calendar = $format['calendar'];

    # нужен нумерованный массив с ключами от 0
    if (!empty($style) && is_array($style))
        $style = array_values($style);
    # если массив с 3 элементами - удвоить массив
    if (( $style_size = sizeof($style) ) == 3) {
        array_push($style, $style[0], $style[1], $style[2]);
        $style_size = 6;
    }

    # параметры полей
    if (empty($style) || (is_array($style) && $style_size != 6)) {
        $style = array("maxlength='2' size='2'", "maxlength='2' size='2'", "maxlength='4' size='4'", "maxlength='2' size='2'", "maxlength='2' size='2'", "maxlength='2' size='2'");
        if ($select)
            $style[1] = "";
    }
    else {
        # если пришла одна строка, то делаем её массивом из 6 элементов
        if (!is_array($style)) {
            $style_arr = (array) $style;
            $style = array_pad($style_arr, 6, $style);
        }

        # проверим, есть ли в параметре "style", атрибуты
        $i = 0;
        foreach ($style AS $val) {
            $style_attr[$i] = nc_reg_search_html_attr($val);
            $i++;
        }

        $date_attr = array(array(2, 2), array(2, 2), array(4, 4), array(2, 2), array(2, 2), array(2, 2));
        # прописываем параметры из $style
        $i = 0;
        $style_opt_arr = array();
        foreach ($style AS $val) {
            $style_opt = "";
            if ($i == 1 && $select == false) {
                if (!in_array("maxlength", $style_attr[$i]))
                    $style_opt.= "maxlength='" . $date_attr[$i][0] . "'";
                if (!in_array("size", $style_attr[$i]))
                    $style_opt.= ( $style_opt ? " " : "") . "size='" . $date_attr[$i][1] . "'";
            }
            if ($style_opt)
                $style_opt_arr[] = " " . $style_opt;
            $i++;
        }
    }

    $result = '';
    # вывод Caption, если нужно
    if ($caption) {
        $result .= nc_field_caption($field);
    }

    # если поле помечено "event..."
    //$fldNotNull[$field_index]
    if ($action != "change" && $field['not_null'] && ($format['type'] == "event" || $format['type'] == "event_date" || $format['type'] == "event_time") && !$value)
        $value = date("Y-m-d H:i:s");

    if ($value) {
        $year = substr($value, 0, 4);
        $month = substr($value, 5, 2);
        $day = substr($value, 8, 2);
        $hours = substr($value, 11, 2);
        $minutes = substr($value, 14, 2);
        $seconds = substr($value, 17, 2);
    }

    if ($format['type'] == "event_date") {
        $timeType = "hidden";
        $timeDiv = "";
    } else {
        $timeType = "text";
    }

    if ($format['type'] == "event_time") {
        $dateType = "hidden";
        $dateDiv = "";
        $use_calendar = false;
    } else {
        $dateType = "text";
    }

    if ($select && defined("NETCAT_MODULE_CALENDAR_MONTH_NAME_ARRAY")) {
        eval("\$monthArray = " . NETCAT_MODULE_CALENDAR_MONTH_NAME_ARRAY . ";");
        if (!$field['not_null'])
            $monthArray = array_pad($monthArray, 13, "");
        if (is_array($monthArray) && !empty($monthArray)) {
            $selectMonth.= "<select name='f_" . $field_name . "_month'" . $style_opt_arr[1] . ($style[1] ? " " . $style[1] : "") . ">";
            foreach ($monthArray AS $key => $value) {
                $selectMonth.= "<option value='" . ( ($key + 1) <= 12 ? sprintf("%02d", $key + 1) : "" ) . "'" . ($month ? ($month == ($key + 1) ? " selected='selected' " : "") : ($field['not_null'] ? ($key == 0 ? " selected='selected'" : "") : ($key == 12 ? " selected='selected'" : "")) ) . ">" . $value . "</option>";
            }
            $selectMonth.= "</select>";
        }
    } else {
        $selectMonth.= "<input type='" . $dateType . "' name='f_" . $field_name . "_month'" . $style_opt_arr[1] . ($style[1] ? " " . $style[1] : "") . " value='" . ((int) $month ? sprintf("%02d", (int) $month) : "") . "' />";
    }

    $result.= "<input type='" . $dateType . "' name='f_" . $field_name . "_day'" . $style_opt_arr[0] . ($style[0] ? " " . $style[0] : "") . " value='" . ((int) $day ? sprintf("%02d", (int) $day) : "") . "' />" . $dateDiv . $selectMonth . $dateDiv . "<input type='" . $dateType . "' name='f_" . $field_name . "_year'" . $style_opt_arr[2] . ($style[2] ? " " . $style[2] : "") . " value='" . ((int) $year ? sprintf("%04d", (int) $year) : "") . "' /> \r\n
             <input type='" . $timeType . "' name='f_" . $field_name . "_hours'" . $style_opt_arr[3] . ($style[3] ? " " . $style[3] : "") . " value='" . ($hours ? sprintf("%02d", (int) $hours) : "") . "' />" . $timeDiv . "<input type='" . $timeType . "' name='f_" . $field_name . "_minutes'" . $style_opt_arr[4] . ($style[4] ? " " . $style[4] : "") . " value='" . ($minutes ? sprintf("%02d", (int) $minutes) : "") . "' />" . $timeDiv . "<input type='" . $timeType . "' name='f_" . $field_name . "_seconds'" . $style_opt_arr[5] . ($style[5] ? " " . $style[5] : "") . " value='" . ($seconds ? sprintf("%02d", (int) $seconds) : "") . "' />";

    if ($use_calendar) {
        echo nc_set_calendar($calendar_theme);
        if ($calendar_template) {
            eval("\$result.= \"" . $calendar_template . "\";");
        } else {
            $result .= "<div style='display: inline; position: relative;'>
                    <img  id='nc_calendar_popup_img_f_" . $field_name . "_day' onclick='nc_calendar_popup(\"f_" . $field_name . "_day\",\"f_" . $field_name . "_month\", \"f_" . $field_name . "_year\", \"" . $calendar_theme . "\");' src='" . $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . "modules/calendar/images/calendar.jpg' style='cursor: pointer; position: absolute; left: 7px; top: -3px;'/>
                  </div>
                 <div style='display: none; z-index: 10000;' id='nc_calendar_popup_f_" . $field_name . "_day'></div>";
        }
    }

    //$result .= nc_field_validation('input', "f_".$field_name, $field['id'], 'date', $field['not_null']);

    return $result;
}

/**
 * Вывод поля типа "Текстовый блок" в альтернативных формах шаблона
 * @param string имя поля
 * @param string дополнительные свойства для <input ...>
 * @param int идентификатор компонента, его стоит указывать при вызове функции т.к. в функции s_list_class() его глобальное значение будет иное
 * @param bool выводить описание поля или нет
 * @param bool выводить панельку с BB-кодами (для панельки нужны IDшники формы и поля, а также стили CSS!)
 * @param string значение по умолчанию
 * @return string
 */
function nc_text_field($field_name, $style = "", $classID = "", $caption = false, $bbcode = false, $value = '') {
    // для получения значения поля
    global $fldValue, $fldID, $systemTableID;
    global $SUB_FOLDER, $HTTP_ROOT_PATH, $ROOT_FOLDER;

    $nc_core = nc_Core::get_object();

    $system_env = $nc_core->get_settings();
    $allowTags = $nc_core->sub_class->get_current('AllowTags');

    // текущее значение компонента
    if (!$classID)
        $classID = $nc_core->sub_class->get_current('Class_ID');

    $show_field_errors = ( $classID == $nc_core->sub_class->get_current('Class_ID'));
    $sysTable = $systemTableID ? $systemTableID : $nc_core->component->get_by_id($classID, 'System_Table_ID');

    $component = new nc_Component($classID, $sysTable);
    $fields = $component->get_fields(NC_FIELDTYPE_TEXT);
    // поиск поля
    $field = 0;
    foreach ($fields as $v)
        if ($v['name'] == $field_name)
            $field = $v;
    // поля не существует
    if (!$field) {
        if ($show_field_errors) {
            trigger_error("<b>nc_text_field()</b>: Incorrect field name (" . $field_name . ")", E_USER_WARNING);
        }
        return false;
    }

    // поле не доступно для редактирования
    if ($field['edit_type'] == 3 || ($field['edit_type'] == 2 && !nc_field_check_admin_perm())) {
        return false;
    }

    // значение поля
    if (!$value && is_array($fldID)) {
        $t = array_flip($fldID);
        $value = $fldValue[$t[$field['id']]];
    }

    # формат поля
    $format = nc_field_parse_format($field['format'], 3);
    $rows = $format['rows'];
    $cols = $format['cols'];

    # проверим, есть ли в параметре "style", атрибуты
    $style_attr = nc_reg_search_html_attr($style);

    # прописываем параметры из формата поля "Текстовый блок", учитывая параметры из $style
    $style_opt = "";
    if (!in_array("rows", $style_attr))
        $style_opt.= "rows='" . ($rows ? $rows : "5") . "'";
    if (!in_array("cols", $style_attr))
        $style_opt.= ( $style_opt ? " " : "") . "cols='" . ($cols ? $cols : "60") . "'";
    if ($style_opt)
        $style_opt = " " . $style_opt;

    // вывод функции
    $result = '';
    # вывод Caption, если нужно
    if ($caption) {
        $result .= nc_field_caption($field,"",$field_name);
    }

    # учтем allowTags еще и от самого формата поля
    // $format['html']: 0- наследовать, 1 - разрешить, 2 - запретить
    if ($format['html'])
        $allowTags = ($format['html'] == 1);

    #редактор встроен или нет?
    $EmbedEditor = $format['fck'] ? ($format['fck'] == 1) : $system_env['EmbedEditor'];
    $no_cm = '';
    # если разрешены HTML-теги, вывести кнопку
    if ($nc_core->admin_mode && $allowTags && $system_env['EditorType'] > 1 && $EmbedEditor != 1) {
        $sess_id = ($AUTHORIZATION_TYPE == "session" ? "&" . session_name() . "=" . session_id() : "");
        $link = $system_env['EditorType'] == NC_FCKEDITOR ? "editors/FCKeditor/neditor.php" : "editors/ckeditor/neditor.php";
        $result.= "<button type='button' onclick=\"window.open('" . $SUB_FOLDER . $HTTP_ROOT_PATH . $link . "?form=adminForm&control=f_" . $field_name . $sess_id . "', 'Editor', 'width=750,height=605,resizable=yes,scrollbars=no,toolbar=no,location=no,status=no,menubar=no');\">" . TOOLS_HTML_INFO . "</button><br />";
        $no_cm = " class='no_cm' ";
        
    } // редактор встроен
    elseif ($allowTags && $system_env['EditorType'] > 1 && $EmbedEditor == 1) {
        include_once ($ROOT_FOLDER . "editors/nc_editors.class.php");
        $editor = new nc_Editors($system_env['EditorType'], "f_" . $field_name, $value);
        $result.= $editor->get_html();
        unset($editor);
    }

    if (!$nc_core->inside_admin && ($format['bbcode'] || $bbcode) && !$format['usereditor']) {
        $result.= nc_bbcode_bar('this', 'adminForm', 'f_' . $field_name, 1);
    }

    if (!$allowTags || $EmbedEditor != 1) {
        $result.= "<textarea $no_cm id='f_" . $field_name . "' name='f_" . $field_name . "'" . $style_opt . ($style ? " " . $style : "") . ">" . htmlspecialchars_decode($value) . "</textarea>";

        if ($format['usereditor']) {
            require_once ($ROOT_FOLDER . "editors/nc_UserEditor/nc_UserEditor.class.php");
            $editor = new nc_UserEditor();
            $editor->Value = $value;
            $result .= $editor->CreateHtml("f_" . $field_name);
        }
    }
    //$result .= nc_field_validation('textarea', 'f_'.$field_name, $field['id'], 'text', $field['not_null']);
    return $result;
}

/**
 * Вывод поля типа "Строка" в альтернативных формах шаблона
 * @param string имя поля
 * @param string дополнительные свойства для <input ...>
 * @param int идентификатор компонента, его стоит указывать при вызове функции т.к. в функции s_list_class() его глобальное значение будет иное
 * @param bool выводить описание поля или нет
 * @return string
 */
function nc_string_field($field_name, $style = "", $classID = "", $caption = false, $value = '', $valid = false, $caption_style = null) {
    // для получения значения поля
    global $fldValue, $fldID, $systemTableID;

    $nc_core = nc_Core::get_object();
    // текущее значение компонента
    if (!$classID)
        $classID = $nc_core->sub_class->get_current('Class_ID');

    $sysTable = $systemTableID ? $systemTableID : $nc_core->component->get_by_id($classID, 'System_Table_ID');
    $show_field_errors = ( $classID == $nc_core->sub_class->get_current('Class_ID'));

    $component = new nc_Component($classID, $sysTable);
    $fields = $component->get_fields(NC_FIELDTYPE_STRING);
    // поиск поля
    $field = 0;
    foreach ($fields as $v)
        if ($v['name'] == $field_name)
            $field = $v;
    // поля не существует
    if (!$field) {
        if ($show_field_errors) {
            trigger_error("<b>nc_string_field()</b>: Incorrect field name (" . $field_name . ")", E_USER_WARNING);
        }
        return false;
    }

    // поле не доступно для редактирования
    if ($field['edit_type'] == 3 || ($field['edit_type'] == 2 && !nc_field_check_admin_perm())) {
        return false;
    }

    // значение поля
    if (!$value && is_array($fldID)) {
        $t = array_flip($fldID);
        $value = $fldValue[$t[$field['id']]];
    }

    // вывод функции
    $result = '';

    # вывод Caption, если нужно
    if ($caption) {
        $result .= nc_field_caption($field, $caption_style,$field_name);
    }
    if($valid) {
        $result = "<span id='nc_field_$fldID'>$result</span>";
    }

    if ($value == NULL) {
        if ($field['format'] == 'url') {
            $value = (isURL($field['default']) ? $field['default'] : "http://");
        } elseif ($field['default']) {
            $value = $field['default'];
        }
    }

    # формат поля
    $inputType = $field['format'] == 'password' ? 'password' : 'text';
//echo $field['format'];
    # проверим, есть ли в параметре "style", атрибуты
    $style_attr = nc_reg_search_html_attr($style);

    # прописываем параметры из $style
    $style_opt = "";
    if (!in_array("maxlength", $style_attr))
        $style_opt.= "maxlength='255'";
    if (!in_array("size", $style_attr))
        $style_opt.= ( $style_opt ? " " : "") . "size='50'";
    if (!in_array("type", $style_attr))
        $style_opt.= ( $style_opt ? " " : "") . "type='" . $inputType . "'";
    if ($style_opt)
        $style_opt = " " . $style_opt;

    $result.= "<input id='f_".$field_name . "' name='f_".$field_name . "'" . $style_opt . ($style ? " " . $style : "") . " value='" . htmlspecialchars_decode($value, ENT_QUOTES) . "' class='form-control'  />";

    //$result .= nc_field_validation('input', 'f_'.$field_name, $field['id'], 'string', $field['not_null'], $field['format']);
    return $result;
}

/**
 * Вывод поля типа "Целое число" в альтернативных формах шаблона
 * @param string имя поля
 * @param string дополнительные свойства для <input ...>
 * @param int идентификатор компонента, его стоит указывать при вызове функции т.к. в функции s_list_class() его глобальное значение будет иное
 * @param bool выводить описание поля или нет
 * @return string
 */
function nc_int_field($field_name, $style = "", $classID = "", $caption = false, $value = '') {
    // для получения значения поля
    global $fldValue, $fldID, $systemTableID;

    $nc_core = nc_Core::get_object();
    // текущее значение компонента
    if (!$classID)
        $classID = $nc_core->sub_class->get_current('Class_ID');

    $show_field_errors = ( $classID == $nc_core->sub_class->get_current('Class_ID'));
    $sysTable = $systemTableID ? $systemTableID : $nc_core->component->get_by_id($classID, 'System_Table_ID');

    $component = new nc_Component($classID, $sysTable);
    $fields = $component->get_fields(NC_FIELDTYPE_INT);
    // поиск поля
    $field = 0;
    foreach ($fields as $v)
        if ($v['name'] == $field_name)
            $field = $v;
    // поля не существует
    if (!$field) {
        if ($show_field_errors) {
            trigger_error("<b>nc_int_field()</b>: Incorrect field name (" . $field_name . ")", E_USER_WARNING);
        }
        return false;
    }

    // поле не доступно для редактирования
    if ($field['edit_type'] == 3 || ($field['edit_type'] == 2 && !nc_field_check_admin_perm())) {
        return false;
    }

    // значение поля
    if (!$value && is_array($fldID)) {
        $t = array_flip($fldID);
        $value = $fldValue[$t[$field['id']]];
    }

    // вывод функции
    $result = '';
    # вывод Caption, если нужно
    if ($caption) {
        $result .= nc_field_caption($field);
    }

    # если поле обязательно для заполнения
    if ($value == NULL && $field['default'] != NULL)
        $value = $field['default'];

    # проверим, есть ли в параметре "style", атрибуты
    $style_attr = nc_reg_search_html_attr($style);

    # прописываем параметры из $style
    $style_opt = "";
    if (!in_array("maxlength", $style_attr))
        $style_opt.= "maxlength='12'";
    if (!in_array("size", $style_attr))
        $style_opt.= ( $style_opt ? " " : "") . "size='12'";
    if (!in_array("type", $style_attr))
        $style_opt.= ( $style_opt ? " " : "") . "type='text'";
    if ($style_opt)
        $style_opt = " " . $style_opt;

    $result.= "<input name='f_" . $field_name . "'" . $style_opt . ($style ? " " . $style : "") . " value='" . $value . "' />";
    //$result .= nc_field_validation('input', 'f_'.$field_name, $field['id'], 'int', $field['not_null']);
    return $result;
}

/**
 * Вывод поля типа "Число с плавающей запятой" в альтернативных формах шаблона
 * @param string имя поля
 * @param string дополнительные свойства для <input ...>
 * @param int идентификатор компонента, его стоит указывать при вызове функции т.к. в функции s_list_class() его глобальное значение будет иное
 * @param bool выводить описание поля или нет
 * @return string
 */
function nc_float_field($field_name, $style = "", $classID = "", $caption = false, $value = null) {
    // для получения значения поля
    global $fldValue, $fldID, $systemTableID;

    $nc_core = nc_Core::get_object();
    // текущее значение компонента
    if (!$classID)
        $classID = $nc_core->sub_class->get_current('Class_ID');

    $show_field_errors = ( $classID == $nc_core->sub_class->get_current('Class_ID'));
    $sysTable = $systemTableID ? $systemTableID : $nc_core->component->get_by_id($classID, 'System_Table_ID');

    $component = new nc_Component($classID, $sysTable);
    $fields = $component->get_fields(NC_FIELDTYPE_FLOAT);
    // поиск поля
    $field = 0;
    foreach ($fields as $v)
        if ($v['name'] == $field_name)
            $field = $v;
    // поля не существует
    if (!$field) {
        if ($show_field_errors) {
            trigger_error("<b>nc_float_field()</b>: Incorrect field name (" . $field_name . ")", E_USER_WARNING);
        }
        return false;
    }

    // поле не доступно для редактирования
    if ($field['edit_type'] == 3 || ($field['edit_type'] == 2 && !nc_field_check_admin_perm())) {
        return false;
    }

    // значение поля
    if (!$value && is_array($fldID)) {
        $t = array_flip($fldID);
        $value = $fldValue[$t[$field['id']]];
    }

    // вывод функции
    $result = '';
    # вывод Caption, если нужно
    if ($caption) {
        $result .= nc_field_caption($field);
    }

    # если поле обязательно для заполнения
    if ($value == NULL && $field['default'] != NULL)
        $value = $field['default'];

    # проверим, есть ли в параметре "style", атрибуты
    $style_attr = nc_reg_search_html_attr($style);

    # прописываем параметры из $style
    $style_opt = "";
    if (!in_array("maxlength", $style_attr))
        $style_opt.= "maxlength='12'";
    if (!in_array("size", $style_attr))
        $style_opt.= ( $style_opt ? " " : "") . "size='12'";
    if (!in_array("type", $style_attr))
        $style_opt.= ( $style_opt ? " " : "") . "type='text'";
    if ($style_opt)
        $style_opt = " " . $style_opt;

    $result.= "<input name='f_" . $field_name . "'" . $style_opt . ($style ? " " . $style : "") . " value='" . $value . "' />";
    //$result .= nc_field_validation('input', 'f_'.$field_name, $field['id'], 'float', $field['not_null']);
    return $result;
}

/**
 * Функция проверка валидности URL
 * @param string URL
 * @return bool
 */
function isURL($url) {
    return nc_preg_match("/^(https?|ftps?):\/\/[0-9a-z" . NETCAT_RUALPHABET . ";\/\?:@&=\+,\.\-_%'\"\$~!\(\)|#\^]+$/i", $url);
}

/**
 * Возвращает полный путь к объекту по его идентификатору и номеру шаблона
 * @param int идентификатор сообщения
 * @param int идентификатор компонента
 * @param  str действие с объектом ( edit, checked, delete, drop)
 * @return string ссылка на сообщение
 */
function nc_message_link($message_id, $class_id, $action = '') {
    global $db;

    $message_id = (int) $message_id;
    $class_id = (int) $class_id;

    if (!$message_id || !$class_id)
        return false;

    # основной запрос для построения пути
    $path = $db->get_var("SELECT CONCAT(sub.`Hidden_URL`, " . ($action ? "'" . $db->escape($action) . "_', " : "") . " IF(m.`Keyword` <> '', m.`Keyword`, CONCAT(cc.`EnglishName`, '_', m.`Message_ID`)), '.html')
              FROM `Message" . $class_id . "` AS m
              LEFT JOIN `Subdivision` AS sub
              ON m.`Subdivision_ID` = sub.`Subdivision_ID`
              LEFT JOIN `Sub_Class` AS cc
              ON m.`Sub_Class_ID` = cc.`Sub_Class_ID`
              WHERE m.`Message_ID` = " . $message_id . "");

    return ($path ? $path : false);
}

/**
 * Получить путь к файлу в поле $field_name_or_id объекта $message_id из шаблона $class_id
 *
 * @param mixed string or int id шаблона/название системной таблицы
 * @param int id сообщения
 * @param mixed string or int имя или ID поля
 * @param string использовать префикс для новых файлов (optional).
 *    "h_" для получения ссылки для скачивания файла под оригинальным именем
 * @return string путь до файла
 */
function nc_file_path($class_id, $message_id, $field_name_or_id, $file_name_prefix = "") {
    global $nc_core;
    static $storage = array();
    static $file_field_info = array();

    // validate
    $message_id = intval($message_id);
    if (!$message_id)
        return false;

    // for local cache
    $storage_diff = array();
    $storage_diff[] = $class_id;
    $storage_diff[] = $message_id;
    $storage_diff[] = $field_name_or_id;
    $storage_diff_key = serialize($storage_diff);

    if (!isset($storage[$storage_diff_key])) {

        // системные таблицы с идентификаторами
        $system_tables = array(1 => "Catalogue", 2 => "Subdivision", 3 => "User", 4 => "Template");

        if ($class_id && is_numeric($class_id)) {
            # query to Message and Filetable
            $message_table = "Message" . $class_id;
            $ft_id_field = "Message_ID";
            # 'Field' query parts
            $query_where = "`Class_ID` = '" . $class_id . "'";
        } elseif ($class_id && in_array($class_id, $system_tables)) {
            # query to
            $message_table = $class_id;
            $ft_id_field = $class_id . "_ID";
            # 'Field' query parts
            $tmp_array = array_flip($system_tables);
            $query_where = "`Class_ID` = 0 AND `System_Table_ID` = '" . $tmp_array[$class_id] . "'";
        } else {
            trigger_error("<b>nc_file_path()</b>: Wrong class ID (" . $class_id . ")", E_USER_WARNING);
            return false;
        }

        // get file fields
        $res = $nc_core->db->get_results("SELECT `Field_ID`, `Field_Name` FROM `Field`
      WHERE " . $query_where . " AND `TypeOfData_ID` = 6", ARRAY_A);

        if (!isset($file_field_info[$class_id])) {
            if (!empty($res)) {
                foreach ($res AS $row) {
                    $file_field_info[$class_id][$row['Field_Name']] = $row['Field_ID'];
                }
            }
        }

        // get correct field_name and field_id
        if (!$file_field_info[$class_id][$field_name_or_id] && is_numeric($field_name_or_id) && in_array($field_name_or_id, $file_field_info[$class_id])) {
            // i.e. Field_ID supplied
            $field_id = $field_name_or_id;
            $tmp_array = array_flip($file_field_info[$class_id]);
            $field_name = $tmp_array[$field_id];
        } elseif ($file_field_info[$class_id][$field_name_or_id]) {
            // Field_Name
            $field_name = $nc_core->db->escape($field_name_or_id);
            $field_id = $file_field_info[$class_id][$field_name];
        } else {
            // it doesn't seems like name nor id
            trigger_error("<b>nc_file_path()</b>: Wrong field name or ID (" . $field_name_or_id . ")", E_USER_WARNING);
            return false;
        }

        // query database
        $res = $nc_core->db->get_row("SELECT m.`" . $field_name . "` AS old_name, ft.`File_Path` AS new_path, ft.`Virt_Name` AS new_name
      FROM `" . $message_table . "` AS m
      LEFT JOIN `Filetable` AS ft
      ON (m.`" . $ft_id_field . "` = ft.`Message_ID` AND ft.`Field_ID` = '" . $field_id . "')
      WHERE m.`" . $ft_id_field . "` = '" . $message_id . "'", ARRAY_A);
        // local cache
        $storage[$storage_diff_key] = $res;
    } else {
        // restore local cache
        $res = $storage[$storage_diff_key];
    }

    if ($res["old_name"]) {
        // возвращаем результат
        // protected fs
        if ($res['new_name']) {
            return $nc_core->SUB_FOLDER . rtrim($nc_core->HTTP_FILES_PATH, "/") . $res['new_path'] . $file_name_prefix . $res['new_name'];
        }
        // значение из таблицы
        $file_data = explode(':', $res['old_name']);
        // оригинальное имя
        $file_name = $file_data[0];
        //расширение
        $ext = substr($file_name, strrpos($file_name, "."));
        // папка
        $file_path = $nc_core->SUB_FOLDER . $nc_core->HTTP_FILES_PATH;
        // путь плностью, в зависимоти от типа файловой системы
        $file_path .= ( $file_data[3]) ? $file_data[3] : $field_id . "_" . $message_id . $ext;

        return $file_path;
    }

    return false;
}

/**
 * Получить идентификаторы всех подразделов раздела с идентификатором $sub
 * @param int $sub идентификатор родительского раздела
 * @return array массив с идентификаторами подразделов
 *
 */
function nc_get_sub_children($sub) {
    global $db;

    $array[] = $sub;
    $sub_array = $db->get_col("SELECT `Subdivision_ID` FROM `Subdivision` WHERE Parent_Sub_ID = '" . intval($sub) . "'");

    if (!empty($sub_array)) {
        foreach ($sub_array AS $key => $val) {
            $array = array_merge($array, nc_get_sub_children($val));
        }
    }

    return $array;
}

/**
 * Получить идентификаторы всех дочерних макетов для макета с идентификатором $template
 * @param int $template идентификатор родительского макета
 * @return array массив с идентификаторами макетов
 *
 */
function nc_get_template_children($template) {
    global $db;
    $template = intval($template);
    $array[] = $template;
    $template_array = $db->get_col("SELECT `Template_ID` FROM `Template` WHERE `Parent_Template_ID` = '" . $template . "'");

    if (!empty($template_array)) {
        foreach ($template_array AS $key => $val) {
            $array = array_merge($array, nc_get_template_children($val));
        }
    }

    return $array;
}

/**
 * Возвращает массив с данными для получения заголовка связанного объекта
 * по формату, указанном в формате поля типа "Связь с др. объектом".
 * Для совместного использования с listQuery.
 *
 * @param string формат поля
 * @param string id связанного объекта, если нужно получить данные только
 *   по этому одному объекту. Если не указан, результат query будет содержать
 *   запрос для получения
 * @return array ассоциативный массив. Ключи:
 *   - relation_class - тип связанного объекта
 *   - query - заготовка SQL-запроса для получения строки-описания связанного объекта
 *
 *   - full_template - шаблон для listQuery для вывода названия объекта и ссылки
 *       на него (ссылка - только в режиме администрирования
 *   - name_template - шаблон - только название объекта
 */
function nc_related_parse_format($field_format, $related_item_id = null) {

    global $db, $admin_mode, $inside_admin, $SUB_FOLDER, $HTTP_ROOT_PATH, $ADMIN_PATH;

    // двойные/одинарные второго параметра в Format сейчас не обрабатываются;
    // они добавлены на случай добавления дополнительных параметров
    // поэтому можно переписать следующее регвыр как preg_split с ограничением
    // количества результатов
    preg_match("/^
                 (subdivision|sub[_-]?class|user|catalogue|\d+)  # relation class
                 (?:                  # caption (optional)
                   \s* : \s*          # delimiter from relation class
                   (['\"])?           # opening quote (optional)
                   (.*)               # caption template for listquery
                 )?
               $/xi", $field_format, $regs);

    list(, $relation_class, $quote, $caption_template) = $regs;
    if (!$relation_class) {
        trigger_error("<b>nc_related_parse_format()</b>: incorrect field format (&quot;{$fldFmt[$field_index]}&quot;)", E_USER_WARNING);
        return array();
    }

    if ($caption_template && $quote) {
        $caption_template = nc_preg_replace("/$quote$/", "", $caption_template);
    }

    $query = "";

    if (is_numeric($relation_class)) { // ШАБЛОН ДАННЫХ
        // may require further optimization
        $query = "SELECT * FROM Message$relation_class WHERE Message_ID = \$related_id";
        // использовать заголовок, указанный в настройках макета
        if (!$caption_template) {
            $caption_template = $db->get_var("SELECT TitleTemplate FROM Class WHERE Class_ID=$relation_class");
        }
        // никакого заголовка нет
        if (!$caption_template) {
            $query = "SELECT c.Class_Name, m.Message_ID
                   FROM Message{$relation_class} as m,
                        Sub_Class as sc,
                        Class as c
                  WHERE m.Message_ID = \$related_id
                    AND m.Sub_Class_ID = sc.Sub_Class_ID
                    AND sc.Class_ID = c.Class_ID";
            $caption_template = '$f_Class_Name #$f_Message_ID';
        }

        if ($admin_mode) {
            $link = $SUB_FOLDER . $HTTP_ROOT_PATH . "message.php?inside_admin=1&classID={$relation_class}&message={\$related_id}";
        } else {
            $link = "\".nc_message_link(\$f_Message_ID, $relation_class).\"";
        }
    } else { // СИСТЕМНАЯ ТАБЛИЦА
        $relation_class = strtolower($relation_class);
        $relation_class = str_replace(array("_", "-"), "", $relation_class); // sub[_-]class

        if ($relation_class == 'subdivision') {
            $query = "SELECT s.*, " .
                    ($admin_mode ? "'" . $SUB_FOLDER . $HTTP_ROOT_PATH . "?sub={\$related_id}' as LinkToObject" : "s.Hidden_URL as LinkToObject") . "
                   FROM Subdivision as s
                  WHERE s.Subdivision_ID = \$related_id";

            if (!$caption_template) {
                $caption_template = '$f_Subdivision_Name';
            }
            $link = ""; // будет взята из LinkToObject
        } elseif ($relation_class == 'user') {
            $query = "SELECT * FROM User WHERE User_ID = \$related_id";
            if (!$caption_template) {
                $caption_template = '$f_' . $GLOBALS['AUTHORIZE_BY'];
            }

            if ($inside_admin) {
                $link = $ADMIN_PATH . "#user.edit(\$f_Message_ID)' target='_top"; // некрасивый хак
            } else {
                $link = "";
            }
        } elseif ($relation_class == 'subclass') {
            $query = "SELECT sc.*, " .
                    ($admin_mode ? "'" . $SUB_FOLDER . $HTTP_ROOT_PATH . "?cc=\$related_id'  as LinkToObject" : "CONCAT(sd.Hidden_URL, sc.EnglishName, '.html') as LinkToObject") . "
                   FROM Sub_Class as sc, Subdivision as sd
                  WHERE sc.Sub_Class_ID = $related_id
                    AND sc.Subdivision_ID = sd.Subdivision_ID
                  ";
            if (!$caption_template) {
                $caption_template = '$f_Sub_Class_Name';
            }
        }
    }

    // extract - для эмуляции поведения TitleTemplate
    $caption_with_link_tpl = '";
                            $f_LinkToObject = "' . $link . '";
                            extract($data, EXTR_PREFIX_ALL, "f");
                            $result .= "<a href=\'$f_LinkToObject\'>' . $caption_template . '</a>';

    return array("relation_class" => $relation_class,
            "query" => $query,
            "full_template" => $caption_with_link_tpl,
            "name_template" => $caption_template);
}

/**
 * Элементы для редактирования поля типа "связь с другим объектом"
 * Функция не должна использоваться внутри s_list_class.
 * Работает только в admin_mode.
 *
 * @param string имя поля
 * @param string кнопка/ссылка на изменение связанного объекта
 *   например '<a href="#" onclick="%s">выбрать</a>'
 *   где на место %s будет подставлен Javascript-код.
 *   Обрабатывается через sprintf, поэтому не должно быть неэкранированного "%".
 *   Разработчику следует учитывать, что внутри вставляемого JS-кода
 *   используются одинарные кавычки.
 * @param string удаление (... $action_remove)
 * @return string
 */
function nc_related_field($field_name, $change_template = "", $remove_template = "") {

    require_once($GLOBALS['ADMIN_FOLDER'] . "related/format.inc.php");

    $result = "";

    global $fld, // массив с буквенными идентификаторами полей
    $fldID, // массив с ID полей
    $fldValue, // значения полей
    $fldName, // названия (описания) полей
    $fldFmt, // формат полей
    $fldNotNull, // обязательное
    $fldType, // тип поля
    $message, // текущий объект
    $db, $admin_mode, $inside_admin, $ADMIN_PATH;

    if (!$admin_mode)
        return "";

    if (is_array($fld) && !in_array($field_name, $fld)) {
        trigger_error("<b>nc_related_field</b>: incorrect field name ($field_name)", E_USER_WARNING);
        return;
    }
    if (!is_array($fld)) {
        return;
    }

    $tmp_array = array_flip($fld);
    $field_index = $tmp_array[$field_name];
    $field_id = $fldID[$field_index];

    if ($fldType[$field_index] != 9) {
        trigger_error("<b>nc_related_field</b>: field '$field_name' is not a link", E_USER_WARNING);
        return;
    }

    // заголовок поля
    $result .= $fldName[$field_index];
    if ($fldNotNull[$field_index])
        $result .= " (*)";
    $result .= ": <br />\n";
    $result .= "<span id='nc_rel_{$field_id}_caption'>";

    $related_id = (int) $fldValue[$field_index];
    $field_data = field_relation_factory::get_instance($fldFmt[$field_index]);

    // вывод значения
    if ($related_id) {
        $related_caption = listQuery($field_data->get_object_query($related_id), $field_data->get_full_admin_template());
        $result .= ( $related_caption ? $related_caption : sprintf(NETCAT_MODERATION_RELATED_INEXISTENT, $related_id));
    } else {
        $result .= NETCAT_MODERATION_NO_RELATED;
    }

    $result .= "</span>\n";

    // кнопки действий: заменить и удалить связь
    if (!$change_template) {
        $change_template = "&nbsp;&nbsp;&nbsp;<a href=\"#\" onclick=\"%s\">" . NETCAT_MODERATION_CHANGE_RELATED . "</a>\n";
    }

    $change_link = "window.open('" . $ADMIN_PATH . "related/select_" . $field_data->get_relation_type() . ".php?field_id={$fldID[$field_index]}', " .
            "'nc_popup_{$fld[$field_index]}', " .
            "'width={$field_data->popup_width},height={$field_data->popup_height},menubar=no,resizable=no,scrollbars=yes,toolbar=no,resizable=yes'); " .
            "return false;";

    $result .= sprintf($change_template, $change_link);

    if (!$fldNotNull[$field_index]) {
        if (!$remove_template) {
            $remove_template = "&nbsp;&nbsp;&nbsp;<a href=\"#\" onclick=\"%s\">" . NETCAT_MODERATION_REMOVE_RELATED . "</a>\n";
        }

        $remove_link = "document.getElementById('nc_rel_{$field_id}_value').value='';" .
                "document.getElementById('nc_rel_{$field_id}_caption').innerHTML = '" . NETCAT_MODERATION_NO_RELATED . "';" .
                "return false;";

        $result .= sprintf($remove_template, $remove_link);
    }

    // hidden
    $result .= "<input type='hidden' name='f_{$fld[$field_index]}' id='nc_rel_{$field_id}_value' value='$related_id' />\n";

    // готово
    $result .= "<br />\n";
    return $result;
}

/**
 * Кнопка "отправить данные" для использования в альтернативных формах.
 * Внутри интерфейса 3.0 рисует кнопку в областе кнопок действий,
 * вне него - обычный <input type=submit>.
 *
 * @param string текст на кнопке
 * @return string;
 */
function nc_submit_button($caption) {
    global $admin_mode;

    if ($admin_mode) {
        return null;
    }

    $inside_admin = $GLOBALS['inside_admin'];
    $UI_CONFIG = $GLOBALS['UI_CONFIG'];

    if ($inside_admin && is_object($UI_CONFIG)) {
        $GLOBALS['UI_CONFIG']->actionButtons[] = array("id" => "submit",
                "caption" => $caption,
                "action" => "mainView.submitIframeForm('adminForm')");
        return "<input type='submit' class='hidden' />\r\n";
    } else {
        return "<input type='submit' value='" . htmlspecialchars_decode($caption) . "' />\r\n";
    }
}

/**
 * Кнопка "отменить" для использования в альтернативных формах.
 * Внутри интерфейса 3.0 НЕрисует кнопку в областе кнопок действий,
 * вне него - обычный <input type=reset>.
 *
 * @param string текст на кнопке
 * @return string;
 */
function nc_reset_button($caption) {

    $inside_admin = $GLOBALS['inside_admin'];
    $UI_CONFIG = $GLOBALS['UI_CONFIG'];

    if ($inside_admin && is_object($UI_CONFIG)) {
        //$GLOBALS['UI_CONFIG']->actionButtons[] = array("id" => "submit",
        //                         "caption" => $caption,
        //                         "action" => "mainView.submitIframeForm('adminForm')");
        //return "<input type='submit' class='hidden'>\r\n";
    } else {
        return "<input type='reset' value='" . htmlspecialchars_decode($caption) . "' />\r\n";
    }
}

/**
 * Convert array to string
 * @param array input array
 * @param array template, keys - prefix, suffix, element, divider
 * @return string
 */
function nc_array_to_string($arr, $template) {
    $result = '';
    eval("\$result = \"" . $template['prefix'] . "\";");

    $numElement = count($arr);
    $i = 1;

    if (!empty($arr)) {
        foreach ($arr as $k => $v) {
            $temp = str_replace(Array('%ELEMENT', '%I', '%KEY'), Array($v, $i, $k), $template['element']);
            eval("\$result .= \"" . $temp . "\";");
            if ($i++ != $numElement)
                eval("\$result .= \"" . $template['divider'] . "\";");
        }
    }

    eval("\$result .= \"" . $template['suffix'] . "\";");

    return $result;
}

/**
 * Вывод массива в структурированном виде
 * @param array массив для вывода
 * @return bool true;
 */
function dump($var) {

    print "<hr><xmp>" . print_r($var, 1) . "</xmp><hr>";

    return true;
}

/**
 * Функция создания массива смалов
 *
 * @no params
 * @return mixed;
 *
 * @todo перенести картинки, их названия и обозначения в базу
 *
 */
function nc_smiles_array() {
    global $SUB_FOLDER;

    $smiles_dir = $SUB_FOLDER . "/images/smiles/";
    # массив смайлов
    $smiles = array(
            array(0 => ":)", 1 => "smile.gif", 2 => NETCAT_SMILE_SMILE),
            array(0 => ":D", 1 => "bigsmile.gif", 2 => NETCAT_SMILE_BIGSMILE),
            array(0 => ":grin:", 1 => "grin.gif", 2 => NETCAT_SMILE_GRIN),
            array(0 => ":laugh:", 1 => "laugh.gif", 2 => NETCAT_SMILE_LAUGH),
            array(0 => ":proud:", 1 => "proud.gif", 2 => NETCAT_SMILE_PROUD),
            array(0 => ":yes:", 1 => "yes.gif", 2 => NETCAT_SMILE_YES),
            array(0 => ":wink:", 1 => "wink.gif", 2 => NETCAT_SMILE_WINK),
            array(0 => ":cool:", 1 => "cool.gif", 2 => NETCAT_SMILE_COOL),
            array(0 => ":eyes:", 1 => "rolleyes.gif", 2 => NETCAT_SMILE_ROLLEYES),
            array(0 => ":lookdown:", 1 => "lookdown.gif", 2 => NETCAT_SMILE_LOOKDOWN),
            array(0 => ":(", 1 => "sad.gif", 2 => NETCAT_SMILE_SAD),
            array(0 => ":spy:", 1 => "suspicious.gif", 2 => NETCAT_SMILE_SUSPICIOUS),
            array(0 => ":angry:", 1 => "angry.gif", 2 => NETCAT_SMILE_ANGRY),
            array(0 => ":bad:", 1 => "shakefist.gif", 2 => NETCAT_SMILE_SHAKEFIST),
            array(0 => ":stern:", 1 => "stern.gif", 2 => NETCAT_SMILE_STERN),
            array(0 => ":kiss:", 1 => "kiss.gif", 2 => NETCAT_SMILE_KISS),
            array(0 => ":think:", 1 => "think.gif", 2 => NETCAT_SMILE_THINK),
            array(0 => ":yep:", 1 => "thumbsup.gif", 2 => NETCAT_SMILE_THUMBSUP),
            array(0 => ":sick:", 1 => "sick.gif", 2 => NETCAT_SMILE_SICK),
            array(0 => ":no:", 1 => "no.gif", 2 => NETCAT_SMILE_NO),
            array(0 => ":cantlook:", 1 => "cantlook.gif", 2 => NETCAT_SMILE_CANTLOOK),
            array(0 => ":doh:", 1 => "doh.gif", 2 => NETCAT_SMILE_DOH),
            array(0 => ":out:", 1 => "knockedout.gif", 2 => NETCAT_SMILE_KNOCKEDOUT),
            array(0 => ":eyeup:", 1 => "eyeup.gif", 2 => NETCAT_SMILE_EYEUP),
            array(0 => ":shh:", 1 => "shh.gif", 2 => NETCAT_SMILE_QUIET),
            array(0 => ":evil:", 1 => "evil.gif", 2 => NETCAT_SMILE_EVIL),
            array(0 => ":upset:", 1 => "upset.gif", 2 => NETCAT_SMILE_UPSET),
            array(0 => ":undecided:", 1 => "undecided.gif", 2 => NETCAT_SMILE_UNDECIDED),
            array(0 => ":cry:", 1 => "cry.gif", 2 => NETCAT_SMILE_CRY),
            array(0 => ":unsure:", 1 => "unsure.gif", 2 => NETCAT_SMILE_UNSURE)
    );

    return array($smiles, $smiles_dir);
}

/**
 * Функция вывода панельки с BB-кодами
 *
 * @param string идентификатор окна для JS кода
 * @param string идентификатор формы для JS кода
 * @param string идентификатор textarea для JS кода
 * @param bool выводить строку с помощью?
 * @param array какие коды выводить, по-умолчанию все
 * @param string префикс вывода панельки с кодами
 * @param string суффикс вывода панельки с кодами
 * @return string;
 */
function nc_bbcode_bar($winID, $formID, $textareaID, $help = "", $codes = "", $prefix = "", $suffix = "", $noscript = false) {
    global $ADMIN_PATH, $SUB_FOLDER;

    if (!($winID && $formID && $textareaID))
        return false;

    # массив вывода BB-кодов
    $BBcode = array(
            "SIZE" => "<select class='nc_bbcode_bar_size' onChange=\"insert_bbcode('" . $winID . "','" . $formID . "','" . $textareaID . "', '[SIZE=' + this.value + ']', '[/SIZE]'); this.selectedIndex=0;\"  name='bb_fontsize' title='" . NETCAT_BBCODE_SIZE . "' " . ($help ? "onMouseOver=\"show_bbcode_tips('" . $winID . "','" . $formID . "','" . $textareaID . "','" . NETCAT_BBCODE_HELP_SIZE . "');\"" : "") . ">\r\n<option value=''>-- " . NETCAT_BBCODE_SIZE_DEF . " --\r\n<option value='8'>8px\r\n<option value='10'>10px\r\n<option value='12'>12px\r\n<option value='14'>14px\r\n<option value='16'>16px\r\n<option value='18'>18px\r\n<option value='20'>20px\r\n<option value='22'>22px\r\n<option value='24'>24px\r\n</select>\r\n",
            "COLOR" => "<a href='#' onClick=\"show_color_buttons('" . $textareaID . "'); return false;\" id='nc_bbcode_color_button_" . $textareaID . "' " . ($help ? "onMouseOver=\"show_bbcode_tips('" . $winID . "','" . $formID . "','" . $textareaID . "','" . NETCAT_BBCODE_HELP_COLOR . "');\"" : "") . "><img src='" . $SUB_FOLDER . "/images/i_color.gif' alt='" . NETCAT_BBCODE_COLOR . "' class='nc_bbcode_wicon'></a>\r\n",
            "SMILE" => "<a href='#' onClick=\"show_smile_buttons('" . $textareaID . "'); return false;\" id='nc_bbcode_smile_button_" . $textareaID . "' " . ($help ? "onMouseOver=\"show_bbcode_tips('" . $winID . "','" . $formID . "','" . $textareaID . "','" . NETCAT_BBCODE_HELP_SMILE . "');\"" : "") . "><img src='" . $SUB_FOLDER . "/images/i_smile.gif' alt='" . NETCAT_BBCODE_SMILE . "' class='nc_bbcode_wicon'></a>\r\n",
            "B" => "<a href='#' onClick=\"insert_bbcode('" . $winID . "','" . $formID . "','" . $textareaID . "','[B]','[/B]'); return false;\" " . ($help ? "onMouseOver=\"show_bbcode_tips('" . $winID . "','" . $formID . "','" . $textareaID . "','" . NETCAT_BBCODE_HELP_B . "');\"" : "") . "><img src='" . $SUB_FOLDER . "/images/i_bold.gif' alt='" . NETCAT_BBCODE_B . "' class='nc_bbcode_icon'></a>\r\n",
            "I" => "<a href='#' onClick=\"insert_bbcode('" . $winID . "','" . $formID . "','" . $textareaID . "','[I]','[/I]'); return false;\" " . ($help ? "onMouseOver=\"show_bbcode_tips('" . $winID . "','" . $formID . "','" . $textareaID . "','" . NETCAT_BBCODE_HELP_I . "');\"" : "") . "><img src='" . $SUB_FOLDER . "/images/i_italy.gif' alt='" . NETCAT_BBCODE_I . "' class='nc_bbcode_icon'></a>\r\n",
            "U" => "<a href='#' onClick=\"insert_bbcode('" . $winID . "','" . $formID . "','" . $textareaID . "','[U]','[/U]'); return false;\" " . ($help ? "onMouseOver=\"show_bbcode_tips('" . $winID . "','" . $formID . "','" . $textareaID . "','" . NETCAT_BBCODE_HELP_U . "');\"" : "") . "><img src='" . $SUB_FOLDER . "/images/i_underline.gif' alt='" . NETCAT_BBCODE_U . "' class='nc_bbcode_icon'></a>\r\n",
            "S" => "<a href='#' onClick=\"insert_bbcode('" . $winID . "','" . $formID . "','" . $textareaID . "','[S]','[/S]'); return false;\" " . ($help ? "onMouseOver=\"show_bbcode_tips('" . $winID . "','" . $formID . "','" . $textareaID . "','" . NETCAT_BBCODE_HELP_S . "');\"" : "") . "><img src='" . $SUB_FOLDER . "/images/i_strike.gif' alt='" . NETCAT_BBCODE_S . "' class='nc_bbcode_icon'></a>\r\n",
            "LIST" => "<a href='#' onClick=\"insert_bbcode('" . $winID . "','" . $formID . "','" . $textareaID . "','[LIST]','[/LIST]'); return false;\" " . ($help ? "onMouseOver=\"show_bbcode_tips('" . $winID . "','" . $formID . "','" . $textareaID . "','" . NETCAT_BBCODE_HELP_LIST . "');\"" : "") . "><img src='" . $SUB_FOLDER . "/images/i_list.gif' alt='" . NETCAT_BBCODE_LIST . "' class='nc_bbcode_icon'></a>\r\n",
            "QUOTE" => "<a href='#' onClick=\"insert_bbcode('" . $winID . "','" . $formID . "','" . $textareaID . "','[QUOTE]','[/QUOTE]'); return false;\" " . ($help ? "onMouseOver=\"show_bbcode_tips('" . $winID . "','" . $formID . "','" . $textareaID . "','" . NETCAT_BBCODE_HELP_QUOTE . "');\"" : "") . "><img src='" . $SUB_FOLDER . "/images/i_quote.gif' alt='" . NETCAT_BBCODE_QUOTE . "' class='nc_bbcode_icon'></a>\r\n",
            "CODE" => "<a href='#' onClick=\"insert_bbcode('" . $winID . "','" . $formID . "','" . $textareaID . "','[CODE]','[/CODE]'); return false;\" " . ($help ? "onMouseOver=\"show_bbcode_tips('" . $winID . "','" . $formID . "','" . $textareaID . "','" . NETCAT_BBCODE_HELP_CODE . "');\"" : "") . "><img src='" . $SUB_FOLDER . "/images/i_code.gif' alt='" . NETCAT_BBCODE_CODE . "' class='nc_bbcode_icon'></a>\r\n",
            "IMG" => "<a href='#' onClick=\"insert_bbcode('" . $winID . "','" . $formID . "','" . $textareaID . "','[IMG=\'http://\']',''); return false;\" " . ($help ? "onMouseOver=\"show_bbcode_tips('" . $winID . "','" . $formID . "','" . $textareaID . "','" . NETCAT_BBCODE_HELP_IMG . "');\"" : "") . "><img src='" . $SUB_FOLDER . "/images/i_picture.gif' alt='" . NETCAT_BBCODE_IMG . "' class='nc_bbcode_icon'></a>\r\n",
            "URL" => "<a href='#' onClick=\"insert_bbcode('" . $winID . "','" . $formID . "','" . $textareaID . "','[URL=\'http://\']','[/URL]'); return false;\" " . ($help ? "onMouseOver=\"show_bbcode_tips('" . $winID . "','" . $formID . "','" . $textareaID . "','" . NETCAT_BBCODE_HELP_URL . "');\"" : "") . "><img src='" . $SUB_FOLDER . "/images/i_link.gif' alt='" . NETCAT_BBCODE_URL . "' class='nc_bbcode_icon'></a>\r\n",
            "CUT" => "<a href='#' onClick=\"insert_bbcode('" . $winID . "','" . $formID . "','" . $textareaID . "','[CUT=\'" . NETCAT_BBCODE_CUT_MORE . "\']','[/CUT]'); return false;\" " . ($help ? "onMouseOver=\"show_bbcode_tips('" . $winID . "','" . $formID . "','" . $textareaID . "','" . NETCAT_BBCODE_HELP_CUT . "');\"" : "") . "><img src='" . $SUB_FOLDER . "/images/i_cut.gif' alt='" . NETCAT_BBCODE_CUT . "' class='nc_bbcode_icon'></a>\r\n");

    if ($codes) {
        $codes = (array) $codes;
        $codes = array_map("strtoupper", $codes);
        # ошибка в BB-кодах
        if (($diff = array_diff($codes, array_keys($BBcode)))) {
            $result = "<div style='nc_bbcode_error'>" . (sizeof($diff) == 1 ? NETCAT_BBCODE_ERROR_1 : NETCAT_BBCODE_ERROR_2) . " " . join(", ", $diff) . "</div>";
            return $result;
        }
        # получаем нужные коды в нужном порядке
        # $codes = array_flip($codes);
        # PHP 5: $BBcode_arr = array_intersect_key($BBcode, $codes);
        foreach ($codes AS $value) {
            $BBcode_arr[] = $BBcode[$value];
        }
        $BBcode_str = join("\r\n", $BBcode_arr); # array_merge($codes, $BBcode_arr)
    } else {
        # получаем все коды
        $BBcode_str = join("\r\n", $BBcode);
    }

    $result = "";

    # формируем панельку с кодами
    if (!$noscript)
        $result.= "<script language='JavaScript' type='text/javascript' src='" . $ADMIN_PATH . "js/bbcode.js'></script>";
    $result.= ( $prefix !== false ? $prefix : "<div>") . "
    " . $BBcode_str . "
  " . ($help ? "<input type='text' name='bbcode_helpbox_" . $textareaID . "' value='" . NETCAT_BBCODE_HELP . "' class='nc_bbcode_helpbox nc_no_' />" : "") . "
    " . ($suffix !== false ? $suffix : "</div>");

    if (!$codes || !empty($codes) && in_array("COLOR", $codes)) {
        # палитра безопасных цветов
        $colors = array("770000", "BB0000", "FF0000", "007700", "00BB00", "00FF00", "000077", "0000BB", "0000FF", "000000",
                "779900", "BB9900", "FF9900", "007799", "00BB99", "00FF99", "990077", "9900BB", "9900FF", "FFFFFF",
                "77CC00", "BBCC00", "FFCC00", "0077CC", "00BBCC", "00FFCC", "CC0077", "CC00BB", "CC00FF", "999999");
        # цветов встроке
        $inline = 10;
        $total_colors = sizeof($colors);
        $i = 0;
        # панелька с цветами
        while ($i < $total_colors) {
            if ($i != 0 && $i != $total_colors && intval($i / $inline) == ($i / $inline)) {
                $result.= "</div>\r\n<div class='nc_bbcode_color'>\r\n";
            } elseif ($i == 0) {
                $result.= "<div id='color_buttons_" . $textareaID . "' class='nc_bbcode_colors' style='display:none;'>\n<div class='nc_bbcode_color_top'>\r\n";
            }
            $result.= "<input type='button' value='' class='" . ($colors[$i] == "FFFFFF" ? "nc_bbcode_color_white" : "nc_bbcode_color") . "' style='background:#" . $colors[$i] . ";' onClick=\"insert_bbcode('" . $winID . "','" . $formID . "','" . $textareaID . "', '[COLOR=" . $colors[$i] . "]', '[/COLOR]'); show_color_buttons('" . $textareaID . "');\" />\r\n";
            if (($i + 1) == $total_colors)
                $result.= "</div>\r\n</div>\r\n";
            ++$i;
        }
    }

    if (!$codes || !empty($codes) && in_array("SMILE", $codes)) {
        # панелька со смайлами
        list($smiles, $smiles_dir) = nc_smiles_array();

        $inline = 5;
        $total_smiles = sizeof($smiles);
        $i = 0;

        while ($i < $total_smiles) {
            if ($i != 0 && $i != $total_smiles && intval($i / $inline) == ($i / $inline)) {
                $result.= "</div>\r\n<div class='nc_bbcode_smile'>\r\n";
            } elseif ($i == 0) {
                $result.= "<div id='smile_buttons_" . $textareaID . "' class='nc_bbcode_smiles' style='display:none;'>\n<div class='nc_bbcode_smile_top'>\r\n";
            }
            $result.= "<input type='button' value='' onclick=\"insert_bbcode('" . $winID . "','" . $formID . "','" . $textareaID . "', '" . $smiles[$i][0] . "', ''); show_smile_buttons('" . $textareaID . "');\" class='nc_bbcode_smile' style='background:url(" . $smiles_dir . $smiles[$i][1] . ") no-repeat center;' />\r\n";
            if (($i + 1) == $total_smiles)
                $result.= "</div>\r\n</div>\r\n";
            ++$i;
        }
    }

    return $result;
}

/**
 * Функция обработки текста с BB-кодами
 * заменяет коды на их HTML эквиваленты
 *
 * @param string текст
 * @param string ссылка на полный просмотр объекта
 * @param bool полный вывод объекта?
 * @param array массив допустимых кодов
 * @return string;
 */
function nc_bbcode($text, $cut_link = "", $cut_full = "", $codes = "") {
    # массив допустимых BB-кодов
    $allow_codes = array("SIZE", "ALIGN", "COLOR", "SMILE", "B", "I", "U", "S", "LIST", "QUOTE", "CODE", "IMG", "URL", "CUT", "OL", "UL", "LI");

    if ($codes) {
        $codes = (array) $codes;
        $codes = array_map("strtoupper", $codes);
        # ошибка в BB-кодах
        if (($diff = array_diff($codes, $allow_codes))) {
            $result = "<div class='nc_bbcode_error'>" . (sizeof($diff) == 1 ? NETCAT_BBCODE_ERROR_1 : NETCAT_BBCODE_ERROR_2) . " " . join(", ", $diff) . "</div>";
            return $result . $text;
        }
        $codes = array_flip($codes);
    }

    if (isset($codes['SMILE']) || !$codes) {
        # получаем данные из функции nc_smiles_array()
        list($smiles, $smiles_dir) = nc_smiles_array();

        $i = 0;
        $total_smiles = sizeof($smiles);
        # заменяем смайлы
        while ($i < $total_smiles) {
            # генерация смайлика
            $smile = "<img src='" . $smiles_dir . $smiles[$i][1] . "' alt='" . $smiles[$i][2] . "' class='nc_bbcode_smile_in_text'>";
            $text = str_replace($smiles[$i][0], $smile, $text);
            ++$i;
        }
    }

    $BBcodes = array();
    if (isset($codes['B']) || !$codes) {
        $BBcodes[] = "b";
    }
    if (isset($codes['I']) || !$codes) {
        $BBcodes[] = "i";
    }
    if (isset($codes['U']) || !$codes) {
        $BBcodes[] = "u";
    }
    # pasing
    for ($i = 0; $i < sizeof($BBcodes); $i++) {
        $BBregex = "#\[(/?" . $BBcodes[$i] . ")\]#si";
        while (preg_match($BBregex, $text)) {
            $text = nc_preg_replace($BBregex, "<\$1>", $text);
        }
    }

    # url_accept_chars
    $uac = "-A-Z0-9\+&@#/%\?=~_|\!:,\.;\[\]";

    # RegExp array
    $RegEx = array();
    # replace array
    $HtmlCodes = array();
    # Условия на доступность BB-кодов

    if (isset($codes['QUOTE']) || !$codes) {
        $RegEx[] = "!\[quote=(?:&quot;|')?(.*?)(?:&quot;|')?\](.*?)\[/quote\]!si";
        $RegEx[] = "!\[quote\](.*?)\[/quote\]!si";
        $HtmlCodes[] = "<div class='nc_bbcode_quote_1_top'><b>\$1 " . NETCAT_BBCODE_QUOTE_USER . ":</b><div class='nc_bbcode_quote_1'>\$2</div></div>";
        $HtmlCodes[] = "<div class='nc_bbcode_quote_2_top'><b>" . NETCAT_BBCODE_QUOTE . ":</b><div class='nc_bbcode_quote_2'>\$1</div></div>";
    }

    if (isset($codes['COLOR']) || !$codes) {
        $RegEx[] = "!\[color=(?:&quot;|')?([a-f\d]{1,6})(?:&quot;|')?\](.*?)\[/color\]!si";
        $HtmlCodes[] = "<span style='color:#\$1;' class='nc_bbcode_color'>\$2</span>";
    }

    if (isset($codes['SIZE']) || !$codes) {
        $RegEx[] = "!\[size=(?:&quot;|')?([\d]{1,2})(?:&quot;|')?\](.*?)\[/size\]!si";
        $HtmlCodes[] = "<span style='font-size:\$1px' class='nc_bbcode_size'>\$2</span>";
    }

    if (isset($codes['ALIGN']) || !$codes) {
        $RegEx[] = "!\[align=(left|center|right|justify)?\](.*?)\[/align\]!si";
        $HtmlCodes[] = "<div style='text-align:\$1;'>\$2</div>";
    }

    if (isset($codes['URL']) || !$codes) {
        $RegEx[] = "!\[url\]((?:https?://|www\.|ftp://)[$uac]*?)\[/url\]!si";
        $RegEx[] = "!\[url=(?:&quot;|')((?:https?://|www\.|ftp://)[$uac]*?)(?:&quot;|')\](.*?)\[/url\]!si";
        $HtmlCodes[] = "<!--noindex--><a href='\$1' class='nc_bbcode_url_1' target='_blank' rel='nofollow'>\$1</a><!--/noindex-->";
        $HtmlCodes[] = "<!--noindex--><a href='\$1' class='nc_bbcode_url_2' target='_blank' rel='nofollow'>\$2</a><!--/noindex-->";
    }

    if (isset($codes['IMG']) || !$codes) {
        $RegEx[] = "!\[img=(?:&quot;|')?((?:https?://|www\.|ftp://)[$uac]*?)(?:&quot;|')?\]!si";
        $RegEx[] = "!\[img\](.*?)\[/img\]!si";
        $HtmlCodes[] = "<img src='\$1' alt='" . NETCAT_BBCODE_IMG . "' class='nc_bbcode_img' />";
        $HtmlCodes[] = "<img src='\$1' alt='" . NETCAT_BBCODE_IMG . "' class='nc_bbcode_img' />";
    }

    if (isset($codes['CODE']) || !$codes) {
        $RegEx[] = "!\[code\](.*?)\[/code\]!si";
        $HtmlCodes[] = "<div class='nc_bbcode_code'><b>" . NETCAT_BBCODE_CODE . ":</b><pre>\$1</pre></div>";
    }

    if (isset($codes['S']) || !$codes) {
        $RegEx[] = "!\[s\](.*?)\[/s\]!si";
        $HtmlCodes[] = "<span style='text-decoration:line-through;' class='nc_bbcode_s'>\$1</span>";
    }

    if (isset($codes['LIST']) || !$codes) {
        $RegEx[] = "'\[list\](.*?(?!\[list\]))\[/list\]([^\r\n]*)\r?\n?'si";
        $HtmlCodes[] = "<span class='nc_bbcode_list_closed'>&bull; \$1</span>\$2";
    }

    if (isset($codes['OL']) || !$codes) {
        $RegEx[] = "!\[ol\](.*?)\[/ol\]!si";
        $HtmlCodes[] = "<ol>\$1</ol>";
    }

    if (isset($codes['UL']) || !$codes) {
        $RegEx[] = "!\[ul\](.*?)\[/ul\]!si";
        $HtmlCodes[] = "<ul>\$1</ul>";
    }

    if (isset($codes['LI']) || !$codes) {
        $RegEx[] = "!\[li\](.*?)\[/li\]!si";
        $HtmlCodes[] = "<li>\$1</li>";
    }

    # обработка
    $t = $text;
    $text = nc_preg_replace($RegEx, $HtmlCodes, $text);
    while ($t != $text) {
        $t = $text;
        $text = nc_preg_replace($RegEx, $HtmlCodes, $text);
    }

    if (isset($codes['LIST']) || !$codes) {
        # поддержка не закрытых кодов списка
        unset($RegEx);
        unset($HtmlCodes);
        $RegEx = array("'\\[list\\]([^\r\n]*)\r?\n?'si");
        $HtmlCodes = array("<div class='nc_bbcode_list'>&bull; \$1</div>");
        $t = $text;
        $text = nc_preg_replace($RegEx, $HtmlCodes, $text);
        while ($t != $text) {
            $t = $text;
            $text = nc_preg_replace($RegEx, $HtmlCodes, $text);
        }
    }

    if (isset($codes['OL']) || !$codes) {
        $text = str_replace(array('[ol]', '[/ol]'), '', $text);
    }
    if (isset($codes['UL']) || !$codes) {
        $text = str_replace(array('[ul]', '[/ul]'), '', $text);
    }
    if (isset($codes['LI']) || !$codes) {
        $text = str_replace(array('[li]', '[/li]'), '', $text);
    }

    if (isset($codes['CUT']) || !$codes) {
        # CUT parsing
        if (!$cut_full) {
            $regex = "|\[cut((=[\"\']?){1}([^\[\]\"\']+)?[\"\']?)?\]((?!.*\[cut([^\[\]]+)?\]).*?)\[/cut\]|is";
            $i = 0;
            while (preg_match($regex, $text, $matches)) {
                $repl = "<a href='$cut_link#nc_cut$i'>" . ($matches[3] ? $matches[3] : NETCAT_BBCODE_CUT_MORE) . "</a>";
                $text = nc_preg_replace($regex, $repl, $text);
                ++$i;
            }
        } else {
            $regex = "|\[cut([^\[\]]+)?\]((?!.*\[cut).*?)|is";
            $i = 0;
            while (preg_match($regex, $text)) {
                $repl = "<a href='#' id='nc_cut$i' class='nc_bbcode_cut_link'></a>\$2";
                $text = nc_preg_replace($regex, $repl, $text);
                ++$i;
            }
        }
        # то что осталось убираем
        $text = nc_preg_replace("|\[cut([^\[\]]+)?\]|i", "", $text);
        $text = nc_preg_replace("|\[/cut\]|i", "", $text);
    }

    return $text;
}

/**
 * Функция очистки текста от BB-кодов (кроме URL)
 *
 * @param string текст
 * @return string;
 */
function nc_bbcode_clear($text) {
    # получаем данные из функции nc_smiles_array()
    list($smiles, $smiles_dir) = nc_smiles_array();

    $i = 0;
    $total_smiles = sizeof($smiles);
    # заменяем смайлы
    while ($i < $total_smiles) {
        # генерация смайлика
        $smile = "";
        $text = str_replace($smiles[$i][0], $smile, $text);
        ++$i;
    }

    $BBcodes = array("b", "i", "u", "s", "ol", "ul", "li", "list", "code", "cut");
    # pasing
    for ($i = 0; $i < sizeof($BBcodes); $i++) {
        $BBregex = "#\[(/?" . $BBcodes[$i] . ")\]#si";
        while (preg_match($BBregex, $text)) {
            $text = nc_preg_replace($BBregex, "", $text);
        }
    }

    # url_accept_chars
    $uac = "-A-Z0-9\+&@#/%\?=~_|\!:,\.;\[\]";

    # RegExp array
    $RegEx = array();
    # replace array
    $HtmlCodes = array();
    # Условия на доступность BB-кодов
    if (isset($codes['QUOTE']) || !$codes) {
        $RegEx[] = "!\[quote=(?:&quot;|')?(.*?)(?:&quot;|')?\](.*?)\[/quote\]!si";
        $RegEx[] = "!\[quote\](.*?)\[/quote\]!si";
        $HtmlCodes[] = "";
        $HtmlCodes[] = "";
    }
    if (isset($codes['COLOR']) || !$codes) {
        $RegEx[] = "!\[color=(?:&quot;|')?([a-f\d]{1,6})(?:&quot;|')?\](.*?)\[/color\]!si";
        $HtmlCodes[] = "\$2";
    }
    if (isset($codes['SIZE']) || !$codes) {
        $RegEx[] = "!\[size=(?:&quot;|')?([\d]{1,2})(?:&quot;|')?\](.*?)\[/size\]!si";
        $HtmlCodes[] = "\$2";
    }
    if (isset($codes['ALIGN']) || !$codes) {
        $RegEx[] = "!\[align=(left|center|right|justify)?\](.*?)\[/align\]!si";
        $HtmlCodes[] = "\$2";
    }
    if (isset($codes['URL']) || !$codes) {
        $RegEx[] = "!\[url\]((?:https?://|www\.|ftp://)[$uac]*?)\[/url\]!si";
        $RegEx[] = "!\[url=(?:&quot;|')?((?:https?://|www\.|ftp://)[$uac]*?)(?:&quot;|')?\](.*?)\[/url\]!si";
        $HtmlCodes[] = "<a href='\$1' class='nc_bbcode_url_1' target='_blank'>\$1</a>";
        $HtmlCodes[] = "<a href='\$1' class='nc_bbcode_url_2' target='_blank'>\$2</a>";
    }
    if (isset($codes['IMG']) || !$codes) {
        $RegEx[] = "!\[img=(?:&quot;|')?((?:https?://|www\.|ftp://)[$uac]*?)(?:&quot;|')?\]!si";
        $RegEx[] = "!\[img\](.*?)\[/img\]!si";
        $HtmlCodes[] = "";
        $HtmlCodes[] = "";
    }

    # обработка
    $t = $text;
    $text = nc_preg_replace($RegEx, $HtmlCodes, $text);
    while ($t != $text) {
        $t = $text;
        $text = nc_preg_replace($RegEx, $HtmlCodes, $text);
    }

    return $text;
}

/**
 * Функция перевода байтов в Kb, Mb, Gb
 *
 * @param int числов байтах;
 * @return string строка;
 */
function nc_bytes2size($byte_size) {

    # byte
    if ($byte_size < 1024)
        $result = ($byte_size ? $byte_size : "0") . NETCAT_SIZE_BYTES;
    # Kb
    if ($byte_size >= 1024 && $byte_size < 1048576)
        $result = round($byte_size / 1024) . NETCAT_SIZE_KBYTES;
    # Mb
    if ($byte_size >= 1048576 && $byte_size < 1073741824)
        $result = round($byte_size / (1024 * 1024), 1) . NETCAT_SIZE_MBYTES;
    # Gb
    if ($byte_size >= 1073741824)
        $result = round($byte_size / (1024 * 1024 * 1024), 3) . NETCAT_SIZE_GBYTES;

    return $result ? $result : 0;
}

/**
 * Функция получения значения визуальных настроек
 *
 * @param int идентификатор компонента в разделе
 * @return array результат
 */
function nc_get_visual_settings($cc) {
    global $db;

    $cc = (int) $cc;
    if (!$cc)
        return false;

    # получаем настройки шаблона в разделе
    $value = $db->get_var("SELECT `CustomSettings` FROM `Sub_Class`
    WHERE `Sub_Class_ID` = '" . $cc . "'");

    if ($value) {
        eval("\$result = $value;");
    }

    return $result;
}

/**
 * Check installed module by keyword
 *
 * @param string module keyword
 * @param bool `Installed` column
 * @return int `Module_ID` or false
 */
function nc_module_check_by_keyword($keyword, $installed = true) {
    global $nc_core;
    $module_data = $nc_core->modules->get_by_keyword($keyword, $installed);
    if ($module_data)
        return $module_data['Module_ID'];

    return false;
}

/**
 * Get data from "from" table and put those data into the "to" table
 *
 * @param string "from" table
 * @param string "to" table
 * @param array equivalence "field_from" => "field_to" array
 * @param bool ignore fields type
 *
 * @return bool true or false
 */
function nc_copy_data($table_from, $table_to, $fieds, $ignore_type = false, $where_str = "") {
    global $db;

    // check startup parameters
    if (empty($fieds))
        return false;

    // validate
    $table_from = $db->escape($table_from);
    $table_to = $db->escape($table_to);

    if ($where_str) {
        nc_preg_replace("/^\s*WHERE\s?/is", "", $where_str);
        $where_str = " WHERE " . $where_str;
    }

    // check tables existance
    if (!$db->get_var("SHOW TABLES LIKE '" . $table_from . "'") || !$db->get_var("SHOW TABLES LIKE '" . $table_to . "'")) {
        return false;
    }

    // get columns from table
    $table_from_columns = $db->get_results("SHOW COLUMNS FROM `" . $table_from . "`", ARRAY_N);
    $table_to_columns = $db->get_results("SHOW COLUMNS FROM `" . $table_to . "`", ARRAY_N);

    // one dimension array with fields names from base
    $table_from_fields_arr = array();
    $table_to_fields_arr = array();
    // one dimension array with fields types from base
    $table_from_types_arr = array();
    $table_to_types_arr = array();
    // from and to fields arrays
    $field_from_arr = array_keys($fieds);
    $field_to_arr = array_values($fieds);

    // build one dimension fields and types array for "from" table
    foreach ($table_from_columns as $value) {
        if (!in_array($value[0], $field_from_arr))
            continue;
        $table_from_fields_arr[] = $value[0];
        if (!$ignore_type)
            $table_from_types_arr[$value[0]] = $db->escape($value[1]);
    }

    // in "to" array may be possible using arrays as value, combine them into the one dimension array
    $field_to_simple_arr = $field_to_arr;
    foreach ($field_to_arr as $value) {
        if (is_array($value)) {
            $field_to_simple_arr = array_merge($field_to_simple_arr, $value);
        }
    }

    // build one dimension fields and types array for "to" table
    foreach ($table_to_columns as $value) {
        if (!in_array($value[0], $field_to_simple_arr))
            continue;
        $table_to_fields_arr[] = $value[0];
        if (!$ignore_type)
            $table_to_types_arr[$value[0]] = $db->escape($value[1]);
    }

    // check pair existance and compare fields type
    foreach ($fieds as $field_from => $field_to) {
        // if value into the "to" array is an array
        if (is_array($field_to)) {
            foreach ($field_to as $field_to_value) {
                // fields existed in tables
                if (!in_array($field_from, $table_from_fields_arr) || !in_array($field_to_value, $table_to_fields_arr)) {
                    return false;
                }
                // check fields type
                if (!$ignore_type && $table_from_types_arr[$field_from] != $table_to_types_arr[$field_to_value])
                    return false;
                $from_query_arr[] = "`" . $field_from . "` AS " . md5($field_to_value);
                $to_query_arr[] = "`" . $field_to_value . "`";
            }
        }
        else {
            // fields existed in tables
            if (!in_array($field_from, $table_from_fields_arr) || !in_array($field_to, $table_to_fields_arr)) {
                return false;
            }
            // check fields type
            if (!$ignore_type && $table_from_types_arr[$field_from] != $table_to_types_arr[$field_to])
                return false;

            $from_query_arr[] = "`" . $field_from . "`";
            $to_query_arr[] = "`" . $field_to . "`";
        }
    }

    // get data to swap
    $data_from = $db->get_results("SELECT " . join(", ", $from_query_arr) . " FROM `" . $table_from . "`" . $where_str, ARRAY_A);

    if (empty($data_from))
        return false;

    $result = array();

    // insert data into table_to
    foreach ($data_from as $data_to_row) {
        $db->query("INSERT INTO `" . $table_to . "` (" . join(", ", $to_query_arr) . ") VALUES ('" . join("', '", $data_to_row) . "')");
        $result[] = $db->insert_id;
    }

    return $result;
}

/**
 *
 * @return string http or https
 */
function nc_get_scheme() {
    $scheme = 'http';
    if (isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] == 'on') {
        $scheme.= 's';
    }
    return $scheme;
}

/*
 * @quantity int число для которого выводим склонение
 * @arForms  array  массив форм слова формата (единственное число, двойственное, множественное), например array('этаж','этажей','этажа') или ('а','','ы') для слова "Квартир".nc_numeral_inclination(20, array('этаж','этажей','этажа') )
 */

function nc_numeral_inclination($quantity, $arForms) {

    $string = strval($quantity);
    $len = strlen($string);

    if ($len > 1) {
        $prev_last_digit = $string[$len - 2];
    } else {
        $prev_last_digit = 0;
    }
    $last_digit = $string[$len - 1];
    if ($last_digit == 1 && $prev_last_digit != 1) {
        return $arForms[0];
    }

    if ($last_digit == 0 || ($prev_last_digit == 1 && $last_digit == 1) || (($prev_last_digit == 1)) || ($last_digit >= 5 && $last_digit <= 9)) {
        return $arForms[1];
    }

    if (($last_digit >= 2 && $last_digit <= 4) && !($quantity >= 11 && $quantity < 20)) {
        return $arForms[2];
    }
}

/**
 * Функция преобразует кавычки в спецсимволы html
 * @param <string> $str
 * @return <string>
 */
function nc_quote_convert($str) {
    return str_replace(array("\"", "'"), array('&quot', '&#039;'), $str);
}

/**
 * Функция обрабатывает строку для присваения js-переменной
 * заменяет "a \r b " н "a" + "\r\" " "b"
 * @param string $str
 * @return string
 */
function nc_text_for_js($str) {
    return str_replace(array("\r\n", "\r", "\n"), '" + "\n" + "', $str);
}

/**
 * Вставка текста в head
 * @param string макет дизайна
 * @param string вставляемый текст
 * @return string результат
 */
function nc_insert_in_head($buffer, $text, $attach_below = false) {
    if (!$text) {
        return $buffer;
    }
    
    //если нужно вставить в конец тега
    if (true == $attach_below) {
        return str_replace('</head>', $text.'</head>', $buffer);
    }
    
    //простой случай
    if (strpos($buffer, '<head>') !== false) {
        return str_replace('<head>', '<head>'.$text, $buffer);
    }

    switch (true) {
        case nc_preg_match("/\<\s*?\/head\s*?\>/im", $buffer):
            $preg_pattern = "/(\<\s*?\/head\s*?\>){1}/im";
            $preg_replacement = $text . "\n\$1";
            break;
        case nc_preg_match("/\<\s*?html\s*?\>/im", $buffer):
            $preg_pattern = "/(\<\s*?html\s*?\>){1}/im";
            $preg_replacement = "\$1\n<head>" . $text . "</head>";
            break;
        default:
            $preg_pattern = "/(\A)/im";
            $preg_replacement = $text . "\n\$1";
    }
    return nc_preg_replace($preg_pattern, $preg_replacement, $buffer);
}


function nc_quickbar_permission() {
    global $perm, $current_sub, $catalogue;

    if (!is_object($perm))
        Authorize();

    if (!is_object($perm))
        return false;

    $allow_id = (array) $perm->GetAllowSub($catalogue, MASK_ADMIN | MASK_MODERATE, false, true, true);
    if (in_array($current_sub['Subdivision_ID'], $allow_id) || $perm->isAnySubClassAdmin())
        return true;
    else
        return false;
}


/**
 * Рекомендуемые скрипты для вставки в макет
 * 
 * @return string html
 */
function nc_js () {
    static $released = false;
    // get super object
    $nc_core = nc_Core::get_object();
    // determine file mode
    if ( $addslashes = (
		$nc_core->template->get_current() ?
		!$nc_core->template->get_current("File_Mode") :
		false
	) ) {
		// get backtrace
		$debug_backtrace = (array) debug_backtrace();
		// search eval
		foreach ($debug_backtrace as $row) {
			if ($row['function'] == 'eval') {
				$addslashes = false;
				break;
			}
		}
	}
    
    $admin_mode = (
		$nc_core->get_variable("admin_mode") ||
		( $nc_core->get_settings("QuickBar") && nc_quickbar_permission() )
	);
    
    // load jQuery and plugins
    $ret_jquery = nc_jquery(true, $admin_mode);
    
    // load CSS
    $ret_css = nc_css();
    
    if ($released) return;

	// system nc variable
	$ret = "<script type='text/javascript'>".
	"if (typeof(nc_token) == 'undefined') {".
	"var nc_token = '" . $nc_core->token->get(+$AUTH_USER_ID) . "';".
	"}".
	"var nc_save_keycode = " . ( $nc_core->get_settings('SaveKeycode') ? $nc_core->get_settings('SaveKeycode') : 83 ) . ";" .
	"</script>".PHP_EOL;
	
	if ( $nc_core->get_settings('JSLoadModulesScripts') ) {
		if ($nc_core->modules->get_by_keyword('auth')) {
			$ret .= "<script type='text/javascript' src='" . $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . "modules/auth/auth.js" . "'></script>".PHP_EOL;
		}

		if ($nc_core->modules->get_by_keyword('minishop')) {
			$ret .= "<script type='text/javascript' src='" . $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . "modules/minishop/minishop.js" . "'></script>".PHP_EOL;
		}
	}

	if ( $nc_core->get_variable("inside_admin") ) {
		$ret .= "<script type='text/javascript' language='Javascript'>".PHP_EOL.
			"var NETCAT_PATH = '" . $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . "',".PHP_EOL.
			"ADMIN_PATH = '" . $nc_core->ADMIN_PATH . "',".PHP_EOL.
			"ICON_PATH = '" . $nc_core->ADMIN_TEMPLATE . "' + 'img/';".PHP_EOL.
			"</script>".PHP_EOL;
	}
	
	if (
		$nc_core->get_variable("admin_mode") ||
		$nc_core->get_settings("QuickBar") && nc_quickbar_permission()
	) {
		$lang = $nc_core->lang->detect_lang(1);
		if ($lang == 'ru') $lang = $nc_core->NC_UNICODE ? "ru_utf8" : "ru_cp1251";
		
		$ret .= "<script type='text/javascript' src='" . $nc_core->ADMIN_PATH . "js/lang/" . $lang . ".js' charset='" . $nc_core->NC_CHARSET . "'></script>".PHP_EOL;
		$ret .= "<script type='text/javascript' src='" . $nc_core->ADMIN_PATH . "js/nc_admin.js'></script>".PHP_EOL;
		$ret .= "<script type='text/javascript' src='" . $nc_core->ADMIN_PATH . "js/lib.js'></script>".PHP_EOL;
		$ret .= "<script type='text/javascript' src='" . $nc_core->ADMIN_PATH . "js/forms.js'></script>".PHP_EOL;
	}
	
	$released = 1;
	
    return $ret_jquery . $ret_css . ($addslashes ? str_replace("\\'", "'", addslashes($ret)) : $ret);
}


/**
 * Стили для вставки в макет
 * @return string html
 */
function nc_css () {
	static $released = 0;
    static $ret = null;
    
    if ($released) return;
    
	// get super object
    $nc_core = nc_Core::get_object();
    // determine file mode
    if ( $addslashes = (
		$nc_core->template->get_current() ?
		!$nc_core->template->get_current("File_Mode") :
		false
	) ) {
		// get backtrace
		$debug_backtrace = (array) debug_backtrace();
		// search eval
		foreach ($debug_backtrace as $row) {
			if ($row['function'] == 'eval') {
				$addslashes = false;
				break;
			}
		}
	}
    
    if ($ret === null) {
		$ret = '';
		if ($nc_core->modules->get_by_keyword('search')) {
            $ret .= "<link type='text/css' rel='stylesheet' href='" . nc_search::get_module_url() . "/suggest/autocomplete.css' />".PHP_EOL;
        }
        
        if (
			$nc_core->get_variable("admin_mode") ||
			( $nc_core->get_settings("QuickBar") && nc_quickbar_permission() )
		):
			if ( $nc_core->get_variable("inside_admin") ) {
				$ret .= "<link type='text/css' rel='Stylesheet' href='" . $nc_core->ADMIN_TEMPLATE . "css/style.css'>".PHP_EOL;
			}
			
			$ret .= "<link type='text/css' rel='Stylesheet' href='" . $nc_core->ADMIN_TEMPLATE . "css/sprites.css'>".PHP_EOL;
			$ret .= "<link rel='stylesheet' rev='stylesheet' type='text/css' href='" . $nc_core->ADMIN_TEMPLATE . "css/nc_admin.css' />".PHP_EOL;
			$ret .= "<link rel='stylesheet' rev='stylesheet' type='text/css' href='" . $nc_core->ADMIN_TEMPLATE . "css/admin_pages.min.css' />".PHP_EOL;
				
		endif;
	} else {
        $ret = '';
    }
    
    $released++;
    
    return ($addslashes ? str_replace("\\'", "'", addslashes($ret)) : $ret);
}


/**
 * This function load jQuery and modules, once
 * 
 * @param boolean addslashes or not
 * @param boolean load jQuery as $nc object or not
 * 
 * @return mixed html text
 */
function nc_jquery ($noconflict = false, $extensions = false) {
    static $released = array();
    static $released_mods = 0;
    
    if ( isset($released[ $noconflict ]) ) return;
    
    // get super object
    $nc_core = nc_Core::get_object();
    // determine file mode
    if ( $addslashes = (
		$nc_core->template->get_current() ?
		!$nc_core->template->get_current("File_Mode") :
		false
	) ) {
		// get backtrace
		$debug_backtrace = (array) debug_backtrace();
		// search eval
		foreach ($debug_backtrace as $row) {
			if ($row['function'] == 'eval') {
				$addslashes = false;
				break;
			}
		}
	}
    
    $http_jquery_folder_path = nc_standardize_path_to_folder($nc_core->SUB_FOLDER . $nc_core->HTTP_TEMPLATE_PATH . "jquery/");

    $jquery_file_array = array();
    $jquery_dir = opendir($nc_core->JQUERY_FOLDER);

	$result = PHP_EOL."<script type='text/javascript' src='" . $http_jquery_folder_path . "jquery.min.js'></script>".PHP_EOL;
	if ($noconflict) {
		$result .= "<script type='text/javascript'>var " . ($addslashes ? '\$nc' : '$nc') . " = jQuery.noConflict();</script>".PHP_EOL;
		if ( $nc_core->get_settings('JSLoadjQueryDollar') ) {
			$result .= "<script type='text/javascript'>if (typeof $ == 'undefined') $ = jQuery;</script>".PHP_EOL;
		}
	}
	
	$released[ $noconflict ] = 1;
	
	if (
		$nc_core->get_variable("admin_mode") ||
		$nc_core->get_settings("JSLoadjQueryExtensionsAlways") ||
		( $nc_core->get_settings("QuickBar") && nc_quickbar_permission() )
	) {
		if ($extensions && !$released_mods) {
			// modules to load
			while ($file = readdir($jquery_dir)) {
				if ($file == '.' || $file == '..' || strpos($file, '.') === 0) {
					continue;
				}
				if ($file == 'jquery.min.js' || $file == '_jquery.min.js') continue;
				$jquery_file_array[] = "<script type='text/javascript' src='" . $http_jquery_folder_path . $file . "'></script>";
			}
			// sort files
			sort($jquery_file_array);
			// released_mods
			$released_mods++;
		}
	}
    
    $ret = $result . join(PHP_EOL, $jquery_file_array) . PHP_EOL;
    
    return ($addslashes ? str_replace(array("\\'", "\\$"), array("'", "\$"), addslashes($ret)) : $ret);
}


function nc_cut_jquery ($template) {
    return preg_replace("#<script.*?jquery.*?((/>)|(>.*?</script>))#mi", "", $template);
}


/**
 * Проверка email'a
 * @param string $email
 * @return bool
 */
function nc_check_email($email) {
    return nc_preg_match("/^[a-z" . NETCAT_RUALPHABET . "0-9\._-]+@[a-z" . NETCAT_RUALPHABET . "0-9\._-]+\.[a-z" . NETCAT_RUALPHABET . "]{2,6}$/i", $email);
}

/**
 * Encodes to punycode
 * @param string $host  ONLY the host name, e.g. "испытание.рф"
 * @return string
 */
function encode_host($host) {
    if (!preg_match("/[^\w\-\.]/", $host)) {
        return $host;
    }
    require_once 'Net/IDNA2.php'; // netcat/require/lib
    $encoder = new Net_IDNA2();
    try {
        $host = $encoder->encode(strtolower($host));
    } catch (Net_IDNA2_Exception $e) {
        trigger_error("Cannot convert host name '$host' to punycode: {$e->getMessage()}", E_USER_WARNING);
        return $host;
    }
    return $host;
}

/**
 * Decodes from punycode
 * @param string $host  ONLY the host name, e.g. "XN----7SBCPNF2DL2EYA.XN--P1AI"
 * @return string
 */
function decode_host($host) {
    if (stripos($host, "xn--") === false) {
        return $host;
    }
    require_once 'Net/IDNA2.php'; // netcat/require/lib
    $decoder = new Net_IDNA2();
    try {
        $host = $decoder->decode(strtolower($host));
    } catch (Net_IDNA2_Exception $e) {
        trigger_error("Cannot convert host name '$host' from punycode: {$e->getMessage()}", E_USER_WARNING);
        return $host;
    }
    return $host;
}

function nc_prepare_message_form($form, $action, $admin_mode, $user_table_mode, $sys_table_id, $current_cc, $f_Checked = null, $f_Priority = '', $f_Keyword = '', $f_ncTitle = '', $f_ncKeywords = '', $f_ncDescription = '', $have_seo = true) {
    $nc_core = nc_Core::get_object();

    if (!CheckUserRights($current_cc['Sub_Class_ID'], "moderate", 0) || !$admin_mode) {
        return $form;
    }

    if (null === $f_Checked && 1 == $current_cc['Moderation_ID']) {
        $f_Checked = 1;
    }

    $seo = "<div class='nc_seo_fields'>";

    if ('change' == $action && !$user_table_mode) {
        global $message;

        $SQL = "SELECT `uAdd`.`{$nc_core->AUTHORIZE_BY}` as `user_add`,
                        `uEdit`.`{$nc_core->AUTHORIZE_BY}` as `user_edit`,
                        a.`IP`,
                        a.`LastIP`,
                        UNIX_TIMESTAMP(a.`Created`) as `Created`,
                        UNIX_TIMESTAMP(a.`LastUpdated`) as `LastUpdated`
                    FROM `Message{$current_cc['Class_ID']}` AS `a`
                      LEFT JOIN `User` as `uAdd` ON `uAdd`.`User_ID` = `a`.`User_ID`
                      LEFT JOIN `User` as `uEdit` ON `uEdit`.`User_ID` = `a`.`LastUser_ID`
                        WHERE `Message_ID` = " . +$message;
        $info = $nc_core->db->get_row($SQL, ARRAY_A);

        $seo .= "<div class='nc_admin_settings_info nc_seo_edit_info'>
					<div class='nc_admin_settings_info_actions'>
                     <div>
                         <span>" . CLASS_TAB_CUSTOM_ADD . ":</span> " . date("d.m.Y H:i:s", $info['Created']) . " {$info['user_add']} ({$info['IP']})
                     </div>";

        if ($info['user_edit']) {
            $seo .= "<div>
						 <span>" . CLASS_TAB_CUSTOM_EDIT . ":</span> " . date('d.m.Y H:i:s', $info['LastUpdated']) . " {$info['user_edit']} ({$info['LastIP']})
					 </div>";
        }
        $seo .= '</div>';
    }

	//<input id='chk' name='f_Checked' type='checkbox' value='".($f_Checked ? "1" : "0")."' " . ($f_Checked ? "checked='checked'" : "") . " />
	// Новости
	if (($current_cc['Sub_Class_ID']==170) || ($current_cc['Sub_Class_ID']==194) || ($current_cc['Sub_Class_ID']==16) || ($current_cc['Sub_Class_ID']==9)) {
	$seo .= "	 
				<div class='nc_admin_settings_info_checked'>
					 <input id='chk' name='f_Checked' type='checkbox' value='".($f_Checked ? "1" : "0")."'  " . ($f_Checked ? "checked" : "") . "  />
					 <label for='chk'>" . NETCAT_MODERATION_TURNON . "</label>
				 </div>
                 

                 <div class='nc_admin_settings_info_priority'>
                     <div>
                        " . CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_PRIORITY . ":
                     </div>

                     <div>
                         <input name='f_Priority' type='text' size='3' maxlength='10' value='" . ($f_Priority ? +$f_Priority : '') . "' />
                     </div>
                 </div>
			 </div>";
	} else {
		$seo .= "</div>";
	}

    if (($current_cc['File_Mode']
            && is_object($class_view = nc_class_view::get_instanse())
            && filesize($class_view->get_field_path('RecordTemplateFull'))) || !$current_cc['File_Mode']) {

        $seo .= "
                 <div>
                     <div>
                         " . CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_KEYWORD . ":
                     </div>

                     <div>
                         <input name='f_Keyword' type='text' size='20' maxlength='255' value='" . htmlspecialchars_decode($f_Keyword, ENT_QUOTES) . "'>
                     </div>
                 </div>

                 <div>
                     <div>
                         " . NETCAT_MODERATION_SEO_TITLE . ":
                     </div>

                     <div>
                         <input type='text' name='f_ncTitle' value='" . htmlspecialchars_decode($f_ncTitle, ENT_QUOTES) . "' />
                     </div>
                 </div>

                 <div>
                     <div>
                         " . NETCAT_MODERATION_SEO_KEYWORDS . ":
                     </div>
                        <textarea name='f_ncKeywords'>" . htmlspecialchars_decode($f_ncKeywords, ENT_QUOTES) . "</textarea>
                     <div>

                     </div>
                 </div>

                 <div>
                     <div>
                         " . NETCAT_MODERATION_SEO_DESCRIPTION . ":
                     </div>

                     <div>
                         <textarea name='f_ncDescription'>" . htmlspecialchars_decode($f_ncDescription, ENT_QUOTES) . "</textarea>
                     </div>
                 </div>";
    }
    $seo .= "</div>";

    return "<div class='nc_admin_form_menu' style='padding-top: 20px;'>
                <h2>".htmlspecialchars_decode($current_cc['Sub_Class_Name'])."</h2>

                <div id='nc_object_slider_menu' class='slider_block_2' style='padding-top: 0px; padding-bottom: 15px;'>
                    <ul>
                        " . ($have_seo ? "
                        <li class='button_on' id='nc_show_main' onClick='return false;'>".NETCAT_MESSAGE_FORM_MAIN."</li>
                        <li id='nc_show_seo' onClick='return false;'>".NETCAT_MESSAGE_FORM_ADDITIONAL."</li>
                        " : "<li />") . "
                    </ul>
                </div>

                <div class='nc_admin_form_menu_hr'></div>
            </div>
            <div id='nc_seo_append'><div class='nc_admin_form_seo' style='display: none;'>$seo</div></div>
            <div class='nc_admin_form_body'>$form</div>" . (!$sys_table_id ? "
            <div class='nc_admin_form_buttons'>
                <input class='nc_admin_metro_button' type='button' value='" . NETCAT_REMIND_SAVE_SAVE . "' disable />
                <input class='nc_admin_metro_button_cancel' style='color: black; margin-right: 16px; background-color: #EEEEEE; border: 1px solid red;' type='button' value='".CONTROL_BUTTON_CANCEL."' />
            </div>
			
			<style>
                a {color:#1a87c2;}
                a:hover {text-decoration:none;}
                a img {border:none;}
                p {margin:0px; padding:0px 0px 18px 0px;}
                h2 {font-size:20px; font-family:'Segoe UI', SegoeWP, Arial; color:#333333; font-weight:normal; margin:0px; padding:20px 0px 10px 0px; line-height:20px;}
                form {margin:0px; padding:0px;}
                input {outline:none;}
                .clear {margin:0px; padding:0px; font-size:0px; line-height:0px; height:1px; clear:both; float:none;}
                select, input, textarea {border:1px solid #dddddd;}
                :focus {outline:none;}
                .input {outline:none; border:1px solid #dddddd;}
            </style>
			
            <script type='text/javascript'>prepare_message_form();</script>" : "");
}

function nc_field_validation($tag, $name, $id, $type, $not_null, $format = null) {
    $v_not_null = $not_null ? "console.log(val); if(val.length == 0) {response = false;}" : "";
    $v_type = '';
    $v_format = '';

    switch($type) {
        case 'date':
        case 'float':
        case 'int':
            $v_type = "if((val+0) != val) {response = false;}".$v_type;
            break;
    }

    $regular = '';
    switch($format) {
        case 'email':
            $regular = "[\w]+@[\w]+\.[\w]+";
            break;
        case 'url':
            $regular = "(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?";
            break;
    }
    $v_format = $regular ? "if(!(/^".$regular."$/i.test(val))) {response = false;}" : '';

    if(!$v_not_null && !$v_type && !$v_format) {
        return '';
    }

    $znak = $type = 'date' ? '^=' : '=';

    return "<script>\$nc('".$tag."[name".$znak."\"".$name."\"]').live('change', function () {
        var val = \$nc(this).val(), name = \$nc(this).attr('name'), form = \$nc(this).closest('form'), response = true;
        ".$v_not_null.$v_type.$v_format."
        if(response) {
            if(window.nc_object_edit_errors != null) {
                \$nc('#nc_capfld_".$id."').css('color', '');
                if(window.nc_object_edit_errors['f".$id."'] != null && window.nc_object_edit_errors['f".$id."'] != 0) {
                    --window.nc_object_edit_errors['f".$id."'];
                }
                var count = 0;
                for(k in window.nc_object_edit_errors) {
                    if(window.nc_object_edit_errors[k] != 0) {
                        count++;
                    }
                }
                if(count == 0) {
                    \$nc('#nc_form_result div.nc_admin_form_buttons input').removeClass('nc_disable');
                }
            }
        }
        else {
            \$nc('#nc_capfld_".$id."').css('color', 'red');
            if(window.nc_object_edit_errors == null) {
                window.nc_object_edit_errors = {};
            }
            if(window.nc_object_edit_errors['f".$id."'] == null) {
                window.nc_object_edit_errors['f".$id."'] = 0;
            }
            ++window.nc_object_edit_errors['f".$id."'];
            \$nc('#nc_form_result div.nc_admin_form_buttons input').addClass('nc_disable');
        }
    });</script>";
}

function nc_field_caption($field, $caption_style = null,$field_name="") {
    return "<label for='f_".$field_name."' style='$caption_style' id='nc_capfld_".$field['id']."'>".$field['description'] . ( $field['not_null'] ? " (*)" : "") . ":</label>\r\n";
}

function nc_multiple_changes_string($field_name, $message, $value, $style = "", $classID = "", $caption = false) {
    return nc_replace_name_for_multiple_changes(nc_string_field($field_name, $style, $classID, $caption, $value, $valid), $field_name, $message);
}

function nc_multiple_changes_int($field_name, $message, $value, $style = "", $classID = "", $caption = false) {
    return nc_replace_name_for_multiple_changes(nc_int_field($field_name, $style, $classID, $caption, $value), $field_name, $message);
}

function nc_multiple_changes_text($field_name, $message, $value, $style = "", $classID = "", $caption = false, $bbcode = false) {
    return nc_replace_name_for_multiple_changes(nc_text_field($field_name, $style, $classID, $caption, $bbcode, $value), $field_name, $message);
}

function nc_multiple_changes_list($field_name, $message, $value = false, $style = "", $classID = "", $caption = false, $selected = false, $disabled = false, $unused = null, $ignore_check = false, $type = null) {
    return nc_replace_name_for_multiple_changes(nc_list_field($field_name, $style, $classID, $caption, $value, $disabled, $unused, $ignore_check, $type), $field_name, $message);
}

function nc_multiple_changes_bool($field_name, $message, $value, $style = "", $classID = "", $caption = false) {
    return nc_replace_name_for_multiple_changes(nc_bool_field($field_name, $style, $classID, $caption, $value), $field_name, $message);
}

function nc_multiple_changes_float($field_name, $message, $value, $style = "", $classID = "", $caption = false) {
    return nc_replace_name_for_multiple_changes(nc_float_field($field_name, $style, $classID, $caption, $value), $field_name, $message);
}

/*
function nc_multiple_changes_file($field_name, $message, $value, $style = "", $classID = "", $caption = false, $getData = false) {
    return nc_replace_name_for_multiple_changes(nc_file_field($field_name, $style, $classID, $caption, $getData), $field_name, $message);
}

function nc_multiple_changes_date($field_name, $message, $style = "", $classID = "", $caption = false, $dateDiv = "-", $timeDiv = ":", $select = false, $use_calendar = null, $calendar_theme = 0, $calendar_template = "") {
    return nc_replace_name_for_multiple_changes(nc_date_field($field_name, $style, $classID, $caption, $dateDiv, $timeDiv, $select, $use_calendar, $calendar_theme, $calendar_template), $field_name, $message);
}

function nc_multiple_changes_related($field_name, $message, $change_template = "", $remove_template = "") {
    return nc_replace_name_for_multiple_changes(nc_related_field($field_name, $change_template, $remove_template), $field_name, $message);
}

function nc_multiple_changes_multilist($field_name, $message, $style = "", $type = "", $classID = "", $caption = false, $selected = false, $disabled = false, $getData = false, $ignore_check = false) {
    return nc_replace_name_for_multiple_changes(nc_multilist_field($field_name, $style, $type, $classID, $caption, $selected, $disabled, $getData, $ignore_check), $field_name, $message);
}
*/

function nc_replace_name_for_multiple_changes($input, $name, $message) {
    return preg_replace("/name='.*?'/", "name='nc_multiple_changes[$message][$name]'", $input);
}


function nc_multiple_changes_prefix() {
    global $sub, $cc, $catalogue, $curPos;
    return "
        <form name='adminForm' id='adminForm' enctype='multipart/form-data' method='post' action='" .  nc_Core::get_object()->SUB_FOLDER . nc_Core::get_object()->HTTP_ROOT_PATH . "message.php'>
            <input name='catalogue' type='hidden' value='$catalogue' />
            <input name='cc' type='hidden' value='$cc' />
            <input name='sub' type='hidden' value='$sub' />
            <input name='curPos' type='hidden' value='$curPos' />
            <input name='posting' type='hidden' value='1' />
            <input name='multiple_changes' type='hidden' value='1' />
            <input name='message' type='hidden' value='1' />";
}


function nc_multiple_changes_suffix() {
    return "
        </form>";
}


function plural_form($quantity_items, $one, $two, $many) {
    $quantity_items = abs($quantity_items) % 100;
    $under_hundred = $quantity_items % 10;

    $form_result = $many;

    if ($under_hundred > 1 && $under_hundred < 5) {
      $form_result = $two;
    }

    if ($under_hundred == 1) {
      $form_result = $one;
    }

    if($quantity_items > 10 && $quantity_items < 20) {
      $form_result = $many;
    }
    return $form_result;
}

function nc_get_http_folder($root_folder) {
    return nc_standardize_path_to_folder('/' . str_replace($_SERVER['DOCUMENT_ROOT'], '', $root_folder));
}


/**
 * Check PHP file
 * 
 * @param string file path
 * 
 * @return boolean checking result
 */
function nc_check_php_file ($file) {	
	// get file data
	@$code = file_get_contents($file);
	
	// file existance
	if ($code === false) {
		throw new Exception('File '.$file.' does not exist');
	}
	
	// tokenizer not installed
	if ( !function_exists('token_get_all') ) return true;
	
    $braces = 0;
    $inString = 0;
    foreach ( token_get_all($code) as $token ) {
        if ( is_array($token) ) {
            switch ($token[0]) {
				case T_CURLY_OPEN:
				case T_DOLLAR_OPEN_CURLY_BRACES:
				case T_START_HEREDOC: ++$inString; break;
				case T_END_HEREDOC:   --$inString; break;
            }
        }
        else if ($inString & 1) {
            switch ($token) {
				case '`':
				case '"': --$inString; break;
            }
        }
        else {
            switch ($token) {
				case '`':
				case '"': ++$inString; break;

				case '{': ++$braces; break;
				case '}':
					if ($inString) {
						--$inString;
					}
					else {
						--$braces;
						if ($braces < 0) {
							throw new Exception('Braces problem!');
						}
					}
                break;
            }
        }
    }

    if ($braces) {
    	throw new Exception('Braces problem!');
    }
	
    $res = false;
    
    ob_start();
	$res = eval('if (0) {?>'.$code.'<?php }; return true;');
	$error_text = ob_get_clean();
	
	// it's not really good idea but...
	// always close your php-code!
	if (!$res) {
		ob_start();
		$res = eval('if (0) {?>'.$code.' ?><?php }; return true;');
		$error_text = ob_get_clean();
	}
	
	// prevent eval() 500 error when display_errors = off;
	header('HTTP/1.0 200 OK');
	
	if (!$res) {
		throw new Exception($error_text);
	}
	
	return true;
}

?>
