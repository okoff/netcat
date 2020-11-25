<?php
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("utils-vendor.php");

function showCost($incoming=array()) {
	$html="";
	$sql="SELECT Message_ID,DeliveryCost,sendprice FROM Message51 WHERE
		Status=4 AND DeliveryMethod=3 AND Created BETWEEN '".$incoming['year']."-01-01 00:00:00' AND '".$incoming['year']."-12-31 23:59:59'
		ORDER BY Message_ID ASC";
	//echo $sql;
	$ordersum=0;
	$num=$num0=0;
	$fullcost=0;
	$num1=$num2=$num3=0;
	$fullcost1=$fullcost2=$fullcost3=0;
	$html1="";
	if ($result=mysql_query($sql)) {
		while($row=mysql_fetch_array($result)) {
			$num=$num+1;
			$fullcost=$fullcost+$row['sendprice'];
			$ordersum=0;
			$sql1="SELECT * FROM Netshop_OrderGoods WHERE Order_ID=".$row['Message_ID'];
			if ($result1=mysql_query($sql1)) {
				while($row1=mysql_fetch_array($result1)) {
					$ordersum=$ordersum+$row1['ItemPrice']*$row1['Qty'];
				}
			}
			if (($ordersum==0)||(!$row['sendprice'])) {
				$html1.="<a href='http://russian-knife.ru/netcat/message.php?catalogue=1&sub=57&cc=53&message={$row['Message_ID']}' target='_blank'>{$row['Message_ID']}</a><br>";
				$num0=$num0+1;
			}
			if ($ordersum<5000) {
				$num1=$num1+1;
				$fullcost1=$fullcost1+$row['sendprice'];
			}
			if (($ordersum>=5000)&&($ordersum<10000)) {
				$num2=$num2+1;
				$fullcost2=$fullcost2+$row['sendprice'];
			}
			if ($ordersum>=10000) {
				$num3=$num3+1;
				$fullcost3=$fullcost3+$row['sendprice'];
			}
		}
	}
	$html.="<h3>Средняя стоимость доставки за год почтой РФ</h3>";
	$html.="<p>Заказов с нулевой стоимостью доставки {$num0}</p>".$html1."<br></br>";
	$html.="<p>Всего заказов с доставкой по почте: {$num}</p>";
	$html.="<p>Общая сумма за доставку: ".number_format($fullcost, 0, ',', ' ')."р</p>";
	$html.="<p>Средняя стоимость доставки: ".number_format($fullcost/$num, 0, ',', ' ')."р</p>";
	
	$html.="<table cellpadding='2' cellspacing='0' border='1'>
		<tr><td>Стоимость заказа</td><td>кол.</td><td>общая стоимость доставки</td><td>средняя за доставку</td></tr>
		<tr><td>&lt; 5000 </td><td>{$num1}</td>
			<td style='text-align:right;'>".number_format($fullcost1, 0, ',', ' ')."</td>
			<td style='text-align:right;'>".number_format($fullcost1/$num1, 0, ',', ' ')."</td></tr>
		<tr><td>5000 &lt;= X &lt; 10000 </td><td>{$num2}</td>
			<td style='text-align:right;'>".number_format($fullcost2, 0, ',', ' ')."</td>
			<td style='text-align:right;'>".number_format($fullcost2/$num2, 0, ',', ' ')."</td></tr>
		<tr><td>&gt;= 10000 </td><td>{$num3}</td>
			<td style='text-align:right;'>".number_format($fullcost3, 0, ',', ' ')."</td>
			<td style='text-align:right;'>".number_format($fullcost3/$num3, 0, ',', ' ')."</td></tr>
		<tr><td>&nbsp;</td><td>".($num1+$num2+$num3)."</td><td>&nbsp;</td><td>&nbsp;</td></tr>
	</table>";
	
	$sql="SELECT Message_ID,DeliveryCost FROM Message51 WHERE
		Status=4 AND DeliveryMethod=1 AND Created BETWEEN '".$incoming['year']."-01-01 00:00:00' AND '".$incoming['year']."-12-31 23:59:59'
		ORDER BY Message_ID ASC";
	//echo $sql;
	$num=$num0=$num1=0;
	$fullcost=$fullcost0=$fullcost1=0;
	if ($result=mysql_query($sql)) {
		while($row=mysql_fetch_array($result)) {
			$num=$num+1;
			$fullcost=$fullcost+$row['DeliveryCost'];
			if ($row['DeliveryCost']==0) {
				$num0=$num0+1;
			} else {
				$num1=$num1+1;
				$fullcost1=$fullcost1+$row['DeliveryCost'];
			}
		}
	}
	$html.="<h3>Средняя стоимость доставки за год Курьером в пределах МКАД</h3>";
	$html.="<p>Всего заказов с доставкой курьером по Москве: {$num}</p>";
	$html.="<p>Общая сумма за доставку: ".number_format($fullcost, 0, ',', ' ')."р</p>";
	$html.="<p>Средняя стоимость доставки: ".number_format($fullcost/$num, 0, ',', ' ')."р</p>";
	$html.="<table cellpadding='2' cellspacing='0' border='1'>
		<tr><td>кол.</td><td>общая стоимость доставки</td><td>средняя за доставку</td></tr>
		<tr><td style='text-align:right;'>{$num1}</td>
			<td style='text-align:right;'>".number_format($fullcost1, 0, ',', ' ')."</td>
			<td style='text-align:right;'>".number_format($fullcost1/$num1, 0, ',', ' ')."</td></tr>
		<tr><td style='text-align:right;'>{$num0}</td>
			<td style='text-align:right;'>".number_format($fullcost0, 0, ',', ' ')."</td>
			<td style='text-align:right;'>".number_format($fullcost0/$num0, 0, ',', ' ')."</td></tr>
		<tr><td>".($num0+$num1)."</td><td>&nbsp;</td><td>&nbsp;</td></tr>
	</table>";
	return $html;
}
//=================================================================================================
// проверка авторизации ---------------------------------------------------------------------------
if (($_SESSION['insideadmin']!=1) && (!isset($_SESSION['nc_token_rand']))) {
	$url="/netcat/modules/netshop/interface/login.php";
	die('<script type="text/javascript">window.location.href="' . $url . '";</script>');
}
if ((!isset($_SESSION['admstat'])) || ($_SESSION['admstat']!=1)) {
	$url="/netcat/modules/netshop/interface/statistic.php";
	die('<script type="text/javascript">window.location.href="' . $url . '";</script>');
} 	
// ------------------------------------------------------------------------------------------------
	$UploadDir=$_SERVER['DOCUMENT_ROOT'].'/netcat_files/postfiles/trace/';
	$DownloadDir='/netcat_files/postfiles/trace/';
