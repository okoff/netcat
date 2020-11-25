<?php
// 25.02.2014 Elen
// поиск и просмотр списка моделей и сертификатов
include_once ("../../../../vars.inc.php");

session_start();

function getItemsNoModelList($incoming) {
	$strwhere=" WHERE (model='' OR model=0) AND Checked=1 ";
	$html="";
	if ((isset($incoming['manufacturer']))&&($incoming['manufacturer']!="")) {
		$strwhere.=" AND Vendor=".intval($incoming['manufacturer']);
	}	
	$sql="SELECT *
		FROM Message57 {$strwhere} 
		ORDER BY ItemID ASC,Name ASC";
	//echo "<br>".$sql;
	$html="<br>
	<table cellpadding='2' cellspacing='0' border='1'>
		<tr style='font-weight:bold;'>
			<td>Вкл.</td>
			<td>Артикул</td>
			<td>Название</td>
			<td>Цена</td>
		</tr>";
	if ($result=mysql_query($sql)) {
		while ($row=mysql_fetch_array($result)) {
			$html.="<tr>
			<td style='text-align:center;'>".(($row['Checked']==1) ? "<img src='/images/icons/ok.png' style='display:block;margin:0 auto;'>" : "-")."</td>
			<td><a target='_blank' href='/netcat/message.php?catalogue=1&sub={$row['Subdivision_ID']}&cc={$row['Sub_Class_ID']}&message={$row['Message_ID']}'>{$row['ItemID']}</a></td>
			<td>{$row['Name']}</td>
			<td>{$row['Price']}</td>
		</tr>";
		}
	}
	$html.="</table>"; 
	return $html;
}


// ------------------------------------------------------------------------------------------------
	include_once ("utils.php");
	$upload_dir=$_SERVER['DOCUMENT_ROOT']."/netcat_files/certificates/";
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
	$html=getItemsNoModelList($incoming);

	

?>
<!DOCTYPE html>
<html>
<head>
	<title>Модели ножей</title>
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
	//print_r($_SERVER);
	
?>
	<h1>Товары без привязки к модели</h1>
<?php
if ($show) {
?>	
	<form action="<?php echo $_SERVER["REQUEST_URI"]; ?>" method="post">
	<table cellspacing="0" cellpadding="1" border="1">
	<tr><td>Производитель</td><td><?php echo selectManufacturer($incoming); ?></td></tr>
	<tr><td colspan="2"><input type="submit" value="Показать"></td></tr>
	</table>
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
