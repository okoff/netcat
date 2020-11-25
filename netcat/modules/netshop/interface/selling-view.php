<?php
// 21.05.2014 Elen
// накладные под реализацию. просмотр оплат, сохранение оплат
include_once ("../../../../vars.inc.php");
include_once ("utils-selling.php");

session_start();

function getNewPayments($incoming) {
	$tmp="";
	//print_r($incoming);
	if ((isset($incoming['supplier']))&&($incoming['supplier']!="")) {
		$tmp=" AND vendor_id=".intval($incoming['supplier']);
	}
	$itog=0;
	$sql="SELECT Waybills_selling.*,Waybills_goods.originalprice,Waybills.vendor_id,Message57.ItemID,Message57.Name FROM Waybills_selling 
		INNER JOIN Waybills ON (Waybills_selling.waybill_id=Waybills.id)
		INNER JOIN Message57 ON (Message57.Message_ID=Waybills_selling.item_id)
		INNER JOIN Waybills_goods ON (Waybills_selling.waybill_id=Waybills_goods.waybill_id AND Waybills_selling.item_id = Waybills_goods.item_id)
		WHERE NOT Waybills_selling.payment_id=0 {$tmp} ORDER BY ItemID ASC ";
	//echo "<br>".$sql;
	$html="<table cellpadding='2' cellspacing='0' border='1'>
		<tr style='font-weight:bold;'>
			<td>Арт.</td>
			<td>Название</td>
			<td>Поставщик</td>
			<td>Кол.</td>
			<td>Цена закуп.</td>
			<td>Сумма</td>
			<td>Дата оплаты</td>
		</tr>";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$html.="<tr>
				<td>{$row['ItemID']}</td>
				<td>{$row['Name']}</td>
				<td>".getSupplier($row['vendor_id'])."</td>
				<td>{$row['qty']}</td>
				<td style='text-align:right;'>{$row['originalprice']}</td>
				<td style='text-align:right;'>".($row['originalprice']*$row['qty'])."</td>
				<td>".date("d.m.Y", strtotime($row['created']))."</td>
			</tr>";
			$itog=$itog+$row['originalprice']*$row['qty'];
			
		}
	}
	$html.="<tr><td colspan='5' style='text-align:right;font-weight:bold;'>Итого:</td><td><b>{$itog}</b></td><td>&nbsp;</td></tr>";
	$html.="</table>";
	$html.="<form action='".$_SERVER['SCRIPT_NAME']."' method='post' id='frm2'>
		<input type='hidden' name='action' id='action' value='save'>
		<input type='hidden' name='supplier' value='".(((isset($incoming['supplier']))&&($incoming['supplier']!="")) ? $incoming["supplier"] : "" )."'>
		<input type='submit' value='Сохранить'>
		</form>";
	return $html;
}

// ------------------------------------------------------------------------------------------------
	include_once ("utils.php");
	$incoming = parse_incoming();
	//print_r($incoming);
	$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
	if (!$con) {
		die('Could not connect: ' . mysql_error());
	}
	
	mysql_select_db($MYSQL_DB_NAME, $con);
	//mysql_set_charset("cp1251", $con);
	mysql_set_charset("utf8", $con);
	$where="";
	$show=1;
	$preh1="";
	//print_r($incoming);
	switch ($incoming['action']) {
		default:
			$html.=getNewPayments($incoming);
			break;
	}
	

?>
<!DOCTYPE html>
<html>
<head>
	<title>Списания по накладным</title>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	</style>
	<script type="text/javascript">
	function chkAction() {
		//alert("chk");
		//document.getElementById("action").value="";
		//document.getElementById("frm1").submit();
		return true;
	}
	</script>
</head>
<body>
	
<?php
if ((isset($_SESSION['nc_token_rand'])) || ((isset($_SESSION['insideadmin'])) && ($_SESSION['insideadmin']==1))) {
	echo printMenu();
	if ($show) {
?>
	<h1>Списания по накладным. Оплаты. <?php echo $preh1; ?></h1>
	<form action="<?php echo $_SERVER["REQUEST_URI"]; ?>" method="post" id="frm1" name="frm1"  onsubmit="return chkAction();">
	<table cellspacing="0" cellpadding="1" border="1">
	<tr><td>Поставщик</td><td><?php echo selectSupplier($incoming,1,1); ?></td></tr>
	<tr><td colspan="2"><input type="submit" value="Показать"></td></tr>
	</table><br>
	</form>
	<?php 
	}
	echo $html; 
} else {
	echo "<p>У вас нет прав для просмотра этой страницы</p><p><a href='/netcat/modules/netshop/interface/login.php'>Вход</a></p>";
}
?>
</body>
</html>
<?php

mysql_close($con);
?>
