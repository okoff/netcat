<?php
// 24.12.2013 Elen
// поиск и просмотр списка розничных продаж (магазин)
include_once ("../../../../vars.inc.php");
include_once ("utils.php");
include_once ("utils-retail.php");
session_start();

function getClientNobuy() {
	$res=0;
	$sql="SELECT * FROM User_nobuy WHERE created LIKE '%".date("Y-m-d")."%'";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$res=$row['qty'];
	}
	return $res;
}
// ------------------------------------------------------------------------------------------------

	//$UploadDir=$_SERVER['DOCUMENT_ROOT'].'/netcat_files/postfiles/upload/';
	//$UploadDirNarv=$_SERVER['DOCUMENT_ROOT'].'/netcat_files/postfiles/narv/';
	//$DownloadDir=$_SERVER['DOCUMENT_ROOT'].'/netcat_files/postfiles/';
	//$DownloadDirLink='/netcat_files/postfiles/';
	$incoming = parse_incoming();
	$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
	if (!$con) {
		die('Could not connect: ' . mysql_error());
	}
	
	mysql_select_db($MYSQL_DB_NAME, $con);
	//mysql_set_charset("cp1251", $con);
	mysql_set_charset("utf8", $con);
	$where="";
	//print_r($incoming);
	if (!isset($incoming['action'])) {
		$html=getRetailList($incoming);
	} else {
		switch ($incoming['action']) {
			default:
				$html=getRetailList($incoming);
				break;
		}
	}

?>
<!DOCTYPE html>
<html>
<head>
	<title>Розничные продажи</title>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<script>
function clickEdit() {
	var len=0;
	var j=1;
	var boxes = document.getElementsByTagName('input');
	if (document.getElementById("action").value=="create") {
		for (i=0; i<boxes.length; i++)  {
			if (boxes[i].type == 'checkbox')   {
				if (boxes[i].checked) {
					//alert(j);
					return true;
				}
				j=j+1;
			}	
		}
		document.getElementById("err").style.display="block";
		return false;
	} else {
		return true;
	}
	
}
	</script>
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
?>
	<h1>Розничные продажи [<a href='/netcat/modules/netshop/interface/retail-addnew.php?start=1'>новая</a>]</h1>
	<h2><?php echo date("d.m.Y"); ?></h2>
	<p><a href='/netcat/modules/netshop/interface/retail-z.php'>z отчет</a></p>
	
	<p>Покупатели без покупки <b><?php echo getClientNobuy(); ?></b>: [<b><a href="/netcat/modules/netshop/interface/client-nobuy.php">добавить</a></b>]</p>
	<?php echo $html; ?>
	
	<p><a href="/netcat/modules/netshop/interface/statistic-retail.php">Посмотреть все розничные продажи</a></p>
<?php	
} else {
	echo "<p>У вас нет прав для просмотра этой страницы</p><p><a href='/netcat/modules/netshop/interface/login.php'>Вход</a></p>";
}
?>
</body>
</html>
<?php

mysql_close($con);
?>
