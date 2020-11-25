<?php
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("utils-vendor.php");

function showPaymentsList($incoming=array()) {
	$html=$html1=$html2=$html3="";
	$payments=0; // оплаты поставщикy
	$sold=$residue=0;
	$k=intval($incoming['supplier']);
	$sql="SELECT * FROM Payments 
		WHERE created BETWEEN '".$incoming['year']."-".$incoming['month']."-01 00:00:00' AND '".$incoming['year']."-".$incoming['month']."-31 23:59:59' 
		AND supplier_id=".$k."
		ORDER BY created DESC";
	//echo $sql."<br>";
	$html1.="<h3>Оплаты</h3>
	<table cellpadding='2' cellspacing='0' border='1'>
		<tr>
		<td><b>Дата</b></td>
		<td><b>Сумма оплаты</b></td>
		<td><b>б/н</b></td>
		<td><b>н</b></td>
		</tr>";
	$itog1=$itog2=0;
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) {
			$payments=$payments+$row['summa'];
			$html1.="<tr><td>".date("d.m.Y",strtotime($row['created']))."</td>
				<td style='text-align:right;'>".number_format($row['summa'], 0, ',', ' ')."</td>
				<td>".(($row['bnal']) ? "<img src='/images/icons/ok.png'>" : "&nbsp;")."</td>
				<td>&nbsp;</td>
			</tr>";
			if ($row['bnal']==1) {
				$itog1=$itog1+$row['summa'];
			} else {
				$itog2=$itog2+$row['summa'];
			}			
		}
	} else {
		die(mysql_error());
	}
	$html1.="<tr>
		<td><b>Итого</b></td>
		<td style='text-align:right;'><b>".number_format(($itog1+$itog2), 0, ',', ' ')."</b></td>
		<td style='text-align:right;'><b>".number_format($itog1, 0, ',', ' ')."</b></td>
		<td style='text-align:right;'><b>".number_format($itog2, 0, ',', ' ')."</b></td>
	</tr>";
	$html1.="</table>";
	
	$html2.="<h3>Накладные прихода</h3>";
	$sql2="SELECT * FROM Waybills 
		WHERE vendor_id=".$k." AND type_id=2 AND created BETWEEN '".$incoming['year']."-".$incoming['month']."-01 00:00:00' AND '".$incoming['year']."-".$incoming['month']."-31 23:59:59'";
	//echo $sql2."<br>";
	$html2.="<table cellpadding='2' cellspacing='0' border='1'>
		<tr>
		<td><b>Дата</b></td>
		<td><b># накладная</b></td>
		<td><b>Сумма</b></td>
		</tr>";
	$wbitog=0;
	if ($result2=mysql_query($sql2)) {
		while($row2 = mysql_fetch_array($result2)) {
			$html2.="<tr><td>".date("d.m.Y",strtotime($row2['created']))."</td>
			<td><a target='_blank' href='/netcat/modules/netshop/interface/waybills-edit.php?id={$row2['id']}&start=1'>".$row2['id']."</a></td>
			<td style='text-align:right;'>".number_format($row2['summoriginal'], 0, ',', ' ')."</td>
			</tr>";
			$wbitog=$wbitog+$row2['summoriginal'];
		}
	} 
	$html2.="</table>";

	$sql1="SELECT * FROM Stats_Goods WHERE supplier_id=".$k." AND created BETWEEN '".$incoming['year']."-".$incoming['month']."-01 00:00:00' AND '".$incoming['year']."-".$incoming['month']."-31 23:59:59'";
	//echo $sql1."<br>";	
	if ($result1=mysql_query($sql1)) {
		while($row1 = mysql_fetch_array($result1)) {
			$sold=$row1['sold_pricez'];
			$residue=$row1['residue_pricez'];
			
		}
	} else {
		$html.="<td>&nbsp;</td><td>&nbsp;</td>";
	}
	//$html.="</tr>";
	
	
	$html3="<h3>Продажи</h3>";
	$html3.="<table cellpadding='2' cellspacing='0' border='1'>
		<tr>
		<td><b>Заказы</b></td>
		<td><b>Розничные продажи</b></td>
		</tr>";
	
	$itog=$itogr=0;
	$sql3="SELECT Message51.Message_ID,Message57_p.Price FROM Message51
		INNER JOIN Netshop_OrderGoods ON (Netshop_OrderGoods.Order_ID=Message51.Message_ID)
		INNER JOIN Message57 ON (Netshop_OrderGoods.Item_ID=Message57.Message_ID)
		INNER JOIN Message57_p ON (Message57_p.Message_ID=Message57.Message_ID)
		WHERE Message57.supplier=".$k." 
			AND Message51.created BETWEEN '{$incoming['year']}-{$incoming['month']}-01 00:00:00' AND '{$incoming['year']}-{$incoming['month']}-".cal_days_in_month(CAL_GREGORIAN, $incoming['month'], $incoming['year'])." 23:59:59'
			AND NOT Message51.Status=2 AND NOT Message51.DeliveryMethod=2";
	//echo $sql3."<br>";
	$html3.="<tr><td>";
	if ($result3=mysql_query($sql3)) {
		while($row3 = mysql_fetch_array($result3)) {
			//$html.="<a target='_blank' href='/netcat/modules/netshop/interface/waybills-edit.php?id={$row2['id']}&start=1'>".$row2['id']."</a><br>";
			$html3.="<a target='_blank' href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$row3['Message_ID']}'>".$row3['Message_ID']."</a><br>";
			$itog=$itog+$row3['Price'];
		}
	} 
	$html3.="</td>";
	
	$sql4="SELECT Retails.id,Message57_p.Price FROM Retails
		INNER JOIN Retails_goods ON (Retails_goods.retail_id=Retails.id)
		INNER JOIN Message57 ON (Retails_goods.item_id=Message57.Message_ID)
		INNER JOIN Message57_p ON (Message57_p.Message_ID=Message57.Message_ID)
		WHERE Message57.supplier=".$k." 
			AND Retails.created BETWEEN '{$incoming['year']}-{$incoming['month']}-01 00:00:00' AND '{$incoming['year']}-{$incoming['month']}-".cal_days_in_month(CAL_GREGORIAN, $incoming['month'], $incoming['year'])." 23:59:59'
			AND Retails_goods.deleted=0";
	//echo $sql4."<br>";
	$html3.="<td>";
	if ($result4=mysql_query($sql4)) {
		while($row4 = mysql_fetch_array($result4)) {
			//$html.="<a target='_blank' href='/netcat/modules/netshop/interface/waybills-edit.php?id={$row2['id']}&start=1'>".$row2['id']."</a><br>";
			$html3.="<a target='_blank' href='/netcat/modules/netshop/interface/retail-edit.php?id={$row4['id']}&start=1'>".$row4['id']."</a><br>";
			$itogr=$itogr+$row4['Price'];
		}
	} 
	$html3.="</td></tr>";	
	$html3.="<tr>
	<td style='text-align:right;'><b>".number_format($itog, 0, ',', ' ')."</b></td>
	<td style='text-align:right;'><b>".number_format($itogr, 0, ',', ' ')."</b></td>
	</tr></table>";
	
	$html.="<table cellpadding='2' cellspacing='0' border='1'>
	<tr>
		<td><b>Сумма оплат</b></td>
		<td><b>Сумма прихода</b></td>
		<td><b>Сумма продаж</b></td>
		<td><b>Сумма остатка</b></td>
	</tr>
	<tr>
		<td style='text-align:right;'><b>".number_format($payments, 0, ',', ' ')."</b></td>
		<td style='text-align:right;'><b>".number_format($wbitog, 0, ',', ' ')."</b></td>
		<td style='text-align:right;'><b>".number_format($sold, 0, ',', ' ')."</b></td>
		<td style='text-align:right;'><b>".number_format($residue, 0, ',', ' ')."</b></td>
	</tr>";
	$html.="</table>\n";
	$html.=$html1."<br>".$html2."<br>".$html3;
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
	<title>Развернутая статистика по поставщикам.</title>
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	td {
		vertical-align:top;
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
	<h1>Развернутая статистика по поставщикам.</h1>
	
	<form name="frm1" id="frm1" action="/netcat/modules/netshop/interface/statistic-paymentsfull.php" method="post">
	<input type="hidden" name="action" id="action" value="show">
	<!--table cellpadding="2" cellspacing="0" border="1">
		
	</table-->
	<table cellpadding="2" cellspacing="0" border="1">
		<tr><td>Поставщик:</td>
			<td><?php echo selectSupplier($incoming); ?></td>
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
				<option value="2017" <?php echo (((isset($incoming['year']))&&($incoming['year']==2017)) ? "selected" : "" );?>>2017</option>
				<option value="2016" <?php echo (((isset($incoming['year']))&&($incoming['year']==2016)) ? "selected" : "" );?>>2016</option>
				<option value="2015" <?php echo (((isset($incoming['year']))&&($incoming['year']==2015)) ? "selected" : "" );?>>2015</option>
				<option value="2014" <?php echo (((isset($incoming['year']))&&($incoming['year']==2014)) ? "selected" : "" );?>>2014</option>
				<option value="2013" <?php echo (((isset($incoming['year']))&&($incoming['year']==2013)) ? "selected" : "" );?>>2013</option>
				<option value="2012" <?php echo (((isset($incoming['year']))&&($incoming['year']==2012)) ? "selected" : "" );?>>2012</option>
				<option value="2011" <?php echo (((isset($incoming['year']))&&($incoming['year']==2011)) ? "selected" : "" );?>>2011</option>
			</select></td>
		</tr>
		<tr><td colspan="4"><input type="submit" value="Показать"></td></tr>
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