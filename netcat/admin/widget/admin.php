<?php

/* $Id: admin.php 7302 2012-06-25 21:12:35Z alive $ */

// get settings
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");
require_once($ADMIN_FOLDER."widget/function.inc.php"); /**/


if (!isset($phase) || !$phase) $phase = 10;
switch ($phase) {
    case 10: // list
        $UI_CONFIG = new ui_config_widgetes();
        $UI_CONFIG->treeMode = 'modules';
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");

        nc_naked_action_header();
        nc_widget_list();
        nc_naked_action_footer();

        break;

    case 20: // add
        $UI_CONFIG = new ui_config_widget('add');
        $UI_CONFIG->treeMode = 'modules';
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");

        if (+$_REQUEST['isNaked']) {
            ob_clean();
            nc_widget_add_form_modal('', intval($widget_id));
            exit;
        }

        nc_widget_add_form('', intval($widget_id));
        break;

    case 21:
        $UI_CONFIG = new ui_config_widget('add');
        $UI_CONFIG->treeMode = 'modules';
        $post = $nc_core->input->fetch_get_post();
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
        if (!$post['Name']) {
            nc_print_status(WIDGET_ADD_ERROR_NAME, 'error');
            nc_widget_add_form($post);
            exit;
        }
        if (!$post['Keyword']) {
            nc_print_status(WIDGET_ADD_ERROR_KEYWORD, 'error');
            nc_widget_add_form($post);
            exit;
        }

        // проверка символов для ключевого слова
        if (!$nc_core->widget->validate_keyword($post['Keyword'])) {
            nc_print_status(CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_KEYWORD_INVALID, 'error');
            nc_widget_add_form($post);
            exit;
        }

        if (is_exist_keyword($post['Keyword'], 0, $widget_id)) {
            nc_print_status(WIDGET_ADD_ERROR_KEYWORD_EXIST, 'error');
            nc_widget_add_form($post);
            exit;
        }

        $res = $db->get_results("SELECT `Field_Name`, `Field_ID` FROM `Field` WHERE `Widget_Class_ID`='".$post['Widget_Class_ID']."'");
        if ($res)
                foreach ($res as $res_row) {
                $fieldIDs[$res_row->Field_Name] = $res_row->Field_ID;
            }
        foreach ($post as $key => $val) {
            if (nc_substr($key, 0, 5) == "field") {
                $fields[intval(nc_substr($key, 5))] = $val;
            } elseif (nc_substr($key, 0, 2) == "f_") {
                $fieldId = $fieldIDs[nc_substr($key, 2)];
                if ($fieldId) $fields[$fieldId] = $val;
            }
        }
        $add_id = nc_widget_add($post, $fields);
        $UI_CONFIG = new ui_config_widgetes();
        nc_print_status(WIDGET_ADD_OK, 'ok');

        nc_naked_action_header();
        nc_widget_list();
        nc_naked_action_footer();
        break;

    case 30: // edit
        $UI_CONFIG = new ui_config_widget('edit', $widget_id);
        $UI_CONFIG->treeMode = 'modules';
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");

        if (+$_REQUEST['isNaked']) {
            ob_clean();
            nc_widget_edit_form_modal('', intval($widget_id));
            exit;
        }

        nc_widget_edit_form('', $widget_id);
        break;

    case 31:
        $UI_CONFIG = new ui_config_widget('edit', $widget_id);
        $UI_CONFIG->treeMode = 'modules';
        $params = array('Name' => $Name, 'Keyword' => $Keyword, 'Widget_Class_ID' => $Widget_Class_ID);
        $post = $nc_core->input->fetch_get_post();
        $widget_id = $post['widget_id'];

        $res = $db->get_results("SELECT `Field_Name`, `Field_ID`, `NotNull`, `Description`, `Format`, `TypeOfData_ID` FROM `Field` WHERE `Widget_Class_ID`='".$post['Widget_Class_ID']."'");
        if ($res)
                foreach ($res as $res_row) {
                $fieldIDs[$res_row->Field_Name] = $res_row->Field_ID;
                $fieldNotNull[$res_row->Field_ID] = $res_row->NotNull;
                $fieldDescriptions[$res_row->Field_ID] = $res_row->Description;
                $fieldTypes[$res_row->Field_ID] = $res_row->TypeOfData_ID;
                $fieldFormats[$res_row->Field_ID] = $res_row->Format;
            }

        foreach ($post as $key => $val) {
            if (nc_substr($key, 0, 5) == "field") {
                $fields[intval(nc_substr($key, 5))] = $val;
            } elseif (nc_substr($key, 0, 2) == "f_") {
                $fieldId = $fieldIDs[nc_substr($key, 2)];
                if ($fieldId) $fields[$fieldId] = $val;
            }
        }
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");

        // проверка символов для ключевого слова
        if (!$nc_core->widget->validate_keyword($post['Keyword'])) {
            nc_print_status(CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_KEYWORD_INVALID, 'error');
            nc_widget_edit_form($post, $widget_id);
            exit;
        }

        if (is_exist_keyword($post['Keyword'], 0, $widget_id)) {
            nc_print_status(WIDGET_ADD_ERROR_KEYWORD_EXIST, 'error');
            nc_widget_edit_form($post, $widget_id);
            exit;
        }

        // проверка полей виджета
        foreach ($fields as $f_Id => $f_val) {
            if (($f_val == '') && ($fieldNotNull[$f_Id] == '1')) {
                nc_print_status(str_replace("%NAME", $fieldDescriptions[$f_Id], NETCAT_MODERATION_MSG_ONE), 'error');
                nc_widget_edit_form($post, $widget_id);
                exit;
            }
            if ($f_val != '') {
                switch ($fieldTypes[$f_Id]) {
                    case NC_FIELDTYPE_STRING:
                        if ($fieldFormats[$f_Id] == "email" && $f_val && !nc_preg_match("/^[a-z0-9\._-]+@[a-z0-9\._-]+\.[a-z]{2,6}$/i", $f_val))
                                $type_err = 1;
                        if ($fieldFormats[$f_Id] == "phone" && $f_val && !nc_preg_match("/^ (\+?\d-?)?  (((\(\d{3}\))|(\d{3})?)-?)?  \d{3}-?\d{2}-?\d{2} $/x", str_replace(array(" ", " \t"), '', $f_val)))
                                $type_err = 1;
                        if ($fieldFormats[$f_Id] == "url" && ($f_val == 'http://' || $f_val == 'ftp://') && ($fieldNotNull[$f_Id] == '0'))
                                $f_val = "";
                        if ($fieldFormats[$f_Id] == "url" && $f_val && !isURL($f_val))
                                $type_err = 1;
                        break;
                    case NC_FIELDTYPE_INT:
                        if ($f_val != "" && $f_val != strval(intval($f_val)))
                                $type_err = 1;
                        break;
                    case NC_FIELDTYPE_FLOAT:
                        if ($f_val != "" && !preg_match("/^\-?[0-9]+(\.[0-9]+)?$/is", str_replace(",", ".", $f_val)))
                                $type_err = 1;
                        if (preg_match("/,/is", $f_val))
                                $f_val = str_replace(",", ".", $f_val);
                        break;
                }
                if ($type_err) {
                    nc_print_status(str_replace("%NAME", $fieldDescriptions[$f_Id], NETCAT_MODERATION_MSG_TWO), 'error');
                    nc_widget_edit_form($post, $widget_id);
                    exit;
                }
            }
        }

        nc_widget_edit($widget_id, $params, $fields);
        nc_print_status(WIDGET_EDIT_OK, 'ok');
        nc_widget_edit_form($post, $widget_id);
        break;

    case 60:  // delete
        BeginHtml($Title6, $Title3.$Delimeter.$Title6, "http://".$DOC_DOMAIN."/management/class/");
        $UI_CONFIG = new ui_config_widget('delete', $widget_id);
        $UI_CONFIG->treeMode = 'modules';
        nc_widget_delete_warning($widget_id);
        break;

    case 61:
        $UI_CONFIG = new ui_config_widgetes();
        $UI_CONFIG->treeMode = 'modules';
        BeginHtml($Title6, $Title3.$Delimeter.$Title6, "http://".$DOC_DOMAIN."/management/class/");
        nc_widget_delete($widget_id);

        nc_naked_action_header();
        nc_widget_list();
        nc_naked_action_footer();
        break;
}

EndHtml();