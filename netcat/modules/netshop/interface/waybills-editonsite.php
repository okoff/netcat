<?php
// 9.04.2014 Elen
// редактирование накладной. оплата и дата оплаты
include_once ("../../../../vars.inc.php");
include_once ("utils.php");
include_once ("utils-waybill.php");
session_start();
$incoming = parse_incoming();
$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
if (!$con) {
	die('Could not connect: ' . mysql_error());
}

mysql_select_db($MYSQL_DB_NAME, $con);
//mysql_set_charset("cp1251", $con);
mysql_set_charset("utf8", $con);
$where="";
//print_r($incoming);
if ((isset($incoming['id'])) && (intval($incoming['id']))) {
	$wbid=$incoming['id'];
} else {
	die("Нет номера накладной!");
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Накладные прихода.</title>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	</style>	
	
	<script language="Javascript" type="text/javascript" src="/js/jquery.js"></script>
	<script type='text/javascript' src='/netcat/editors/ckeditor/ckeditor.js'></script>
</head>
<body>

<?php
if ((isset($_SESSION['nc_token_rand'])) || ((isset($_SESSION['insideadmin'])) && ($_SESSION['insideadmin']==1))) {
	//print_r($incoming);
	echo printMenu();
	$title="Редактирование описания накладной №".$incoming['id'];
?>

<?php
	$show=1;
	if ($incoming["request_method"]=="post") {
		// save onsite data
		((isset($incoming['onsite']))&&($incoming['onsite']==1)) ? $onsite=1 : $onsite=0;
		$sql="UPDATE Waybills SET onsite={$onsite},title='".quot_smart($incoming["ttl"])."',intro='".quot_smart($incoming['intro'])."',description='".quot_smart($incoming['description'])."' WHERE id=".$wbid;
		//echo $sql;
		if (mysql_query($sql)) {	
			$show=0;
			$html.="<h2>Накладная сохранена</h2>
			<p><a href='/netcat/modules/netshop/interface/waybills-list.php'>Перейти в список накладных</a></p>";
		} else {
			die($sql."Ошибка: ".mysql_error());
		}
	}
	$sql="SELECT * FROM Waybills WHERE id=".$wbid;
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$onsite=$row["onsite"];
		$ttl=$row["title"];
		$intro=$row["intro"];
		$description=$row["description"];
		$name=$row["name"];
		$supplier=$row["vendor_id"];
	}
?>
<h1><?php echo $title." (".$name; ?>) от <?php echo printVendor($supplier); ?></h1>
<?php
if ($show) {
?>
<form id="frm" name="frm" action="/netcat/modules/netshop/interface/waybills-editonsite.php" method="post">
<input type="hidden" id="id" name="id" value="<?php echo $wbid; ?>">
<table cellpadding='2' cellspacing='0' border='1'>
<tr>
	<td style='text-align:right;'>Показывать на сайте</td>
	<td><input type='checkbox' name='onsite' id='onsite' value='1' <?php echo ($onsite) ? "checked" : "";?>></td>
</tr>
<tr>
	<td style='text-align:right;'>Название</td>
	<td><input type='text' name='ttl' id='ttl' style="width:450px;" value='<?php echo $ttl; ?>'></td>
</tr><tr>
	<td style='text-align:right;'>Описание</td>
	<td><textarea name='intro' style="width:450px;"><?php echo $intro; ?></textarea></td>
</tr><tr>
	<td style='text-align:right;'>Полный текст</td>
	<td><textarea name='description' id='nc_editor' style="width:450px;"><?php echo $description; ?></textarea></td>
</tr>
<tr>
	<td colspan="2" style="text-align:center;"><input type="submit" value="Сохранить"></td>
</tr>
</table>
<br class="clear">
<p><a href="/netcat/modules/netshop/interface/waybills-list.php">вернуться в список накладных</a></p>

</form>
<?php
}
	echo $html;
} else {
	echo "<p>У вас нет прав для просмотра этой страницы</p><p><a href='/netcat/modules/netshop/interface/login.php'>Вход</a></p>";
}
?>
 <script type='text/javascript'>
  <!--
 // document.getElementById('nc_editor').value = "<?php //echo $intro; ?>";

  CKEDITOR.replace('nc_editor', {
                    skin : 'office2003',
                    width: '100%', height: 330,
                    language : 'ru_utf8',
                    smiley_path : '/images/smiles/'
                    });

  </script>
</body>
</html>
<?php

mysql_close($con);
?>
