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
	#addclient, #findclient {
		padding-left:50px;
	}
	</style>	
	
	<script language="Javascript" type="text/javascript" src="/js/jquery.js"></script>
</head>
<body>

<?php
if ((isset($_SESSION['nc_token_rand'])) || ((isset($_SESSION['insideadmin'])) && ($_SESSION['insideadmin']==1))) {
	//print_r($incoming);
	echo printMenu();
	$title="Редактирование оплаты по накладной №".$incoming['id'];
?>

<?php
	$show=1;
	if ($incoming["request_method"]=="post") {
		// save paid data
		((isset($incoming['paid']))&&($incoming['paid']==1)) ? $paid=1 : $paid=0;
		((isset($incoming['paid_date']))&&($incoming['paid_date']!="")) ? $paid_date=date("Y-m-d",strtotime($incoming['paid_date'])) : $paid_date='';
		$sql="UPDATE Waybills SET paid={$paid},paid_date='{$paid_date}',paid_comment='".quot_smart($incoming['paid_comment'])."' WHERE id=".$wbid;
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
		$paid_date=((date("d.m.Y",strtotime($row["paid_date"]))!="01.01.1970")and(date("d.m.Y",strtotime($row["paid_date"]))!="30.11.-0001")) ? date("d.m.Y",strtotime($row["paid_date"])) : "";
		$paid=$row["paid"];
		$paid_comment=$row["paid_comment"];
		$name=$row["name"];
		$supplier=$row["vendor_id"];
	}
?>
<h1><?php echo $title." (".$name; ?>) от <?php echo printVendor($supplier); ?></h1>
<?php
if ($show) {
?>
<form id="frm" name="frm" action="/netcat/modules/netshop/interface/waybills-editpayment.php" method="post">
<input type="hidden" id="id" name="id" value="<?php echo $wbid; ?>">
<table cellpadding='2' cellspacing='0' border='1'>
<tr>
	<td style='text-align:right;'>Дата оплаты</td><td><input type='text' name='paid_date' id='paid_date' size='20' value='<?php echo $paid_date; ?>' class="datepickerTimeField"></td>
	<td><input type='checkbox' name='paid' id='paid' value='1' <?php echo ($paid) ? "checked" : "";?>>Оплачено</td>
</tr>
<tr>
	<td style='text-align:right;'>Комментарий к оплате</td>
	<td colspan="2"><input type='text' name='paid_comment' id='paid_comment' style="width:450px;" value='<?php echo $paid_comment; ?>'></td>
</tr>
<tr>
	<td colspan="2"><input type="submit" value="Сохранить"></td>
	<td colspan="2"><a href="/netcat/modules/netshop/interface/waybills-list.php">вернуться в список накладных</a></td>
</tr>
</table>
<br class="clear">


</form>
<?php
}
	echo $html;
} else {
	echo "<p>У вас нет прав для просмотра этой страницы</p><p><a href='/netcat/modules/netshop/interface/login.php'>Вход</a></p>";
}
?>
<link type="text/css" href="/css/latest.css" rel="Stylesheet" />
<script type="text/javascript" src="/js/ui.datepicker.js"></script>
<script>
$(".datepickerTimeField").datepicker({
		changeMonth: true,
		changeYear: true,
		dateFormat: 'dd.mm.yy',
		firstDay: 1, changeFirstDay: false,
		navigationAsDateFormat: false,
		duration: 0// отключаем эффект появления
});
</script>
</body>
</html>
<?php

mysql_close($con);
?>
