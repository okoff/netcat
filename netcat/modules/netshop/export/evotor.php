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

header("Content-type: text/html");
$ret = "uuid;name;group;code;parentCode;measureName;tax;allowToSell;description;articleNumber;type;price;costPrice;quantity;barCodes;alcoCodes;alcoholByVolume;alcoholProductKindCode;tareVolume\n";

$sql="SELECT Subdivision.Subdivision_ID,Sub_Class.Sub_Class_ID,Subdivision.Catalogue_ID,Subdivision.Parent_Sub_ID,Subdivision.Subdivision_Name FROM Subdivision, Sub_Class 
			WHERE Sub_Class.Subdivision_ID = Subdivision.Subdivision_ID 
			AND Sub_Class.Class_ID IN (57) AND Subdivision.Checked = 1
			ORDER BY Subdivision.Parent_Sub_ID ASC,Subdivision.Subdivision_ID ASC";	
	//echo $sql;
if ($result=mysql_query($sql)) {
	while($row = mysql_fetch_array($result)) {
		//$ret.="		<category id='{$row['Subdivision_ID']}' parentid='".(($row['Parent_Sub_ID']==57) ? "0" : $row['Parent_Sub_ID'])."'>{$row['Subdivision_Name']}</category>\n";
		$ret.=";".htmlspecialchars($row["Subdivision_Name"]).";1;".$row['Subdivision_ID'].";".htmlspecialchars($row["Parent_Sub_ID"]).";0;0;1;;;;;;;;;;;\n";
		
	}
}

// GOODS CATALOGUE -----------------------------------------------
/*$query = "SELECT m . * , CONCAT( u.Hidden_URL, s.EnglishName, '_', m.Message_ID, '.html' ) AS URL
FROM (`Message57` AS m, `Subdivision` AS u, `Sub_Class` AS s)
LEFT JOIN Message57 AS parent ON ( m.`Parent_Message_ID` !=0 AND m.`Parent_Message_ID` = parent.`Message_ID` )  
                WHERE  m.`Checked`=1 
					AND s.`Subdivision_ID` = m.`Subdivision_ID`
                    AND s.`Sub_Class_ID` = m.`Sub_Class_ID`
                    AND u.`Subdivision_ID` = m.`Subdivision_ID`
			ORDER BY m.ItemID ASC, m.Name ASC ";
			//echo $query."<br>";*/
$query = "SELECT m . * , CONCAT( u.Hidden_URL, s.EnglishName, '_', m.Message_ID, '.html' ) AS URL
FROM (`Message57` AS m, `Subdivision` AS u, `Sub_Class` AS s)
LEFT JOIN Message57 AS parent ON ( m.`Parent_Message_ID` !=0 AND m.`Parent_Message_ID` = parent.`Message_ID` )  
                WHERE  s.`Subdivision_ID` = m.`Subdivision_ID`
                    AND s.`Sub_Class_ID` = m.`Sub_Class_ID`
                    AND u.`Subdivision_ID` = m.`Subdivision_ID`
	
			ORDER BY m.ItemID ASC, m.Name ASC ";
			//echo $query."<br>"; 				AND m.code1C=''
$i=1;
if ($result=mysql_query($query)) {
	while($row = mysql_fetch_array($result)) {
		$name=htmlspecialchars($row['Name']);
		$name=str_replace("&quot;","",$row['Name']);
		$ret.=";".$name.";0;".$row['Message_ID'].";".htmlspecialchars($row["Subdivision_ID"]).";шт;0;1;;".htmlspecialchars($row["ItemID"]).";NORMAL;".htmlspecialchars($row["Price"]).";0;".htmlspecialchars($row["StockUnits"]).";;;;;\n";
		

		$i=$i+1;
    }
}

/*$ret.="</offers>
</yml_catalog>";*/

mysql_close($con);

$dir=$_SERVER['DOCUMENT_ROOT']."/netcat_files/evotor/";//postfiles/upload/';
$dir1="/netcat_files/evotor/";//postfiles/upload/';
//echo $dir;
$dte=date("Ymd");
$fname="evotor-{$dte}.csv";
$handle = fopen($dir.$fname, "w");
fwrite($handle,$ret);
fclose($handle);

echo "<p>Файл создан. <a href='{$dir1}{$fname}'>Скачать</a></p>
<p><a href='/netcat/modules/netshop/interface/order-list.php?start=1'>Назад</a></p>";
//echo $ret;
?>