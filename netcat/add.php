<?php

/* $Id: add.php 8158 2012-09-27 11:25:57Z lemonade $ */

$action = "add";

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -2)) . ( strstr(__FILE__, "/") ? "/" : "\\" );
@include_once ($NETCAT_FOLDER . "vars.inc.php");


require ($INCLUDE_FOLDER . "index.php");
require_once($INCLUDE_FOLDER . "classes/nc_imagetransform.class.php");

ob_start();

do {
// security section
    $catalogue = $catalogue + 0;
    $sub = $sub + 0;
    $cc = $cc + 0;
    $classID = $classID + 0;
    $curPos = $curPos + 0;

    $cc_env = $current_cc;
    $to_cc = +$_POST['to_cc'];
    $to_sub = +$_POST['to_sub'];

    $_db_cc = $cc;
    $_db_sub = $sub;
    $_db_catalogue = $catalogue;

    if ($current_cc['SrcMirror']) {
        $mirror_data = $nc_core->sub_class->get_by_id($current_cc['SrcMirror']);
        $cc = $mirror_data['Sub_Class_ID'];
        $sub = $mirror_data['Subdivision_ID'];
        $catalogue = $mirror_data['Catalogue_ID'];
    }

    if (!isset($use_multi_sub_class)) {
        // subdivision multisubclass option
        $use_multi_sub_class = $nc_core->subdivision->get_current("UseMultiSubClass");
    }

	echo "<!--".$classPreview.$current_cc["Class_Template_ID"].$current_cc["Class_ID"]."-->"; //26 регистрация пользователя 
    if ($classPreview == ($current_cc["Class_Template_ID"] ? $current_cc["Class_Template_ID"] : $current_cc["Class_ID"])) {
        $magic_gpc = get_magic_quotes_gpc();
        $addTemplate = $magic_gpc ? stripslashes($_SESSION["PreviewClass"][$classPreview]["AddTemplate"]) : $_SESSION["PreviewClass"][$classPreview]["AddTemplate"];
        $addCond = $magic_gpc ? stripslashes($_SESSION["PreviewClass"][$classPreview]["AddCond"]) : $_SESSION["PreviewClass"][$classPreview]["AddCond"];
        $addActionTemplate = $magic_gpc ? stripslashes($_SESSION["PreviewClass"][$classPreview]["AddActionTemplate"]) : $_SESSION["PreviewClass"][$classPreview]["AddActionTemplate"];
	}
	//echo $addCond;
	//echo $addTemplate; ".eval("if (!\$shop) \$shop=new Netshop(); return \$shop->PrintOrderForm();")."
	
    $alter_goBackLink = "";
    $alter_goBackLink_true = false;

    if (isset($_REQUEST['goBackLink'])) {
        $alter_goBackLink = $_REQUEST['goBackLink'];
        if ($admin_mode && preg_match("/^[\/a-z0-9_-]+\?catalogue=[[:digit:]]+&sub=[[:digit:]]+&cc=[[:digit:]]+(&curPos=[[:digit:]]{0,12})?$/im", $alter_goBackLink))
            $alter_goBackLink_true = true;
        if (!$admin_mode && preg_match("/^[\/a-z0-9_-]+(\.html)?(\?curPos=[[:digit:]]{0,12})?$/im", $alter_goBackLink))
            $alter_goBackLink_true = true;
    }

    if (!$alter_goBackLink_true) {
        if ($admin_mode) {
            $goBackLink = $admin_url_prefix . "?catalogue=" . $catalogue . "&sub=" . $sub . "&cc=" . $cc . "&curPos=" . $curPos;
        } else {
            $goBackLink = $current_catalogue['Hidden_Host'] . $SUB_FOLDER . $current_sub['Hidden_URL'] . (!$user_table_mode ? $current_cc['EnglishName'] . ".html" : "") . ($curPos ? "?curPos=" . $curPos : "");
        }
    } else {
        $goBackLink = $alter_goBackLink;
    }

    $goBack = "<a href='" . $goBackLink . "'>" . NETCAT_MODERATION_BACKTOSECTION . "</a>";

	
	
    $cc_settings = nc_get_visual_settings($cc);
	
	$nc_core->page->set_current_metatags($current_sub);

    if ($posting && $nc_core->token->is_use($action)) {
        if (!$nc_core->token->verify()) {
            echo NETCAT_TOKEN_INVALID;
            break;
        }
    }

    if (!isset($cc_env['File_Mode'])) {
        $Class_Template_ID = nc_Core::get_object()->sub_class->get_by_id($cc, 'Class_Template_ID');
        if (is_array($cc_env)) {
            $cc_env = array_merge($cc_env, nc_get_file_mode_and_file_path($Class_Template_ID ? $Class_Template_ID : $classID));
        } else {
            $cc_env = nc_get_file_mode_and_file_path($Class_Template_ID ? $Class_Template_ID : $classID);
        }
    }

    if ($cc_env['File_Mode']) {
        $file_class = new nc_class_view($CLASS_TEMPLATE_FOLDER, $db);
        $file_class->load($cc_env['Class_Template_ID'] ? $cc_env['Class_Template_ID'] : $cc_env['Class_ID'], $cc_env['File_Path'], $cc_env['File_Hash']);
        require $INCLUDE_FOLDER . "classes/nc_class_aggregator_editor.class.php";
        $nc_class_aggregator = nc_class_aggregator_editor::init($file_class);
        if (is_object($nc_class_aggregator) && +$_REQUEST['nc_get_message_select']) {
            if (!$nc_class_aggregator->ignore_catalogue) {
                $nc_class_aggregator->catalogue_id = $cc_env['Catalogue_ID'];
            }
            
            ob_clean();
            echo $nc_class_aggregator->get_message_select(+$_REQUEST['db_Class_ID'], (array) $_POST['nc_select_attrs'], (array) $_POST['nc_option_attrs'], +$_REQUEST['db_selected']);
            exit;
        }
    }
	
    if ($posting) {
		//echo "!".$posting.$cc_env['File_Mode'];
        if ($cc_env['File_Mode']) {
            $nc_parent_field_path = $file_class->get_parent_fiend_path('AddCond');
            $nc_field_path = $file_class->get_field_path('AddCond');
            // check and include component part
			try {
				if ( nc_check_php_file($nc_field_path) ) {
					include $nc_field_path;
				}
			}
			catch (Exception $e) {
				if ( is_object($perm) && $perm->isSubClassAdmin($cc) ) {
					// do not post this
					$posting = 0;
					// error message
					echo sprintf(CONTROL_CLASS_CLASSFORM_CHECK_ERROR, CONTROL_CLASS_CLASS_FORMS_ADDRULES);
				}
			}
            $nc_parent_field_path = null;
            $nc_field_path = null;
        } else {
			// регистрация пользователя
			//echo "*";
			//echo $addCond;
            eval($addCond);
        }
    }

    require $ROOT_FOLDER . "message_fields.php";

    if (!$posting) {
	
        if ($cc_env['File_Mode']) {
            $addTemplate = file_get_contents($file_class->get_field_path('AddTemplate'));
        }
        if ($addTemplate) {
            if ($warnText) {
                nc_preg_match_all('#\$([a-z0-9_]+)#i', $addTemplate, $all_template_variables);
                foreach ($all_template_variables[1] as $template_variable) {
                    if ($_REQUEST[$template_variable] == $$template_variable) {
                        $$template_variable = stripslashes($$template_variable);
                    }
                }
            }
			if ($cc_env['File_Mode']) {                                
                // обертка для вывода ошибки в админке
                if ($warnText && ($nc_core->inside_admin || $isNaked)) {
                    ob_start();
                    nc_print_status($warnText, 'error');
                    $warnText = ob_get_clean();
                }
                
                $nc_parent_field_path = $file_class->get_parent_fiend_path('AddTemplate');
                $nc_field_path = $file_class->get_field_path('AddTemplate');                                 
                $addForm = '';
				
                // check and include component part
				try {
					if ( nc_check_php_file($nc_field_path) ) {
						ob_start();
						include $nc_field_path;
						$addForm = ob_get_clean();
					}
				}
				catch (Exception $e) {
					if ( is_object($perm) && $perm->isSubClassAdmin($cc) ) {
						// error message
						$addForm = sprintf(CONTROL_CLASS_CLASSFORM_CHECK_ERROR, CONTROL_CLASS_CLASS_FORMS_ADDFORM);
					}
				}
                $nc_parent_field_path = null;
                $nc_field_path = null;
            } else {
                eval("\$addForm = \"" . $addTemplate . "\";");
            }
		
            echo nc_prepare_message_form($addForm, $action, $admin_mode, $user_table_mode, $sys_table_id, $current_cc, $f_Checked = null, $f_Priority = '', $f_Keyword = '', $f_ncTitle = '', $f_ncKeywords = '', $f_ncDescription = '');
        } else {
            require ($ROOT_FOLDER . "message_edit.php");
        }


        if ($inside_admin && $UI_CONFIG && $goBackLink) {
            $UI_CONFIG->actionButtons[] = array("id" => "goback",
                    "caption" => CONTROL_AUTH_HTML_BACK,
                    "align" => 'left',
                    "action" => "mainView.loadIframe('" . $goBackLink . "&inside_admin=1')");
        }
    } else {
        if ($systemTableID == "3")
            $message = NULL;

		//echo $user_table_mode;
			
        include ($ROOT_FOLDER . "message_put.php");
        $IsChecked = 2 - $moderationID;

        if ($admin_mode) {
            $IsChecked = $f_Checked ? 1 : 0;
        }

        if (!$user_table_mode) {
            $f_Parent_Message_ID = (int) $f_Parent_Message_ID;
            $fieldString .= "`Created`, `Parent_Message_ID`, `IP`, `UserAgent`, ";
            $valueString .= "\"" . date("Y-m-d H:i:s") . "\", \"" . $f_Parent_Message_ID . "\", \"" . $db->escape($REMOTE_ADDR) . "\", \"" . $db->escape($HTTP_USER_AGENT) . "\", ";
            $SQL = "INSERT INTO `Message" . $classID . "`
			(`Subdivision_ID`, `Sub_Class_ID`, " . $fieldString . " `Checked`, `Keyword`, `User_ID`)
			VALUES
			(" . ($to_sub ? $to_sub : $sub) . ", " . ($to_cc ? $to_cc : $cc) . ", " . $valueString . $IsChecked . ", '" . ($admin_mode ? $f_Keyword : "") . "', '" . $AUTH_USER_ID . "')";

            $resMsg = $db->query($SQL);
            $msgID = $db->insert_id;
            if (is_array($SQL_multifield)) {
                $SQL_multifield = array_reverse($SQL_multifield);
                $SQL_multifield = str_replace('%msgID%', $msgID, join(', ', $SQL_multifield));
                if ($SQL_multifield) {
                    $SQL = "INSERT INTO Multifield(`Field_ID`, `Message_ID`, `Name`, `Size`, `Path`, `Preview`)
                            VALUES $SQL_multifield";
                    $db->query($SQL);
                }
            }
            if ($f_Priority) {
                $f_Priority = $f_Priority + 0;
                if ($admin_mode) {
                    // get ids
                    $_messages = $db->get_col("SELECT `Message_ID` FROM `Message" . $classID . "`
          WHERE `Priority`>=" . $f_Priority . " AND `Subdivision_ID` = '" . ($to_sub ? $to_sub : $sub) . "' AND `Sub_Class_ID` = '" . ($to_cc ? $to_cc : $cc) . "'");
                    // update info
                    if (!empty($_messages)) {
                        $res = $db->query("UPDATE `Message" . $classID . "`
            SET `Priority` = `Priority` + 1, `LastUpdated` = `LastUpdated`
            WHERE `Message_ID` IN (" . join(", ", $_messages) . ")");
                        // execute core action
                        $nc_core->event->execute("updateMessage", $catalogue, ($to_sub ? $to_sub : $sub), ($to_cc ? $to_cc : $cc), $classID, $_messages);
                    }
                    // for current message
                    $res = $db->query("UPDATE `Message" . $classID . "`
          SET `Priority` = '" . $f_Priority . "', `LastUpdated` = `LastUpdated`
          WHERE `Message_ID` = '" . $msgID . "'");
                }
            } else {
                $maxPriority = $db->get_var("SELECT MAX(`Priority`) FROM `Message" . $classID . "`
				WHERE `Subdivision_ID` = '" . ($to_sub ? $to_sub : $sub) . "' AND `Sub_Class_ID` = '" . ($to_cc ? $to_cc : $cc) . "' AND `Parent_Message_ID` = '" . $f_Parent_Message_ID . "'");
                $res = $db->query("UPDATE `Message" . $classID . "`
				SET `Priority` = " . ($maxPriority + 1) . ", `LastUpdated` = `LastUpdated`
				WHERE `Message_ID` = '" . $msgID . "'");
            }
            // execute core action
            $nc_core->event->execute("addMessage", $catalogue, ($to_sub ? $to_sub : $sub), ($to_cc ? $to_cc : $cc), $classID, $msgID);
        } else {
            $RegistrationCode = md5(uniqid(rand()));
            $IsChecked = ($nc_core->get_settings('premoderation', 'auth') || $nc_core->get_settings('confirm', 'auth') ) ? 0 : 1;
            $groups = explode(",", $nc_core->get_settings('group', 'auth'));
            $mainGroup = intval(min((array) $groups));
            $resMsg = $db->query("INSERT INTO `User`
			(" . $fieldString . "`Password`, `PermissionGroup_ID`,   `Checked`, `Created`, `RegistrationCode`" . ($nc_core->get_settings('confirm', 'auth') ? ", `Confirmed`" : "") . ", Catalogue_ID)
			VALUES
			(" . $valueString . " " . $nc_core->MYSQL_ENCRYPT . "('" . $Password . "'), '" . $mainGroup . "', '" . $IsChecked . "', \"" . date("Y-m-d H:i:s") . "\", '" . $RegistrationCode . "'" . ($nc_core->get_settings('confirm', 'auth') ? ",'0'" : "") . ", " . $catalogue . ")");
            $msgID = $db->insert_id;
            // execute core action
            $nc_core->event->execute("addUser", $msgID);

            //add user to group
            if ($msgID) {
                foreach ((array) $groups as $group_id) {
                    nc_usergroup_add_to_group($msgID, $group_id);
                }
            }
            $ConfirmationLink = "http://" . $HTTP_HOST . $SUB_FOLDER . $HTTP_ROOT_PATH . "modules/auth/confirm.php?id=" . $msgID . "&code=" . $RegistrationCode;
        }
        if (!$message) {
            $message = $msgID;
        }

        if ($filetable_lastid) {
            $resMsgArr = array();
            foreach ($filetable_lastid AS $id) {
                $resMsgArr[] = $id;
            }
            if (!empty($resMsgArr)) {
                $resMsg = $db->query("UPDATE `Filetable` SET `Message_ID` = '" . $message . "' WHERE ID IN (" . join(", ", $resMsgArr) . ")");
            }
        }

        for ($i = 0; $i < count($tmpFile); $i++) {
            # array $File_Path is defined in message_put.php
            # !!possibly we've moved file there already!!
            eval("\$tmpNewFile[\$i] = \"" . $tmpNewFile[$i] . "\";");
            @rename($FILES_FOLDER . $tmpFile[$i], $FILES_FOLDER . $File_Path[$i] . $tmpNewFile[$i]);
            @chmod($FILES_FOLDER . $File_Path[$i] . $tmpNewFile[$i], $FILECHMOD);
        }
        if ($resMsg) {
            if ($cc && !$user_table_mode && $IsChecked && $MODULE_VARS['subscriber']
                    && (!$MODULE_VARS['subscriber']['VERSION'] || $MODULE_VARS['subscriber']['VERSION'] == 1)) {
                eval("\$mailbody = \"" . $subscribeTemplate . "\";");
                subscribe_sendmail(($to_cc ? $to_cc : $cc), $mailbody);
            }

            if ($cc_env['File_Mode']) {
                $nc_parent_field_path = $file_class->get_parent_fiend_path('AddActionTemplate');
                $nc_field_path = $file_class->get_field_path('AddActionTemplate');
                // check and include component part
				try {
					if ( nc_check_php_file($nc_field_path) ) {
						include $nc_field_path;
					}
				}
				catch (Exception $e) {
					if ( is_object($perm) && $perm->isSubClassAdmin($cc) ) {
						// error message
						echo sprintf(CONTROL_CLASS_CLASSFORM_CHECK_ERROR, CONTROL_CLASS_CLASS_FORMS_ADDLASTACTION);
					}
				}
                $nc_parent_field_path = null;
                $nc_field_path = null;
            } else if ($addActionTemplate) {
                eval("echo \"" . $addActionTemplate . "\";");
            } else {
                if ($inside_admin) {
                    ob_end_clean();
                    header("Location: " . $goBackLink . "&inside_admin=1");
                    exit;
                } else {
                    echo ($IsChecked ? NETCAT_MODERATION_MSG_OBJADD : NETCAT_MODERATION_MSG_OBJADDMOD) . "<br/><br/>" . $goBack;
                }
            }
        } else {
            echo NETCAT_MODERATION_ERROR_NOOBJADD . "<br/><br/>" . $goBack;
        }
    }
    $cc_add = $cc;
    if ($cc_array && $use_multi_sub_class && !$inside_admin) {
        foreach ($cc_array AS $cc) {
            if (( $cc && $cc != $cc_add ) || $user_table_mode) {
                $current_cc = $nc_core->sub_class->set_current_by_id($cc);
                echo s_list_class($sub, $cc, $parsed_url['query'] . ($date ? "&date=" . $date : "") . "&isMainContent=1&isSubClassArray=1");
            }
        }
        $current_cc = $nc_core->sub_class->set_current_by_id($cc_add);
    }
} while (false);

$nc_result_msg = ob_get_clean();
if ($catalogue==5) {
	//echo $File_Mode;
	$File_Mode=1;
}
if ($File_Mode) {
    require_once $INCLUDE_FOLDER.'index_fs.inc.php';
    if (!$templatePreview) {
        echo $template_header;
        echo $nc_result_msg;
        echo $template_footer;
    } else {
        eval('?>'.$template_header);
        echo $nc_result_msg;
        eval('?>'.$template_footer);
    }
} else {
	// Get template for the second site (folding-knives.ru) when check order form
	if ($catalogue==2) {
		//echo "0".$catalogue;
		$temp = $db->get_results("SELECT * FROM Template WHERE Template_ID=30");
		//print_r($temp);
		eval("echo \"".$temp[0]->Header."\";");
		//$nc_result_msg=substr_replace($nc_result_msg, "", strpos($nc_result_msg, "<a href='/profile/'>пройдите по ссылке</a>"),-1);
		if (strpos($nc_result_msg, "<a href='/profile/'>")>0) {
			echo "Регистрация прошла успешно. <a href='/cart/'>В корзину</a>";
		} else {
			echo $nc_result_msg;
		}
		
		eval("echo \"".$temp[0]->Footer."\";");
		
	} elseif ($catalogue==3) {
		//echo "0".$catalogue;
		$temp = $db->get_results("SELECT * FROM Template WHERE Template_ID=40");
		//print_r($temp);
		eval("echo \"".$temp[0]->Header."\";");
		//$nc_result_msg=substr_replace($nc_result_msg, "", strpos($nc_result_msg, "<a href='/profile/'>пройдите по ссылке</a>"),-1);
		if (strpos($nc_result_msg, "<a href='/profile/'>")>0) {
			echo "Регистрация прошла успешно. <a href='/cart/'>В корзину</a>";
		} else {
			echo $nc_result_msg;
		}
		
		eval("echo \"".$temp[0]->Footer."\";");
		
	} else {
		eval("echo \"".$template_header."\";");
		//$nc_result_msg=substr_replace($nc_result_msg, "", strpos($nc_result_msg, "<a href='/profile/'>пройдите по ссылке</a>"),-1);
		if (strpos($nc_result_msg, "<a href='/profile/'>")>0) {
		
			echo "Регистрация прошла успешно. <a href='/Netshop/Cart/'>В корзину</a>";
		} else {
		echo $nc_result_msg;
		}
		
		eval("echo \"".$template_footer."\";");
	}
}
?>
