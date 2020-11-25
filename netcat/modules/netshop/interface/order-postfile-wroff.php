<?php
// 16.09.2013 Elen
// orderstatus=8 - подготовлен к отправке на почту
// orderstatus=9 - передан на почту (пришел первый ответ от почты)
include_once ("../../../../vars.inc.php");
session_start();


include_once ("utils.php");

$incoming = parse_incoming();
$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
if (!$con) {
	die('Could not connect: ' . mysql_error());
}

mysql_select_db($MYSQL_DB_NAME, $con);
//mysql_set_charset("cp1251", $con);
mysql_set_charset("utf8", $con);


$sql="SELECT * FROM Netshop_PostFilesNarv WHERE created>'2015-01-01 00:00:00' ORDER BY id DESC";
if ($result=mysql_query($sql)) {
	while($row = mysql_fetch_array($result)) {
		echo $row['barcodes']."<br>";
		$tmp=explode(";",$row['barcodes']);
		for ($i=0;$i<count($tmp);$i++) {
			echo $tmp[$i]."<br>";
			if ($tmp[$i]) {
				$sql="SELECT * FROM Message51 WHERE barcode LIKE '%".trim($tmp[$i])."%'";
				if ($result1=mysql_query($sql)) {
					while($row1 = mysql_fetch_array($result1)) {
						echo "{$tmp[$i]}-{$row1['Message_ID']}-{$row1['wroff']}<br>";
						$sqlup="UPDATE Message51 SET 
								wroff=1,wroffdate='".date("Y-m-d",strtotime($row['created']))."' WHERE Message_ID={$row1['Message_ID']}";
						echo $sqlup."<br>"; 
						if (mysql_query($sqlup)) {
						//	$html.= " Заказ {$row1['Message_ID']} обновлен.<br>";
						} else {
							die("Ошибка: " . mysql_error());
						}
					}
				}
			}
		}
	}
}
	
mysql_close($con);
?>
