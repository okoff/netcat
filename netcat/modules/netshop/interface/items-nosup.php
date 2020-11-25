<?php
// 25.02.2014 Elen
// поиск и просмотр списка поставщиков
include_once ("../../../../vars.inc.php");

session_start();

function getItemsList() {
	$sql="SELECT *
		FROM Message57 
		WHERE supplier=0
		ORDER BY ItemID ASC";
	//echo $sql;
	$html1=$html2=$hdr="";
	$hdr= //"<p>[<b><a href='/netcat/modules/netshop/interface/suppliers.php?id=".(getLastInsertID("Classificator_Supplier","Supplier_ID")+1)."&action=edit'>Добавить</a></b>]</p>
	"<table cellpadding='2' cellspacing='0' border='1'>
		<tr style='font-weight:bold;'>
			<td>Артикул</td>
			<td>Название</td>
			<td>На складе</td>
			<td>Производитель</td></tr>";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$tmp="<tr>
				<td><a target='_blank' href='/netcat/message.php?catalogue=1&sub={$row['Subdivision_ID']}&cc={$row['Sub_Class_ID']}&message={$row['Message_ID']}'>{$row['ItemID']}</a></td>
				<td>{$row['Name']}</td>
				<td>{$row['StockUnits']}</td>
				<td>".getManufacturer($row['Vendor'])."</td>
				</tr>";
			if ($row['Checked']==1) {
				$html1.=$tmp; 
			} else {
				$html2.=$tmp;
			}
			
		}
	}
	$html=$hdr.$html1."</table><br><hr><br>".$hdr.$html2."</table>";
	return $html;
}
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
	$where="";
	$show=1;

	$html=getItemsList();


?>
<!DOCTYPE html>
<html>
<head>
	<title>Товары без поставщика</title>
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
	if ($show) {
?>
	<h1>Товары без поставщика</h1>
	
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
