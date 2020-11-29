<?php
include "../vars.inc.php";

//$itemname = isset($_POST['itemname']) ? trim(strip_tags($_POST['itemname'])) : 'Нож Японский (дамасская сталь), береста';

$link = mysqli_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DB_NAME);

if (!$link) {
	echo iconv("UTF-8","windows-1251//TRANSLIT", 'Ошибка подключения к базе данных.');
	exit;
}

//mysqli_set_charset($link, "utf8");

//error_log("handler.php utm:[".var_export($utm,true)."]");
//error_log("href:".var_export($href,true)."]");
//error_log("REQUEST_URI={".$_SERVER['REQUEST_URI']."]");
//error_log("HTTP_REFERER=[".$_SERVER['HTTP_REFERER']."]");
//error_log('utm="'.iconv('windows-1251','utf-8',$utm).'"');		
//error_log('href="'.iconv('windows-1251','utf-8',$href).'"');

$answer = [];

$sql = "SELECT steel_ID,steel_Name FROM Classificator_steel WHERE Checked=1 ";
$sql.= "AND EXISTS (SELECT * FROM Message57 where steel = steel_ID AND Checked=1) ";
$sql.= "ORDER BY steel_Name ASC";
$result=mysqli_query($link,$sql);
if ($result==false) {
	die("Error MySQL request: ".mysqli_error($link)."<br>Query: ".$sql);
}
while ($row = mysqli_fetch_array($result)) {
	$object["id"] = $row["steel_ID"]; 
	$object["name"] = $row["steel_Name"]; 
	$answer["steel"][] = $object;
}
$sql = "SELECT Manufacturer_ID, Manufacturer_Name FROM Classificator_Manufacturer WHERE Checked=1 ";
$sql.= "AND EXISTS (SELECT * FROM Message57 where Vendor = Manufacturer_ID AND Checked=1) ";
$sql.= "ORDER BY Manufacturer_Name ASC";
$result=mysqli_query($link,$sql);
if ($result==false) {
	die("Error MySQL request: ".mysqli_error($link)."<br>Query: ".$sql);
}
while ($row = mysqli_fetch_array($result)) {
	$object["id"] = $row["Manufacturer_ID"]; 
	$object["name"] = $row["Manufacturer_Name"]; 
	$answer["manuf"][] = $object;
}

$sql = "SELECT Country_ID, Country_Name FROM Classificator_Country WHERE Checked=1 "; 
$sql.= "AND EXISTS (SELECT * FROM Message57 where country = Country_ID AND Checked=1) ";
$sql.= "ORDER BY Country_Name ASC";
$result=mysqli_query($link,$sql);
if ($result==false) {
	die("Error MySQL request: ".mysqli_error($link)."<br>Query: ".$sql);
}
while ($row = mysqli_fetch_array($result)) {
	$object["id"] = $row["Country_ID"]; 
	$object["name"] = $row["Country_Name"]; 
	$answer["state"][] = $object;
}

//$sql = "SELECT DISTINCT handle FROM Message57 where Checked=1 ";
//$sql.= "ORDER BY handle ASC";
//$result=mysqli_query($link,$sql);
//if ($result==false) {
//	die("Error MySQL request: ".mysqli_error($link)."<br>Query: ".$sql);
//}
//while ($row = mysqli_fetch_array($result)) {
//	$object["id"] = $row["handle"]; 
//	$object["name"] = $row["handle"]; 
//	$answer["mater"][] = $object;
//}

echo (json_encode($answer));
exit;
?>