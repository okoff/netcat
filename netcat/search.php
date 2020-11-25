<?php
/* $Id: search.php 8008 2012-08-23 12:32:10Z vadim $ */

$action = "search";

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -2)).( strstr(__FILE__, "/") ? "/" : "\\" );
@include_once ($NETCAT_FOLDER."vars.inc.php");

require ($INCLUDE_FOLDER.'index.php');

if (!isset($use_multi_sub_class)) {
    // subdivision multisubclass option
    $use_multi_sub_class = $nc_core->subdivision->get_current("UseMultiSubClass");
}

if ($classPreview == ($current_cc["Class_Template_ID"] ? $current_cc["Class_Template_ID"] : $current_cc["Class_ID"])) {
    $magic_gpc = get_magic_quotes_gpc();
    $searchTemplate = $magic_gpc ? stripslashes($_SESSION["PreviewClass"][$classPreview]["SearchTemplate"]) : $_SESSION["PreviewClass"][$classPreview]["SearchTemplate"];
}

require ($INCLUDE_FOLDER.'s_files.inc.php');
require_once ($ADMIN_FOLDER.'admin.inc.php');

ob_start();

$nc_core->page->set_current_metatags($current_sub);

$cc_settings = nc_get_visual_settings($cc);

if ($searchTemplate) {
    if ($current_cc['File_Mode']) {
        $file_class = new nc_class_view($CLASS_TEMPLATE_FOLDER, $db);
        $file_class->load($current_cc['Class_ID'], $current_cc['File_Path'], $current_cc['File_Hash']);
        $nc_parent_field_path = $file_class->get_parent_fiend_path('SearchTemplate');
        $nc_field_path = $file_class->get_field_path('SearchTemplate');
        // check and include component part
		try {
			if ( nc_check_php_file($nc_field_path) ) {
				include $nc_field_path;
			}
		}
		catch (Exception $e) {
			if ( is_object($perm) && $perm->isSubClassAdmin($cc) ) {
				// error message
				echo sprintf(CONTROL_CLASS_CLASSFORM_CHECK_ERROR, CONTROL_CLASS_CLASS_FORMS_SEARCH);
			}
		}
        $nc_parent_field_path = null;
        $nc_field_path = null;
    } else {
        eval("echo \"".$searchTemplate."\";");
    }
} else {
    require ($ROOT_FOLDER.'message_fields.php');

    if ($srchFrm = showSearchForm($fldName, $fldType, $fldDoSearch, $fldFmt)) {
        $form_action = $SUB_FOLDER.$current_sub['Hidden_URL'].$current_cc['EnglishName'].'.html';
?>
        <form action='<?= $form_action
?>' method='get'>
            <input type='hidden' name='action' value='index'>
                    <?= $srchFrm
?>
        <input value='<?= NETCAT_SEARCH_FIND_IT ?>' type='submit'>
        </form>
<?php
    } else {
        nc_print_status(NETCAT_SEARCH_ERROR, 'error');
    }
}

$cc_search = $cc;

if ($cc_array && $use_multi_sub_class && !$inside_admin) {
    foreach ($cc_array as $cc) {
        if (( $cc && $cc_search != $cc ) || $user_table_mode) {
            // поскольку компонентов несколько, то current_cc нужно переопределить
            $current_cc = $nc_core->sub_class->set_current_by_id($cc);
            echo s_list_class($sub, $cc, $nc_core->url->get_parsed_url('query').($date ? "&date=".$date : "")."&isMainContent=1&isSubClassArray=1");
        }
    }
    // current_cc нужно вернуть в первоначальное состояние, чтобы использовать в футере макета
    $current_cc = $nc_core->sub_class->set_current_by_id($cc_search);
}

$nc_result_msg = ob_get_clean();

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
    eval("echo \"".$template_header."\";");
    echo $nc_result_msg;
    eval("echo \"".$template_footer."\";");
}
