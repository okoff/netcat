<?php
/* $Id: function.inc.php 8305 2012-10-30 08:38:58Z vadim $ */
if (!class_exists("nc_System"))
    die("Unable to load file.");

/**
 * Init filed types, field name
 */
function InitVars() {
    global $nc_core, $field_types, $field_type_name, $type_of_edit, $type_of_edit_name, $type_of_error;

    $field_types = array(
        1 => 'icon_type_string',
        2 => 'icon_type_int',
        3 => 'icon_type_text',
        4 => 'icon_type_classificator',
        5 => 'icon_type_bool',
        6 => 'icon_type_file',
        7 => 'icon_type_float',
        8 => 'icon_type_date',
        9 => 'icon_type_link',
        10 => 'icon_type_multiclassificator',
        11 => 'icon_type_m_file'
    );

    $field_type_name = array(
        1 => CLASSIFICATOR_TYPEOFDATA_STRING,
        2 => CLASSIFICATOR_TYPEOFDATA_INTEGER,
        3 => CLASSIFICATOR_TYPEOFDATA_TEXTBOX,
        4 => CLASSIFICATOR_TYPEOFDATA_LIST,
        5 => CLASSIFICATOR_TYPEOFDATA_BOOLEAN,
        6 => CLASSIFICATOR_TYPEOFDATA_FILE,
        7 => CLASSIFICATOR_TYPEOFDATA_FLOAT,
        8 => CLASSIFICATOR_TYPEOFDATA_DATETIME,
        9 => CLASSIFICATOR_TYPEOFDATA_RELATION,
        10 => CLASSIFICATOR_TYPEOFDATA_MULTILIST,
        11 => CLASSIFICATOR_TYPEOFDATA_MULTIFILE
    );

    if ($nc_core->modules->get_by_keyword('auth') && nc_auth_openid_possibility()) {
        $field_types[11] = 'i_type_openid.gif';
        $field_type_name[11] = CLASSIFICATOR_TYPEOFDATA_OPENID;
    }

    $type_of_edit = array(
        1 => 'field_access_all',
        2 => 'field_access_admin',
        3 => 'field_access_no'
    );

    $type_of_edit_name = array(
        1 => CLASSIFICATOR_TYPEOFEDIT_ALL,
        2 => CLASSIFICATOR_TYPEOFDATA_ADMINS,
        3 => CLASSIFICATOR_TYPEOFDATA_NOONE
    );

    $type_of_error = array(
        0 => CONTROL_FIELD_DB_ERROR,
        1 => CONTROL_FIELD_NAME_ERROR,
        2 => CONTROL_FIELD_ONE_RESERVED,
        3 => CONTROL_FIELD_EXITS_ERROR,
        4 => CONTROL_FIELD_FORMAT_ERROR,
        5 => CONTROL_FIELD_DIGIT_ERROR
    );

    return;
}

/**
 * Insert(modify, drop) into Table (Message or Subdivision or Template or.. see SystemTable) field
 * @param int FieldID
 * @param int type operation: 1 - add, 2 - modify, 3 - drop
 * @param object $db
 */
function ColumnInMessage($FieldID, $type, $db) {
    global $db;

    $FieldID = intval($FieldID);

    $SelectField = $db->get_row("SELECT `Class_ID`, `Widget_Class_ID`, `Field_Name`, `TypeOfData_ID`, `NotNull`, `DoSearch`, `DefaultState`, `System_Table_ID` FROM `Field` WHERE `Field_ID`= '" . $FieldID . "'");

    if ($SelectField->Widget_Class_ID)
        return true;

    $isSys = $SelectField->System_Table_ID;  #for short, ==0 - if Component Field
    $TableName = $isSys ? GetSystemTableName($SelectField->System_Table_ID) : "Message" . $SelectField->Class_ID;

    switch ($type) {
        case 1: $oper = "ADD";
            break;
        case 2: $oper = "MODIFY";
            break;
        case 3: $oper = "DROP";
            break;
    }

    if ($type == 2 && !$isSys) {
        $arr_indexes = $db->get_results("SHOW INDEX FROM `Message{$SelectField->Class_ID}`", ARRAY_A);
        if (!empty($arr_indexes))
            foreach ($arr_indexes as $arr_indexes_row) {
                if ($arr_indexes_row['Key_name'] == $SelectField->Field_Name) {
                    $db->query("ALTER TABLE `Message" . $SelectField->Class_ID . "` DROP INDEX `" . $SelectField->Field_Name . "`");
                }
            }
    }




    $alter = "ALTER TABLE `" . $TableName . "` " . $oper . " `" . $SelectField->Field_Name . "`";

    if ($type == 3) {
        $db->query($alter);
        return true;
    }

    $alter.= " ";

    switch ($SelectField->TypeOfData_ID) {
        case 1:
            $alter.= "CHAR(255)";
            break;
        case 2:
            $alter.= "INT";
            break;
        case 3:
            $alter.= "LONGTEXT";
            break;
        case 4:
            $alter.= "INT";
            break;
        case 5:
            $alter.= "TINYINT";
            break;
        case 6:
            $alter.= "CHAR(255)";
            break;
        case 7:
            $alter.= "DOUBLE";
            break;
        case 8:
            $alter.= "DATETIME";
            break;
        case 9:
            $alter.= "INT";
            break;
        case 10:
            $alter.= "TEXT";
            break;
        case 11:
            $alter.= "CHAR(255)";
            break;
    }

    switch (true) {
        case $SelectField->DefaultState != NULL && !in_array($SelectField->TypeOfData_ID, array(3, 6, 8)):
            $alter.= " NOT NULL DEFAULT '" . $db->escape($SelectField->DefaultState) . "'";
            break;
        case $SelectField->NotNull :
            $alter.= " NOT NULL";
            break;
        default:
            $alter.= " NULL";
    }

    $db->query($alter);
    if ($isSys)
        return true;

    if ($SelectField->DoSearch && $SelectField->TypeOfData_ID != 3) {
        $db->query("ALTER TABLE `" . $TableName . "` ADD INDEX (`" . $SelectField->Field_Name . "`)");
    }

    return true;
}

