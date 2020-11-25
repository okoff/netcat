<?php
// отмена списаний по поставщику
$supID=18;  // жбанов
//$supID=5;  // кустари
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

$sql="SELECT * FROM Message51 ORDER BY Message_ID ASC";
$result=mysql_query($sql);
while ($row = mysql_fetch_array($result)) {	
	echo "<p>{$row['Message_ID']}</p>";
	if ($row['closed']==1) {
		$sql3="UPDATE Message51 SET wroff=1,wroffdate=created WHERE Message_ID={$row['Message_ID']}";
		echo $sql3."<br>";
		if (!mysql_query($sql3)) {	
			die($sql3."<br>Ошибка: ".mysql_error());
		}
	}
	if ($row['paid']==1) {
		echo $sql4."<br>";
		$sql4="UPDATE Message51 SET wroff=1,wroffdate=created WHERE Message_ID={$row['Message_ID']}";	
		if (!mysql_query($sql4)) {
			die($sql4." Error: ".mysql_error());
		}
	}
	echo "<br>";
	//break;
}

mysql_close($con);
?>