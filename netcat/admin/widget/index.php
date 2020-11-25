<?php

/* $Id: index.php 8381 2012-11-08 14:52:09Z lemonade $ */
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require_once ($INCLUDE_FOLDER."s_common.inc.php");
require ($ADMIN_FOLDER."function.inc.php");
require ($ADMIN_FOLDER."widget/function.inc.php");

$Delimeter = " &gt ";
$item_id = 9;
$Title2 = TOOLS_WIDGET;

if (!isset($phase)) $phase = 10;
switch ($phase) {

    case 10: // list
        $UI_CONFIG = ($category ? new ui_config_widgetclasses('widgetgroup', $category) : new ui_config_widgetclasses());
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
        nc_widgetclass_list($category);
        break;

    case 20: // add
        $UI_CONFIG = new ui_config_widgetclass('add');
        BeginHtml($Title4, $Title3.$Delimeter.$Title4, "http://".$DOC_DOMAIN."/management/class/");
        nc_widgetclass_add_template($widget_group);
        break;

    case 21:
        $UI_CONFIG = new ui_config_widgetclass('add', $widgetclass_id);
        BeginHtml($Title4, $Title3.$Delimeter.$Title4, "http://".$DOC_DOMAIN."/management/class/");
        nc_widgetclass_add_form('', $widgetclass_id, $widget_group);
        break;

    case 22:
        $UI_CONFIG = new ui_config_widgetclass('add');
        $post = $nc_core->input->fetch_get_post();
        $category = $post['Category_New'] ? $post['Category_New'] : $post['Category'];
        $params = array('Name' => $post['Name'], 'Keyword' => $post['Keyword'], 'Category' => $category, 'Description' => $post['Description'], 'Template' => $post['Template'], 'InDevelop' => $post['InDevelop'], 'WidgetDisallow' => $post['WidgetDisallow'], 'Settings' => $post['Settings'], 'Update' => $post['Update']);

        BeginHtml($Title4, $Title3.$Delimeter.$Title4, "http://".$DOC_DOMAIN."/management/class/");
        if (!$post['Name']) {
            nc_print_status(WIDGET_ADD_ERROR_NAME, 'error');
            nc_widgetclass_add_form($params, '', '', $base_widgetclass_id);
            exit;
        }
        if (!$post['Keyword']) {
            nc_print_status(WIDGET_ADD_ERROR_KEYWORD, 'error');
            nc_widgetclass_add_form($params, '', '', $base_widgetclass_id);
            exit;
        }

        // проверка символов для ключевого слова
        if (!$nc_core->subdivision->validate_hidden_url($post['Keyword'])) {
            nc_print_status(CONTROL_CONTENT_SUBDIVISION_SUBCLASS_ERROR_KEYWORD_INVALID, 'error');
            nc_widgetclass_add_form($params, '', '', $base_widgetclass_id);
            exit;
        }

        if (is_exist_keyword($post['Keyword'], 1)) {
            nc_print_status(WIDGET_ADD_ERROR_KEYWORD_EXIST, 'error');
            nc_widgetclass_add_form($params, '', '', $base_widgetclass_id);
            exit;
        }
        $add_id = nc_widgetclass_add($params, $base_widgetclass_id);
        $UI_CONFIG = new ui_config_widgetclass('edit', $add_id);
        $isNewCategory = $db->get_var("SELECT COUNT(Category) FROM Widget_Class WHERE Category = '".$category."'");
        if ($isNewCategory == 1) {
            $UI_CONFIG->treeChanges['addNode'][] = array(
                    "parentNodeId" => "widgetclass.list",
                    "nodeId" => "widgetgroup-".md5($category),
                    "name" => $category,
                    "href" => "#widgetgroup.edit(".md5($category).")",
                    "image" => 'i_widgetclassgroup.gif',
                    "buttons" => $classgroup_buttons,
                    "hasChildren" => 1,
                    "dragEnabled" => false
            );
        }

        nc_widgetclass_edit_form($params, $add_id, $post['phase']);
        break;

    case 30: // edit
        $UI_CONFIG = new ui_config_widgetclass('edit', $widgetclass_id);
        $AJAX_SAVER = true;
        if ($perm->isGuest()) $AJAX_SAVER = false;
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
        nc_widgetclass_edit_form('', $widgetclass_id, $phase);
        break;

    case 31:
        $UI_CONFIG = new ui_config_widgetclass('edit', $widgetclass_id);
        $post = $nc_core->input->fetch_get_post();
        $params = array('Name' => $post['Name'], 'Keyword' => $post['Keyword'], 'Category' => ($post['Category_New'] ? $post['Category_New'] : $post['Category']), 'Description' => $post['Description'], 'Template' => $post['Template'], 'InDevelop' => $post['InDevelop'], 'WidgetDisallow' => $post['WidgetDisallow'], 'Settings' => $post['Settings'], 'Update' => $post['Update']);
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
        $before_edit = $nc_core->widget->get_widgetclass(intval($widgetclass_id));        

        if (is_exist_keyword($post['Keyword'], $post['widgetclass_id'])) {
            nc_print_status(WIDGET_ADD_ERROR_KEYWORD_EXIST, 'error');
            nc_widgetclass_edit_form($params, $post['widgetclass_id'], $post['phase']);
            exit;
        }
        nc_widgetclass_edit($post['widgetclass_id'], $params);

        if (+$_REQUEST['isNaked']) {
            ob_clean();
            echo 'OK';
            exit;
        }

        nc_print_status(WIDGET_EDIT_OK, 'ok');
        if ($before_edit['Name'] != $params['Name']) {
            $UI_CONFIG->headerText = $params['Name'];
            $UI_CONFIG->treeChanges['updateNode'][] = array(
                "nodeId" => "widgetclass-".$widgetclass_id,
                "name" => $widgetclass_id.'. '.$params['Name']
            );
        }

        nc_widgetclass_edit_form($params, $post['widgetclass_id'], $post['phase']);
        break;

    case 40: // info
        $UI_CONFIG = new ui_config_widgetclass('info', $widgetclass_id);
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
        nc_widgetclass_info($widgetclass_id);
        break;

    case 50: // action
        $UI_CONFIG = new ui_config_widgetclass('action', $widgetclass_id);
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
        nc_widgetclass_action_form('', $widgetclass_id);
        break;

    case 51:
        $UI_CONFIG = new ui_config_widgetclass('action', $widgetclass_id);
        $post = $nc_core->input->fetch_get_post();
        $params = array('AddForm' => $post['AddForm'], 'EditForm' => $post['EditForm']);
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
        nc_widgetclass_action($widgetclass_id, $params);
        nc_widgetclass_action_form($params, $widgetclass_id);
        break;

    case 60:  // delete
        BeginHtml($Title6, $Title3.$Delimeter.$Title6, "http://".$DOC_DOMAIN."/management/class/");
        $post = $nc_core->input->fetch_get_post();
        if (!$from_tree) {
            foreach ($post as $key => $val) {
                if (nc_substr($key, 0, 6) == "Delete" && $val) {
                    $delete[] = intval($val);
                }
            }

            if (!isset($delete)) {
                $UI_CONFIG = new ui_config_widgetclasses();
                nc_print_status(WIDGET_LIST_ERROR_DELETE, 'error');
                nc_widgetclass_list($category);
                exit;
            }
        } else {
            $delete = $widgetclass_id;
        }
        $UI_CONFIG = new ui_config_widgetclass('delete', $widgetclass_id);
        nc_widgetclass_delete_warning($delete, $from_tree);
        break;

    case 61:
        $UI_CONFIG = new ui_config_widgetclasses();
        BeginHtml($Title6, $Title3.$Delimeter.$Title6, "http://".$DOC_DOMAIN."/management/class/");
        nc_widgetclass_delete($delete, $from_tree);
        $delete = explode(',', $delete);
        foreach ((array) $delete as $d) {
            $UI_CONFIG->treeChanges['deleteNode'][] = "widgetclass-".$d;
        }
        nc_widgetclass_list($category);
        break;

    case 80: // import
        $UI_CONFIG = new ui_config_widgetclass('import');
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
        nc_widgetclass_import_form();
        break;

    case 81:
        $UI_CONFIG = new ui_config_widgetclass('import');
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
        if ($_FILES['import']['error'] != 0) {
            nc_print_status(WIDGET_IMPORT_ERROR, 'error');
            nc_widgetclass_import_form();
        } else {
            nc_print_status(WIDGET_IMPORT_OK, 'ok');
            $id = nc_widgetclass_import($_FILES['import']);
            $fs = +$_REQUEST['fs'];
            echo "<a href='index.php?phase=30&amp;widgetclass_id=".$id."&fs=".$fs."'>".WIDGET_TAB_EDIT."</a>";
        }
        break;

    case 90: // результаты ajax
        $perm->ExitIfNotAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_RIGHT, -1, 0, 0);

        $post = $nc_core->input->fetch_get_post();
        $widgetclass_id = +$post['Widget_Class_ID'];
        $widget_id = intval($post['Widget_ID']);
        $action = $_POST['action'];
        $res = '';

        $SQL = "SELECT `Field_ID`,
                       `Field_Name`,
                       `Description`,
                       `TypeOfData_ID`,
                       `Format`,
                       `NotNull`,
                       `DefaultState`
                    FROM `Field`
                        WHERE `Widget_Class_ID` = '".$widgetclass_id."'
                            ORDER BY `Priority`";
        $result = $db->get_results($SQL, ARRAY_A);

        if ($result) {
            foreach ($result as $r) {
                $fields[] = $r['Field_ID'];
                $fieldIDs[$r['Field_ID']] = $r['Field_Name'];
            }
            $widget_fields = $db->get_results("SELECT `Field_ID`, `Value`
                                         FROM `Widget_Field`
                                         WHERE `Field_ID` IN (".implode(',', $fields).") AND `Widget_ID`= '".$widget_id."'
                                         ORDER BY `Field_ID`", ARRAY_A);
            $SQL = "SELECT `AddForm`,
                           `EditForm`,
                           `File_Mode`
                        FROM `Widget_Class`
                            WHERE `Widget_Class_ID` = " . $widgetclass_id;

            $widget_result = $db->get_row($SQL);
            $alter_add_form = $widget_result->AddForm;
            $alter_edit_form = $widget_result->EditForm;
            $File_Mode = $widget_result->File_Mode;

            if ($File_Mode) {
                $widget_view = new nc_widget_view($nc_core->WIDGET_TEMPLATE_FOLDER, $db);
                $widget_view->load($widgetclass_id);
                $alter_add_form = file_get_contents($widget_view->get_field_path('AddForm'));
                $alter_edit_form = file_get_contents($widget_view->get_field_path('EditForm'));
            }

            foreach ((array) $widget_fields as $wf) {
                $widget_fields_array[] = array($wf['Field_ID'] => $wf['Value']);
            }

            if (($post["show_old"] == '1') && ($post['old_widget_class_id'] == $widgetclass_id)) {
                if ($alter_edit_form) {
                    foreach ($post as $post_name => $post_val) {
                        if (($post_name != 'Widget_ID') && ($post_name != 'Widget_Class_ID'))
                                ${$post_name} = $post_val;
                    }
                } else {
                    $i = 0;
                    foreach ((array) $result as $r) {
                        $description = "<font color='gray'>".($r['Description'] != '' ? $r['Description'] : $r['Field_Name']).($r['NotNull'] == '1' ? " (*)" : "").":</font><br />\n";
                        if ($r['TypeOfData_ID'] == 3) {
                            $res .= $description;
                            $res .= nc_admin_textarea_simple("f_".$fieldIDs[$r['Field_ID']], $post["f_".$fieldIDs[$r['Field_ID']]], '', 0, 0, '', 'soft')."<br /><br />\n";
                        } else {
                            $res .= $description;
                            $res .= "<input name='f_".$fieldIDs[$r['Field_ID']]."' size='50' value='".$post["f_".$fieldIDs[$r['Field_ID']]]."' /><br /><br />\n";
                        }
                        $i++;
                    }
                }

                if ($File_Mode) {
                    ob_start();
                    include $widget_view->get_field_path('AddForm');
                    $res .= ob_get_clean();
                } else {
                    eval("\$res .= \"".$alter_edit_form."\";");
                }

            } else if ($alter_add_form && !$action && !$widget_id) {
                $res = $alter_add_form;
            } else if ($alter_edit_form && $widget_id) {
                if ($widget_fields_array) {
                    foreach ($widget_fields_array as $wf) {
                        foreach ($wf as $key => $val) {
                            ${'field'.$key} = $val;
                            ${'f_'.$fieldIDs[$key]} = $val;
                        }
                    }
                }

                if ($File_Mode) {
                    ob_start();
                    include $widget_view->get_field_path('EditForm');
                    $res .= ob_get_clean();
                } else {
                    eval("\$res .= \"".$alter_edit_form."\";");
                }

            } else {
                $i = 0;
                foreach ((array) $result as $r) {
                    $description = "<font color='gray'>".($r['Description'] != '' ? $r['Description'] : $r['Field_Name']).($r['NotNull'] == '1' ? " (*)" : "").":</font><br />\n";
                    if ($r['TypeOfData_ID'] == 3) {
                        $res .= $description;
                        $res .= nc_admin_textarea_simple("f_".$fieldIDs[$r['Field_ID']], ($action == 'EditForm' ? '$f_'.$fieldIDs[$r['Field_ID']] : (($action == 'AddForm' || !$widget_id) ? $r['DefaultState'] : $widget_fields_array[$i][$r['Field_ID']])), '', 0, 0, '', 'soft')."<br /><br />\n";
                    } else {
                        $res .= $description;
                        $res .= "<input name='f_".$fieldIDs[$r['Field_ID']]."' size='50' value='".($action == 'EditForm' ? '$f_'.$fieldIDs[$r['Field_ID']] : (($action == 'AddForm' || !$widget_id) ? $r['DefaultState'] : $widget_fields_array[$i][$r['Field_ID']]))."' /><br /><br />\n";
                    }
                    $i++;
                }
            }

            echo $res;
        }
        exit();
        break;

    case 91: // ajax превью
        $perm->ExitIfNotAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_RIGHT, -1, 0, 0);

        $result = $db->get_var("SELECT `Result`
                            FROM `Widget`
                            WHERE `Keyword` = '".$keyword."'");
        print $result;
        exit();
        break;

    case 100: // для CKEditor'a
        $perm->ExitIfNotAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_RIGHT, -1, 0, 0);
        $result = $db->get_results("SELECT REPLACE(w.`Name`, CONCAT(wc.`Category`, ':'), '') as `Name`, w.`Keyword`, w.`Result`, wc.`Category`
                            FROM `Widget` as `w`, `Widget_Class` as `wc`
                            WHERE w.Widget_Class_ID = wc.Widget_Class_ID
                            ORDER BY wc.Category");
        die(json_encode($result));
}

EndHtml ();