<?php
// 22.04.2016 Elen
// Заказы поставщикам
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("utils-waybill.php");
$incoming = parse_incoming();
$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
if (!$con) {
	die('Could not connect: ' . mysql_error());
}

mysql_select_db($MYSQL_DB_NAME, $con);
//mysql_set_charset("cp1251", $con);
mysql_set_charset("utf8", $con);

$UploadDir=$_SERVER['DOCUMENT_ROOT'].'/netcat_files/pricelist/';

function printRKOrder($n) {
	$html="";
	//echo $n."<br>";
	$itemscount=array();
	$created="";
	if ($n==0) {
		$html="<h1>Не указан номер заказа!</h1>";
		return $html;
	}
	$sql="SELECT * FROM RKOrders WHERE id=".intval($n);
	if ($result=mysql_query($sql)) {
		if ($row = mysql_fetch_array($result)) {
			$supplier=$row['supplier_id'];
			$created=$row['created'];
		}
	}
	
	$html.="
	<h1>Заказ №{$n} от ".date("d.m.Y",strtotime($created))."</h1>
	<p>Поставщик: ".getSupplier($supplier)."</p>";
	
	$sql="SELECT * FROM RKOrders_Goods
		INNER JOIN Message57 ON (RKOrders_Goods.item_id=Message57.Message_ID)
		WHERE rkorder_id=".$n;
	//echo $sql."<br>";
	if ($result=mysql_query($sql)) {
		$html.="<table cellpadding='2' cellspacing='0' border='1'>
		<tr>
			<td><b>Артикул<br>поставщика</b></td>
			<td><b>Артикул</b></td>
			<td><b>Название</b></td>
			<td><b>Количество</b></td>
		</tr>";
		$j=0;
		while ($row = mysql_fetch_array($result)) {
			$html.="
			<tr>
			<td><b>{$row['vendor_itemid']}</b></td>
			<td>{$row['ItemID']}</td>
			<td>{$row['Name']}</td>
			<td>{$row['qty']}</td>
			</tr>";
			$j=$j+1;
		}
		$html.="</table>";
	} else {
		die(mysql_error());
	}
	
	$html.="</form>";
	return $html;
}


// проверка авторизации ---------------------------------------------------------------------------
if (($_SESSION['insideadmin']!=1) && (!isset($_SESSION['nc_token_rand']))) {
	$url="/netcat/modules/netshop/interface/login.php";
	die('<script type="text/javascript">window.location.href="' . $url . '";</script>');
}
if ((!isset($_SESSION['admstat'])) || ($_SESSION['admstat']!=1)) {
	$url="/netcat/modules/netshop/interface/statistic.php";
	die('<script type="text/javascript">window.location.href="' . $url . '";</script>');
} 	
$html="";
$n=0;
if ((isset($incoming['n']))&&($incoming['n']!=0)) {
	$n=intval($incoming['n']);
}
$html.=printRKOrder($n,$incoming);


//echo $html;
// ------------------------------------------------------------------------------------------------

?>
<!DOCTYPE html>
<html>
<head>
	<title>Заказ поставщику</title>
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
echo $html;
?>

</body>
</html>
<?php

mysql_close($con);
?>
