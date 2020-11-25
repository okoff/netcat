<?php
// 24.12.2013 Elen
// поиск и просмотр списка розничных продаж (магазин)
include_once ("../../../../vars.inc.php");
include_once ("utils.php");
include_once ("utils-waybill.php");
session_start();

function getClientNobuy() {
	$res=0;
	$sql="SELECT * FROM User_nobuy WHERE created LIKE '%".date("Y-m-d")."%'";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$res=$row['qty'];
	}
	return $res;
}
// ------------------------------------------------------------------------------------------------

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
	$where="";
	//print_r($incoming);
	if (!isset($incoming['action'])) {
		$html=getWaybillList($incoming);
	} else {
		switch ($incoming['action']) {
			default:
				$html=getWaybillList($incoming);
				break;
		}
	}

?>
<!DOCTYPE html>
<html>
<head>
	<title>Накладные прихода</title>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<script>
function clickEdit() {
	var len=0;
	var j=1;
	var boxes = document.getElementsByTagName('input');
	if (document.getElementById("action").value=="create") {
		for (i=0; i<boxes.length; i++)  {
			if (boxes[i].type == 'checkbox')   {
				if (boxes[i].checked) {
					//alert(j);
					return true;
				}
				j=j+1;
			}	
		}
		document.getElementById("err").style.display="block";
		return false;
	} else {
		return true;
	}
	
}
	</script>
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	</style>
	
	<script language="Javascript" type="text/javascript" src="/js/jquery.js"></script>
</head>
<body>
	
<?php
if ((isset($_SESSION['nc_token_rand'])) || ((isset($_SESSION['insideadmin'])) && ($_SESSION['insideadmin']==1))) {
	echo printMenu();
?>
	<h1>Накладные прихода [<a href='/netcat/modules/netshop/interface/waybills-edit.php?start=1'>новая</a>]</h1>
	<form name="frm1" id="frm1" action="/netcat/modules/netshop/interface/waybills-list.php" method="post">
	<input type="hidden" name="action" id="action" value="filter">
	<table cellpadding="2" cellspacing="0" border="1">
		<tr><td colspan="2">
			<table cellpadding="0" cellspacing="5" border="0"><tr>
					<td>с</td>
					<td><input name="min" value="<?php echo isset($incoming['min']) ? date("d.m.Y", strtotime($incoming['min'])) : date("d.m.Y",mktime(0, 0, 0, date("m")-2, date("d"), date("Y"))); ?>" class="datepickerTimeField"></td>
					<td>по</td>
					<td><input name="max" value="<?php echo isset($incoming['max']) ? date("d.m.Y", strtotime($incoming['max'])) : date("d.m.Y") ?>" class="datepickerTimeField"></td>
			</tr></table>
			</td>
			<td style="text-align:right;">Статус:</td>
			<td><?php echo selectWaybillStatus($incoming); ?></td>
		</tr>
		
		<tr>
			<td style="text-align:right;">Поставщик:</td>
			<td><?php echo selectSupplier($incoming); ?></td>
			<td style="text-align:right;">Тип оплаты:</td>
			<td><?php echo selectWaybillPayment($incoming); ?></td>
		</tr>
		<tr>
			<td style="text-align:right;">Тип накладной:</td>
			<td><?php echo selectWaybillType($incoming); ?></td>
			<td style="text-align:right;">Способ оплаты:</td>
			<td><?php echo selectWaybillPaymentType($incoming); ?></td>
		</tr>
		<tr>
			
		</tr>
		<tr><td colspan="4"><input type="submit" value="Показать"></td></tr>
	</table>
	</form>
	<!--h2><?php echo date("d.m.Y"); ?></h2>
	<p>Покупатели без покупки <b><?php echo getClientNobuy(); ?></b>: [<b><a href="/netcat/modules/netshop/interface/client-nobuy.php">добавить</a></b>]</p-->
	<?php echo $html; 
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
