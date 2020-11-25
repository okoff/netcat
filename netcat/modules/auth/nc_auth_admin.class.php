<?php
/* $id$ */

class nc_auth_admin {

    protected $core, $db;

    public function __construct() {
        $this->core = nc_Core::get_object();
        // global variables to internal
        $this->db = &$this->core->db;
    }

    public function get_mainsettings_url() {
        return "#module.auth.general";
    }

    /**
     * Вывод информации о модуле
     */
    public function info_show() {
        $all_user_count = $this->db->get_var("SELECT COUNT(`User_ID`) FROM `User`");
        $all_user_unckecked = $this->db->get_var("SELECT COUNT(`User_ID`) FROM `User` WHERE `Checked` = 0");
        $all_user_nonconfirmed = $this->db->get_var("SELECT COUNT(`User_ID`) FROM `User` WHERE `Checked` = 0 AND `Confirmed` = 0 AND `RegistrationCode` <> '' ");

        echo "<br /><div style='margin-bottom: 20px;'>" . NETCAT_MODULE_AUTH_DESCRIPTION . "</div>";
        echo "<div style='margin-bottom: 4px;'>" . NETCAT_MODULE_AUTH_ADMIN_INFO_USER_COUNT . ": " . ( $all_user_count ? "<a href='" . $this->core->ADMIN_PATH . "user/'>" . $all_user_count . "</a>" : NETCAT_MODULE_AUTH_ADMIN_INFO_NONE) . "</div>";
        echo "<div style='margin-bottom: 4px;'>" . NETCAT_MODULE_AUTH_ADMIN_INFO_USER_COUNT_UNCHECKED . ": " . ( $all_user_unckecked ? "<a href='" . $this->core->ADMIN_PATH . "user/?Checked=2'>" . $all_user_unckecked . "</a>" : NETCAT_MODULE_AUTH_ADMIN_INFO_NONE) . "</div>";
        echo "<div>" . NETCAT_MODULE_AUTH_ADMIN_INFO_USER_COUNT_UNCONFIRMED . ": " . ( $all_user_nonconfirmed ? "<a href='" . $this->core->ADMIN_PATH . "user/?nonConfirmed=1'>" . $all_user_nonconfirmed . "</a>" : NETCAT_MODULE_AUTH_ADMIN_INFO_NONE) . "</div>";
    }

    /**
     * dummy
     */
    public function info_save() {
        return;
    }