/**
 * Show field list
 *
 * @param int $Id - ClassID or SystemTableID
 * @param bool is field in system table
 * @param bool show for component wizard
 * @return bool  true - if success
 */
function FieldList($Id, $isSys = 0, $isWizard = 0, $isWidget = 0) {
    global $UI_CONFIG, $ADMIN_PATH, $ADMIN_TEMPLATE;
    global $field_types, $field_type_name, $type_of_edit, $type_of_edit_name;

    $nc_core = nc_Core::get_object();
    $db = $nc_core->db;

    $Id += 0;
    if (!$Id)
        return false;

    $where = $isSys ? "`System_Table_ID`" : ($isWidget ? "`Widget_Class_ID`" : "`Class_ID`");

    if (isset($_REQUEST['fs'])) {
        $File_Mode = +$_REQUEST['fs'];
    } else {
        $queries = array(
                '`System_Table_ID`' => 'select File_Mode from Class where `System_Table_ID` = 3 and `ClassTemplate` = 0',
                '`Widget_Class_ID`' => 'select File_Mode from Widget_Class where Widget_Class_ID = ' . $Id,
                '`Class_ID`' => 'select File_Mode from Class where Class_ID = ' . $Id);
        $File_Mode = +$db->get_var($queries[$where]);
    }
    $where .= "='" . $Id . "'";

    $Result = $db->get_results("SELECT `Field_ID`, `Field_Name`, `Description`, `TypeOfData_ID`, `DoSearch`, `NotNull`, `TypeOfEdit_ID`, `Priority`
    FROM `Field`
    WHERE " . $where . "
    ORDER BY `Priority`");

    if (($countFields = $db->num_rows)) {

        if (!$isWizard)
            print "<form method='post' action='index.php'>\n";
        ?>
        <table border='0' cellpadding='0' cellspacing='0' width='100%'>
            <tr>
                <td >
                    <table class='admin_table' width='100%'>
                        <tr>
                            <th>ID</th>
                            <th width='20%'><?= CONTROL_FIELD_LIST_NAME
        ?></th>
                            <th width='50%'><?= CONTROL_FIELD_LIST_DESCRIPTION
        ?></th>
                            <th colspan='4' class='align-center' width='10%'><?= CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_DOPL ?></th>
                            <td align='center'>
                                <div class='icons icon_prior' title='<?= CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_PRIORITY ?>'></div>
                            </td>
                        <? if (!$isWizard): ?>
                                <td align=center>
                                    <div class='icons icon_delete' title='<?= CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_DELETE ?>'></div></td>
                        <? endif; ?>
                        </tr>

                        <?php
                        foreach ($Result as $Row) {
                            print "<tr>\n";
                            print "  <td>" . $Row->Field_ID . "</td>\n";
                            if ($isWizard) {
                                print "  <td>" . $Row->Field_Name . "</td>\n";
                            } else {
                                print "  <td><a href='index.php?fs=$File_Mode&phase=4&amp;FieldID={$Row->Field_ID}&amp;isSys={$isSys}&isWidget=$isWidget'>" . $Row->Field_Name . "</a></td>\n";
                            }
                            print "  <td>" . $Row->Description . "</td>\n";
                            print "  <td><div class='icons " . $field_types[$Row->TypeOfData_ID] . "' title='" . $field_type_name[$Row->TypeOfData_ID] . "'></div></td>\n";
                            print "  <td><div class='icons icon_search" . ($Row->DoSearch ? "" : "_disabled") . "' title='".CONTROL_FIELD_ONE_INDEX."'></div></td>\n";
                            print "  <td><div class='icons icon_notnull" . ($Row->NotNull ? "" : "_disabled") . "' title='".CONTROL_FIELD_ONE_MUSTBE."'></div></td>\n";
                            print "  <td><div class='icons icon_".$type_of_edit[$Row->TypeOfEdit_ID]."' title='".$type_of_edit_name[$Row->TypeOfEdit_ID]."'></div></td>\n";
                            print "  <td align='center'>" .
                                    nc_admin_input_simple("priority[" . $Row->Field_ID . "]", ($Row->Priority ? $Row->Priority : 0), 3, '', "class='s' maxlength='5'" . ($isWizard ? " disabled" : "")) . "</td>\n";
                            if (!$isWizard)
                                print "  <td align='center'>" . nc_admin_checkbox_simple('Delete[]', $Row->Field_ID) . "</td>\n";
                            print "</tr>\n";
                        }

                        $name = $isSys ? "SystemTableID" : ($isWidget ? "widgetclass_id" : "ClassID");
                        print "<input type='hidden' name='" . $name . "' value='" . $Id . "'>\n";
                        print "<input type='hidden' name='isSys' value='" . $isSys . "'>\n";
                        print "<input type='hidden' name='fs' value='" . $File_Mode . "'>\n";
                        print $nc_core->token->get_input();
                        ?>
                    </table>
                </td>
            </tr>
        </table>
        <br>
        <?php
    }
    else {
        nc_print_status(CONTROL_FIELD_LIST_NONE, 'info');
    }
    if (!$isWizard) {
        if ($countFields) {
            $UI_CONFIG->actionButtons[] = array("id" => "submit",
                "caption" => CONTROL_FIELD_LIST_CHANGE,
                "action" => "mainView.submitIframeForm()");
        }
        $location = $isSys ? "systemfield" : ($isWidget ? "widgetfield" : "field");
        $UI_CONFIG->actionButtons[] = array("id" => "addClass",
            "caption" => CONTROL_FIELD_LIST_ADD,
            "action" => "urlDispatcher.load('" . $location . (+$_REQUEST['fs'] ? '_fs' : '') . ".add(" . $Id . ")')",
            "align" => "left");
        if ($countFields) {
            ?>
            <input type='hidden' name='phase' value='6'>
            <input type='hidden' name='fs' value='<?=$File_Mode?>'>
            <input type='submit' class='hidden'>
            </form>
            <?php
        }
    }
    return true;
}

#   --- End function FieldList ---
#########################################
# Редактирование свойств отдельного поля ИЛИ СОЗДАНИЕ НОВОГО СВОЙСТВА
#########################################

/**
 * Show form to edit field or create new
 *
 * @param int Field id, 0 - if new
 * @param int ClassId or SystemTableId, if 0 - edit field
 * @param bool is field in system table
 * @param string action
 * @param string form name
 * @param string form id
 * @param string Advanced elements
 * @return bool true
 */
function FieldForm($FieldID, $Id, $isSys = 0, $action = "index.php", $FormName = '', $FormID = '', $Additional = '', $isWidget = 0) {
    global $db, $nc_core;
    global $UI_CONFIG;
    global $field_type_name, $type_of_edit_name;
    $FieldID = intval($FieldID);
    $Id = intval($Id);

    $no_multifile = $isSys && ($Id != 3);

    if ($FieldID) {
        $Array = $db->get_row("SELECT `Field_Name`, `Description`, `TypeOfData_ID`, `Format`, `NotNull`, `Priority`, `DoSearch`,
      `Inheritance`, `DefaultState`, `TypeOfEdit_ID`, `Widget_Class_ID`
      FROM `Field`
      WHERE `Field_ID`='" . $FieldID . "' AND `Checked` = 1", ARRAY_A);
        if ($Array["Widget_Class_ID"]) {
            $isWidget = 1;
        }
    } else {
        $Array = $_POST;
        $where = $isSys ? "`System_Table_ID`" : ($isWidget ? "`Widget_Class_ID`" : "`Class_ID`");
        $where .= "='" . $Id . "'";

        if (!$Array["Priority"]) {
            $res = $db->get_var("SELECT (Priority+1) FROM `Field` WHERE " . $where . " ORDER BY `Priority` DESC LIMIT 1");
            if (!is_null($res))
                $Array["Priority"] = $res;
        }
    }
    // js для визульного заполнения "Формата"
    $js = "<script type='text/javascript' language='JavaScript'>
    " . ( $Array["TypeOfData_ID"] || 1 ? "nc_field_additional_format (" . intval($Array["TypeOfData_ID"] ? $Array["TypeOfData_ID"] : 1) . ");" : "" ) . "

    function nc_field_additional_format ( selected_field ) {

     //settings for text
     document.getElementById('div_upload_limits').style.display = ((selected_field == 6 || selected_field == 11 ) ? 'block' : 'none');

    // select FS for file field
     document.getElementById('div_field_file').style.display = ((selected_field == 6) ? 'block' : 'none');

     //don't show Format field for text
     document.getElementById('div_format').style.display = ((selected_field == 3 || selected_field == 1 ) ? 'none' : 'block');

     //settings for text
     document.getElementById('div_field_text').style.display = ((selected_field == 3 ) ? 'block' : 'none');

    //settings for string
     document.getElementById('div_field_string').style.display = ((selected_field == 1 ) ? 'block' : 'none');

     //settings for date
     document.getElementById('div_field_date').style.display = ((selected_field == 8 ) ? 'block' : 'none');";
     
	if (!$isSys) {
		$js.= "//settings for search
		document.getElementById('dosr').disabled = ((selected_field == 9 || selected_field == 11) ? true : false);
		document.getElementById('dosr').style.background = ( (selected_field == 9 || selected_field == 11) ? '#eee' : '' );";
	}

     $js.= "// default value field
     var defaultField = document.getElementById('DefaultState');
     
    if (defaultField) {
         // default value field disabled
         defaultField.disabled = ( (selected_field == 11 || selected_field==3 || selected_field==6 || selected_field==8 || selected_field==10) ? true : false );
         defaultField.style.background = ( (selected_field == 11 || selected_field==3 || selected_field==6 || selected_field==8) ? '#eee' : '' );

         // default value field data
         defaultField.value = (selected_field==" . intval($Array["TypeOfData_ID"]) . " ? '" . $db->escape($Array["DefaultState"]) . "' : '');
    }
     return false;
    }

    function nc_select_field () {
     var selected_field = document.getElementById('TypeOfData_ID').selectedIndex + 1 ;
     nc_field_additional_format (selected_field);
     nc_field_select_fs ();
     return false;
    }

    function nc_field_select_fs () {
      var selected_fs = document.getElementById('Format_FS').selectedIndex + 1;
      document.getElementById('attachment').disabled = ( selected_fs != 3 );
      document.getElementById('download').disabled = ( selected_fs != 3 );
      return false;
    }
    nc_field_select_fs ();
  </script>\n";

    print "<fieldset>";
    if ($FieldID)
        print "<legend>" . ($Array["Description"] ? $Array["Description"] : $Array["Field_Name"]) . "</legend>";

    print "<br/><form method='post' action='" . $action . "' name='" . $FormName . "' id='" . $FormID . "'>";

    $Format_FS = NC_FS_PROTECTED; // тип по ФС по умолчанию
    $format_text = nc_field_parse_format("", NC_FIELDTYPE_TEXT); // для textarea значения по умолчанию

    if ($FieldID) {
        print CONTROL_FIELD_LIST_NAME . ": " . $Array["Field_Name"] . "<br><br>";

        switch ($Array['TypeOfData_ID']) {
            case NC_FIELDTYPE_FILE:  // определим тип ФС
                $format_file = nc_field_parse_format($Array['Format'], $Array['TypeOfData_ID']);
                $Format_FS = $format_file['fs'];
                $file_attach = $format_file['disposition']; // закачиваемый или нет?
                $file_download = $format_file['download'];
                $Array['Format'] = nc_preg_replace('/(:?)(fs)(\d+)/', '', $Array['Format']); // уберем из Format тип ФС
                $Array['Format'] = nc_preg_replace('/(:?)(download)/', '', $Array['Format']); // уберем download
                $Array['Format'] = nc_preg_replace('/(:?)((attachment)|(inline))/', '', $Array['Format']); // уберем attachment
                break;
            case NC_FIELDTYPE_TEXT:
                $format_text = nc_field_parse_format($Array['Format'], $Array['TypeOfData_ID']);
                break;
            case NC_FIELDTYPE_DATETIME:
                $format_date = nc_field_parse_format($Array['Format'], $Array['TypeOfData_ID']);
                $Array['Format'] = $format_date['type'];
                break;
        }
    } else {
        print CONTROL_FIELD_LIST_NAMELAT . ":<br>" . nc_admin_input_simple('FieldName', $Array["Field_Name"], 50, '', "maxlength='64'") . "<br><br>";
    }

    print CONTROL_FIELD_LIST_DESCRIPTION . ":<br>" . nc_admin_input_simple('Description', htmlspecialchars_decode( $Array["Description"] ) , 50, '', "maxlength='64'") . "<br><br>";

    $openid_possibility = ( $nc_core->modules->get_by_keyword('auth') && nc_auth_openid_possibility() );
    if ($FieldID && $Array['TypeOfData_ID'] == 11 && $openid_possibility)
        print "<input name='TypeOfData_ID' value='11' type='hidden'>";
    print CONTROL_FIELD_ONE_FTYPE . ":<br><select name='TypeOfData_ID' id='TypeOfData_ID' onchange='nc_select_field(); return false;'" . ($FieldID && $Array['TypeOfData_ID'] == 11 && $openid_possibility ? " disabled='disabled'" : NULL) . ">";

    //print CONTROL_FIELD_ONE_FTYPE.":<br><select name='TypeOfData_ID' id='TypeOfData_ID' onchange='nc_select_field(); return false;'>";
    for ($i = 1; $i <= count($field_type_name); $i++) {
        // Полe тиа "Связь с другими.." для системных полей не нужно
        if ($isSys && ($i === 9))
            continue;

        if($i == 11 && $no_multifile) {
            continue;
        }

        if ($isWidget && ($i === 4 || $i === 5 || $i === 6 || $i === 8 || $i === 9 || $i === 10))
            continue;
        print "<option " . ($Array["TypeOfData_ID"] == $i ? "selected" : "") . " value='" . $i . "'>" . $i . ": " . $field_type_name[$i] . "</option>\n";
    }
    print "</select><br><br>";

    $html = "<div id='div_format'>" . CONTROL_FIELD_ONE_FORMAT . ":<br>" . nc_admin_input_simple('Format', ($Array["TypeOfData_ID"] != 3 ? $Array["Format"] : ""), 50, '', "maxlength='255'") . "<br><br></div>";

    $html .= "<div id='div_field_string'>" . nc_admin_select_simple(CONTROL_FIELD_ONE_FORMAT . ":<br/>", 'Format_String', array('' => CONTROL_FIELD_ONE_FORMAT_NONE, 'email' => CONTROL_FIELD_ONE_FORMAT_EMAIL,
                'url' => CONTROL_FIELD_ONE_FORMAT_URL, 'phone' => CONTROL_FIELD_ONE_FORMAT_PHONE,
                'tags' => CONTROL_FIELD_ONE_FORMAT_TAGS), $Array["Format"]) . "
  </div>";

    // Вывод ограничений на загрузку файлов для тип поля Файл и множественный выбор файлов
    $html .= "<div id='div_upload_limits' style='display: none;'>" . CONTROL_FIELD_FILE_UPLOADS_LIMITS . "<br />
            post_max_size (" . CONTROL_FIELD_FILE_POSTMAXSIZE . "): " . ini_get('post_max_size') . " <br />
            upload_max_filesize (" . CONTROL_FIELD_FILE_UPLOADMAXFILESIZE . "): " . ini_get('upload_max_filesize') . "<br />
            max_file_uploads (" . CONTROL_FIELD_FILE_MAXFILEUPLOADS . "): " . ini_get('max_file_uploads') . "<br />
            <br /></div>";

    // расширение формата для Файлов
    $html .= "<div id='div_field_file' style='display: none;'>\r\n"
             . CLASSIFICATOR_TYPEOFFILESYSTEM . ":
            <select name='Format_FS' id='Format_FS' onchange='nc_field_select_fs(); return false;' >\r\n
               <option value='" . NC_FS_SIMPLE . "' " . ( NC_FS_SIMPLE == $Format_FS ? 'selected' : '') . ">" . CONTROL_FS_NAME_SIMPLE . "</option>\r\n
               <option value='" . NC_FS_ORIGINAL . "' " . ( NC_FS_ORIGINAL == $Format_FS ? 'selected' : '') . ">" . CONTROL_FS_NAME_ORIGINAL . "</option>\r\n
               <option value='" . NC_FS_PROTECTED . "' " . ( NC_FS_PROTECTED == $Format_FS ? 'selected' : '') . ">" . CONTROL_FS_NAME_PROTECTED . "</option>\r\n
             </select>
             <br />" . nc_admin_checkbox_simple('attachment', '', CONTROL_FIELD_ATTACHMENT, $file_attach, '', "disabled") . "
               <br/>" . nc_admin_checkbox_simple('download', '', CONTROL_FIELD_DOWNLOAD_COUNT, $file_download, '', "disabled") . "
            </div>";

    // расширение формата для текста
    $html .= "<div id='div_field_text' style='display: none;'>
  <table class='admin_table' width='40%' >
  <col width='25%'/><col width='25%'/><col width='25%'/><col width='25%'/>
  <tr align='center'>
  <th></th>
  <th>" . CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_INHERIT . "</th>
  <th>" . CONTROL_CONTENT_SUBDIVISION_FUNCS_OBJ_YES . "</th>
  <th>" . CONTROL_CONTENT_SUBDIVISION_FUNCS_OBJ_NO . "</th>
  </tr>
  <tr align='center'>
  <td>" . CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_HTML . "</td>";
    // разрешать HTML-теги
    for ($i = 0; $i <= 2; $i++) {
        $html .= "<td bgcolor='" . ( $format_text['html'] == $i ? "#E7E7E7" : "#FFFFFF") . "'>" .
                nc_admin_radio_simple('format_html', $i, '', $format_text['html'] == $i, '', "class='w'") . "
      </td>";
    }
    $html .= "</tr><tr align='center'>
  <td>" . CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_BR . "</td>";
    // перенос строки
    for ($i = 0; $i <= 2; $i++) {
        $html .= "<td bgcolor='" . ( $format_text['br'] == $i ? "#E7E7E7" : "#FFFFFF") . "'>" .
                nc_admin_radio_simple('format_br', $i, '', $format_text['br'] == $i, '', "class='w'") . "
      </td>";
    }

    $html .= "
  </tr><tr align='center'>
  <td>" . CONTROL_FIELD__EDITOR_EMBED_TO_FIELD . "</td>";
    // встроить редактор
    for ($i = 0; $i <= 2; $i++) {
        $html .= "<td bgcolor='" . ( $format_text['fck'] == $i ? "#E7E7E7" : "#FFFFFF") . "'>" .
                nc_admin_radio_simple('format_fck', $i, '', $format_text['fck'] == $i, '', "class='w'") . "
      </td>";
    }

    $html .= "</tr></table><br/>";


    $html .=CONTROL_FIELD__TEXTAREA_SIZE . ":<br/>
  <table class='admin_table' width='40%' >
  <col width='50%'/><col width='50%'/>
    <tr align='center'>
      <td>" . CONTROL_FIELD_HEIGHT . "</td>
      <td>" . nc_admin_input_simple('format_height', $format_text['rows'], 0, 'width:100%') . "</td></tr>
    <tr align='center'>
      <td>" . CONTROL_FIELD_WIDTH . "</td>
      <td>" . nc_admin_input_simple('format_width', $format_text['cols'], 0, 'width:100%') . "</td>
    </tr>
  </table>
  <br />" . nc_admin_checkbox_simple('format_bbcode', 1, CONTROL_FIELD_BBCODE_ENABLED, $format_text['bbcode']) . "
  <br/>" . nc_admin_checkbox_simple('format_usereditor', 1, CONTROL_FIELD_USER_EDITOR, $format_text['usereditor']) . "
  <br/><br/>
  </div>";

    $html .= "<div id='div_field_date' style='display: none'>" . nc_admin_checkbox_simple('use_calendar', 1, CONTROL_FIELD_USE_CALENDAR, false, 'format_usereditor', ($format_date['calendar'] ? "checked='checked'" : "")) . "
            </div><br/>";

    echo $html;

    print nc_admin_checkbox_simple('NotNull', 1, CONTROL_FIELD_ONE_MUSTBE, $Array["NotNull"], 'notn') . "<br>";
    if ($isSys) {
        print nc_admin_checkbox_simple('Inheritance', 1, CONTROL_FIELD_ONE_INHERITANCE, $Array["Inheritance"], 'inhr') . "<br><br>";
    } else {
        print nc_admin_checkbox_simple('DoSearch', 1, CONTROL_FIELD_ONE_INDEX, $Array["DoSearch"] && $Array['TypeOfData_ID'] != 9, 'dosr', ( $Array['TypeOfData_ID'] != 9 ? " disabled='disabled'" : "")) . "<br><br>";
    }

    print CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_PRIORITY . ": " . nc_admin_input_simple('Priority', ($Array["Priority"] + 0), 3, '', "maxlength='5'") . "<br><br>";

    print CONTROL_FIELD_ONE_DEFAULT . "*:<br>";
    print nc_admin_input_simple('DefaultState', $Array["DefaultState"], 50, '', "id='DefaultState' maxlength='255'") . "\n";
    print "<br>* " . CONTROL_FIELD_ONE_DEFAULT_NOTE . ".<br><br>\n";

    print "<table class='admin_table' width='60%' >" .
            "<col width='25%'/><col width='25%'/><col width='25%'/><col width='25%'/>" .
            "<tr><td></td>";

    if (!$Array["TypeOfEdit_ID"])
        $Array["TypeOfEdit_ID"] = 1;

    for ($i = 1; $i <= count($type_of_edit_name); $i++) {
        print "<td text-align:center'><label for='mod" . $i . "'>" . $type_of_edit_name[$i] . "</label></td>";
    }

    print "</tr><tr><td text-align:center'>" . CONTROL_FIELD_ONE_ACCESS . "</td>";

    for ($i = 1; $i <= count($type_of_edit_name); $i++) {
        print "<td text-align:center'>" .
                nc_admin_radio_simple('TypeOfEdit_ID', $i, '', $Array["TypeOfEdit_ID"] == $i, "mod" . $i, "class='w'") . "</td>";
    }

    print "</tr>" .
            "</table>";

    // js для визуальной настройки формата
    print $js;

    if (!$FieldID) {
        $UI_CONFIG->actionButtons[] = array("id" => "submit",
            "caption" => CONTROL_FIELD_LIST_ADD,
            "action" => "mainView.submitIframeForm('" . $FormID . "')");
    } else {
        $UI_CONFIG->actionButtons[] = array("id" => "submit",
            "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SAVE,
            "action" => "mainView.submitIframeForm('" . $FormID . "')");
    }

    $name = $isSys ? "SystemTableID" : ($isWidget ? "widgetclass_id" : "ClassID");

    if ($Id) {
        print "<input type='hidden' name='" . $name . "' value='" . $Id . "'>\n";
        print "<input type='hidden' name='phase' value='3'>\n";
    } else {
        if ($isSys)
            print "<input type='hidden' name='SystemTableID' value='" . $Id . "'>\n";
        print "<input type='hidden' name='FieldID' value='" . $FieldID . "'>\n";
        print "<input type='hidden' name='phase' value='5'>\n";
    }
    print $Additional;
    print $nc_core->token->get_input();
    print "<input type='hidden' name='isSys' value='" . $isSys . "'>\n";
    print "<input type='hidden' name='fs' value='" . +$_REQUEST['fs'] . "'>";
    print "<input type='submit' class='hidden'>\n";
    print "<input type='hidden' name='isWidget' value='$isWidget' />";
    print "</form>\n";

    print "</fieldset>";

    return true;
}

/**
 * Insert into DB properties of field
 *
 * @param bool is field in system table
 * @return int field id ,0 -  unknown error, -1 = incorrect field name, -2 = field name is mysql_keyword, -3 = field already exits
 *                                           -4 = incorrect field format
 *
 */
function FieldCompleted() {
    global $db;
    global $ClassID, $SystemTableID, $widgetclass_id;
    global $Inheritance, $DoSearch;
    global $FieldID, $FieldName, $Description, $TypeOfData_ID, $TypeOfEdit_ID, $Format, $Format_String, $Format_FS, $NotNull, $Priority, $DefaultState, $attachment, $download;
    global $format_height, $format_width, $format_html, $format_br, $format_fck, $format_bbcode, $format_usereditor, $use_calendar;
    global $isSys, $widgetclass_id;

    $ClassID = intval($ClassID);
    $FieldID = intval($FieldID);

    if ($TypeOfData_ID == 1)
        $Format = $Format_String;
    if ($FieldID) {
        $TableName = $isSys ? GetSystemTableName(GetSystemTableIDByFieldID($FieldID)) : "Message" . GetClassIDByFieldID($FieldID);
    } else {
        $TableName = $isSys ? GetSystemTableName($SystemTableID) : "Message" . $ClassID;
    }

    // widgets
    $widgetclass_id += 0;
    if ($FieldID) {
        $widgetclass_id = $db->get_var("SELECT `Widget_Class_ID` FROM `Field` WHERE `Field_ID`= '" . $FieldID . "'");
    }

    $NotNull += 0;
    $DoSearch += 0;
    $Inheritance += 0;
    $Priority += 0;

    $format_height += 0;
    $format_width += 0;
    $format_html += 0;
    $format_br += 0;
    $format_fck += 0;
    $format_bbcode += 0;
    $format_usereditor += 0;

    $use_calendar += 0;

    // float field
    if ($TypeOfData_ID == 7)
        $DefaultState = str_replace(",", ".", $DefaultState);

    $FieldName = str_replace(' ', '', $FieldName);
    if (!isCorrectFormat($Format, $TypeOfData_ID))
        return -4;
    switch (1) {
        case ( count($db->get_results(($widgetclass_id ? "SELECT * FROM `Field` WHERE `Widget_Class_ID`='" . $widgetclass_id . "' AND `Field_Name`='" . $FieldName . "'" : "SHOW FIELDS FROM `" . $TableName . "` LIKE '" . $FieldName . "'"))) ):   //check for exits
            return -3;
        //case ( !$FieldName  || preg_match("/[^a-zA-Z0-9_]/", $FieldName) ):
        //  return -1;
        case ( nc_is_mysql_keyword($FieldName) ):                                                   // check for reserved sql words
            return -2;
    }

    // Добавим к Формату тип файловой системы\ настройки textarea
    if ($TypeOfData_ID == NC_FIELDTYPE_FILE) {
        if (!$Format_FS)
            $Format_FS = NC_FS_SIMPLE;
        $Format .= ":fs" . $Format_FS;
        $Format .= ":" . ($attachment ? 'attachment' : 'inline');
        $Format .= ( $download ? ':download' : '');

        if (nc_substr($Format, 0, 1) == ':')
            $Format = nc_substr($Format, 1);
    }
    else if ($TypeOfData_ID == NC_FIELDTYPE_TEXT) {
        $Format = ($format_height ? $format_height : 5) . ":" . ($format_width ? $format_width : 60) . ";";
        $Format .= "html:" . $format_html . ";br:" . $format_br . ";fck:" . $format_fck . ";";
        $Format .= "bbcode:" . $format_bbcode . ";usereditor:" . $format_usereditor . ";";
    } else if ($TypeOfData_ID == NC_FIELDTYPE_DATETIME && $use_calendar) {
        $Format .= ( $Format ? ";" : "" ) . 'calendar';
    }

    $Description = htmlspecialchars( $Description );

    // Add new field
    if (!$FieldID) {
        $fl1 = $isSys ? "`System_Table_ID`" : ($widgetclass_id ? "`Widget_Class_ID`" : "`Class_ID`");
        $fl2 = $isSys ? "`Inheritance`" : "`DoSearch`";
        $insert_field = "`Field` (" . $fl1 . ", " . $fl2 . ", `Field_Name`,`Description`,`TypeOfData_ID`,`Format`,`NotNull`,`Priority`,`DefaultState`,`TypeOfEdit_ID`)";
        $value = " VALUES ('";
        $value .= $isSys ? $SystemTableID . "','" . $Inheritance : ($widgetclass_id ? $widgetclass_id . "','" . $DoSearch : $ClassID . "','" . $DoSearch);
        $value .= "','" . $FieldName . "','" . $Description . "'," . $TypeOfData_ID . ",'" . $Format . "'," . $NotNull . "," . $Priority . ",'" . $DefaultState . "','" . $TypeOfEdit_ID . "')";
        $query = "INSERT INTO " . $insert_field . $value;

        if (!$FieldName || nc_preg_match("/[^a-zA-Z0-9_]/", $FieldName))
            return -1;
        if (!$FieldName || nc_preg_match("/^[\d]+/", $FieldName))
            return -5;
        // check if field name is already exists
        if (!$widgetclass_id) {
            $table = $ClassID ? "`Message" . $ClassID . "`" : $db->get_var("select `System_Table_Name` from `System_Table` where `System_Table_ID`=" . $SystemTableID);
            if ($db->column_exists($table, $FieldName)) {
                return -3;
            }
        }
        if ($db->query($query)) {
            $CurrentFieldID = $db->insert_id;
            if (!$widgetclass_id)
                ColumnInMessage($CurrentFieldID, 1, $db);
        }
        return $CurrentFieldID;
    }
    // Update field
    else {
        $query = "UPDATE `Field` SET `Description`='" . $Description . "',`TypeOfData_ID`='" . $TypeOfData_ID . "',`Format`='" . $Format . "',`NotNull`='" . $NotNull;
        $query .= "',`Priority`='" . $Priority . "',`DefaultState`='" . $DefaultState . "',`TypeOfEdit_ID`='" . $TypeOfEdit_ID . "'";
        $query .= $isSys ? ", `Inheritance`='" . $Inheritance . "'" : ", `DoSearch`='" . $DoSearch . "'";
        $query .= " WHERE `Field_ID`='" . $FieldID . "'";
        if ($db->query($query) && !$widgetclass_id)
            ColumnInMessage($FieldID, 2, $db);

        return $FieldID;
    }

    return 0;
}

/**
 * Update priority
 *
 * @param array priority
 * @return bool
 */
function UpdateFieldPriority($priority) {
    global $db;

    if (is_array($priority) && !empty($priority)) {
        foreach ($priority AS $key => $val) {
            $db->query("UPDATE `Field` SET `Priority` = '" . (int) $val . "' WHERE `Field_ID` = '" . (int) $key . "'");
        }
    }

    return true;
}

/**
 * Confirm to field delete
 *
 * @param array fields
 * @param int Id - ClassId or SystemTableID
 * @param bool is field in system table
 * @return empty
 */
function ConfirmFieldsRemoval($fields, $Id, $isSys = 0, $widgetclass_id = '') {

    global $db, $UI_CONFIG;
    global $priority;

    $nc_core = nc_Core::get_object();
    $fields = array_map('intval', $fields);
    $field_array = $db->get_results("SELECT `Field_ID`, `Field_Name`, `Description`, `TypeOfData_ID` FROM `Field` WHERE Field_ID IN (" . join(',', $fields) . ")", ARRAY_A);

    if ($db->num_rows > 1) {
        nc_print_status(CONTROL_FIELD_MSG_CONFIRM_REMOVAL_MANY, 'info');
    } else {
        nc_print_status(CONTROL_FIELD_MSG_CONFIRM_REMOVAL_ONE, 'info');
    }

    $name = $isSys ? "SystemTableID" : "ClassID";

    print "<form method='post' action='index.php'>\n";
    print "<input type='hidden' name='phase' value='7'>\n";
    print "<input type='hidden' name='widgetclass_id' value='" . $widgetclass_id . "'>\n";
    print "<input type='hidden' name='" . $name . "' value='" . $Id . "'>\n";
    print "<input type='hidden' name='isSys' value='" . $isSys . "'>\n";
    print "<input type='hidden' name='fs' value='".$_REQUEST['fs']."'>";
    print $nc_core->token->get_input();

    print "<ul>\n";
    foreach ((array) $field_array as $field) {
        print "  <li>" . $field['Field_Name'] . " (" . $field['Description'] . ")<input type='hidden' name='Delete[]' value='" . $field['Field_ID'] . "'>";
    }
    print "</ul>\n";

    if ($priority)
        foreach ($priority as $key => $val)
            echo "<input type='hidden' name='priority[" . $key . "]' value='" . $val . "'>\n";
    print "</form>\n";

    $UI_CONFIG->actionButtons[] = array("id" => "submit",
        "caption" => CONTROL_FIELD_CONFIRM_REMOVAL,
        "action" => "mainView.submitIframeForm()");
    return;
}

/**
 * Delete fields
 *
 * @param array $fields
 * @return int count deleted fields
 */
function DeleteFields($fields) {
    global $db, $UI_CONFIG;
    // start up value
    $fields_to_delete = array();
    // if no array
    if (empty($fields))
        return 0;
    //
    $fields = array_map('intval', $fields);
    $fields_data = $db->get_results("SELECT `Field_ID`, `System_Table_ID` FROM `Field`
    WHERE `Field_ID` IN (" . join(", ", $fields) . ")", ARRAY_A);
    // walk
    foreach ($fields_data as $field) {
        if (ColumnInMessage($field['Field_ID'], 3, $db)) {
            $fields_to_delete[] = intval($field['Field_ID']);
            $UI_CONFIG->treeChanges['deleteNode'][] = ($field['System_Table_ID'] ? "system" : "") . "field-" . $field['Field_ID'];
        }
    }
    // delete fields from base
    if (!empty($fields_to_delete)) {
        // delete fields files
        DeleteFieldsFiles($fields_to_delete);
        // delete fields from base
        $db->query("DELETE FROM `Field` WHERE `Field_ID` IN (" . join(", ", $fields_to_delete) . ")");
        return $db->rows_affected;
    }
    // return 0 if no fields deleted from base
    return 0;
}

function DeleteFieldsFiles($fields) {
    global $db, $FILES_FOLDER;
    // set as array
    $fields = (array) $fields;
    $file_fields_to_delete = array();
    // validate for security reason
    $fields = array_map("intval", $fields);
    // return if array is empty
    if (empty($fields))
        return 0;
    // get files info to delete
    $files_to_delete = $db->get_results("SELECT ft.`Field_ID`, ft.`Virt_Name`, ft.`File_Path`, fl.`System_Table_ID` FROM `Filetable` AS ft
    LEFT JOIN `Field` as fl ON ft.`Field_ID` = fl.`Field_ID`
    WHERE ft.`Field_ID` IN (" . join(", ", $fields) . ") AND fl.`TypeOfData_ID` = 6", ARRAY_A);

    if (empty($files_to_delete))
        return 0;

    foreach ($files_to_delete AS $_file_to_delete) {
        // set full path to file
        $file_to_delete_path = $FILES_FOLDER . $_file_to_delete['File_Path'] . $_file_to_delete['Virt_Name'];
        // try to delete file from disk
        if (is_writable($file_to_delete_path)) {
            @unlink($file_to_delete_path);
            $file_fields_to_delete[] = $_file_to_delete['Field_ID'];
        }
    }
    // drop rows form `Filetable` table
    if (!empty($file_fields_to_delete)) {
        $db->query("DELETE FROM `Filetable` WHERE `Field_ID` IN (" . join(", ", $file_fields_to_delete) . ")");
        return $db->rows_affected;
    }
    // return 0 if no files deleted from base
    return 0;
}

/** Checking - correct field format
 * @param Format
 * @param Date type
 * @return bool, true - if correct
 */
function isCorrectFormat($format, $id) {
    switch ($id) {
        case NC_FIELDTYPE_SELECT: // select
            return (bool) $format; // формат не должен быть пустой
            break;
        case NC_FIELDTYPE_MULTISELECT: //MultuSelect
            return nc_preg_match("/^[_a-z0-9]+((:)((select(:[0-9]+)?)|(checkbox)))?$/i", $format);
            break;
        case NC_FIELDTYPE_RELATION: // связь с объектами
            return (bool) $format; // формат не должен быть пустой
            break;
        default: //Для остальных типов данных проверки нет
            return true;
    }
}

###############################################################################

/**
 * Check, is param is mysql keyword
 *
 * @param string Field Name
 * @return bool true - if keyword
 */
function nc_is_mysql_keyword($FieldName) {
    return in_array(strtolower($FieldName), nc_preg_split("/\s+/", "accessible add all alter analyze and as asc asensitive
                  before between bigint binary blob both by call cascade
                  case change char character check collate column condition connection
                  constraint continue convert create cross current_date
                  current_time current_timestamp current_user
                  cursor database databases day_hour day_microsecond day_minute
                  day_second dec decimal declare default delayed delete desc describe
                  deterministic distinct distinctrow div double drop dual each else
                  elseif enclosed escaped exists exit explain false fetch float
                  float4 float8 for force foreign from fulltext goto grant
                  group having high_priority hour_microsecond hour_minute hour_second
                  if ignore in index infile inner inout insensitive insert
                  int int1 int2 int3 int4 int8 integer interval into is iterate join
                  key keys kill label leading leave left like limit linear lines load
                  localtime localtimestamp lock long longblob longtext
                  loop low_priority match mediumblob mediumint mediumtext
                  middleint minute_microsecond minute_second mod modifies natural
                  not no_write_to_binlog null numeric on optimize
                  option optionally or order out outer outfile precision primary
                  procedure purge range read reads read_only read_write real references
                  regexp release rename repeat replace require restrict return revoke
                  right rlike schema schemas second_microsecond select sensitive separator set
                  show smallint spatial specific sql sqlexception sqlstate sqlwarning sql_big_result
                  sql_calc_found_rows sql_small_result ssl starting straight_join table
                  terminated then tinyblob tinyint tinytext title to trailing trigger true
                  undo union unique unlock unsigned update upgrade usage use
                  using utc_date utc_time utc_timestamp values varbinary
                  varchar varcharacter varying when where while with write x509
                  xor year_month zerofill _nc_final UserGroup"));
}

class ui_config_field extends ui_config {

    private $_location;

    function ui_config_field($active_tab = 'edit', $field_id, $id, $is_sys, $isWidget = false) {
        $db = nc_Core::get_object()->db;
        $fs_suffix = +$_REQUEST['fs'] ? '_fs' : '';
        $id = intval($id);
        $field_id = intval($field_id);

        $this->_location = $is_sys ? "systemfield" : "field";
        $this->headerImage = 'i_folder_big.gif';

        switch ($active_tab) {
            case 'add':
                $this->tabs = array(
                    array('id' => 'add',
                        'caption' => CONTROL_FIELD_ADDING,
                        'location' => $this->_location . $fs_suffix . ".add(" . $id . ")")
                );
                $this->headerText = $is_sys ? constant($db->get_var("SELECT `System_Table_Rus_Name` FROM `System_Table` WHERE `System_Table_ID` = '" . $id . "'")) :
                        $db->get_var("SELECT `Class_Name` FROM `Class` WHERE `Class_ID` = '" . $id . "'");

                break;
            case 'edit':
                $this->tabs = array(
                    array('id' => 'edit',
                        'caption' => CONTROL_FIELD_EDITING,
                        'location' => $this->_location . $fs_suffix . ".edit(" . $field_id . ")")
                );
                $this->headerText = $db->get_var("SELECT `Description` FROM `Field` WHERE `Field_ID` = '" . $field_id . "'");
                $this->treeSelectedNode = $this->_location . "field-{$field_id}";
                break;
            case 'delete':
                $this->tabs = array(
                    array('id' => 'delete',
                        'caption' => CONTROL_FIELD_DELETING,
                        'location' => $this->_location . $fs_suffix . ".delete(" . $id . ")")
                );
                $this->headerText = $is_sys ? constant($db->get_var("SELECT `System_Table_Rus_Name` FROM `System_Table` WHERE `System_Table_ID` = '" . $id . "'")) :
                        $db->get_var("SELECT `Class_Name` FROM `Class` WHERE `Class_ID` = '" . $id . "'");
                $this->treeSelectedNode = $is_sys ? "systemclass-{" . $system_class_id . "}" : "dataclass-{" . $class_id . "}";

                break;
        }

        $this->activeTab = $active_tab;
        switch (true) {
            case $isWidget:
                $this->treeMode = 'widgetclass';
                break;

            case $is_sys:
                $this->treeMode = 'systemclass';
                break;

            default:
                $this->treeMode = 'dataclass';
                break;
        }
        $this->treeMode .= $fs_suffix;
    }

    function updateTreeFieldNode($field_id, $field_type) {
        global $field_types;

        $this->treeChanges['updateNode'][] = array("nodeId" => $this->_location . "-" . $field_id,
            "image" => $field_types[$field_type]);
        return;
    }

}
