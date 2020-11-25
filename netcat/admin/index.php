<?php
/* $Id: index.php 8445 2012-11-21 13:49:24Z vadim $ */

require_once ("function.inc.php");
$system_env = $nc_core->get_settings();
$an = new nc_AdminNotice();
$adminNotice = $an->check();

?><!DOCTYPE html>
<html style="overflow-y: hidden;">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=<?= $nc_core->NC_CHARSET ?>" />
        <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
        <title><?= ($title ? $title : "NetCat ".BEGINHTML_VERSION." ".$VERSION_ID." ".$SYSTEM_NAME) ?></title>
        <link rel="stylesheet" rev="stylesheet" type="text/css" href="<?= $ADMIN_TEMPLATE ?>css/font.css?<?= $LAST_LOCAL_PATCH ?>" />
        <script type='text/javascript'>
            var FIRST_TREE_MODE = '<?= $treeMode ?>';
        </script>
        <?= nc_js(); ?>
        <script type="text/javascript" src="<?= $ADMIN_PATH ?>js/main.js?<?= $LAST_LOCAL_PATCH ?>"></script>
        <script type='text/javascript' src='<?= $ADMIN_PATH ?>js/container.js?<?= $LAST_LOCAL_PATCH ?>'></script>
        <script type='text/javascript' src='<?= $ADMIN_PATH ?>js/dispatcher.js?<?= $LAST_LOCAL_PATCH ?>'></script>
        <script type='text/javascript' src='<?= $ADMIN_PATH ?>js/url_routes.js?<?= $LAST_LOCAL_PATCH ?>'></script>

        <?
		// MODULE URL DISPATCHERS
        $modules = $nc_core->modules->get_data();
        //ADMIN_LANGUAGE
        if ( !empty($modules) ) {
			foreach ($modules as $module) {
				if (file_exists($MODULE_FOLDER.$module['Keyword']."/".MAIN_LANG.".lang.php")) {
					require_once ($MODULE_FOLDER.$module['Keyword']."/".MAIN_LANG.".lang.php");
				} else {
					require_once ($MODULE_FOLDER.$module['Keyword']."/en.lang.php");
				}
				if (file_exists($MODULE_FOLDER.$module['Keyword']."/url_routes.js")) {
					echo "<script type='text/javascript' src='".$SUB_FOLDER.$HTTP_ROOT_PATH."modules/".$module['Keyword']."/url_routes.js?".$LAST_LOCAL_PATCH."'></script>\n";
				}
			}
		}

        include($ADMIN_FOLDER."modules/module_list.inc.php");
        ?>
        <script type='text/javascript' src='<?= $ADMIN_PATH ?>js/main_view.js?<?= $LAST_LOCAL_PATCH ?>'></script>
        <script type='text/javascript' src='<?= $ADMIN_PATH ?>js/drag.js?<?= $LAST_LOCAL_PATCH ?>'></script>
        <script type='text/javascript'>
            var REMIND_SAVE = '<?= $REMIND_SAVE ?>';
            var TEXT_SAVE = '<?= NETCAT_REMIND_SAVE_TEXT ?>';
            var TEXT_REFRESH = '<?= NETCAT_TAB_REFRESH ?>';
        </script>
    </head>
    <body style="overflow-y: hidden;">

        <div class="header">
            <a href="#" class="logo"><img src="<?= $ADMIN_TEMPLATE ?>img/logo.png" alt="NetCat <?= BEGINHTML_VERSION ?> <?= $VERSION_ID ?> <?= $SYSTEM_NAME ?>" /></a>

            <ul class="menu_top">
                <?php
                $all_site_admin = $perm->isAccess(NC_PERM_ITEM_SITE, 'viewall', 0, 0);

				// получим id всех каталогов, к которому пользователь имеет доступ админа\модер
				// или иммет доступ к его разделам, тоже админ\модер
				// если ф-ция вернет не массив, то значит есть достп ко всем
                $array_id = $perm->GetAllowSite(MASK_ADMIN | MASK_MODERATE, true);
                $sites = $db->get_results("SELECT `Catalogue_ID`, `Catalogue_Name`, `Domain`, `Mirrors`, `Checked`, `ncMobile`, `ncResponsive`
				  FROM `Catalogue`".( is_array($array_id) && !empty($array_id) ? " WHERE `Catalogue_ID` IN (".join(',', $array_id).")" : "" )."
				  ORDER BY `Priority`", ARRAY_A);

				// показывать или нет "Сайт"
                if ($perm->isAccessSiteMap() || $perm->isGuest()) {
                    print "<li><a href='#' onClick='return false;'>".SECTION_INDEX_MENU_SITE."</a>".
                            "<ul>";

                    if ($sites) {
                        foreach ($sites as $site) {
                            // each site
							$image = 'icon_site';
							$image .= $site['ncMobile'] ? '_mobile' : '';
							$image .= $site['ncResponsive'] ? '_adapt' : '';
							$image .= $site['Checked'] ? '' : '_disabled';
                            print "<li class='with_separator'><a href='#site.map(".$site['Catalogue_ID'].")'><div class='icons ".$image."'></div>".$site['Catalogue_Name']."</a></li>";
                        }

                        print "<li class='border'></li>";

                        if ($all_site_admin) {
                            print
                            "<li class='with_separator'>
                                 <a href='#site.list'>
                                     <div class='icons icon_site_list'></div>
                                     " . SECTION_INDEX_SITE_LIST . "
                                 </a>
                              </li>";
                        }
                    }

                    if ($all_site_admin) {
                        //add and wizard
                        print "<li class='with_separator'><a href='#site.add()'><div class='icons icon_site_add'></div><font class='personal_disabled'>".CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_ADDSITE."</font></a></li>";
                                "<li><a href='#site.wizard(1,0)'><div class='icons icon_site_wizard'></div><font class='personal_disabled'>".SECTION_INDEX_WIZARD_SUBMENU_SITE."</font></a></li>";
                    }

                    print "</ul>".
                            "</li>";
                } //Показывать или нет "Сайт"
// Пользователи
                if ($perm->isUserMenuShow()) {
                    print "<li><a href='#' onClick='return false;'>".CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_USERS."</a>
             <ul>";
                    if ( $perm->isAccess(NC_PERM_ITEM_USER, NC_PERM_ACTION_ADD, 0, 0) ) {
                        print"<li>\n
                <a href='#user.add'>\n
                  <div class='icons icon_user_add'></div><font class='personal_disabled'>".CONTROL_USER_REG."</font></a>\n
              </li>\n";
                    }
                    print "<li>\n
               <a href='#user.list'>\n
               <div class='icons icon_user'></div>".SECTION_CONTROL_USER_LIST."</a>\n
             </li>\n";
                    if ( $perm->isAccess(NC_PERM_ITEM_GROUP, 0, 0, 0) ) {
                        print "<li><a href='#usergroup.list'><div class='icons icon_usergroups'></div><font class='personal_disabled'>".SECTION_CONTROL_USER_GROUP."</font></a></li>";
                        print "<li><a href='#user.mail'><div class='icons icon_sendmail'></div><font class='personal_disabled'>".SECTION_INDEX_USER_USER_MAIL."</font></a></li>";
                    }


                    print"        </ul>\n
             </li>";
                }

// инструменты
                if ($perm->isSupervisor() || $perm->isGuest()) {
                    print "<li><a href='#' onClick='return false;'>".SECTION_INDEX_MENU_TOOLS."</a>".
                            "<ul>";
                    print "<li><a href='#widgets'><div class='icons icon_widget'></div><font class='personal_disabled'>".SECTION_SECTIONS_INSTRUMENTS_WIDGETS."</font></a>";
                    print "<li><a href='#cron.settings'><div class='icons icon_cron'></div><font class='personal_disabled'>".SECTION_SECTIONS_INSTRUMENTS_CRON."</font></a>";
                    print "<li><a href='#redirect.settings'><div class='icons icon_redirect'></div><font class='personal_disabled'>".TOOLS_REDIRECT."</font></a>";
                    print "<li class='border'></li>";
                    if ($nc_core->modules->get_by_keyword('stats')) {
                        print "<li><a href='#module.stats'><div class='icons icon_module_stats'></div>".NETCAT_MODULE_STATS."</a></li>";
                    }
                    if ($nc_core->modules->get_by_keyword('banner')) {
                        print "<li><a href='#module.banner'><div class='icons icon_module_banner'></div>".NETCAT_MODULE_BANNER."</a></li>";
                    }
                    if ($nc_core->modules->get_by_keyword('search')) {
                        print "<li><a href='#module.search.brokenlinks'><div class='icons icon_module_brockenlinks'></div>".NETCAT_MODULE_SEARCH_ADMIN_BROKEN_LINKS_MENU_ITEM."</a></li>";
                    }
                    if ($nc_core->modules->get_by_keyword('filemanager')) {
                        print "<li><a href='#module.filemanager'><div class='icons icon_module_filemanager'></div><font class='personal_disabled'>".NETCAT_MODULE_FILEMANAGER."</font></a></li>";
                    }
                    print "<li><a href='#tools.seo(".$HTTP_HOST.")'><div class='icons icon_tool_siteinfo'></div><font class='personal_disabled'>".NETCAT_SITEINFO_LINK."</font></a></li>";
                    print "<li><a href='#tools.copy()'><div class='icons icon_copy'></div>".TOOLS_COPYSUB."</a></li>";
                    print "<li><a href='#trash.list'><div class='icons icon_trash'></div>".SECTION_SECTIONS_INSTRUMENTS_TRASH."</a></li>";
                    print "<li class='border'></li>";
                    print "
          <li><a href='#tools.sql'><div class='icons icon_tool_sql'></div><font class='personal_disabled'>".SECTION_SECTIONS_INSTRUMENTS_SQL."</font></a>
          <li class='border'></li>
          <!--<li class='with_separator'><a href='#tools.html'><font class='personal_disabled'>".SECTION_SECTIONS_INSTRUMENTS_HTML."</font></a>-->
          <li><a href='#tools.backup'><div class='icons icon_tool_backup'></div>".SECTION_SECTIONS_MODDING_ARHIVES."</a></li>
          <li><a href='#tools.patch'><div class='icons icon_patch'></div>".TOOLS_PATCH."</a></li>
        ".( $nc_core->is_trial ? "<li><a href='#tools.activation'>".TOOLS_ACTIVATION."</a></li>" : "")."
          <li><a href='#tools.installmodule'><div class='icons icon_install_module'></div><font class='personal_disabled'>".TOOLS_MODULES_MOD_INSTALL."</font></a>
          <li class='border'></li>
          <li><a href='#tools.totalstat'><div class='icons icon_totalstat'></div>".SECTION_REPORTS_TOTAL."</a></li>";
                    if ($nc_core->modules->get_by_keyword('logging')) {
                        print "<li><a href='#module.logging'><div class='icons icon_module_logging'></div>".NETCAT_MODULE_LOGGING."</a></li>";
                    }
                    print "<li><a href='#tools.systemmessages'><div class='icons icon_systemmessages'></div>".SECTION_REPORTS_SYSMESSAGES."</a></li>
        </ul>
      </li>";
                }


//Development
                if ($perm->isAccessDevelopment() || $perm->isGuest()) {
                    $print_result = "<li class='nc_dev_menu'><a href='#' onClick='return false;'>".SECTION_INDEX_MENU_DEVELOPMENT."</a>".
                            "<ul>";
                    if ($perm->isSupervisor() || $perm->isGuest()) //Access to class
                            $print_result .= "<li class='nc_dev_menu_new'><a href='#dataclass_fs.list'><div class='icons icon_classes'></div>".SECTION_CONTROL_CLASS."</a></li>";
                    if ($perm->isSupervisor() || $perm->isGuest()) //Access to template
                            $print_result .= "<li class='nc_dev_menu_new'><a href='#template_fs.list'><div class='icons icon_templates'></div>".SECTION_CONTROL_TEMPLATE_SHOW."</a></li>";
                    if ($perm->isSupervisor() || $perm->isGuest()) //Access to system table
                            $print_result .= "<li class='nc_dev_menu_new'><a href='#systemclass_fs.list'><div class='icons icon_sysclasses'></div>".SECTION_SECTIONS_OPTIONS_SYSTEM."</a></li>";
                    if ($perm->isSupervisor() || $perm->isGuest()) //Access to widget
                            $print_result .= "<li class='nc_dev_menu_new'><a href='#widgetclass_fs.list'><div class='icons icon_widgetclasses'></div>".SECTION_CONTROL_WIDGETCLASS."</a></li>";
                    if ($perm->isAnyClassificator() || $perm->isGuest()) //Access to classificator
                            $print_result .= "<li class='nc_dev_menu_new'><a href='#classificator.list'><div class='icons icon_classificators'></div>".SECTION_CONTROL_CONTENT_CLASSIFICATOR."</a></li>";
                    if (false && $perm->isSupervisor() || $perm->isGuest()) //Access to classWizard
                            $print_result .= "<li class='nc_dev_menu_new'><a href='#dataclass_fs.wizard(1,0,0)'><div class='icons icon_class_wizard'></div><font class='personal_disabled'>".SECTION_INDEX_WIZARD_SUBMENU_CLASS."</font></a></li>";
                    $print_result .="<li class='border'></li>";
                    // 4.0
                    if ($perm->isSupervisor() || $perm->isGuest()) //Access to class
                            $print_result .= "<li class='nc_dev_menu_old'><a href='#dataclass.list'><div class='icons icon_classes'></div>".SECTION_CONTROL_CLASS." 4.0</a></li>";
                    if ($perm->isSupervisor() || $perm->isGuest()) //Access to template
                            $print_result .= "<li class='nc_dev_menu_old'><a href='#template.list'><div class='icons icon_templates'></div>".SECTION_CONTROL_TEMPLATE_SHOW." 4.0</a></li>";
                    if ($perm->isSupervisor() || $perm->isGuest()) //Access to system table
                            $print_result .= "<li class='nc_dev_menu_old'><a href='#systemclass.list'><div class='icons icon_sysclasses'></div>".SECTION_SECTIONS_OPTIONS_SYSTEM." 4.0</a></li>";
                    if ($perm->isSupervisor() || $perm->isGuest()) //Access to widget
                            $print_result .= "<li class='nc_dev_menu_old'><a href='#widgetclass.list'><div class='icons icon_widgetclasses'></div>".SECTION_CONTROL_WIDGETCLASS." 4.0</a></li>";

                    $print_result .= "</ul>".
                            "</li>";
                    print $print_result;
                }



// настройки
                if ($perm->isSupervisor() || $perm->isGuest()) {
                    print "<li><a href='#' onClick='return false;'>".SECTION_INDEX_MENU_SETTINGS."</a>
               <ul>
                 <li><a href='#system.settings'><div class='icons icon_settings'></div>".SECTION_SECTIONS_OPTIONS."</a></li>
                 <li><a href='#module.list'><div class='icons icon_settings'></div>".SECTION_SECTIONS_OPTIONS_MODULE_LIST."</a></li>
                 <li><a href='".( count($sites) == 1 ? "#site.edit(".$sites[0]['Catalogue_ID'].")" : "#site.list" )."'><div class='icons icon_site'></div>".SECTION_INDEX_SITES_SETTINGS."</a></li>";



                    if (!empty($modules)) {
                        print "<li class='border'></li>";
                        foreach ($modules as $module) {
                            $settings_url = "#module.settings(".$module['Keyword'].")";
                            if ($module['Keyword'] == 'calendar') {
                                $settings_url = '#module.calendar';
                            }
                            $modImg = file_exists($ADMIN_TEMPLATE_FOLDER."img/i_module_".$module['Keyword'].".gif") ? "i_module_".$module['Keyword'].".gif" : "i_modules.gif";
                            if (file_exists($MODULE_FOLDER.$module['Keyword']."/nc_".$module['Keyword']."_admin.class.php")) {
                                require_once $MODULE_FOLDER.$module['Keyword']."/nc_".$module['Keyword']."_admin.class.php";
                                if (class_exists($cn = "nc_".$module['Keyword']."_admin")) {
                                    $admin_obj = new $cn();
                                    if (is_callable(array($admin_obj, 'get_mainsettings_url'))) {
                                        $settings_url = $admin_obj->get_mainsettings_url();
                                    }
                                }
                            }
                            print "<li><a href='".$settings_url."'><div class='icons icon_module_" . $module['Keyword'] . "'></div><font class='personal_disabled'>".constant($module['Module_Name'])."</font></a>";
                            unset($modImg);
                        }
                    }
                    print "</ul>";
                    print "</li>";
                }


// HELP
                print "<li><a href='#' onClick='return false;'>".SECTION_INDEX_MENU_HELP."</a>
               <ul>
                 <!--<li><a href='http://docs.netcat.ru/30/' target='_blank'>".SECTION_INDEX_HELP_SUBMENU_HELP."</a></li>-->
                 <li class='with_separator'><a href='http://www.netcat.ru/developes/docs/' target='_blank'><div class='icons icon_netcatdocs'></div>".SECTION_INDEX_HELP_SUBMENU_DOC."</a></li>
                 <li><a href='http://www.netcat.ru/forclients/support/tickets/' target='_blank'><div class='icons icon_netcatonline'></div>".SECTION_INDEX_HELP_SUBMENU_HELPDESC."</a></li>
                 <li><a href='http://www.netcat.ru/support/forum/' target='_blank'><div class='icons icon_netcatforum'></div>".SECTION_INDEX_HELP_SUBMENU_FORUM."</a></li>
                 <li class='with_separator'><a href='http://www.netcat.ru/support/knowledge/' target='_blank'><div class='icons icon_netcatknowledge'></div>".SECTION_INDEX_HELP_SUBMENU_BASE."</a></li>
                 <li><a href='#help.about'><div class='icons icon_netcatabout'></div>".SECTION_INDEX_HELP_SUBMENU_ABOUT."</a></li>
               </ul>
             </li>";

                if ($ANY_SYSTEM_MESSAGE) {
                    $msg_title = BEGINHTML_ALARMON;
                    $msg_img = 'active';
                } else {
                    $msg_title = BEGINHTML_ALARMOFF;
                    $msg_img = 'inactive';
                }
                ?>
               </ul>
               <ul class="menu_top" style="background: #1A87C2; position: absolute; right: 0;">
                <li style="float: right;">                  
                        <a  style='display:inline-block; vertical-align: top;' href="#" onClick='return false;'><?= $perm->getLogin(); ?></a>
                        <ul style="margin-left: 100%; left: -261px;">
                            <li>
                                <div style="padding-left: 10px;">
                                    <?= NETCAT_ADMIN_AUTH_PERM ?> <span style="color: #AAAAAA;"><?= join(', ', Permission::get_all_permission_names_by_id($AUTH_USER_ID)); ?></span>
                                </div>
                            </li>

                            <li class="border"></li>

                            <li>
                                <div style="padding: 10px;">
                                    <div style='display: inline-block; cursor: pointer;' onClick='nc_password_change();'>
                                        <?= NETCAT_ADMIN_AUTH_CHANGE_PASS ?>
                                    </div>
                                    <div style='display: inline-block; cursor: pointer; float: right;'>
                                        <a href='<?= $MODULE_VARS['auth'] ? $SUB_FOLDER . $HTTP_ROOT_PATH . "modules/auth/?logoff=1&amp;REQUESTED_FROM=" . $REQUEST_URI  : $ADMIN_PATH . "unauth.php"; ?>' style='padding: 0px; background-color: white;'>
                                            <?= NETCAT_ADMIN_AUTH_LOGOUT ?>
                                        </a>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    
                </li>
                <li style="float: right;">
                    <div style='display:inline-block' id='mainMenuMessages'><a href='#tools.systemmessages' title='<?= $msg_title ?>'><div id='mainMenuMessagesIcon' class="icon_sysmsg_<?= $msg_img ?>"></div></a></div>
                </li>
            </ul>

        <?= "
        <script>
            \$nc(document).ready(function() {

                \$nc('div#nc_password_change input.nc_admin_metro_button').click(function() {
                    \$nc('div#nc_password_change_body form').submit();
                });

                \$nc('div#nc_password_change input.nc_admin_metro_button_cancel').click(function() {
                    \$nc.modal.close();
                });
            });
        </script>

        <div id='nc_password_change' style='display: none;'>
            <div id='nc_password_change_header'>
                <div>
                    <h2 style='padding: 0px;'>".NETCAT_ADMIN_AUTH_CHANGE_PASS."</h2>
                </div>
            </div>

            <div id='nc_password_change_body'>
                <form method='post' action='{$ADMIN_PATH}user/index.php'>
                    <div>
                        <div>
                            " . CONTROL_USER_NEWPASSWORD . "
                        </div>

                        <div>
                            <input type='password' name='Password1' maxlength='32' />
                        </div>
                    </div>

                    <div>
                        <div>
                            " . CONTROL_USER_NEWPASSWORDAGAIN . "
                        </div>

                        <div>
                            <input type='password' name='Password2' maxlength='32' />
                        </div>
                    </div>

                    <input type='hidden' name='UserID' value='$AUTH_USER_ID' />
                    <input type='hidden' name='phase' value='7' />
                    " . $nc_core->token->get_input() . "
                </form>
            </div>

            <div id='nc_password_change_footer'>
                <input class='nc_admin_metro_button' type='button' value='" . NETCAT_REMIND_SAVE_SAVE . "'  title='" . NETCAT_REMIND_SAVE_SAVE . "' />
                <input class='nc_admin_metro_button_cancel' style='color: black; margin-right: 16px; background-color: #EEEEEE; border: 1px solid red;' type='button' value='". CONTROL_BUTTON_CANCEL ."' title='". CONTROL_BUTTON_CANCEL ."' />
            </div>
        </div>"; ?>

            <div class="clear"></div>
        </div>

        <div class="middle">
            <div class="middle_left">
                <div class='title' id='tree_mode_name'>
                    <?= NETCAT_TREE_SITEMAP ?>
                </div>
                <script>
                    var tree_modes = {
                        'sitemap' : '<?= NETCAT_TREE_SITEMAP; ?>',
                        'classificator' : '<?= SECTION_CONTROL_CONTENT_CLASSIFICATOR; ?>',
                        'dataclass' : '<?= SECTION_INDEX_DEV_CLASSES . ' 4.0'; ?>',
                        'dataclass_fs' : '<?= SECTION_INDEX_DEV_CLASSES; ?>',
                        'systemclass' : '<?= SECTION_SECTIONS_OPTIONS_SYSTEM . ' 4.0'; ?>',
                        'systemclass_fs' : '<?= SECTION_SECTIONS_OPTIONS_SYSTEM; ?>',
                        'template' : '<?= SECTION_INDEX_DEV_TEMPLATES . ' 4.0'; ?>',
                        'template_fs' : '<?= SECTION_INDEX_DEV_TEMPLATES; ?>',
                        'widgetclass' : '<?= SECTION_INDEX_DEV_WIDGET . ' 4.0'; ?>',
                        'widgetclass_fs' : '<?= SECTION_INDEX_DEV_WIDGET; ?>',
                        'modules' : '<?= NETCAT_TREE_MODULES; ?>',
                        'users' : '<?= NETCAT_TREE_USERS; ?>'
                    }
                </script>
                <div class="menu_left_opacity"></div>
                <iframe name='treeIframe' id='treeIframe' width="100%" height="100%" frameborder="0" allowtransparency="true"></iframe>
            </div>
            <div class="middle_right">
                <div class="wrap">
                    <div class="wrap_block">
                        <div class="middle_border"></div>
                        <div class="wrap_block_2">
                            <div class="menu_right_opacity"></div>
                            <div class="header_block">
                                <span id='mainViewHeader'></span>

                                <div class="slider_block slider_block_1" id="tabs" style="display: none;">
                                    <div class="left_gradient"><div class="gradient"></div></div>
                                    <div class="right_gradient"><div class="gradient"></div></div>
                                    <a href="#" onclick="return false;" class="arrow left_arrow"></a><a href="#" onclick="return false;" class="arrow right_arrow"></a>
                                    <div class="overflow">
                                        <div class="slide">
                                            <ul id='mainViewTabs'></ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="slider_block slider_block_2" id="sub_tabs" style="display: none;">
                                    <div class="left_gradient"><div class="gradient"></div></div>
                                    <div class="right_gradient"><div class="gradient"></div></div>
                                    <a href="#" onclick="return false;" class="arrow left_arrow"></a><a href="#" onclick="return false;" class="arrow right_arrow"></a>
                                    <div class="overflow">
                                        <div class="slide">
                                            <div class='toolbar'>
                                                <ul id='mainViewToolbar'></ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                            </div>
                            <div class="content_block">
                                <div id='mainViewContent'>
                                    <iframe id='mainViewIframe' name='mainViewIframe' style='width:100%; height:100%; overflow: hidden;' frameborder='0'></iframe>
                                </div>
                            </div>


                            <div class="clear clear_footer"></div>
                        </div>
                    </div>
                </div>
                <div class="footer">
                    <div class='main_view_buttons' id='mainViewButtons'></div>
                </div>
            </div>

    </body>
</html>