<?php
// 12.11.2013 Elen
// поиск и просмотр списка заказов
include_once ("../../../../vars.inc.php");
session_start();

// ------------------------------------------------------------------------------------------------
include_once ("utils.php");
//$UploadDir=$_SERVER['DOCUMENT_ROOT'].'/netcat_files/postfiles/upload/';
//$UploadDirNarv=$_SERVER['DOCUMENT_ROOT'].'/netcat_files/postfiles/narv/';
//$DownloadDir=$_SERVER['DOCUMENT_ROOT'].'/netcat_files/postfiles/';
//$DownloadDirLink='/netcat_files/postfiles/';
$incoming = parse_incoming();
$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
if (!$con) {
	die('Could not connect: ' . mysql_error());
}

mysql_select_db($MYSQL_DB_NAME, $con);
//mysql_set_charset("cp1251", $con);
mysql_set_charset("utf8", $con);

if ($incoming['request_method']=="post") {
	if (isset($incoming['cid'])) {
		$sql="INSERT INTO User_comments (comment, user_id, created) 
			VALUES ('".addslashes($incoming['comment'])."', {$incoming['cid']}, '".date("Y-m-d H:i:s")."')";
		if (mysql_query($sql)) {
			$html.="<p>Информация о клиенте обновлена.</p>
			<p><a href='/netcat/modules/netshop/interface/clients.php?cid={$incoming['cid']}'>Перейти в карточку клиента</a></p>";
		} else {
			die($sql."Ошибка: ".mysql_error());
		}
	}
}
	//print_r($incoming);
	
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
	
	if (isset($incoming['cid'])) {
		$sql="SELECT * FROM User WHERE User_ID=".intval($incoming['cid']);
		if ($result=mysql_query($sql)) {
			$row = mysql_fetch_array($result); 
				//$res.="<option value='{$row['ShopOrderStatus_ID']}' ".(($row['ShopOrderStatus_ID']==$sel) ? "selected" : "").">{$row['ShopOrderStatus_Name']}</option>";
			//}
		}
	}

	//echo printMenu();
	echo $html; 
?>
	<h2>Добавить комментарий к клиенту <?php echo $row['Login']; ?></h2>
	<form action="/netcat/modules/netshop/interface/client-edit.php" method="post" name="frm1" id="frm1">
	<fieldset style='border:0;'>
	<input type="hidden" id="cid" name="cid" value="<?php echo $incoming['cid']; ?>">
	<input type="hidden" id="oid" name="oid" value="<?php echo $incoming['oid']; ?>">
	<table cellpadding='2' cellspacing='0' border='1'>
		<tr>
			<td align='right'>Комментарий:</td>
			<td><input type='text' style='width:600px;' value='' name='comment' id='comment'></td>
		</tr>
		<td colspan='2'><input type='submit' value='Сохранить'></td>
		</tr>
	</table>
	
	</fieldset>
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
