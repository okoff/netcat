<?php
// отмена списаний по поставщику
$supID=18;  // жбанов
//$supID=5;  // кустари
//$supID=45;  // мастер гарант
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");

$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
if (!$con) {
	die('Could not connect: ' . mysql_error());
}

mysql_select_db($MYSQL_DB_NAME, $con);
//mysql_set_charset("cp1251", $con);
mysql_set_charset("utf8", $con);
$incoming = parse_incoming();

$sql="SELECT *
FROM `Message51`
WHERE `wroffdate`='2015-02-03 00:00:00'
AND `paydate` < '2015-01-26 00:00:00'
ORDER BY `Message51`.`Message_ID` DESC";
$result=mysql_query($sql);
while ($row = mysql_fetch_array($result)) {	
	if ((strtotime($row['paydate'])<strtotime($row['wroffdate']))&&(date("d.m.Y",strtotime($row['wroffdate']))=="03.02.2015")) {
		$sql1="UPDATE Message51 SET wroffdate=paydate WHERE Message_ID={$row['Message_ID']}";
		echo $sql1."<br>";
		if (!mysql_query($sql1)) {	
			die($sql1."<br>Ошибка: ".mysql_error());
		}
	}
			/*if (!mysql_query($sql5)) {	
				die($sql5."<br>Ошибка: ".mysql_error());
			}*/
	
	//}
	//break;
}

mysql_close($con);
?>