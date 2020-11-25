<?php

/* $Id: admin.inc.php 7302 2012-06-25 21:12:35Z alive $ */

# вывод диалога создания блога

function nc_admin_blog_begin($phase, $catalogue="") {
    global $nc_core, $db, $UI_CONFIG, $MODULE_VARS;

    echo "
	<fieldset>
	<legend>
		".NETCAT_MODULE_BLOG_ADMIN_LEGEND_INFO."
	</legend>

	<form method='post' id='mainForm' action='admin.php' style='padding:0; margin:0;'>";

    if ($phase == 1) {
        echo "
		<div style='padding:10px 0 5px'>
			".NETCAT_MODULE_BLOG_ADMIN_CREATEBLOG_INFO."<br>
			<select name='BlogCatalogue' style='width:428px'>";
        if ($catalogues = $db->get_results("SELECT Catalogue_ID, Catalogue_Name FROM Catalogue", ARRAY_A))
                foreach ($catalogues AS $value) {
                echo "<option value='".$value['Catalogue_ID']."'>".$value['Catalogue_Name']."</option>";
            }
        echo "
		</select>";
    } elseif ($phase == 2) {
        echo "
		<input type='hidden' name='BlogCatalogue' value='".$catalogue."'>
		<div style='padding:10px 0 5px'>
			".NETCAT_MODULE_BLOG_ADMIN_CREATEBLOG_INFO."<br>
			<select name='BlogSubdivision' style='width:428px'>
				<option value='0'>-- В корневом разделе --</option>";
        $subdivisions = $db->get_results("SELECT Subdivision_ID as value, CONCAT(Subdivision_ID, '. ', Subdivision_Name) as description, Parent_Sub_ID as parent
			                                      FROM Subdivision
												  WHERE Catalogue_ID=".intval($catalogue)."
												  ORDER BY Subdivision_ID", ARRAY_A);
        echo nc_select_options($subdivisions);
        echo "
			</select>";

        echo "
		<div style='padding:10px 0 5px'>
			".NETCAT_MODULE_BLOG_ADMIN_BLOGTYPE_INFO."<br>
			<select name='BlogType' style='width:428px'>";
        $blogTypes = array("personal" => NETCAT_MODULE_BLOG_TYPE_1, "collective" => NETCAT_MODULE_BLOG_TYPE_2);
        foreach ($blogTypes AS $key => $value) {
            echo "<option value='".$key."'>".$value."</option>";
        }
        echo "
			</select>
		</div>
		<div style='padding:10px 0 5px'>
			".NETCAT_MODULE_BLOG_ADMIN_SUBNAME_INFO."<br>
			<input type='text' name='BlogName' value='' style='width:428px'>
		</div>
		<div style='padding:10px 0 5px'>
			".NETCAT_MODULE_BLOG_ADMIN_KEYWORD_INFO."<br>
			<input type='text' name='BlogKeyword' value='' style='width:428px'>
		</div>
		<div style='padding:10px 0 5px'>
			<input type='checkbox' name='BlogComments' style='margin:3px 0 0; padding:0;'> &nbsp; ".NETCAT_MODULE_BLOG_ADMIN_COMMENTS_INFO."
		</div>
		<div style='padding:10px 0 5px'>
			<input type='checkbox' name='BlogChecked' checked style='margin:3px 0 0; padding:0;'> &nbsp; ".NETCAT_MODULE_BLOG_ADMIN_SUBON_INFO."
		</div>
		<div style='padding:10px 0 15px'>
			".NETCAT_MODULE_BLOG_ADMIN_MAIN_INFO."<br>
		</div>";
    }

    # кнопки в панеле администрирования
    $UI_CONFIG->actionButtons[] = array("id" => "submit",
            "caption" => NETCAT_MODULE_BLOG_ADMIN_SAVE_BUTTON,
            "action" => "mainView.submitIframeForm('mainForm')");
    echo
    "<input type='hidden' name='phase' value='".($phase + 1)."'>
	</form>";

    echo "
	</fieldset>";
}

# функция создания нового блога

function nc_admin_blog_create($blogArray) {
    global $nc_core, $db, $MODULE_VARS;

    $error = false;
    $catalogue = $blogArray['Catalogue'];
    $subdivision = $blogArray['Subdivision'];
    $blogType = $blogArray['BlogType'];
    $EnglishName = $db->escape($blogArray['Keyword']);
    $blogName = $db->escape($blogArray['Name']);
    $Checked = $blogArray['Checked'];
    $Comments = $blogArray['Comments'];
    $date = date('Y-m-d H:i:s');
    $Priority = $db->get_var("SELECT max(Priority) FROM Subdivision WHERE Parent_Sub_ID=0") + 1;
    $allSubPermissions = 1; # все посетители
    $userSubPermissions = 2; # зарегистрированные пользователи
    $hiddenSubPermissions = 3; # уполномоченные пользователи

    if ($subdivision)
            $Hidden_URL = $db->get_var("SELECT Hidden_URL FROM Subdivision WHERE Subdivision_ID=".$subdivision."");
    if ($Hidden_URL) $Hidden_URL .= $EnglishName."/"; else
            $Hidden_URL .= "/".$EnglishName."/";

    # создаём раздел
    $db->query("INSERT INTO `Subdivision`
				(`Catalogue_ID`, `Parent_Sub_ID`, `Subdivision_Name`, `Template_ID`, `ExternalURL`, `EnglishName`, `LastUpdated`, `Created`, `Hidden_URL`, `Read_Access_ID`, `Write_Access_ID`, `Priority`, `Checked`, `Edit_Access_ID`, `Subscribe_Access_ID`".($nc_core->modules->get_by_keyword('cache') ? ", `Cache_Access_ID`" : "").")
				VALUES
				(".$catalogue.", ".$subdivision.", '".$blogName."', 0, '', '".$EnglishName."', '".$date."', '".$date."', '".$Hidden_URL."', 0, 0, ".$Priority.", ".$Checked.", 0, 0".($nc_core->modules->get_by_keyword('cache') ? ", 2" : "").")");
    # получаем ID
    $blog_Sub_ID = $db->insert_id;
    if (!$blog_Sub_ID) $error = true;
    else $errorBackUp[] = "DELETE FROM Subdivision WHERE Subdivision_ID=".$blog_Sub_ID."";

    # Запись в таблицу Blog_Parent
    if (!$error) {
        $db->query("INSERT INTO `Blog_Parent`
			(`Subdivision_ID`, `Type`)
			VALUES
			('".$blog_Sub_ID."', '".$blogType."')");

        $bp = $db->insert_id;
        if (!$bp) $error = true;
        else $errorBackUp[] = "DELETE FROM `Blog_Parent` WHERE `ID` = '".$bp."'";
    }

    if (!$error) {
        # кладем туда шаблон
        $Sub_Class_Name = $db->get_var("SELECT Class_Name FROM Class WHERE Class_ID=".$MODULE_VARS['blog']['BLOG_CLASS_ID'].""); ###
        $db->query("INSERT INTO Sub_Class
					(Subdivision_ID, Class_ID, Sub_Class_Name, Priority, EnglishName, Checked, Catalogue_ID, AllowTags, Created, LastUpdated, DefaultAction, NL2BR, UseCaptcha, CustomSettings)
					VALUES
					(".$blog_Sub_ID.", ".$MODULE_VARS['blog']['BLOG_CLASS_ID'].", '".$Sub_Class_Name."', 1, '".$EnglishName."', 1, ".$catalogue.", -1, '".$date."', '".$date."', 'index', -1, -1, '')");
        # получаем ID
        $blog_cc_ID = $db->insert_id;
        if (!$blog_cc_ID) $error = true;
        else $errorBackUp[] = "DELETE FROM Sub_Class WHERE Subdivision_ID=".$blog_cc_ID."";
    }

    # Comments
    if ($Comments && !$error) {
        # создаём раздел
        $db->query("INSERT INTO Subdivision
					(Catalogue_ID, Parent_Sub_ID, Subdivision_Name, Template_ID, ExternalURL, EnglishName, LastUpdated, Created, Hidden_URL, Read_Access_ID, Write_Access_ID, Priority, Checked, Edit_Access_ID, Subscribe_Access_ID)
					VALUES
					(".$catalogue.", ".$blog_Sub_ID.", 'Комментарии', 0, '', 'comments', '".$date."', '".$date."', '".$Hidden_URL."comments/', ".$allSubPermissions.", ".$allSubPermissions.", 1, 0, ".$allSubPermissions.", ".$hiddenSubPermissions.")");
        # получаем ID
        $blog_comm_Sub_ID = $db->insert_id;
        if (!$blog_comm_Sub_ID) $error = true;
        else
                $errorBackUp[] = "DELETE FROM Subdivision WHERE Subdivision_ID=".$blog_comm_Sub_ID."";
    }
    # Comments sub_class_id
    if (!$error && $blog_comm_Sub_ID) {
        # кладем туда шаблон
        $db->query("INSERT INTO Sub_Class
					(Subdivision_ID, Class_ID, Sub_Class_Name, Priority, EnglishName, Checked, Catalogue_ID, AllowTags, Created, LastUpdated, DefaultAction, NL2BR, UseCaptcha, CustomSettings)
					VALUES
					(".$blog_comm_Sub_ID.", ".$MODULE_VARS['blog']['BLOG_COMMENTS_CLASS_ID'].", 'Комментарии', 1, 'comments', 1, ".$catalogue.", -1, '".$date."', '".$date."', 'index', -1, -1, '')");
        # получаем ID
        $blog_comm_cc_ID = $db->insert_id;
        if (!$blog_comm_cc_ID) $error = true;
        else
                $errorBackUp[] = "DELETE FROM Sub_Class WHERE Subdivision_ID=".$blog_comm_cc_ID."";
    }

    # делаем запись в визуальные настройки о типе блога, данных комментариев (SUB, CC)...
    if (!$error) {
        unset($customSettings);
        if ($blogType && preg_match("/^[a-z]+$/", $blogType)) {
            $customSettings[] = "'BlogType' => '".$blogType."'";
        }
        if ($Comments) {
            $customSettings[] = "'BlogCommentsSUB' => '".$blog_comm_Sub_ID."'";
            $customSettings[] = "'BlogCommentsCC' => '".$blog_comm_cc_ID."'";
        }
        $CustomSettingsStr = join(",", $customSettings);
        $CustomSettingsStr = "\$CustomSettings = array(".$db->escape($CustomSettingsStr).")";
        $db->query("UPDATE Sub_Class SET CustomSettings='".$CustomSettingsStr."' WHERE Sub_Class_ID=".$blog_cc_ID."");
    }

    # если возникли проблемы - делаем это
    if ($error && $errorBackUp) nc_blog_backup($errorBackUp);

    return!$error ? true : false;
}