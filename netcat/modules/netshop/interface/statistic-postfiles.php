<?php
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("utils-vendor.php");
	
function showPostFilesList($incoming) {
	$html="";
	$html.="<table cellpadding='2' cellspacing='0' border='1'>
	<tr>
		<td><b>#</b></td>
		<td><b>Дата создания</b></td>
		<!--td><b>Список заказов</b></td-->
		<td><b>Количество заказов</b></td>
		<td><b>Сумма</b></td>
	</tr>";
	//print_r($incoming);
	$fullcost=0;
	$sql="SELECT * FROM Netshop_PostFiles 
		WHERE created BETWEEN '".date("Y-m-d 00:00:00",strtotime($incoming['min']))."' AND '".date("Y-m-d 23:59:59",strtotime($incoming['max']))."'";
	//echo $sql;
	if ($res=mysql_query($sql)) {
		$i=1;
		while($row=mysql_fetch_array($res)) {
			$html.="<tr>
			<td>{$i}</td>
			<td>".date("d.m.Y",strtotime($row["created"]))."</td>";
		//	<td>".$row["postfile"]."</td>";
			$tmp=explode(";",$row["postfile"]);
			$n=count($tmp);
			//print_r($tmp);
			$ordercost=0;
			foreach($tmp as $t) {
				if ($t!="") {
					$ordercost=$ordercost+getOrderCost($t);
				}
			}
			//echo "<br>";
			$html.="<td>{$row['reqcount']}</td>";
			$html.="<td style='text-align:right;'>".($ordercost+$row['reqdeliverycost'])."</td>";
			$html.="</tr>";
			$i=$i+1;
			$fullcost=$fullcost+$ordercost;
		}
	}
	$html.="<tr><td colspan='3' style='text-align:right;'><b>Итого:</b></td><td style='text-align:right;'>{$fullcost}</td></tr>";
	$html.="</table>";
	return $html;
}
	
//=================================================================================================
// проверка авторизации ---------------------------------------------------------------------------
if (($_SESSION['insideadmin']!=1) && (!isset($_SESSION['nc_token_rand']))) {
	$url="/netcat/modules/netshop/interface/login.php";
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
			$html.=showPostFilesList($incoming);
			break;
		default:
			//$html.=showListFiles();
			break;
	}
?>
<!DOCTYPE html>
<html>
<head>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<title>Отчет по файлам для почты</title>
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
	<h1>Отчет по файлам для почты</h1>
	<form name="frm1" id="frm1" action="/netcat/modules/netshop/interface/statistic-postfiles.php" method="post">
	<input type="hidden" name="action" id="action" value="filter">
	<table cellpadding="2" cellspacing="0" border="1">
		<tr><td>
		<table cellpadding="0" cellspacing="5" border="0"><tr>
				<td>с</td>
				<td><input name="min" value="<?php echo isset($incoming['min']) ? date("d.m.Y", strtotime($incoming['min'])) : "01.".date("m.Y") ?>" class="datepickerTimeField"></td>
				<td>по</td>
				<td><input name="max" value="<?php echo isset($incoming['max']) ? date("d.m.Y", strtotime($incoming['max'])) : date("d.m.Y") ?>" class="datepickerTimeField"></td>
		</tr></table>
		</td>
		</tr>
		<tr><td><input type="submit" value="Показать"></td></tr>
	</table>
	</form><br>
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