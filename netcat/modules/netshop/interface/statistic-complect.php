<?php
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("utils-vendor.php");
include_once ("utils-statistic.php");

function getItemNameById($id) {
	$name="";
	$sql="SELECT Name,ItemID FROM Message57 
		WHERE Message_ID={$id}";
	$r=mysql_query($sql);
	while ($row1=mysql_fetch_array($r)) {
		$name.="{$row1['ItemID']} {$row1['Name']}";
	}
	return $name;
}

function showComplectList($incoming) {
	$res="";
	//print_r($incoming);
	$res.="<table cellpadding='2' cellspacing='0' border='1'>
	<tr><td style='width:100px;'><b>Комплект</b></td>
		<td style='width:100px;'><b>Состав</b></td>
		<td style='width:100px;'><b>Кол.</b></td>
		<td style='width:100px;'><b>Заказ</b></td>
		<td style='width:100px;'><b>Розн. продажа</b></td>
		<td style='width:100px;'><b>Дата продажи</b></td>
		</tr>";
	
	$sql="SELECT Complects_off.*,Message57.Name,Message57.ItemID FROM Complects_off
		INNER JOIN Message57 ON (Complects_off.complect_id=Message57.Message_ID)
		ORDER BY created DESC";
	$r=mysql_query($sql);
	$tmp=0;
	while ($row1=mysql_fetch_array($r)) {
		$res.="<tr><td>".(($tmp!=$row1['complect_id']) ? "{$row1['ItemID']} {$row1['Name']}" : "&nbsp;")."</td>";
		$res.="<td>".getItemNameById($row1['item_id'])."</td>";
		$res.="<td>{$row1['qty']}</td>";
		$res.="<td>".(($row1['order_id']==0) ? "&nbsp;" : "<a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$row1['order_id']}' target='_blank'>{$row1['order_id']}</a>")."</td>";
		$res.="<td>".(($row1['retail_id']==0) ? "&nbsp;" : "<a href='/netcat/modules/netshop/interface/retail-edit.php?id={$row1['retail_id']}&start=1' target='_blank'>{$row1['retail_id']}</a>")."</td>";
		$res.="<td>".date("d.m.Y",strtotime($row1['created']))."</td>";
		$res.="</tr>";
		$tmp=$row1['complect_id'];
	}
	$res.="</table>";
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
			$html.=showComplectList($incoming);
			$html.="<br>";
			//$html.=showPriceGiftList($incoming);

			break;
		default:
			$html.=showComplectList($incoming);
			//$incoming['month']=date("m");
			//$incoming['year']=date("Y");
			//$html.=viewStatsGoods();
			break;
	}
?>
<!DOCTYPE html>
<html>
<head>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<title>Статистика по списанию комплектов</title>
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
	<h1>Статистика по списанию комплектов</h1>
	<!--form name="frm1" id="frm1" action="/netcat/modules/netshop/interface/statistic-complect.php" method="post">
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
	</form-->
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