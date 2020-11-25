<?php
/* $Id: message.php 8374 2012-11-08 13:06:19Z lemonade $ */

$action = "change";
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -2)) . ( strstr(__FILE__, "/") ? "/" : "\\" );
@include_once ($NETCAT_FOLDER . "vars.inc.php");

require ($INCLUDE_FOLDER . "index.php");
require ($INCLUDE_FOLDER . "s_files.inc.php");
require_once ($ADMIN_FOLDER . "admin.inc.php");
require_once ($INCLUDE_FOLDER . "classes/nc_imagetransform.class.php");

// security section
$catalogue += 0;
$sub += 0;
$cc += 0;
$curPos += 0;
//загрузка окружения заданного в require/index.php
$cc_env = $current_cc;

// $cc not found
if ( empty($cc_env) && $nc_core->inside_admin ) {
	require_once ($ADMIN_FOLDER . "function.inc.php");
	require_once ($ADMIN_FOLDER . "catalogue/function.inc.php");
	// make UI
	$UI_CONFIG = new ui_config_catalogue('map', $catalogue);
	// show admin template
	BeginHtml();
	nc_print_status(CONTROL_CONTENT_CATALOUGE_FUNCS_ERROR_NOSUBCLASS, 'error');
	EndHtml();
	// halt
	exit;
}

// если параметр trash не задан, считаем, что надо просто удалить объект, иначе - переместить в корзину
$Class_Template_ID = nc_Core::get_object()->sub_class->get_by_id($cc, 'Class_Template_ID');
if (!isset($cc_env['File_Mode'])) {
    if (is_array($cc_env)) {
        $cc_env = array_merge($cc_env, nc_get_file_mode_and_file_path($Class_Template_ID ? $Class_Template_ID : $classID));
    } else {
        $cc_env = nc_get_file_mode_and_file_path($Class_Template_ID ? $Class_Template_ID : $classID);
    }
}

if ($cc_env['File_Mode']) {
    $file_class = new nc_class_view($CLASS_TEMPLATE_FOLDER, $db);
    $file_class->load($Class_Template_ID ? $Class_Template_ID : $classID, $cc_env['File_Path'], $Class_Template_ID ? null : $cc_env['File_Hash']);

    require $INCLUDE_FOLDER . "classes/nc_class_aggregator_editor.class.php";
    $nc_class_aggregator = nc_class_aggregator_editor::init($file_class);
}

ob_start();

