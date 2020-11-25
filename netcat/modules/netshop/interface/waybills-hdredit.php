<?php
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("utils-retail.php");
include_once ("utils-waybill.php");
	
function editWbHeader($wb_id) {
	$html="";
	
	$sql="SELECT * FROM Waybills WHERE id=".intval($wb_id);
	$result=mysql_query($sql);
	if ($row = mysql_fetch_array($result)) {	
		//print_r($row);
	} else {
		die("Ошибка! Такой накладной нет!");
	}
	//echo $row['vendor_date'];
	$html.="<form id='frm2' name='frm2' action='{$_SERVER["REQUEST_URI"]}' method='post'>";
	$html.="<h3>Накладная #{$row['id']} ".printWaybillStatus($row['status_id'])."</h3>";
	$html.="<input type='hidden' id='id' name='id' value='{$row['id']}'>
<input type='hidden' id='action' name='action' value='save'>
<table cellpadding='2' cellspacing='0' border='1' style='width:1000px;'>
<tr><td>Название</td><td colspan='3'><input name='name' type='text' value='{$row['name']}' style='width:600px;'></td>
<td>Дата создания</td><td><input name='created' type='text' value='".((($row['created'])&&($row['created']!='1970-01-01')&&($row['created']!='0000-00-00')) ? date("d.m.Y",strtotime($row['created'])) : "")."'></td>
</tr>
<tr><td colspan='6'>Комментарий:<br>
	<input type='text' name='comments' id='comments' size='50'  value='{$row['comments']}' style='font-size:8pt;width:800px;'></td>
</tr>
<tr><td>Тип накладной</td><td>".selectWaybillType($row['type_id'])."</td>
	<td>Тип оплаты</td><td>".selectWaybillPayment($row['payment_id'])."</td>
	<td>Способ оплаты</td><td>".selectWaybillPaymentType($row['moneytype_id'])."</td></tr>
<tr><td>Поставщик</td><td>".selectSupplier(array('supplier'=>$row['vendor_id']))."</td>
	<td>Дата поставщика</td><td><input name='vendor_date' type='text' value='".((($row['vendor_date'])&&($row['vendor_date']!='1970-01-01')&&($row['vendor_date']!='0000-00-00')) ? date("d.m.Y",strtotime($row['vendor_date'])) : "")."'></td>
	<td colspan='2'>
	Организация:".selectOrganiz($row['organiz_id'])."
	<!--<input type='checkbox' name='put1c' id='put1c' value='1' ".(($row['put1c']==1) ? "checked" : "").">Передать в 1С
		<input type='text' name='put1cdate' id='put1cdate' value='".((($row['put1cdate'])&&(($row['put1cdate']!='1970-01-01 00:00:00'))&&($row['put1cdate']!='0000-00-00 00:00:00')) ? date("d.m.Y",strtotime($row['put1cdate'])) : "")."'>
	-->
	</td></tr>
</table>
<h3>Оплата</h3>
<table cellpadding='2' cellspacing='0' border='1' style='width:1000px;'>
<tr><td>Оплачено</td><td><input type='checkbox' name='paid' id='paid' value='1' ".(($row['paid']==1) ? "checked" : "")."></td>
	<td>Дата оплаты</td><td><input name='paid_date' type='text' value='".((($row['paid_date'])&&($row['paid_date']!='1970-01-01')&&($row['paid_date']!='0000-00-00')) ? date("d.m.Y",strtotime($row['paid_date'])) : "")."'></td></tr>
<tr><td colspan='4'>Комментарий к оплате<br>
	<input type='text' name='paid_comment' id='paid_comment' style='width:800px;' value='{$row['paid_comment']}' style='font-size:8pt;width:800px;'></td>
</table>
<h3>На сайте</h3>
<table cellpadding='2' cellspacing='0' border='1' style='width:1000px;'>
<tr><td>Показывать в новинках</td><td><input type='checkbox' name='onsite' id='onsite' value='1' ".(($row['onsite']) ? "checked" : "")."></td></tr>
<tr><td>Название</td><td><input type='text' name='title' id='title' value='{$row['title']}' style='width:800px;'></td></tr>
<tr><td>Краткое описание</td><td><textarea name='intro' style='width:800px;font-size:8pt;'>{$row['intro']}</textarea></td></tr>
<tr><td>Полное описание</td><td><textarea name='description' style='width:800px;font-size:8pt;'>{$row['description']}</textarea></td></tr>
</table><br>";

	$html.="<input type='submit' value='Сохранить'>";
	$html.="</form>";
	
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
			$show=0;
			($incoming['paid']==1) ? $paid=1 : $paid=0;
			($incoming['onsite']==1) ? $onsite=1 : $onsite=0;
			($incoming['put1c']==1) ? $put1c=1 : $put1c=0;
			$sql="UPDATE Waybills SET
				name='".quot_smart($incoming['name'])."', 
				created='".date("Y-m-d", strtotime(quot_smart($incoming['created'])))."', 
				vendor_id=".(($incoming['supplier']) ? $incoming['supplier'] : 0).", 
				vendor_date='".date("Y-m-d", strtotime(quot_smart($incoming['vendor_date'])))."', 
				type_id={$incoming['wbtype']}, 
				payment_id=".(($incoming['wbpayment']) ? $incoming['wbpayment'] : 0).", 
				moneytype_id=".(($incoming['wbpaymenttype']) ? $incoming['wbpaymenttype'] : 0).", 
				paid={$paid}, 
				paid_date='".(($incoming['paid_date']) ? date("Y-m-d", strtotime(quot_smart($incoming['paid_date']))) : "")."', 
				put1c={$put1c}, 
				put1cdate='".(($incoming['put1cdate']) ? date("Y-m-d", strtotime(quot_smart($incoming['put1cdate']))) : "")."', 
				comments='".quot_smart($incoming['comments'])."', 
				onsite={$onsite},
				title='".quot_smart($incoming['title'])."',
				intro='".quot_smart($incoming['intro'])."',
				description='".quot_smart($incoming['description'])."',
				organiz_id='".intval($incoming['organiz'])."'
				
			WHERE id=".intval($incoming['id']);
			//echo $sql."<br>";
			if (mysql_query($sql)) {	
				$html.="<h2>Описание накладной сохранено</h2>
				<p><a href='/netcat/modules/netshop/interface/waybills-list.php'>Перейти в список накладных</a></p>";
				//$showall=0;
			} else {
				die($sql."Ошибка: ".mysql_error());
			}
			break;
		case "find":
			$show=0;
			$html.=editWbHeader($incoming['wb_id']);
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
	<title>Статистика по товарам</title>
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
	<h1>Редактировать описание накладной.</h1>
	<?php
	if ($show==1) {
	?>
	<form name="frm1" id="frm1" action="<?php echo $_SERVER["REQUEST_URI"];?>" method="post">
	<input type='hidden' name="action" id="action" value="find">
	<table cellpadding="2" cellspacing="0" border="1">
		<tr>
			<td style="text-align:right;"># накладной:</td>
			<td><input type="text" value="<?php echo (isset($incoming['item_id']) ? $incoming['item_id'] : "" ); ?>" name="wb_id" id="wb_id"></td>
		</tr>
		<tr><td colspan="2"><input type="submit" value="Найти"></td></tr>
	</table>
	</form>
<?php 
}
echo $html; 
?>

</body>
</html>