<?php
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("utils-vendor.php");
include_once ("utils-statistic.php");

function showResidueForVendorForMonth1($supplier_id,$year,$mo) {
	$res="";
	$sql="SELECT * FROM Stats_Goods WHERE supplier_id={$supplier_id} AND created LIKE '{$year}-".(($mo<10) ? "0".$mo : $mo)."%'";
	//echo $sql;
	$r=mysql_query($sql);
	while ($row1=mysql_fetch_array($r)) {
		$res.="<td>{$row1['residue_count']}</td>";
		$res.="<td>{$row1['residue_price']}</td>";
		$res.="<td>{$row1['residue_pricez']}</td>";
	}
	return $res;
}

function showStatisticList($incoming) {
	$res="";
	//print_r($incoming);
	$res.="<table cellpadding='2' cellspacing='0' border='1'>
	<tr><td style='width:100px;'><b>Поставщик</b></td>
		<!--td style='width:100px;'><b>Кол. проданных штук</b></td>
		<td style='width:100px;'><b>Сумма<br>реализации</b></td>
		<td style='width:100px;'><b>Сумма<br>реализации закуп.</b></td>
		<td style='width:100px;'><b>Сред. стоимость<br>покупки</b></td>
		<td style='width:100px;'><b>% от итого</b></td-->
		<td style='width:100px;'><b>Остаток</b></td><td><b>Сумма остатка</b></td>
		<td style='width:100px;'><b>Сумма остатка<br>закуп.</b></td>
		<!--td style='width:100px;'><b>% от итого</b></td>
		<td style='width:100px;'><b>% наценки</b></td--></tr>";
	
	$sql="SELECT Supplier_ID,Supplier_Name FROM Classificator_Supplier WHERE Checked=1 AND Statistic=1 ORDER BY Supplier_Name ASC";
	$r=mysql_query($sql);
	while ($row1=mysql_fetch_array($r)) {
		$res.="<tr><td>{$row1['Supplier_Name']}</td>";
		//$res.=showStatisticVendorForMonth($row1['Supplier_ID'], $incoming['year'],$incoming['month']);
		$res.=showResidueForVendorForMonth1($row1['Supplier_ID'], $incoming['year'],$incoming['month']);
		$res.="</tr>";
	}
	$res.="</table>";
	return $res;
}
//0 - 0-3000
//1 - 3-5.5000
//2 - 5.500-10.000
//3 - 10-15
//4 - >15
function showPriceList($incoming) {
	//print_r($incoming);
	$price=array();
	$fprice=array();
	$fpricez=array();
	for ($i=0;$i<5;$i++) {
		$price[$i]=0;
		$fprice[$i]=0;
		$fpricez[$i]=0;
	}
	$res="";
	$k=0;
	$year=(($incoming['year']) ? $incoming['year'] : date("Y"));
	$mo=(($incoming['month']) ? $incoming['month'] : date("m"));
	//echo $mo."<br>";
	//$mo=($mo<10) ? "0".$m : $m;
	$sql="SELECT Netshop_OrderGoods.*,Message57.Sub_Class_ID,Message57_p.Price AS PriceZ FROM Message51 
		INNER JOIN Netshop_OrderGoods ON (Message51.Message_ID=Netshop_OrderGoods.Order_ID)
		INNER JOIN Message57 ON (Message57.Message_ID=Netshop_OrderGoods.Item_ID)
		INNER JOIN Message57_p ON (Message57_p.Message_ID=Netshop_OrderGoods.Item_ID)
		WHERE Message51.Created BETWEEN '{$year}-".(($mo<10) ? "0".$mo : $mo)."-01 00:00:00' AND '{$year}-".(($mo<10) ? "0".$mo : $mo)."-31 23:59:59' 
		AND (Message51.Status=4 OR Message51.closed=1 OR Message51.paid=1)
		AND Message57.Sub_Class_ID IN (176,136,173,135,174,139,185,187,191,195,325)
		ORDER BY ItemPrice";
	//echo $sql."<br>";
	$r=mysql_query($sql);
	while ($row=mysql_fetch_array($r)) {
		if ($row['ItemPrice']<=3000) {
			$price[0]=$price[0]+1;
			$fprice[0]=$fprice[0]+$row['ItemPrice']*$row['Qty'];
			$fpricez[0]=$fpricez[0]+$row['PriceZ']*$row['Qty'];
		}
		if (($row['ItemPrice']>3000)&&($row['ItemPrice']<=5500)) {
			$price[1]=$price[1]+1;
			$fprice[1]=$fprice[1]+$row['ItemPrice']*$row['Qty'];
			$fpricez[1]=$fpricez[1]+$row['PriceZ']*$row['Qty'];
		}
		if (($row['ItemPrice']>5500)&&($row['ItemPrice']<=10000)) {
			$price[2]=$price[2]+1;
			$fprice[2]=$fprice[2]+$row['ItemPrice']*$row['Qty'];
			$fpricez[2]=$fpricez[2]+$row['PriceZ']*$row['Qty'];
		}
		if (($row['ItemPrice']>10000)&&($row['ItemPrice']<=15000)) {
			$price[3]=$price[3]+1;
			$fprice[3]=$fprice[3]+$row['ItemPrice']*$row['Qty'];
			$fpricez[3]=$fpricez[3]+$row['PriceZ']*$row['Qty'];
		}
		if ($row['ItemPrice']>15000) {
			$price[4]=$price[4]+1;
			$fprice[4]=$fprice[4]+$row['ItemPrice']*$row['Qty'];
			$fpricez[4]=$fpricez[4]+$row['PriceZ']*$row['Qty'];
		}
		$k=$k+1;
	}
	//print_r($price);
	//echo "<br>";
	$sql="SELECT Retails_goods.*,Message57.Sub_Class_ID,Message57_p.Price AS PriceZ FROM Retails 
		INNER JOIN Retails_goods ON (Retails.id=Retails_goods.retail_id)
		INNER JOIN Message57 ON (Message57.Message_ID=Retails_goods.item_id)
		INNER JOIN Message57_p ON (Message57_p.Message_ID=Retails_goods.item_id)
		WHERE Retails.created BETWEEN '{$year}-".(($mo<10) ? "0".$mo : $mo)."-01 00:00:00' AND '{$year}-".(($mo<10) ? "0".$mo : $mo)."-31 23:59:59' 
		AND (Retails.closed=1 OR Retails.paid=1)
		AND Message57.Sub_Class_ID IN  (176,136,173,135,174,139,185,187,191,195,325)
		ORDER BY itemprice";
	//echo $sql."<br>";
	$r=mysql_query($sql);
	while ($row=mysql_fetch_array($r)) {
		if ($row['itemprice']<=3000) {
			$price[0]=$price[0]+1;
			$fprice[0]=$fprice[0]+$row['itemprice']*$row['qty'];
			$fpricez[0]=$fpricez[0]+$row['PriceZ']*$row['qty'];
		}
		if (($row['itemprice']>3000)&&($row['itemprice']<=5500)) {
			$price[1]=$price[1]+1;
			$fprice[1]=$fprice[1]+$row['itemprice']*$row['qty'];
			$fpricez[1]=$fpricez[1]+$row['PriceZ']*$row['qty'];
		}
		if (($row['itemprice']>5500)&&($row['itemprice']<=10000)) {
			$price[2]=$price[2]+1;
			$fprice[2]=$fprice[2]+$row['itemprice']*$row['qty'];
			$fpricez[2]=$fpricez[2]+$row['PriceZ']*$row['qty'];
		}
		if (($row['itemprice']>10000)&&($row['itemprice']<=15000)) {
			$price[3]=$price[3]+1;
			$fprice[3]=$fprice[3]+$row['itemprice']*$row['qty'];
			$fpricez[3]=$fpricez[3]+$row['PriceZ']*$row['qty'];
		}
		if ($row['itemprice']>15000) {
			$price[4]=$price[4]+1;
			$fprice[4]=$fprice[4]+$row['itemprice']*$row['qty'];
			$fpricez[4]=$fpricez[4]+$row['PriceZ']*$row['qty'];
		}
		$k=$k+1;
	}
	//print_r($price);
	//echo "<br>";
	
	$res.="<h3>Ножи</h3>
	<p>Всего: {$k}</p>
	<table cellpadding='2' cellspacing='0' border='1'>
	<tr>
		<td>&nbsp;</td>
		<td><b>&lt;3.000</b></td>
		<td><b>3.000 - 5.500</b></td>
		<td><b>5.500 - 10.000</b></td>
		<td><b>10.000 - 15.000</b></td>
		<td><b>&gt;15.000</b></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>{$price[0]}</td>
		<td>{$price[1]}</td>
		<td>{$price[2]}</td>
		<td>{$price[3]}</td>
		<td>{$price[4]}</td>
	</tr>
	<tr>
		<td><b>Цена розн.</b></td>
		<td>{$fprice[0]}</td>
		<td>{$fprice[1]}</td>
		<td>{$fprice[2]}</td>
		<td>{$fprice[3]}</td>
		<td>{$fprice[4]}</td>
	</tr>
	<tr>
		<td><b>Цена закуп.</b></td>
		<td>{$fpricez[0]}</td>
		<td>{$fpricez[1]}</td>
		<td>{$fpricez[2]}</td>
		<td>{$fpricez[3]}</td>
		<td>{$fpricez[4]}</td>
	</tr>
	<tr>
		<td><b>Доход</b></td>
		<td>".($fprice[0]-$fpricez[0])."</td>
		<td>".($fprice[1]-$fpricez[1])."</td>
		<td>".($fprice[2]-$fpricez[2])."</td>
		<td>".($fprice[3]-$fpricez[3])."</td>
		<td>".($fprice[4]-$fpricez[4])."</td>
	</tr>
	</table>";
	return $res;
}
function showPriceGiftList($incoming) {
	//print_r($incoming);
	$price=array();
	$fprice=array();
	$fpricez=array();
	for ($i=0;$i<5;$i++) {
		$price[$i]=0;
		$fprice[$i]=0;
		$fpricez[$i]=0;
	}
	$res="";
	$k=0;
	$year=(($incoming['year']) ? $incoming['year'] : date("Y"));
	$mo=(($incoming['month']) ? $incoming['month'] : date("m"));
	//echo $mo."<br>";
	//$mo=($mo<10) ? "0".$m : $m;
	$sql="SELECT Netshop_OrderGoods.*,Message57.Sub_Class_ID,Message57_p.Price AS PriceZ FROM Message51 
		INNER JOIN Netshop_OrderGoods ON (Message51.Message_ID=Netshop_OrderGoods.Order_ID)
		INNER JOIN Message57 ON (Message57.Message_ID=Netshop_OrderGoods.Item_ID)
		INNER JOIN Message57_p ON (Message57_p.Message_ID=Netshop_OrderGoods.Item_ID)
		WHERE Message51.Created BETWEEN '{$year}-".(($mo<10) ? "0".$mo : $mo)."-01 00:00:00' AND '{$year}-".(($mo<10) ? "0".$mo : $mo)."-31 23:59:59' 
		AND (Message51.Status=4 OR Message51.closed=1 OR Message51.paid=1)
		AND Message57.Sub_Class_ID IN (142,182)
		ORDER BY ItemPrice";
	//echo $sql."<br>";
	$r=mysql_query($sql);
	while ($row=mysql_fetch_array($r)) {
		if ($row['ItemPrice']<=5000) {
			$price[0]=$price[0]+1;
			$fprice[0]=$fprice[0]+$row['ItemPrice']*$row['Qty'];
			$fpricez[0]=$fpricez[0]+$row['PriceZ']*$row['Qty'];
		}
		if (($row['ItemPrice']>5000)&&($row['ItemPrice']<=15000)) {
			$price[1]=$price[1]+1;
			$fprice[1]=$fprice[1]+$row['ItemPrice']*$row['Qty'];
			$fpricez[1]=$fpricez[1]+$row['PriceZ']*$row['Qty'];
		}
		if (($row['ItemPrice']>15000)&&($row['ItemPrice']<=35000)) {
			$price[2]=$price[2]+1;
			$fprice[2]=$fprice[2]+$row['ItemPrice']*$row['Qty'];
			$fpricez[2]=$fpricez[2]+$row['PriceZ']*$row['Qty'];
		}
		if (($row['ItemPrice']>35000)&&($row['ItemPrice']<=50000)) {
			$price[3]=$price[3]+1;
			$fprice[3]=$fprice[3]+$row['ItemPrice']*$row['Qty'];
			$fpricez[3]=$fpricez[3]+$row['PriceZ']*$row['Qty'];
		}
		if ($row['ItemPrice']>50000) {
			$price[4]=$price[4]+1;
			$fprice[4]=$fprice[4]+$row['ItemPrice']*$row['Qty'];
			$fpricez[4]=$fpricez[4]+$row['PriceZ']*$row['Qty'];
		}
		$k=$k+1;
	}
	//print_r($price);
	//echo "<br>";
	$sql="SELECT Retails_goods.*,Message57.Sub_Class_ID,Message57_p.Price AS PriceZ  FROM Retails 
		INNER JOIN Retails_goods ON (Retails.id=Retails_goods.retail_id)
		INNER JOIN Message57 ON (Message57.Message_ID=Retails_goods.item_id)
		INNER JOIN Message57_p ON (Message57_p.Message_ID=Retails_goods.item_id)
		WHERE Retails.created BETWEEN '{$year}-".(($mo<10) ? "0".$mo : $mo)."-01 00:00:00' AND '{$year}-".(($mo<10) ? "0".$mo : $mo)."-31 23:59:59' 
		AND (Retails.closed=1 OR Retails.paid=1)
		AND Message57.Sub_Class_ID IN (142,182)
		ORDER BY itemprice";
	//echo $sql."<br>";
	$r=mysql_query($sql);
	while ($row=mysql_fetch_array($r)) {
		if ($row['itemprice']<=5000) {
			$price[0]=$price[0]+1;
			$fprice[0]=$fprice[0]+$row['itemprice']*$row['qty'];
			$fpricez[0]=$fpricez[0]+$row['PriceZ']*$row['qty'];
		}
		if (($row['itemprice']>5000)&&($row['itemprice']<=15000)) {
			$price[1]=$price[1]+1;
			$fprice[1]=$fprice[1]+$row['itemprice']*$row['qty'];
			$fpricez[1]=$fpricez[1]+$row['PriceZ']*$row['qty'];
		}
		if (($row['itemprice']>15000)&&($row['itemprice']<=35000)) {
			$price[2]=$price[2]+1;
			$fprice[2]=$fprice[2]+$row['itemprice']*$row['qty'];
			$fpricez[2]=$fpricez[2]+$row['PriceZ']*$row['qty'];
		}
		if (($row['itemprice']>35000)&&($row['itemprice']<=50000)) {
			$price[3]=$price[3]+1;
			$fprice[3]=$fprice[3]+$row['itemprice']*$row['qty'];
			$fpricez[3]=$fpricez[3]+$row['PriceZ']*$row['qty'];
		}
		if ($row['itemprice']>50000) {
			$price[4]=$price[4]+1;
			$fprice[4]=$fprice[4]+$row['itemprice']*$row['qty'];
			$fpricez[4]=$fpricez[4]+$row['PriceZ']*$row['qty'];
		}
		$k=$k+1;
	}
	//print_r($price);
	//echo "<br>";
	
	$res.="<h3>Ножи подарочные и авторские</h3>
	<p>Всего: {$k}</p>
	<table cellpadding='2' cellspacing='0' border='1'>
	<tr>
		<td>&nbsp;</td>
		<td><b>&lt;5.000</b></td>
		<td><b>5.000 - 15.000</b></td>
		<td><b>15.000 - 35.000</b></td>
		<td><b>35.000 - 50.000</b></td>
		<td><b>&gt;50.000</b></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>{$price[0]}</td>
		<td>{$price[1]}</td>
		<td>{$price[2]}</td>
		<td>{$price[3]}</td>
		<td>{$price[4]}</td>
	</tr>
	<tr>
		<td><b>Цена розн.</b></td>
		<td>{$fprice[0]}</td>
		<td>{$fprice[1]}</td>
		<td>{$fprice[2]}</td>
		<td>{$fprice[3]}</td>
		<td>{$fprice[4]}</td>
	</tr>
	<tr>
		<td><b>Цена закуп.</b></td>
		<td>{$fpricez[0]}</td>
		<td>{$fpricez[1]}</td>
		<td>{$fpricez[2]}</td>
		<td>{$fpricez[3]}</td>
		<td>{$fpricez[4]}</td>
	</tr>
	<tr>
		<td><b>Доход</b></td>
		<td>".($fprice[0]-$fpricez[0])."</td>
		<td>".($fprice[1]-$fpricez[1])."</td>
		<td>".($fprice[2]-$fpricez[2])."</td>
		<td>".($fprice[3]-$fpricez[3])."</td>
		<td>".($fprice[4]-$fpricez[4])."</td>
	</tr>
	</table>";
	return $res;
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
		case "filter":
			$html.=showPriceList($incoming);
			$html.="<br>";
			$html.=showPriceGiftList($incoming);

			break;
		default:
			$incoming['month']=date("m");
			$incoming['year']=date("Y");
			//$html.=viewStatsGoods();
			break;
	}
?>
<!DOCTYPE html>
<html>
<head>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<title>Статистика по поставщикам</title>
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
		echo printMenu();
	?>
	<h1>Статистика по поставщикам. Просмотр продаж.</h1>
	<form name="frm1" id="frm1" action="/netcat/modules/netshop/interface/statistic-price.php" method="post">
	<input type="hidden" name="action" id="action" value="filter">
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
				<option value="2018" <?php echo (((isset($incoming['year']))&&($incoming['year']==2017)) ? "selected" : "" );?>>2018</option>
				<option value="2017" <?php echo (((isset($incoming['year']))&&($incoming['year']==2017)) ? "selected" : "" );?>>2017</option>
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
<br clear='both'>
<?php echo $html; ?>

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