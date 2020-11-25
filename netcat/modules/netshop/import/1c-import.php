<?php
// 1C экспорт товара
include_once ("../../../../vars.inc.php");
session_start();

function getManufacturer($id) {
	$res="";
	$sql="SELECT * FROM Classificator_Supplier WHERE Supplier_ID={$id} ORDER BY Supplier_Name ASC";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		//print_r($row);
		$res.=$row['Supplier_Name'];
	}
	return $res;
}


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
$filename="names1.csv";
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
			$sql1="SELECT * FROM Message57
				WHERE ItemID LIKE '{$tmp1[0]}' 
				ORDER BY ItemID ASC, Name ASC";
				//AND Name LIKE '".iconv("windows-1251","utf-8",$tmp1[2])."' 
			$sql="SELECT * FROM Message57
				WHERE ItemID LIKE '".iconv("windows-1251","utf-8",$tmp1[0])."' 
				ORDER BY ItemID ASC, Name ASC";
			echo $sql1."<br>";
			if ($result=mysql_query($sql)) {
				while($row = mysql_fetch_array($result)) {
					$sql1="UPDATE Message57 SET code1c='{$tmp1[1]}' WHERE Message_ID={$row['Message_ID']}";
					echo $sql1."<br>";
					if (mysql_query($sql1)) {
						echo "ok<br>";
					} else {
						die("Ошибка: ".mysql_error());
					}
				}
			} else {
				echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!<br>";
			}
		}
	}
} else {
	//die("<br>".$filename."<br>".ERR_CONFIG);
	return $cfg;
}		

mysql_close($con);

?>