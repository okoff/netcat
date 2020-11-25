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
	<title>Клиенты без покупки. Добавление</title>
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
	echo printMenu();
	$sql="SELECT * FROM User_nobuy WHERE created LIKE '".date("Y-m-d")."%'";
	//echo $sql;
	if ($result=mysql_query($sql)) {
		if ($row = mysql_fetch_array($result)) { 
			$sql="UPDATE User_nobuy SET qty=qty+1 WHERE id={$row['id']}";
			echo "<p>Клиенты сегодня уже были. Этот ".($row['qty']+1)."</p>";
			if (!mysql_query($sql)) {
				die($sql."Ошибка: ".mysql_error());
			}
			 
		} else {
			echo "<p>Первый клиент.</p>";
			$sql="INSERT INTO User_nobuy (created, qty) VALUES ('".date("Y-m-d H:i:s")."', 1)";
			if (!mysql_query($sql)) {
				die($sql."Ошибка: ".mysql_error());
			}
		}
	}
	echo "<p><a href='/netcat/modules/netshop/interface/retail-list.php'>Вернуться в список продаж</a></p>";
	/*if ($incoming['request_method']=="post") {
		$sql="SELECT * FROM User WHERE Email LIKE '{$incoming['email']}' ".(($incoming['discountcard']) ? " OR discountcard LIKE '{$incoming['discountcard']}'" : "");
		
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
	}*/
} else {
	echo "<p>У вас нет прав для просмотра этой страницы</p><p><a href='/netcat/modules/netshop/interface/login.php'>Вход</a></p>";
}
	
?>
</body>
</html>
<?php

mysql_close($con);
?>
