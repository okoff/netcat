<?php
/* $Id: CheckUserFunctions.inc.php 8329 2012-11-02 11:31:02Z vadim $ */

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -3)).( strstr(__FILE__, "/") ? "/" : "\\" );
@include_once ($NETCAT_FOLDER."vars.inc.php");
global $ADMIN_FOLDER;
@include_once ($ADMIN_FOLDERR."admin.inc.php");

/**
 * Получение языков доступных в системе
 * @return array Element[English.php] = English
 */
function Language_Show() {
    global $ADMIN_FOLDER;
    if (($handle = opendir($ADMIN_FOLDER."lang"))) {
        $lang = array();
        while (false !== ($file = readdir($handle))) {
            if (strlen($file) > 4 && !strpos($file, '_')) {
                $name = explode(".", $file);
                $lang[$file] = $name[0];
            }
        }
        closedir($handle);
        return($lang);
    } else {
        return(false);
    }
}

function Unauthorize() {
    global $HTTP_HOST, $EDIT_DOMAIN, $AUTHORIZATION_TYPE;
    global $PHP_AUTH_USER, $PHP_AUTH_PW, $_SERVER, $PHP_AUTH_LANG, $PHP_AUTH_SID;
    global $PHPSESSID, $MODULE_VARS;
    global $db;

    if ($sname = session_name()) global $$sname;

    switch (true) {
        // cookie
        case $AUTHORIZATION_TYPE == 'cookie':
            $EditDomainHost = substr($EDIT_DOMAIN, 0, nc_strlen($EDIT_DOMAIN) - nc_strlen(strchr($EDIT_DOMAIN, "/")));
            $db->query("DELETE FROM `Session` WHERE `Session_ID` = '".$PHP_AUTH_SID."' OR `SessionTime` < '".time()."'");
            // unset back-end and front-end cookies
            $cookie_domain = $HTTP_HOST;
            setcookie("PHP_AUTH_SID", NULL, NULL, "/", $cookie_domain);
            setcookie("PHP_AUTH_LANG", NULL, NULL, "/", $cookie_domain);
            $cookie_domain = str_replace("www.", "", $HTTP_HOST);
            setcookie("PHP_AUTH_SID", NULL, NULL, "/", $cookie_domain);
            setcookie("PHP_AUTH_LANG", NULL, NULL, "/", $cookie_domain);
            break;
        // http
        case $AUTHORIZATION_TYPE == 'http':
            unset($_SERVER['PHP_AUTH_USER']);
            unset($_SERVER['PHP_AUTH_PW']);
            unset($_SERVER['HTTP_AUTHORIZATION']);
            break;
        // session
        case $AUTHORIZATION_TYPE == 'session':
            if ($$sname != "") {
                $db->query("DELETE FROM Session WHERE Session_ID = '".$$sname."' OR SessionTime < ".time());
                $$sname = $_POST['$sname'];
                $$sname = $_GET['$sname'];
            }
            unset($_SESSION['User']);
            session_destroy();
            break;
    }
}

function LoginFormHeader() {
    global $ADMIN_FOLDER, $ADMIN_TEMPLATE;
    $nc_core = nc_Core::get_object();
    $lang = $nc_core->lang->detect_lang();
    require_once($ADMIN_FOLDER."lang/".$lang.".php");
    
    ?><!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional'>
<html>
  <head>
  <meta http-equiv='content-type' content='text/html; charset=<?= $nc_core->NC_CHARSET ?>'/>
  <link type='text/css' rel='Stylesheet' href='<?= $nc_core->ADMIN_TEMPLATE ?>css/login.css'>
  <link type='text/css' rel='Stylesheet' href='<?= $nc_core->ADMIN_TEMPLATE ?>css/style.css'>
  <?= nc_js(); ?>
  <title><?= CONTROL_AUTH_HTML_CMS . " NetCat" ?></title>
  </head>
  <body>
  	<div class='login_wrap'>
    <div class='top_line'>
        <div class='logo'><a href='#'><img src='<?= $nc_core->ADMIN_TEMPLATE ?>img/logo.png' alt='NetCat <?= BEGINHTML_VERSION . " " . $VERSION_ID . " " . $SYSTEM_NAME ?>' /></a></div>
        <div class='top_text'><?= CONTROL_AUTH_HTML_CMS ?> <a href="http://www.netcat.ru">NetCat</a></div>
    </div>
    <div class='content' align='center'>
	<?
}

