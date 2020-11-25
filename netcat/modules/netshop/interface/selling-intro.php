<?php
// 12.05.2014 Elen
// накладные под реализацию. проверка списаний
include_once ("../../../../vars.inc.php");

session_start();


// ------------------------------------------------------------------------------------------------
	include_once ("utils.php");
	include_once ("utils-selling.php");
	$incoming = parse_incoming();
	$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
	if (!$con) {
		die('Could not connect: ' . mysql_error());
	}
	
	mysql_select_db($MYSQL_DB_NAME, $con);
	//mysql_set_charset("cp1251", $con);
	mysql_set_charset("utf8", $con);
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
	//print_r($incoming);
	
?>
	<h1>Списания по накладным</h1>
	<table cellpadding='2' cellspacing='0' border='1'>
	<tr>
		<td style='font-weight:bold;text-align:center;'>Поставщик</td>
		<td style='font-weight:bold;text-align:center;'>Просмотр и сохранение<br>списаний.</td>
		<td style='font-weight:bold;text-align:center;'>Просмотр неоплаченных позиций.<br>Сохранение оплат.</td>
		<td style='font-weight:bold;text-align:center;'>Просмотр оплат.</td>
		<td style='font-weight:bold;text-align:center;'>Дата последней оплаты.</td>
		<td style='font-weight:bold;text-align:center;'>Оплачено.</td>
		<td style='font-weight:bold;text-align:center;'>Сумма последней оплаты.</td>
	</tr>
<?php
	$onreal=" WHERE Onreal=1 ";
	$sql="SELECT * FROM Classificator_Supplier {$onreal} ORDER BY Supplier_Name ASC";
	$result=mysql_query($sql);
	$i=1;
	while ($row = mysql_fetch_array($result)) {
		$stl="style='background:#e0e0e0;'";
		echo "<tr ".(($i%2) ? $stl : "")."><td>{$row['Supplier_Name']}</td>
		<td style='text-align:center;'><a href='/netcat/modules/netshop/interface/selling.php?supplier={$row['Supplier_ID']}'>&gt;&gt;&gt;</a></td>
		<td style='text-align:center;'><a href='/netcat/modules/netshop/interface/selling-payment.php?supplier={$row['Supplier_ID']}'>&gt;&gt;&gt;</a></td>
		<td style='text-align:center;'><a href='/netcat/modules/netshop/interface/selling-transfer.php?supplier={$row['Supplier_ID']}'>&gt;&gt;&gt;</a></td>";
		$sql1="SELECT * FROM Waybills_transfer WHERE supplier_id={$row['Supplier_ID']} ORDER BY id DESC LIMIT 1";
		$result1=mysql_query($sql1);
		$tmp="";
		while ($row1 = mysql_fetch_array($result1)) {
			$tmp.="<td>".date("d.m.Y",strtotime($row1['created']))."</td>";
			$tmp.="<td>".(($row1['paid']==1) ? "<img src='/images/icons/ok.png' style='display:block;margin:0 auto;'>".
				(($row1['paiddate']!="0000-00-00 00:00:00") ? date("d.m.Y",strtotime($row1['paiddate'])) : "") : "&nbsp;")."</td>";
			$tmp.="<td>{$row1['summa']}</td>";
		}
		echo ($tmp) ? $tmp : "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>";
		echo "</tr>";
		$i=$i+1;
		//$res.="<option value='{$row['Supplier_ID']}' ".(($incoming['supplier']==$row['Supplier_ID']) ? "selected" : "").">{$row['Supplier_Name']}</option>";
	}
	
?>
	</table>

<?php 
} else {
?>
	<p>У вас нет прав для просмотра этой страницы</p><p><a href='/netcat/modules/netshop/interface/login.php?jump=<?php echo $_SERVER['REQUEST_URI']; ?>'>Вход</a></p>
<?php
}
?>
</body>
</html>
<?php

mysql_close($con);
?>
