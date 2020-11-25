<?php
// 12.05.2014 Elen
// накладные под реализацию. проверка списаний
include_once ("../../../../vars.inc.php");

session_start();

// ------------------------------------------------------------------------------------------------
	include_once ("utils.php");
	$incoming = parse_incoming();
	$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
	if (!$con) {
		die('Could not connect: ' . mysql_error());
	}
	
	mysql_select_db($MYSQL_DB_NAME, $con);
	//mysql_set_charset("cp1251", $con);
	mysql_set_charset("utf8", $con);
	
	//print_r($incoming);
	$html="";
	$html.="<h1>Накладная #".intval($incoming['wb'])."</h1>";
	$sql="SELECT * FROM Message57 WHERE Message_ID=".intval($incoming['item']);
	if ($result=mysql_query($sql)) {
		while ($row = mysql_fetch_array($result)) {
			$html.="<p>Товар [{$row['ItemID']}] {$row['Name']}</p>";
		}
	}
	$html.="<table cellpadding='2' cellspacing='0' border='1'>
	<tr style='font-weight:bold;'><td>Дата учета</td><td>Заказ, дата списания</td><td>Розн. продажа, дата создания</td><td>Кол.</td><td>Оплата</td></tr>";
	if ((isset($incoming['wb']))&&($incoming['wb'])&&(isset($incoming['item']))&&($incoming['item'])) {
		$sql="SELECT Waybills_selling.*,Message51.wroffdate AS ocreated,Retails.created AS rcreated,Waybills_paid.transfer_id FROM Waybills_selling 
		LEFT JOIN Message51 ON (Message51.Message_ID=Waybills_selling.order_id)
		LEFT JOIN Retails ON (Retails.id=Waybills_selling.retail_id)
		LEFT JOIN Waybills_paid ON (Waybills_paid.id=Waybills_selling.payment_id)
		WHERE Waybills_selling.waybill_id=".intval($incoming['wb'])." AND Waybills_selling.item_id=".intval($incoming['item']);
		//echo $sql."<br>";
		if ($result=mysql_query($sql)) {
			while ($row = mysql_fetch_array($result)) {
				//print_r($row);
				//echo "<br>";
				$html.="<tr>
					<td>".date("d.m.Y H:i:s",strtotime($row['created']))."</td>
					<td>".(($row['order_id']) ? "<a href='/netcat/message.php?catalogue=1&sub=57&cc=53&message={$row['order_id']}'>{$row['order_id']}</a> ".date("d.m.Y",strtotime($row['ocreated']))."" : "&nbsp;")."</td>
					<td>".(($row['retail_id']) ? "<a href='/netcat/modules/netshop/interface/retail-edit.php?id={$row['retail_id']}&start=1'>{$row['retail_id']}</a> ".date("d.m.Y",strtotime($row['rcreated'])) : "&nbsp;")."</td>
					<td>{$row['qty']}</td>
					<td><a target='_blank' href='/netcat/modules/netshop/interface/selling-transfer.php?n={$row['transfer_id']}'>{$row['transfer_id']}</a></td>
				</tr>";
			}
		}
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
</head>
<body>
	
<?php
if ((isset($_SESSION['nc_token_rand'])) || ((isset($_SESSION['insideadmin'])) && ($_SESSION['insideadmin']==1))) {
	echo printMenu();
	echo printSellingMenu();
	if ($show) {
?>
	<h1>Списания по накладным</h1>
	<!--form action="<?php echo $_SERVER["REQUEST_URI"]; ?>" method="post">
	<table cellspacing="0" cellpadding="1" border="1">
	<tr><td>Поставщик</td><td><?php echo selectSupplier($incoming); ?></td></tr>
	<tr><td colspan="2"><input type="submit" value="Показать"></td></tr>
	</table-->
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
