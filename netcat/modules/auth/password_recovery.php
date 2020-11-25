<?php

/* $Id: password_recovery.php 5007 2011-07-21 10:27:33Z denis $ */



$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)) . ( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER . "vars.inc.php");
require_once ($ROOT_FOLDER . "connect_io.php");

$url = $parsed_url['path'] . ($parsed_url['query'] ? '?' . $parsed_url['query'] : '');
$url = str_replace($nc_core->SUB_FOLDER, '', $url);

$current_catalogue = $nc_core->catalogue->get_by_host_name($_SERVER['HTTP_HOST']);
$SQL = "
    SELECT sub.Catalogue_ID,
           sub.Subdivision_ID
        FROM Subdivision AS sub
            WHERE sub.Catalogue_ID = " . +$current_catalogue['Catalogue_ID'] . "
              AND sub.ExternalURL LIKE '%" . $db->escape($url) . "%'";

list($catalogue, $sub) = $db->get_row($SQL, ARRAY_N);

if (!$sub && isset($_GET['sub']))
    $sub = (int) $_GET['sub'];
require_once($INCLUDE_FOLDER . "index.php");
require_once($ADMIN_FOLDER . "admin.inc.php");

if ($File_Mode) {
    $auth_view = new nc_module_view();
    $auth_view->load('auth', $nc_core->get_interface());
}

ob_start();

