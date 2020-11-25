<?php
// 21.11.2013 Elen
// создание нового клиента 
include_once ("../../../../vars.inc.php");
error_reporting(E_ALL);
session_start();

// ------------------------------------------------------------------------------------------------
include_once ("utils.php");

$incoming = parse_incoming();
$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
if (!$con) {
	die('Could not connect: ' . mysql_error());
}

mysql_select_db($MYSQL_DB_NAME, $con);
//mysql_set_charset("cp1251", $con);
mysql_set_charset("utf8", $con);

?>
<!DOCTYPE html>
<html>
<head>
	<title>Клиенты</title>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	</style>
</head>
<body>
	
<?php
if ((isset($_SESSION['nc_token_rand'])) || ((isset($_SESSION['insideadmin'])) && ($_SESSION['insideadmin']==1))) {
	//print_r($incoming);
	if ($incoming['request_method']=="post") {
		$sql="SELECT * FROM User WHERE Email LIKE '{$incoming['email']}' ".(($incoming['discountcard']) ? " OR discountcard LIKE '{$incoming['discountcard']}'" : "");
		if ($result=mysql_query($sql)) {
			while ($row = mysql_fetch_array($result)) { 
				$uid=$row['User_ID'];
				if ($uid) {
					$html.="<p>Клиент с такими данными уже есть:
					<a href='/netcat/modules/netshop/interface/clients.php?action=history&cid={$uid}'>{$row['Login']}</a>
					</p>";
				}
			}
		}
		if (!$uid) {
			$sql="INSERT INTO User (Email, Login,Password,PermissionGroup_ID, Checked, Language,Confirmed,Catalogue_ID,InsideAdminAccess, Created, discountcard, phone) 
					VALUES ('{$incoming['email']}','{$incoming['login']}','111',2,1,'Russian',1, 1,0,'".date("Y-m-d H:i:s")."','{$incoming['discountcard']}', '{$incoming['phone']}')";
			if (mysql_query($sql)) {
				$html.="Клиент создан<br>";
				$sql="SELECT User_ID FROM User WHERE Email LIKE '{$incoming['email']}' ORDER BY User_ID DESC LIMIT 1";
				if ($result1=mysql_query($sql)) {
					if ($row1 = mysql_fetch_array($result1)) { 
						$html.="<p><a href='/netcat/modules/netshop/interface/clients.php?action=history&cid={$row1['User_ID']}'>Перейти в карточку клиента</a></p>";
					}
				}
			} else {
				die($sql."Ошибка: ".mysql_error());
			}
		}
		/*$sql="SELECT * FROM Message51 WHERE Message_ID=".intval($incoming['oid']);
		// check if user exists 
		if ($result=mysql_query($sql)) {
			if ($row = mysql_fetch_array($result)) { 
				$phone=$row['Phone'];
				$phone=str_replace("+7", "8", $phone);
				$phone=str_replace(" ", "", $phone);
				//echo $phone."<br>";
				$sql="SELECT * FROM User WHERE Email LIKE '{$phone}@russian-knife.ru'";
				if ($result1=mysql_query($sql)) {
					if ($row1 = mysql_fetch_array($result1)) { 
						($row1[User_ID]!="") ? $uid=$row1['User_ID'] : $uid=0;
					}
				}
				//echo $uid."<br>";
				if ($uid==0) {
					$sql="INSERT INTO User (Email, Login,Password,PermissionGroup_ID, Checked, Language,Confirmed,Catalogue_ID,InsideAdminAccess, Created) 
					VALUES ('{$phone}@russian-knife.ru','{$row['ContactName']}','111',2,1,'Russian',1, 1,0,'".date("Y-m-d H:i:s")."')";
					if (mysql_query($sql)) {
						$html.="Клиент создан<br>";
						$sql="SELECT User_ID FROM User WHERE Email LIKE '{$phone}@russian-knife.ru' ORDER BY User_ID DESC LIMIT 1";
						if ($result1=mysql_query($sql)) {
							if ($row1 = mysql_fetch_array($result1)) { 
								$sql="UPDATE Message51 SET User_ID={$row1['User_ID']} WHERE Message_ID={$incoming['oid']}";
								if (mysql_query($sql)) {
									$html.="Заказ перенесен<br>";
								} else {
									die($sql."Ошибка: ".mysql_error());
								}
							}
						}
					} else {
						die($sql."Ошибка: ".mysql_error());
					}
				} else {
					$sql="UPDATE Message51 SET User_ID={$uid} WHERE Message_ID={$incoming['oid']}";
					if (mysql_query($sql)) {
						$html.="Заказ перенесен<br>";
					} else {
						die($sql."Ошибка: ".mysql_error());
					}
				}
			}
		}*/
	}

	echo printMenu();
	echo $html; 
?>
<h3>Добавление нового клиента</h3>
<form method="post" action="/netcat/modules/netshop/interface/client-addnew.php" name="frm1" id="frm1">
<table cellpadding="0" cellspacing="2" border="0">
	<tr><td>ФИО</td><td><input type="text" name="login" id="login" value="<?php echo ((isset($incoming['login'])) ? $incoming['login'] : ""); ?>"></td></tr>
	<tr><td>e-mail</td><td><input type="text" name="email" id="email" value="<?php echo ((isset($incoming['email'])) ? $incoming['email'] : ""); ?>"></td></tr>
	<tr><td>Дисконтная карта</td><td><input type="text" name="discountcard" id="discountcard" value="<?php echo ((isset($incoming['discountcard'])) ? $incoming['discountcard'] : ""); ?>"></td></tr>
	<tr><td>Телефон</td><td><input type="text" name="phone" id="phone" value="<?php echo ((isset($incoming['phone'])) ? $incoming['phone'] : ""); ?>"></td></tr>
	<tr><td colspan='2'><input type="submit" value="Сохранить"></td></tr>
</table>
</form>
<?php
} else {
	echo "<p>У вас нет прав для просмотра этой страницы</p><p><a href='/netcat/modules/netshop/interface/login.php'>Вход</a></p>";
}
?>
</body>
</html>
<?php

mysql_close($con);
?>
