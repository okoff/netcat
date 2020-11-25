<?php
/* $Id: subclass.inc.php 8404 2012-11-13 07:39:10Z lemonade $ */

function ShowList() {
    global $db, $loc, $nc_core;
    global $EDIT_DOMAIN, $HTTP_ROOT_PATH, $DOMAIN_NAME;
    global $UI_CONFIG, $ADMIN_PATH, $ADMIN_TEMPLATE, $SUB_FOLDER;

    $Select = "SELECT a.Sub_Class_ID,
                      a.Sub_Class_Name,
                      b.Class_Name,
                      a.Priority,
                      a.Checked,
                      a.Class_ID,
                      a.EnglishName,
                      d.Domain,
                      c.Hidden_URL,
                      b.System_Table_ID,
                      c.UseMultiSubClass,
                      b.File_Mode
                   FROM (Sub_Class AS a,
                        Class AS b)
                     LEFT JOIN Subdivision AS c ON a.Subdivision_ID = c.Subdivision_ID
                     LEFT JOIN Catalogue AS d ON c.Catalogue_ID = d.Catalogue_ID
                       WHERE a.Subdivision_ID = {$loc->SubdivisionID}
                         AND a.Catalogue_ID = {$loc->CatalogueID}
                         AND a.Class_ID = b.Class_ID
                         AND b.`ClassTemplate` = 0
                           ORDER BY a.Priority";

    $Result = $db->get_results($Select, ARRAY_N);

    if ($totrows = $db->num_rows) {
        ?>
        <form enctype='multipart/form-data' method='post' action='SubClass.php'>

            <table border=0 cellpadding=0 cellspacing=0 width=100%><tr>

                        <table border=0 cellpadding=0 cellspacing=0 width=100% class='border-bottom'>
                            <tr>
                                <td>ID</td>
                                <td>
                                        <?
                                        if ($loc->SubdivisionID) {
                                            $wsts = CONTROL_CONTENT_SUBCLASS_ONSECTION;
                                        } else {
                                            $wsts = CONTROL_CONTENT_SUBCLASS_ONSITE;
                                        }
                                        printf(CONTROL_CONTENT_SUBCLASS_CLASSNAME, $wsts);
                                        ?>
                                </td>
                                <td><?= CONTROL_CONTENT_CLASS ?></td>
                                <td align=center><div class='icons icon_prior' title='<?= CONTROL_CONTENT_SUBDIVISION_FUNCS_PRIORITY ?>'></div></td>
                                <td align=center><?= CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_GOTO ?></td>
                                <td align=center><div class='icons icon_delete' title='<?=CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_DELETE ?>'></div></td>
                            </tr>

                            <?= nc_get_modal_radio('UseMultiSubClass', array(
                                    array(
                                            'attr' => array(
                                                    'value' => '1'),
                                            'desc' => CONTROL_CONTENT_SUBCLASS_MULTI_ONONEPAGE),
                                    array(
                                            'attr' => array(
                                                    'value' => '2'),
                                            'desc' => CONTROL_CONTENT_SUBCLASS_MULTI_ONTABS),
                                    array(
                                            'attr' => array(
                                                    'value' => '0'),
                                            'desc' => CONTROL_CONTENT_SUBCLASS_MULTI_NONE)
                            ), $Result[0][10]);

                            foreach ($Result as $Array) {
                                $hidden_host = "http://" . ($Array[7] ? (strchr($Array[7], ".") ? $Array[7] : $Array[7] . "." . $DOMAIN_NAME) : $DOMAIN_NAME) . $SUB_FOLDER;

                                print "<tr><td ><font " . (!$Array[4] ? " color=cccccc" : "") . ">" . $Array[0] . "</td>";
                                print "<td><a href='SubClass.php?phase=3&SubClassID={$Array[0]}&SubdivisionID={$loc->SubdivisionID}&fs={$Array[11]}'>" . (!$Array[4] ? "<font color=cccccc>" : "") . $Array[1] . "</a></td>";
                                if (!$Array[9]) {
                                    print "<td><a href=" . $ADMIN_PATH . "class/index.php?phase=4&fs={$Array[11]}&ClassID=" . $Array[5] . ">" . (!$Array[4] ? "<font color=cccccc>" : "") . $Array[2] . "</a></td>";
                                } else {
                                    print "<td><a href=" . $ADMIN_PATH . "field/system.php?phase=2&fs={$Array[11]}&SystemTableID=" . $Array[9] . ">" . (!$Array[4] ? "<font color=cccccc>" : "") . $Array[2] . "</a></td>";
                                }
                                print "<td align=center>" . nc_admin_input_simple("Priority" . $Array[0], $Array[3], 3, '', "class='s'") . "</td>";
                                print "<td align=center>";

                                //setup
                                print "<a href=\"SubClass.php?phase=3&SubdivisionID=" . $loc->SubdivisionID . "&CatalogueID=" . $loc->CatalogueID . "&SubClassID=" . $Array[0] . "\"><div class='icons icon_settings" . (!$Array[4] ? "_disabled" : "") . "' title='".CONTROL_CONTENT_SUBDIVISION_FUNCS_TOOPTIONSSUBCLASS."'></div></a>";

                                //edit
                                print "<a target=_blank href=http://" . $EDIT_DOMAIN . $SUB_FOLDER . $HTTP_ROOT_PATH . "?catalogue=" . $loc->CatalogueID . "&sub=" . $loc->SubdivisionID . "&cc=" . $Array[0] . (strlen(session_id()) > 0 ? "&" . session_name() . "=" . session_id() . "" : "") . "><div class='icons icon_pencil" . (!$Array[4] ? "_disabled" : "") . "' title='".CONTROL_CONTENT_SUBDIVISION_FUNCS_TOEDIT."'></div></a>";

                                //browse
                                print $loc->SubdivisionID ? "<a href=" . $hidden_host . $Array[8] . $Array[6] . ".html target=_blank><div class='icons icon_preview" . (!$Array[4] ? "_disabled" : "") . "' title='".CONTROL_CONTENT_SUBDIVISION_FUNCS_TOVIEW."'></div></a>" : "<img src='" . $ADMIN_PATH . "images/emp.gif' width=15 height=18 style='margin:0px 2px 0px 2px;'>";

                                print "</td>";
                                print "<td align=center>" . nc_admin_checkbox_simple("Delete" . $Array[0], $Array[0]) . "</td>\n";
                                print "</tr>\n";
                            }
                            ?>

                        </table></td></tr></table><br>
            <?
        } else {
            nc_print_status(CONTROL_CONTENT_SUBCLASS_MSG_NONE, 'info');
        }

        if ($totrows) {
            print $nc_core->token->get_input();
            print "<input type=hidden name=phase VALUE=5>";
            print "<input type=hidden name=CatalogueID VALUE=" . $loc->CatalogueID . ">";
            print "<input type=hidden name=SubdivisionID VALUE=" . $loc->SubdivisionID . ">";
            print "<input type='submit' class='hidden'>";
            print "</form>";
            $UI_CONFIG->actionButtons[] = array("id" => "submit",
                    "caption" => STRUCTURE_TAB_SUBCLASS_ADD,
                    "location" => "subclass.add(" . $loc->SubdivisionID . ")",
                    "align" => "left");

            $UI_CONFIG->actionButtons[] = array("id" => "delete",
                    "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SAVE,
                    "action" => "mainView.submitIframeForm()",
                    "align" => "right");
        }
    }

    function ShowList_for_modal() {
    global $loc;
    global $EDIT_DOMAIN, $HTTP_ROOT_PATH, $DOMAIN_NAME;
    global $ADMIN_PATH, $ADMIN_TEMPLATE, $SUB_FOLDER;

    $nc_core = nc_Core::get_object();
    $db = $nc_core->db;

    $Select = "SELECT a.Sub_Class_ID,
                      a.Sub_Class_Name,
                      b.Class_Name,
                      a.Priority,
                      a.Checked,
                      a.Class_ID,
                      a.EnglishName,
                      d.Domain,
                      c.Hidden_URL,
                      b.System_Table_ID,
                      c.UseMultiSubClass
                   FROM (Sub_Class AS a,
                        Class AS b)
                     LEFT JOIN Subdivision AS c ON a.Subdivision_ID=c.Subdivision_ID
                     LEFT JOIN Catalogue AS d ON c.Catalogue_ID=d.Catalogue_ID
                       WHERE a.Subdivision_ID = {$loc->SubdivisionID}
                         AND a.Catalogue_ID = {$loc->CatalogueID}
                         AND a.Class_ID = b.Class_ID
                         AND b.`ClassTemplate` = 0
                           ORDER BY a.Priority";

    $Result = $db->get_results($Select, ARRAY_N);
    $totrows = $db->num_rows;
    if ($totrows) {
        ?>
            <style>
        div.nc_sub_class_list_table > div {
            border-bottom: 1px #cccccc solid;
            display: table;
        }

        div.nc_sub_class_list_table > div > div {
            display: inline-block;
            padding-top: 9px;
            padding-bottom: 11px;
        }

        div.nc_sub_class_list_table div.col_1 {
            width: 42px;
        }

        div.nc_sub_class_list_table div.col_2,
        div.nc_sub_class_list_table div.col_3 {
            width: 335px;
        }

        div.nc_sub_class_list_table div.col_4 {
            text-align: center;
            width: 90px;
        }

        div.nc_sub_class_list_table div.col_5 {
            text-align: center;
            width: 76px;
        }

        div.nc_sub_class_list_table > div.row_1 {
            padding-top: 3px;
            padding-bottom: 2px;
        }

        div.nc_sub_class_list_table div.disabled {
            color: #cccccc;
        }

    </style>

    <div id='nc_sub_class_list_div' style="display: none;">
        <div class='nc_sub_class_list_table'>

            <div style="border-bottom: 0px; padding-bottom: 24px; padding-top: 12px;">
                <input style='margin-left: 0px;'type='checkbox' name='UseMultiSubClass' id='UseMultiSubClass' value='1'<?= $Result[0][10] ? " checked" : ""; ?> />
                <?= CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_MULTI_SUB_CLASS; ?>
            </div>

            <div class='row_1'>
                <div class='col_1'>
                    ID
                </div>

                <div class='col_2'>
                    <?php
                        if ($loc->SubdivisionID) {
                            $wsts = CONTROL_CONTENT_SUBCLASS_ONSECTION;
                        } else {
                            $wsts = CONTROL_CONTENT_SUBCLASS_ONSITE;
                        }
                        printf(CONTROL_CONTENT_SUBCLASS_CLASSNAME, $wsts);
                    ?>
                </div>

                <div class='col_3'>
                    <?= CONTROL_CONTENT_CLASS; ?>
                </div>

                <div class='col_4'>
                    <?= CONTROL_CONTENT_SUBDIVISION_FUNCS_PRIORITY; ?>
                </div>

                <div class='col_5'>
                    <?= CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_DELETE; ?>
                </div>
            </div>

            <?php
                $SubClassID_list = array();

                foreach ($Result as $Array) {
                    $SubClassID_list[] = array(
                            'ID' => $Array[0],
                            'name' => $Array[1]);

                    echo "<div>
                            <div class='col_1" . (!$Array[4] ? " disabled" : "") . "'>
                                $Array[0]
                            </div>

                            <div class='col_2'>
                                <a href='$ADMIN_PATH#subdivision.edit({$loc->SubdivisionID})' >" . (!$Array[4] ? "<font color=cccccc>" : "") . $Array[1] . "</a>
                            </div>

                            <div class='col_3'>
                                ".(!$Array[9] ? "<a href='$ADMIN_PATH#dataclass.edit({$Array[5]})' target='_blank'>" . (!$Array[4] ? "<font color='cccccc'>" : "") . $Array[2] . "</a>"
                                                : "<a href='$ADMIN_PATH#systemclass.edit({$Array[9]})' target='_blank'>" . (!$Array[4] ? "<font color='cccccc'>" : "") . $Array[2] . "</a>")."
                            </div>

                            <div class='col_4'>
                                " . nc_admin_input_simple("Priority" . $Array[0], $Array[3], 3, '', "class='s'") . "
                            </div>

                            <div class='col_5'>
                                " . nc_admin_checkbox_simple("Delete" . $Array[0], $Array[0]) . "
                            </div>
                        </div>";
                }


            ?>
        </div><?

            echo $nc_core->token->get_input();
            echo "<input type='hidden' name='phase' value='5' />";
            echo "<input type='hidden' name='CatalogueID' value='{$loc->CatalogueID}' />";
            echo "<input type='hidden' name='SubdivisionID' value='{$loc->SubdivisionID}' />";
            echo "<input type='submit' style='display: none;' />";

            ?>
    </div>
            <?
        } else {
            nc_print_status(CONTROL_CONTENT_SUBCLASS_MSG_NONE, 'info');
        }
        return $SubClassID_list;
    }

