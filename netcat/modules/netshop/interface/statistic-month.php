<?php
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("utils-vendor.php");
include_once ("utils-statistic.php");

function showStatisticList($incoming) {
	$res="";
	//print_r($incoming);
	$res.="<table cellpadding='2' cellspacing='0' border='1'>
	<tr><td style='width:100px;'><b>Поставщик</b></td>
		<td style='width:100px;'><b>Кол. проданных штук</b></td>
		<td style='width:100px;'><b>Сумма<br>реализации</b></td>
		<td style='width:100px;'><b>Сумма<br>реализации закуп.</b></td>
		<td style='width:100px;'><b>Сред. стоимость<br>покупки</b></td>
		<td style='width:100px;'><b>% от итого</b></td>
		<td style='width:100px;'><b>Остаток</b></td><td><b>Сумма остатка</b></td>
		<td style='width:100px;'><b>Сумма остатка<br>закуп.</b></td>
		<td style='width:100px;'><b>% от итого</b></td>
		<td style='width:100px;'><b>% наценки</b></td></tr>";
	
	$sql="SELECT Supplier_ID,Supplier_Name FROM Classificator_Supplier WHERE Checked=1 AND Statistic=1 ORDER BY Supplier_Name ASC";
	$r=mysql_query($sql);
	while ($row1=mysql_fetch_array($r)) {
		$res.="<tr><td>{$row1['Supplier_Name']}</td>";
		$res.=showStatisticVendorForMonth($row1['Supplier_ID'], $incoming['year'],$incoming['month']);
		$res.=showResidueForVendorForMonth($row1['Supplier_ID'], $incoming['year'],$incoming['month']);
	}
	$res.="</table>
	
	<br>
<form id='frm2' name='frm2' action='/netcat/modules/netshop/interface/statistic-month.php' method='post'>
<input type='hidden' id='action' name='action' value='save'>
<input type='hidden' id='year' name='year' value='".((isset($incoming['year'])) ? $incoming['year'] : "")."'>
<input type='hidden' id='month' name='month' value='".((isset($incoming['month'])) ? $incoming['month'] : "")."'>
<input type='submit' value='Сохранить'>
</form>";
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
	
	while ($row1=mysql_fetch_array($r)) {
		$i=0;
		$res.="<tr><td>{$row1['Supplier_Name']}</td>";
		$sql="SELECT created FROM Stats_Goods WHERE supplier_id={$row1['Supplier_ID']} ORDER BY created DESC";
		//echo $sql."<br>";
		$rs=mysql_query($sql);
		while ($row=mysql_fetch_array($rs)) {
			$res.="<td style='text-align:center;'>".(($row['created']) ? "<img src='/images/icons/ok.png'>" : "-")."</td>";
			$i=$i+1;
			
		}
		//$j=2;
		while ($i<$j) {
			$res.="<td>&nbsp;</td>";
			$i=$i+1;
		}
		$res.="</tr>";
	}
	$res="<table cellpadding='2' cellspacing='0' border='1'>".$thead."</tr>".$res."</table>";
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
		case "save":
			//print_r($incoming);
			if ((isset($incoming['year']))&&(isset($incoming['month']))) {
				$html.="<p><a href='/netcat/modules/netshop/interface/statistic-month.php'><b>&lt;&lt; Назад</b></a></p>";
				$sql="SELECT Supplier_ID,Supplier_Name FROM Classificator_Supplier WHERE Checked=1 AND Statistic=1 ORDER BY Supplier_Name ASC";
				$r=mysql_query($sql);
				while ($row1=mysql_fetch_array($r)) {
					$sold=getSold($row1['Supplier_ID'],$incoming['year'],$incoming['month']);
					$tt=explode(":",$sold);
					
					$residue=getResidue($row1['Supplier_ID'],$incoming['year'],$incoming['month']);
					$rsd=explode(":",$residue);
					
					$sql="INSERT INTO Stats_Goods (supplier_id, created, sold_count,sold_price,sold_pricez,residue_count,residue_price,residue_pricez) 
						VALUES ({$row1['Supplier_ID']},'{$incoming['year']}-{$incoming['month']}-".cal_days_in_month(CAL_GREGORIAN, $incoming['month'], $incoming['year'])." 23:59:59',
							{$tt[0]},{$tt[1]},{$tt[2]},{$rsd[0]},{$rsd[1]},{$rsd[2]})";
					//echo $sql;
					if (!mysql_query($sql)) {
						die($sql." Error: ".mysql_error());
					}
					$html.="Статистика по {$row1['Supplier_Name']} за {$incoming['month']}.{$incoming['year']} сохранена!<br>";
				}
				$html.="<p><a href='/netcat/modules/netshop/interface/statistic-month.php'><b>&lt;&lt; Назад</b></a></p>";
			}
			break;
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
	<h1>Статистика по поставщикам за текущий месяц. Сохранение остатков.</h1>
	<form name="frm1" id="frm1" action="/netcat/modules/netshop/interface/statistic-month.php" method="post">
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
			<?php
				$y = date("Y");
				for ($o = $y; $o > $y-5; $o--) {
					echo "<option value=\"".$o."\" ". (((isset($incoming['year']))&&($incoming['year']==$o)) ? "selected" : "" ).">".$o."</option>";
				}
			?>
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