<?php
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("utils-vendor.php");

function getUserName($id) {
	$str="";
	$sql="SELECT * FROM User WHERE User_ID=".$id;
	//echo $sql."<br>";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$str.=$row['Login'];
	}
	return $str;
}
function showStatisticTopList($incoming) {
	$res="";
	$clients=array();
	$clientsord=array();
	$clientsphone=array();
	$clientsaddr=array();
	$sql="SELECT Netshop_OrderGoods.*,Message51.User_ID,Message51.Phone,Message51.Email,Message51.Region,Message51.Town FROM Netshop_OrderGoods
		INNER JOIN Message51 ON (Netshop_OrderGoods.Order_ID=Message51.Message_ID)
		INNER JOIN Message57 ON (Netshop_OrderGoods.Item_ID=Message57.Message_ID)
		WHERE Message51.Created BETWEEN '".date("Y-m-d 00:00:00", strtotime($incoming['min']))."' AND '".date("Y-m-d 23:59:59", strtotime($incoming['max']))."'
			AND Message51.Status=4 AND Message57.supplier=".$incoming['supplier']."
		ORDER BY Message51.Message_ID ASC";
		
	//echo $sql."<br>";
	$result=mysql_query($sql);
	$j=0;
	while ($row = mysql_fetch_array($result)) {
		if ($j!=$row['Order_ID']) {
			$clients[$row['User_ID']]=$clients[$row['User_ID']]+$row['ItemPrice']*$row['Qty'];
			$clientsord[$row['User_ID']]=$clientsord[$row['User_ID']]."<a target='_blank' href='/netcat/message.php?catalogue=1&sub=57&cc=53&message=".$row['Order_ID']."'>".$row['Order_ID']."</a>,";
			$clientsphone[$row['User_ID']]=$row['Phone'];
			$clientsaddr[$row['User_ID']]=$row['Region']." ".$row['Town'];
		} 
		$j=$row['Order_ID'];
	}
	$sql="SELECT Retails_goods.*,Retails.user_id FROM Retails_goods
		INNER JOIN Retails ON (Retails_goods.retail_id=Retails.id)
		INNER JOIN Message57 ON (Retails_goods.item_id=Message57.Message_ID)
		WHERE Retails.created BETWEEN '".date("Y-m-d 00:00:00", strtotime($incoming['min']))."' AND '".date("Y-m-d 23:59:59", strtotime($incoming['max']))."'
			AND Message57.supplier=".$incoming['supplier']." AND not Retails.user_id=-1
		ORDER BY Retails.id ASC";
		
	//echo $sql."<br>";
	$result=mysql_query($sql);
	$j=0;
	while ($row = mysql_fetch_array($result)) {
		
		$clients[$row['user_id']]=$clients[$row['user_id']]+$row['itemprice']*$row['qty'];
		$clientsord[$row['user_id']]=$clientsord[$row['user_id']]."<a target='_blank' href='/netcat/modules/netshop/interface/retail-edit.php?id=".$row['retail_id']."&start=1'>".$row['retail_id']."</a>,";
	}
	//print_r($clients);
	//echo "<br><br>";
	arsort($clients);
	//print_r($clients);
	$res.="<table cellpadding='2' cellspacing='0' border='1'>
	<tr><td><b>ID клиента</b></td>
		<td><b>ФИО</b></td>	
		<td><b>Телефон</b></td>
		<td><b>Адрес</b></td>
		<td><b>Сумма покупок</b></td>
		<td><b>Заказы</b></td>
		
	</tr>";
	foreach($clients as $key => $val) {
		if ($key!=-1) {
			$res.="<tr><td>{$key}</td>";
			$res.="<td><a target='_blank' href='/netcat/modules/netshop/interface/clients.php?action=history&cid={$key}'>".getUserName($key)."</a></td>";
			$res.="<td>{$clientsphone[$key]}</td>";
			$res.="<td>{$clientsaddr[$key]}</td>";
			$res.="<td>{$val}</td>";
			$res.="<td>".$clientsord[$key]."</td>
			</tr>";
		}
	}
	return $res;
}
function printTblStat($order,$tbl) {
	$html="";
	
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
		case "filter":
			$html.=showStatisticTopList($incoming);
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
	<title>Статистика продаж по клиентам.</title>
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
	<h1>Статистика продаж по клиентам.</h1>
	<form name="frm1" id="frm1" action="/netcat/modules/netshop/interface/statistic-top.php" method="post">
	<input type="hidden" name="action" id="action" value="filter">
	<table cellpadding="2" cellspacing="0" border="1">
		<tr><td>Поставщик:</td>
			<td><?php echo selectSupplier($incoming); ?></td></tr>
		<tr><td colspan="2">
		<table cellpadding="0" cellspacing="5" border="0"><tr>
				<td>с</td>
				<td><input name="min" value="<?php echo isset($incoming['min']) ? date("d.m.Y", strtotime($incoming['min'])) : "01.".date("m.Y") ?>" class="datepickerTimeField"></td>
				<td>по</td>
				<td><input name="max" value="<?php echo isset($incoming['max']) ? date("d.m.Y", strtotime($incoming['max'])) : date("d.m.Y") ?>" class="datepickerTimeField"></td>
		</tr></table>
		</td></tr>
		<tr><td colspan="2"><input type="submit" value="Показать"></td></tr>
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