#######################################################################

    function  ActionForm($SubClassID, $phase, $type) {
        global $loc, $perm;
        global $SubdivisionID;
        global $CatalogueID;
        global $UI_CONFIG, $SUB_FOLDER, $HTTP_ROOT_PATH, $MODULE_FOLDER, $ADMIN_FOLDER, $ADMIN_PATH;

        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;

        $SubdivisionID = $SubdivisionID ? intval($SubdivisionID) : $nc_core->sub_class->get_by_id($SubClassID, 'Subdivision_ID');
        $CatalogueID = $CatalogueID ? intval($CatalogueID) : $nc_core->subdivision->get_by_id($SubdivisionID, 'Catalogue_ID');

        if ($type == 2) {
            $SubEnv = $nc_core->sub_class->get_by_id($SubClassID);
            $ClassEnv = $db->get_row("SELECT * FROM `Class` WHERE `Class_ID` = '" . intval($SubEnv["Class_ID"]) . "'", ARRAY_A);
        } elseif ($type == 1) {
            if (!$SubdivisionID) {
                $SubEnv = $db->get_row("SELECT * FROM `Catalogue` WHERE `Catalogue_ID` = '" . $CatalogueID . "'", ARRAY_A);
            } else {
                $SubEnv = $nc_core->subdivision->get_by_id($SubdivisionID);
            }
            $UI_CONFIG->locationHash = "subclass.add(" . $SubdivisionID . ")";
        }

        if ($phase == 2 && $type == 1 && $SubdivisionID) {
            $Sub_Class_count = $db->get_var("SELECT COUNT(*) FROM `Sub_Class` WHERE `Subdivision_ID` = '" . $SubdivisionID . "'");
            if (!$Sub_Class_count) {
                nc_print_status(CONTROL_CONTENT_SUBDIVISION_FIRST_SUBCLASS, 'info');
            }
        }

        echo "<form enctype='multipart/form-data' method='post' action='SubClass.php' id='adminForm'>";

        if ($type == 1) {   // insert
            global $ClassID;
            $Sub_Class_fs = $db->get_var("SELECT c.`File_Mode` FROM `Sub_Class` AS sc, `Class` AS c WHERE sc.`Class_ID` = c.`Class_ID` AND sc.`Subdivision_ID` = '" . $SubdivisionID . "'");
            $classes = $db->get_results("SELECT `Class_ID` as value,
      CONCAT(`Class_ID`, '. ', `Class_Name`) as description,
      `Class_Group` as optgroup
      FROM `Class`
      WHERE `ClassTemplate` = 0 ".($Sub_Class_fs !== NULL ? "AND File_Mode = ".$Sub_Class_fs : " " )."
      ORDER BY `Class_Group`, `Class_ID`", ARRAY_A);

            if (!$ClassID) {
                $selected_value = $db->get_var("SELECT `Class_ID` FROM `Class` WHERE `Class_ID` = 1");
                if (!$selected_value)
                    $selected_value = $db->get_var("SELECT `Class_ID` FROM `Class` ORDER BY `Class_Group`, `Class_ID` LIMIT 1");
            } else {
                $selected_value = $ClassID;
            }
            $ClassEnv = $db->get_row("SELECT * FROM `Class` WHERE `Class_ID`='" . intval($selected_value) . "'", ARRAY_A);

            $Array["AllowTags"] = -1;
            $Array["NL2BR"] = -1;
            $Array["UseCaptcha"] = -1;

            global $SubClassName, $Read_Access_ID, $Write_Access_ID, $Edit_Access_ID, $DefaultAction;
            global $Checked_Access_ID, $Delete_Access_ID;
            global $SubscribeAccessID, $Moderation_ID, $Checked, $Priority, $CustomSettings;
            global $EnglishName, $DaysToHold, $AllowTags, $NL2BR, $RecordsPerPage, $SortBy, $UseCaptcha, $Class_Template_ID, $isNaked;
            if (nc_module_check_by_keyword("cache"))
                global $CacheForUser;

            if ($Priority == "" && $Checked == "")
                $Checked = 1;
            if ($Priority == "") {
                $Priority = $db->get_var("SELECT (`Priority` + 1) FROM `Sub_Class` WHERE `Subdivision_ID` = '" . $loc->SubdivisionID . "' ORDER BY `Priority` DESC LIMIT 1");
                list($SubClassName, $EnglishName) = $db->get_row("SELECT `Subdivision_Name`, `EnglishName` FROM `Subdivision` WHERE `Subdivision_ID` = '" . $loc->SubdivisionID . "'", ARRAY_N);
            }

            $Array["Sub_Class_Name"] = $SubClassName;
            $Array["Read_Access_ID"] = $Read_Access_ID;
            $Array["Write_Access_ID"] = $Write_Access_ID;
            $Array["Edit_Access_ID"] = $Edit_Access_ID;
            $Array["Checked_Access_ID"] = $Checked_Access_ID;
            $Array["Delete_Access_ID"] = $Delete_Access_ID;
            $Array["Subscribe_Access_ID"] = $SubscribeAccessID;
            if (nc_module_check_by_keyword("cache")) {
                $Array ["Cache_Access_ID"] = $CacheAccessID;
                $Array ["Cache_Lifetime"] = $CacheLifetime;
                $Array["CacheForUser"] = $CacheForUser != "" ? $CacheForUser : -1;
            }
            $Array["Moderation_ID"] = $Moderation_ID;
            $Array["DefaultAction"] = $DefaultAction;
            $Array["Checked"] = $Checked;
            $Array["Priority"] = $Priority;
            $Array["EnglishName"] = $EnglishName . ($Sub_Class_count ? '-'.$Sub_Class_count : '');
            $Array["DaysToHold"] = $DaysToHold;
            if ($AllowTags != "")
                $Array["AllowTags"] = $AllowTags;
            if ($NL2BR != "")
                $Array["NL2BR"] = $NL2BR;
            if ($UseCaptcha != "")
                $Array["UseCaptcha"] = $UseCaptcha;
            $Array["RecordsPerPage"] = $RecordsPerPage;
            $Array["SortBy"] = $SortBy;
            $Array["Class_Template_ID"] = $Class_Template_ID;
            $Array["isNaked"] = $isNaked;
            $Array["SrcMirror"] = $SrcMirror;

            // visual settings
            $Array['CustomSettingsTemplate'] = $db->get_var("SELECT `CustomSettingsTemplate` FROM `Class`
      WHERE `Class_ID` = '" . ($Class_Template_ID ? $Class_Template_ID : $ClassID) . "'");

            $classInfo = "<tr><td>";

            $classInfo .= "
                <font color='gray'>" . CONTROL_CONTENT_SUBCLASS_TYPE . ":<br/>

                <div id='nc_mirror_radio'>
                    ". nc_get_modal_radio('is_mirror', array(
                            array(
                                    'attr' => array(
                                            'value' => '0',
                                            'onClick' => "\$nc('#nc_class_select').show(); \$nc('#nc_mirror_select').hide(); \$nc('#loadClassTemplates').html('')"),
                                    'desc' => CONTROL_CONTENT_SUBCLASS_TYPE_SIMPLE),
                            array(
                                    'attr' => array(
                                            'value' => '1',
                                            'onClick' => "\$nc('#nc_class_select').hide(); \$nc('#nc_mirror_select').show(); \$nc('#loadClassTemplates').html('')"),
                                    'desc' => CONTROL_CONTENT_SUBCLASS_TYPE_MIRROR)
                    ), 0) . "
                </div>";

            $classInfo .= "<div id='nc_class_select'>";

            if (!empty($classes)) {
                $classInfo.= "<font color='gray'>".CONTROL_CLASS_CLASS.":<br>";
                $classInfo.= "<select id='ClassID' name='ClassID' onchange='if (this.options[this.selectedIndex].value) {loadClassDescription(this.options[this.selectedIndex].value); loadClassCustomSettings(this.options[this.selectedIndex].value); loadClassTemplates(this.options[this.selectedIndex].value, 0, ".$CatalogueID.");}'>";
                $classInfo.= nc_select_options($classes, $selected_value);
                $classInfo.= "</select>";
                $classInfo.= "<div id='loadClassDescription'></div>";
                $classInfo.= "<script>if ('" . $selected_value . "') {loadClassDescription(" . $selected_value . ");}</script>";
            } else {
                $classInfo.= CONTROL_CLASS_NONE;
            }

            $classInfo .= "</div>";

            $classInfo .= "
                <div id='nc_mirror_select' style='display: none;'>
                    <div>
                        " . CONTROL_CONTENT_SUBCLASS_SRCMIRROR . ":
                    </div>

                    <div>
                        <span id='cs_SrcMirror_caption' style='font-weight:bold;'>" . CONTROL_CONTENT_SUBCLASS_SRCMIRROR_NONE . "</span>
                            <input id='cs_SrcMirror_value' name='SrcMirror' type='hidden' value='' />&nbsp;&nbsp;
                            <a href='#' onclick=\"window.open('/netcat/admin/related/select_subclass.php?cs_type=rel_cc&amp;cs_field_name=SrcMirror', 'nc_popup_SrcMirror', 'width=800,height=500,menubar=no,resizable=no,scrollbars=yes,toolbar=no,resizable=yes'); return false;\">
								" . CONTROL_CONTENT_SUBCLASS_SRCMIRROR_EDIT . "
							</a>&nbsp;&nbsp;

                            <a href='#' onclick=\"document.getElementById('cs_SrcMirror_value').value='';document.getElementById('cs_SrcMirror_caption').innerHTML = '" . CONTROL_CONTENT_SUBCLASS_SRCMIRROR_NONE . "';return false;\">
                                " .CONTROL_CONTENT_SUBCLASS_SRCMIRROR_DELETE . "
                            </a>
                    </div>

                </div>";

            $classInfo.= "<div id='loadClassTemplates'></div>";
            $classInfo.= "
                        <script>
                            var old_val = \$nc('#cs_SrcMirror_value').val();
                            setInterval(function() {
                                var val = \$nc('#cs_SrcMirror_value').val();
                                if (old_val != val) {
                                    if (val) {
                                        loadClassTemplates(val, 0, 0, 1);
                                    }
                                    old_val = val;
                                }
                            }, 200);
                            if ('".$selected_value."') {loadClassTemplates(".$selected_value.($Class_Template_ID ? ", ".$Class_Template_ID : ", 0").($CatalogueID ? ", ".$CatalogueID : "").");}
                        </script>";

            $classInfo.= "</td></tr>\n";
        }

        if ($type == 2) {


            if (nc_module_check_by_keyword("cache")) {
                $cache_select_fields = "s.`Cache_Access_ID`, s.`Cache_Lifetime`, s.`CacheForUser`,";
            } else {
                $cache_select_fields = "";
            }
            $select = "SELECT
          " . $cache_select_fields . "
          s.`Sub_Class_Name`,
          s.`Subdivision_ID`,
          s.`Priority`,
          s.`Read_Access_ID`,
          s.`Write_Access_ID`,
          s.`Edit_Access_ID`,
          s.`Checked_Access_ID`,
          s.`Delete_Access_ID`,
          s.`Moderation_ID`,
          s.`EnglishName`,
          s.`Checked`,
          s.`Subscribe_Access_ID`,
          s.`DaysToHold`,
          s.`AllowTags`,
          s.`NL2BR`,
          s.`RecordsPerPage`,
          s.`SortBy`,
          s.`Created`,
          s.`LastUpdated`,
          c.`Class_Name`,
          c.`Class_ID`,
          c.`System_Table_ID`,
          s.`DefaultAction`,
          s.`UseCaptcha`,
          c.`CustomSettingsTemplate`,
          s.`CustomSettings`,
          s.`Class_Template_ID`,
          s.`isNaked`,
          s.`SrcMirror`,
          s.`AllowRSS`,
          s.`Edit_Class_Template`
        FROM
          `Sub_Class` as s,
          `Class` as c
        WHERE
          `Sub_Class_ID` = '" . intval($SubClassID) . "'
        AND
          c.`Class_ID` = s.`Class_ID`";

            $Array = $db->get_row($select, ARRAY_A);

            if ($db->is_error) {
                throw new nc_Exception_DB_Error($db->last_query, $db->last_error);
            }

            if (empty($Array)) {
                nc_print_status(CONTROL_CONTENT_CATALOUGE_FUNCS_ERROR_NOSUBCLASS, 'info');
                return;
            }

            if ($Array['Class_Template_ID']) {
                $Array['CustomSettingsTemplate'] = $db->get_var("SELECT `CustomSettingsTemplate` FROM `Class`
          WHERE `Class_ID` = '" . intval($Array['Class_Template_ID']) . "'");
            }

            $mobile = $nc_core->catalogue->get_by_id($CatalogueID, 'ncMobile');

            $SQL = "SELECT `Class_Name`,
                           `Class_ID`
                        FROM `Class`
                            WHERE `ClassTemplate` = {$Array['Class_ID']}
                              AND `Type` IN ('useful', 'title', 'mobile', 'responsive')";

            $classTemplatesArr = $db->get_results($SQL, ARRAY_A);

            $class_array = nc_get_class_template_array_by_id($Array['Class_Template_ID'] ? $Array['Class_Template_ID'] : $Array['Class_ID']);

            $edit_class_select = null;
            if (count($class_array) > 1) {
                $edit_class_select = nc_get_class_template_form_select_by_array($class_array, $Array['Edit_Class_Template']);
            }

            $classInfo = nc_sub_class_get_classInfo($perm, $Array, $classTemplatesArr, $edit_class_select);
        }

        $wsts_msg = nc_sub_class_get_wsts_msg($wsts);

        require_once($ADMIN_FOLDER."related/format.inc.php");
        $field = new field_relation_subclass();

        $fieldsets = new nc_admin_fieldset_collection();
        $fieldsets->set_prefix(nc_sub_class_get_prefix($SubClassID, $Array, true));
        $fieldsets->set_static_prefix(nc_sub_class_get_style_prefix());
        $fieldsets->set_suffix("
                </div>
                " . $nc_core->token->get_input() . "
                <input type='hidden' name='phase' value='$phase' />
                <input type='hidden' name='SubClassID' value='$SubClassID' />
                <input type='hidden' name='SubdivisionID' value='{$loc->SubdivisionID}' />
                <input type='hidden' name='CatalogueID' value='{$loc->CatalogueID}' />
                <input type='submit' class='hidden'>
            </form>");
        $fieldsets->new_fieldset('main_info', CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MAININFO)->add(nc_sub_class_get_main_info($Array, $classInfo, $wsts_msg, $field));
        $fieldsets->new_fieldset('objlist', CONTROL_CONTENT_SUBDIVISION_FUNCS_OBJ_HOWSHOW)->add(nc_sub_class_get_objlist($Array));


        if ($Array['CustomSettingsTemplate']) {
            require_once $ADMIN_FOLDER . 'array_to_form.inc.php';
            $values = $CustomSettings ? $CustomSettings : $Array['CustomSettings'];
            $a2f = new nc_a2f($Array['CustomSettingsTemplate'], 'CustomSettings');
            $a2f->set_value($values);
            $fieldsets->new_fieldset('CustomSettings', CONTROL_CONTENT_SUBDIVISION_CUSTOM_SETTINGS_TEMPLATE)->add(nc_sub_class_get_CustomSettings($a2f));
        } else {
            $fieldsets->new_fieldset('CustomSettings')->add("<div id='loadClassCustomSettings'></div>");
        }


        $fieldsets->new_fieldset('access', CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_ACCESS)->add(nc_subdivision_show_access($SubEnv));

        if ($type == 2) {
            $fieldsets->new_fieldset('rss', 'RSS')->add(nc_subclass_show_export('rss', $SubdivisionID, $SubClassID));
            $fieldsets->new_fieldset('xml', 'XML')->add(nc_subclass_show_export('xml', $SubdivisionID, $SubClassID));
        }

        if (nc_module_check_by_keyword('cache')) {
            $fieldsets->new_fieldset('cache', CONTROL_CONTENT_SUBCLASS_FUNCS_CACHE)->add(nc_subdivision_show_cache($SubEnv));
        }

        if (nc_module_check_by_keyword('comments')) {
            require_once $nc_core->MODULE_FOLDER . 'comments/function.inc.php';
            $fieldsets->new_fieldset('comments', CONTROL_CONTENT_SUBCLASS_FUNCS_COMMENTS)->add(nc_subdivision_show_comments($SubEnv));
        }

        echo $fieldsets->to_string();

        if ($type == 1) {
            $UI_CONFIG->actionButtons[] = array("id" => "submit",
                    "caption" => STRUCTURE_TAB_SUBCLASS_ADD,
                    "action" => "mainView.submitIframeForm()",
                    "align" => "right"
            );

        } elseif ($type == 2) {
            $UI_CONFIG->actionButtons[] = array("id" => "submit",
                    "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SAVE,
                    "action" => "mainView.submitIframeForm()",
                    "align" => "right"
            );
        }
}

function ActionForm_for_modal_prefix($SubClassID_list, $one = false) {
    $nc_core = nc_Core::get_object();

    $li_array = array();

    if (!$one) {
        $li_array[] = "<li id='nc_sub_class_list' class='button_on'>".SUBDIVISION_TAB_INFO_TOOLBAR_CCLIST."</li>";
        foreach ($SubClassID_list as $SubClass) {
            $li_array[] = "<li id='nc_sub_class_{$SubClass['ID']}'>{$SubClass['name']}</li>";
        }
        $active = 'nc_sub_class_list_div';
    } else {
        $active = "nc_sub_class_{$SubClassID_list[0]['ID']}_div";
    }

?>
    <div class='nc_admin_form_menu' style='padding-top: 20px;'>
            <h2><?= $one ? $SubClassID_list[0]['name'] : STRUCTURE_TAB_USED_SUBCLASSES; ?></h2>
            <div id='nc_object_slider_menu' class='slider_block_2' style='padding-top: 0px; padding-bottom: 15px;'>
                <ul>
                    <?= join("\n                    ", $li_array); ?>
                </ul>
            </div>
            <div class='nc_admin_form_menu_hr'></div>
        </div>

        <script>
            var nc_slider_li = $nc('div#nc_object_slider_menu ul li');

            nc_slider_li.click(function() {
                nc_slider_li.removeClass('button_on');
                $nc(this).addClass('button_on');
                $nc('div.nc_current_content').html($nc('div#' + this.id + '_div').html());
            });

            setTimeout(function () {
                $nc('div.nc_current_content').html($nc('div#<?= $active; ?>').html());
            }, 250);
        </script>

        <div class='nc_admin_form_body'>
            <form id='adminForm' method='post' action='<?= $nc_core->ADMIN_PATH ?>subdivision/SubClass.php' enctype='multipart/form-data'>
                <div class='nc_current_content'>
<?php

}

function ActionForm_for_modal_suffix() {
?>
                </div>
                <input type='hidden' name='isNaked' value='1' />
            </form>
    </div>
        <div class='nc_admin_form_buttons'>
            <input class='nc_admin_metro_button' type='button' value='<?= NETCAT_REMIND_SAVE_SAVE; ?>' title='<?= NETCAT_REMIND_SAVE_SAVE; ?>' disable />
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
<?php
}

function ActionForm_for_modal($SubClassID) {
        global $CatalogueID, $SubdivisionID, $loc, $perm;
        global $SUB_FOLDER, $HTTP_ROOT_PATH, $MODULE_FOLDER, $ADMIN_FOLDER, $ADMIN_PATH;

        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;
        $type = 2;

        $SubdivisionID = $SubdivisionID ? +$SubdivisionID : $nc_core->sub_class->get_by_id($SubClassID, 'Subdivision_ID');
        $CatalogueID = $CatalogueID ? +$CatalogueID : $nc_core->subdivision->get_by_id($SubdivisionID, 'Catalogue_ID');

        $SubEnv = $nc_core->sub_class->get_by_id($SubClassID);
        $ClassEnv = $db->get_row("SELECT * FROM `Class` WHERE `Class_ID` = '" . intval($SubEnv["Class_ID"]) . "'", ARRAY_A);

        if (nc_module_check_by_keyword("cache")) {
            $cache_select_fields = "s.`Cache_Access_ID`, s.`Cache_Lifetime`, s.`CacheForUser`,";
        } else {
            $cache_select_fields = "";
        }
        $select = "SELECT " . $cache_select_fields . "
                          s.`Sub_Class_Name`,
                          s.`Subdivision_ID`,
                          s.`Priority`,
                          s.`Read_Access_ID`,
                          s.`Write_Access_ID`,
                          s.`Edit_Access_ID`,
                          s.`Checked_Access_ID`,
                          s.`Delete_Access_ID`,
                          s.`Moderation_ID`,
                          s.`EnglishName`,
                          s.`Checked`,
                          s.`Subscribe_Access_ID`,
                          s.`DaysToHold`,
                          s.`AllowTags`,
                          s.`NL2BR`,
                          s.`RecordsPerPage`,
                          s.`SortBy`,
                          s.`Created`,
                          s.`LastUpdated`,
                          c.`Class_Name`,
                          c.`Class_ID`,
                          c.`System_Table_ID`,
                          s.`DefaultAction`,
                          s.`UseCaptcha`,
                          c.`CustomSettingsTemplate`,
                          s.`CustomSettings`,
                          s.`Class_Template_ID`,
                          s.`isNaked`,
                          s.`SrcMirror`,
                          s.`AllowRSS`,
                          s.`Edit_Class_Template`
                       FROM `Sub_Class` as s,
                            `Class` as c
                           WHERE `Sub_Class_ID` = " . +$SubClassID . "
                             AND c.`Class_ID` = s.`Class_ID`";

        $Array = $db->get_row($select, ARRAY_A);

        if ($db->is_error) {
            throw new nc_Exception_DB_Error($db->last_query, $db->last_error);
        }

        if (empty($Array)) {
            nc_print_status(CONTROL_CONTENT_CATALOUGE_FUNCS_ERROR_NOSUBCLASS, 'info');
            return;
        }

        if ($Array['Class_Template_ID']) {
            $Array['CustomSettingsTemplate'] = $db->get_var("SELECT `CustomSettingsTemplate`
                                                                 FROM `Class`
                                                                     WHERE `Class_ID` = " . $Array['Class_Template_ID']);
        }

        $mobile = $nc_core->catalogue->get_by_id($CatalogueID, 'ncMobile');
        $classTemplatesArr = $db->get_results("SELECT `Class_Name`,
                                                      `Class_ID`
                                                   FROM `Class`
                                                       WHERE `ClassTemplate` = '".$Array['Class_ID']."'
                                                         AND `Type` ".(!$mobile ? "IN ('useful', 'title', 'mobile')" : "= 'mobile'"), ARRAY_A);

         $class_array = nc_get_class_template_array_by_id($Array['Class_Template_ID'] ? $Array['Class_Template_ID'] : $Array['Class_ID']);

        $edit_class_select = null;
        if (count($class_array) > 1) {
            $edit_class_select = nc_get_class_template_form_select_by_array($class_array, $Array['Edit_Class_Template']);
        }

        $classInfo = nc_sub_class_get_classInfo($perm, $Array, $classTemplatesArr, $edit_class_select);

        if ($loc->SubdivisionID) {
            $wsts = CONTROL_CONTENT_SUBCLASS_ONSECTION;
        } else {
            $wsts = CONTROL_CONTENT_SUBCLASS_ONSITE;
        }

        $wsts_msg = nc_sub_class_get_wsts_msg($wsts);

        require_once($ADMIN_FOLDER."related/format.inc.php");
        $field = new field_relation_subclass();

        $fieldsets = new nc_admin_fieldset_collection();
        $fieldsets->set_prefix(nc_sub_class_get_prefix($SubClassID, $Array));
        $fieldsets->set_static_prefix(nc_sub_class_get_style_prefix());
        $fieldsets->set_suffix("
            </div>
            " . $nc_core->token->get_input() . "
            <input type='hidden' name='phase' value='4' />
            <input type='hidden' name='SubClassID' value='$SubClassID' />
            <input type='hidden' name='SubdivisionID' value='{$loc->SubdivisionID}' />
            <input type='hidden' name='CatalogueID' value='{$loc->CatalogueID}' />
            <input type='submit' style='display: none;' />
            ");

        $fieldsets->new_fieldset('main_info', CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_MAININFO)->add(nc_sub_class_get_main_info($Array, $classInfo, $wsts_msg, $field));
        $fieldsets->new_fieldset('objlist', CONTROL_CONTENT_SUBDIVISION_FUNCS_OBJ_HOWSHOW)->add(nc_sub_class_get_objlist($Array));

        if ($Array['CustomSettingsTemplate']) {
            require_once $ADMIN_FOLDER . 'array_to_form.inc.php';
            $values = $CustomSettings ? $CustomSettings : $Array['CustomSettings'];
            $a2f = new nc_a2f($Array['CustomSettingsTemplate'], 'CustomSettings');
            $a2f->set_value($values);
            $fieldsets->new_fieldset('CustomSettings', CONTROL_CONTENT_SUBDIVISION_CUSTOM_SETTINGS_TEMPLATE)->add(nc_sub_class_get_CustomSettings($a2f));
        }

        $fieldsets->new_fieldset('access', CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_ACCESS)->add(nc_subdivision_show_access($SubEnv));
        #$fieldsets->new_fieldset('rss', 'RSS')->add(nc_subclass_show_export('rss', $SubdivisionID, $SubClassID));
        #$fieldsets->new_fieldset('xml', 'XML')->add(nc_subclass_show_export('xml', $SubdivisionID, $SubClassID));

        if (nc_module_check_by_keyword('cache')) {
            $fieldsets->new_fieldset('cache', CONTROL_CONTENT_SUBCLASS_FUNCS_CACHE)->add(nc_subdivision_show_cache($SubEnv));
        }

        if (nc_module_check_by_keyword('comments')) {
            $fieldsets->new_fieldset('comments', CONTROL_CONTENT_SUBCLASS_FUNCS_COMMENTS)->add(nc_subdivision_show_comments($SubEnv));
        }

        echo $fieldsets->to_string();
}

function ActionSubClassCompleted($type) {
    global $nc_core, $db, $ClassID;
    global $loc, $ADMIN_FOLDER, $MODULE_FOLDER, $CustomSettings;

    $params = array('Priority', 'Checked', 'SubClassName', 'EnglishName', 'Class_Template_ID',
            'DefaultAction', 'isNaked', 'AllowTags', 'NL2BR', 'UseCaptcha',
            'RecordsPerPage', 'SortBy', 'Read_Access_ID', 'Write_Access_ID', 'Cache_Lifetime',
            'Edit_Access_ID', 'Checked_Access_ID', 'Delete_Access_ID', 'Moderation_ID',
            'CacheAccessID', 'CacheLifetime', 'CacheForUser', 'CommentAccessID', 'Edit_Class_Template',
            'CommentsEditRules', 'CommentsDeleteRules', 'SubClassID', 'SubdivisionID', 'CatalogueID', 'SrcMirror', 'Cache_Access_ID');

    foreach ($params as $v)
        $$v = $nc_core->input->fetch_get_post($v);

    if (nc_module_check_by_keyword("comments")) {
        include_once ($MODULE_FOLDER . "comments/function.inc.php");
    }

    if (+$_POST['is_mirror']) {
        $ClassID = $nc_core->sub_class->get_by_id(+$SrcMirror, 'Class_ID');
    }

    if ($Class_Template_ID == $ClassID)
        $Class_Template_ID = 0;

    if ($Priority === '') {
        $Priority = $db->get_var("SELECT (`Priority` + 1) FROM `Sub_Class` WHERE `Subdivision_ID` = '" . $loc->SubdivisionID . "' ORDER BY `Priority` DESC LIMIT 1");
    }


    if ($type == 1) {
        if (nc_module_check_by_keyword("cache")) {
            $cache_insert_fields = "`Cache_Access_ID`, `Cache_Lifetime`, `CacheForUser`,";
            $cache_insert_values = "'" . $Cache_Access_ID . "', '" . $Cache_Lifetime . "', '" . $CacheForUser . "',";
        } else {
            $cache_insert_fields = "";
            $cache_insert_values = "";
        }
        $insert = "INSERT INTO `Sub_Class` (" . $cache_insert_fields . "`Subdivision_ID`, `Catalogue_ID`, `Class_ID`, `Sub_Class_Name`, `Read_Access_ID`, `Write_Access_ID`, `Edit_Access_ID`, `Checked_Access_ID`, `Delete_Access_ID`, `Subscribe_Access_ID`, `Moderation_ID`, `Checked`, `Priority`, `EnglishName`, `DaysToHold`, `AllowTags`, `NL2BR`, `RecordsPerPage`, `SortBy`, `Created`, `DefaultAction`, `UseCaptcha`, `CustomSettings`, `Class_Template_ID`, `isNaked`, `SrcMirror`)";
        $insert.= " VALUES (" . $cache_insert_values . "'" . $loc->SubdivisionID . "', '" . $loc->CatalogueID . "', '" . $ClassID . "', '" . $db->escape($SubClassName) . "', '" . $Read_Access_ID . "', '" . $Write_Access_ID . "', '" . $Edit_Access_ID . "', '" . $Checked_Access_ID . "','" . $Delete_Access_ID . "','" . $SubscribeAccessID . "', '" . $Moderation_ID . "', '" . $Checked . "', '" . $Priority . "', '" . $EnglishName . "', ";

                                $insert.= $DaysToHold == "" ? "NULL, " : "'".$DaysToHold."', ";
                                $insert.= "'".$AllowTags."', ";
                                $insert.= "'".$NL2BR."', ";
                                $insert.= $RecordsPerPage == "" ? "NULL" : "'".$RecordsPerPage."'";
                                $insert.= ",'$SortBy','".date("Y-m-d H:i:s")."','".$DefaultAction."', '".$UseCaptcha."', '".addcslashes($CustomSettings, "'")."', '".$Class_Template_ID."', '".$isNaked."', '".$SrcMirror."')";
                 
        $db->query($insert);
        // inserted ID
        $insertedSubClassID = $db->insert_id;

        // execute core action
        $nc_core->event->execute("addSubClass", $loc->CatalogueID, $loc->SubdivisionID, $insertedSubClassID, $ClassID);

        if (nc_module_check_by_keyword("comments")) {
            if ($CommentAccessID > 0) {
                // add comment relation
                $CommentRelationID = nc_comments::addRule($db, array($loc->CatalogueID, $loc->SubdivisionID, $insertedSubClassID), $CommentAccessID, $CommentsEditRules, $CommentsDeleteRules);
                // update inserted data
                $db->query("UPDATE `Sub_Class` SET `Comment_Rule_ID` = '" . (int) $CommentRelationID . "' WHERE `Sub_Class_ID` = '" . (int) $insertedSubClassID . "'");
            }
        }

        return $insertedSubClassID;
    }
    
    if ($type == 2) {
        $cur_checked = $db->get_var("SELECT `Checked` FROM `Sub_Class` WHERE `Sub_Class_ID` = '" . $SubClassID . "'");
        if (nc_module_check_by_keyword("comments")) {
            $CommentData = nc_comments::getRuleData($db, array($loc->CatalogueID, $loc->SubdivisionID, $SubClassID));
            $CommentRelationID = $CommentData['ID'];

            switch (true) {
                case $CommentAccessID > 0 && $CommentRelationID:
                    // update comment rules
                    nc_comments::updateRule($db, array($loc->CatalogueID, $loc->SubdivisionID, $SubClassID), $CommentAccessID, $CommentsEditRules, $CommentsDeleteRules);
                    break;
                case $CommentAccessID > 0 && !$CommentRelationID:
                    // add comment relation
                    $CommentRelationID = nc_comments::addRule($db, array($loc->CatalogueID, $loc->SubdivisionID, $SubClassID), $CommentAccessID, $CommentsEditRules, $CommentsDeleteRules);
                    break;
                case $CommentAccessID <= 0 && $CommentRelationID:
                    // delete comment rules
                    nc_comments::dropRuleSubClass($db, $SubClassID);
                    $CommentRelationID = 0;
                    break;
            }
        }

        $update = "UPDATE `Sub_Class` SET ";
        $update.= "`Sub_Class_Name` = '" . $db->escape($SubClassName) . "',";
        $update.= "`Read_Access_ID` = '" . $Read_Access_ID . "',";
        $update.= "`Write_Access_ID` = '" . $Write_Access_ID . "',";
        $update.= "`Edit_Access_ID` = '" . $Edit_Access_ID . "',";
        $update.= "`Checked_Access_ID` = '" . $Checked_Access_ID . "',";
        $update.= "`Delete_Access_ID` = '" . $Delete_Access_ID . "',";
        $update.= "`Subscribe_Access_ID` = '" . $SubscribeAccessID . "',";
        if (nc_module_check_by_keyword("cache")) {
            $update.= "`Cache_Access_ID` = '" . $Cache_Access_ID . "',";
            $update.= "`Cache_Lifetime` = '" . $Cache_Lifetime . "',";
            $update.= "`CacheForUser` = '" . $CacheForUser . "',";
        }
        if (nc_module_check_by_keyword("comments")) {
            $update.= "`Comment_Rule_ID` = '" . $CommentRelationID . "',";
        }
        $update.= "`Moderation_ID` = '" . $Moderation_ID . "',";
        $update.= "`Checked` = '" . $Checked . "',";
        //$update.= "`Priority` = '" . $Priority . "',";
        $update.= "`EnglishName` = '" . $EnglishName . "',";
        $update.= "`DefaultAction` = '" . $DefaultAction . "',";
        $update.= $DaysToHold == "" ? "`DaysToHold` = NULL," : "`DaysToHold` = '" . $DaysToHold . "',";
        $update.= "`AllowTags` = '" . $AllowTags . "',";
        $update.= "`NL2BR` = '" . $NL2BR . "',";
        $update.= $RecordsPerPage == "" ? "`RecordsPerPage` = NULL," : "`RecordsPerPage` = '" . $RecordsPerPage . "',";
        $update.= "`SortBy` = '" . $SortBy . "',";
        $update.= "`UseCaptcha` = '" . $UseCaptcha . "', ";
        $update.= "`CustomSettings` = '" . addcslashes($CustomSettings, "'") . "', ";
        $update.= "`Class_Template_ID` = '" . $Class_Template_ID . "', ";
        $update.= "`Edit_Class_Template` = '" . $Edit_Class_Template . "', ";
        $update.= "`isNaked` = '" . $isNaked . "', ";
        $update.= "`SrcMirror` = '" . $SrcMirror . "', ";
        $update.= "`AllowRSS` = '" . intval($nc_core->input->fetch_get_post('AllowRSS' . $SubClassID)) . "',";
        $update.= "`AllowXML` = '" . intval($nc_core->input->fetch_get_post('AllowXML' . $SubClassID)) . "'";
        $update.= " WHERE `Sub_Class_ID` = '" . $SubClassID . "'";
        
        $db->query($update);

        if ($db->is_error) {
            throw new nc_Exception_DB_Error($db->last_query, $db->last_error);
        }

        $subclass_data = $nc_core->sub_class->get_by_id($SubClassID);
        // execute core action
        $nc_core->event->execute("updateSubClass", $subclass_data['Catalogue_ID'], $subclass_data['Subdivision_ID'], $SubClassID, $subclass_data['Class_ID']);

        // произошло включение / выключение
        if ($cur_checked != $Checked) {
            $nc_core->event->execute($Checked ? "checkSubClass" : "uncheckSubClass", $subclass_data['Catalogue_ID'], $subclass_data['Subdivision_ID'], $SubClassID, $subclass_data['Class_ID']);
        }
        return $db->rows_affected;
    }
}

###############################################################################

function ShowSubClassMenu($SubClassID, $phase1, $action1, $phase2, $action2, $phase3, $action3) {
    global $db, $nc_core;
    global $EDIT_DOMAIN, $HTTP_ROOT_PATH, $DOMAIN_NAME, $ADMIN_PATH, $SUB_FOLDER;
    $SubClassID = intval($SubClassID);
    $Array = $db->get_row("SELECT * FROM Sub_Class WHERE Sub_Class_ID=" . $SubClassID, ARRAY_A);

    list($Class_Name, $System_Table_ID) = $db->get_row("SELECT Class_Name,System_Table_ID FROM Class WHERE Class_ID=" . $Array["Class_ID"], ARRAY_N);

    //$SubEnv = InheritSubClassEnv($SubClassID);
    $SubEnv = $nc_core->sub_class->get_by_id($SubClassID);

    list($Domain, $HiddenURL) = $db->get_row("SELECT b.Domain,a.Hidden_URL FROM Subdivision AS a,Catalogue AS b where a.Catalogue_ID=b.Catalogue_ID AND a.Subdivision_ID=" . $Array["Subdivision_ID"], ARRAY_N);
    $HiddenHost = ($Domain ? $Domain : $DOMAIN_NAME);

    if (!$System_Table_ID) {
        $MessageCount = $db->get_var("SELECT COUNT(*) FROM Message" . $Array["Class_ID"] . " WHERE Sub_Class_ID=" . $SubClassID);
    }

    if ($SubEnv["Moderation_ID"] == 2) {
        $ModerationType = CLASSIFICATOR_TYPEOFMODERATION_MODERATION;
    } else {
        $ModerationType = CLASSIFICATOR_TYPEOFMODERATION_RIGHTAWAY;
    }//end if

    $UserGroupName = array(1 => CLASSIFICATOR_USERGROUP_ALL,
            2 => CLASSIFICATOR_USERGROUP_REGISTERED,
            3 => CLASSIFICATOR_USERGROUP_AUTHORIZED);

    //  In MySQL 4.1, TIMESTAMP display format changes to be the same as DATETIME.
    if ($Array['LastUpdated'][4] != '-') {
        $Array['LastUpdated'] = substr($Array['LastUpdated'], 0, 4) . "-" . substr($Array['LastUpdated'], 4, 2) . "-" . substr($Array['LastUpdated'], 6, 2) . " " . substr($Array['LastUpdated'], 8, 2) . ":" . substr($Array['LastUpdated'], 10, 2) . ":" . substr($Array['LastUpdated'], 12, 2);
    }
    ?>

    <table border=0 cellpadding=0 cellspacing=0 width=100% class='border-bottom'><tr>
                <table border=0 cellpadding=6 cellspacing=1 width=100%><tr><td bgcolor=white>
                            <table border=0 cellpadding=0 cellspacing=0 width=100%>
                                <tr><td width=50%><?
    if ($Array["Subdivision_ID"]) {
        $wsts = CONTROL_CONTENT_SUBCLASS_ONSECTION;
    } else {
        $wsts = CONTROL_CONTENT_SUBCLASS_ONSITE;
    }
    printf(CONTROL_CONTENT_SUBCLASS_CREATIONDATE, $wsts);
    ?>:</td><td><?= $Array["Created"]
    ?></td></tr>
                                <tr><td><?
                                    if ($Array["Subdivision_ID"]) {
                                        $wsts = CONTROL_CONTENT_SUBCLASS_ONSECTION;
                                    } else {
                                        $wsts = CONTROL_CONTENT_SUBCLASS_ONSITE;
                                    }
                                    printf(CONTROL_CONTENT_SUBCLASS_UPDATEDATE, $wsts);
    ?>:</td><td><?= $Array['LastUpdated'] ?></td></tr>
                            </table>
                        </td></tr><tr><td bgcolor=white>
                            <table border=0 cellpadding=0 cellspacing=0 width=100%>
                                <tr><td width=50%><?= CONTROL_CONTENT_SUBCLASS_USECLASS
    ?>:</td><td><a href=<?= $ADMIN_PATH ?>class/index.php?phase=4&ClassID=<?= $Array["Class_ID"] ?>><?= $Class_Name ?></a></td></tr>
                            </table>
                        </td></tr><tr><td bgcolor=white>
                            <table border=0 cellpadding=0 cellspacing=0 width=100%>
                                <? if ($Array["Subdivision_ID"] && !$System_Table_ID) {
                                    ?>
                                    <tr><td width=50%><?= CONTROL_CONTENT_SUBCLASS_TOTALOBJECTS ?>:</td><td><?= $MessageCount ?> (<a target=_blank href=<?= "http://" . $EDIT_DOMAIN . $SUB_FOLDER . $HTTP_ROOT_PATH . "add.php?catalogue=" . $Array["Catalogue_ID"] . "&sub=" . $Array["Subdivision_ID"] . "&cc=" . $SubClassID . (strlen(session_id()) > 0 ? "&" . session_name() . "=" . session_id() . "" : "") ?>><?= CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_ADD ?></a>)</td></tr>
                                <? } ?>
                                <tr><td width=50%><?
                            if ($Array["Subdivision_ID"]) {
                                $wsts = CONTROL_CONTENT_SUBCLASS_ONSECTION;
                            } else {
                                $wsts = CONTROL_CONTENT_SUBCLASS_ONSITE;
                            }
                            printf(CONTROL_CONTENT_SUBCLASS_CLASSSTATUS, $wsts);
                                ?>:</td><td><?= $Array["Checked"] ? CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_ON : CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_OFF ?></td></tr>
                            </table>
                        </td></tr><tr><td bgcolor=white>
                            <table border=0 cellpadding=0 cellspacing=0 width=100%>
                                <tr><td width=50%><?= CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_READACCESS ?>:</td><td><?= $UserGroupName[$SubEnv["Read_Access_ID"]] ?> <?= CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_USERS ?></td></tr>
                                <tr><td><?= CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_ADDACCESS ?>:</td><td><?= $UserGroupName[$SubEnv["Write_Access_ID"]] ?> <?= CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_USERS ?></td></tr>
                                <tr><td><?= CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_EDITACCESS ?>:</td><td><?= $UserGroupName[$SubEnv["Edit_Access_ID"]] ?> <?= CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_USERS ?></td></tr>
                                <tr><td><?= CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_SUBSCRIBEACCESS ?>:</td><td><?= $UserGroupName[$SubEnv["Subscribe_Access_ID"]] ?> <?= CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_USERS ?></td></tr>
                                <tr><td><?= CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_PUBLISHACCESS ?>:</td><td><?= $ModerationType ?></td></tr>
                            </table>
                        </td></tr></table></td></tr></table>
    <table border=0 cellpadding=6 cellspacing=0 width=100%><tr><td nowrap>
                <? if ($Array["Subdivision_ID"]) {
                    ?>
                    <a href=http://<?= $HiddenHost . $SUB_FOLDER . $HiddenURL . $Array["EnglishName"] ?>.html target=_blank><?= CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_VIEW ?></a> |
                    <a target=_blank href=http://<?= $EDIT_DOMAIN . $SUB_FOLDER . $HTTP_ROOT_PATH ?>?catalogue=<?= $Array["Catalogue_ID"] ?>&sub=<?= $Array["Subdivision_ID"] ?>&cc=<?= $SubClassID . (strlen(session_id()) > 0 ? "&" . session_name() . "=" . session_id() . "" : "") ?>><?= CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_EDIT ?></a> |
                <? } ?>
                <a href="<? print $action2; ?>?phase=<? print $phase2; ?>&SubClassID=<? print $SubClassID; ?>&SubdivisionID=<?= $Array["Subdivision_ID"] ?>&CatalogueID=<?= $Array["Catalogue_ID"] ?>"><?
            if ($Array["Subdivision_ID"]) {
                $wsts = CONTROL_CONTENT_SUBCLASS_ONSECTION;
            } else {
                $wsts = CONTROL_CONTENT_SUBCLASS_ONSITE;
            }
            printf(CONTROL_CONTENT_SUBCLASS_CHANGEPREFS, $wsts);
                ?></a><br><br>
            </td>
            <td align=right><a href=<?= "SubClass.php?" . $nc_core->token->get_url() . "&phase=5&Delete" . $SubClassID . "=" . $SubClassID . ($Array["Subdivision_ID"] ? "&SubdivisionID=" . $Array["Subdivision_ID"] : "&CatalogueID=" . $Array["Catalogue_ID"]) ?>><font color=cc3300><?
                if ($Array["Subdivision_ID"]) {
                    $wsts = CONTROL_CONTENT_SUBCLASS_ONSECTION;
                } else {
                    $wsts = CONTROL_CONTENT_SUBCLASS_ONSITE;
                }
                printf(CONTROL_CONTENT_SUBCLASS_DELETECLASS, $wsts);
                ?></font></a></td>
        </tr></table>
    <?
}

function UpdateSubClassPriority() {
    global $nc_core, $db;

    reset($_POST);
    while (list($key, $val) = each($_POST)) {
        // this cc must be deleted
        if (strcmp(substr($key, 0, strlen("Delete")), "Delete") == 0)
            continue;

        if (substr($key, 0, 8) == "Priority") {
            $subclass_id = substr($key, 8, strlen($key) - 8) + 0;
            $val += 0;

            $db->query("UPDATE `Sub_Class` SET `Priority` = '" . $val . "', `LastUpdated` = `LastUpdated` WHERE `Sub_Class_ID` = '" . $subclass_id . "'");

            $data = $nc_core->sub_class->get_by_id($subclass_id);
            // execute core action
            $nc_core->event->execute("updateSubClass", $data['Catalogue_ID'], $data['Subdivision_ID'], $subclass_id);
        }
    }
}
?>

        <?php

function nc_sub_class_get_main_info($Array, $classInfo, $wsts_msg, $field) {
    $nc_core = nc_Core::get_object();

    return "
        <div id='main_info'>
            <input type='hidden' name='SrcMirror' value='{$Array['SrcMirror']}' />
                <div>
                    <div>
                        $classInfo
                    </div>
                </div><br />

                <div>
                    <div>
                        $wsts_msg:
                    </div>

                    <div>
                        ". nc_admin_input_simple('SubClassName', $Array["Sub_Class_Name"], 50, '', "maxlength='255'") . "
                    </div>
                </div><br />

                <div>
                    <div>
                        " . CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_KEYWORD.":
                    </div>

                    <div>
                        " . nc_admin_input_simple('EnglishName', $Array["EnglishName"], 50, '', "maxlength='255'") ."
                    </div>
                </div><br />

                <div>
                    <div>
                        " . CONTROL_CONTENT_SUBCLASS_DEFAULTACTION . ":
                    </div>

                    <div>
                        <select name='DefaultAction'>
                            <option " . ($Array["DefaultAction"] == "index" ? "selected " : "") . " value='index'>" . CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_VIEW . " </option>
                            <option " . ($Array["DefaultAction"] == "add" ? "selected " : "") . " value='add'>" . CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_ADDING . " </option>
                            <option " . ($Array["DefaultAction"] == "search" ? "selected " : "") ." value='search'>" . CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_SEARCHING . " </option>
                            " . ($nc_core->modules->get_by_keyword('subscriber', 0) ? "<option " . ($Array["DefaultAction"] == "subscribe" ? "selected " : "") . " value='subscribe'>". CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWMENU_SUBSCRIBING ." </option>" : "") . "
                        </select>
                    </div>
                </div>
                ".(false && $Array["SrcMirror"] ? "<br />
                <div>
                    <div>
                        " . CONTROL_CONTENT_SUBCLASS_SRCMIRROR . ":
                    </div>

                    <div>
                        <span id='cs_SrcMirror_caption' style='font-weight:bold;'>
                            " . listQuery($field->get_object_query($Array["SrcMirror"]), $field->get_full_admin_template()) . "
                        </span>
                            <input id='cs_SrcMirror_value' name='SrcMirror' type='hidden' value='{$Array['SrcMirror']}' />&nbsp;&nbsp;
                            <a href='#' onclick=\"window.open('/netcat/admin/related/select_subclass.php?cs_type=rel_cc&amp;cs_field_name=SrcMirror', 'nc_popup_SrcMirror', 'width=800,height=500,menubar=no,resizable=no,scrollbars=yes,toolbar=no,resizable=yes'); return false;\">
                                " . CONTROL_CONTENT_SUBCLASS_SRCMIRROR_EDIT . "
                            </a>&nbsp;&nbsp;

                            <a href='#' onclick=\"document.getElementById('cs_SrcMirror_value').value='';document.getElementById('cs_SrcMirror_caption').innerHTML = '" . CONTROL_CONTENT_SUBCLASS_SRCMIRROR_NONE . "';return false;\">
                                " . CONTROL_CONTENT_SUBCLASS_SRCMIRROR_DELETE . "
                            </a>
                    </div>
                </div>" : "")."
            </div>";
}

function nc_sub_class_get_objlist($Array) {
    $checked_html = " checked='checked'";

    return "
        <div class='nc_table_objlist' id='objlist_table'>
                <div class='row_1'>
                    <div class='col_1'>

                    </div>

                    <div class='col_2'>
                        " . CONTROL_CONTENT_SUBDIVISION_FUNCS_ACCESS_INHERIT . "
                    </div>

                    <div class='col_3'>
                        " . CONTROL_CONTENT_SUBDIVISION_FUNCS_OBJ_YES . "
                    </div>

                    <div class='col_4'>
                        " . CONTROL_CONTENT_SUBDIVISION_FUNCS_OBJ_NO . "
                    </div>
                </div>

                <div>
                    <div class='col_1'>
                        " . CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_HTML . "
                    </div>

                    <div class='col_2".($Array['AllowTags'] == -1 ? ' checked' : '')."'>
                        <input type='radio' name='AllowTags' value='-1'".($Array['AllowTags'] == -1 ? $checked_html : '')." />
                    </div>

                    <div class='col_3".($Array['AllowTags'] == 1 ? ' checked' : '')."'>
                        <input type='radio' name='AllowTags' value='1'".($Array['AllowTags'] == 1 ? $checked_html : '')." />
                    </div>

                    <div class='col_4".($Array['AllowTags'] == 0 ? ' checked' : '')."'>
                        <input type='radio' name='AllowTags' value='0'".($Array['AllowTags'] == 0 ? $checked_html : '')." />
                    </div>
                </div>

                <div>
                    <div class='col_1'>
                        " . CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ_BR . "
                    </div>

                    <div class='col_2".($Array['NL2BR'] == -1 ? ' checked' : '')."'>
                        <input type='radio' name='NL2BR' value='-1'".($Array['NL2BR'] == -1 ? $checked_html : '')." />
                    </div>

                    <div class='col_3".($Array['NL2BR'] == 1 ? ' checked' : '')."'>
                        <input type='radio' name='NL2BR' value='1'".($Array['NL2BR'] == 1 ? $checked_html : '')." />
                    </div>

                    <div class='col_4".($Array['NL2BR'] == 0 ? ' checked' : '')."'>
                        <input type='radio' name='NL2BR' value='0'".($Array['NL2BR'] == 0 ? $checked_html : '')." />
                    </div>
                </div>

                <div>
                    <div class='col_1'>
                        " . CONTROL_CLASS_USE_CAPTCHA . "
                    </div>

                    <div class='col_2".($Array['UseCaptcha'] == -1 ? ' checked' : '')."'>
                        <input type='radio' name='UseCaptcha' value='-1'".($Array['UseCaptcha'] == -1 ? $checked_html : '')." />
                    </div>

                    <div class='col_3".($Array['UseCaptcha'] == 1 ? ' checked' : '')."'>
                        <input type='radio' name='UseCaptcha' value='1'".($Array['UseCaptcha'] == 1 ? $checked_html : '')." />
                    </div>

                    <div class='col_4".($Array['UseCaptcha'] == 0 ? ' checked' : '')."'>
                        <input type='radio' name='UseCaptcha' value='0'".($Array['UseCaptcha'] == 0 ? $checked_html : '')." />
                    </div>
                </div>
            </div><br />

            <div>
                <div>
                    <div>
                        " . CONTROL_CLASS_CLASS_OBJECTSLIST_SHOW . "
                        " . nc_admin_input_simple('RecordsPerPage', $Array['RecordsPerPage'], 3, '', "maxlength='32'") . "
                        " . CONTROL_CLASS_CLASS_OBJECTSLIST_SHOWOBJ . "
                    </div>
                </div><br />

                <div>
                    <div>
                        " . CONTROL_CLASS_CLASS_OBJECTSLIST_SORT . ":
                    </div>

                    <div>
                        " . nc_admin_input_simple('SortBy', $Array['SortBy'], 50, '', "maxlength='255'") . "
                    </div>
                </div>
            </div>";
}

function nc_sub_class_get_CustomSettings($a2f) {
    $vs_template = array(
            'header' => "<table cellpadding='0' cellspacing='1' class='ncf_table'>
                            ",

            'object' => "   <tr>
                                <td class='ncf_td_caption'>%CAPTION: <br /> %VALUE %DEFAULT <br /><br /></td>
                                <td class='ncf_td_value'></td>
                                <td class='ncf_td_default'></td>
                            </tr>",

            'divider' => "  <tr>
                                <td colspan='3' class='ncf_td_divider'>%CAPTION</td>
                            </tr>",
            'footer' => "</table>");

    return "
        <div id='loadClassCustomSettings'>
            <div>
                <div>
                    " . $a2f->render($vs_template['header'], $vs_template['object'], $vs_template['footer'], $vs_template['divider']) . "
                </div>
            </div>
        </div>";
}

function nc_sub_class_get_style_prefix() {
    return "
        <style>
            div.nc_table_objlist > div {
                border-bottom: 1px #cccccc solid;
                display: table;
            }

            div.nc_table_objlist > div > div {
                display: inline-block;
                padding-top: 9px;
                padding-bottom: 11px;
            }

            div.nc_table_objlist div.col_1 {
                width: 261px;
            }

            div.nc_table_objlist div.col_2 {
                width: 134px;
                text-align: center;
            }

            div.nc_table_objlist div.col_3 {
                width: 75px;
                text-align: center;
            }

            div.nc_table_objlist div.col_4 {
                text-align: center;
                width: 81px;
            }


            div.nc_table_objlist > div.row_1 {
                padding-top: 3px;
                padding-bottom: 2px;
            }

            div.nc_table_objlist div.checked {
                background-color: #eeeeee;
            }
        </style>";
}

function nc_sub_class_get_prefix($SubClassID, $Array, $display = false) {
    return "<div id='nc_sub_class_{$SubClassID}_div'".($display ? '' : " style='display: none;'")." class='nc_admin_settings_info'>
                " . ($Array['Created'] || $Array['LastUpdated'] ? "
                <div class='nc_admin_settings_info_actions'>
                    <div>
                        " . ($Array['Created'] ? "<span>" . CLASS_TAB_CUSTOM_ADD . ":</span>" . $Array['Created']: '') . "
                    </div>
                    " . ($Array['LastUpdated'] ? "
                    <div>
                        <span>" . CLASS_TAB_CUSTOM_EDIT . ":</span> {$Array['LastUpdated']}
                    </div>" : '') . "
                <div>" : '')."
                <div class='nc_admin_settings_info_checked'>
                    " . nc_admin_checkbox_simple('Checked', 1, CONTROL_CONTENT_SUBDIVISION_FUNCS_MAINDATA_TURNON, $Array['Checked'], 'turnon') . "
                </div>";
}

function nc_sub_class_get_classInfo($perm, $Array, $classTemplatesArr, $edit_class_select) {
    $nc_core = nc_Core::get_object();
    $classInfo = '';

    if ($perm->isSupervisor()) {
        $class_href = $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . "admin/#";
        if (!$Array['SrcMirror']) {
            $fspref = (nc_get_file_mode('Class', $Array['Class_ID']) ? '_fs' : '');
            $class_href .= !$Array['System_Table_ID'] ? "dataclass".$fspref.".edit(" . $Array['Class_ID'] . ")" : "systemclass".$fspref.".edit(" . $Array['System_Table_ID'] . ")";
        } else {
            $class_href .= "object.list({$Array['SrcMirror']})";
        }
        $classInfo = "
                <div>
                    " . (!$Array['System_Table_ID'] ? ($Array['SrcMirror'] ? CONTROL_CONTENT_SUBCLASS_MIRROR : CONTROL_CLASS_CLASS) : CONTROL_CLASS_SYSTEM_TABLE) . ":
                    <b>
                        <a href='$class_href' target='_blank'>
                            " . $Array["Class_Name"] . "
                        </a>
                    </b>
                </div><br />";
    }

    if (!empty($classTemplatesArr)) {
        $classInfo .= "
                <div>
                    <div>
                        " . CONTROL_CLASS_CLASS_TEMPLATE . ":
                    </div>

                    <div>
                        <select name='Class_Template_ID' id='Class_Template_ID' onchange=\"if (this.options[this.selectedIndex].value) {loadClassCustomSettings(this.options[this.selectedIndex].value); document.getElementById('classtemplateEditLink').disabled = (this.options[this.selectedIndex].value==" . $Array['Class_ID'] . " ? true : false)}\">
                            <option value='{$Array['Class_ID']}'>" . CONTROL_CLASS_CLASS_DONT_USE_TEMPLATE . "</option>";

        foreach ($classTemplatesArr as $classTemplate) {
            $classInfo .= "<option value='{$classTemplate['Class_ID']}'" . ($Array['Class_Template_ID'] == $classTemplate['Class_ID'] ? " selected" : "") . ">{$classTemplate['Class_Name']}</option>";
        }

        $classInfo .= "</select>
                        <button type='button' onclick=\"window.open('{$nc_core->SUB_FOLDER}{$nc_core->HTTP_ROOT_PATH}admin/#classtemplate".(nc_get_file_mode('Class', $Array['Class_ID']) ? '_fs' : '').".edit(' + document.getElementById('Class_Template_ID').value + ')', 1)\" id='classtemplateEditLink'" . (!$Array['Class_Template_ID'] ? " disabled" : "") . ">" . CONTROL_CLASS_CLASS_TEMPLATE_BUTTON_EDIT . "</button></a>
                    </div><br />

                    ".($edit_class_select ? "<div>$edit_class_select</div>" : '')."
                </div>";
    }

    return $classInfo;
}

function nc_sub_class_get_wsts_msg($wsts) {
    ob_start();
    printf(CONTROL_CONTENT_SUBCLASS_CLASSNAME, $wsts);
    return ob_get_clean();
}
