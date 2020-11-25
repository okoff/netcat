<?php
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("utils-retail.php");
	
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
function showRetailList($incoming) {
	$res="";
	//print_r($incoming);
	$strw="";
	if ((isset($incoming["fixed"]))&&($incoming["fixed"]==1)) {
		$strw=" AND Retails.fixed=1 ";
	}
	if ((isset($incoming["cash"]))&&($incoming["cash"]==1)&&(isset($incoming["nocash"]))&&($incoming["nocash"]==1)) {
		$strw.=" AND (Retails.cash=0 OR Retails.cash=1)";
	} else {
		if ((isset($incoming["cash"]))&&($incoming["cash"]==1)) {
			$strw.=" AND Retails.cash=1 ";
		}
		if ((isset($incoming["nocash"]))&&($incoming["nocash"]==1)) {
			$strw.=" AND Retails.cash=0 ";
		}
	}
	$sql="SELECT * FROM Retails 
			WHERE created BETWEEN '".date("Y-m-d 00:00:00", strtotime($incoming['min']))."' AND '".date("Y-m-d 23:59:59", strtotime($incoming['max']))."' 
			{$strw}
			ORDER BY id DESC";
			
	if ((isset($incoming['manufacturer'])) && ($incoming['manufacturer']!="")) {
		$sql="SELECT Retails.* FROM Retails
				INNER JOIN Retails_goods ON ( Retails_goods.retail_id = Retails.id )
				INNER JOIN Message57 ON ( Retails_goods.item_id = Message57.Message_ID )
				WHERE Retails.created BETWEEN '".date("Y-m-d 00:00:00", strtotime($incoming['min']))."' AND '".date("Y-m-d 23:59:59", strtotime($incoming['max']))."'
				AND Message57.Vendor={$incoming['manufacturer']} {$strw}
				GROUP BY Retails.id
				ORDER BY Retails.id DESC";
			//echo $sql;
	}	
	if ((isset($incoming['articul'])) && ($incoming['articul']!="")) {
		$sql="SELECT Retails.* FROM Retails
				INNER JOIN Retails_goods ON ( Retails_goods.retail_id = Retails.id )
				INNER JOIN Message57 ON ( Retails_goods.item_id = Message57.Message_ID )
				WHERE Retails.created BETWEEN '".date("Y-m-d 00:00:00", strtotime($incoming['min']))."' AND '".date("Y-m-d 23:59:59", strtotime($incoming['max']))."'
				AND Message57.ItemID LIKE '{$incoming['articul']}' {$strw}
				GROUP BY Retails.id
				ORDER BY Retails.id DESC";
			//echo $sql;
	}	
	
	$result=mysql_query($sql);
	//echo $sql;
	$res.="<table cellpadding='2' cellspacing='0' border='1'>
	<tr><td><b>#</b></td><td><b>Дата</b></td><td><b>Оплачено</b></td><td><b>Наличные</b></td><td><b>К.</b></td>
		<td><b>Закрыт</b></td><td><b>Сумма</b></td><td><b>Сумма нал.</b></td><td><b>Состав заказа</b></td><td><b>Изменение</b></td>
		<td><b>Из заказа</b></td>
		</tr>";
	$allnum=mysql_num_rows($result);
	$j=mysql_num_rows($result);
	$itog=$itog1=0;
	$show=1;
	while ($row = mysql_fetch_array($result)) {
		//print_r($row);
		/*if ((isset($incoming['manufacturer'])) && ($incoming['manufacturer']!="")) {
			$sql="SELECT id FROM Retails_goods 
				INNER JOIN Message57 ON (Retails_goods.item_id=Message57.Message_ID)
				WHERE retail_id={$row['id']} AND Vendor={$incoming['manufacturer']} AND Message57.ItemID LIKE '%{$incoming['articul']}%'";
			//echo $sql;
			$rtmp=mysql_query($sql);
			if ($row1=mysql_fetch_array($rtmp)) {
				$show=1;
			} else {
				$show=0;
			}
		}*/
		//if ($show) {
			
			$res.="<tr><td><a href='/netcat/modules/netshop/interface/retail-edit.php?id={$row['id']}&start=1'><b>{$j}</b> [{$row['id']}]</a></td>
			<td>".date("d.m.Y H:i:s", strtotime($row['created']))."</td>
			<td>".(($row['paid']) ? "<img src='/images/icons/ok.png'>" : "-")."</td>
			<td>".(($row['cash']) ? "<img src='/images/icons/ok.png'>" : "-")."</td>
			<td>".(($row['fixed']) ? "<img src='/images/icons/ok.png'>" : "-")."</td>
			<td>".(($row['closed']) ? "<img src='/images/icons/ok.png'>" : "-")."</td>
			<td style='text-align:right;'>{$row['summ']}</td>
			<td style='text-align:right;'>{$row['summ1']}</td>
			<td>".printRetailCart($row['id'])."</td>
			<td>".($row['edited'] ? date("d.m.Y", strtotime($row['edited'])) : "&nbsp;")."</td>
			<td>".($row['order_id'] ? "<a target='_blank' href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$row['order_id']}'>{$row['order_id']}</a>" : "&nbsp;")."</td>
			</tr>";
			
			$itog=$itog+$row['summ'];
			$itog1=$itog1+$row['summ1'];
			$j=$j-1;
		//}
	}
	$res.="<tr><td colspan='6'><b>Итого:</b></td>
		<td style='text-align:right;'><b>".str_replace(","," ",number_format($itog))."</b></td>
		<td style='text-align:right;'><b>".str_replace(","," ",number_format($itog1))."</b></td><td colspan='3'>&nbsp;</td></tr>";
	$res.="</table>";
	$res.="<p>Всего заказов: {$allnum}</p>";
	$res.="<p>Средняя стоимость заказа: ".$itog/$allnum."</p>";
	

	$dte=strtotime($incoming['min']); //date("d.m.Y");
	//echo $dte."|".strtotime($incoming['max'])."<br>";
	while ($dte<=strtotime($incoming['max'])) {
		//$dte="19.05.2015";
		$sql="SELECT * FROM Retails 
				WHERE created BETWEEN '".date("Y-m-d 00:00:00", $dte)."' AND '".date("Y-m-d 23:59:59", $dte)."' 
				ORDER BY id DESC";
				
		$result=mysql_query($sql);
		//echo $sql;
		$res.="<h3>".date("d.m.Y",$dte)."</h3>";
		//echo $sql."<br>";
		/*$res.="<table cellpadding='2' cellspacing='0' border='1'>
		<tr><td><b>#</b></td><td><b>Дата</b></td><td><b>Оплачено</b></td><td><b>Наличные</b></td><td><b>К.</b></td>
			<td><b>Закрыт</b></td><td><b>Сумма</b></td><td><b>Состав заказа</b></td>
			</tr>";*/
		$allnum=0;//mysql_num_rows($result);
		$j=1; //mysql_num_rows($result);
		$jbeznal=$jnal=1;
		$itog=$beznal=$nal=0;
		$show=1;
		$res.="<table cellpadding='2' cellspacing='0' border='1'>
		<tr><td>#</td><td>сумма без нал.</td><td>сумма нал. чеков</td><td>Общая сумма</td></tr>";
		while ($row = mysql_fetch_array($result)) {
			// безнал, касса, наличные с безналом 
			if (($row['cash']==0)||($row['fixed']==1)) {
				(!$row['cash']) ? $beznal=$beznal+$row['summ']-$row['summ1'] : $nal=$nal+$row['summ'];
				(!$row['cash']) ? $jbeznal=$jbeznal+1 : $jnal=$jnal+1;
				$res.="<tr><td>{$j}</td>
					<td>".((!$row['cash']) ? ($row['summ']-$row['summ1']) : "-")."</td>
					<td>".((($row['cash']!=0)||($row['summ1']!=0)) ? (($row['summ1']!=0) ? $row['summ1'] : $row['summ']) : "-")."</td>
					<td>".($row['summ'])."</td></tr>"; //-$row['summ1']
				$j=$j+1;
			}
		}
		
		$res.="<tr><td>&nbsp;</td><td><b>{$beznal}</b></td><td><b>{$nal}</b></td><td><b>".($beznal+$nal)."</b></td></tr>";
		$res.="</table>";
		$res.="<p>Всего заказов: ".($j-1)."; по безналу ".($jbeznal-1)."</p>";
		$dte=mktime(0, 0, 0, date("m",$dte)  , date("d",$dte)+1, date("Y",$dte));
		//echo $dte."<br>";
	}

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
			$html.=showRetailList($incoming);
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
	<title>Статистика по розничным продажам</title>
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
	<h1>Статистика по розничным продажам</h1>
	<form name="frm1" id="frm1" action="/netcat/modules/netshop/interface/statistic-retail.php" method="post">
	<input type="hidden" name="action" id="action" value="filter">
	<table cellpadding="2" cellspacing="0" border="1">
		<tr><td colspan="5">
		<table cellpadding="0" cellspacing="5" border="0"><tr>
				<td>с</td>
				<td><input name="min" value="<?php echo isset($incoming['min']) ? date("d.m.Y", strtotime($incoming['min'])) : date("d.m.Y") ?>" class="datepickerTimeField"></td>
				<td>по</td>
				<td><input name="max" value="<?php echo isset($incoming['max']) ? date("d.m.Y", strtotime($incoming['max'])) : date("d.m.Y") ?>" class="datepickerTimeField"></td>
		</tr></table>
		</td></tr>
		
		<tr>
			<td style="text-align:right;">Поставщик:</td>
			<td><?php echo selectManufacturer($incoming); ?></td>
			<td>К. <input type='checkbox' value='1' id='fixed' name='fixed' <?php echo (((isset($incoming['fixed'])) && ($incoming['fixed']==1)) ? "checked" : "");  ?>></td>
			<td>Нал. <input type='checkbox' value='1' id='cash' name='cash' <?php echo (((isset($incoming['cash'])) && ($incoming['cash']==1)) ? "checked" : "");  ?>></td>
			<td>Безнал. <input type='checkbox' value='1' id='nocash' name='nocash' <?php echo (((isset($incoming['nocash'])) && ($incoming['nocash']==1)) ? "checked" : "");  ?>></td>
		</tr>
		<tr>
			<td style="text-align:right;">Артикул:</td>
			<td colspan="2"><input type="text" name="articul" id="articul" value="<?php echo (isset($incoming['articul']) ? $incoming['articul'] : ""); ?>"></td>
			<td>&nbsp;</td><td>&nbsp;</td>
		</tr>
		<tr><td colspan="5"><input type="submit" value="Показать"></td></tr>
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