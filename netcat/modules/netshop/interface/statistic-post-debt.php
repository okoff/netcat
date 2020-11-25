<?php
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
	
function ifOrderPaid($id) {
	$res=0;
	$sql="SELECT paid FROM Message51 WHERE Message_ID=".$id;
	$result=mysql_query($sql);
	if ($row = mysql_fetch_array($result)) {
		$res=$row['paid'];
	}
	return $res;
}

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
function showOrdersOnPostDebt($incoming) {
	$res="";
	$sql="SELECT * FROM Netshop_PostFiles WHERE answer<>'' AND created BETWEEN '".date("Y-m-d", strtotime($incoming['min']))."' AND '".date("Y-m-d", strtotime($incoming['max']))."' 
		ORDER BY id DESC";
	//echo $sql;
	$res.="<table cellpadding='2' cellspacing='0' border='1'>";
	$res.="<tr style='font-weight:bold;'><td>#</td><td>#</td><td>Дата отправки</td><td>Дата отгрузки</td><td>Кол. заказов</td><td>Общая сумма по заказам</td>
		<td>Кол. отгруженных товаров</td>
		<td>Кол. оплаченных заказов</td><td>Сумма оплаченных заказов</td>
		<td>Кол. неоплачен. заказов</td>
		<td>Ждем возврат</td>
		<td>Возврат получен</td>
		<td>Долг почты</td>
		</tr>";
	//	<td>Кол.</td><td>Почта. Страховая сумма</td><td>Почта. Сумма НП</td><td>Почта. Доставка</td>";
	$result=mysql_query($sql);
	$reqins=$reqnp=$reqdlv=0;
	$ansins=$ansnp=$ansdlv=0;
	$itotal=$itotal1=0;
	$itog_orders=$itog_summ=$itog_items=$itog_itemspaid=$itog_itemsunpaid=$itog_summpaid=$itog_summunpaid=$itog_summunpaid_ret=$itog_summunpaid_retwait=0;
	$ords1=0;
	$unpaid="";
	$k=1;
	while ($row = mysql_fetch_array($result)) {
		$itl1=0;
		$itlpaid=$itlunpaid=$itlunpaid_ret=$itlunpaid_retwait=0;
		$summ=0;
		$summpaid=$summunpaid=0;
		$unpaid=$retwait=$retget="";
		$ans=explode(";", $row['answer']);
		foreach($ans as $ord) { 
			if ($ord) {
				$itl1=$itl1+getCountCart($ord);
				$summord=getOrderCostNP($ord);
				$summ=$summ+$summord;
				if (ifOrderPaid($ord)) {
					$summpaid=$summpaid+$summord;
					$itlpaid=$itlpaid+1;
				} else {
					
					$order_st=getOrderStatus($ord);
					if ($order_st==7) {
						$itlunpaid_retwait=$itlunpaid_retwait+1;
						$retwait.=" <a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$ord}' target='_blank'>".$ord."</a>";
					} if ($order_st==15) {
						$itlunpaid_retwait=$itlunpaid_retwait+1;
						$retwait.=" <a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$ord}' target='_blank'>".$ord."</a>";
					} else if ($order_st==12){
						$itlunpaid_ret=$itlunpaid_ret+1;
						$retget.=" <a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$ord}' target='_blank'>".$ord."</a>";
					} else {
						$summunpaid=$summunpaid+$summord;
						$itlunpaid=$itlunpaid+1;
						$unpaid.=" <a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$ord}' target='_blank'>".$ord."</a>";
					}
				}
			}
		}
		$itog_orders=$itog_orders+count($ans)-1;
		$itog_summ=$itog_summ+$summ;
		$itog_items=$itog_items+$itl1;
		$itog_itemspaid=$itog_itemspaid+$itlpaid;
		$itog_itemsunpaid=$itog_itemsunpaid+$itlunpaid;
		$itog_itemsunpaid_ret=$itog_itemsunpaid_ret+$itlunpaid_ret;
		$itog_itemsunpaid_retwait=$itog_itemsunpaid_retwait+$itlunpaid_retwait;
		$itog_summpaid=$itog_summpaid+$summpaid;
		$itog_summunpaid=$itog_summunpaid+$summunpaid;
		//$itog_summunpaid_ret=$itog_summunpaid_ret+$summunpaid;
		
		$res.="<tr><td><b>{$k}</b></td><td>{$row['id']}</td><td>".date("d.m.Y", strtotime($row['created']))."</td>
		<td>".date("d.m.Y", strtotime($row['answered']))."</td>
		<td style='text-align:right;'>".(count($ans)-1)."<!-- {$row['answer']}--></td>
		<td style='text-align:right;'>".number_format($summ,0,'.',' ')."</td>
		<td style='text-align:right;'>{$itl1}</td>
		<td style='text-align:right;'>{$itlpaid}</td>
		<td style='text-align:right;'>".number_format($summpaid,0,'.',' ')."</td>
		<td><b>{$itlunpaid}</b> ".(($unpaid) ? "[{$unpaid}]" : "")."</td>
		<td><b>{$itlunpaid_retwait}</b> ".(($retwait) ? "[{$retwait}]" : "")."</td>
		<td><b>{$itlunpaid_ret}</b> ".(($retget) ? "[{$retget}]" : "")."</td>
		<td style='text-align:right;'>".number_format($summunpaid,0,'.',' ')."</td>
		</tr>";
		//$res.="<h2>".date("d.m.Y", strtotime($row["created"]))." {$row['name']}</h2>";
		$k=$k+1;
	}
	$res.="<tr style='font-weight:bold;'><td colspan='4'>Итого:</td>
		<td style='text-align:right;'>{$itog_orders}</td>
		<td style='text-align:right;'>".number_format($itog_summ,0,'.','  ')."</td>
		<td style='text-align:right;'>{$itog_items}</td>
		<td style='text-align:right;'>{$itog_itemspaid}</td>
		<td style='text-align:right;'>".number_format($itog_summpaid,0,'.',' ')."</td>
		<td style='text-align:right;'>{$itog_itemsunpaid}</td>
		<td style='text-align:right;'>{$itog_itemsunpaid_retwait}</td>
		<td style='text-align:right;'>{$itog_itemsunpaid_ret}</td>
		<td style='text-align:right;'>".number_format($itog_summunpaid,0,'.',' ')."</td>";
//		<td><b>".str_replace(","," ",number_format($reqins))."</b></td>
//		<td><b>".str_replace(","," ",number_format($reqnp))."</b></td><td><b>".str_replace(","," ",number_format($reqdlv))."</b></td>
//	<td><b>{$ords1} [{$itotal1}]</b></td><td><b>".str_replace(","," ",number_format($ansins))."</b></td>
//		<td><b>".str_replace(","," ",number_format($ansnp))."</b></td><td><b>".str_replace(","," ",number_format($ansdlv))."</b></td></tr>";
	$res.="</table>";

	return $res;
}	

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
		$html.=showOrdersOnPostDebt($incoming);
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
	<h1>Статистика по почтовым отправлениям (долги почты)</h1>
	<form name="frm1" id="frm1" action="/netcat/modules/netshop/interface/statistic-post-debt.php" method="post">
	<input type="hidden" name="action" id="action" value="filter">
	<table border="0" cellpadding="0" cellspacing="5">
		<tr>
			<td>с</td>
			<td><input name="min" value="<?php echo isset($incoming['min']) ? date("d.m.Y", strtotime($incoming['min'])) : "01.01.2013" ?>" class="datepickerTimeField"></td>
			<td>по</td>
			<td><input name="max" value="<?php echo isset($incoming['max']) ? date("d.m.Y", strtotime($incoming['max'])) : date("d.m.Y") ?>" class="datepickerTimeField"></td>
			<td><input type="submit" value="Показать"></td>
		</tr>
	</table>
	</form>
<br clear='both'>
<br clear='both'>
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