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

function viewStatsGoods() {
	$res="";
	$thead="<tr style='font-weight:bold;'><td>Поставщик</td>";
	$dte="01.11.2013";
	$now=date("d.m.Y");
	$j=$i=0;
	//echo strtotime($dte)."|".strtotime(date("d.m.Y"))."<br>";
	while (strtotime($now)>strtotime($dte)) {
		$now=date("d.m.Y",mktime(0, 0, 0, date("m",strtotime($now))-1, 1, date("Y",strtotime($now))));
		$thead.="<td>".date("m.Y",strtotime($now))."</td>";
		$j=$j+1;
	}
	//echo $dte;
	$thead.="</tr>";
	//$res="<table cellpadding='2' cellspacing='0' border='1'>";
	$sql="SELECT Supplier_ID,Supplier_Name FROM Classificator_Supplier WHERE Checked=1 AND Statistic=1 ORDER BY Supplier_Name ASC";
	$r=mysql_query($sql);
	$itog=0;
	$itogz=0;
	$itogstr=array();
	while ($row1=mysql_fetch_array($r)) {
		$i=0;
		$res.="<tr><td>{$row1['Supplier_Name']}</td>";
		$sql="SELECT * FROM Stats_Goods WHERE supplier_id={$row1['Supplier_ID']} ORDER BY created DESC";
		//echo $sql."<br>";
		$rs=mysql_query($sql);
		while ($row=mysql_fetch_array($rs)) {
			if ($row['created']) {
				$res.="<td style='text-align:center;'>{$row['residue_price']}&nbsp;|&nbsp;{$row['residue_pricez']}</td>";
				$itog=$itog+$row['residue_price'];
				$itogz=$itogz+$row['residue_pricez'];
			}
			//$res.="<td style='text-align:center;'>".(($row['created']) ? "<img src='/images/icons/ok.png'>" : "-")."</td>";
			$i=$i+1;
			$itogstr[$i]=$itog."&nbsp;|&nbsp;".$itogz;
		}
		//$j=2;
		while ($i<$j) {
			$res.="<td>&nbsp;</td>";
			$i=$i+1;
		}
		$res.="</tr>";
	}
	$res="<table cellpadding='2' cellspacing='0' border='1'>".$thead."</tr>".$res;
	$res.="<tr><td><b>Итого</b></td>";
	$i=0;
	while ($i<$j) {
		$i=$i+1;
		$res.="<td>{$itogstr[$i]}</td>";
	}
	//"</table>";
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
			$html.=showStatisticList($incoming);

			break;
		default:
			$incoming['month']=date("m");
			$incoming['year']=date("Y");
			$html.=viewStatsGoods();
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
	<h1>Статистика по поставщикам. Просмотр остатков.</h1>
	<form name="frm1" id="frm1" action="/netcat/modules/netshop/interface/statistic-month-remains.php" method="post">
	<input type="hidden" name="action" id="action" value="filter">
	<!--table cellpadding="2" cellspacing="0" border="1">
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
				<option value="2015" <?php echo (((isset($incoming['year']))&&($incoming['year']==2016)) ? "selected" : "" );?>>2016</option>
				<option value="2015" <?php echo (((isset($incoming['year']))&&($incoming['year']==2015)) ? "selected" : "" );?>>2015</option>
				<option value="2014" <?php echo (((isset($incoming['year']))&&($incoming['year']==2014)) ? "selected" : "" );?>>2014</option>
				<option value="2013" <?php echo (((isset($incoming['year']))&&($incoming['year']==2013)) ? "selected" : "" );?>>2013</option>
				<option value="2012" <?php echo (((isset($incoming['year']))&&($incoming['year']==2012)) ? "selected" : "" );?>>2012</option>
				<option value="2011" <?php echo (((isset($incoming['year']))&&($incoming['year']==2011)) ? "selected" : "" );?>>2011</option>
			</select></td>
		</tr>
		<tr><td colspan="2"><input type="submit" value="Показать"></td></tr>
	</table-->
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