do {
    // check if user is a guest (deny password recovery then)
    if (is_object($perm) && $perm->isGuest()) {
        $warnText = NETCAT_MODERATION_ERROR_NORIGHT;
        if ($File_Mode) {
            echo output_auth_field('recovery_password_warn', $auth_view);
        } else {
            eval("echo \"" . $nc_core->get_settings('recovery_password_warn', 'auth') . "\";");
        }
        break;
    }

    // ��������������� �������������� ������ ���������
    if ($nc_core->get_settings('deny_recovery', 'auth')) {
        if ($File_Mode) {
            echo output_auth_field('recovery_password_deny', $auth_view);
        } else {
            eval("echo \"" . $nc_core->get_settings('recovery_password_deny', 'auth') . "\";");
        }        
        break;
    }

    // ����� ����� ��������� ������
    if (isset($_GET['uid']) && isset($_GET['ucc'])) {
        $uid = (int) $_GET['uid'];
        $confirm_code = $db->get_var("SELECT `RegistrationCode` FROM User WHERE User_ID = '" . $uid . "'");

        if ($confirm_code == md5($_GET['ucc'] . ';-$')) {
            echo $nc_auth->change_password_form();
        } else {
            $warnText = NETCAT_MODULE_AUTH_NEWPASS_ERROR;
            if ($File_Mode) {
                echo output_auth_field('recovery_password_warn', $auth_view);
            } else {
                eval("echo \"" . $nc_core->get_settings('recovery_password_warn', 'auth') . "\";");
            }
        }
        break;
    }

    $fromname = $system_env['SpamFromName'];
    $fromemail = $system_env['SpamFromEmail'];
    $EmailField = $system_env['UserEmailField'];

    // ��� ���� ��� Email
    if (!$EmailField) {
        $warnText = NETCAT_MODULE_AUTH_ERR_NOFIELDSET;
        if ($File_Mode) {
            echo output_auth_field('recovery_password_warn', $auth_view);
        } else {
            eval("echo \"" . $nc_core->get_settings('recovery_password_warn', 'auth') . "\";");
        }
        break;
    }

    $warnText = '';
    /**
     * ���� ������:
     * 1 - ���� �� ���������
     * 2 - ������������ �� ������
     */
    $nc_error_num = 0;
    $nc_err_text[1] = NETCAT_MODULE_AUTH_MSG_FILLFIELD;
    $nc_err_text[2] = NETCAT_MODULE_AUTH_ERR_NOUSERFOUND;
    // ��������
    if ($post) {
        $Login = $db->escape($Login);
        $Email = $db->escape($Email);
        if (!$Login && !$Email) {
            $nc_error_num = 1;
        } else {
            // ����� ������������
            $res = $db->get_row("SELECT `User_ID`, `" . $EmailField . "`, `" . $AUTHORIZE_BY . "`
                         FROM `User`
                         WHERE `Checked` = '1' AND ( 0
                         " . ( $Email ? "OR `" . $EmailField . "`='" . $Email . "'" : "") . "
                         " . ( $Login ? "OR `" . $AUTHORIZE_BY . "`='" . $Login . "'" : "") . "
                         )", ARRAY_N);
            if (!$res) {
                $nc_error_num = 2;
            } else {
                list($UserID, $UserEmail, $UserLogin) = $res;
            }
        }

        if ($nc_error_num)
            $post = 0;
    }

    // ����� ����� ����������
    if (!$post) {
        if ($nc_error_num) {
            $warnText = $nc_err_text[$nc_error_num];
            
            if ($File_Mode) {
                echo output_auth_field('recovery_password_warn', $auth_view);
            } else {
                eval("echo \"" . $nc_core->get_settings('recovery_password_warn', 'auth') . "\";");
            }
        }
        echo $nc_auth->recovery_password_form();
    } else {
        // old: sha1(uniqid(time() - rand()));
        $confirm_code = md5( sha1( $nc_core->token->seed() ) . $nc_core->get_settings('SecretKey') );
        
        $mail_info = $nc_auth->get_recovery_mail($UserID, $confirm_code);

        $db->query("UPDATE `User` SET `RegistrationCode` = '" . md5($confirm_code . ';-$') . "' WHERE `User_ID` = '" . $UserID . "'");

        $mailer = new CMIMEMail();
        $mailer->mailbody(strip_tags($mail_info['body']), $mail_info['html'] ? $mail_info['body'] : '');
		// �������� ������ � �����
        $mailer->send($UserEmail, $fromemail, $fromemail, $mail_info['subject'], $fromname);

        if ($File_Mode) {
            echo output_auth_field('recovery_password_after', $auth_view);
        } else {
            eval("echo \"" . $nc_core->get_settings('recovery_password_after', 'auth') . "\";");
        }
    }
} while (false);

$nc_result_msg = ob_get_clean();

if ($File_Mode) {
    require_once $INCLUDE_FOLDER . 'index_fs.inc.php';

    echo $template_header;
    echo $nc_result_msg;
    echo $template_footer;
} else {
    //eval("echo \"" . $template_header . "\";");
    //echo $nc_result_msg;
    //eval("echo \"" . $template_footer . "\";");
	if ($current_catalogue['Catalogue_ID']==1) {
		eval("echo \"".$template_header."\";");
		echo $nc_result_msg;
		eval("echo \"".$template_footer."\";");
	} elseif ($current_catalogue['Catalogue_ID']==2) {
		// get template 90
		$res = $nc_core->db->query("SELECT * FROM `Template` WHERE Template_ID=30" );
		$arr = $nc_core->db->last_result;
		if (!empty($arr)) {
			foreach ($arr as $row) {
				//echo $row->Header;
				eval("echo \"".$row->Header."\";");
			}
		}
		//eval("echo \"".$template_header."\";");
		echo "<div style='padding:15px;'><h1>��������� ������</h1><br>".$nc_result_msg."</div>";
		eval("echo \"".$template_footer."\";");
	} else {
	eval("echo \"".$template_header."\";");
		echo $nc_result_msg;
		eval("echo \"".$template_footer."\";");
	}
}

function output_auth_field($field, $auth_view) {
    $field_path = $auth_view->get_field_path($field);
    global $warnText;
    
    if (file_get_contents($field_path)) {
        ob_start();
        include($field_path);
        return ob_get_clean(); 
    }
    
    return '';
}
?>