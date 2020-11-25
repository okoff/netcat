<?php
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
	
function showListFiles() {
	$res="<table cellpadding='2' cellspacing='0' border='1'>";
	$sql="SELECT * FROM Netshop_PostHistoryFiles ORDER BY id ASC";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		//print_r($row);
		$res.="<tr><td>".$row["id"]."</td>
		<td>".date("d.m.Y", strtotime($row["created"]))."</td>
		<td>{$row['name']}</td>
		<td>".(($row['checked']==1) ? "<a href='/netcat/modules/netshop/interface/order-post-history.php?action=read&fid={$row['id']}'>результат</a>" : "<a href='/netcat/modules/netshop/interface/order-post-history.php?action=process&fid={$row['id']}&f={$row['name']}'>начать обработку файла</a>")."</td>
		</tr>";
	}
	$res.="</table>";
	return $res;
}		
function showOrdersOnPost($incoming) {
	$res="";
	$sql="SELECT * FROM Netshop_PostFiles WHERE created BETWEEN '".date("Y-m-d", strtotime($incoming['min']))."' AND '".date("Y-m-d", strtotime($incoming['max']))."' ";
	//echo $sql;
	$res.="<table cellpadding='2' cellspacing='0' border='1'>
		<tr style='font-weight:bold;'><td>#</td><td>#</td><td>Дата</td><td>Кол. заказов</td><td>Кол. товаров</td><td>Страховая сумма</td><td>Сумма НП</td><td>Доставка</td>
		<td>Кол.</td><td>Почта. Страховая сумма</td><td>Почта. Сумма НП</td><td>Почта. Доставка</td>";
	$result=mysql_query($sql);
	$reqins=$reqnp=$reqdlv=0;
	$ansins=$ansnp=$ansdlv=0;
	$itotal=$itotal1=0;
	$ords=0;
	$ords1=0;
	$k=1;
	while ($row = mysql_fetch_array($result)) {
		//print_r($row);
		$itl=$itl1=0;
		$reqins=$reqins+doubleval($row['reqinsurance']);
		$reqnp=$reqnp+doubleval($row['reqnp']);
		$reqdlv=$reqdlv+doubleval($row['reqdeliverycost']);
		$ansins=$ansins+doubleval($row['ansinsurance']);
		$ansnp=$ansnp+doubleval($row['ansnp']);
		$ansdlv=$ansdlv+doubleval($row['ansdeliverycost']);
		$req=explode(";", $row['postfile']);
		$ords=$ords+count($req)-1;
		foreach($req as $ord) { 
			$itl=$itl+getCountCart($ord);
		}
		$ans=explode(";", $row['answer']);
		foreach($ans as $ord) { 
			$itl1=$itl1+getCountCart($ord);
		}
		$ords1=$ords1+count($ans)-1;
		$res.="<tr><td><b>{$k}</b></td><td>{$row['id']}</td><td>".date("d.m.Y", strtotime($row['created']))."</td>
		<td>".(count($req)-1)."</td><td>{$itl}<!--{$row['postfile']}--></td>
		<td>{$row['reqinsurance']}</td>
		<td>{$row['reqnp']}</td>
		<td>{$row['reqdeliverycost']}</td>
		<td>".(count($ans)-1)." [{$itl1}]<!--{$row['answer']}--></td>
		<td>{$row['ansinsurance']}</td>
		<td>{$row['ansnp']}</td>
		<td>{$row['ansdeliverycost']}</td>
		</tr>";
		$itotal=$itotal+$itl;
		$itotal1=$itotal1+$itl1;
		//$res.="<h2>".date("d.m.Y", strtotime($row["created"]))." {$row['name']}</h2>";
		$k=$k+1;
	}
	$res.="<tr><td colspan='3'><b>Итого:</b></td><td><b>{$ords}</b></td><td><b>{$itotal}</b></td><td><b>".str_replace(","," ",number_format($reqins))."</b></td>
		<td><b>".str_replace(","," ",number_format($reqnp))."</b></td><td><b>".str_replace(","," ",number_format($reqdlv))."</b></td>
	<td><b>{$ords1} [{$itotal1}]</b></td><td><b>".str_replace(","," ",number_format($ansins))."</b></td>
		<td><b>".str_replace(","," ",number_format($ansnp))."</b></td><td><b>".str_replace(","," ",number_format($ansdlv))."</b></td></tr>";
	$res.="</table>";
	//$res.="<p>Всего заказов: {$ords}</p>";
	$res.="<p>Средняя стоимость заказа: ".$reqins/$ords."</p>";
	$res.="<p>Средняя стоимость товара: ".$reqins/$itotal."</p>";
	/*$res.="<table cellpadding='2' cellspacing='0' border='1'>";
	$sql="SELECT * FROM Netshop_PostHistory WHERE file_id={$fid} ORDER BY id ASC";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		//print_r($row);
		$res.="<tr><!--td>".$row["id"]."</td-->
		<td>".date("d.m.Y", strtotime($row["created"]))."</td>
		<td>{$row['Order_ID']}</td>
		<td>{$row['status_id']}</td>
		<td>".(($row['status_id']!=8) ? "<b>{$row['status']}</b>" : $row['status'] )."</td>
		<td>{$row['address']}</td>
		</tr>";
	}
	$res.="</table>";*/
	return $res;
}	
	
	$UploadDir=$_SERVER['DOCUMENT_ROOT'].'/netcat_files/postfiles/trace/';
	$DownloadDir='/netcat_files/postfiles/trace/';

//if (!(((isset($_SESSION['nc_token_rand'])) || ((isset($_SESSION['insideadmin'])) && ($_SESSION['insideadmin']==1))))) {
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
			$html.=showOrdersOnPost($incoming);
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
	<title>Статистика по почтовым отправлениям</title>
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
	<h1>Статистика по почтовым отправлениям</h1>
	<form name="frm1" id="frm1" action="/netcat/modules/netshop/interface/statistic-post.php" method="post">
	<input type="hidden" name="action" id="action" value="filter">
	<table border="0" cellpadding="0" cellspacing="5">
		<tr>
			<td>с</td>
			<td><input name="min" value="<?php echo isset($incoming['min']) ? date("d.m.Y", strtotime($incoming['min'])) : "01.".date("m.Y") ?>" class="datepickerTimeField"></td>
			<td>по</td>
			<td><input name="max" value="<?php echo isset($incoming['max']) ? date("d.m.Y", strtotime($incoming['max'])) : date("d.m.Y") ?>" class="datepickerTimeField"></td>
			<td><input type="submit" value="Показать"></td>
		</tr>
	</table>
	</form>
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
<?php
mysql_close();
?>