do {

    nc_check_availability_candidates_for_delete_in_multifile_and_delete();

    if (!$user_table_mode && !$message && !$delete && !$export && !$import && !$nc_recovery) {
        nc_print_status(NETCAT_MODERATION_ERROR_NORIGHTS, "error");
        break;
    }

    if ($posting && $nc_core->token->is_use($delete ? "delete" : "change")) {
        if (!$nc_core->token->verify()) {
            echo NETCAT_TOKEN_INVALID;
            break;
        }
    }

    $is_there_any_files = $user_table_mode ? getFileCount(0, $systemTableID) : getFileCount($classID, 0);

# права модератора
    $modRights = CheckUserRights($current_cc['Sub_Class_ID'], "moderate", $posting);

# формирование обратной ссылки
    $alter_goBackLink = "";
    $alter_goBackLink_true = false;

    if (isset($_REQUEST['goBackLink'])) {
        $alter_goBackLink = $_REQUEST['goBackLink'];
        if ($admin_mode && preg_match("/^[\/a-z0-9_-]+\?catalogue=[[:digit:]]+&sub=[[:digit:]]+&cc=[[:digit:]]+(&curPos=[[:digit:]]{0,12})?$/im", $alter_goBackLink))
            $alter_goBackLink_true = true;
        if (!$admin_mode && preg_match("/^[\/a-z0-9_-]+(\.html)?(\?curPos=[[:digit:]]{0,12})?$/im", $alter_goBackLink))
            $alter_goBackLink_true = true;
    }

# если путь не задан в форме
    if (!$alter_goBackLink_true) {
        if ($admin_mode) {
            $goBackLink = $admin_url_prefix . "?catalogue=" . $catalogue . "&sub=" . $sub . "&cc=" . $cc . "&curPos=" . $curPos;
        } else {
            $goBackLink = $current_catalogue['Hidden_Host'] . $SUB_FOLDER . $current_sub['Hidden_URL'] . (!$user_table_mode ? $current_cc['EnglishName'] . ".html" : "") . ($curPos ? "?curPos=" . $curPos : "");
        }
    } else {
        $goBackLink = $alter_goBackLink;
    }

    $goBack = "<a href=" . $goBackLink . ">" . NETCAT_MODERATION_BACKTOSECTION . "</a>";

// визуальные настройки
    $cc_settings = nc_get_visual_settings($cc);

// удаление или включение/выключение одного объекта
// нужно загрузить все данные полей
    if (($delete || $checked) && $message && !is_array($message) && $posting) {
        $component = new nc_Component($classID, $user_table_mode ? 3 : 0);
        $component->make_query();

        $field_names = $component->get_fields_query();
        $field_vars = $component->get_fields_vars();
        $multilist_fileds = $component->get_fields(10);
        //$date_field = $component->get_date_field(); #not used

        $message_select = "
    SELECT  " . $field_names . "
    FROM (" . ($user_table_mode ? "`User`" : "`Message" . $classID . "`") . " AS a )" .
                $component->get_joins() . "
    WHERE  a.`Message_ID` = '" . (int) $message . "'";


        eval("list(" . $field_vars . ") = \$db->get_row(\$message_select, ARRAY_N);");

        // Multiselect
        $multilist_fileds = $component->get_fields(10);
        if (!empty($multilist_fileds)) {
            // просмотр каждого поля типа multiselect
            foreach ($multilist_fileds as $multilist_filed) {
                // таблицу с элементами можно взять их кэша, если ее там нет - то добавить
                if (!$_cache['classificator'][$multilist_filed['table']]) {
                    $db_res = $db->get_results("SELECT `" . $multilist_filed['table'] . "_ID` AS ID, `" . $multilist_filed['table'] . "_Name` AS Name, `Value`
                                     FROM `Classificator_" . $multilist_filed['table'] . "`", ARRAY_A);
                    if (!empty($db_res)) {
                        foreach ($db_res as $v) { // запись в кэш
                            $_cache['classificator'][$multilist_filed['table']][$v['ID']] = array($v['Name'], $v['Value']);
                        }
                    }
                    unset($db_res);
                }

                ${"f_" . $multilist_filed['name'] . "_id"} = array();
                ${"f_" . $multilist_filed['name'] . "_value"} = array();

                if (($value = ${"f_" . $multilist_filed['name']})) { // значение из базы
                    ${"f_" . $multilist_filed['name']} = array();
                    $ids = explode(',', $value);
                    if (!empty($ids)) {
                        foreach ($ids as $id) { // для каждого элемента по id определяем имя
                            if ($id) {
                                array_push(${"f_" . $multilist_filed['name']}, $_cache['classificator'][$multilist_filed['table']][$id][0]);
                                array_push(${"f_" . $multilist_filed['name'] . "_value"}, $_cache['classificator'][$multilist_filed['table']][$id][1]);
                                array_push(${"f_" . $multilist_filed['name'] . "_id"}, $id);
                            }
                        }
                    }
                }
                // default value
                if (!is_array(${"f_" . $multilist_filed['name']}))
                    ${"f_" . $multilist_filed['name']} = array();
            }
            unset($ids);
            unset($id);
            unset($value);
        }

        // 'left join' used to provide compatibility with old fs ($f_File_url)
        $res = $db->get_results("SELECT fd.`Field_ID` AS field_id, fd.`Field_Name` AS field, ft.`File_Path` AS path, ft.`Virt_Name` AS name
    FROM `Field` AS fd
    LEFT JOIN `Filetable` AS ft
    ON (fd.`Field_ID` = ft.`Field_ID` AND ft.`Message_ID` = '" . (int) $message . "')
    WHERE fd.`Class_ID` = '" . (int) $classID . "'
    AND fd.`TypeOfData_ID` = 6", ARRAY_A);

        foreach ((array) $res AS $row) {
            $field_value = ${"f_" . $row['field']}; // то, что хранится в базе
            // возможен случай, что файла нет.
            if (!$field_value)
                continue;
            $field_value = explode(':', $field_value);

            // оригинальное имя, тип, размер
            ${"f_" . $row['field'] . "_name"} = $field_value[0];
            ${"f_" . $row['field'] . "_type"} = $field_value[1];
            ${"f_" . $row['field'] . "_size"} = $field_value[2];


            if ($row['name']) { // Protected FileSystem
                ${"f_" . $row['field']} = $SUB_FOLDER . rtrim($HTTP_FILES_PATH, '/') . $row['path'] . "h_" . $row['name'];
                ${"f_" . $row['field'] . "_url"} = $SUB_FOLDER . rtrim($HTTP_FILES_PATH, '/') . $row['path'] . $row['name'];
            } else if ($field_value[3]) { // Original FileSystem
                ${"f_" . $row['field']} = ${"f_" . $row['field'] . "_url"} = $SUB_FOLDER . $HTTP_FILES_PATH . $field_value[3];
            } else { // Simple FileSystem
                $ext = substr($field_value[0], strrpos($field_value[0], "."));
                ${"f_" . $row['field'] . "_url"} = ${"f_" . $row['field']} = $SUB_FOLDER . $HTTP_FILES_PATH . $row['field_id'] . "_" . $f_RowID . $ext;
            }
        }
        // free memory
        unset($field_value);
        unset($ext);
        unset($res);

        $f_Created_year = substr($f_Created, 0, 4);
        $f_Created_month = substr($f_Created, 5, 2);
        $f_Created_day = substr($f_Created, 8, 2);
        $f_Created_hours = substr($f_Created, 11, 2);
        $f_Created_minutes = substr($f_Created, 14, 2);
        $f_Created_seconds = substr($f_Created, 17, 2);

        $f_LastUpdated_year = substr($f_LastUpdated, 0, 4);
        $f_LastUpdated_month = substr($f_LastUpdated, 4, 2);
        $f_LastUpdated_day = substr($f_LastUpdated, 6, 2);
        $f_LastUpdated_hours = substr($f_LastUpdated, 8, 2);
        $f_LastUpdated_minutes = substr($f_LastUpdated, 10, 2);
        $f_LastUpdated_seconds = substr($f_LastUpdated, 12, 2);
    }

    if ($nc_recovery && is_array($nc_trashed_ids)) {
        $nc_core->trash->recovery($nc_trashed_ids);
        ob_end_clean();
        header("Location: " . $goBackLink . "&inside_admin=1");
        exit;
    }

	// DELETE: удаление объекта(ов)
    if ($delete) {
        // подтверждение удаления
        if (+$_REQUEST['isNaked'] && !+$_REQUEST['force_delete'] && !+$db->get_var("SELECT `Value` FROM Settings  WHERE `Key` = 'TrashUse'")) {
            ob_clean();
            echo "cart_disabled";
            exit;
        }

        if (!$posting) {
            if ($message && !is_array($message)) {
                if ($inside_admin) {
                    echo $nc_core->trash->delete_preform();
                }
                if ($cc_env['File_Mode']) {
                    $nc_parent_field_path = $file_class->get_parent_fiend_path('DeleteTemplate');
                    $nc_field_path = $file_class->get_field_path('DeleteTemplate');
                    // check and include component part
					try {
						if ( nc_check_php_file($nc_field_path) ) {
							include $nc_field_path;
						}
					}
					catch (Exception $e) {
						if ( is_object($perm) && $perm->isSubClassAdmin($cc) ) {
							// error message
							echo sprintf(CONTROL_CLASS_CLASSFORM_CHECK_ERROR, CONTROL_CLASS_CLASS_FORMS_DELETEFORM);
						}
					}
                    $nc_parent_field_path = null;
                    $nc_field_path = null;
                } else {
                    eval("\$result = \"" . ($DeleteTemplate ? $DeleteTemplate : nc_fields_form("message")) . "\";");
                    echo $result;
                }
            } else {
                $url = $admin_url_prefix . "message.php?catalogue=" . $catalogue . "&sub=" . $sub . "&cc=" . $cc . "&delete=1&posting=1" . ($admin_mode ? "&admin_mode=1" : "");
                $url .= $nc_core->token->is_use('drop') ? "&" . $nc_core->token->get_url() : "";
                if (!empty($message)) {
                    foreach ($message as $v)
                        $url .= "&message[" . $v . "]=" . $v;
                }

                if($isNaked) {
                    echo "kill_all$url";
                    exit;
                } else {
                    nc_print_status(sprintf(NETCAT_MODERATION_WARN_COMMITDELETIONINCLASS, $cc), 'info');                    
                    echo "<a href='" . $url . "'>" . NETCAT_MODERATION_COMMON_KILLALL . "</a> | " . $goBack;
                }
            }
            
            break;
        }

        // выясним, какие объекты удлаять
        $message_ids = $db->get_col("SELECT `Message_ID` FROM `Message" . $classID . "`
            WHERE ".(!$message ? "`Sub_Class_ID` = '".$cc."' " : "`Message_ID` IN ( ".( is_array($message) ? join(',', $message) : $message) . ")" )."
			".($modRights ? "" : " AND `User_ID` = '" . $AUTH_USER_ID . "' ") );

        if (!$message_ids) {
            echo NETCAT_MODERATION_ERROR_NORIGHTS . "<br/><br/>".$goBack;
            break;
        }

        // если идет пакетное удаление или удаление всех, то в
        // $message должен быть массив реально удаляемых объектов
        // при удаление одного объекта $message должен быть числом
        if ( !$message || is_array($message) ) {
            $message = $message_ids;
        }

        // условие удаления
        if ($cc_env['File_Mode']) {
            $nc_parent_field_path = $file_class->get_parent_fiend_path('DeleteCond');
            $nc_field_path = $file_class->get_field_path('DeleteCond');
            // check and include component part
			try {
				if ( nc_check_php_file($nc_field_path) ) {
					include $nc_field_path;
				}
			}
			catch (Exception $e) {
				if ( is_object($perm) && $perm->isSubClassAdmin($cc) ) {
					// error message
					echo sprintf(CONTROL_CLASS_CLASSFORM_CHECK_ERROR, CONTROL_CLASS_CLASS_FORMS_DELETERULES);
				}
			}
            $nc_parent_field_path = null;
            $nc_field_path = null;
        } else {
            eval($DeleteCond);
        }

        // posting может обнулиться в условие удаления
        if (!$posting) {
            eval("echo \"" . $warnText . "\";");
            break;
        }

        // удаление
        $trash = $nc_core->get_settings('TrashUse');
        $nc_core->message->delete_by_id($message, $classID, $trash);

        if ($isNaked) {
            echo 'deleted';
        }

        $SQL = "SELECT `Path`, `Preview`
			FROM `Multifield` AS m,
				`Field` AS f
			WHERE m.`Message_ID` ".( is_array($message) ? "IN(" . join(', ', $message) . ")" : " = '".$message."'" )."
				AND m.`Field_ID` = f.`Field_ID`
				AND f.`Class_ID` = ". +$classID;
        $file_for_del = $db->get_results($SQL);
        if ($file_for_del) {
            $array_file_for_del = array();
            foreach ($file_for_del as $file) {
                $array_file_for_del[] = $file->Path;
                if ($file->Preview) {
                    $array_file_for_del[] = $file->Preview;
                }
            }
            $SQL = "DELETE FROM `Multifield` AS m,
				`Field` sd f
				WHERE Message_ID ".( is_array($message) ? "IN(" . join(', ', $message) . ")" : "= '".$message."'" )."
					AND m.`Field_ID` = f.`Field_ID`
					AND f.`Class_ID` = ". +$classID;
            $db->query($SQL);
            foreach ($array_file_for_del as $file) {
                if (file_exists($DOCUMENT_ROOT . $SUB_FOLDER . $file)) {
                    @unlink($DOCUMENT_ROOT . $SUB_FOLDER . $file);
                }
            }
        }

        // при перемещении объектов в корзину добавим в $goBackLink нужные айди
        if ($trash) {
            if ($nc_core->trash->is_full()) {
                $goBackLink .= strpos($goBackLink, '?') === false ? '?' : '&';
                $goBackLink .= 'nc_trash_full=1';
            }
            if ($nc_core->trash->folder_fail()) {
                $goBackLink .= strpos($goBackLink, '?') === false ? '?' : '&';
                $goBackLink .= 'nc_folder_fail=1';
            }
            if (($deleted_ids = $nc_core->trash->get_deleted_ids()) && !empty($deleted_ids)) {
                $added_ar = array();
                $goBackLink .= strpos($goBackLink, '?') === false ? '?' : '&';
                foreach ($deleted_ids as $v)
                    $added_ar[] = "nc_trashed_ids[]=" . $v;
                $goBackLink .= join('&', $added_ar);
            }
        }

        // действие после удаления
        //если нет действия после удаления выводить стандартную форму
        if ($cc_env['File_Mode']) {
            $nc_parent_field_path = $file_class->get_parent_fiend_path('DeleteActionTemplate');
            $nc_field_path = $file_class->get_field_path('DeleteActionTemplate');
            
            if (filesize($nc_field_path)){
            // check and include component part
			try {
				if ( nc_check_php_file($nc_field_path) ) {
					include $nc_field_path;
				}
			}
			catch (Exception $e) {
				if ( is_object($perm) && $perm->isSubClassAdmin($cc) ) {
					// error message
					echo sprintf(CONTROL_CLASS_CLASSFORM_CHECK_ERROR, CONTROL_CLASS_CLASS_FORMS_ONDELACTION);
				}
			}
            } else {
                eval("echo \"" . nc_fields_action_code("deleteaction") . "\";");
            }
            
            $nc_parent_field_path = null;
            $nc_field_path = null;
        } else {
            eval("echo \"" . ($DeleteActionTemplate ? $DeleteActionTemplate : nc_fields_action_code("deleteaction")) . "\";");
        }

        if ($isNaked) {
            echo ob_get_clean();
            exit;
        }
        
        break;
    }
	else {
		if ($checked && $message && $posting) {
			$noRights = false;
			$checked = intval($checked);

			// все объекты, которые нужно включить \ выключить
			$messages = $db->get_col("SELECT `Message_ID` FROM `Message" . $classID . "`
									  WHERE " . ($modRights ? "1" : "`User_ID` = '" . $AUTH_USER_ID . "'" ) . "
									  AND `Message_ID` IN (" . join(',', (array) $message) . ")");
			if (empty($messages)) {
				echo NETCAT_MODERATION_ERROR_NORIGHT . "<br/><br/>";
				break;
			}

			if ($passed_thru_404) { // Изменение видимости не в режиме admin_mode - поменять состояние на противоположное
				// в БД хранится 0 или 1
				// Если $checked = 2 - объекты включается, если $checked = 1 - объект выключается
				$checked = 2 - $db->get_var("SELECT `Checked` FROM `Message" . $classID . "` WHERE `Message_ID` = '" . $message . "'");
			}

			$res = $db->query("UPDATE `Message" . $classID . "` SET `Checked` = " . ($checked - 1) . " WHERE `Message_ID` IN (" . join(',', $messages) . ")");

			// execute core action
			$nc_core->event->execute(($checked == 2 ? "checkMessage" : "uncheckMessage"), $catalogue, $sub, $cc, $classID, $messages);

			if ($cc_env['File_Mode']) {
				$nc_parent_field_path = $file_class->get_parent_fiend_path('CheckActionTemplate');
				$nc_field_path = $file_class->get_field_path('CheckActionTemplate');
				// check and include component part
				try {
					if ( nc_check_php_file($nc_field_path) ) {
						include $nc_field_path;
					}
				}
				catch (Exception $e) {
					if ( is_object($perm) && $perm->isSubClassAdmin($cc) ) {
						// error message
						echo sprintf(CONTROL_CLASS_CLASSFORM_CHECK_ERROR, CONTROL_CLASS_CLASS_FORMS_ONONACTION);
					}
				}
				$nc_parent_field_path = null;
				$nc_field_path = null;
			} else {
				eval("echo \"" . ($CheckActionTemplate ? $CheckActionTemplate : nc_fields_action_code('checkaction')) . "\";");
			}
			
			break;
		}

		if ( $classPreview && $classPreview == ($current_cc["Class_Template_ID"] ? $current_cc["Class_Template_ID"] : $current_cc["Class_ID"])) {
			$magic_gpc = get_magic_quotes_gpc();
			$editTemplate = $magic_gpc ? stripslashes($_SESSION["PreviewClass"][$classPreview]["EditTemplate"]) : $_SESSION["PreviewClass"][$classPreview]["EditTemplate"];
			$editCond = $magic_gpc ? stripslashes($_SESSION["PreviewClass"][$classPreview]["EditCond"]) : $_SESSION["PreviewClass"][$classPreview]["EditCond"];
			$editActionTemplate = $magic_gpc ? stripslashes($_SESSION["PreviewClass"][$classPreview]["EditActionTemplate"]) : $_SESSION["PreviewClass"][$classPreview]["EditActionTemplate"];
		}

		if ($posting) {
			if ($cc_env['File_Mode']) {
				$nc_parent_field_path = $file_class->get_parent_fiend_path('EditCond');
				$nc_field_path = $file_class->get_field_path('EditCond');
				// check and include component part
				try {
					if ( nc_check_php_file($nc_field_path) ) {
						include $nc_field_path;
					}
				}
				catch (Exception $e) {
					if ( is_object($perm) && $perm->isSubClassAdmin($cc) ) {
						// error message
						echo sprintf(CONTROL_CLASS_CLASSFORM_CHECK_ERROR, CONTROL_CLASS_CLASS_FORMS_EDITRULES);
					}
				}
				$nc_parent_field_path = null;
				$nc_field_path = null;
			} else {
				eval($editCond);
			}
		}

		$fld = array();
		require $ROOT_FOLDER . "message_fields.php";

		$query_str = "SELECT
	  DATE_FORMAT(a.`Created`,'%d.%m.%Y, %H:%i'),
	  DATE_FORMAT(a.LastUpdated,'%d.%m.%Y, %H:%i')" .
				(!$user_table_mode ? ",
		a.`User_ID`,
		a.`IP`,
		a.`LastUser_ID`,
		a.`LastIP`,
		a.`Priority`" : NULL
				);

		if (!$user_table_mode && $admin_mode && $AUTHORIZE_BY !== 'User_ID') {
			$query_str .= ", uNewAdminInterface.`" . $AUTHORIZE_BY . "` AS f_newAdminInterface_user_add, uNewAdminInterface2.`" . $AUTHORIZE_BY . "` AS f_newAdminInterface_user_change ";
		}

		$query_str .= " FROM " . ($user_table_mode ? "`User`" : "`Message" . $classID . "`") . " AS a ";

		if (!$user_table_mode && $admin_mode && $AUTHORIZE_BY !== 'User_ID') {
			$query_str .= " LEFT JOIN `User` AS uNewAdminInterface ON a.`User_ID` = uNewAdminInterface.`User_ID` LEFT JOIN `User` AS uNewAdminInterface2 ON a.`LastUser_ID` = uNewAdminInterface2.`User_ID` ";
		}

		$query_str .= "WHERE a.`" . ($user_table_mode ? "User" : "Message") . "_ID` = " . $message;

		$res = $db->get_row($query_str, ARRAY_N);

		if (!$user_table_mode) {
			if ($AUTHORIZE_BY === 'User_ID') {
				list($f_Created, $f_LastUpdated, $f_UserID, $f_IP, $f_LastUserID, $f_LastIP, $tmp_f_Priority) = $res;
				$f_newAdminInterface_user_add = $f_UserID;
				$f_newAdminInterface_user_change = $f_LastUserID;
			} else {
				list($f_Created, $f_LastUpdated, $f_UserID, $f_IP, $f_LastUserID, $f_LastIP, $tmp_f_Priority, $f_newAdminInterface_user_add, $f_newAdminInterface_user_change) = $res;
			}
			if (!$posting)
				$f_Priority = $res[6];
		}
		else {
			list($f_Created, $f_LastUpdated) = $res;
		}


	// редактируем пользователя, нужно проверить права
		if ($user_table_mode) {
			if (!$message)
				$message = $AUTH_USER_ID;
			//пользователь должен быть авторизованным и
			// иметь доступ к изменению пользователя (если это не он сам)
			if (!is_object($perm) || ($AUTH_USER_ID != $message && !$perm->isAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_EDIT, $message, $posting))) {
				nc_print_status(NETCAT_MODERATION_ERROR_NORIGHTS, "error");
				break;
			}
		}

		if ($posting == 0) {
			if (!isset($posting) && count($fld)) {
				$fieldQuery = "`" . join($fld, "`, `") . "`";

				if ($user_table_mode) {
					$select = "SELECT " . $fieldQuery . ", `Checked`, `Keyword`
			FROM `User`
			WHERE `User_ID` = '" . $message . "'";
				} else {
					$select = "SELECT " . $fieldQuery . ", `Checked`, `Keyword`, `ncTitle`, `ncKeywords`, `ncDescription`, `Parent_Message_ID`
			FROM `Message" . $classID . "`
			WHERE `Message_ID` = '" . $message . "'";
				}

				$fldValueVars = $db->get_row($select, ARRAY_A);

				if ($fldValueVars)
					$fldValue = array_values($fldValueVars);

				for ($n = 0, $end = count($fldType); $n < $end; ++$n) {
					if ($fldType[$n] == NC_FIELDTYPE_MULTIFILE) {
						$fldValueVars[$fld[$n]] = ${'f_' . $fld[$n]};
					}
				}
				if ($fldValueVars)
					extract($fldValueVars, EXTR_PREFIX_ALL, "f");

				//if ($user_table_mode) $message = $AUTH_USER_ID;

				for ($i = 0; $i < $fldCount; $i++) {
					if ($fldType[$i] == 8) {
						eval("\$f_" . $fld[$i] . "_year = substr(\$f_" . $fld[$i] . ", 0, 4);");
						eval("\$f_" . $fld[$i] . "_month = substr(\$f_" . $fld[$i] . ", 5, 2);");
						eval("\$f_" . $fld[$i] . "_day = substr(\$f_" . $fld[$i] . ", 8, 2);");
						eval("\$f_" . $fld[$i] . "_hours = substr(\$f_" . $fld[$i] . ", 11, 2);");
						eval("\$f_" . $fld[$i] . "_minutes = substr(\$f_" . $fld[$i] . ", 14, 2);");
						eval("\$f_" . $fld[$i] . "_seconds = substr(\$f_" . $fld[$i] . ", 17, 2);");
					} else if ($fldType[$i] == 6 && $fldValue[$i]) {
						${"f_" . $fld[$i] . "_old"} = $fldValue[$i];

						$fileinfo = $db->get_row("SELECT * FROM `Filetable`
			  WHERE `Field_ID` = " . $fldID[$i] . " AND `Message_ID` = " . $message, ARRAY_A);

						if ($fileinfo) {
							${"f_" . $fld[$i]} = $SUB_FOLDER . rtrim($HTTP_FILES_PATH, "/") . $fileinfo["File_Path"] . "h_" . $fileinfo["Virt_Name"];
							${"f_" . $fld[$i] . "_url"} = $SUB_FOLDER . rtrim($HTTP_FILES_PATH, "/") . $fileinfo["File_Path"] . $fileinfo["Virt_Name"];
							${"f_" . $fld[$i] . "_name"} = $fileinfo["Real_Name"];
							${"f_" . $fld[$i] . "_size"} = $fileinfo["File_Size"];
							${"f_" . $fld[$i] . "_type"} = $fileinfo["File_Type"];
						} else {
                                                        $field_value = explode(':', $fldValue[$i]);
                                                        ${"f_" . $fld[$i] . "_name"} = $field_value[0];
                                                        ${"f_" . $fld[$i] . "_type"} = $field_value[1];
                                                        ${"f_" . $fld[$i] . "_size"} = $field_value[2];                                                        
                                                        ${"f_" . $fld[$i]} = ${"f_" . $fld[$i] . "_url"} = $SUB_FOLDER . $HTTP_FILES_PATH . $field_value[3];
						}
					}
				}
			}

			if (!$modRights && $f_UserID != $AUTH_USER_ID && !$user_table_mode) {
				nc_print_status(NETCAT_MODERATION_ERROR_NORIGHTS, 'error');
				break;
			}

			if ($editTemplate || ($cc_env['File_Mode'] && filesize($file_class->get_field_path('EditTemplate')))) {
				if (!$systemTableID) {
					$editTemplate = nc_prepare_message_form($editTemplate, $action, $admin_mode, $user_table_mode, $sys_table_id, $current_cc, $f_Checked, $f_Priority, $f_Keyword, $f_ncTitle, $f_ncKeywords, $f_ncDescription);
				}
				// Если возникла ошибка после sumbit, то все данные прошли через
				// magic_quotes и содержат слэши, ненужные при повторном выводе формы
				if ($warnText) {
					// получим список переменных, используемых в альтернативной форме, и переберем его
					nc_preg_match_all('#\$([a-z0-9_]+)#i', $editTemplate, $all_template_variables);
					foreach ($all_template_variables[1] AS $template_variable) {
						// если значение переменной было установлено в запросе и не менялось в ходе выполнения скриптов,
						// необходимо убрать из него "лишние" слэши
						if ($_REQUEST[$template_variable] == $$template_variable) {
							$$template_variable = stripslashes($$template_variable);
						}
					}
				}

				if ($cc_env['File_Mode']) {
					$nc_parent_field_path = $file_class->get_parent_fiend_path('EditTemplate');
					$nc_field_path = $file_class->get_field_path('EditTemplate'); 
                                        
                                        // обертка для вывода ошибки в админке
                                        if ($warnText && ($nc_core->inside_admin || $isNaked)) {
                                            ob_start();
                                            nc_print_status($warnText, 'error');
                                            $warnText = ob_get_clean();
                                        }

                                        ob_start(); 
					// check and include component part
					try {
						if ( nc_check_php_file($nc_field_path) ) {
							include $nc_field_path;
						}
					}
					catch (Exception $e) {
						if ( is_object($perm) && $perm->isSubClassAdmin($cc) ) {
							// error message
							echo sprintf(CONTROL_CLASS_CLASSFORM_CHECK_ERROR, CONTROL_CLASS_CLASS_FORMS_EDITFORM);
						}
					}
					echo nc_prepare_message_form(ob_get_clean(), $action, $admin_mode, $user_table_mode, $sys_table_id, $current_cc, $f_Checked, $f_Priority, $f_Keyword, $f_ncTitle, $f_ncKeywords, $f_ncDescription);
					$nc_parent_field_path = null;
					$nc_field_path = null;
				} else {
					eval("echo \"" . $editTemplate . "\";");
				}
			} else if ($multiple_changes) {
				echo "<script type='text/javascript'>history.go(-1);</script>";
				exit;
			} else {
				require $ROOT_FOLDER . "message_edit.php";
			}

			if ($inside_admin && $UI_CONFIG && $goBackLink) {
				$UI_CONFIG->actionButtons[] = array("id" => "goback",
						"caption" => CONTROL_AUTH_HTML_BACK,
						"align" => 'left',
						"action" => "mainView.loadIframe('" . $goBackLink . "&inside_admin=1')");
			}

			break;
		} else if ($posting == 1) {
			//echo "!!!<br>";
			require $ROOT_FOLDER . "message_put.php";
			if (is_array($SQL_multifield)) {
				nc_multifield_sql_exec($message ? $message : $AUTH_USER_ID, $SQL_multifield);
			}

			$f_Checked = 0;
			if(isset($_POST['f_Checked'])) {
				$f_Checked = 1;
			}

			if ($multiple_changes) {
				foreach ($updateStrings as $multiple_changes_msg_id => $update_string) {
					if ($user_table_mode) {
						$resMsg = $db->query("UPDATE `User` SET $update_string WHERE `User_ID` = " . $multiple_changes_msg_id);
					} else {
						$SQL = "UPDATE `Message$classID`
								SET $update_string,
									`LastUser_ID` = $AUTH_USER_ID,
									`LastIP` = '" . $db->escape($REMOTE_ADDR) . "',
									`LastUserAgent` = '" . $db->escape($HTTP_USER_AGENT) . "'
									WHERE `Message_ID` = " . $multiple_changes_msg_id;
						$resMsg = $db->query($SQL);
					}
				}
			} else {
				if ($user_table_mode) {
					$resMsg = $db->query("UPDATE `User` SET " . $updateString . " `Checked` = `Checked`" . ($admin_mode ? ", `Keyword` = '" . $f_Keyword . "'" : "") . " " . ($Password ? ", `Password` = " . $nc_core->MYSQL_ENCRYPT . "('" . $db->escape($Password) . "'), `UserType` = 'normal' " : "") . " WHERE `User_ID` = '" . $message . "'");
				} else {
					// save edited order
					if (($classID==51)&&($admin_mode)) {
						$wroff=0;
						$wroffdate="";
						// проверяем признак "Списать с реализации" (wroff)
						$db_res1 = $db->get_results("SELECT `wroff`,`wroffdate` FROM Message51 WHERE `Message_ID` = '".$message."'", ARRAY_A);
						if (!empty($db_res1)) {
							//print_r($db_res1);
							foreach ($db_res1 as $v) { 
								$wroff=$v['wroff'];
								$wroffdate=$v['wroffdate'];
							}
						}
						//echo $wroff."|".$wroffdate."<br>";
						if (!$wroff) {
							// ищем изменения для простановки wroff
							//print_r($_POST);
							if ((isset($_POST['f_wroffdatestr']))&&($_POST['f_wroffdatestr'])) {
								$updateString.="`wroffdate`='".date("Y-m-d H:i:s",strtotime($_POST['f_wroffdatestr']))."',";
							}
							
							if (($_POST['f_Status']==4)||($_POST['f_paid']==1)||($_POST['f_wroff'])) {
								$updateString.="`wroff`=1,`wroffdate`='".(($_POST['f_wroffdatestr']) ? date("Y-m-d H:i:s",strtotime($_POST['f_wroffdatestr'])) : date("Y-m-d H:i:s"))."',";
							}
							// записываем историю заказа
							$sql = "INSERT INTO Netshop_OrderHistory (Order_ID, created,orderstatus_id,comments) VALUES
								({$message}, '".date("Y-m-d H:i:s")."',{$_POST['f_Status']},'Установлен признак Списать с релизации')"; 
							//echo $SQL;
							$res = $db->query($sql);
							//echo $res;
						}
						// обновление даты отправки посылки
						if ((isset($_POST['f_senddatestr']))&&($_POST['f_senddatestr'])) {
							$updateString.="`senddate`='".date("Y-m-d H:i:s",strtotime($_POST['f_senddatestr']))."',";
						}
					}
					//echo $updateString."<br>";
						
					$SQL = "UPDATE `Message" . $classID . "` SET " . $updateString . ($admin_mode ? " `Checked` = '" . $f_Checked . "', `Keyword` = '" . $f_Keyword . "', " : "") . "`LastUser_ID` = '" . $AUTH_USER_ID . "', `LastIP` = '" . $db->escape($REMOTE_ADDR) . "', `LastUserAgent` = '" . $db->escape($HTTP_USER_AGENT) . "' WHERE `Message_ID` = '" . $message . "'" . (!$modRights ? " AND `User_ID` = '" . $AUTH_USER_ID . "'" : "");
					//echo $SQL;
					$resMsg = $db->query($SQL);
					
				}
			}

			if ($db->is_error) {
				$resMsg = 0;
			} else {
				$resMsg = 1;
				// execute core action
				if ($user_table_mode) {
					$nc_core->event->execute("updateUser", $message);
				} else {
					$nc_core->event->execute("updateMessage", $catalogue, $sub, $cc, $classID, $message);
				}
			}

			for ($i = 0; $i < count($tmpFile); $i++) {
				if (!file_exists($FILES_FOLDER . $message_sub)) {
					@mkdir($FILES_FOLDER . $message_sub, $DIRCHMOD);
					@chmod($FILES_FOLDER . $message_sub, $DIRCHMOD);
				}

				if (!file_exists($FILES_FOLDER . $message_sub . "/" . $message_cc)) {
					@mkdir($FILES_FOLDER . $message_sub . "/" . $message_cc, $DIRCHMOD);
					@chmod($FILES_FOLDER . $message_sub . "/" . $message_cc, $DIRCHMOD);
				}

				eval("\$tmpNewFile[\$i] = \"" . $tmpNewFile[$i] . "\";");
				@rename($FILES_FOLDER . $tmpFile[$i], $FILES_FOLDER . $File_Path[$i] . $tmpNewFile[$i]);
				@chmod($FILES_FOLDER . $File_Path[$i] . $tmpNewFile[$i], $FILECHMOD);
			}


			// обновить значение Message_ID в таблице Filetable
			if ($filetable_lastid) {
				$resMsgArr = array();
				foreach ($filetable_lastid AS $id) {
					$resMsgArr[] = $id;
				}
				if (!empty($resMsgArr)) {
					$resMsg = $db->query("UPDATE `Filetable` SET `Message_ID` = '" . $message . "' WHERE ID IN (" . join(", ", $resMsgArr) . ")");
				}
				unset($resMsgArr);
			}

			if ($admin_mode && !$user_table_mode && isset($f_Priority)) {
				$f_Priority = (int) $f_Priority;
				$res = $db->query("UPDATE `Message" . $classID . "` SET `Priority` = " . ($f_Priority) . ", `LastUpdated` = `LastUpdated`
		  WHERE `Message_ID` = '" . $message . "'");
			}

			if ($resMsg) {
				if ($cc_env['File_Mode']) {
					$nc_parent_field_path = $file_class->get_parent_fiend_path('EditActionTemplate');
					$nc_field_path = $file_class->get_field_path('EditActionTemplate');
					// check and include component part
					try {
						if ( nc_check_php_file($nc_field_path) ) {
							include $nc_field_path;
						}
					}
					catch (Exception $e) {
						if ( is_object($perm) && $perm->isSubClassAdmin($cc) ) {
							// error message
							echo sprintf(CONTROL_CLASS_CLASSFORM_CHECK_ERROR, CONTROL_CLASS_CLASS_FORMS_EDITLASTACTION);
						}
					}
					$nc_parent_field_path = null;
					$nc_field_path = null;
				} else {
					eval("echo \"" . ($editActionTemplate ? $editActionTemplate : nc_fields_action_code('editaction')) . "\";");
				}
			} else {
				echo NETCAT_MODERATION_ERROR_NOOBJCHANGE . "<br/><br/>" . $goBack;
			}
		}
	}

} while (false);

$nc_result_msg = ob_get_clean();

if ($File_Mode) {
    require_once $INCLUDE_FOLDER.'index_fs.inc.php';
    echo $template_header;
    echo $nc_result_msg;
	//echo "1";
    echo $template_footer;
} else {
	$goBackLink1 = "/netcat/message.php?catalogue=" . $catalogue . "&sub=" . $sub . "&cc=" . $cc . "&message=".$message;
    
    eval("echo \"".$template_header."\";");
	echo $nc_result_msg;
	if ($sub!=57) {
		if ($posting == 1) {
			echo "<br><br><a href='". $goBackLink1."'>Вернуться в редактирование товара</a>";
		}
	}
	//echo "2";
    eval("echo \"".$template_footer."\";");
}
?>