    /**
     * Настройки регистрации по логину и паролю
     */
    public function classic_show() {
        global $UI_CONFIG;
        $nc_core = nc_Core::get_object();
        $UI_CONFIG->add_reg_toolbar();
        // настройки
        $settings = $nc_core->get_settings('', 'auth');

        // поля из системной таблицы
        $st = new nc_Component(0, 3);
        $fields = array();
        foreach ($st->get_fields() as $v) {
            if ($v['edit_type'] != 1)
                continue;
            if ($v['name'] == $this->core->AUTHORIZE_BY)
                continue;
            $fields[$v['name']] = $v['description'];
        }

        // группы пользователя
        $groups = array();
        $res = $this->db->get_results("SELECT `PermissionGroup_ID` as `id`, `PermissionGroup_Name` as `name` FROM `PermissionGroup` ORDER BY `PermissionGroup_ID`", ARRAY_A);
        if (!empty($res))
            foreach ($res as $v)
                $groups[$v['id']] = $v['name'];

        // основные настройки
        echo "<form method='post' action='admin.php' id='adminForm' style='padding:0; margin:0;'>\n" .
        "<fieldset>\n" .
        "<legend>\n" .
        "" . NETCAT_MODULE_CACHE_ADMIN_MAINSETTINGS_TITLE . "\n" .
        "</legend>\n";
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_DENY_REG, 'deny_reg', $settings);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_DENY_RECOVERY, 'deny_recovery', $settings);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_ALLOW_CYRILLIC, 'allow_cyrillic', $settings);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_ALLOW_SPECIALCHARS, 'allow_specialchars', $settings);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_ALLOW_CHANGE_LOGIN, 'allow_change_login', $settings);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_BING_TO_CATALOGUE, 'bind_to_catalogue', $settings);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_WITH_SUBDOMAIN, 'with_subdomain', $settings);
        echo nc_admin_input_in_text(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_AUTH_CAPTCHA_NUM, 'auth_captcha_num', $settings, 1) . "<br/>";
        echo nc_admin_input_in_text(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_PASS_MIN, 'pass_min', $settings, 1);
        echo "</fieldset>\n";

        // форма регистрации
        echo
        "<fieldset>\n" .
        "<legend>\n" .
        "" . NETCAT_MODULE_AUTH_ADMIN_CLASSIC_REGISTRATION_FORM . "\n" .
        "</legend>\n";
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_CHECK_LOGIN, 'check_login', $settings);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_CHECK_PASS, 'check_pass', $settings);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_CHECK_PASS2, 'check_pass2', $settings);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_CHECK_AGREED, 'agreed', $settings);

        echo NETCAT_MODULE_AUTH_ADMIN_CLASSIC_FIELDS_IN_REG_FORM . ":<br/>";
        echo "<input type='radio' name='field_all' id='field_all' value='1' " . ($settings['field_all'] ? "checked='checked'" : "") . "><label for='field_all'>" . NETCAT_MODULE_AUTH_ADMIN_CLASSIC_FIELDS_IN_REG_FORM_ALL . "</label><br/>";
        echo "<input type='radio' name='field_all' id='field_custom' value='0' " . (!$settings['field_all'] ? "checked='checked'" : "") . "><label for='field_custom'>" . NETCAT_MODULE_AUTH_ADMIN_CLASSIC_FIELDS_IN_REG_FORM_CUSTOM . "</label>";
        echo "<div style='padding-left: 15px;'>";
        $f = explode(',', $settings['field_custom']);
        foreach ($fields as $k => $v) {
            echo nc_admin_checkbox($v, 'field_custom_' . $k, in_array($k, $f));
        }
        echo "</div>";
        echo "</fieldset>\n";


        // активация
        echo
        "<fieldset>\n" .
        "<legend>\n" .
        "" . NETCAT_MODULE_AUTH_ADMIN_CLASSIC_ACTIVATION . "\n" .
        "</legend>\n";
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_CONFIRM, 'confirm', $settings);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_PREMODARATION, 'premoderation', $settings);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_NOTIFY_ADMIN, 'notify_admin', $settings);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_AUTHAUTORIZE, 'autoauthorize', $settings);
        echo nc_admin_input_in_text(NETCAT_MODULE_AUTH_ADMIN_CLASSIC_CONFIRM_TIME, 'confirm_time', $settings, 4);
        echo "</fieldset>\n";

        // группы пользователей
        echo
        "<fieldset>\n" .
        "<legend>\n" .
        "" . NETCAT_MODULE_AUTH_GROUPS_WHERE_USER_WILL_BE . "\n" .
        "</legend>\n";
        $f = explode(',', $settings['group']);
        foreach ($groups as $g_id => $g_name) {
            echo nc_admin_checkbox($g_name, 'group_' . $g_id, in_array($g_id, $f));
        }
        echo "</fieldset>\n";


        echo "<input type='hidden' name='Catalogue_ID' value='" . $Catalogue_ID . "' />\n";
        echo $nc_core->token->get_input() . "\n";
        echo "<input type='hidden' name='view' value='classic' />\n";
        echo "<input type='hidden' name='act' value='save' />\n";
        echo "</form>\n";



        $UI_CONFIG->actionButtons[] =
                array("id" => "submit",
                        "caption" => NETCAT_MODERATION_BUTTON_CHANGE,
                        "action" => "mainView.submitIframeForm('adminForm')");
    }

    public function classic_save() {
        $nc_core = nc_Core::get_object();
        $params = array('deny_reg', 'deny_recovery', 'allow_cyrillic', 'allow_specialchars', 'allow_change_login',
                'auth_captcha_num', 'pass_min', 'bind_to_catalogue', 'with_subdomain',
                'check_login', 'check_pass', 'check_pass2', 'agreed', 'confirm_time',
                'field_all', 'confirm', 'premoderation', 'notify_admin', 'autoauthorize');
        // настройки
        foreach ($params as $v) {
            $nc_core->set_settings($v, intval($nc_core->input->fetch_get_post($v)), 'auth');
        }

        // поля, выводимые при регистрации
        $f = array();
        foreach ($nc_core->input->fetch_get_post() as $k => $v) {
            if (nc_preg_match('/field_custom_([a-z_]+)/i', $k, $match)) {
                $f[] = $match[1];
            }
        }
        $nc_core->set_settings('field_custom', join(',', $f), 'auth');

        // группа
        $f = array();
        foreach ($nc_core->input->fetch_get_post() as $k => $v) {
            if (nc_preg_match('/group_([0-9]+)/i', $k, $match)) {
                $f[] = $match[1];
            }
        }
        $nc_core->set_settings('group', join(',', $f), 'auth');
    }

    /**
     * Показ формы настроек шаблонов вывода
     */
    public function templates_show() {
        $nc_auth = nc_auth::get_object();

        $auth_editor = new nc_module_editor();
        $auth_editor->load('auth')->fill();
        ?>

        <script type="text/javascript">
            body = {
                user_login_form: "<?php echo nc_text_for_js(str_replace('"', '\\"', $nc_auth->tpl->get_user_login_form_default_fs())); ?>",
                change_password_form: "<?php echo nc_text_for_js(str_replace('"', '\\"', $nc_auth->tpl->get_change_password_form_default_fs())); ?>",
                recovery_password_form: "<?php echo nc_text_for_js(str_replace('"', '\\"', $nc_auth->tpl->get_recovery_password_form_default_fs())); ?>"
		};

            function recovery( type, name, confirmed) {
                if ( !confirmed ) {
                    if ( confirm(ncLang.WarnAuthMail) ) {
                        recovery(type, name, 1 );
                    }
                    return false;
                }
                
                selectedTextarea = jQuery("#" + type + "_" + name);
                selectedTextarea.val(body[name]);
                if (typeof selectedTextarea.codemirror == 'function') {
                    selectedTextarea.codemirror('setValue'); 
                }
            }
        </script>
        <br />
        <?php
        $auth_settings = $auth_editor->get_all_fields();
        $auth_settings['old'] = nc_Core::get_object()->get_settings('', 'auth');
        echo "<form action='admin.php' name='AuthSettings' id='AuthSettings' method='post'>";
        echo NETCAT_MODULE_AUTH_ADMIN_INFO;

        foreach ($auth_settings as $type => $settings) {
            $pref = ($type == 'old' ? '' : $type.'_');
            echo "
    <legend style='padding-bottom: 0px;'>" . constant("TITLE_" . strtoupper($type)) . "</legend>
    <fieldset>
        <div style='float:right; margin-right: 20px;'><a href='#' onclick=\"recovery('$type', 'user_login_form'); return false;\" >
        " . NETCAT_MODULE_AUTH_RESTORE_DEF . "</a></div><br clear='all'>
        " . nc_admin_textarea(NETCAT_MODULE_AUTH_FORM_AUTH, $pref . 'user_login_form', $settings['user_login_form'], 1, 0, "height:15em;") . "
        " . nc_admin_checkbox(NETCAT_MODULE_AUTH_FORM_DISABLED, $pref . 'user_login_form_disable', $settings['user_login_form_disable']) . "
    </fieldset>

    <fieldset>
        <div style='float:right; margin-right: 20px;'><a href='#' onclick=\"recovery('$type', 'change_password_form'); return false;\" >" . NETCAT_MODULE_AUTH_RESTORE_DEF . "</a></div><br clear='all'>" .
            nc_admin_textarea(NETCAT_MODULE_AUTH_FORM_CHG_PASS, $pref . 'change_password_form', $settings['change_password_form'], 1, 0, "height:15em;") .
            nc_admin_textarea(NETCAT_MODULE_AUTH_FORM_CHG_PASS_AFTER, $pref . 'change_password_after', $settings['change_password_after'], 1) .
            nc_admin_textarea(NETCAT_MODULE_AUTH_FORM_CHG_PASS_WARNBLOCK, $pref . 'change_password_warn', $settings['change_password_warn'], 1) . "
    </fieldset>

    <fieldset>
        <div style='float:right; margin-right: 20px;'>
        <a href='#' onclick=\"recovery('$type', 'recovery_password_form'); return false;\" >" . NETCAT_MODULE_AUTH_RESTORE_DEF . "</a></div><br clear='all'>" .
            nc_admin_textarea(NETCAT_MODULE_AUTH_FORM_REC_PASS, $pref . 'recovery_password_form', $settings['recovery_password_form'], 1, 0, "height:15em;") .
            nc_admin_textarea(NETCAT_MODULE_AUTH_FORM_REC_PASS_AFTER, $pref . 'recovery_password_after', $settings['recovery_password_after'], 1) .
            nc_admin_textarea(NETCAT_MODULE_AUTH_FORM_CHG_PASS_WARNBLOCK, $pref . 'recovery_password_warn', $settings['recovery_password_warn'], 1) .
            nc_admin_textarea(NETCAT_MODULE_AUTH_FORM_CHG_PASS_DENY, $pref . 'recovery_password_deny', $settings['recovery_password_deny'], 1) . "
    </fieldset>
        ";
        }
        echo "<br />";

        echo $this->core->token->get_input() . "
    <input type='hidden' name='view' value='templates' />
    <input type='hidden' name='act' value='save' /></form>";

        global $UI_CONFIG;
        $UI_CONFIG->add_settings_toolbar();
        $UI_CONFIG->actionButtons[] =
                array("id" => "submit",
                        "caption" => NETCAT_MODERATION_BUTTON_CHANGE,
                        "action" => "mainView.submitIframeForm('adminForm')");
        return 0;
    }

    public function templates_save() {
        $nc_core = nc_Core::get_object();

        $params = array(
                'user_login_form',
                'user_login_form_disable',
                'change_password_form',
                'change_password_after',
                'change_password_warn',
                'recovery_password_form',
                'recovery_password_after',
                'recovery_password_warn',
                'recovery_password_deny');
        // настройки
        foreach ($params as $v) {
            $nc_core->set_settings($v, $nc_core->input->fetch_get_post($v), 'auth');
        }

        $module_editor = new nc_module_editor();
        $module_editor->load('auth')->save($_POST);
    }

    /**
     * Показ формы настроек авторизации через внешние сервисы
     */
    public function ex_show() {
        global $UI_CONFIG;
        $UI_CONFIG->add_reg_toolbar();

        $nc_core = nc_Core::get_object();

        // настройки
        $settings = $nc_core->get_settings('', 'auth');
        $settings['ex_enabled'] = unserialize($settings['ex_enabled']);
        $settings['ex_apps'] = unserialize($settings['ex_apps']);
        $settings['ex_group'] = unserialize($settings['ex_group']);
        $settings['ex_addaction'] = unserialize($settings['ex_addaction']);
        $settings['ex_fields'] = unserialize($settings['ex_fields']);
        $settings['ex_openid_providers'] = unserialize($settings['ex_openid_providers']);
        if (!$settings['ex_group'])
            $settings['ex_group'] = array();

        // группы пользователя
        $groups = array();
        $res = $this->db->get_results("SELECT `PermissionGroup_ID` as `id`, `PermissionGroup_Name` as `name` FROM `PermissionGroup` ORDER BY `PermissionGroup_ID`", ARRAY_A);
        if (!empty($res))
            foreach ($res as $v)
                $groups[$v['id']] = $v['name'];

        // поля из системной таблицы
        $utable = new nc_Component(0, 3);
        $field_user = $utable->get_fields();
        $js_field_user = array();
        if (!empty($field_user))
            foreach ($field_user as $v) {
                $js_field_user[] = "" . $v['name'] . ": '" . $v['description'] . "'";
            }

        if (!$nc_core->php_ext("curl")) {
            nc_print_status(NETCAT_MODULE_AUTH_ADMIN_EX_CURL_REQUIRED, 'info');
        }
        if (!$nc_core->php_ext("json")) {
            nc_print_status(NETCAT_MODULE_AUTH_ADMIN_EX_JSON_REQUIRED, 'info');
        }

        echo "
      <form action='admin.php' name='adminForm' id='adminForm' method='post'>
      " . $this->core->token->get_input() . "
      <input type='hidden' name='view' value='ex' />
      <input type='hidden' name='act' value='save' />";

        // настройки каждого сервиса
        $types = array('vk', 'fb', 'twitter', 'openid');
        foreach ($types as $v) {
            $field = new nc_admin_fieldset(constant("NETCAT_MODULE_AUTH_ADMIN_EX_" . strtoupper($v)));
            $field->add(nc_admin_checkbox(constant("NETCAT_MODULE_AUTH_ADMIN_EX_" . strtoupper($v) . "_ENABLED"), $v . '_enabled', $settings['ex_enabled'][$v]));
            if ($v != 'openid')
                $field->add(
                        nc_admin_input(constant("NETCAT_MODULE_AUTH_APPLICATION_ID_" . strtoupper($v)), $v . '_app_id', $settings['ex_apps'][$v]['app_id'], 0, 'width:30%; margin-bottom: 5px;') .
                        nc_admin_input(constant("NETCAT_MODULE_AUTH_SECRET_KEY_" . strtoupper($v)), $v . '_app_key', $settings['ex_apps'][$v]['app_key'], 0, 'width:30%')
                );

            // группы
            $html = "<div style='margin-bottom: 5px;'>" . NETCAT_MODULE_AUTH_GROUPS_WHERE_USER_WILL_BE . ":</div>";
            foreach ($groups as $g_id => $g_name) {
                $html .= "<input id='" . $v . "_group_" . $g_id . "' type='checkbox' name='" . $v . "_group[]' value='" . $g_id . "' " . ( in_array($g_id, $settings['ex_group'][$v]) ? " checked='checked' " : "") . " />
            <label for='" . $v . "_group_" . $g_id . "'>" . $g_name . "</label><br/>";
            }
            $field->add($html);

            // действие после добавления
            $field->add(nc_admin_textarea(NETCAT_MODULE_AUTH_ACTION_AFTER_FIRST_AUTHORIZATION, $v . '_addaction', $settings['ex_addaction'][$v], 1));

            // соответствие полей
            $html = "<div style='font-weight: bold; margin-top: 10px;'>" . NETCAT_MODULE_AUTH_ACTION_FIELDS_MAPPING . "</div>";
            $html .= "<div id='" . $v . "_mapping'></div>
        <div onclick='nc_mf_" . $v . ".add()' class='mf_add'>
          <div class='icons icon_obj_add' title='".NETCAT_MODULE_AUTH_ACTION_FIELDS_MAPPING_ADD."'></div>" . NETCAT_MODULE_AUTH_ACTION_FIELDS_MAPPING_ADD . "
        </div>";
            $field->add($html);

            if ($v == 'openid') {
                $html = "<div id='openid_providers'></div>
        <div onclick='op.add()' class='openid_providers_add'>
          <div class='icons icon_obj_add' title='".NETCAT_MODULE_AUTH_PROVIDER_ADD."'></div>" . NETCAT_MODULE_AUTH_PROVIDER_ADD . "
        </div>";
                $field->add($html);
            }

            $result .= $field->result();
            unset($field);
        }

        echo $result;

        // js для полей и openid провайдеров
        echo "<script type='text/javascript'>
      nc_mf_vk = new nc_mapping_fields({ " . join(',', $js_field_user) . " }, { 'uid' : 'ID', 'first_name': '" . NETCAT_MODULE_AUTH_FIRST_NAME . "', 'last_name': '" . NETCAT_MODULE_AUTH_LAST_NAME . "', 'nickname':'" . NETCAT_MODULE_AUTH_NICKNAME . "', 'photo_big' : '" . NETCAT_MODULE_AUTH_PHOTO . "'}, 'vk_mapping', 'nc_mf_vk', '" . NETCAT_MODULE_AUTH_ADMIN_EX_DATA_VK . "' );
      nc_mf_fb = new nc_mapping_fields({ " . join(',', $js_field_user) . " }, { 'id' : 'ID', 'name': '" . NETCAT_MODULE_AUTH_FIRST_NAME . "', 'email':'Email', 'picture' : '" . NETCAT_MODULE_AUTH_PHOTO . "'}, 'fb_mapping', 'nc_mf_fb', '" . NETCAT_MODULE_AUTH_ADMIN_EX_DATA_FB . "' );
      nc_mf_twitter = new nc_mapping_fields({ " . join(',', $js_field_user) . " }, { 'id' : 'ID', 'name': '" . NETCAT_MODULE_AUTH_FIRST_NAME . "', 'profile_image_url' : '" . NETCAT_MODULE_AUTH_PHOTO . "', 'screen_name':'" . NETCAT_MODULE_AUTH_LOGIN . "'}, 'twitter_mapping', 'nc_mf_twitter', '" . NETCAT_MODULE_AUTH_ADMIN_EX_DATA_TWITTER . "' );
      nc_mf_openid = new nc_mapping_fields({ " . join(',', $js_field_user) . " }, { 'nickname' : '" . NETCAT_MODULE_AUTH_NICKNAME . "', 'fullname': '" . NETCAT_MODULE_AUTH_FIRST_NAME . "', 'email':'Email'}, 'openid_mapping', 'nc_mf_openid', '" . NETCAT_MODULE_AUTH_ADMIN_EX_DATA_OPENID . "' );
      op = new nc_openidproviders(); ";
        // поля
        foreach ($types as $v) {
            if ($settings['ex_fields'][$v])
                foreach ($settings['ex_fields'][$v] as $f1 => $f2) {
                    echo "nc_mf_" . $v . ".add('" . $f1 . "','" . $f2 . "');";
                }
        }
        // провайдеры
        if ($settings['ex_openid_providers'])
            foreach ($settings['ex_openid_providers'] as $provider) {
                echo "op.add('" . $provider['name'] . "','" . $provider['url'] . "', '" . $provider['imglink'] . "');";
            }
        echo "</script>";
        echo nc_admin_js_resize();
        echo "</form>";

        $UI_CONFIG->actionButtons[] =
                array("id" => "submit",
                        "caption" => NETCAT_MODERATION_BUTTON_CHANGE,
                        "action" => "mainView.submitIframeForm('adminForm')");
        return true;
    }

    /**
     * Сохранение настроек "Авторизация через внешние сервисы"
     */
    public function ex_save() {
        $nc_auth = nc_auth::get_object();
        $nc_core = nc_Core::get_object();

        $input = $this->core->input->fetch_get_post();

        $types = array('vk', 'fb', 'twitter', 'openid');

        // основные параметры
        foreach ($types as $v) {
            // возможность авторизации
            $ex_enabled[$v] = intval($input[$v . '_enabled']);
            // id приложения
            if (isset($input[$v . '_app_id']))
                $ex_apps[$v]['app_id'] = $input[$v . '_app_id'];
            // секретный ключ
            if (isset($input[$v . '_app_key']))
                $ex_apps[$v]['app_key'] = $input[$v . '_app_key'];
            // действие после добавления
            $ex_addaction[$v] = $input[$v . '_addaction'];
            // группы
            $ex_group[$v] = $input[$v . '_group'];
        }
        // соответствие полей и openid-провайдеры
        foreach ($input as $k => $v) {
            if (preg_match('/([a-z]+)_mapping_field1_value_(\d+)/i', $k, $match)) {
                $name = $match[1];
                $id = $match[2];
                $f1 = $input[$name . '_mapping_field1_value_' . $id];
                $f2 = $input[$name . '_mapping_field2_value_' . $id];
                $ex_fields[$name][$f1] = $f2;
            }

            if (preg_match('/openid_providers_name_(\d+)/i', $k, $match)) {
                $id = $match[1];
                if (!$input['openid_providers_name_' . $id])
                    continue;
                $ex_openid_providers[$id] = array(
                        'name' => $input['openid_providers_name_' . $id],
                        'url' => $input['openid_providers_url_' . $id],
                        'imglink' => $input['openid_providers_imglink_' . $id]);
            }
        }

        $nc_core->set_settings('ex_enabled', serialize($ex_enabled), 'auth');
        $nc_core->set_settings('ex_apps', serialize($ex_apps), 'auth');
        $nc_core->set_settings('ex_addaction', serialize($ex_addaction), 'auth');
        $nc_core->set_settings('ex_group', serialize($ex_group), 'auth');
        $nc_core->set_settings('ex_fields', serialize($ex_fields), 'auth');
        $nc_core->set_settings('ex_openid_providers', serialize($ex_openid_providers), 'auth');

        return true;
    }

    /**
     * Форма общих настроек
     */
    public function general_show() {
        global $UI_CONFIG;
        $UI_CONFIG->add_settings_toolbar();
        $nc_core = nc_Core::get_object();
        // настройки
        $settings = $nc_core->get_settings('', 'auth');

        // Способы авторизации на сайте
        echo "<form method='post' action='admin.php' id='adminForm' style='padding:0; margin:0;'>\n" .
        "<fieldset>\n" .
        "<legend>\n" .
        "" . NETCAT_MODULE_AUTH_ADMIN_GENERAL_AUTH_SITE . "\n" .
        "</legend>\n";
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_GENERAL_METHOD_LOGIN, 'authtype_site_login', $settings['authtype_site'] & NC_AUTHTYPE_LOGIN);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_GENERAL_METHOD_HASH, 'authtype_site_hash', $settings['authtype_site'] & NC_AUTHTYPE_HASH);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_GENERAL_METHOD_EX, 'authtype_site_ex', $settings['authtype_site'] & NC_AUTHTYPE_EX);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_GENERAL_METHOD_TOKEN, 'authtype_site_token', $settings['authtype_site'] & NC_AUTHTYPE_TOKEN);
        echo "</fieldset>\n";

        // Способы авторизации в систему администрирования
        echo
        "<fieldset>\n" .
        "<legend>\n" .
        "" . NETCAT_MODULE_AUTH_ADMIN_GENERAL_AUTH_ADMIN . "\n" .
        "</legend>\n";
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_GENERAL_METHOD_LOGIN, 'authtype_admin_login', $settings['authtype_admin'] & NC_AUTHTYPE_LOGIN);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_GENERAL_METHOD_HASH, 'authtype_admin_hash', $settings['authtype_admin'] & NC_AUTHTYPE_HASH);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_GENERAL_METHOD_TOKEN, 'authtype_admin_token', $settings['authtype_admin'] & NC_AUTHTYPE_TOKEN);
        echo "<div style='height: 3px;'>&nbsp;</div>";
        //echo nc_admin_checkbox('Разрешить вход только по https-протоколу', 'admin_https', $settings['admin_https']);
        echo "</fieldset>\n";

        // основные настройки
        echo
        "<fieldset>\n" .
        "<legend>\n" .
        "" . NETCAT_MODULE_AUTH_ADMIN_GENERAL_PM . "\n" .
        "</legend>\n";
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_GENERAL_PM_ALLOW, 'pm_allow', $settings);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_GENERAL_PM_NOTIFY, 'pm_notify', $settings);
        echo "</fieldset>\n";

        // друзья
        echo
        "<fieldset>\n" .
        "<legend>\n" .
        "" . NETCAT_MODULE_AUTH_ADMIN_GENERAL_FRIEND_BANNED . "\n" .
        "</legend>\n";
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_GENERAL_FRIEND_ALLOW, 'friend_allow', $settings);
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_GENERAL_BANNED_ALLOW, 'banned_allow', $settings);
        echo "</fieldset>\n";

        // личный счет
        echo
        "<fieldset>\n" .
        "<legend>\n" .
        "" . NETCAT_MODULE_AUTH_ADMIN_GENERAL_PA . "\n" .
        "</legend>\n";
        echo nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_GENERAL_PA_ALLOW, 'pa_allow', $settings);
        echo nc_admin_input_in_text(NETCAT_MODULE_AUTH_ADMIN_GENERAL_PA_CURRENCY, 'pa_currency', $settings['pa_currency'], 4) . '<br/>';
        echo nc_admin_input_in_text(NETCAT_MODULE_AUTH_ADMIN_GENERAL_PA_START, 'pa_start', $settings['pa_start'], 4);
        echo "</fieldset>\n";


        echo $nc_core->token->get_input() . "\n";
        echo "<input type='hidden' value='general' name='view' />";
        echo "<input type='hidden' value='save' name='act' />";
        echo "</form>\n";


        $UI_CONFIG->actionButtons[] =
                array("id" => "submit",
                        "caption" => NETCAT_MODERATION_BUTTON_CHANGE,
                        "action" => "mainView.submitIframeForm('adminForm')");
    }

    public function general_save() {
        $nc_core = nc_Core::get_object();

        // включение/выключение Личного счета надо обрабатывать отдельно
        $cur_pa_allow = $nc_core->get_settings('pa_allow', 'auth');
        if ($cur_pa_allow != $nc_core->input->fetch_get_post('pa_allow')) {
            // компонент "Личный счет"
            if (($class_id = $nc_core->get_settings('pa_class_id', 'auth'))) {
                $subs = $nc_core->db->get_results("SELECT s.`Subdivision_ID` as `id`, s.`Subdivision_Name` as `name` FROM `Subdivision` as `s`, `Sub_Class` AS `sc` WHERE sc.Subdivision_ID = s.Subdivision_ID AND sc.Class_ID = '" . $class_id . "' ", ARRAY_A);
                $subs_id = $nc_core->db->get_col(null, 0);
                $subs_name = $nc_core->db->get_col(null, 1);
            }
            $field = $nc_core->get_settings('pa_field', 'auth');
            $method = $cur_pa_allow ? 'uncheck_field' : 'check_field';
            // включение/выключение поля
            $nc_core->$method('User', $field);
            // включение/выключение разделов
            if ($subs_id) {
                $nc_core->db->query("UPDATE Subdivision SET Checked = '" . ( $cur_pa_allow ? 0 : 1) . "' WHERE Subdivision_ID IN (" . join(',', $subs_id) . ") ");
            }
        }

        // способы авторизации на сайте
        $r = $nc_core->input->fetch_get_post('authtype_site_login') ? NC_AUTHTYPE_LOGIN : 0;
        $r += $nc_core->input->fetch_get_post('authtype_site_hash') ? NC_AUTHTYPE_HASH : 0;
        $r += $nc_core->input->fetch_get_post('authtype_site_ex') ? NC_AUTHTYPE_EX : 0;
        $r += $nc_core->input->fetch_get_post('authtype_site_token') ? NC_AUTHTYPE_TOKEN : 0;
        $nc_core->set_settings('authtype_site', $r, 'auth');

        // способы авторизации в админку
        $r = $nc_core->input->fetch_get_post('authtype_admin_login') ? NC_AUTHTYPE_LOGIN : 0;
        $r += $nc_core->input->fetch_get_post('authtype_admin_hash') ? NC_AUTHTYPE_HASH : 0;
        $r += $nc_core->input->fetch_get_post('authtype_admin_token') ? NC_AUTHTYPE_TOKEN : 0;
        $nc_core->set_settings('authtype_admin', $r, 'auth');



        $params = array('pm_allow', 'pm_notify', 'friend_allow', 'banned_allow', 'pa_allow', 'pa_start', 'pa_currency', 'admin_https');
        // настройки
        foreach ($params as $v) {
            $nc_core->set_settings($v, $nc_core->input->fetch_get_post($v), 'auth');
        }
    }

    public function mail_show() {
        global $UI_CONFIG;
        $UI_CONFIG->add_settings_toolbar();
        $settings = nc_Core::get_object()->get_settings('', 'auth');
        ?>
        <script type="text/javascript">
            body = {mail_confirm: "<?php echo nc_text_for_js(NETCAT_MODULE_AUTH_ADMIN_DEF_MAIL_CONFIRM_BODY) ?>",
                mail_recovery: "<?php echo nc_text_for_js(NETCAT_MODULE_AUTH_ADMIN_DEF_MAIL_PASSWORDRECOVERY_BODY) ?>",
                mail_notify_admin: "<?php echo nc_text_for_js(NETCAT_MODULE_AUTH_ADMIN_DEF_MAIL_ADMIN_NOTIFY_BODY) ?>"};
            subject = {mail_confirm: "<?php echo nc_text_for_js(NETCAT_MODULE_AUTH_ADMIN_DEF_MAIL_CONFIRM_SUBJECT) ?>",
                mail_recovery: "<?php echo nc_text_for_js(NETCAT_MODULE_AUTH_ADMIN_DEF_MAIL_PASSWORDRECOVERY_SUBJECT) ?>",
                mail_notify_admin: "<?php echo nc_text_for_js(NETCAT_MODULE_AUTH_ADMIN_DEF_MAIL_ADMIN_NOTIFY_SUBJECT) ?>"};

            function recovery( name, confirmed) {
                if ( !confirmed ) {
                    if ( confirm(ncLang.WarnAuthMail) ) {
                        recovery( name, 1 );
                    }
                    return false;
                }
                jQuery("#" + name +"_body").val( body[name]);
                jQuery("#" + name +"_subject").val( subject[name]);
                //jQuery("#" + name + "_is_html").attr('checked', 'checked');
            }
        </script>

        <?php
        echo "<form action='admin.php' method='post'>
    <fieldset><legend>" . NETCAT_MODULE_AUTH_REG_CONFIRM . "</legend>
    <div style='float:left'>" . NETCAT_MODULE_AUTH_ADMIN_MAIL_SUBJECT . ":</div>
		<div style='float:right'><a href='#' onclick='recovery(\"mail_confirm\", 0); return false;'>" . NETCAT_MODULE_AUTH_RESTORE_DEF . "</a></div><br clear='all'>
		<input id='mail_confirm_subject' name='mail_confirm_subject'  type='text' style='width:100%; margin-top:5px;' value='" . htmlspecialchars($settings['mail_confirm_subject'], ENT_QUOTES) . "'>
    " . nc_admin_textarea(NETCAT_MODULE_AUTH_ADMIN_MAIL_BODY, 'mail_confirm_body', $settings, 1, 0, 'height:10em;') . "
		" . nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_MAIL_HTML, 'mail_confirm_is_html', $settings) . "
		</fieldset>

    <fieldset><legend>" . NETCAT_MODULE_AUTH_RECOVERY . "</legend>
    <div style='float:left'>" . NETCAT_MODULE_AUTH_ADMIN_MAIL_SUBJECT . ":</div>
		<div style='float:right'><a href='#' onclick='recovery(\"mail_recovery\", 0); return false;'>" . NETCAT_MODULE_AUTH_RESTORE_DEF . "</a></div><br clear='all'>
		<input id='mail_recovery_subject' name='mail_recovery_subject'  type='text' style='width:100%; margin-top:5px;' value='" . htmlspecialchars($settings['mail_recovery_subject'], ENT_QUOTES) . "'>
    " . nc_admin_textarea(NETCAT_MODULE_AUTH_ADMIN_MAIL_BODY, 'mail_recovery_body', $settings, 1, 0, 'height:10em;') . "
		" . nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_MAIL_HTML, 'mail_recovery_is_html', $settings) . "
		</fieldset>

    <fieldset><legend>" . NETCAT_MODULE_AUTH_ADMIN_MAIL_NOTIFY . "</legend>
    <div style='float:left'>" . NETCAT_MODULE_AUTH_ADMIN_MAIL_SUBJECT . ":</div>
		<div style='float:right'><a href='#' onclick='recovery(\"mail_notify_admin\", 0); return false;'>" . NETCAT_MODULE_AUTH_RESTORE_DEF . "</a></div><br clear='all'>
		<input id='mail_notify_admin_subject' name='mail_notify_admin_subject'  type='text' style='width:100%; margin-top:5px;' value='" . htmlspecialchars($settings['mail_notify_admin_subject'], ENT_QUOTES) . "'>
    " . nc_admin_textarea(NETCAT_MODULE_AUTH_ADMIN_MAIL_BODY, 'mail_notify_admin_body', $settings, 1, 0, 'height:10em;') . "
		" . nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_MAIL_HTML, 'mail_notify_admin_is_html', $settings) . "
		</fieldset>


	  " . $this->core->token->get_input() . "
    <input type='hidden' name='view' value='mail' />
    <input type='hidden' name='act' value='save' />
	  </form>";
        echo nc_admin_js_resize();

        $UI_CONFIG->actionButtons[] =
                array("id" => "submit",
                        "caption" => NETCAT_MODERATION_BUTTON_CHANGE,
                        "action" => "mainView.submitIframeForm('adminForm')");
    }

    public function mail_save() {
        $nc_core = nc_Core::get_object();
        $params = array('mail_confirm_subject', 'mail_confirm_body', 'mail_confirm_is_html',
                'mail_recovery_subject', 'mail_recovery_body', 'mail_recovery_is_html',
                'mail_notify_admin_subject', 'mail_notify_admin_body', 'mail_notify_admin_is_html');
        foreach ($params as $v) {
            $nc_core->set_settings($v, $nc_core->input->fetch_get_post($v), 'auth');
        }
    }

    public function system_show() {
        global $UI_CONFIG;
        $UI_CONFIG->add_settings_toolbar();
        $settings = nc_Core::get_object()->get_settings('', 'auth');
        echo "
    <style>
      select {width: 98%; margin-left: 10px; }
      .nc_t { width:70%}
      .nc_t .f { width:30%}
      .nc_t .l { width:70%}
    </style>
    <form action='admin.php' method='post'>
    <fieldset><legend>" . NETCAT_MODULE_AUTH_ADMIN_SYSTEM_COMPONENTS_SUBS . "</legend>
    <table class='nc_t'>
    <tr>" . nc_admin_select_component('<td class="f">' . NETCAT_MODULE_AUTH_ADMIN_SYSTEM_COMPONENT_FRIENDS . '</td><td class="l">', 'friend_class_id', $settings) . "</td></tr>
    <tr>" . nc_admin_select_component('<td>' . NETCAT_MODULE_AUTH_ADMIN_SYSTEM_COMPONENT_PM . '</td><td>', 'pm_class_id', $settings) . "</td></tr>
    <tr>" . nc_admin_select_component('<td>' . NETCAT_MODULE_AUTH_ADMIN_SYSTEM_COMPONENT_PA . '</td><td>', 'pa_class_id', $settings) . "</td></tr>
    <tr>" . nc_admin_select_field('User', '<td>' . NETCAT_MODULE_AUTH_ADMIN_SYSTEM_FIELD_PA . '</td><td>', 'pa_field', $settings) . "</td></tr>
    <tr>" . nc_admin_select_subdivision('<td>' . NETCAT_MODULE_AUTH_ADMIN_SYSTEM_SUB_MATERIALS . '</td><td>', 'materials_sub_id', $settings) . "</td></tr>
    <tr>" . nc_admin_input_in_text('<td>' . NETCAT_MODULE_AUTH_ADMIN_SYSTEM_SUB_MODIFY . '</td><td style="padding-left: 10px;">%input</td>', 'modify_sub', $settings, 0, 'width: 100%;') . "</tr>
    <tr>" . nc_admin_input_in_text('<td>' . NETCAT_MODULE_AUTH_ADMIN_SYSTEM_CC_USER_LIST . '</td><td style="padding-left: 10px;">%input</td>', 'user_list_cc', $settings, 0, 'width: 100%;') . "</tr>
    </table>
    </fieldset>

    <fieldset><legend>" . NETCAT_MODULE_AUTH_ADMIN_SYSTEM_PSEUDO . "</legend>
    " . nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_SYSTEM_PSEUDO_ALLOW, 'pseudo_enabled', $settings) . "
    " . nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_SYSTEM_PSEUDO_CHECK_IP, 'pseudo_check_ip', $settings) . "
    <table>
    <tr>" . nc_admin_select_usergroup('<td>' . NETCAT_MODULE_AUTH_ADMIN_SYSTEM_PSEUDO_GROUP . '</td><td>', 'pseudo_group', $settings['pseudo_group']) . "</td></tr>
    <tr>" . nc_admin_select_field('User', '<td>' . NETCAT_MODULE_AUTH_ADMIN_SYSTEM_PSEUDO_FIELD . '</td><td>', 'pseudo_field', $settings) . "</td></tr>
    </table>
    </fieldset>

    <fieldset><legend>" . NETCAT_MODULE_AUTH_ADMIN_SYSTEM_HASH . "</legend>
    " . nc_admin_checkbox(NETCAT_MODULE_AUTH_ADMIN_SYSTEM_HASH_DELETE, 'hash_delete', $settings) . "
    " . nc_admin_input_in_text(NETCAT_MODULE_AUTH_ADMIN_SYSTEM_HASH_EXPIRE, 'hash_expire', $settings, 4) . "<br/>
    " . nc_admin_input_in_text(NETCAT_MODULE_AUTH_ADMIN_SYSTEM_HASH_DISABLED_SUBS, 'hash_disabled_subs', $settings, 12) . "
    </fieldset>

    <fieldset><legend>" . NETCAT_MODULE_AUTH_ADMIN_SYSTEM_OTHER . "</legend>
    " . nc_admin_input_in_text(NETCAT_MODULE_AUTH_ADMIN_SYSTEM_OTHER_ONLINE, 'online_timeleft', $settings, 4) . "<br/>
    " . nc_admin_input_in_text(NETCAT_MODULE_AUTH_ADMIN_SYSTEM_OTHER_IP, 'ip_check_level', $settings, 4) . "
    </fieldset>

    " . $this->core->token->get_input() . "
    <input type='hidden' name='view' value='system' />
    <input type='hidden' name='act' value='save' />
    </form>";

        $UI_CONFIG->actionButtons[] =
                array("id" => "submit",
                        "caption" => NETCAT_MODERATION_BUTTON_CHANGE,
                        "action" => "mainView.submitIframeForm('adminForm')");
    }

    public function system_save() {
        $nc_core = nc_Core::get_object();
        $params = array('friend_class_id', 'pm_class_id', 'pa_class_id', 'pa_field', 'materials_sub_id', 'modify_sub', 'user_list_cc',
                'pseudo_enabled', 'pseudo_check_ip', 'pseudo_group', 'pseudo_field',
                'hash_delete', 'hash_expire', 'hash_disabled_subs',
                'online_timeleft', 'ip_check_level');
        foreach ($params as $v) {
            $nc_core->set_settings($v, $nc_core->input->fetch_get_post($v), 'auth');
        }

        $materials_sub_id = $nc_core->input->fetch_get_post('materials_sub_id');
        $url = $nc_core->subdivision->get_by_id($materials_sub_id, 'Hidden_URL');
        $nc_core->set_settings('materials_url', $url, 'auth');
    }

}