//	$DownloadDirLink='/netcat_files/postfiles/';
	$incoming = parse_incoming();
	$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
	if (!$con) {
		die('Could not connect: ' . mysql_error());
	}
	
	mysql_select_db($MYSQL_DB_NAME, $con);
	//mysql_set_charset("cp1251", $con);
	mysql_set_charset("utf8", $con);
	$where="";
	$orders=array();
	$show=1;
	//print_r($incoming);
	switch ($incoming['action']) {
		case "show":
			$html.=showCost($incoming);
			break;
		default:
			$incoming['month']=date("m");
			$incoming['year']=date("Y");
			break;
	}
?>
<!DOCTYPE html>
<html>
<head>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<title>Стоимость доставки почтой.</title>
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	</style>
	<link rel="stylesheet" href="style.css" type="text/css" media="print, projection, screen" />
	<script language="Javascript" type="text/javascript" src="/js/jquery.js"></script>
</head>

<body>

	<?php
		echo printMenu();
	?>
	<h1>Средняя стоимость доставки за год.</h1>
	<form name="frm1" id="frm1" action="/netcat/modules/netshop/interface/statistic-deliverycost.php" method="post">
	<input type="hidden" name="action" id="action" value="show">
	
	<table cellpadding="2" cellspacing="0" border="1">
		<tr>
			<td><select name="year" id="year">
				<option value="2016" <?php echo (((isset($incoming['year']))&&($incoming['year']==2016)) ? "selected" : "" );?>>2016</option>
				<option value="2015" <?php echo (((isset($incoming['year']))&&($incoming['year']==2015)) ? "selected" : "" );?>>2015</option>
				<option value="2014" <?php echo (((isset($incoming['year']))&&($incoming['year']==2014)) ? "selected" : "" );?>>2014</option>
				<option value="2013" <?php echo (((isset($incoming['year']))&&($incoming['year']==2013)) ? "selected" : "" );?>>2013</option>
				<option value="2012" <?php echo (((isset($incoming['year']))&&($incoming['year']==2012)) ? "selected" : "" );?>>2012</option>
				<option value="2011" <?php echo (((isset($incoming['year']))&&($incoming['year']==2011)) ? "selected" : "" );?>>2011</option>
			</select></td>
		</tr>
		<tr><td colspan="2"><input type="submit" value="Показать"></td></tr>
	</table>
	</form>

<?php 
if ($show) {
?>
	
<?php
}
 echo $html; ?>

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