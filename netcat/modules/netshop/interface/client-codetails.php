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
$form=1;
if ($incoming['request_method']=="post") {
	if (isset($incoming['cid'])) {
		if ($incoming["company"]!=0) {
			$sql="UPDATE User_codetails SET name='".htmlspecialchars($incoming['name'])."',
									inn_kpp='".htmlspecialchars($incoming['inn_kpp'])."',
									address_ur='".htmlspecialchars($incoming['address_ur'])."',
									bank='".htmlspecialchars($incoming['bank'])."',
									count_rasch='".htmlspecialchars($incoming['count_rasch'])."',
									count_korr='".htmlspecialchars($incoming['count_korr'])."',
									bik='".htmlspecialchars($incoming['bik'])."',
									phone='".htmlspecialchars($incoming['phone'])."'
				WHERE user_id=".intval($incoming["cid"])." AND id=".intval($incoming["company"]);
		} else {
			$sql="INSERT INTO User_codetails (user_id,name,inn_kpp,address_ur,bank,count_rasch,count_korr,bik,phone)
				VALUES (".intval($incoming["cid"]).",
				'".htmlspecialchars($incoming['name'])."','".htmlspecialchars($incoming['inn_kpp'])."','".htmlspecialchars($incoming['address_ur'])."',
				'".htmlspecialchars($incoming['bank'])."','".htmlspecialchars($incoming['count_rasch'])."','".htmlspecialchars($incoming['count_korr'])."',
				'".htmlspecialchars($incoming['bik'])."','".htmlspecialchars($incoming['phone'])."')";
		}
		
		if (mysql_query($sql)) {
			$html.="<p>Информация о клиенте обновлена.</p>
			<p><a href='/netcat/modules/netshop/interface/clients.php?action=history&cid={$incoming["cid"]}'>Перейти в карточку клиента</a></p>";
			$form=0;
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
	echo printMenu();
	if (isset($incoming['cid'])) {
		$sql="SELECT * FROM User WHERE User_ID=".intval($incoming['cid']);
		if ($result=mysql_query($sql)) {
			$row = mysql_fetch_array($result); 
				//$res.="<option value='{$row['ShopOrderStatus_ID']}' ".(($row['ShopOrderStatus_ID']==$sel) ? "selected" : "").">{$row['ShopOrderStatus_Name']}</option>";
			//}
		}
	}

	//echo printMenu();
	//echo $html; 
	$hdn="";
if ($form) {
	$co=0;
	$sql="SELECT * FROM User_codetails WHERE user_id=".intval($incoming["cid"]);
	if ($rs=mysql_query($sql)) {
		$row1 = mysql_fetch_array($rs); 
		(!empty($row1)) ? $co=$row1["id"] : $co=0;
	}
?>
	<h2>Добавить реквизиты юр. лица для клиента <?php echo $row['Login']; ?></h2>
	<form action="/netcat/modules/netshop/interface/client-codetails.php" method="post" name="frm1" id="frm1">
	<fieldset style='border:0;'>
	<input type="hidden" id="cid" name="cid" value="<?php echo $incoming['cid']; ?>">
	<input type='hidden' name='company' id='company'  value="<?php echo $co; ?>">
	<table cellpadding="0" cellspacing="5" border="0" style="width:500px;">
	<tr><td style="align:right;">Название</td><td><input type="text" name="name" value="<?php echo $row1['name']; ?>" style="width:400px;"></td></tr>
	<tr><td style="align:right;">ИНН/КПП</td><td><input type="text" name="inn_kpp" value="<?php echo $row1['inn_kpp']; ?>" style="width:400px;"></td></tr>
	<tr><td style="align:right;">Юр. адрес</td><td><input type="text" name="address_ur" value="<?php echo $row1['address_ur']; ?>" style="width:400px;"></td></tr>
	<tr><td style="align:right;">Банк</td><td><input type="text" name="bank" value="<?php echo $row1['bank']; ?>" style="width:400px;"></td></tr>
	<tr><td style="align:right;">Расчетный счёт</td><td><input type="text" name="count_rasch" value="<?php echo $row1['count_rasch']; ?>" style="width:400px;"></td></tr>
	<tr><td style="align:right;">Корр. счёт</td><td><input type="text" name="count_korr" value="<?php echo $row1['count_korr']; ?>" style="width:400px;"></td></tr>
	<tr><td style="align:right;">БИК</td><td><input type="text" name="bik" value="<?php echo $row1['bik']; ?>" style="width:400px;"></td></tr>
	<tr><td style="align:right;">Телефон</td><td><input type="text" name="phone" value="<?php echo $row1['phone']; ?>" style="width:400px;"></td></tr>
	<tr><td colspan="2" style="text-align:center;"><input type="submit" value="Сохранить"></td></tr>
	</table>
	</fieldset>
	</form>
<?php 
}
	echo $html;
} else {
	echo "<p>У вас нет прав для просмотра этой страницы</p><p><a href='/netcat/modules/netshop/interface/login.php'>Вход</a></p>";
}
?>
</body>
</html>
<?php

mysql_close($con);
?>
