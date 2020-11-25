<?php
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("utils-vendor.php");

function showPaymentsList($incoming=array()) {
	$html="";
	$payments=array(); // оплаты от поставщика
	/*$sql="SELECT Payments.*,Classificator_Supplier.Supplier_Name FROM Payments 
		INNER JOIN Classificator_Supplier ON (Classificator_Supplier.Supplier_ID=Payments.supplier_id)
		WHERE Payments.supplier_id=".intval($incoming['supplier']);*/
	$fullp=$fullpbn=$fullpsum=$full1=$full2=$full3=0;
	$html.="<table cellpadding='2' cellspacing='0' border='1'>
				<tr>
				<td rowspan='2'><b>Поставщик</b></td>
				<td colspan='3'><b>Сумма оплаты</b></td>
				<td rowspan='2'><b>Сумма поставки</b></td>
				<td rowspan='2'><b>Сумма продаж</b></td>
				<td rowspan='2'><b>Сумма остатка</b></td>
				</tr>
				<tr>
					<td>нал</td>
					<td>б/нал</td>
					<td>сумма</td>
				</tr>
				";
			
	$sql1="SELECT * FROM Classificator_Supplier ORDER BY Supplier_Name ASC";
	if ($result1=mysql_query($sql1)) {
		while($row1 = mysql_fetch_array($result1)) {
			$sql="SELECT * FROM Payments 
				WHERE supplier_id={$row1['Supplier_ID']} AND
				created BETWEEN '".$incoming['year']."-".$incoming['month']."-01 00:00:00' AND '".$incoming['year']."-".$incoming['month']."-31 23:59:59' 
				ORDER BY created DESC";
			$payments=0;
			$paymentsbn=0;
			if ($result=mysql_query($sql)) {
				while($row = mysql_fetch_array($result)) {
					if ($row['bnal']==1){
						$paymentsbn=$paymentsbn+$row['summa'];
					} else {
						$payments=$payments+$row['summa'];
					}
				}
			} else {
				die(mysql_error());
			}
			$style=(($row1['Statistic']) ? "" : " style='background:#fff79b;'");
			$style1=(($row1['Statistic']) ? " style='text-align:right;'" : " style='background:#fff79b;text-align:right;'");
			$html.="<tr><td {$style}>{$row1['Supplier_Name']}</td>";
			$html.="<td {$style1}>".number_format($payments, 0, ',', ' ')."</td>";
			$html.="<td {$style1}>".number_format($paymentsbn, 0, ',', ' ')."</td>";
			$html.="<td {$style1}>".number_format(($payments+$paymentsbn), 0, ',', ' ')."</td>";
			
			// select * from waybills
			$wb=0;
			$sql2="SELECT * FROM Waybills 
				WHERE vendor_id={$row1['Supplier_ID']} AND type_id=2 AND
				created BETWEEN '".$incoming['year']."-".$incoming['month']."-01 00:00:00' AND '".$incoming['year']."-".$incoming['month']."-31 23:59:59'";
			if ($result2=mysql_query($sql2)) {
				while($row2 = mysql_fetch_array($result2)) {
					$wb=$wb+$row2['summoriginal'];
				}
			} 
	
			$html.="<td {$style1}>".number_format($wb, 0, ',', ' ')."</td>";
			if ($row1['Statistic']) {
				$sql2="SELECT * FROM Stats_Goods WHERE supplier_id=".$row1['Supplier_ID']." AND created BETWEEN '".$incoming['year']."-".$incoming['month']."-01 00:00:00' AND '".$incoming['year']."-".$incoming['month']."-31 23:59:59'";
					//echo $sql1."<br>";	
				$temp="";
				if ($result2=mysql_query($sql2)) {
					while($row2 = mysql_fetch_array($result2)) {
						$temp.="<td {$style1}>".number_format($row2['sold_pricez'], 0, ',', ' ')."</td>";
						$temp.="<td {$style1}>".number_format($row2['residue_pricez'], 0, ',', ' ')."</td>";
						
						$full2=$full2+$row2['sold_pricez'];
						$full3=$full3+$row2['residue_pricez'];
					}
				} 
				$html.=($temp) ? $temp : "<td {$style}>&nbsp;</td><td {$style}>&nbsp;</td>";
				
			} else {
				$html.="<td {$style1}>0</td>";
				$html.="<td {$style1}>0</td>";
			}
			$html.="</tr>";
			
			$fullp=$fullp+$payments;
			$fullpbn=$fullpbn+$paymentsbn;
			$fullpsum=$fullpsum+$payments+$paymentsbn;
			$full1=$full1+$wb;
		}
	}
	
	//echo $sql."<br>";
	$html.="<tr>
	<td><b>ИТОГО</b></td>
	<td style='text-align:right;'><b>".number_format($fullp, 0, ',', ' ')."</b></td>
	<td style='text-align:right;'><b>".number_format($fullpbn, 0, ',', ' ')."</b></td>
	<td style='text-align:right;'><b>".number_format($fullpsum, 0, ',', ' ')."</b></td>
	<td style='text-align:right;'><b>".number_format($full1, 0, ',', ' ')."</b></td>
	<td style='text-align:right;'><b>".number_format($full2, 0, ',', ' ')."</b></td>
	<td style='text-align:right;'><b>".number_format($full3, 0, ',', ' ')."</b></td>
	</tr>";
	
	$html.="</table>\n";
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
			$html.=showPaymentsList($incoming);
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
	<title>Оплата поставщику.</title>
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	</style>
	<link rel="stylesheet" href="style.css" type="text/css" media="print, projection, screen" />
	<script language="Javascript" type="text/javascript" src="/js/jquery.js"></script>
	<script type="text/javascript" src="/js/jquery.tablesorter.js"></script> 
</head>

<body>
<script type="text/javascript">
$(document).ready(function() 
    { 
        $("#myTable").tablesorter(); 
    } 
); 
</script>
	<?php
		echo printMenu();
	?>
	<h1>Свернутая статистика по поставщикам. Оплаты.</h1>
	<p><a href="/netcat/modules/netshop/interface/statistic-paymentsfull.php">Развернутая статистика по поставщику</a></p>
	<p><a href="/netcat/modules/netshop/interface/statistic-paymentsfull1.php">Все оплаты за месяц</a></p>
	<form name="frm1" id="frm1" action="/netcat/modules/netshop/interface/statistic-payments.php" method="post">
	<input type="hidden" name="action" id="action" value="show">
	<!--table cellpadding="2" cellspacing="0" border="1">
		<tr><td>Поставщик:</td>
			<td><?php echo selectSupplier($incoming); ?></td></tr>
		<tr><td colspan="2"><input type="submit" value="Показать"></td></tr>
	</table-->
	<table cellpadding="2" cellspacing="0" border="1">
		<tr>
			<td><select name="month" id="month">
			<option value="1" <?php echo (((isset($incoming['month']))&&($incoming['month']==1)) ? "selected" : "" );?>>январь</option>
			<option value="2" <?php echo (((isset($incoming['month']))&&($incoming['month']==2)) ? "selected" : "" );?>>февраль</option>
			<option value="3" <?php echo (((isset($incoming['month']))&&($incoming['month']==3)) ? "selected" : "" );?>>март</option>
			<option value="4" <?php echo (((isset($incoming['month']))&&($incoming['month']==4)) ? "selected" : "" );?>>апрель</option>
			<option value="5" <?php echo (((isset($incoming['month']))&&($incoming['month']==5)) ? "selected" : "" );?>>май</option>
			<option value="6" <?php echo (((isset($incoming['month']))&&($incoming['month']==6)) ? "selected" : "" );?>>июнь</option>
			<option value="7" <?php echo (((isset($incoming['month']))&&($incoming['month']==7)) ? "selected" : "" );?>>июль</option>
			<option value="8" <?php echo (((isset($incoming['month']))&&($incoming['month']==8)) ? "selected" : "" );?>>август</option>
			<option value="9" <?php echo (((isset($incoming['month']))&&($incoming['month']==9)) ? "selected" : "" );?>>сентябрь</option>
			<option value="10" <?php echo (((isset($incoming['month']))&&($incoming['month']==10)) ? "selected" : "" );?>>октябрь</option>
			<option value="11" <?php echo (((isset($incoming['month']))&&($incoming['month']==11)) ? "selected" : "" );?>>ноябрь</option>
			<option value="12" <?php echo (((isset($incoming['month']))&&($incoming['month']==12)) ? "selected" : "" );?>>декабрь</option>
			</select></td>
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
<br><br>
<table cellpadding="0" cellspacing="0" border="1">
<tr><td style="background:#fff79b;">&nbsp;&nbsp;&nbsp;</td>
	<td>статистика не собирается</td>
</tr>
</table>
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