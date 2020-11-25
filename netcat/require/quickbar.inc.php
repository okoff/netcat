<?php

/* $Id: quickbar.inc.php 8373 2012-11-08 12:59:04Z ewind $ */

function nc_quickbar_in_template_header($buffer, $File_Mode = false) {
    global $MODULE_VARS, $AUTH_USER_ID, $ADMIN_TEMPLATE, $HTTP_ROOT_PATH, $ADMIN_PATH, $perm;
    global $SUB_FOLDER, $REQUEST_URI, $REQUEST_METHOD, $ADMIN_AUTHTIME;
    global $current_catalogue, $current_sub, $current_cc, $current_user, $AUTHORIZE_BY;
    global $inside_admin, $admin_mode, $user_table_mode, $action, $message;

    $nc_core = nc_Core::get_object();

    if ($inside_admin || !nc_quickbar_permission()) {
        return $buffer;
    }
    
    // reversive direction!
    $buffer = nc_insert_in_head( $buffer, nc_js(), $nc_core->get_variable("admin_mode") );
    
    if ($nc_core->modules->get_by_keyword('auth')) {
        $profile_url = nc_auth_profile_url($AUTH_USER_ID);
    }

    $view_link = ($current_sub['Hidden_URL'] != "/index/" ? $current_sub['Hidden_URL'] : "/") . ($message && $current_cc['EnglishName'] ? $current_cc['EnglishName'] . "_" . $message . ".html" : "");

    if (!$user_table_mode) {
        $edit_link = $HTTP_ROOT_PATH . ($action == "change" ? "message" : $action) . ".php?catalogue=" . $current_catalogue['Catalogue_ID'] . ($current_sub['Subdivision_ID'] ? "&amp;sub=" . $current_sub['Subdivision_ID'] : "") . ($current_cc['Sub_Class_ID'] ? "&amp;cc=" . $current_cc['Sub_Class_ID'] : "") . ($message ? "&amp;message=" . $message : "");
    } else {
        $edit_link = $HTTP_ROOT_PATH . "?catalogue=" . $current_catalogue['Catalogue_ID'] . ($current_sub['Subdivision_ID'] ? "&amp;sub=" . $current_sub['Subdivision_ID'] : "") . ($current_cc['Sub_Class_ID'] ? "&amp;cc=" . $current_cc['Sub_Class_ID'] : "") . ($message ? "&amp;message=" . $message : "");
    }

    $admin_link = "";

    switch (true) {
        case $current_cc['System_Table_ID'] == 3 && $message:
            $admin_link = "#user.edit(" . $message . ")";
            break;
        case $current_cc['Sub_Class_ID'] && $message:
            $admin_link = "#object.view(" . $current_cc['Sub_Class_ID'] . "," . $message . ")";
            break;
        case $current_cc['Sub_Class_ID']:
            $admin_link = "#object.list(" . $current_cc['Sub_Class_ID'] . ")";
            break;
        case $current_sub['Subdivision_ID']:
            $admin_link = "#subclass.list(" . $current_sub['Subdivision_ID'] . ")";
            break;
        case $current_catalogue['Catalogue_ID']:
            $admin_link = "#site.map(" . $current_catalogue['Catalogue_ID'] . ")";
    }

    $admin_link = $ADMIN_PATH . $admin_link;
    $sub_admin_limk = $ADMIN_PATH . "subdivision/index.php?phase=5&SubdivisionID={$current_sub['Subdivision_ID']}&view=all";
    $template_admin_limk = $ADMIN_PATH . 'template/index.php?phase=4&TemplateID=' . $nc_core->template->get_current('Template_ID');
    $sub_class_admin_link = $ADMIN_PATH . "subdivision/SubClass.php?SubdivisionID=" . $current_sub['Subdivision_ID'];
    $msg_img = $ADMIN_PATH . 'skins/default/img/msg.png';
    $pass_admin_link = $ADMIN_PATH . 'user/index.php';
    $lock_img = $ADMIN_PATH . 'skins/default/img/lock.png';
    $right_img = $ADMIN_PATH . 'skins/default/img/right.png';

    $ANY_SYSTEM_MESSAGE = $nc_core->db->get_var("SELECT COUNT(*) FROM `SystemMessage` WHERE `Checked` = 0");
    if ($ANY_SYSTEM_MESSAGE) {
        $msg_title = BEGINHTML_ALARMON;
        $msg_img = 'active';
    } else {
        $msg_title = BEGINHTML_ALARMOFF;
        $msg_img = 'inactive';
    }

    $lang = $nc_core->lang->detect_lang(1);
    if ($lang == 'ru')
        $lang = $nc_core->NC_UNICODE ? "ru_utf8" : "ru_cp1251";

    if ($nc_core->modules->get_by_keyword('cache'))
        $cache_link = $ADMIN_PATH . "#module.cache";

    $PermissionGroup_Name = $nc_core->db->get_col("SELECT PermissionGroup_Name FROM PermissionGroup WHERE PermissionGroup_ID IN (" . join(', ', (array) $current_user['Permission_Group']) . ")");
	/*<script type='text/javascript' src='" . $SUB_FOLDER . $ADMIN_PATH . "js/sitemap.js'></script>
	<script type='text/javascript' src='" . $SUB_FOLDER . $ADMIN_PATH . "js/remind_save.js'></script>*/
    $addon = "<!-- Netcat QuickBar -->\n" .
            "<script type='text/javascript' src='" . $ADMIN_PATH . "js/classes/nc_cookies.class.js'></script>\n" .
            "<script type='text/javascript' src='" . $ADMIN_PATH . "js/classes/nc_drag.class.js'></script>\n" .
            "<script type='text/javascript' src='" . $ADMIN_PATH . "js/lang/" . $lang . ".js?" . $LAST_LOCAL_PATCH . "' charset='" . $nc_core->NC_CHARSET . "'></script>
                <link rel='stylesheet' href='" . $ADMIN_PATH . "/js/codemirror/lib/codemirror.css'>
                <script src='" . $ADMIN_PATH . "js/codemirror/lib/codemirror.js'></script>
                <script src='" . $ADMIN_PATH . "js/codemirror/mode/xml.js'></script>
                <script src='" . $ADMIN_PATH . "js/codemirror/mode/mysql.js'></script>
                <script src='" . $ADMIN_PATH . "js/codemirror/mode/javascript.js'></script>
                <script src='" . $ADMIN_PATH . "js/codemirror/mode/css.js'></script>
                <script src='" . $ADMIN_PATH . "js/codemirror/mode/clike.js'></script>
                <script src='" . $ADMIN_PATH . "js/codemirror/mode/php.js'></script>
                <script type='text/javascript'>
                    var nc_token = '".$nc_core->token->get(+$AUTH_USER_ID)."';
                </script>
                <script type='text/javascript'>
                    jQuery(function () {

                        function getEditorTypeById(id) {
                            if(id == 'Query') {
                                return 'text/x-mysql';
                            }
                            return 'text/x-php';
                        }

                        if(true){

                            window.CMEditors = [];

                            function createCMEditor(ind, el) {
                                var init = true;
                                return function () {
                                    if(init) {
                                        var h = jQuery(el).height();
                                        window.CMEditors[ind] = CodeMirror.fromTextArea(el,{
                                            lineNumbers: true,
                                            mode: getEditorTypeById(jQuery(el).attr('id')),
                                            indentUnit: 4
                                        });
                                        window.CMEditors[ind].id = jQuery(el).attr('id');
                                        var scrollEl = jQuery(window.CMEditors[ind].getScrollerElement());
                                        scrollEl.height(h);
                                    }
                                    else {
                                        var h = jQuery(window.CMEditors[ind].getScrollerElement()).height();
                                        window.CMEditors[ind].toTextArea();
                                        jQuery(el).height(h);
                                    }
                                    init = !init;
                                }
                            }

                            jQuery('textarea').each(function (ind, el) {
                                return null;
                                var prev0 =  jQuery(el).prev(), prev = prev0.prev(), prevPrev = prev.prev(),
                                prev0F = prev0.filter('div.resize_block').children(), prevF = prev.filter('div.resize_block').children(), prevPrevF = prevPrev.filter('div.resize_block').children();
                                prevF.add(prev0F).add(prevPrevF).each(function (i, e) {
                                    jQuery(e).bind('click', function () {
                                        var idd = jQuery(this).attr('href').substr(1);
                                        for(var k in window.CMEditors) {
                                            if(window.CMEditors[k].id == idd) {
                                                var scrollEl = jQuery(window.CMEditors[k].getScrollerElement());
                                                if(jQuery(this).hasClass('textarea_shrink')) {
                                                    scrollEl.height(scrollEl.height() + 20);
                                                }
                                                else if(scrollEl.height() > 120) {
                                                    scrollEl.height(scrollEl.height() - 20);
                                                }
                                                break;
                                            }
                                        }
                                    });
                                });
                                jQuery(el).after(jQuery('<input>').attr({type: 'checkbox', id: 'cmtext'+ind})
                                .click(createCMEditor(ind, el))
                                .after(jQuery('<label>').attr('for', 'cmtext'+ind).html(' " . NETCAT_SETTINGS_CODEMIRROR_SWITCH . "')));
                            });
                        }
                    });
                    jQuery('body').attr('style', 'overflow-y: auto;');
                </script>
                <!-- для диалога генерации альтернативных форм -->
                <script type='text/javascript'>
                    var SUB_FOLDER = '" . $SUB_FOLDER . "';
                    var NETCAT_PATH = '" . $SUB_FOLDER . $HTTP_ROOT_PATH . "';
                    var ADMIN_PATH = '" . $ADMIN_PATH . "';
                    var ADMIN_LANG = '" . MAIN_LANG . "';
                    var NC_CHARSET = '" . $nc_core->NC_CHARSET . "';
                    var ICON_PATH = '" . $ADMIN_TEMPLATE . " + img/';
                </script>" .
            "<script>
                    function showhide(val, val2) {
                        var obj=document.getElementById(val)
                        var obj2=document.getElementById(val2)
                        obj.className=(obj.className=='show_add')? 'hide_add': 'show_add'
                        obj2.className=(obj2.className=='blue')? 'white': 'blue'
                }
                </script>";

    $addon .= "
        <!-- Netcat QuickBar -->
        <script>
            jQuery(document).ready(function(){
                jQuery('div#nc_password_change_footer input.nc_admin_metro_button').click(function() {
                    jQuery('div#nc_password_change_body form').submit();
              });
            });
        </script>

        <div id='nc_quick_bar'>
            <div id='nc_quick_bar_left' style='position: absolute;'>
                <div class='nc_quick_bar".(!$admin_mode ? '_active' : '')."'>
                    <div>
                        <a href='{$SUB_FOLDER}{$view_link}'>" . NETCAT_QUICKBAR_BUTTON_VIEWMODE . "</a>
                    </div>
                </div>

                <div class='nc_quick_bar".($admin_mode ? '_active' : '')."'>
                    <div>
                        <a href='{$SUB_FOLDER}{$edit_link}'>" . NETCAT_QUICKBAR_BUTTON_EDITMODE . "</a>
                    </div>
                </div>
            </div>

            <script>
                function nc_qb_show(item) {
                    if(jQuery('.nc_'+item+'_menu').hasClass('hover_'+item)) {
                        jQuery('.nc_'+item+'_menu').removeClass('hover_'+item);
                    } else {
                        jQuery('.nc_'+item+'_menu').addClass('hover_'+item);
                    }

                    jQuery('.nc_'+item+'_menu').mouseleave(function() {
                        jQuery('.nc_'+item+'_menu').removeClass('hover_'+item);
                    });
                }
            </script>

            <div id='nc_quick_bar_right' style='position: absolute; left: 285px;'>
                <div class='nc_inner_more_menu' style='top: 0px; padding-left: 19px;'>
                    <div class='nc_more_menu' style='padding-top: 10px;'>
                        <span id='nc_qb_more' style='display: block; cursor: pointer;' onClick='nc_qb_show(\\\"more\\\");'>".NETCAT_QUICKBAR_BUTTON_MORE."</span>
                        <span style='top: 44px; font-size: 12px;'>
                            <span>
                                <div class='icons icon_settings'></div>
                                <div class='nc_qb_desc'>
                                    <a style='text-decoration: none; color: black;' href='$sub_admin_limk' onclick='nc_form(this.href); return false;'>".NETCAT_QUICKBAR_BUTTON_SUBDIVISION_SETTINGS."</a>
                                </div>
                            </span>

                            <span>
                                <div class='icons icon_templates'></div>
                                <div class='nc_qb_desc'>
                                    <a style='text-decoration: none; color: black;' href='$template_admin_limk' onclick='nc_form(this.href); return false;'>".NETCAT_QUICKBAR_BUTTON_TEMPLATE_SETTINGS."</a>
                                </div>
                            </span>

                            <span>
                                <div class='icons icon_install_module'></div>
                                <div class='nc_qb_desc'>
                                    <a style='text-decoration: none; color: black;' href='$admin_link'>".NETCAT_QUICKBAR_BUTTON_ADMIN."</a>
                                </div>
                            </span>
                        </span>
                    </div>
                </div>
            </div>

                <div class='nc_inner_user_menu' style='top: 0px; padding-left: 19px;'>
                    <div class='nc_user_menu' style='vertical-align: top; padding-top:10px; padding-left:0px;'>
                        <span id='nc_qb_user' style='display: block; cursor: pointer;'onClick='nc_qb_show(\\\"user\\\");'>" . $perm->getLogin() . "</span>
                        <span style='top: 43px; font-size: 12px;'>
                        <span style='border-bottom: 1px solid #AAAAAA;'>
                            " . NETCAT_ADMIN_AUTH_PERM . " <span style='color: #AAAAAA;'>" . addslashes( join(', ', Permission::get_all_permission_names_by_id($AUTH_USER_ID))) . "</span>
                        </span>
                        <span>
                            <div style='cursor: pointer;' onClick='nc_password_change();'>
                                " . NETCAT_ADMIN_AUTH_CHANGE_PASS . "
                            </div>
                            <div style='float: right;'>
                                <a href='" . ($MODULE_VARS['auth'] ? $SUB_FOLDER . $HTTP_ROOT_PATH . "modules/auth/?logoff=1&amp;REQUESTED_FROM=" . $REQUEST_URI . "&amp;REQUESTED_BY=" . $REQUEST_METHOD : $ADMIN_PATH . "unauth.php" ) . "' style='text-decoration: none; color: black;'>
                                    " . NETCAT_ADMIN_AUTH_LOGOUT . "
                                </a>
                            </div>
                        </span>
                    </span>
                </div>
            </div>
            
            <div class='nc_inner_notice' title='".$msg_title."'>
				<a href='/netcat/admin/#tools.systemmessages' ><div class='icon_sysmsg_$msg_img'></div></a>
			</div>
        </div>

        <div id='nc_password_change' style='display: none;'>
            <div id='nc_password_change_header'>
                <div>
                    <h2 style='padding: 0px;'>".CONTROL_USER_CHANGEPASS."</h2>
                </div>
            </div>

            <div id='nc_password_change_body'>
                <form method='post' action='$pass_admin_link'>
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
                <input class='nc_admin_metro_button' type='button' value='" . NETCAT_REMIND_SAVE_SAVE . "' />
                <input class='nc_admin_metro_button_cancel' style='color: black; margin-right: 16px; background-color: #EEEEEE; border: 1px solid red;' type='button' value='". CONTROL_BUTTON_CANCEL ."' />
            </div>
        </div>

        <div id='nc_quick_bar_padding'></div>

        <!-- /Netcat QuickBar -->";

    if ($File_Mode) {
        $addon = str_replace("\\\"", "\"", $addon);
    }

    switch (true) {
        case nc_preg_match("/\<\s*?frameset.*?\>/im", $buffer):
            break;
        case nc_preg_match("/\<\s*?body.*?\>/im", $buffer):
            $preg_pattern = "/(\<\s*?body.*?\>){1}/im";
            $preg_replacement = "\$1\n" . $addon;
            break;
        case nc_preg_match("/\<\s*?html\s*?\>/im", $buffer):
            $preg_pattern = "/(\<\s*?html\s*?\>){1}/im";
            $preg_replacement = "\$1\n<body>" . $addon . "</body>";
            break;
    }

    if ($preg_pattern && $preg_replacement) {
        $buffer = nc_preg_replace($preg_pattern, $preg_replacement, $buffer);
    }

    return $buffer;
}


?>
