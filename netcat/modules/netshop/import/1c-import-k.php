<?php
// 1C экспорт товара
include_once ("../../../../vars.inc.php");
session_start();


$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
if (!$con) {
	die('Could not connect: ' . mysql_error());
}

mysql_select_db($MYSQL_DB_NAME, $con);
//mysql_set_charset("cp1251", $con);
mysql_set_charset("utf8", $con);


//$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -5)).( strstr(__FILE__, "/") ? "/" : "\\" );
//include_once ($NETCAT_FOLDER."vars.inc.php");
//require_once ($ROOT_FOLDER."connect_io.php");

// open csv file
$filename="kontragents.csv";
if (file_exists($filename)) {
	$fp = fopen($filename, "r");
	//$cfg = fread($file, filesize($filename));
	while (!feof($fp)) { 
		$str.=fread($fp, 1024); 				
	} 
	fclose($fp);
	$tmp = explode("\n", $str);
	//print_r($tmp);
	foreach($tmp as $t) {
		echo $t."<br>";
		if ($t) {
			$tmp1=explode(";",$t);
			$sql="SELECT * FROM Classificator_kontragetns
				WHERE kontragetns_Name LIKE '".iconv("windows-1251","utf-8",$tmp1[0])."' 
				ORDER BY kontragetns_ID ASC";
			echo $sql."<br>";
			$i=0;
			if ($result=mysql_query($sql)) {
				while($row = mysql_fetch_array($result)) {
					$i=1;
					$sql1="UPDATE  Classificator_kontragetns SET kontragetns_Name='".iconv("windows-1251","utf-8",$tmp1[0])."',code1c='{$tmp1[1]}' WHERE kontragetns_ID={$row['kontragetns_ID']}";
					echo $sql1."<br>";
					//if (mysql_query($sql1)) {
					//	echo "ok<br>";
					//} else {
					//	die("Ошибка: ".mysql_error());
					//}
				}
			} 
			if (!$i) {
				$sql1="INSERT INTO  Classificator_kontragetns (kontragetns_Name,code1c,buyer) 
					VALUES ('".iconv("windows-1251","utf-8",$tmp1[0])."','{$tmp1[1]}',0)";
				echo $sql1."<br>";
				if (mysql_query($sql1)) {
					echo "ok<br>";
				} else {
					die("Ошибка: ".mysql_error());
				}	
			}
		}
		//break;
	}
} else {
	//die("<br>".$filename."<br>".ERR_CONFIG);
	return $cfg;
}		

mysql_close($con);

?>