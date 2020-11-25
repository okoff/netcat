<?php
if (!class_exists("nc_System"))
    die("Unable to load file.");
/*
 * Скопируем поля из основного шаблона во вновь созданный
 */

function InsertFieldsFromBaseClass($BaseClassID, $NewClassID) {
    global $db;

    $BaseClassID += 0;
    $NewClassID += 0;

    $db->query("INSERT INTO `Field` (`Class_ID`, `Field_Name`, `Description`, `TypeOfData_ID`, `Format`, `NotNull`, `Priority`, `DoSearch`, `DefaultState`, `Inheritance`, `System_Table_ID`, `TypeOfEdit_ID`)
    SELECT " . $NewClassID . ", `Field_Name`, `Description`, `TypeOfData_ID`, `Format`, `NotNull`, `Priority`, `DoSearch`, `DefaultState`, `Inheritance`, `System_Table_ID`, `TypeOfEdit_ID`
      FROM `Field`
      WHERE `Class_ID` = '" . $BaseClassID . "'");

    $Result = $db->get_results("SELECT `Field_ID` FROM `Field` WHERE `Class_ID` = '" . $NewClassID . "'");
    if (!empty($Result)) {
        foreach ($Result as $Array) {
            ColumnInMessage($Array->Field_ID, 1, $db);
        }
    }
}

/*
 * Скопируем поля действий из основного шаблона во вновь созданный
 */

function InsertActionsFromBaseClass($BaseClassID, $NewClassID) {
    global $nc_core, $db;

    $BaseClassID += 0;
    $NewClassID += 0;

    $ret = false;

    $Result = $db->get_results("SELECT `AddTemplate`, `EditTemplate`, `AddActionTemplate`, `EditActionTemplate`, `SearchTemplate`, `FullSearchTemplate`, `SubscribeTemplate`, `AddCond`, `EditCond`, `SubscribeCond`, `CheckActionTemplate`, `DeleteActionTemplate`
    FROM `Class`
    WHERE `Class_ID` = '" . $BaseClassID . "'", ARRAY_A);

    $q = array();

    if (!empty($Result)) {
        foreach ($Result as $Array) {
            foreach ($Array as $key => $val) {
                if ($val != "") {
                    $q[] = "`" . $key . "` = '" . addslashes($val) . "'";
                    $ret = true;
                }
            }
        }
    }


    if ($ret) {
        $db->query("UPDATE `Class` SET " . join(',', $q) . " WHERE `Class_ID` = '" . $NewClassID . "';");

        $ClassTemplate = $db->get_var("SELECT `ClassTemplate` FROM `Class` WHERE `Class_ID` = '" . $NewClassID . "'");

        // execute core action
        if (!$ClassTemplate) {
            $nc_core->event->execute("updateClass", $NewClassID);
        } else {
            // main class, template class
            $nc_core->event->execute("updateClassTemplate", $ClassTemplate, $NewClassID);
        }
    }
}

##############################################
# Вывод списка групп шаблонов
##############################################

function ClassGroupList() {
    global $db;

    if (($Result = $db->get_results("SELECT DISTINCT `Class_Group` FROM `Class` WHERE `System_Table_ID` = 0 AND `ClassTemplate` = 0 ORDER BY `Class_Group` ASC"))) {
        ?>
        <form method='post' action='index.php'>
            <table border='0' cellpadding='0' cellspacing='0' width='100%'><tr><td>

                        <table class='admin_table'  width='100%'>
                            <tr>
                                <th><?= CONTROL_CLASS_CLASS_GROUPS ?></th>
                            </tr>
                            <?
                            foreach ($Result as $Array) {
                                print "<tr>";
                                print "<td><a href=\"index.php?phase=1&ClassGroup=" . urlencode($Array->Class_Group) . "\">" . $Array->Class_Group . "</a></td>";
                                print "</tr>";
                            }
                            ?>
                        </table>
                    </td></tr></table><br>
            <?
        } else {
            nc_print_status(CONTROL_CLASS_NONE, 'info');
        }
?>
        <a href='index.php?phase=10'><b><?= CONTROL_CLASS_ADD ?></b></a>
<?
    }

/**
 * Вывод списка шаблонов
 * 
 * @param string group name
 */
function ClassList ($Class_Group = false) {
	global $db, $ADMIN_PATH, $ADMIN_TEMPLATE, $UI_CONFIG;
	
	// get nc_core
	$nc_core = nc_Core::get_object();
	
	$file_mode = intval( $nc_core->input->fetch_get_post('fs') );
	
	if ($Class_Group) {
		$select = "SELECT c.`Class_ID`,
		c.`Class_Name`,
		COUNT(f.`Field_ID`) AS Fields,
		IF(c.`AddTemplate` <> ''
			OR c.`AddCond` <> ''
			OR c.`AddActionTemplate` <> '', 1, 0) AS IsAdd,
		IF(c.`EditTemplate` <> ''
			OR c.`EditCond` <> ''
			OR c.`EditActionTemplate` <> ''
			OR c.`CheckActionTemplate` <> '', 1, 0) AS IsEdit,
		IF(c.`SearchTemplate` <> ''
			OR c.`FullSearchTemplate` <> '', 1, 0) AS IsSearch,
		IF(c.`SubscribeTemplate` <> ''
			OR c.`SubscribeCond` <> '', 1, 0) AS IsSubscribe,
		IF(c.`DeleteActionTemplate` <> ''
			OR c.`DeleteTemplate` <> ''
			OR c.`DeleteCond` <> '', 1, 0) as IsDelete
		FROM `Class` AS c
		LEFT JOIN `Field` AS f ON c.`Class_ID` = f.`Class_ID`
		WHERE md5(c.`Class_Group`) = '" . $db->escape($Class_Group) . "'
			AND c.`System_Table_ID` = 0
			AND c.`ClassTemplate` = 0
			AND c.`File_Mode` = '" . $file_mode . "'	 
		GROUP BY c.`Class_ID`
		ORDER BY c.`Class_ID`";
	} else {
		$select = "SELECT c.`Class_ID`,
		c.`Class_Name`,
		COUNT(f.`Field_ID`) AS Fields,
		IF(c.`AddTemplate` <> ''
			OR c.`AddCond` <> ''
			OR c.`AddActionTemplate` <> '', 1, 0) AS IsAdd,
		IF(c.`EditTemplate` <> ''
			OR c.`EditCond` <> ''
			OR c.`EditActionTemplate` <> ''
			OR c.`CheckActionTemplate` <> ''
			OR c.`DeleteActionTemplate` <> '', 1, 0) AS IsEdit,
		IF(c.`SearchTemplate` <> ''
			OR c.`FullSearchTemplate` <> '', 1, 0) AS IsSearch,
		IF(c.`SubscribeTemplate` <> ''
			OR c.`SubscribeCond` <> '', 1, 0) AS IsSubscribe,
			c.`Class_Group`
		FROM `Class` AS c
		LEFT JOIN `Field` AS f ON c.`Class_ID` = f.`Class_ID`
		WHERE c.`System_Table_ID` = 0
			AND c.`ClassTemplate` = 0
			AND c.`File_Mode` = '" . $file_mode . "'
		GROUP BY c.`Class_ID`
		ORDER BY c.`Class_ID`";
	}

	if ( $Result = $db->get_results($select) ):
?>
		<form method='post' action='index.php'>
			<table border='0' cellpadding='0' cellspacing='0' width='100%' class='border-bottom'>
				<tr>
					<td>
<?php
	$action_map = array(
		// array(myaction, title, icon, check_prop)
		array(1, CONTROL_CLASS_ACTIONS_ADD, 'obj_add', 'IsAdd'),
		array(2, CONTROL_CLASS_ACTIONS_EDIT, 'pencil', 'IsEdit'),
		array(5, CONTROL_CLASS_ACTIONS_DELETE, 'delete', 'IsDelete'),
		array(3, CONTROL_CLASS_ACTIONS_SEARCH, 'search', 'IsSearch'),
		array(4, CONTROL_CLASS_ACTIONS_MAIL, 'module_comments', 'IsSubscribe')
	);
?>
						<table class='admin_table' width='100%'>
							<tr>
								<th>ID</th>
								<th width='35%'><?= CONTROL_CLASS_CLASS ?></th>
<?php if (!$Class_Group): ?>
								<th width='35%'><?=CONTROL_USER_GROUP?></th>
<?php endif; ?>
								<th class='align-center' colspan='<?=count($action_map)?>' style='padding: 0;'><?= CONTROL_CLASS_ACTIONS ?></th>
								<th class='align-center' width='10%'><?= CONTROL_CLASS_FIELDS ?></th>

								<td align='center'><div class='icons icon_delete' title='<?= CONTROL_CLASS_DELETE ?>'></div></td>
							</tr>
<?php foreach ($Result as $Array): ?>
							<tr>
							<td><?=$Array->Class_ID?></td>
							<td><a href="index.php?fs=<?=$file_mode?>&phase=4&ClassID=<?=$Array->Class_ID . ($Class_Group ? '&ClassGroup=' . md5($Class_Group) : '')?>"><?=$Array->Class_Name?></a></td>
<?php if (!$Class_Group): ?>
							<td width='1%'><a href="index.php?fs=<?=$file_mode?>&phase=1&ClassGroup=<?=md5($Array->Class_Group)?>"><?=$Array->Class_Group?></a></td>
<?php endif;

	foreach ($action_map as $action_props):
		$action_href = 'index.php?fs='.$file_mode.'&phase=8&ClassID='.$Array->Class_ID.'&myaction='.$action_props[0];
		if ($Class_Group) {
			$action_href .= '&ClassGroup=' . urlencode($Class_Group);
		}
		$check_prop = $action_props[3];
		$is_inactive = !$Array->$check_prop;
?>
							<td width="1%" class="button">
								<a href="<?=$action_href?>"<?=$is_inactive ? ' style="color:#888;"' : ''?> title="<?=$action_props[1]?>">
									<div class="icons icon_<?=$action_props[2] . ($is_inactive ? ' icon_disabled' : '')?>"></div>
								</a>
							</td>
	<?php endforeach; ?>
								
							<td align='center'><a href="<?=$ADMIN_PATH?>field/?ClassID=<?=$Array->Class_ID?>&fs=<?=$file_mode?>"><?=$Array->Fields?> <?=plural_form($Array->Fields, CONTROL_CLASS_FIELD, CONTROL_CLASS_FIELDS, CONTROL_CLASS_FIELDS_COUNT)?></a></td>
							<td align='center'><input type='checkbox' name='Delete<?=$Array->Class_ID?>' value='<?=$Array->Class_ID?>'></td>
							</tr>
<?php endforeach;	?>
						</table>
					</td>
				</tr>
			</table>
			<br />
<?php
	else:
		nc_print_status(CONTROL_CLASS_NONE, 'info');
	endif;

	$UI_CONFIG->actionButtons[] = array(
		"id" => "addClass",
		"caption" => CONTROL_CLASS_FUNCS_SHOWCLASSLIST_ADDCLASS,
		"action" => "urlDispatcher.load('dataclass" . ($file_mode ? '_fs' : '') . ".add($Class_Group)')",
		"align" => "left"
	);
	
	$UI_CONFIG->actionButtons[] = array(
		"id" => "importClass",
		"caption" => CONTROL_CLASS_FUNCS_SHOWCLASSLIST_IMPORTCLASS,
		"action" => "urlDispatcher.load('dataclass" . ($file_mode ? '_fs' : '') . ".import($Class_Group)')",
		"align" => "left"
	);

	if ($Array):
		$UI_CONFIG->actionButtons[] = array(
			"id" => "submit",
			"caption" => NETCAT_ADMIN_DELETE_SELECTED,
			"action" => "mainView.submitIframeForm()"
		);

	if ($Class_Group): ?>
		<input type='hidden' name='ClassGroup' value='<?=$Class_Group?>'>
	<?php endif; ?>
		<input type='hidden' name='fs' value="<?=$file_mode?>">
		<input type='hidden' name='phase' value='6'>
		<input type='submit' class='hidden'>
	</form>
<?php
	endif;
}

    function ClassTemplatesList($Class_ID = 0) {
        global $UI_CONFIG;

        // system superior object
        $nc_core = nc_Core::get_object();
        $Class_ID = intval($Class_ID);
        // system db object
        if (is_object($nc_core->db))
            $db = &$nc_core->db;

        $result = $nc_core->db->get_results("SELECT `Class_ID`, `Class_Name`,
    IF(`AddTemplate` <> '' OR `AddCond` <> '' OR `AddActionTemplate` <> '', 1, 0) AS IsAdd,
    IF(`EditTemplate` <> '' OR `EditCond` <> '' OR `EditActionTemplate` <> '' OR `CheckActionTemplate` <> '', 1, 0) AS IsEdit,
    IF(`SearchTemplate` <> '' OR `FullSearchTemplate` <> '', 1, 0) AS IsSearch,
    IF(`SubscribeTemplate` <> '' OR `SubscribeCond` <> '', 1, 0) AS IsSubscribe,
    IF(`DeleteActionTemplate` <> '' OR `DeleteTemplate` <> '' OR `DeleteCond` <> '', 1, 0) as IsDelete,
    IF( `Type` <> '', `Type`, 'useful') AS `Type`
    FROM `Class`
    WHERE " . ($Class_ID ? "`ClassTemplate` = '" . $Class_ID . "'" : "`ClassTemplate` != 0") .
                "ORDER BY `Class_ID`");

        if (!empty($result)) {
            ?>
            <form method='post' action='index.php'>
                <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                    <tr>
                        <td>
                            <table class='admin_table'  width='100%'>
                                <tr>
                                    <th>ID</th>
                                    <th width='45%'><?= CONTROL_CLASS_CLASS_TEMPLATE ?></th>
                                    <th><?= CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE ?></th>
                                    <th class='align-center'><?= CONTROL_CLASS_ACTIONS ?></th>
                                    <td class='align-center'>
                                        <div class='icons icon_delete' title='<?= CONTROL_CLASS_DELETE ?>'></div>
                                    </td>
                                </tr>
                                <?php
                                foreach ($result as $Array) {
                                    print "<tr>";
                                    print "<td>" . $Array->Class_ID . "</td>";
                                    print "<td><a href='index.php?fs=" . +$_REQUEST['fs'] . "&phase=16&amp;ClassID=" . $Array->Class_ID . "'>" . $Array->Class_Name . "</a></td>";
                                    print "<td>" . constant("CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_" . strtoupper($Array->Type)) . "</td>";
                                    print "
                                        <td align='center'>
                                            <font size='-2'>
                                                <nobr>
                                                    <a href='index.php?fs=" . +$_REQUEST['fs'] . "&phase=22&amp;ClassID=" . $Array->Class_ID . "&amp;myaction=1'>" . (!$Array->IsAdd ? "<font color='gray'>" : "") . CONTROL_CLASS_ACTIONS_ADD . (!$Array->IsAdd ? "</font>" : "") . "</a>&nbsp;&nbsp;
                                                    <a href='index.php?fs=" . +$_REQUEST['fs'] . "&phase=22&amp;ClassID=" . $Array->Class_ID . "&amp;myaction=2'>" . (!$Array->IsEdit ? "<font color='gray'>" : "") . CONTROL_CLASS_ACTIONS_EDIT . (!$Array->IsEdit ? "</font>" : "") . "</a>&nbsp;&nbsp;
                                                    <a href='index.php?fs=" . +$_REQUEST['fs'] . "&phase=22&amp;ClassID=" . $Array->Class_ID . "&amp;myaction=5'>" . (!$Array->IsDelete ? "<font color='gray'>" : "") . CONTROL_CLASS_ACTIONS_DELETE . (!$Array->IsDelete ? "</font>" : "") . "</a>&nbsp;&nbsp;
                                                    <a href='index.php?fs=" . +$_REQUEST['fs'] . "&phase=22&amp;ClassID=" . $Array->Class_ID . "&amp;myaction=3'>" . (!$Array->IsSearch ? "<font color='gray'>" : "") . CONTROL_CLASS_ACTIONS_SEARCH . (!$Array->IsSearch ? "</font>" : "") . "</a>&nbsp;&nbsp;
                                                    <a href='index.php?fs=" . +$_REQUEST['fs'] . "&phase=22&amp;ClassID=" . $Array->Class_ID . "&amp;myaction=4'>" . (!$Array->IsSubscribe ? "<font color='gray'>" : "") . CONTROL_CLASS_ACTIONS_MAIL . (!$Array->IsSubscribe ? "</font>" : "") . "</a>
                                                </nobr>
                                            </font>
                                        </td>";
                                    print "<td align='center'><input type='checkbox' name=\"Delete" . $Array->Class_ID . "\" value='" . $Array->Class_ID . "'></td>";
                                    print "</tr>";
                                }
                                ?>
                            </table>
                        </td>
                    </tr>
                </table>
                <br/>
                <?php
            } else {
                nc_print_status(CONTROL_CLASS_CLASS_TEMPLATE_ERROR_NOT_FOUND, 'info');
            }

            if ($Array) {
                $UI_CONFIG->actionButtons[] = array(
                        "id" => "submit",
                        "align" => "left",
                        "caption" => CONTROL_CLASS_CLASS_TEMPLATE_ADD,
                        "location" => "classtemplate".(+$_REQUEST['fs'] ? '_fs' : '').".add(" . $Class_ID . ")"
                );
                $UI_CONFIG->actionButtons[] = array(
                        "id" => "submit",
                        "caption" => NETCAT_ADMIN_DELETE_SELECTED,
                        "action" => "mainView.submitIframeForm()"
                );
            }

            if ($Array) {
                ?>
                <input type='hidden' name='phase' value='18' />
                <input type='hidden' name='ClassTemplate' value='<?= $Class_ID ?>' />
                <input type='hidden' name='fs' value='<?= +$_REQUEST['fs']; ?>' />
                <input type='submit' class='hidden' />
            </form>
            <?php
        }
    }

    /*
     * Component add/edit form
     *
     * @param int component ID (0 if action "add")
     * @param string action, for example "index.php"
     * @param int phase from index.php
     * @param int operation type, 1 - insert, 2 - update, 3 - update for System_Table (ClassID==SystemTableID)
     *
     * @return HTML code into the output buffer
     */

    function ClassForm($ClassID, $action, $phase, $type, $BaseClassID) {
        global $ROOT_FOLDER, $ClassGroup, $ADMIN_PATH, $UI_CONFIG;

        // system superior object
        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;

        // compile main MySQL query
        $select = "SELECT `Class_ID`, `Class_Name`, `DaysToHold`, `AllowTags`, `NL2BR`, `System_Table_ID`, `File_Hash`, ";
        $select.= "`FormPrefix`, `FormSuffix`, `RecordTemplate`, `RecordsPerPage`, ";
        $select.= "`SortBy`, `RecordTemplateFull`, `TitleTemplate`, `UseAltTitle`, `TitleList`, `Settings`, `Class_Group`, `UseCaptcha`, `CustomSettingsTemplate`, `ClassDescription`, `ClassTemplate`, `Type` ";
        if ($nc_core->modules->get_by_keyword("cache"))
            $select.= ", `CacheForUser`";
        $select.= "FROM `Class` WHERE ";

        if ($BaseClassID) {
            $type_o = $type;
            $type = 2;
            $ClassID = $BaseClassID;
        }

        $File_Mode = nc_get_file_mode('Class', $ClassID);

        if ($File_Mode) {
            $class_editor = new nc_class_editor($nc_core->CLASS_TEMPLATE_FOLDER, $nc_core->db);
        }
        
        if ($_POST['Class_Group_New'] && $ClassID) {
            ?>
            <script>
                parent.window.frames[0].window.location.href += '&selected_node=dataclass-<?= $ClassID; ?>';
            </script>
            <?
        }
        
        ?> <form method='post' id='ClassForm' action='<?= $action ?>'>
        <?
        if ($File_Mode) {
            ?> <input type="hidden" value="1" name="fs" /> <?
    } else {
        echo "<br /><div>" . CONTROL_CLASS_INFO_ADDSLASHES . "</div>";
    }

    if ($type == 1) {
        if (!$nc_core->input->fetch_post()) {
            if (!$Array->Class_Name)
                $Array->Class_Name = CONTROL_CLASS_NEWCLASS;
            if (!$Array->FormPrefix) {
                $Array->FormPrefix = "\$f_AdminCommon";
                if ($File_Mode) {
                    $Array->FormPrefix = '<? echo ' . $Array->FormPrefix . '; ?>';
                }
            }
            if (!$Array->RecordTemplate)
                $Array->RecordTemplate = "\$f_AdminButtons";
            if (!$Array->RecordsPerPage)
                $Array->RecordsPerPage = "20";
            if (!$Array->Class_Group) {
                $Array->Class_Group = $db->get_var("SELECT `Class_Group` FROM `Class` WHERE md5(`Class_Group`) = '" . $ClassGroup . "'");
            }
            if ($File_Mode) {
                $Array->RecordTemplate = '<? echo ' . $Array->RecordTemplate . '; ?>';
            }
        } else {
            $Array->FormPrefix = $nc_core->input->fetch_post('FormPrefix');
            $Array->FormSuffix = $nc_core->input->fetch_post('FormSuffix');
            $Array->RecordTemplate = $nc_core->input->fetch_post('RecordTemplate');
            $Array->RecordTemplateFull = $nc_core->input->fetch_post('RecordTemplateFull');
            $Array->Settings = $nc_core->input->fetch_post('Settings');
            $Array->Class_Name = $nc_core->input->fetch_post('Class_Name');
            $Array->Class_Group = $nc_core->input->fetch_post('Class_Group');
            $Array->Class_Group_New = $nc_core->input->fetch_post('Class_Group_New');
            $Array->RecordsPerPage = $nc_core->input->fetch_post('RecordsPerPage');
            $Array->SortBy = $nc_core->input->fetch_post('SortBy');
            $Array->AllowTags = $nc_core->input->fetch_post('AllowTags');
            $Array->NL2BR = $nc_core->input->fetch_post('NL2BR');
            $Array->TitleTemplate = $nc_core->input->fetch_post('TitleTemplate');
            $Array->TitleList = $nc_core->input->fetch_post('TitleList');
            $Array->UseAltTitle = $nc_core->input->fetch_post('UseAltTitle');
            $Array->UseCaptcha = $nc_core->input->fetch_post('UseCaptcha');
            $Array->CustomSettingsTemplate = $nc_core->input->fetch_post('CustomSettingsTemplate');
            $Array->ClassDescription = $nc_core->input->fetch_post('ClassDescription');

            if ($nc_core->modules->get_by_keyword("cache")) {
                $Array->CacheForUser = $nc_core->input->fetch_post('CacheForUser');
            }
        }
    } elseif ($type == 2) {
        $select .= " `Class_ID` = '" . $ClassID . "'";
        $Array = $db->get_row($select);

        if ($ClassGroup) {
            $Array->Class_Group = $db->get_var("SELECT `Class_Group` FROM `Class` WHERE md5(`Class_Group`) = '" . $ClassGroup . "'");
        }
        if ($phase == 5) {
            if ($ClassGroup)
                $Array->Class_Group = $ClassGroup;
        }
        if (!$Array)
            nc_print_status(CONTROL_CLASS_ERRORS_DB, 'error');
    }
    elseif ($type == 3) {
        $select .= " `System_Table_ID` = '" . $ClassID . "' AND `ClassTemplate` = 0 AND File_Mode = " . +$_REQUEST['fs'];
        $Array = $db->get_row($select);
        if (!$Array)
            nc_print_status(CONTROL_CLASS_ERRORS_DB, 'error');
    }

    if ($File_Mode && ($type == 2 || $type == 3)) {
        $class_editor->load($Array->Class_ID, null, $Array->File_Hash);
        $class_editor->fill_fields();
        $class_fields = $class_editor->get_fields();
        $Array->FormPrefix = $class_fields['FormPrefix'];
        $Array->FormSuffix = $class_fields['FormSuffix'];
        $Array->RecordTemplate = $class_fields['RecordTemplate'];
        $Array->RecordTemplateFull = $class_fields['RecordTemplateFull'];
        $Array->Settings = $class_fields['Settings'];
        
        $class_absolute_path = $class_editor->get_absolute_path();
        $class_filemanager_link = $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . "modules/filemanager/admin.php?page=manager&phase=1&dir=" . $nc_core->SUB_FOLDER . $nc_core->HTTP_TEMPLATE_PATH . 'class' . $class_editor->get_relative_path();
        
        echo "<br />".PHP_EOL."<div>".sprintf(CONTROL_CLASS_CLASSFORM_TEMPLATE_PATH, $class_filemanager_link, $class_absolute_path)."</div>";
    }

    if ($type == 1 && !$Array->Settings && $File_Mode) {
        $Array->Settings = "<?php\n\n\n?>";
    }

    $Array->RecordTemplate = nc_cleaned_RecordTemplate_of_string_service($Array->RecordTemplate);
    if ($type == 1 || $BaseClassID) {
        echo "<h2>".CONTROL_CLASS_CLASSFORM_INFO_FOR_NEWCLASS."</h2>";
         ?>
            </div>
                <?php

                    echo CONTROL_CLASS_CLASS_NAME.":<br/>";
                    echo "<input type='text' name='Class_Name' size='50' value=\"".htmlspecialchars_decode($Array->Class_Name)."\"><br/><br/>";

                    // if not component template - show groups
                    if (!($Array->ClassTemplate || $phase == 15)) {
                        $classGroups = $db->get_col("SELECT DISTINCT `Class_Group` FROM `Class`");
                        if (!empty($classGroups)) {
                            echo CONTROL_USER_GROUP.":<br/><select name='Class_Group' style='width:auto;'>\n";
                            foreach ($classGroups as $Class_Group) {
                                if ($Array->Class_Group == $Class_Group) {
                                    echo("\t<option value='".$Class_Group."' selected='selected'>".$Class_Group."</option>\n");
                                } else {
                                    echo("\t<option value='".$Class_Group."'>".$Class_Group."</option>\n");
                                }
                            }
                            echo "</select>&nbsp;&nbsp;&nbsp;";
                        }
                        unset($classGroups);

                        echo CONTROL_CLASS_NEWGROUP."&nbsp;&nbsp;&nbsp;<input type='text' name='Class_Group_New' size='25' maxlength='64' value='".htmlspecialchars_decode($Array->Class_Group_New)."'><br/><br/>";
                    } else {
                        echo CONTROL_USER_GROUP.": ".CONTROL_CLASS_CLASS_TEMPLATE_GROUP."";
                        echo "<input type='hidden' name='Class_Group' value='".CONTROL_CLASS_CLASS_TEMPLATE_GROUP."'>";
                    }

                    if ($Array->ClassTemplate) {
                        if (!$Array->Type) $Array->Type = 'useful';
                        echo "<br/> ".CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE.":  ";
                        echo ''.constant("CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_".strtoupper($Array->Type)).'';
                    }
                    if ($nc_core->modules->get_by_keyword("cache")) {
                    ?>
                    <table border='0' cellpadding='0' cellspacing='0' width='98%'>
                        <tr>
                            <td style='border: none;'>
                                <?= CONTROL_CLASS_CACHE_FOR_AUTH ?>*:<br/>
                                <select name='CacheForUser' style='width:320px; margin-right: 5px;'>
                                    <option value='0'<?= (!$CacheForUser ? " selected" : "") ?>><?= CONTROL_CLASS_CACHE_FOR_AUTH_NONE ?></option>
                                    <option value='1'<?= ($CacheForUser == 1 ? " selected" : "") ?>><?= CONTROL_CLASS_CACHE_FOR_AUTH_USER ?></option>
                                    <option value='2'<?= ($CacheForUser == 2 ? " selected" : "") ?>><?= CONTROL_CLASS_CACHE_FOR_AUTH_GROUP ?></option>
                                </select><br/>
                                * <?= CONTROL_CLASS_CACHE_FOR_AUTH_DESCRIPTION ?>
                            </td>
                        </tr>
                    </table>
                    <br/>
                <? } ?>
                <br/>
                <?php
            } else {
                ?> <input type="hidden" value="<?php echo $Array->Class_Name ? $Array->Class_Name : $_GET['Class_Name']; ?>" name="Class_Name" /> <?
            }

            $set = $nc_core->get_settings();
            if($set['CMEmbeded']) {
				?>
                                <div id="classFields" class="completionData" style="display:none"></div>
                                <div id="classCustomSettings" class="completionData" style="display:none"></div>
                                <script>
				   $nc('#classFields').data('completionData', $nc.parseJSON("<?=addslashes(json_encode(getCompletionDataForClassFields($ClassID)))?>"));
				   $nc('#classCustomSettings').data('completionData', $nc.parseJSON("<?=addslashes(json_encode(getCompletionDataForClassCustomSettings($ClassID)))?>"));
				</script>
				<?
			   }
            ob_start();
            ?>

            <table border='0' cellpadding='0' cellspacing='0' width='99%'>
                <tr>
                    <td  style='border: none;'>
                        <?= CONTROL_CLASS_CLASS_OBJECTSLIST_TITLE ?>:<br>
                        <input type='text' name='TitleList' size='50' maxlength='255' value="<?=htmlspecialchars_decode($Array->TitleList) ?>">
                        <br />&nbsp;
                    </td>
                </tr>
                <tr>
                    <td  style='border: none;'>
                        <?= CONTROL_CLASS_CLASS_OBJECTSLIST_PREFIX ?>:<br/>
                        <textarea id='ListPrefix' wrap='OFF' rows='10' cols='60' name='FormPrefix'><?=htmlspecialchars_decode($Array->FormPrefix)?></textarea>
                        <br />&nbsp;
                    </td>
                </tr>
                <tr>
                    <td  style='border: none;'>
                        <?= CONTROL_CLASS_CLASS_OBJECTSLIST_BODY ?>:<br/>
                        <textarea id='ListBody' wrap='OFF' rows='10' cols='60' name='RecordTemplate'><?=htmlspecialchars_decode($Array->RecordTemplate)?></textarea>
                        <br />&nbsp;
                    </td>
                </tr>
                <tr>
                    <td  style='border: none;'>
                        <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SUFFIX?>:<br/>
                        <textarea id='ListSuffix' wrap='OFF' rows='10' cols='60' name='FormSuffix'><?=htmlspecialchars_decode($Array->FormSuffix)?></textarea>
                        <br />&nbsp;
                    </td>
                </tr>
                <tr>
                    <td  style='border: none;'>
                        <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOW?>
						<input type='text' name='RecordsPerPage' SIZE='4' maxlength='255' value="<?=htmlspecialchars_decode($Array->RecordsPerPage)?>"> <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ
                        ?><br />&nbsp;
                    </td>
                </tr>
                <tr>
                    <td style='border: none;'>
                        <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SORT
                        ?>*:<br/><input id='SortBy' type='text' name='SortBy' size='50' maxlength='255' value="<?=htmlspecialchars_decode($Array->SortBy)?>"><br/>
                        * <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SORTNOTE?>
                    </td>
                </tr>
            </table>

            <?

            $fieldset = new nc_admin_fieldset(CONTROL_CLASS_CLASS_OBJECTSLIST);
            echo $fieldset->add(ob_get_clean())->result();

            ob_start();

            ?>

            <table border=0 cellpadding=0 cellspacing=0 width=98%>
                <tr>
                    <td  style='border: none;'>
                        <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_PAGETITLE; ?>:<br />
                        <input type='text' name='TitleTemplate' size='50' maxlength='255' value="<?= htmlspecialchars_decode($Array->TitleTemplate)
                        ?>">
                    </td>
                </tr>
                <tr>
                    <td style='border: none;'>
                        <input type='checkbox' name='UseAltTitle' id='UseAltTitle'  value='1' <?= ($Array->UseAltTitle ? "checked" : ""); ?> />
                        <label for='UseAltTitle'><?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_USEASALT; ?></label>
                        <br /><br />
                    </td>
                </tr>
                <tr>
                    <td  style='border: none;'>
                        <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_PAGEBODY; ?>:<br />
                        <textarea id='PageBody' wrap='OFF' rows='10' cols='60' name='RecordTemplateFull'><?= htmlspecialchars_decode($Array->RecordTemplateFull); ?></textarea>
                    </td>
                </tr>
            </table>
            <?

            $fieldset = new nc_admin_fieldset(CONTROL_CLASS_CLASS_OBJECTVIEW);
            echo $fieldset->add(ob_get_clean())->result();

            ob_start();

            ?>

            <table border='0' cellpadding='0' cellspacing='0' width='99%'>
                <tr>
                    <td colspan='2'  style='border: none;'>
                        <input type='checkbox' id='tags' name='AllowTags' <?= ($Array->AllowTags ? "checked" : "") ?> value='1' />
                        <label for='tags'><?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_HTML ?></label>
                    </td>
                </tr>
                <tr>
                    <td colspan='2' style='border: none;'>
                        <input type='checkbox' id='br' name='NL2BR' <?= ($Array->NL2BR ? "checked" : "") ?> value='1' />
                        <label for='br'><?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_BR ?></label>
                    </td>
                </tr>
                <tr>
                    <td colspan='2' style='border: none;'>
                        <input type='checkbox' id='captcha' name='UseCaptcha' <?= ($Array->UseCaptcha ? "checked" : "") ?> value='1' />
                        <label for='captcha'><?= CONTROL_CLASS_USE_CAPTCHA ?></label>
                        <br /><br />
                    </td>
                </tr>
                <tr>
                    <td colspan='2' style='border: none;'>
                        <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_SYSTEM ?>:<br/><textarea id='Settings' wrap='OFF' rows='8' cols='60' name='Settings'><?= htmlspecialchars_decode($Array->Settings) ?></textarea>
                    </td>
                </tr>

                <tr  style="display:none">
                    <td colspan='2' style='border: none;'>
                        <input type='hidden' name='DaysToHold' size='4' value="<?= htmlspecialchars_decode($Array->DaysToHold) ?>" />
                    </td>
                </tr>
                <?= ($type == 2 && !$BaseClassID && !($Array->ClassTemplate || $phase == 15) ? "
    <tr><td colspan='2'  style='border: none;'>
      <a href='ExportToFile.php?ClassID=" . $ClassID . "&amp;" . $nc_core->token->get_url() . "'>" . CONTROL_CLASS_EXPORT . "</a>
    </td></tr>" : "") ?>
            </table>
            <?

            $fieldset = new nc_admin_fieldset(CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_DOPL);
            echo $fieldset->add(ob_get_clean())->result();

            ?>

            <div align='right'>
                <?php
                if ($type == 1 || $BaseClassID) {
                    $UI_CONFIG->actionButtons[] = array(
                            "id" => "submit",
                            "caption" => $phase == 15 ? CONTROL_CLASS_CLASS_TEMPLATE_ADD : CONTROL_CLASS_ADD,
                            "action" => "mainView.submitIframeForm()"
                    );
                } elseif ($type > 1) {
                    $UI_CONFIG->actionButtons[] = array(
                            "id" => "submit",
                            "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SAVE,
                            "action" => 'return false;" id="nc_class_save'
                    );
                    // add component template button
                    if (!($Array->ClassTemplate || $phase == 15)) {
                        $UI_CONFIG->actionButtons[] = array(
                                "id" => "submit",
                                "align" => "left",
                                "caption" => CONTROL_CLASS_CLASS_TEMPLATE_ADD,
                                "location" => "classtemplate".(+$_REQUEST['fs'] ? '_fs' : '').".add(" . ($type == 3 ? $Array->Class_ID : $ClassID) . ")"
                        );
                    }
                    global $system_env;
                    if ($system_env['SyntaxCheck']) {
                        $UI_CONFIG->actionButtons[] = array(
                                "id" => "debug",
                                "caption" => NETCAT_DEBUG_BUTTON_CAPTION,
                                "action" => "document.getElementById('mainViewIframe').contentWindow.FormAsyncDebug()"
                        );
                    }
                    $UI_CONFIG->actionButtons[] = array(
                            "id" => "preview",
                            "caption" => NETCAT_PREVIEW_BUTTON_CAPTIONCLASS,
                            "align" => "left",
                            "action" => "document.getElementById('mainViewIframe').contentWindow.SendClassPreview('','../../index.php')"
                    );
                }
                ?>
            </div>

            <?php

            nc_print_admin_save_scritp('ClassForm');

            // Используется для мастера создания шаблонов
            global $Class_Type;
            echo "<input type='hidden' name='Class_Type' value='" . $Class_Type . "'>\n";
            if ($BaseClassID) {
                print "<input type='hidden' name='BaseClassID' value='" . $BaseClassID . "'>\n";
            } else {
                print "<input type='hidden' name='ClassID' value='" . $ClassID . "'>\n";
            }

            print $nc_core->token->get_input();
            if ($Array->System_Table_ID)
                print "<input type='hidden' name='System_Table_ID' value='" . $Array->System_Table_ID . "'>\n";
            ?>


            <input type='hidden' name='ClassGroup' value='<?= $ClassGroup ?>'>
            <input type='hidden' name='phase' value='<?= $phase ?>'>
            <input type='hidden' name='type' value='<?= ($BaseClassID ? 1 : $type) ?>'>
            <?php
            if ($phase == 15)
                echo "<input type='hidden' name='ClassTemplate' value='" . $BaseClassID . "'>";
            ?>
            <?php
            if ($Array->ClassTemplate)
                echo "<input type='hidden' name='ClassTemplate' value='" . $Array->ClassTemplate . "'>";
            ?>
            <input type='submit' class='hidden'>
        </form>

        <!--  START   REMIND_SAVE -->
        <script type='text/javascript'>remind_redaction();</script>
        <!-- END REMIND_SAVE -->
        <?
    }

    function ClassForm_developer_mode($ClassID) {
        global $ROOT_FOLDER, $ADMIN_PATH;

        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;

        $SQL = "SELECT `Class_ID`,
                       `Class_Name`,
                       `DaysToHold`,
                       `AllowTags`,
                       `NL2BR`,
                       `System_Table_ID`,
                       `File_Hash`,
                       `File_Mode`,
                       `File_Path`,
                       `FormPrefix`,
                       `FormSuffix`,
                       `RecordTemplate`,
                       `RecordsPerPage`,
                       `SortBy`,
                       `RecordTemplateFull`,
                       `TitleTemplate`,
                       `UseAltTitle`,
                       `TitleList`,
                       `Settings`,
                       `Class_Group`,
                       `UseCaptcha`,
                       `CustomSettingsTemplate`,
                       `ClassDescription`,
                       `ClassTemplate`,
                       `Type`,
                       `AddTemplate`,
                       `AddCond`,
                       `AddActionTemplate`,
                       `EditTemplate`,
                       `EditCond`,
                       `EditActionTemplate`,
                       `CheckActionTemplate`,
                       `DeleteTemplate`,
                       `DeleteCond`,
                       `DeleteActionTemplate`,
                       `SearchTemplate`,
                       `FullSearchTemplate`,
                       `SubscribeTemplate`,
                       `SubscribeCond`
                       " . ($nc_core->modules->get_by_keyword("cache") ? ', `CacheForUser`' : '') . "
                    FROM `Class`
                        WHERE `Class_ID` = " . $ClassID;

        $Array = $db->get_row($SQL);
        $sysTable = +$Array->System_Table_ID;
        $File_Mode = $Array->File_Mode;
        $File_input = '';

        if ($File_Mode) {
            $class_editor = new nc_class_editor($nc_core->CLASS_TEMPLATE_FOLDER, $nc_core->db);
            $class_editor->load($ClassID, $Array->File_Path, $Array->File_Hash);
            $class_editor->fill_fields();
            $class_fields = $class_editor->get_fields();

            foreach ($class_fields as $field => $content) {
                $Array->$field = $field == 'RecordTemplate' ? nc_cleaned_RecordTemplate_of_string_service($content) : $content;
            }

            $File_input = "<input type='hidden' value='1' name='fs' />";
        }

        if (!$Array) {
            nc_print_status(CONTROL_CLASS_ERRORS_DB, 'error');
        }
        
        if ($GLOBALS["AJAX_SAVER"]) { ?>
			<script>
				var formAsyncSaveEnabled = true;
				var NETCAT_HTTP_REQUEST_SAVING = "<?= str_replace('"', "&quot;", NETCAT_HTTP_REQUEST_SAVING) ?>";
				var NETCAT_HTTP_REQUEST_SAVED  = "<?= str_replace('"', "&quot;", NETCAT_HTTP_REQUEST_SAVED) ?>";
				var NETCAT_HTTP_REQUEST_ERROR  = "<?= str_replace('"', "&quot;", NETCAT_HTTP_REQUEST_ERROR) ?>";
			</script>
		<? } else { ?>
			<script>var formAsyncSaveEnabled = false;</script>
		<? }
        
        ?>

        <div class='nc_admin_form_menu' style='padding-top: 20px;'>
            <h2><?= $Array->Class_Name; ?></h2>
            <div id='nc_object_slider_menu' class='slider_block_2' style='padding-top: 0px; padding-bottom: 15px;'>
                <ul>
                    <li id='nc_class_main' class='button_on'><?= CONTROL_CLASS_CLASS ?></li>
                    <li id='nc_class_add'><?= CONTROL_CLASS_ACTIONS_ADD ?></li>
                    <li id='nc_class_edit'><?= CONTROL_CLASS_ACTIONS_EDIT ?></li>
                    <li id='nc_class_del'><?= CONTROL_CLASS_ACTIONS_DELETE ?></li>
                    <li id='nc_class_search'><?= CONTROL_CLASS_ACTIONS_SEARCH ?></li>
                </ul>
            </div>
            <div class='nc_admin_form_menu_hr'></div>
        </div>

        <script>
            var nc_slider_li = $nc('div#nc_object_slider_menu ul li');

            nc_slider_li.click(function() {
                nc_slider_li.removeClass('button_on');
                $nc(this).addClass('button_on');
                $nc('form#adminForm > div > div').addClass('nc_class_none');
                $nc('form#adminForm > div > div#' + this.id + '_div').removeClass('nc_class_none').find('textarea').codemirror(nc_cmConfig);
            });
        </script>

        <div class='nc_admin_form_body'>

            <form method='post' id='adminForm' class="ClassForm" action='<?= $nc_core->SUB_FOLDER; ?>/netcat/admin/class/index.php'>

                <div id='nc_class_add_div' class='nc_class_none'>

                    <?php
                        print CONTROL_CLASS_CLASS_FORMS_ADDFORM
                            . " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'AddTemplate'); return false;\">"
                            . CONTROL_CLASS_CLASS_FORMS_ADDFORM_GEN . "</a>)" . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='AddTemplate' id='AddTemplate' "
                            . ">" . htmlspecialchars_decode($Array->AddTemplate) . "</TEXTAREA><br><br>";

                        print CONTROL_CLASS_CLASS_FORMS_ADDRULES
                            . " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'AddCond'); return false;\">"
                            . CONTROL_CLASS_CLASS_FORMS_ADDCOND_GEN . "</a>)" . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='AddCond' id='AddCond'>"
                            . htmlspecialchars_decode($Array->AddCond) . "</TEXTAREA><br><br>";

                        print CONTROL_CLASS_CLASS_FORMS_ADDLASTACTION
                            . " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'AddActionTemplate'); return false;\">"
                            . CONTROL_CLASS_CLASS_FORMS_ADDACTION_GEN . "</a>)" . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='AddActionTemplate' id='AddActionTemplate'>"
                            . htmlspecialchars_decode($Array->AddActionTemplate) . "</TEXTAREA><br><br>";
                    ?>
                </div>

                <div id='nc_class_edit_div' class='nc_class_none'>
                    <?php
                        print CONTROL_CLASS_CLASS_FORMS_EDITFORM
                            . " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'EditTemplate'); return false;\">"
                            . CONTROL_CLASS_CLASS_FORMS_EDITFORM_GEN . "</a>)" . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='EditTemplate' id='EditTemplate' "
                            . ">" . htmlspecialchars_decode($Array->EditTemplate) . "</TEXTAREA><br><br>";

                        print CONTROL_CLASS_CLASS_FORMS_EDITRULES
                            . " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'EditCond'); return false;\">"
                            . CONTROL_CLASS_CLASS_FORMS_EDITCOND_GEN . "</a>)" . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='EditCond' id='EditCond'>"
                            . htmlspecialchars_decode($Array->EditCond) . "</TEXTAREA><br><br>";

                        print CONTROL_CLASS_CLASS_FORMS_EDITLASTACTION
                            .  " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'EditActionTemplate'); return false;\">"
                            . CONTROL_CLASS_CLASS_FORMS_EDITACTION_GEN . "</a>)" . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='EditActionTemplate' id='EditActionTemplate'>"
                            . htmlspecialchars_decode($Array->EditActionTemplate) . "</TEXTAREA><br><br>";

                        print CONTROL_CLASS_CLASS_FORMS_ONONACTION
                            . " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'CheckActionTemplate'); return false;\">"
                            . CONTROL_CLASS_CLASS_FORMS_CHECKACTION_GEN . "</a>)" . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='CheckActionTemplate' id='CheckActionTemplate'>"
                            . htmlspecialchars_decode($Array->CheckActionTemplate) . "</TEXTAREA><br><br>";
                    ?>
                </div>

                <div id='nc_class_del_div' class='nc_class_none'>
                    <?php
                        print CONTROL_CLASS_CLASS_FORMS_DELETEFORM
                            . " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'DeleteTemplate'); return false;\">"
                            . CONTROL_CLASS_CLASS_FORMS_EDITFORM_GEN . "</a>)" . "<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='DeleteTemplate' id='DeleteTemplate'>"
                            . htmlspecialchars_decode($Array->DeleteTemplate) . "</TEXTAREA><br><br>";

                        print CONTROL_CLASS_CLASS_FORMS_DELETERULES
                            . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='DeleteCond' id='DeleteCond'>"
                            . htmlspecialchars_decode($Array->DeleteCond) . "</TEXTAREA><br><br>";

                        print CONTROL_CLASS_CLASS_FORMS_ONDELACTION
                            . " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'DeleteActionTemplate'); return false;\">"
                            . CONTROL_CLASS_CLASS_FORMS_DELETEACTION_GEN . "</a>)" . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='DeleteActionTemplate' id='DeleteActionTemplate'>"
                            . htmlspecialchars_decode($Array->DeleteActionTemplate) . "</TEXTAREA><br><br>";
                    ?>
                </div>

                <div id='nc_class_search_div' class='nc_class_none'>
                    <?php
                        print CONTROL_CLASS_CLASS_FORMS_QSEARCH
                            . " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'FullSearchTemplate'); return false;\">"
                            . CONTROL_CLASS_CLASS_FORMS_QSEARCH_GEN . "</a>)" . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='FullSearchTemplate' id='FullSearchTemplate'>"
                            . htmlspecialchars_decode($Array->FullSearchTemplate) . "</TEXTAREA><br><br>";

                        print CONTROL_CLASS_CLASS_FORMS_SEARCH
                            . " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'SearchTemplate'); return false;\">"
                            . CONTROL_CLASS_CLASS_FORMS_SEARCH_GEN . "</a>)" . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS=60 NAME='SearchTemplate' id='SearchTemplate'>"
                            . htmlspecialchars_decode($Array->SearchTemplate) . "</TEXTAREA><br><br>";
                    ?>
                </div>

                <div id='nc_class_main_div'>
                    <?= $File_input; ?>
                    <input type="hidden" value="<?php echo $Array->Class_Name ? $Array->Class_Name : $_GET['Class_Name']; ?>" name="Class_Name" />
                    <div id="classFields" style="display:none"><?= GetFieldsByClassId($ClassID) ?></div>

                    <h2><?= CONTROL_CLASS_CLASS_OBJECTSLIST ?></h2>
                    <table border='0' cellpadding='0' cellspacing='0' width='99%'>
                        <tr>
                            <td  style='border: none;'>
                                <?= CONTROL_CLASS_CLASS_OBJECTSLIST_TITLE ?>:<br>
                                <input type='text'name='TitleList' size='50' maxlength='255' value="<?= htmlspecialchars_decode($Array->TitleList) ?>"><br />
                                <br />
                            </td>
                        </tr>
                        <tr>
                            <td  style='border: none;'>
                                <?= CONTROL_CLASS_CLASS_OBJECTSLIST_PREFIX ?>:<br/>
                                <textarea id='ListPrefix' wrap='OFF' rows='10' cols='60' name='FormPrefix'><?= htmlspecialchars_decode($Array->FormPrefix)
                                ?></textarea><br />
                            </td>
                        </tr>
                        <tr>
                            <td  style='border: none;'>
                                <?= CONTROL_CLASS_CLASS_OBJECTSLIST_BODY ?>:<br/>
                                <textarea id='ListBody' wrap='OFF' rows='10' cols='60' name='RecordTemplate'><?= htmlspecialchars_decode($Array->RecordTemplate)
                                ?></textarea><br />
                            </td>
                        </tr>
                        <tr>
                            <td  style='border: none;'>
                                <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SUFFIX?>:<br/>
                                <textarea id='ListSuffix' wrap='OFF' rows='10' cols='60' name='FormSuffix'><?= htmlspecialchars_decode($Array->FormSuffix)
                                ?></textarea><br />
                            </td>
                        </TR>
                        <tr>
                            <td  style='border: none;'>
                                <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOW?> <input type='text'name='RecordsPerPage' SIZE='4' maxlength='255' value="<?= htmlspecialchars_decode($Array->RecordsPerPage)
                                ?>"> <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ ?><br/>
                                <br/>
                            </td>
                        </tr>
                        <tr>
                            <td style='border: none;'>
                                <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SORT
                                ?>*:<br/><input id='SortBy' type='text'name='SortBy' size='50' maxlength='255' value="<?= htmlspecialchars_decode($Array->SortBy)
                                ?>"><br/>
                                * <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SORTNOTE
                                ?>
                            </td>
                        </tr>
                    </table>
                    <br/>

                    <h2><?= CONTROL_CLASS_CLASS_OBJECTVIEW?></h2>
                    <table border=0 cellpadding=6 cellspacing=0 width=99%>
                        <tr>
                            <td  style='border: none;'>
                                <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_PAGETITLE
                                ?>:<br /><input type='text'name='TitleTemplate' size='50' maxlength='255' value="<?= htmlspecialchars_decode($Array->TitleTemplate)
                                ?>"><br />
                            </td>
                        </tr>
                        <tr>
                            <td style='border: none;'>
                                <input type='checkbox' name='UseAltTitle' id='UseAltTitle'  value='1' <?= ($Array->UseAltTitle ? "checked" : "")
                                ?>  /><label for='UseAltTitle'><?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_USEASALT
                                ?></label>
                            </td>
                        </tr>
                        <tr>
                            <td  style='border: none;'>
                                <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_PAGEBODY?>:<br />
                                <textarea id='PageBody' wrap='OFF' rows='10' cols='60' name='RecordTemplateFull'><?= htmlspecialchars_decode($Array->RecordTemplateFull)
                                ?></textarea><br />
                            </td>
                        </tr>
                    </table>

                    <h2><?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_DOPL ?></h2>
                    <table border='0' cellpadding='6' cellspacing='0' width='99%'>
                        <tr>
                            <td colspan='2'  style='border: none;'>
                                <input type='checkbox' id='tags' name='AllowTags' <?= ($Array->AllowTags ? "checked" : "") ?> value='1' /> <label for='tags'><?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_HTML ?></label>
                            </td>
                        </tr>
                        <tr>
                            <td colspan='2' style='border: none;'>
                                <input type='checkbox' id='br' name='NL2BR' <?= ($Array->NL2BR ? "checked" : "") ?> value='1' /> <label for='br'><?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_BR ?></label>
                            </td>
                        </tr>
                        <tr>
                            <td colspan='2' style='border: none;'>
                                <input type='checkbox' id='captcha' name='UseCaptcha' <?= ($Array->UseCaptcha ? "checked" : "") ?> value='1' /> <label for='captcha'><?= CONTROL_CLASS_USE_CAPTCHA ?></label>
                            </td>
                        </tr>
                        <tr>
                            <td colspan='2' style='border: none;'>
                                <?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_SYSTEM ?>:<br/><textarea id='Settings' wrap='OFF' rows='8' cols='60' name='Settings'><?= htmlspecialchars_decode($Array->Settings) ?></textarea><br />
                            </td>
                        </tr>

                        <tr  style="display:none">
                            <td colspan='2' style='border: none;'>
                                <input type='hidden' name='DaysToHold' size='4' value="<?= htmlspecialchars_decode($Array->DaysToHold) ?>" />
                            </td>
                        </tr>
                    </table>
                    <br/>
                    <?php
                    echo "<input type='hidden' name='Class_Type' value='" . $Class_Type . "'>\n";
                    echo "<input type='hidden' name='ClassID' value='" . $ClassID . "'>\n";
                    echo $nc_core->token->get_input();

                    if ($Array->System_Table_ID)
                        print "<input type='hidden' name='System_Table_ID' value='" . $Array->System_Table_ID . "'>\n";
                    ?>

                    <input type='hidden' name='phase' value='5' />
                    <input type='hidden' name='type' value='2' />
                    <input type='hidden' name='admin_mode' value='1' />
                    <input type='hidden' name='isNaked' value='1' />
                    <?php
                    if ($Array->ClassTemplate)
                        echo "<input type='hidden' name='ClassTemplate' value='" . $Array->ClassTemplate . "'>";
                    ?>
                </div>
            </form>
            <?=include_cd_files()?>
        </div>
        <div class='nc_admin_form_buttons'>
            <input class='nc_admin_metro_button' type='button' value='<?= NETCAT_REMIND_SAVE_SAVE; ?>' title='<?= NETCAT_REMIND_SAVE_SAVE; ?>' />
            <input class='nc_admin_metro_button_cancel' style='color: black; margin-right: 16px; background-color: #EEEEEE; border: 1px solid red;' type='button' value='<?= CONTROL_BUTTON_CANCEL ?>' title='<?= CONTROL_BUTTON_CANCEL ?>' />
        </div>
        
        <style>
            a { color:#1a87c2; }
            a:hover { text-decoration:none; }
            a img { border:none; }
            p { margin:0px; padding:0px 0px 18px 0px; }
            h2 { font-size:20px; font-family:'Segoe UI', SegoeWP, Arial; color:#333333; font-weight:normal; margin:0px; padding:20px 0px 10px 0px; line-height:20px; }
            form { margin:0px; padding:0px; }
            input { outline:none; }
            .clear { margin:0px; padding:0px; font-size:0px; line-height:0px; height:1px; clear:both; float:none; }
            select, input, textarea { border:1px solid #dddddd; }
            :focus { outline:none;}
            .input { outline:none; border:1px solid #dddddd; }
        </style>
        
        <script>
            var nc_admin_metro_buttons = $nc('.nc_admin_metro_button');

            $nc(function() {
                $nc('#adminForm').html('<div class="nc_admin_form_main">' + $nc('#adminForm').html() + '</div>');
            });

            nc_admin_metro_buttons.click(function() {
                 $nc('#adminForm').submit();
            });
        </script>
        <?
    }

##############################################
# Добавление/изменение шаблона
##############################################

    function ActionClassComleted($type, $parentID = false) {
        global $nc_core, $db;

        $ClassID = $nc_core->input->fetch_get_post('ClassID');
        $Class_Name = $nc_core->input->fetch_get_post('Class_Name');
        $Class_Group_New = $nc_core->input->fetch_get_post('Class_Group_New');
        $Class_Group = $nc_core->input->fetch_get_post('Class_Group');
        $ClassTemplate = $nc_core->input->fetch_get_post('ClassTemplate');
        $params = $nc_core->input->fetch_post();

        if ($type == 1) {
            // создание шаблона на основе другого компонента
            if (false != $parentID) {
                $File_Mode = nc_get_file_mode('Class', $parentID);
                $template = array();                
                if ($File_Mode) {
                        $class_editor = new nc_class_editor($nc_core->CLASS_TEMPLATE_FOLDER, $nc_core->db);
                        $class_editor->load($parentID);
                        $class_editor->fill_fields();
                        $template = $class_editor->get_fields();
                }
                else {
                    $template = $nc_core->component->get_by_id($parentID);
                }                
                $params += $template;
            }

            return $nc_core->component->add($Class_Name, ( $Class_Group_New ? $Class_Group_New : $Class_Group), $params, $ClassTemplate ? $ClassTemplate : 0);
        }

        if ($type == 3) {
            $ClassID = $db->get_var("
                SELECT `Class_ID`
                    FROM `Class`
                        WHERE `System_Table_ID` = '" . intval($ClassID) . "'
                          AND `ClassTemplate` = 0
                          AND `File_Mode` = " . +$_REQUEST['fs']);
        }

        $input = $nc_core->input->fetch_post();

        if ($input['Class_Group_New']) {
            $input['Class_Group'] = $input['Class_Group_New'];
        }

        foreach (array('UseAltTitle', 'AllowTags', 'NL2BR', 'UseCaptcha') as $v) {
            $input[$v] += 0;
        }

        return $nc_core->component->update($ClassID, $input);
    }

    /*
     * Вывод списка шаблонов при создании нового
     */

    function addNewTemplate($Class_Group = "") {
        global $db, $UI_CONFIG, $ADMIN_PATH;

        $File_Mode = nc_get_file_mode('Class');

        $fs_input = '';
        $SQL_where = '`File_Mode` = ' . $File_Mode;

        if ($File_Mode) {
            $fs_input = "<input type='hidden' name='fs' value='1'>";
        }

        $classes = $db->get_results("SELECT `Class_ID` AS value,
		CONCAT(`Class_ID`, '. ', `Class_Name`) AS description,
		`Class_Group` AS optgroup
		FROM `Class`
                WHERE $SQL_where
                      AND Type != 'trash'
		ORDER BY `Class_Group`, `Class_ID`", ARRAY_A);
        ?>

            <h2><?= CONTROL_CLASS_CLASS_CREATENEW_BASICOLD ?></h2>
            <form method='get' action=''>
                <?= $fs_input ?>
                <table border='0' cellpadding='0' cellspacing='0'>
                     <tr>
                        <td width='80%'>
                            <?
                            echo "<select name='BaseClassID'>";
                            echo "<option value='0'>" . CONTROL_CLASS_CLASS_CREATENEW_CLEARNEW . "</option>";
                            if (!empty($classes))
                                echo nc_select_options($classes);
                            echo "</select>";
                            ?>
                        </td>
                        <td>&nbsp;</td>
                    </tr>
                </table>
                <?
                $UI_CONFIG->actionButtons[] = array(
                        "id" => "submit",
                        "caption" => CONTROL_CLASS_CONTINUE,
                        "action" => "mainView.submitIframeForm()"
                );
                if ($Class_Group) {
                    print "<input type='hidden' name='ClassGroup' value='" . $Class_Group . "'>";
                }
                ?>
                <input type='hidden' name='action_type' value=3 />
                <input type='hidden' name='phase' value='2'>
                <input type='submit' class='hidden'>
            </form>

        <?
    }

##############################################
# Подтверждение удаления
##############################################

    function ConfirmDeletion($Class_Group = '') {
        global $db;
        global $UI_CONFIG;
        $ask = false;

        $class_id = 0;
        $class_id_array = array();
        print "<form method='post' action='index.php'>";

        $nc_core = nc_Core::get_object();
        $template_class_id_array = array();

        $input = $nc_core->input->fetch_get_post();

        if (!empty($input)) {
            foreach ($input as $key => $val) {
                if (nc_substr($key, 0, 6) == "Delete" && $val) {
                    $ask = true;

                    $class_id = intval($val);

                    $SelectArray = $db->get_var("SELECT `Class_Name` FROM `Class` WHERE `Class_ID`='" . $class_id . "'");					
					// check template existence
					if (!$SelectArray) {
						nc_print_status( sprintf(CONTROL_CLASS_CLASS_NOT_FOUND, $class_id), 'error');
						continue;
					}
					
					$class_id_array[] = $class_id;
					
                    print "<input type='hidden' name='" . $key . "' value='" . $val . "'>";
                    $class_counter++;

                    $template_ids = $db->get_col("SELECT Class_ID FROM Class WHERE ClassTemplate = '" . $class_id . "'");
                    if ($template_ids)
                        $template_class_id_array = array_merge($template_class_id_array, $template_ids);
                }
            }
        }

        if (!$ask)
            return false;

        if ($class_counter > 1) {
            $UI_CONFIG = new ui_config_class("delete", "", $ClassGroup);
            $post_f1 = CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_WARNING_SITEDELETE_I;
            $post_f2 = CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_WARNING_SITEDELETE_U;
        } else {
            print "<input type='hidden' name='ClassGroup' value='" . $db->get_var("SELECT md5(`Class_Group`) FROM `Class` WHERE `Class_ID` = '" . $class_id . "' GROUP BY `Class_Group`") . "'>";
            $UI_CONFIG = new ui_config_class('delete', $class_id, $ClassGroup);
        }

        print $nc_core->token->get_input();
        print "<input type='hidden' name='fs' value='".$_REQUEST['fs']."'>".
            "<input type='hidden' name='phase' value='7'>".
            "</form>";
            
        if ( !empty($class_id_array) ):
			nc_print_status(CONTROL_CLASS_CLASS_DELETE_WARNING, 'info', array($post_f1, $post_f2));
			nc_list_class_use($class_id_array, 0, 0);
			if ($template_class_id_array) {
				echo "<br/>";
				nc_list_class_template_use($template_class_id_array);
			}
        endif;
        
        $UI_CONFIG->actionButtons[] = array(
				"id" => "submit",
				"caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_CONFIRMDELETE,
				"action" => "mainView.submitIframeForm()"	
		);
        
        return true;
    }

    function ConfirmClassTemplateDeletion($ClassTemplate = 0) {
        global $UI_CONFIG;

        // system superior object
        $nc_core = nc_Core::get_object();

        // system db object
        if (is_object($nc_core->db))
            $db = &$nc_core->db;

        $ask = false;
        $class_id = 0;
        $class_id_array = array();
        $ClassTemplate = intval($ClassTemplate);

        print "<form method='post' action='index.php'>";

        $need_arr = $nc_core->input->fetch_get_post();

        if (!empty($need_arr)) {
            foreach ($need_arr as $key => $val) {
                if (substr($key, 0, 6) == "Delete" && $val) {
                    $ask = true;

                    $class_id = intval($val);
                    $class_id_array[] = $class_id;

                    print "<input type='hidden' name='" . $key . "' value='" . $val . "'>";
                    $class_counter++;
                }
            }
        }

        if (!$ask)
            return false;

        if ($ClassTemplate) {
            $BaseClassName = $db->get_var("SELECT `Class_Name` FROM `Class` WHERE `Class_ID` = '" . $ClassTemplate . "'");
        } else {
            list($ClassTemplate, $BaseClassName) = $nc_core->db->get_row("SELECT mc.`Class_ID`, mc.`Class_Name`
				FROM `Class` AS tc
				LEFT JOIN `Class` AS mc ON tc.`ClassTemplate` = mc.`Class_ID`
				WHERE tc.`Class_ID` = '" . $class_id . "'", ARRAY_N);
        }
		
		// check template existence
		if (!$BaseClassName) {
			nc_print_status( sprintf(CONTROL_CLASS_CLASS_TEMPLATE_NOT_FOUND, $class_id), 'error');
		}
		else {
			if ($class_counter > 1) {
				$UI_CONFIG = new ui_config_class_template("delete", 0, $ClassTemplate);
			} else {
				$UI_CONFIG = new ui_config_class_template('delete', $class_id);
			}
			// notice
			nc_print_status(sprintf(CONTROL_CLASS_CLASS_TEMPLATE_DELETE_WARNING, $BaseClassName), 'info');
		}
        
        print $nc_core->token->get_input();
        ?>
        <input type='hidden' name='phase' value='19' />
        <input type='hidden' name='ClassTemplate' value='<?= $ClassTemplate ?>' />
        <input type='hidden' name='fs' value='<?= +$_REQUEST['fs']; ?>' />
    </form>
    <?php
    
    if ($BaseClassName) {
		nc_list_class_template_use($class_id_array, 0);
    }
    
    $UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_CONFIRMDELETE,
            "action" => "mainView.submitIframeForm()"
    );
}

/*
 * Удаление шаблона
 */

function CascadeDeleteClass($ClassID) {
    global $nc_core, $db;
    global $UI_CONFIG, $INCLUDE_FOLDER;

    $ClassID = intval($ClassID);

    $File_Mode = nc_get_file_mode('Class', $ClassID);

    if ($File_Mode) {
        $class_editor = new nc_class_editor($nc_core->CLASS_TEMPLATE_FOLDER, $nc_core->db);
        $class_editor->load($ClassID);
        $class_editor->delete_template();
    }

    // удаление шаблонов
    $template_ids = $db->get_col("SELECT Class_ID FROM Class WHERE ClassTemplate = '" . $ClassID . "'");
    if (!empty($template_ids))
        foreach ($template_ids as $v) {
            CascadeDeleteClassTemplate($v);
        }



    $ClassGroup = $db->get_var("SELECT `Class_Group` FROM `Class` WHERE `Class_ID` = '" . $ClassID . "'");
    $isMoreClasses = $db->get_var("SELECT COUNT(*) - 1 FROM `Class` WHERE `Class_Group` = '" . $ClassGroup . "'");

    //$LockTables = "LOCK TABLES `Class` WRITE, `Field` WRITE,";
    //$LockTables.= "`Message".$ClassID."` WRITE,";
    //$LockTables.= "`Sub_Class` WRITE";
    //$LockResult = $db->query($LockTables.$AddLockTables);
    // get ids
    $messages = $db->get_results("SELECT sc.`Catalogue_ID`, m.`Subdivision_ID`, m.`Sub_Class_ID`, m.`Message_ID`, m.*
    FROM `Message" . $ClassID . "` AS m
    LEFT JOIN `Sub_Class` AS sc ON sc.`Sub_Class_ID` = m.`Sub_Class_ID`
    ORDER BY sc.`Catalogue_ID`, m.`Subdivision_ID`, m.`Sub_Class_ID`", OBJECT);

    if ($messages) {
        $messages_data = array_combine($db->get_col(NULL, 3), $db->get_results(NULL));
    }
    //костыль, для $messages_data нужен массив объектов

    $messages = $db->get_results("SELECT sc.`Catalogue_ID`, m.`Subdivision_ID`, m.`Sub_Class_ID`, m.`Message_ID`, m.*
    FROM `Message" . $ClassID . "` AS m
    LEFT JOIN `Sub_Class` AS sc ON sc.`Sub_Class_ID` = m.`Sub_Class_ID`
    ORDER BY sc.`Catalogue_ID`, m.`Subdivision_ID`, m.`Sub_Class_ID`", ARRAY_N);

    // delete messages
    $db->query("DROP TABLE `Message" . $ClassID . "`");
    // call event
    if (!empty($messages)) {
        $catalogue = $messages[0][0];
        $sub = $messages[0][1];
        $cc = $messages[0][2];
        $messages_arr = array($messages[0][3]);
        $messages_data_arr = array($messages_data[$messages[0][3]]);
        foreach ($messages as $value) {
            if ($value[0] != $catalogue || $value[1] != $sub || $value[2] != $cc) {
                // execute core action
                $nc_core->event->execute("dropMessage", $catalogue, $sub, $cc, $ClassID, $messages_arr, $messages_data_arr);
                $catalogue = $value[0];
                $sub = $value[1];
                $cc = $value[2];
                $messages_arr = array($value[3]);
                $messages_data_arr = array($messages_data[$value[3]]);
            } else {
                $messages_arr[] = $value[3];
                $messages_data_arr[] = $messages_data[$value[3]];
            }
        }
    }

    // delete fields
    $db->query("DELETE FROM `Field` WHERE `Class_ID` = '" . $ClassID . "'");

    $subclasses = $db->get_results("SELECT `Catalogue_ID`, `Subdivision_ID`, `Sub_Class_ID`, `Class_ID` FROM `Sub_Class`
    WHERE `Class_ID` = '" . $ClassID . "'", ARRAY_N);
    // delete subclasses
    if (!empty($subclasses)) {
        foreach ($subclasses as $subclass) {
            // delete related files
            require_once ($INCLUDE_FOLDER . "s_files.inc.php");
            DeleteSubClassFiles($subclass[2], $subclass[3]);
            $db->query("DELETE FROM `Sub_Class` WHERE `Sub_Class_ID` = '" . $subclass[2] . "'");
            // execute core action
            $nc_core->event->execute("dropSubClass", $subclass[0], $subclass[1], $subclass[2]);
        }
    }

    $db->query("DELETE FROM `Class` WHERE `Class_ID` = '" . $ClassID . "'");
    // execute core action
    $nc_core->event->execute("dropClass", $ClassID);

    //$UnlockResult = $db->query("UNLOCK TABLES");

    if (!$isMoreClasses) {
        $UI_CONFIG->treeChanges['deleteNode'][] = "group-" . md5($ClassGroup);
    } else {
        $UI_CONFIG->treeChanges['deleteNode'][] = "dataclass-" . $ClassID;
    }

    return $isMoreClasses;
}

function CascadeDeleteClassTemplate($ClassTemplateID) {
    global $UI_CONFIG;

    // system superior object
    $nc_core = nc_Core::get_object();

    // system db object
    if (is_object($nc_core->db))
        $db = &$nc_core->db;

    $ClassTemplateID = intval($ClassTemplateID);

    $File_Mode = nc_get_file_mode('Class', $ClassTemplateID);
    if ($File_Mode) {
        $class_editor = new nc_class_editor($nc_core->CLASS_TEMPLATE_FOLDER, $nc_core->db);
        $class_editor->load($ClassTemplateID);
        $class_editor->delete_template();
    }

    list($mainComponentID, $type) = $nc_core->db->get_row("SELECT `ClassTemplate`, `Type` FROM `Class`
    WHERE `Class_ID` = '" . $ClassTemplateID . "'", ARRAY_N);
    $isMoreClassTemplates = 0;
    if ($mainComponentID) {
        $isMoreClassTemplates = $db->get_var("SELECT COUNT(*) - 1 FROM `Class` WHERE `ClassTemplate` = '" . $mainComponentID . "'");
    }

    $added_sql = '';
    if ($type == 'rss')
        $added_sql = " `AllowRSS` = 0 ";
    if ($type == 'xml')
        $added_sql = " `AllowXML` = 0 ";

    if ($added_sql) {
        $subclasses = $nc_core->db->get_results("SELECT `Catalogue_ID`, `Subdivision_ID`, `Sub_Class_ID` FROM `Sub_Class`
    WHERE `Class_ID` = '" . $mainComponentID . "' ", ARRAY_N);
        if (!empty($subclasses))
            foreach ($subclasses as $subclass) {
                $nc_core->db->query("UPDATE `Sub_Class` SET " . $added_sql . "
        WHERE `Sub_Class_ID` = '" . $subclass[2] . "'");
                // execute core action
                $nc_core->event->execute("updateSubClass", $subclass[0], $subclass[1], $subclass[2]);
            }
    }

    $subclasses = $nc_core->db->get_results("SELECT `Catalogue_ID`, `Subdivision_ID`, `Sub_Class_ID` FROM `Sub_Class`
    WHERE `Class_Template_ID` = '" . $ClassTemplateID . "'", ARRAY_N);
    // update subclasses
    if (!empty($subclasses)) {
        foreach ($subclasses as $subclass) {
            $nc_core->db->query("UPDATE `Sub_Class` SET `Class_Template_ID` = '0'
        WHERE `Sub_Class_ID` = '" . $subclass[2] . "'");
            // execute core action
            $nc_core->event->execute("updateSubClass", $subclass[0], $subclass[1], $subclass[2]);
        }
    }

    $nc_core->db->query("DELETE FROM `Class` WHERE `Class_ID` = '" . $ClassTemplateID . "'");
    // execute core action
    $nc_core->event->execute("dropClassTemplate", $mainComponentID, $ClassTemplateID);

    if (!$isMoreClassTemplates && $mainComponentID) {
        $UI_CONFIG->treeChanges['deleteNode'][] = "classtemplates-" . $mainComponentID;
    } else {
        $UI_CONFIG->treeChanges['deleteNode'][] = "classtemplate-" . $ClassTemplateID;
    }

    return $nc_core->db->rows_affected;
}

/**
 * Форма действий шаблона
 *
 * @param int $ClassID or SystemTableID
 * @param string action -
 * @param int $phase
 * @param int type: 1 - class, 2 - system table
 * @param int myaction: 1 - add, 2 - edit, 3 - search, 4 - subscribe, 5 - delete
 */
function ClassActionForm($ClassID, $action, $phase, $type, $myaction, $isNaked = false) {
    global $ClassGroup, $SystemTableID, $user_table_mode;
    global $UI_CONFIG;

    $nc_core = nc_Core::get_object();
    $db = $nc_core->db;

    if (!$ClassID) {
        print nc_print_status(CONTROL_CLASS_ERRORS_DB, 'error');
        return;
    }

    if (!$isNaked) {
        ?>
        <form method='post' id="ClassForm" action='<?= $action ?>'><?
    }
?>

        <font color='gray'>

        <?php
        $select = "SELECT `AddTemplate`,
                          `AddCond`,
                          `AddActionTemplate`,
                          `EditTemplate`,
                          `EditCond`,
                          `EditActionTemplate`,
                          `CheckActionTemplate`,
                          `DeleteTemplate`,
                          `DeleteCond`,
                          `DeleteActionTemplate`,
                          `SearchTemplate`,
                          `FullSearchTemplate`,
                          `SubscribeTemplate`,
                          `SubscribeCond`,
                          `System_Table_ID`,
                          `File_Mode`,
                          `File_Path`";


        // class or system table
        $select.= ( $type == 1) ? " FROM `Class` WHERE `Class_ID` = "
                                : " FROM `Class` WHERE `File_Mode` = " . +$_REQUEST['fs'] . " AND `ClassTemplate` = 0 AND `System_Table_ID` = ";
        $select.= "'" . intval($ClassID) . "'";

        if (!$Array = $nc_core->db->get_row($select)) {
            print nc_print_status(CONTROL_CLASS_ERRORS_DB, "error");
            exit();
        }

        $show_generate_link = false;

        if (!$SystemTableID || $SystemTableID == 3) {
            $show_generate_link = true;
            $sysTable = $SystemTableID ? $SystemTableID : 0;
        }

        $SystemTableID != $Array->System_Table_ID && $type == 1 ? $sysTable = $Array->System_Table_ID : "";

        $classTemplate = ($type == 1 ? $nc_core->component->get_by_id($ClassID, "ClassTemplate") : 0);

        $File_Mode = nc_get_file_mode('Class');
        $File_Mode = $File_Mode ? $File_Mode : $Array->File_Mode;

        echo "<input type='hidden' name='fs' value='$File_Mode'>";

        if ($File_Mode) {
            if (true || !$classTemplate) {
                $class_editor = new nc_class_editor($nc_core->CLASS_TEMPLATE_FOLDER, $nc_core->db);
                $class_editor->load($ClassID, $Array->File_Path);
                $class_editor->fill_fields();
                $class_fields = $class_editor->get_fields();
                foreach ($class_fields as $key => $val) {
                    $Array->$key = $val;
                }
            }
        }
        // Add, edit, delete, search or subscribe
        switch ($myaction) {
            // add
            case 1:
                if ($type == 1) {
                    if (!$classTemplate) {
                        $UI_CONFIG = new ui_config_class('customadd', $ClassID);
                    } else {
                        $UI_CONFIG = new ui_config_class_template('customadd', $ClassID);
                    }
                }

                if ($type == 2) {
                    $UI_CONFIG = new ui_config_system_class('customadd', $SystemTableID);
                }

                echo "<br />";

                print CONTROL_CLASS_CLASS_FORMS_ADDFORM . ($show_generate_link ? " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'AddTemplate'); return false;\">" . CONTROL_CLASS_CLASS_FORMS_ADDFORM_GEN . "</a>)" : "") . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='AddTemplate' id='AddTemplate'>" . htmlspecialchars_decode($Array->AddTemplate) . "</TEXTAREA><br><br>";
                print CONTROL_CLASS_CLASS_FORMS_ADDRULES . ($show_generate_link ? " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'AddCond'); return false;\">" . CONTROL_CLASS_CLASS_FORMS_ADDCOND_GEN . "</a>)" : "") . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='AddCond' id='AddCond'>" . htmlspecialchars_decode($Array->AddCond) . "</TEXTAREA><br><br>";
                print CONTROL_CLASS_CLASS_FORMS_ADDLASTACTION . ($show_generate_link ? " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'AddActionTemplate'); return false;\">" . CONTROL_CLASS_CLASS_FORMS_ADDACTION_GEN . "</a>)" : "") . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='AddActionTemplate' id='AddActionTemplate'>" . htmlspecialchars_decode($Array->AddActionTemplate) . "</TEXTAREA><br><br>";
                print "<script>remind_add();</script>";
                print_bind();
                break;
            // edit
            case 2:
                if ($type == 1) {
                    if (!$classTemplate) {
                        $UI_CONFIG = new ui_config_class('customedit', $ClassID);
                    } else {
                        $UI_CONFIG = new ui_config_class_template('customedit', $ClassID);
                    }
                }

                if ($type == 2) {
                    $UI_CONFIG = new ui_config_system_class('customedit', $SystemTableID);
                }

                echo "<br />";

                print CONTROL_CLASS_CLASS_FORMS_EDITFORM . "" . ($show_generate_link ? " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'EditTemplate'); return false;\">" . CONTROL_CLASS_CLASS_FORMS_EDITFORM_GEN . "</a>)" : "") . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='EditTemplate' id='EditTemplate'>" . htmlspecialchars_decode($Array->EditTemplate) . "</TEXTAREA><br><br>";
                print CONTROL_CLASS_CLASS_FORMS_EDITRULES . ($show_generate_link ? " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'EditCond'); return false;\">" . CONTROL_CLASS_CLASS_FORMS_EDITCOND_GEN . "</a>)" : "") . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='EditCond' id='EditCond'>" . htmlspecialchars_decode($Array->EditCond) . "</TEXTAREA><br><br>";
                print CONTROL_CLASS_CLASS_FORMS_EDITLASTACTION . ($show_generate_link ? " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'EditActionTemplate'); return false;\">" . CONTROL_CLASS_CLASS_FORMS_EDITACTION_GEN . "</a>)" : "") . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='EditActionTemplate' id='EditActionTemplate'>" . htmlspecialchars_decode($Array->EditActionTemplate) . "</TEXTAREA><br><br>";
                print CONTROL_CLASS_CLASS_FORMS_ONONACTION . ($show_generate_link ? " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'CheckActionTemplate'); return false;\">" . CONTROL_CLASS_CLASS_FORMS_CHECKACTION_GEN . "</a>)" : "") . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='CheckActionTemplate' id='CheckActionTemplate'>" . htmlspecialchars_decode($Array->CheckActionTemplate) . "</TEXTAREA><br><br>";
                print_bind();
                break;
            // search
            case 3:
                if ($type == 1) {
                    if (!$classTemplate) {
                        $UI_CONFIG = new ui_config_class('customsearch', $ClassID);
                    } else {
                        $UI_CONFIG = new ui_config_class_template('customsearch', $ClassID);
                    }
                }

                if ($type == 2) {
                    $UI_CONFIG = new ui_config_system_class('customsearch', $SystemTableID);
                }

                echo "<br />";

                print CONTROL_CLASS_CLASS_FORMS_QSEARCH . "" . ($show_generate_link ? " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'FullSearchTemplate'); return false;\">" . CONTROL_CLASS_CLASS_FORMS_QSEARCH_GEN . "</a>)" : "") . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='FullSearchTemplate' id='FullSearchTemplate'>" . htmlspecialchars_decode($Array->FullSearchTemplate) . "</TEXTAREA><br><br>";
                print CONTROL_CLASS_CLASS_FORMS_SEARCH . "" . ($show_generate_link ? " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'SearchTemplate'); return false;\">" . CONTROL_CLASS_CLASS_FORMS_SEARCH_GEN . "</a>)" : "") . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS=60 NAME='SearchTemplate' id='SearchTemplate'>" . htmlspecialchars_decode($Array->SearchTemplate) . "</TEXTAREA><br><br>";
                print "<script>remind_search();</script>";
                print_bind();
                break;
            // subscribe
            case 4:
                if ($type == 1) {
                    if (!$classTemplate) {
                        $UI_CONFIG = new ui_config_class('customsubscribe', $ClassID);
                    } else {
                        $UI_CONFIG = new ui_config_class_template('customsubscribe', $ClassID);
                    }
                }

                if ($type == 2) {
                    $UI_CONFIG = new ui_config_system_class('customsubscribe', $SystemTableID);
                }

                echo "<br />";

                print CONTROL_CLASS_CLASS_FORMS_MAILRULES . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='SubscribeCond' id='SubscribeCond'>" . htmlspecialchars_decode($Array->SubscribeCond) . "</TEXTAREA><br><br>";
                print CONTROL_CLASS_CLASS_FORMS_MAILTEXT . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='SubscribeTemplate' id = 'SubscribeTemplate'>" . htmlspecialchars_decode($Array->SubscribeTemplate) . "</TEXTAREA><br><br>";
                print "<script>remind_subscrib();</script>";
                print_bind();
                break;
            // delete
            case 5:
                if ($type == 1) {
                    if (!$classTemplate) {
                        $UI_CONFIG = new ui_config_class('customdelete', $ClassID);
                    } else {
                        $UI_CONFIG = new ui_config_class_template('customdelete', $ClassID);
                    }
                }

                if ($type == 2) {
                    $UI_CONFIG = new ui_config_system_class('customdelete', $SystemTableID);
                }

                echo "<br />";

                print CONTROL_CLASS_CLASS_FORMS_DELETEFORM . ($show_generate_link ? " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'DeleteTemplate'); return false;\">" . CONTROL_CLASS_CLASS_FORMS_EDITFORM_GEN . "</a>)" : "") . "<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='DeleteTemplate' id='DeleteTemplate'>" . htmlspecialchars_decode($Array->DeleteTemplate) . "</TEXTAREA><br><br>";
                print CONTROL_CLASS_CLASS_FORMS_DELETERULES . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='DeleteCond' id='DeleteCond'>" . htmlspecialchars_decode($Array->DeleteCond) . "</TEXTAREA><br><br>";
                print CONTROL_CLASS_CLASS_FORMS_ONDELACTION . ($show_generate_link ? " (<a href='#' onclick=\"generateForm(" . ($classTemplate ? $classTemplate : $ClassID) . ", " . $sysTable . ", 'DeleteActionTemplate'); return false;\">" . CONTROL_CLASS_CLASS_FORMS_DELETEACTION_GEN . "</a>)" : "") . ":<br><TEXTAREA ROWS='10' WRAP='OFF' COLS='60' NAME='DeleteActionTemplate' id='DeleteActionTemplate'>" . htmlspecialchars_decode($Array->DeleteActionTemplate) . "</TEXTAREA><br><br>";
                print "<script>remind_delete();</script>";
                print_bind();
                break;
        }

        if (!$isNaked) {

            $UI_CONFIG->actionButtons[] = array(
                    "id" => "submit",
                    "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SAVE,
                    "action" => 'return false;" id="nc_class_save'
            );

            global $system_env;
            if ($system_env['SyntaxCheck']) {
                $UI_CONFIG->actionButtons[] = array(
                        "id" => "debug",
                        "caption" => NETCAT_DEBUG_BUTTON_CAPTION,
                        "action" => "document.getElementById('mainViewIframe').contentWindow.FormAsyncDebug()"
                );
            }
            switch ($myaction) {
                case 1:
                    $UI_CONFIG->actionButtons[] = array(
                            "id" => "preview",
                            "caption" => NETCAT_PREVIEW_BUTTON_CAPTIONADDFORM,
                            "align" => "left",
                            "action" => "document.getElementById('mainViewIframe').contentWindow.SendClassPreview('','../../add.php')"
                    );
                    break;
                case 2:
                    $UI_CONFIG->actionButtons[] = array(
                            "id" => "preview",
                            "caption" => NETCAT_PREVIEW_BUTTON_CAPTIONEDITFORM,
                            "align" => "left",
                            "action" => "document.getElementById('mainViewIframe').contentWindow.SendClassPreview('','../../message.php')"
                    );
                    break;
                case 3:
                    $UI_CONFIG->actionButtons[] = array(
                            "id" => "preview",
                            "caption" => NETCAT_PREVIEW_BUTTON_CAPTIONSEARCHFORM,
                            "align" => "left",
                            "action" => "document.getElementById('mainViewIframe').contentWindow.SendClassPreview('','../../search.php')"
                    );
                    break;
            }

            nc_print_admin_save_scritp('ClassForm');

            print $nc_core->token->get_input();
            print "<input type='hidden' name='ClassID' value='" . $ClassID . "'/>\n";
            print "<input type='hidden' name='phase' value='" . $phase . "'/>";
            print "<input type='hidden' name='myaction' value='" . $myaction . "'/>";
            print "<input type='hidden' name='type' value='" . $type . "'/>";
            print "<input type='hidden' name='ClassGroup' value='" . $ClassGroup . "'/>";
            print "<input type='hidden' name='ClassTemplate' value='" . $classTemplate . "'/>";
            print "
	<input type='submit' class='hidden' /><div style='display:none' id='classFields'>" . GetFieldsByClassId($ClassID) . "</div>";
        }

        print "
      </font> ";

        if (!$isNaked) {
            "</form>";
        }
    }


##############################################
# Изменение форм действий шаблона
##############################################

    function ClassActionCompleted($myaction, $type) {
        global $nc_core, $db;
        # type=1 - это шаблон
        # type=2 - это системная таблица
        # при type=2 ClassID - это на самом деле SystemTableID

        $ClassID = intval($nc_core->input->fetch_get_post('ClassID'));

        if ($type == 2) {
            $ClassID = $db->get_var("SELECT `Class_ID` FROM `Class`  WHERE `System_Table_ID` = '" . $ClassID . "' AND `ClassTemplate` = 0 AND File_Mode = " . +$_REQUEST['fs']);
        }

        return $nc_core->component->update($ClassID, $nc_core->input->fetch_post());
    }

    function nc_class_info($class_id, $action, $phase) {
        global $UI_CONFIG;
        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;

        $class_id = intval($class_id);

        $info = $db->get_row("SELECT `ClassDescription`, `CustomSettingsTemplate` FROM `Class` WHERE `Class_ID` = '" . $class_id . "'", ARRAY_A);
        $fields = $db->get_results("SELECT `Field_Name`, `Description` FROM `Field` WHERE `Class_ID` = '" . $class_id . "' ORDER BY `Priority`", ARRAY_A);

        echo ClassInformation($class_id, $action, $phase);

        $UI_CONFIG->actionButtons[] = array(
                "id" => "submit",
                "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SAVE,
                "action" => "mainView.submitIframeForm()"
        );
    }

    /**
     * Show using class
     *
     * @param array | null arrayClassID, if !isset - all class
     * @param int 0 - all, 1 - only used, 2 - only unused
     * @param show "checkbox" for clear subClass
     */
    function nc_list_class_use($arrayClassID, $param_show = 0, $withdeleted = 1) {
        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;
        $colspan = $withdeleted ? 5 : 4;

        if (!empty($arrayClassID)) {
            $arrayClassID = array_map("intval", $arrayClassID);
        }

        $Result = $db->get_results("
            SELECT Class.`Class_ID` AS ClassID,
                   Class.`Class_Name` AS ClassName,
                   Sub_Class.`Sub_Class_ID` AS SubClassID,
                   Sub_Class.`Sub_Class_Name` AS SubClassName,
                   Sub_Class.`EnglishName` AS SubClassKeyword,
                   Subdivision.`Subdivision_Name` AS SubName,
                   Subdivision.`Subdivision_ID` AS SubID,
                   Subdivision.`Hidden_URL` AS SubURL
                FROM `Class`
                  LEFT JOIN `Sub_Class` ON Sub_Class.`Class_ID` = Class.`Class_ID`
                  LEFT JOIN `Subdivision` ON Subdivision.`Subdivision_ID` = Sub_Class.`Subdivision_ID`
                    WHERE " . (!empty($arrayClassID) ? "Class.`Class_ID` IN(" . join(",", $arrayClassID) . ")" : "Class.`System_Table_ID` = '0'
                      AND Class.`ClassTemplate` = '0'" ), ARRAY_A);

        $SQL = "SELECT s.Subdivision_ID,
                       c.Domain
                    FROM Subdivision as s,
                         Catalogue as c
                        WHERE s.Catalogue_ID = c.Catalogue_ID";

        $_domains = $db->get_results($SQL);
        $domains = array();
        foreach ($_domains as $row) {
            $domains[$row->Subdivision_ID] = $row->Domain;
        }

        $curClass = -1;

        if ($withdeleted)
            echo "<form action='index.php' name='del' method='post' id='del'>\n";

        echo "<table border='0' cellpadding='0' cellspacing='0' width='100%'><tr>\n" .
        "<table class='admin_table' width='100%'>\n" .
        "<tr>\n" .
        "<th width='30%'><font size='-1'>" . CONTROL_CLASS . "</th>\n" .
        "<th width='30%'><font size='-1'>" . CONTROL_CONTENT_SUBDIVISION_FUNCS_SECTION . "</th>\n" .
        "<th width='30%'><font size='-1'>" . CONTROL_CONTENT_SUBDIVISION_CLASS . "</th>\n" .
        "<th align='center'><font size='-1'>" . CONTROL_CONTENT_SUBCLASS_TOTALOBJECTS . "</th>\n" .
        ($withdeleted ? "<td align='center'><font size='-1'>" . REPORTS_STAT_CLASS_CLEAR . "</th>\n" : "") .
        "</tr>";

        $str = "";
        foreach ($Result as $row) {

            if ($curClass != $row['ClassID']) {
                switch ($param_show) {
                    case 0:
                        print($str);
                        break;
                    case 1:
                        if ($useSubClass)
                            print($str);
                        break;
                    case 2:
                        if (!$useSubClass)
                            print($str);
                        break;
                }

                //так сделано, чтобы для первого класса не выводился раздилитель
                //$str = $divider;

                $str = "<tr>";
                //$divider = "<tr><td colspan='" . $colspan . "' height='2px' bgcolor='#CCC'  style='padding: 0px; height:2px'></tr>";
                $curClass = $row['ClassID'];
                $db->query("SELECT COUNT(`Message_ID`), `Sub_Class_ID` FROM `Message" . $curClass . "` GROUP BY `Sub_Class_ID`");
                unset($countMes);
                if ($db->get_col(null, 1)) {
                    $countMes = array_combine((array) $db->get_col(null, 1), (array) $db->get_col(null, 0));
                }
                $db->query("SELECT COUNT(`Message_ID`), `Sub_Class_ID` FROM `Message" . $curClass . "` WHERE `Checked` = 0 GROUP BY `Sub_Class_ID`");

                unset($countMes_off);
                if ($db->get_col(null, 1)) {
                    $countMes_off = array_combine((array) $db->get_col(null, 1), (array) $db->get_col(null, 0));
                }
                $useSubClass = false;
                if ($row['SubClassID'])
                    $useSubClass = true;
                $str.= "<td><a href='../class/index.php?phase=4&amp;ClassID=" . $curClass . "' style='text-decoration:none'>" .
                        "<font color='#000000'>" . $curClass . ". " . $row['ClassName'] . "</font></a></td>";
            }
            else {
                $str.= "<tr><td><font size='-2'></td>";
            }

            if ($row['SubID']) {
                $temp1 = "<a href = 'http://{$domains[$row['SubID']]}{$row['SubURL']}' style='text-decoration:underline' target='_blank'>" . $row['SubID'] . ". " . $row['SubName'] . "</a>";
                $temp2 = "<a href = 'http://{$domains[$row['SubID']]}{$row['SubURL']}{$row['SubClassKeyword']}.html' style='text-decoration:underline' target='_blank'>" . $row['SubClassID'] . ". " . $row['SubClassName'] . "</a>";
            } else {
                $temp1 = $temp2 = "—";
            }

            $str .= "<td><font size='-2'>" . $temp1 . "</td>" .
                    "<td><font size='-2'>" . $temp2 . "</td>";

            $str.="<td align='center'>" . $countMes[$row['SubClassID']];
            if ($countMes_off[$row['SubClassID']])
                $str .= "(" . $countMes_off[$row['SubClassID']] . ")";
            $str.= "</td>";
            if ($withdeleted) {
                $str.= "<td align='center'>" . ($countMes[$row['SubClassID']] ? ("<input type='checkbox' value='" . $row['SubClassID'] . "' name='Delete" . $row['SubClassID'] . "'>") : "") . "</td>";
            }
            $str.= "</tr>\r\n";
        }

        switch ($param_show) {
            case 0:
                print($str);
                break;
            case 1:
                if ($useSubClass)
                    print($str);
                break;
            case 2:
                if (!$useSubClass)
                    print($str);
                break;
        }

        print "</table></td></tr></table>";

        if ($withdeleted) {
            print "<input type='hidden' name='phase' value='3'></form>\n";
            print $nc_core->token->get_input();
        }
    }

    /**
     * Show using class
     *
     * @param array | null arrayClassID, if !isset - all class
     * @param int 0 - all, 1 - only used, 2 - only unused
     */
    function nc_list_class_template_use($arrayClassID, $param_show = 0) {
        // system superior object
        $nc_core = nc_Core::get_object();

        // system db object
        if (is_object($nc_core->db))
            $db = &$nc_core->db;

        if (empty($arrayClassID)) {
            nc_print_status(CONTROL_CLASS_CLASS_TEMPLATE_ERROR_NOT_FOUND, "error");
            return false;
        } else {
            $arrayClassID = array_map("intval", $arrayClassID);
        }

        $Result = $nc_core->db->get_results("SELECT Class.`Class_ID` AS ClassID, Class.`Class_Name` AS ClassName,
    Sub_Class.`Sub_Class_ID` AS SubClassID, Sub_Class.`Sub_Class_Name` AS SubClassName,
    Subdivision.`Subdivision_Name` AS SubName, Subdivision.`Subdivision_ID` AS SubID
    FROM `Class`
    LEFT JOIN `Sub_Class` ON Sub_Class.`Class_ID` = Class.`ClassTemplate`
      AND Sub_Class.`Class_Template_ID` = Class.`Class_ID`
    LEFT JOIN `Subdivision` ON Subdivision.`Subdivision_ID` = Sub_Class.`Subdivision_ID`
    WHERE " . (!empty($arrayClassID) ? "Class.`Class_ID` IN(" . join(",", $arrayClassID) . ")" : "Class.`System_Table_ID` = '0'" ) . "
      AND Class.`ClassTemplate` != '0'", ARRAY_A);

        $curClass = -1;

        echo "<table border='0' cellpadding='0' cellspacing='0' width='100%'><tr>\n" .
        "<table class='admin_table' width='100%'>\n" .
        "<tr>\n" .
        "<th width='40%'>" . CONTROL_CLASS_CLASS_TEMPLATE . "</th>\n" .
        "<th width='30%'>" . CONTROL_CONTENT_SUBDIVISION_FUNCS_SECTION . "</th>\n" .
        "<th width='30%'>" . CONTROL_CONTENT_SUBDIVISION_CLASS . "</th>\n" .
        "</tr>";

        $str = "";
        foreach ($Result as $row) {

            if ($curClass != $row['ClassID']) {
                switch ($param_show) {
                    case 0:
                        print($str);
                        break;
                    case 1:
                        if ($useSubClass)
                            print($str);
                        break;
                    case 2:
                        if (!$useSubClass)
                            print($str);
                        break;
                }

                //так сделано, чтобы для первого класса не выводился раздилитель
                //$str = $divider;

                $str = "<tr>";
                //$divider = "<tr><td colspan='3' style='background:#CCCCCC; height:2px; padding:0px'></tr>";
                $curClass = $row['ClassID'];
                $useSubClass = false;
                if ($row['SubClassID'])
                    $useSubClass = true;
                $str.= "<td>" .
                        "<a href='" . $nc_core->ADMIN_PATH . "class/index.php?phase=4&amp;ClassID=" . $curClass . "' style='text-decoration:none'>" .
                        "<font color='#000000'>" . $curClass . ". " . $row['ClassName'] . "</font></a>" .
                        "</td>";
            }
            else {
                $str.= "<tr><td></td>";
            }

            if ($row['SubID']) {
                $temp1 = "<a href='" . $nc_core->ADMIN_PATH . "subdivision/index.php?phase=5&amp;SubdivisionID=" . $row['SubID'] . "' style='text-decoration:underline'>" . $row['SubID'] . ". " . $row['SubName'] . "</a>";
                $temp2 = "<a href='" . $nc_core->ADMIN_PATH . "subdivision/SubClass.php?phase=3&amp;SubClassID=" . $row['SubClassID'] . "&amp;SubdivisionID=" . $row['SubID'] . "' style='text-decoration:underline'>" . $row['SubClassID'] . ". " . $row['SubClassName'] . "</a>";
            } else {
                $temp1 = $temp2 = "&mdash;";
            }

            $str.= "<td>" . $temp1 . "</td>";
            $str.= "<td>" . $temp2 . "</td>";
            $str.= "</tr>";
        }

        switch ($param_show) {
            case 0:
                print($str);
                break;
            case 1:
                if ($useSubClass)
                    print($str);
                break;
            case 2:
                if (!$useSubClass)
                    print($str);
                break;
        }

        print "</table></td></tr></table>";
    }

    /**
     * Вывод формы для создания нового шаблона компонента
     *
     * @global object $db
     * @global object $UI_CONFIG
     *
     * @param int $class_id номер компонента, шаблон которого создается
     *
     * @return int 0
     */
    function nc_classtemplate_preadd_from($class_id) {
        global $UI_CONFIG;

        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;

        $class_id = intval($class_id);

        // доступные типы шаблонов
        $types = array(
                'useful',
                'inside_admin',
                'admin_mode',
                'rss',
                'xml',
                'title',
                'trash',
                'mobile',
                'responsive',
                'multi_edit');
        $exist_types = $db->get_col("SELECT DISTINCT `Type` FROM `Class` WHERE `ClassTemplate` ='" . $class_id . "' AND `Type` <> 'useful'");
        if ($exist_types) { $types = array_diff($types, $exist_types); }

        // определение компонентов, на базе которых можно создать
        $class_name = $db->get_var("SELECT CONCAT(`Class_ID`, '. ', `Class_Name`) FROM `Class` WHERE `Class_ID` = '" . $class_id . "'");
        $base = array('auto' => CONTROL_CLASS_COMPONENT_TEMPLATE_BASE_AUTO,
                'empty' => CONTROL_CLASS_COMPONENT_TEMPLATE_BASE_EMPTY);
        $base['class_' . $class_id] = $class_name;
        $templates = $db->get_results("SELECT `Class_ID` as `id`, CONCAT(`Class_ID`, '. ', `Class_Name`) as `name`
                                       FROM `Class` WHERE `ClassTemplate` = '" . $class_id . "'", ARRAY_A);
        if (!empty($templates))
            foreach ($templates as $k => $v) {
                $base['class_' . $v['id']] = $v['name'];
            }

        echo "<fieldset><legend>" . CONTROL_CLASS_COMPONENT_TEMPLATE_ADD_PARAMETRS . "</legend>\r\n";
        echo "<form action='index.php' method='post'>
                  <input type='hidden' name='fs' value='".+$_REQUEST['fs']."' />";

        $Type = $nc_core->input->fetch_get_post('Type');

        // тип шаблона компонента
        echo CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_CLASSTEMPLATE . ": <br/>\r\n";
        echo " <select name='Type' style='width: 250px;'>\r\n";
        foreach ($types as $v)
            echo "\t<option " . ( $Type === $v ? "selected='selected'" : NULL ) . " value='" . $v . "'>" . constant("CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_" . strtoupper($v)) . "</option>\r\n";
        echo "</select><br/><br/>\r\n";

        // на основе..
        echo CONTROL_CLASS_COMPONENT_TEMPLATE_CREATE_BASE . ": <br/>\r\n";
        echo " <select name='base' style='width: 250px;'>\r\n";
        foreach ($base as $k => $v)
            echo "\t<option value='" . $k . "'>" . $v . "</option>\r\n";
        echo "</select>\r\n";

        echo $nc_core->token->get_input();
        echo "<input type='hidden' name='phase' value='141' />\r\n";
        echo "<input type='hidden' name='ClassID' value='" . $class_id . "' />\r\n";
        echo "</form>\r\n";
        echo "</fieldset>\r\n";

        $UI_CONFIG->actionButtons[] = array(
                "id" => "submit",
                "caption" => CONTROL_CLASS_CLASS_TEMPLATE_ADD,
                "action" => "mainView.submitIframeForm()"
        );

        return 0;
    }

    /**
     * Создание шаблона компонента
     *
     * @param int $class_id номер исходного класса
     * @param string $type тип шаблона: useful, rss, admin_mode, inside_admin, xml
     * @param string $base создать на основе компонента - class_XX, auto, empty
     *
     * @return int номер созданого шаблона
     */
    function nc_classtempalte_make($class_id, $type = 'useful', $base = 'auto') {
        $nc_core = nc_Core::get_object();

        $class_id = intval($class_id);
        $class_name = $nc_core->component->get_by_id($class_id, 'Class_Name');
        $File_Mode = false;

		// создание шаблона на основе другого компонента
		if ( preg_match('/class_(\d+)/i', ($base == 'auto' ? 'class_' . $class_id : $base), $match) ) {
			$File_Mode = nc_get_file_mode('Class', $match[1]);
		}
		
		if ( !is_writable($nc_core->CLASS_TEMPLATE_FOLDER) ) {
			return false;
		}

        if ($type != 'useful' && $type != 'mobile')
            $class_name = constant("CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_" . strtoupper($type));

        if ($type == 'mobile')
            $class_name = $class_name . " (" . constant("CONTROL_CLASS_COMPONENT_TEMPLATE_TYPE_" . strtoupper($type)) . ")";

        if ($base == 'empty') {
            return $nc_core->component->add($class_name, CONTROL_CLASS_CLASS_TEMPLATE_GROUP, array(), $class_id, $type);
        }

        if ($base == 'auto' && in_array($type, array('rss', 'xml', 'trash', 'inside_admin'))) {
            $template = call_user_func('nc_classtemplate_make_' . $type, $class_id);
        }

        if ($base == 'auto' && in_array($type, array('useful', 'admin_mode', 'title', 'mobile'))) {
            $base = 'class_' . $class_id;
        }
        // создание шаблона на основе другого компонента
        if (preg_match('/class_(\d+)/i', $base, $match)) {
		if ($File_Mode) {
				$class_editor = new nc_class_editor($nc_core->CLASS_TEMPLATE_FOLDER, $nc_core->db);
				$class_editor->load($match[1]);
				$class_editor->fill_fields();
				$template = $class_editor->get_fields();
		}
		else {
			$template = $nc_core->component->get_by_id($match[1]);
		}
        }
        
        return $nc_core->component->add($class_name, CONTROL_CLASS_CLASS_TEMPLATE_GROUP, $template, $class_id, $type);
    }

    function nc_classtemplate_make_inside_admin($class_id) {
        $nc_core = nc_Core::get_object();
        $prefix = "<? require_once \$nc_parent_field_path; ?> \r\n";
        $prefix .= '<?= ($searchForm ? "
    <div id=\'nc_admin_filter\'>
      <fieldset>
        <legend>" . NETCAT_MODERATION_FILTER . "</legend>
        $searchForm
      </fieldset>
    </div>
    " : "" ); ?>';
        $record = "<? require_once \$nc_parent_field_path; ?>";
        $suffix = "<? require_once \$nc_parent_field_path; ?>";
        $full = "<? require_once \$nc_parent_field_path; ?>";
        $settings = "<? require_once \$nc_parent_field_path; ?>";
        $custom_settings = $nc_core->component->get_by_id($class_id, 'CustomSettingsTemplate');

        return array('FormPrefix' => $prefix, 'RecordTemplate' => $record, 'FormSuffix' => $suffix,
                'Settings' => $settings, 'RecordTemplateFull' => $full, 'CustomSettingsTemplate' => $custom_settings);
    }

    function nc_classtemplate_make_rss($class_id) {
        $component = new nc_Component($class_id);


        // поля, которые могут попасть в ленту
        $string_fields = $component->get_fields(NC_FIELDTYPE_STRING);
        $text_fields = $component->get_fields(NC_FIELDTYPE_TEXT);
        $date_fields = $component->get_fields(NC_FIELDTYPE_DATETIME);
        $file_fields = $component->get_fields(NC_FIELDTYPE_FILE);

        $File_Mode = $component->get_by_id($class_id, 'File_Mode');

        // префикс

        if ($File_Mode) {
            $prefix =
            '<?= "<?xml version=\'1.0\' encoding=\'{$nc_core->NC_CHARSET}\'?>"; ?>
                <?= "<?xml-stylesheet type=\'text/xsl\' href=\'/images/rss.xsl\'?>"; ?>
                    <rss version="2.0" xml:lang="ru-RU">
                        <channel>
                            <title><?= htmlspecialchars_decode($system_env["ProjectName"], ENT_QUOTES); ?></title>
                            <link>http://<?= $_SERVER["HTTP_HOST"]; ?>/</link>
                            <description><?= htmlspecialchars_decode(strip_tags($current_sub["Description"]), ENT_QUOTES); ?></description>
                            <language>ru-RU</language>
                            <copyright>Copyright <?= date("Y"); ?> <?= htmlspecialchars_decode($system_env["ProjectName"], ENT_QUOTES); ?></copyright>
                            <lastBuildDate><?= gmdate("D, d M Y H:i:s", $nc_last_update); ?> GMT</lastBuildDate>
                            <generator>CMS NetCat</generator>
                            <category><?= htmlspecialchars_decode(strip_tags($current_sub["Subdivision_Name"]), ENT_QUOTES); ?></category>
                            <managingEditor><?= $system_env["SpamFromEmail"]; ?> (<?= htmlspecialchars_decode($system_env["SpamFromName"], ENT_QUOTES); ?>)</managingEditor>
                            <webMaster><?= $system_env["SpamFromEmail"]; ?> (<?= htmlspecialchars_decode($system_env["SpamFromName"], ENT_QUOTES); ?>)</webMaster>
                            <ttl>30</ttl>';


        } else {
            $prefix = '<?xml version=\"1.0\" encoding=\"".$nc_core->NC_CHARSET."\"?>
                <?xml-stylesheet type=\"text/xsl\" href=\"/images/rss.xsl\"?>
                <rss version=\"2.0\" xml:lang=\"ru-RU\">
                    <channel>
                    <title>".htmlspecialchars_decode($system_env[\'ProjectName\'], ENT_QUOTES)."</title>
                    <link>http://".$_SERVER[\'HTTP_HOST\']."/</link>
                    <description>".htmlspecialchars_decode(strip_tags($current_sub[\'Description\']), ENT_QUOTES)."</description>
                    <language>ru-RU</language>
                    <copyright>Copyright ".date("Y")." ".htmlspecialchars_decode($system_env[\'ProjectName\'], ENT_QUOTES)."</copyright>
                    <lastBuildDate>".gmdate("D, d M Y H:i:s", $nc_last_update)." GMT</lastBuildDate>
                    <generator>CMS NetCat</generator>
                    <category>".htmlspecialchars_decode(strip_tags($current_sub[\'Subdivision_Name\']), ENT_QUOTES)."</category>
                    <managingEditor>".$system_env[\'SpamFromEmail\']." (".htmlspecialchars_decode($system_env[\'SpamFromName\'], ENT_QUOTES).")</managingEditor>
                    <webMaster>".$system_env[\'SpamFromEmail\']." (".htmlspecialchars_decode($system_env[\'SpamFromName\'], ENT_QUOTES).")</webMaster>
                    <ttl>30</ttl>';
        }
        // суффикс
        $suffix = '</channel>
             </rss>';

        // ищем поле для titl'a
        $title = "'" . NETCAT_MODERATION_TITLE . "'";
        if (!empty($string_fields)) {
            foreach ($string_fields as $v) {
                if (nc_preg_match('/(titl|caption|name|subject)/i', $v['name'])) {
                    $title = '$f_' . $v['name'];
                    break;
                }
            }
        }

        // ищем поле для description'a
        $description = "'" . NETCAT_MODERATION_DESCRIPTION . "'";
        if (!empty($text_fields)) {
            foreach ($text_fields as $v) {
                if (nc_preg_match('/(text|message|announce|description|content)/i', $v['name'])) {
                    $description = '$f_' . $v['name'];
                    break;
                }
            }
        }

        // ищем поле для даты
        $pubDate = '$f_Created';
        if (!empty($date_fields)) {
            foreach ($date_fields as $v) {
                if (nc_preg_match('/(date)/i', $v['name'])) {
                    $pubDate = '($f_' . $v['name'] . ' ? $f_' . $v['name'] . ' : $f_Created) ';
                    break;
                }
            }
        }

        // картинка
        $enclosure = "";
        if (!empty($file_fields)) {
            foreach ($file_fields as $v) {
                $enclosure .= '".( $f_' . $v['name'] . '  ? "<enclosure url=\"http://".$_SERVER[\'HTTP_HOST\'].$f_' . $v['name'] . '."\" length=\"$f_' . $v['name'] . '_size\" type=\"$f_' . $v['name'] . '_type\"  />" : "" )."';
                break;
            }
        }

        if($File_Mode) {
            $record =
            '<item>
                <title><?= htmlspecialchars_decode(' . $title . '); ?></title>
                <link>http://<?= $_SERVER["HTTP_HOST"] . $fullLink; ?></link>
                <description><?= ' . $description . '; ?></description>
                <category><?= htmlspecialchars_decode($current_sub["Subdivision_Name"]); ?></category>
                <pubDate><?= date(DATE_RSS, strtotime(' . $pubDate . ') ); ?></pubDate>
                ' . ($enclosure ? "<?= \"$enclosure\"; ?>": "") . '
                <guid isPermaLink="true">http://<?= $_SERVER["HTTP_HOST"] . $fullLink; ?></guid>
            </item>';
        } else {
            $record = '<item>
                <title>".htmlspecialchars_decode(' . $title . ')."</title>
                <link>http://".$_SERVER[\'HTTP_HOST\']."$fullLink</link>
                <description>".' . $description . '."</description>
                <category>".htmlspecialchars_decode($current_sub[\'Subdivision_Name\'])."</category>
                <pubDate>".date(DATE_RSS, strtotime(' . $pubDate . ') )."</pubDate>
                ' . $enclosure . '
                <guid isPermaLink=\"true\">http://".$_SERVER[\'HTTP_HOST\']."$fullLink</guid>
            </item>';
        }



        // системные настройки
        $settings = '$nc_last_update = $db->get_var("SELECT MAX(UNIX_TIMESTAMP(`Created`)) FROM `Message".$classID."` WHERE `Sub_Class_ID` = \'".$cc."\' AND `Checked` = 1 ");';
        $settings .= "\r\nheader(\"Content-type: text/xml; charset=\".\$nc_core->NC_CHARSET);";

        $rss_template['ClassDescription'] = CONTROL_CLASS_COMPONENT_FOR_RSS;
        $rss_template['RecordsPerPage'] = 10;
        $rss_template['FormPrefix'] = $prefix;
        $rss_template['RecordTemplate'] = $record;
        $rss_template['FormSuffix'] = $suffix;
        $rss_template['Settings'] = $File_Mode ? "<?php\n$settings\n?>" : $settings;

        return $rss_template;
    }

    function nc_classtemplate_make_xml($class_id) {
        $component = new nc_Component($class_id);
        $File_Mode = $component->get_by_id($class_id, 'File_Mode');
        // поля, которые могут попасть в ленту
        $fields = $component->get_fields();

        // префикс
        if ($File_Mode) {
            $prefix = '<?= "<?xml version=\'1.0\' encoding=\'{$nc_core->NC_CHARSET}\'?>"; ?>';
        } else {
            $prefix = '<?xml version=\"1.0\" encoding=\"".$nc_core->NC_CHARSET."\"?>';
        }

        $prefix .= "\r\n<messages>";

        if ($File_Mode) {
            $record = '<message id="<?= $f_RowID; ">';
        } else {
            $record = '<message id=\"".$f_RowID."\">';
        }
        $record .= "\r\n";

        if (!empty($fields)) {
            foreach ($fields as $v) {
                if (in_array($v['type'], array(1, 2, 3, 4, 5, 7))) {
                    if ($File_Mode) {
                        $record .= "\t" . '<' . strtolower($v['name']) . '><?= htmlspecialchars_decode($f_' . $v['name'] . '); ?></' . strtolower($v['name']) . '>' . "\r\n";
                    } else {
                        $record .= "\t" . '<' . strtolower($v['name']) . '>".htmlspecialchars_decode($f_' . $v['name'] . ')."</' . strtolower($v['name']) . '>' . "\r\n";
                    }
                } else if ($v['type'] == 6) {
                    if ($File_Mode) {
                        $record .= "\t" . '<' . strtolower($v['name']) . '><?= htmlspecialchars_decode($f_' . $v['name'] . '_name)."</' . strtolower($v['name']) . '>' . "\r\n";
                    } else {
                        $record .= "\t" . '<' . strtolower($v['name']) . '>".htmlspecialchars_decode($f_' . $v['name'] . '_name)."</' . strtolower($v['name']) . '>' . "\r\n";
                    }
                }
            }
        }

        $record .= '</message>' . "\r\n";

        $suffix = "</messages>\r\n";

        $settings = "ob_end_clean(); \r\nheader(\"Content-type: text/xml\");";
        return array(
                'FormPrefix' => $prefix,
                'RecordTemplate' => $record,
                'FormSuffix' => $suffix,
                'Settings' => $File_Mode ? "<?php\n$settings\n?>" : $settings);
    }

    function nc_classtemplate_make_trash($class_id) {
        $component = new nc_Component($class_id);
        $File_Mode = nc_get_file_mode('Class', $class_id);
        // поля, которые могут попасть в ленту
        $fields = $component->get_fields();

        $string_fields = $component->get_fields(NC_FIELDTYPE_STRING);
        $text_fields = $component->get_fields(NC_FIELDTYPE_TEXT);

        // ищем поле для titl'a
        $title = '';
        if (!empty($string_fields))
            foreach ($string_fields as $v) {
                if (nc_preg_match('/(titl|caption|name|subject)/i', $v['name'])) {
                    $title = 'f_' . $v['name'];
                    break;
                }
            }

        if (empty($title) && !empty($string_fields)) {
            $title = 'f_' . $string_fields[0]['name'];
        } elseif (empty($title) && empty($string_fields) && !empty($text_fields)) {
            $title = 'f_' . $text_fields[0]['name'];
        } elseif (empty($title) && !empty($fields)) {
            $title = 'f_' . $fields[0]['name'] . ($fields[0]['type'] == 6 ? '_name' : NULL);
        } elseif (empty($title)) {
            $title = 'f_RowID';
        }
        $record = $File_Mode ? '<?php echo "' : '';
        $record .= '$f_AdminButtons $' . $title . "<br /><br />\r\n";
        $record .= $File_Mode ? '"; ?>' : '';
        return array('RecordTemplate' => $record);
    }

    class ui_config_class extends ui_config {

        function ui_config_class($active_tab = 'edit', $class_id = 0, $class_group = '') {

            global $MODULE_VARS;

            $nc_core = nc_Core::get_object();
            $db = $nc_core->db;
            $fs_suffix = +$_REQUEST['fs'] ? '_fs' : '';

            $class_id = intval($class_id);
            $class_group = $db->escape($class_group);

            if ($class_id) {
                $this->headerText = $db->get_var("SELECT Class_Name FROM Class WHERE Class_ID = $class_id");
            } elseif ($class_group) {
                $this->headerText = $db->get_var("SELECT Class_Group FROM Class WHERE md5(Class_Group) = '" . $class_group . "' GROUP BY Class_Group");
            } else {
                $this->headerText = SECTION_INDEX_DEV_CLASSES;
            }

            if (in_array($active_tab, array('customadd', 'customedit', 'customsearch', 'customdelete'))) {
                $active_toolbar = $active_tab;
                $active_tab = 'classaction';
            }

            if ($active_tab)
                $this->headerImage = 'i_folder_big.gif';

            if ($active_tab == 'add') {
                $this->tabs = array(
                        array('id' => 'add',
                                'caption' => CONTROL_CLASS_ADD_ACTION,
                                'location' => "dataclass$fs_suffix.add($class_group)"));
            } elseif ($active_tab == 'addtemplate') {
                $this->tabs = array(
                        array('id' => 'addtemplate',
                                'caption' => CONTROL_CLASS_CLASS_TEMPLATE_ADD,
                                'location' => "dataclass$fs_suffix.addtemplate(" . $class_id . ")"));
            } elseif ($active_tab == 'delete') {
                $this->tabs = array(
                        array('id' => 'delete',
                                'caption' => CONTROL_CLASS_DELETE,
                                'location' => "dataclass$fs_suffix.delete($class_group)"));
            } elseif ($active_tab == 'import') {
                $this->tabs = array(
                        array('id' => 'import',
                                'caption' => CONTROL_CLASS_IMPORT,
                                'location' => "dataclass$fs_suffix.import($class_group)"));
            } else {
                $this->tabs = array(
                        array('id' => 'info',
                                'caption' => CLASS_TAB_INFO,
                                'location' => "dataclass$fs_suffix.info($class_id)"),
                        array('id' => 'edit',
                                'caption' => CLASS_TAB_EDIT,
                                'location' => "dataclass$fs_suffix.edit($class_id)"),
                        array('id' => 'classaction',
                                'caption' => CLASS_TAB_CUSTOM_ACTION,
                                'location' => "dataclass$fs_suffix.customadd($class_id)"),
                        array('id' => 'fields',
                                'caption' => CONTROL_CLASS_FIELDS,
                                'location' => "dataclass$fs_suffix.fields($class_id)"),
                        array('id' => 'custom',
                                'caption' => CONTROL_CLASS_CUSTOM,
                                'location' => "dataclass$fs_suffix.custom($class_id)"));
            }

            // Активная вкладка - "Шаблоны действий"
            if ($active_tab == 'classaction') {
                $this->toolbar = array(
                        array('id' => 'customadd',
                                'caption' => CLASS_TAB_CUSTOM_ADD,
                                'location' => "dataclass$fs_suffix.customadd($class_id)",
                                'group' => "grp1"),
                        array('id' => 'customedit',
                                'caption' => CLASS_TAB_CUSTOM_EDIT,
                                'location' => "dataclass$fs_suffix.customedit($class_id)",
                                'group' => "grp1"),
                        array('id' => 'customdelete',
                                'caption' => CLASS_TAB_CUSTOM_DELETE,
                                'location' => "dataclass$fs_suffix.customdelete($class_id)",
                                'group' => "grp1"),
                        array('id' => 'customsearch',
                                'caption' => CLASS_TAB_CUSTOM_SEARCH,
                                'location' => "dataclass$fs_suffix.customsearch($class_id)",
                                'group' => "grp1"));
            }

            $this->activeTab = $active_tab;
            $this->activeToolbarButtons[] = $active_toolbar;

            if ($active_tab == 'add' || $active_tab == 'import') {
                $this->locationHash = "#dataclass.$active_tab($class_group)";
            } elseif ($active_tab == 'delete') {
                // иначе сбрасывается
            } else {
                if ($active_tab == 'classaction') {
                    $this->locationHash = "#dataclass.$active_toolbar($class_id)";
                } else {
                    $this->locationHash = "#dataclass.$active_tab($class_id)";
                }

                $this->treeSelectedNode = "dataclass-{$class_id}";
            }

            $this->treeMode = 'dataclass' . $fs_suffix;
        }

        function updateTreeClassNode($class_id, $class_name) {

            $this->treeChanges['updateNode'][] = array("nodeId" => "sub-$node_id",
                    "name" => "$node_id. $node_name");
        }

    }

    class ui_config_class_template extends ui_config {

        function ui_config_class_template($active_tab = 'edit', $class_id = 0, $base_class = '') {

            global $db, $nc_core;
            $class_id = intval($class_id);
            $type = '';

            $sys = nc_Core::get_object()->db->get_var("SELECT System_Table_ID FROM Class WHERE Class_ID = " .($active_tab == 'add' ? $base_class : $class_id));
            $suffix = +$_REQUEST['fs'] ? '_fs' : '';


            if ($class_id) {
                $this->headerText = $db->get_var("SELECT `Class_Name` FROM `Class` WHERE `Class_ID` = '" . $class_id . "'");
                $type = $db->get_var("SELECT `Type` FROM `Class` WHERE `Class_ID` = '" . $class_id . "' ");
            } else {
                $this->headerText = SECTION_INDEX_DEV_CLASS_TEMPLATES;
            }

            if (in_array($active_tab, array('customadd', 'customedit', 'customsearch', 'customsubscribe', 'customdelete'))) {
                $active_toolbar = $active_tab;
                $active_tab = 'classaction';
            }

            if ($active_tab)
                $this->headerImage = 'i_folder_big.gif';

            if ($active_tab == 'add') {
                $this->tabs = array(
                        array(
                                'id' => 'add',
                                'caption' => CONTROL_CLASS_CLASS_TEMPLATE_ADD,
                                'location' => "classtemplate.add(" . $base_class . ")"
                        )
                );
            } elseif ($active_tab == 'delete') {
                $this->tabs = array(
                        array(
                                'id' => 'delete',
                                'caption' => CLASS_TEMPLATE_TAB_DELETE,
                                'location' => "classtemplate.delete(" . $class_id . ($base_class ? "," . $base_class : "") . ")"
                        )
                );
            } else {
                $this->tabs = array(
                        array(  'id' => 'info',
                                'caption' => CLASS_TEMPLATE_TAB_INFO,
                                'location' => "classtemplate$suffix.info(" . $class_id . ")"
                        ),
                        array(
                                'id' => 'edit',
                                'caption' => CLASS_TEMPLATE_TAB_EDIT,
                                'location' => "classtemplate$suffix.edit(" . $class_id . ")"
                        ));

                if ($type != 'rss' && $type != 'xml')
                    $this->tabs[] = array(
                            'id' => 'classaction',
                            'caption' => CLASS_TAB_CUSTOM_ACTION,
                            'location' => "classtemplate$suffix.classaction(" . $class_id . ")"
                    );

                if ($type == 'useful' || $type == 'title' || $type == 'mobile')
                // пользовательские настройки
                    $this->tabs[] = array(
                            'id' => 'custom',
                            'caption' => CONTROL_CLASS_CUSTOM,
                            'location' => "classtemplate$suffix.custom(" . $class_id . ")"
                    );
            }

            // Активная вкладка - "Шаблоны действий"
            if ($active_tab == 'classaction') {
                $this->toolbar = array(
                        array(
                                'id' => 'customadd',
                                'caption' => CLASS_TAB_CUSTOM_ADD,
                                'location' => "classtemplate$suffix.customadd(" . $class_id . ")",
                                'group' => "grp1"
                        ),
                        array(
                                'id' => 'customedit',
                                'caption' => CLASS_TAB_CUSTOM_EDIT,
                                'location' => "classtemplate$suffix.customedit(" . $class_id . ")",
                                'group' => "grp1"
                        ),
                        array(
                                'id' => 'customdelete',
                                'caption' => CLASS_TAB_CUSTOM_DELETE,
                                'location' => "classtemplate$suffix.customdelete(" . $class_id . ")",
                                'group' => "grp1"
                        ),
                        array(
                                'id' => 'customsearch',
                                'caption' => CLASS_TAB_CUSTOM_SEARCH,
                                'location' => "classtemplate$suffix.customsearch(" . $class_id . ")",
                                'group' => "grp1"
                        )
                );

            }



            $this->activeTab = $active_tab;
            $this->activeToolbarButtons[] = $active_toolbar;

            if ($active_tab == 'add') {
                $this->locationHash = "#classtemplate." . $active_tab . "(" . $base_class . ")";
            } elseif ($active_tab == 'delete') {
            } else {
                if ($active_tab == 'classaction') {
                    $this->locationHash = "#classtemplate." . $active_toolbar . "(" . $class_id . ")";
                } else {
                    $this->locationHash = "#classtemplate." . $active_tab . "(" . $class_id . ")";
                }
                $this->treeSelectedNode = "classtemplate-" . $class_id;
            }
            $this->treeMode = $sys ? 'systemclass' : 'dataclass';
            $this->treeMode .= $suffix;
        }

        function updateTreeClassNode($class_id, $class_name) {

            $this->treeChanges['updateNode'][] = array(
                    "nodeId" => "sub-" . $node_id,
                    "name" => $node_id . ". " . $node_name
            );
        }

    }

    class ui_config_class_templates extends ui_config {

        function ui_config_class_templates($active_tab = 'edit', $class_id) {

            $this->headerText = CONTROL_CLASS_CLASS_TEMPLATES;
            $this->headerImage = 'i_folder_big.gif';
            $this->tabs = array(
                    array(
                            'id' => 'edit',
                            'caption' => CONTROL_CLASS_CLASS_TEMPLATES,
                            'location' => "classtemplates.edit(" . $class_id . ")"
                    )
            );

            $sys = nc_Core::get_object()->db->get_var("SELECT System_Table_ID FROM Class WHERE Class_ID = " . $class_id);

            $this->activeTab = $active_tab;
            $suffix = +$_REQUEST['fs'] ? '_fs' : '';
            $this->locationHash = "classtemplates.edit(" . $class_id . ")";
            $this->treeMode = $sys ? 'systemclass' : 'dataclass';
            $this->treeMode .= $suffix;
            $this->treeSelectedNode = "classtemplates-" . $class_id;
        }

    }

    class ui_config_class_group extends ui_config {

        function ui_config_class_group($active_tab = 'edit', $class_group) {

            global $db;
            $class_group = $db->escape($class_group);

            $this->headerText = $db->get_var("SELECT Class_Group FROM Class WHERE md5(Class_Group) = '$class_group' GROUP BY Class_Group");
            $this->headerImage = 'i_folder_big.gif';
            $this->tabs = array(
                    array('id' => 'edit',
                            'caption' => CONTROL_CLASS_CLASS_GROUPS,
                            'location' => "classgroup.edit($class_group)")
            );

            $this->activeTab = $active_tab;
            $this->locationHash = "classgroup.edit($class_group)";
            $this->treeMode = 'dataclass' . (+$_REQUEST['fs'] ? '_fs' : '');
            $this->treeSelectedNode = "group-$class_group";
        }

    }

    class ui_config_classes extends ui_config {

        function ui_config_classes($active_tab = 'dataclass.list') {
            $this->headerText = SECTION_CONTROL_CLASS;
            $this->headerImage = 'i_folder_big.gif';
            $this->tabs = array(
                    array(
                            'id' => 'dataclass.list',
                            'caption' => SECTION_CONTROL_CLASS,
                            'location' => "dataclass.list()"));
            $this->activeTab = $active_tab;
            $this->locationHash = "#dataclass.list()";
            $this->treeMode = 'dataclass' . (+$_REQUEST['fs'] ? '_fs' : '');
            $this->treeSelectedNode = "dataclass.list()";
        }

    }

    class ui_config_system_class extends ui_config {

        function ui_config_system_class($active_tab = 'edit', $system_class_id = 0) {
            $nc_core = nc_Core::get_object();
            $db = $nc_core->db;

            $suffix = +$_REQUEST['fs'] ? '_fs' : '';

            $system_class_id = +$system_class_id;
            $system_class = $db->get_row("SELECT a.System_Table_ID, a.System_Table_Rus_Name, b.Class_ID, IF(b.AddTemplate<>'' OR b.AddCond<>'' OR b.AddActionTemplate<>'',1,0) AS IsAdd, IF(b.EditTemplate<>'' OR b.EditCond<>'' OR b.EditActionTemplate<>'' OR b.CheckActionTemplate<>'' OR b.DeleteActionTemplate<>'',1,0) AS IsEdit, IF(b.SearchTemplate<>'' OR b.FullSearchTemplate<>'',1,0) AS IsSearch, IF(b.SubscribeTemplate<>'' OR b.SubscribeCond<>'',1,0) AS IsSubscribe FROM System_Table AS a LEFT JOIN Class AS b ON a.System_Table_ID=b.System_Table_ID WHERE a.System_Table_ID = '" . $system_class_id . "'", ARRAY_A);

            $this->headerText = constant($system_class["System_Table_Rus_Name"]);
            $this->headerImage = 'i_folder_big.gif';

            if ($system_class["Class_ID"] || $system_class[System_Table_ID]) {
                if ($system_class_id == 3 && $nc_core->modules->get_by_keyword('auth', 0)) {
                    $this->tabs[] = array(
                            'id' => 'edit',
                            'caption' => CLASS_TAB_EDIT,
                            'location' => "systemclass$suffix.edit($system_class[System_Table_ID])");

                    $this->tabs[] = array(
                            'id' => 'customadd',
                            'caption' => CLASS_TAB_CUSTOM_ADD,
                            'location' => "systemclass$suffix.customadd($system_class[System_Table_ID])");

                    $this->tabs[] = array(
                            'id' => 'customedit',
                            'caption' => CLASS_TAB_CUSTOM_EDIT,
                            'location' => "systemclass$suffix.customedit($system_class[System_Table_ID])");

                    $this->tabs[] = array(
                            'id' => 'customsearch',
                            'caption' => CLASS_TAB_CUSTOM_SEARCH,
                            'location' => "systemclass$suffix.customsearch($system_class[System_Table_ID])");
                }
                $this->tabs[] = array(
                        'id' => 'fields',
                        'caption' => CONTROL_CLASS_FIELDS,
                        'location' => "systemclass$suffix.fields($system_class[System_Table_ID])");
            }
            $this->activeTab = $active_tab;
            $this->locationHash = "#systemclass.$active_tab($system_class[System_Table_ID])";

            $this->treeMode = 'systemclass' . (+$_REQUEST['fs'] ? '_fs' : '');
            $this->treeSelectedNode = "systemclass-{$system_class[System_Table_ID]}";
        }

    }

    class ui_config_system_classes extends ui_config {
        function ui_config_system_classes($active_tab = 'systemclass.list') {
            $this->headerText = SECTION_CONTROL_CLASS;
            $this->headerImage = 'i_folder_big.gif';
            $this->tabs = array(
                    array(
                            'id' => 'systemclass.list',
                            'caption' => SECTION_SECTIONS_OPTIONS_SYSTEM,
                            'location' => "systemclass.list"));
            $this->activeTab = $active_tab;
            $this->treeMode = 'systemclass' . (+$_REQUEST['fs'] ? '_fs' : '');
            $this->treeSelectedNode = "systemclass.list";
        }
    }

    function print_bind() {
        return null;
    }

    function print_resizeblock($textarea_id) {
        return null;
    }


    function ClassInformation($ClassID, $action, $phase) {
        global $ROOT_FOLDER, $ClassGroup, $ADMIN_PATH, $UI_CONFIG;
        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;

        $ClassID = +$ClassID;

        $select = "SELECT `Class_ID`, 
                          `Class_Name`,
                          `System_Table_ID`,
                          `File_Hash`,
                          `File_Mode`,
                          `Class_Group`";

        if ($nc_core->modules->get_by_keyword("cache")) {
            $select.= ", `CacheForUser`";
        }

        $select.= "FROM `Class` WHERE ";
        $select.= " `Class_ID` = '" . $ClassID . "'";

        $Array = $db->get_row($select);
        if ($File_Mode) {
            $class_editor->load($ClassID);
            $class_editor->fill_fields();
            $class_filds = $class_editor->get_fields();
            $Array->Class_Name = $class_filds['Class_Name'];
            $Array->Settings = $class_filds['Settings'];
        }
        
        if (!$Array) {
            nc_print_status(CONTROL_CLASS_ERRORS_DB, 'error');
        }

        $fieldsets = new nc_admin_fieldset_collection();

        ob_start();

        ?>
        <form method='post' id='ClassForm' action='<?= $action ?>'>
            <input type='hidden' name='ClassID' value='<?= $ClassID ?>' />
            <input type='hidden' name='phase' value='<?= $phase ?>' />
            <input type='hidden' name='action_type' value='1' />
            <input type='hidden' name='fs' value='<?= $Array->File_Mode; ?>' />
            <?= $nc_core->token->get_input(); ?>
        <?

        $fieldsets->set_prefix(ob_get_clean())->set_suffix("</form>");

        $fieldsets->new_fieldset('main_info', CONTROL_CLASS_CLASSFORM_MAININFO);
        ob_start();
        ?>
            <div id='maininfoOn'>
                <?
                echo "<div class='inf_block'><label>" . CONTROL_CLASS_CLASS_NAME . ":</label><br/>";
                echo "<input type='text' name='Class_Name' size='50' value=\"" . htmlspecialchars_decode($Array->Class_Name) . "\"><br/><br/>";

                // if not component template - show groups
                if (!($Array->ClassTemplate || $phase == 15 || $phase == 17)) {
                    $classGroups = $db->get_col("SELECT DISTINCT `Class_Group` FROM `Class`");
                    if (!empty($classGroups)) {
                        echo "<div class='inf_block'><label>" . CONTROL_USER_GROUP . "</label>:<br /><select name='Class_Group' style='width:320px; margin-right: 5px;'>\n";
                        foreach ($classGroups as $Class_Group) {
                            if ($Array->Class_Group == $Class_Group) {
                                echo("\t<option value='" . $Class_Group . "' selected='selected'>" . $Class_Group . "</option>\n");
                            } else {
                                echo("\t<option value='" . $Class_Group . "'>" . $Class_Group . "</option>\n");
                            }
                        }
                        echo "</select></div>";
                    }
                    unset($classGroups);

                    echo "<br /><div class='inf_block'><label>" . CONTROL_CLASS_NEWGROUP . "</label><br/><input type='text' name='Class_Group_New' size='50' value='" . htmlspecialchars_decode($Array->Class_Group_New) . "'></div>";
                } else {
                    echo "<div class='inf_block'><label>" . CONTROL_USER_GROUP . ":</label><br/> " . CONTROL_CLASS_CLASS_TEMPLATE_GROUP . "</div>";
                    echo "<input type='hidden' name='Class_Group' value='" . CONTROL_CLASS_CLASS_TEMPLATE_GROUP . "'>";
                }
                ?>
            </div>
            <?

            $fieldsets->main_info->add(ob_get_clean());

            if ($nc_core->modules->get_by_keyword("cache")) {
                $fieldsets->new_fieldset('cache_info', CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_CACHE);
                ob_start();

                ?>
                <div id='cacheinfoOn'>
                    <?

                    $CacheForUser = $db->get_var("SELECT `CacheForUser` FROM `Class` WHERE `Class_ID` = ".$ClassID);

                    ?>
                    <table border='0' cellpadding='0' cellspacing='0' width='99%'>
                        <tr>
                            <td style='border: none;'>
                                <?= CONTROL_CLASS_CACHE_FOR_AUTH ?>*:<br/>
                                <select name='CacheForUser' style='width:320px; margin-right: 5px;'>
                                    <option value='0'<?= (!$CacheForUser ? " selected" : "") ?>><?= CONTROL_CLASS_CACHE_FOR_AUTH_NONE ?></option>
                                    <option value='1'<?= ($CacheForUser == 1 ? " selected" : "") ?>><?= CONTROL_CLASS_CACHE_FOR_AUTH_USER ?></option>
                                    <option value='2'<?= ($CacheForUser == 2 ? " selected" : "") ?>><?= CONTROL_CLASS_CACHE_FOR_AUTH_GROUP ?></option>
                                </select><br/>
                                * <?= CONTROL_CLASS_CACHE_FOR_AUTH_DESCRIPTION ?>
                            </td>
                        </tr>
                    </table>
                </div>
            <?

            $fieldsets->cache_info->add(ob_get_clean());
        }

        return $fieldsets->to_string();
    }
