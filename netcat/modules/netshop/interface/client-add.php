<?php
// 12.11.2013 Elen
// создание клиента из заказа 
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
<h1>Перенос заказа <? echo $incoming['oid']; ?> к новому клиенту</h1>
<?php
if ((isset($_SESSION['nc_token_rand'])) || ((isset($_SESSION['insideadmin'])) && ($_SESSION['insideadmin']==1))) {
	
	if (isset($incoming['oid'])) {
		$sql="SELECT * FROM Message51 WHERE Message_ID=".intval($incoming['oid']);
		// check if user exists 
		if ($result=mysql_query($sql)) {
			if ($row = mysql_fetch_array($result)) { 
				$phone=$row['Phone'];
				$phone=str_replace("+7", "", $phone);
				$phone=str_replace(" ", "", $phone);
				if ($incoming['request_method']=="post") {
					//print_r($incoming);
					if ($incoming['uid']!=0) {
						$sql="UPDATE Message51 SET User_ID={$incoming['uid']} WHERE Message_ID={$incoming['oid']}";
						if (mysql_query($sql)) {
							$html.="Заказ перенесен<br>";
						} else {
							die($sql."Ошибка: ".mysql_error());
						}
					} else {
						// create new user
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
					}
					
				} else {
					$html.="<form name='frm1' id='frm1' action='/netcat/modules/netshop/interface/client-add.php' method='post'>
					<input type='hidden' name='oid' value='{$incoming['oid']}'>
					<table cellpadding='2' cellspacing='0' border='1'>";
					
					$name=explode(" ",$row['ContactName']);
					//echo $phone."<br>";
					$sql="SELECT * FROM User WHERE Login LIKE '%{$row['ContactName']}%' OR Email LIKE '{$phone}@russian-knife.ru' OR Email LIKE '{$row['Email']}' OR phone LIKE '%{$phone}%' ORDER BY User_ID ASC";
					//echo $sql;
					if ($result1=mysql_query($sql)) {
						while ($row1 = mysql_fetch_array($result1)) { 
							//($row1[User_ID]!="") ? $uid=$row1['User_ID'] : $uid=0;
							$html.="<tr><td><input type='radio' value='{$row1['User_ID']}' name='uid'></td>
								<td><a href='/netcat/modules/netshop/interface/clients.php?action=history&cid={$row1['User_ID']}'>{$row1['Login']}</a></td>
								<td>{$row1['Email']}</td><td>{$row1['phone']}</td>
								<td>Заказов: ".getOrderCount($row1['User_ID'])."</td>
								</tr>";
						}
					}
					$html.="<tr><td><input type='radio' value='0' name='uid'></td><td colspan='4'>Новый</td></tr>";
					$html.="</table>";
					$html.="<input type='submit' value='Привязать'>
					</form>";
				}
			}
		}
	}

	//echo printMenu();
	echo $html; 
	echo "<p><a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$incoming['oid']}'>Вернуться в редактирование заказа</a></p>";
} else {
	echo "<p>У вас нет прав для просмотра этой страницы</p><p><a href='/netcat/modules/netshop/interface/login.php'>Вход</a></p>";
}
?>
</body>
</html>
<?php

mysql_close($con);
?>