function LoginFormFooter() {
?>
    </div>
    </div> <!-- / .login_wrap -->
    <div class='bottom_line'>
        <div class='left'>&copy; 1999&#8212;<?=date("Y")?> 
        <a href='http://www.netcat.ru'><?=ENDHTML_NETCAT?></a>
        </div>
    </div>
    </body>
    </html>
<?php
}

/**
 * Вывод формы авторизации пользователя
 */
function LoginForm() {
    global $REQUEST_URI, $AUTH_USER, $ADMIN_LANGUAGE, $ADMIN_TEMPLATE, $AUTH_PW;
    global $posting, $USER_LANG, $ADMIN_AUTHTYPE, $AUTHORIZATION_TYPE;
    global $SUB_FOLDER, $HTTP_ROOT_PATH;
    global $nc_core;

    if ($_REQUEST['AUTH_USER'] || $_REQUEST['AUTH_PW']) {
        $textinfo = CONTROL_AUTH_LOGIN_OR_PASSWORD_INCORRECT;
    }

    $m_auth = $nc_core->modules->get_by_keyword('auth'); // есть модуль ЛК
    $need_captcha = 0; // нужна ли каптча
    $login_en = 1; // доступна авторизация по логину
    $token_en = 0; // доступна авторизация по токену
    if ($m_auth) {
        $nc_auth = nc_auth::get_object();
        $login_en = $nc_core->get_settings('authtype_admin', 'auth') & NC_AUTHTYPE_LOGIN;
        $token_en = $nc_auth->token_enabled();
        $nc_auth_token = new nc_auth_token();
        $nc_token_rand = $nc_auth_token->get_random_256();
        $_SESSION['nc_token_rand'] = $nc_token_rand;
        $need_captcha = $nc_auth->need_captcha();
        if ($nc_auth->is_invalid_captcha()) {
            $textinfo = NETCAT_MODULE_CAPTCHA_WRONG_CODE_SMALL;
        }
    }

    $lang = Language_Show();
    $sellang = $_COOKIE['PHP_AUTH_LANG'] ? $_COOKIE['PHP_AUTH_LANG'] : $ADMIN_LANGUAGE;

    // селект с языком
    $lang_select = "	<select name='NEW_AUTH_LANG' style='width:100%'>";
    foreach ($lang AS $val) {
        $lang_select .= "<option value='".$val."'".($val == $sellang ? " selected" : "").">".$val."</option>\n";
    }
    $lang_select .="  </select>";

    // сохранить логин пароль
    $loginsave = '';
    if ($ADMIN_AUTHTYPE == 'manual' && $AUTHORIZATION_TYPE == 'cookie') {
        $loginsave = nc_admin_checkbox_simple('loginsave', '', CONTROL_AUTH_HTML_SAVELOGIN);
    }
?>
    <noscript><div style="font-weight: bold;"><?=CONTROL_AUTH_JS_REQUIRED ?></div></noscript>

<?php if ($m_auth) : ?>
    <script type='text/javascript' src='<?=$SUB_FOLDER.$HTTP_ROOT_PATH.'modules/auth/auth.js' ?>'></script>
<?php endif; ?>

<script type='text/javascript'>
    function authCheckFields () {
        var authForm = document.getElementById('AUTH_FORM');
        var login = document.getElementsByName('AUTH_USER');
        var pass = document.getElementsByName('AUTH_PW');

        switch (true) {
            case (login.value == '' && pass.value == ''):
                alert('<?=CONTROL_AUTH_FIELDS_NOT_EMPTY ?>');
                return false;
                break;
            case (login.value == ''):
                alert('<?=CONTROL_AUTH_LOGIN_NOT_EMPTY ?>');
                return false;
                break;
            default:
            	return true;
                //authForm.submit();
            }
        }
        $nc(function() {
			$nc('#AUTH_FORM').submit( function() {
				var login = $nc("input[name = 'AUTH_USER']").val();
				var pass = $nc("input[name = 'AUTH_PW']").val();
				if (!login  && !pass) {
					alert('<?=CONTROL_AUTH_FIELDS_NOT_EMPTY ?>');
					return false;
				}
				if (!login) {
					alert('<?=CONTROL_AUTH_LOGIN_NOT_EMPTY ?>');
					return false;						
				}
				return true;
			});
			
			function place_footer() {
				var footer = $nc('.bottom_line');
				
				var form  = $nc('.content');
				var body_height = $nc(document.body).height();
				
				var form_bottom = form.offset().top + form.height();
				
				footer.css({top:null, bottom:null});
				
				if (form_bottom + footer.height() > body_height) {
					footer.css({top:form_bottom+'px'});
				} else {
					footer.css({bottom:'0px'});
				}
			}
			
			$nc(window).resize(place_footer);
			
			place_footer();
        });
</script>
<form action='<?=$REQUEST_URI ?>' method='post' name='AUTH_FORM' id='AUTH_FORM'>
    <input type='hidden' name='AuthPhase' value='1'>

    <table border='0' cellpadding='4' cellspacing='0' id="classical" style="display:none;">
        <tr>
            <td></td>
            <td class="error">
        <?=$textinfo ?>
            </td>
        </tr>
        <tr>
            <td><?=CONTROL_AUTH_HTML_LOGIN ?></td>
            <td><?=nc_admin_input_simple('AUTH_USER', stripcslashes($AUTH_USER), 32, '', "id='AUTH_USER' maxlength='255'") ?></td>
        </tr>
        <tr>
            <td><?=CONTROL_AUTH_HTML_PASSWORD ?></td>
            <td><?=nc_admin_input_password('AUTH_PW', stripcslashes($AUTH_PW), 32, "", "maxlength='255'") ?></td>
        </tr>
        <tr>
            <td><?=CONTROL_AUTH_HTML_LANG ?></td>
            <td><?=$lang_select ?></td>
        </tr>
<?php if ($need_captcha) : ?>
            <tr>
                <td></td>
                <td class="captcha"><?=nc_captcha_formfield() ?></td>
            </tr>
            <tr>
                <td><?=NETCAT_MODERATION_CAPTCHA_SMALL ?></td>
                        <td><?=nc_admin_input_simple('nc_captcha_code', '', 32, "maxlength='255'") ?></td>
                    </tr>
<?php endif; ?>
                    <tr>
                        <td rowspan='2'><?=$icon ?></td>
                        <td><?=$loginsave ?></td>
                    </tr>
                    <tr>
                        <td>
                            <input type='submit' class='login_submit' value='<?=CONTROL_AUTH_HTML_AUTH ?>' title='<?=CONTROL_AUTH_HTML_AUTH ?>'/>
<?php
                    if ($posting && $REQUEST_URI != $REQUESTED_FROM) {
                        echo "<br/><a href='".$REQUESTED_FROM."' class='relogin'>".CONTROL_AUTH_HTML_BACK."</a>";
                    }
?>
                </td>
            </tr>
        </table>

        <!-- форма авторизация по токену -->
<? /* TEST $token_en=1; */if ($token_en) { ?>
            <table border='0' cellpadding='4' cellspacing='0' id="token" style="display:none;">
                <tr>
                    <td colspan="2">
                        <div id='tokeninfo' class="error"></div>
                    </td>
                </tr>
                <tr><td colspan="2">
                            <div id='nc_token_plugin_wrapper'></div>
                            <script>
                                $nc("#nc_token_plugin_wrapper").append("<object id='nc_token_plugin' type='application/x-rutoken' width='0' height='0'></object>");
                            </script>
                        <input type='hidden' value='' id='nc_token_signature'  name='nc_token_signature'/>
                    </td></tr>
                <tr>
                    <td><?=CONTROL_AUTH_HTML_LOGIN ?></td>
                                <td><select  name='nc_token_login' id='nc_token_login'></select></td>
                            </tr>
                            <tr>
                                <td><?=CONTROL_AUTH_HTML_LANG ?></td>
                                <td><?=$lang_select ?></td>
                            </tr>
                            <tr>
                                <td>
                                </td>
                                <td colspan="2">
                                    <input type='submit'  class="login_submit" value='<?=CONTROL_AUTH_HTML_AUTH ?>' title='<?=CONTROL_AUTH_HTML_AUTH ?>' onclick='nc_token_sign(); return false;'>
                                </td>
                            </tr>
                        </table>
<? } ?>

                       <div id='menu' class='menu'></div>
                    </form>

                    <script type='text/javascript'>
                            var authForm = document.getElementById('AUTH_FORM');
                            // перенаправлять туда, куда пользователь хотел зайти
                            authForm.action += window.location.hash;

                            function show_token () {
<?php if ($login_en && $token_en): ?>
                                  $nc("#menu").html("<a href='#' onclick='show_classical(); return false;'><?=NETCAT_AUTH_TYPE_LOGINPASSWORD ?></a>");
<?php endif; ?>

                                  $nc("#classical").hide();
                                  $nc("#token").show();
                                  $nc("#classical :input").attr('disabled', true);
                                  $nc("#token :input").removeAttr('disabled');
                                  $nc('#tokeninfo').hide();
                                  if ( !nc_token_obj.load() ) {
                                      $nc('#tokeninfo').html("<?=CONTROL_AUTH_USB_TOKEN_NOT_INSERTED ?>");
                                      $nc('#tokeninfo').show();
                                  }
                              }

                              function show_classical () {
<?php if ($login_en && $token_en): ?>
                                      $nc("#menu").html("<a href='#' onclick='show_token(); return false;' ><?=NETCAT_AUTH_TYPE_TOKEN ?></a>");
<?php endif; ?>

                                      $nc("#classical").show();
                                      $nc("#token").hide();
                                      $nc("#classical :input").removeAttr('disabled');
                                      $nc("#token :input").attr('disabled', true);
                                  }

<?php if ($m_auth)
                                        echo "nc_token_obj = new nc_auth_token ( {'randnum' : '".$nc_token_rand."'}); "; ?>

                                function nc_token_sign ( ) {
                                    $nc('#tokeninfot').hide();
                                    err_text = { 1: "<?=CONTROL_AUTH_TOKEN_PLUGIN_DONT_INSTALL ?>", 2: "<?=CONTROL_AUTH_USB_TOKEN_NOT_INSERTED ?>",
                                        3: "<?=CONTROL_AUTH_PIN_INCORRECT ?>", 4: "<?=CONTROL_AUTH_KEYPAIR_INCORRECT ?>"};

                                    if ( (err_num = nc_token_obj.sign()) ) {
                                        $nc('#tokeninfo').html(err_text[err_num]);
                                        $nc('#tokeninfo').show();
                                    }
                                }
<?= $login_en ? "show_classical();" : "show_token();"; ?>

                            </script>
<?php
                            }

                            function Refuse() {
                                global $nc_core, $AUTH_TYPE, $admin_mode, $nc_auth;
                                // AJAX call
                                if ($_POST["NC_HTTP_REQUEST"] || NC_ADMIN_ASK_PASSWORD === false) {
                                    // issue strange header (actually not RFC2616-compliant) and die
                                    header($_SERVER['SERVER_PROTOCOL']." 401 Authorization Required");
                                    exit;
                                }

                                switch ($nc_core->AUTHORIZATION_TYPE) {
                                    case 'cookie':
                                    case 'session':
                                        if (!$admin_mode) {
                                            if (is_object($nc_auth))
                                                    $nc_auth->login_form();
                                        }
                                        else {
                                            LoginFormHeader();
                                            LoginForm ();
                                            LoginFormFooter();
                                        }
                                        break;

                                    default :
                                        # по дефолту авторизация 'http'
                                        Header("WWW-authenticate:  basic  realm=Enter your login and password");
                                        Header($_SERVER['SERVER_PROTOCOL'].' 401  Unauthorized');
                                        LoginFormHeader();
                                        print CONTROL_AUTH_MSG_MUSTAUTH;
                                        LoginFormFooter();
                                }

                                exit;
                            }

                            /**
                             * Функция для авторизации
                             *
                             * @param int $required_id = 0, если не равен 0, то выполнится авторизация пользователя с id  = required_id
                             * @param str фаза авторизации: attempt - попытка продлить авторизацию, authorize - авторизация
                             * @param str вариант авторизации: NC_AUTHTYPE_LOGIN, NC_AUTHTYPE_HASH, ...
                             * @param bool - авторизация в админку
                             * @param bool создавать сессию
                             *
                             * @return int  идентификатор авторизированного пользователя
                             */
                            function Authorize($required_id = 0, $auth_phase = 'attempt', $auth_variant = NC_AUTHTYPE_LOGIN, $isInsideAdmin = 0, $create_session = 1) {
                                global $nc_core, $perm, $AUTH_USER_ID, $AuthPhase;

                                if (is_object($perm)) return $AUTH_USER_ID;

                                // для совместимости со старыми версиями
                                if ($nc_core->modules->get_by_keyword('auth') && !class_exists('nc_auth')) {
                                    $nc_core->modules->load_env();
                                }

                                if ($required_id) {
                                    return $nc_core->user->authorize_by_id($required_id, $auth_variant, $isInsideAdmin, $create_session);
                                }

                                if ($AuthPhase || $auth_phase == 'authorize') {
                                    global $AUTH_USER, $AUTH_PW;
                                    return $nc_core->user->authorize_by_pass($AUTH_USER, $AUTH_PW);
                                }

                                return $nc_core->user->attempt_to_authorize();
                            }

                            function CheckUserRights($SubClassID, $action, $posting) {
                                global $db;
                                global $perm;
                                # значения action
                                #   1 - read
                                #   2 - add
                                #   4 - change
                                #   8 - subscribe
                                #  16 - moderate

                                if (!$perm) Authorize();
                                if (!is_object($perm)) return 0;

                                if ($perm->isSupervisor()) {
                                    return 1;
                                }


                                switch ($action) {
                                    case "read":
                                        $mask = MASK_READ | MASK_MODERATE; //moderator can read all
                                        break;
                                    case "comment":
                                        $mask = MASK_COMMENT;
                                        break;
                                    case "add":
                                        $mask = MASK_ADD;
                                        break;
                                    case "change":
                                        global $delete, $checked; // нужно точно узнать, какое изменение происходит
                                        $mask = MASK_MODERATE; // в любом случае модератор может все
                                        switch (true) {
                                            case isset($delete):
                                                $mask |= MASK_DELETE;
                                                break;
                                            case isset($checked):
                                                $mask |= MASK_CHECKED;
                                                break;
                                            default:
                                                $mask |= MASK_EDIT;
                                        }
                                        break;
                                    case "subscribe":
                                        $mask = MASK_SUBSCRIBE;
                                        break;
                                    case "moderate":
                                        $mask = MASK_MODERATE;
                                        break;
                                    default:
                                        $mask = MASK_READ | MASK_MODERATE;
                                        break;
                                }

                                if ($perm->isGuest()) { // право гость
                                    //дает просматривать без записи в БД
                                    return ($posting == 0 || $mask == 1);
                                }


                                #сообственно, проверка прав
                                //случай, когда в разделе есть компонент
                                if ($SubClassID)
                                        return $perm->isSubClass($SubClassID, $mask, true);

                                // Возможен случай, когда в разделе нет компонента, в этом случае надо проеврить
                                // на доступ к разделу.
                                global $current_sub;
                                return $perm->isSubdivision($current_sub['Subdivision_ID'], $mask);
                            }
