<?php
// 1C экспорт товара
include_once ("../../../../vars.inc.php");
session_start();

function getDiscount($id, $originalprice) {
	//echo $id;
	$res=$originalprice;
	$sql="SELECT Name, Description, UserGroups, Goods, ValidFrom, ValidTo, `Condition`,
                 Function, FunctionDestination, FunctionOperator, StopItem
            FROM Message54
           WHERE AppliesTo = 1
             AND ((ValidFrom IS NULL AND ValidTo IS NULL) OR
                  (ValidFrom <= NOW() AND ValidTo >= NOW()))
             AND Checked = 1
			 AND Goods LIKE '%57:{$id},%'
           ORDER BY Priority DESC";
	//echo $sql;
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		//print_r($row);
		if (($row['FunctionOperator'])=="*=") {
			$res=$originalprice*$row['Function'];
		}
		if (($row['FunctionOperator'])=="-=") {
			$res=$originalprice-$row['Function'];
		}
	}
	if ($res==$originalprice) {
		$sql="SELECT Name, Description, UserGroups, Goods, ValidFrom, ValidTo, `Condition`,
                 Function, FunctionDestination, FunctionOperator, StopItem
            FROM Message54
           WHERE AppliesTo = 1
             AND ((ValidFrom IS NULL AND ValidTo IS NULL) OR
                  (ValidFrom <= NOW() AND ValidTo >= NOW()))
             AND Checked = 1
			 AND Goods LIKE '%57:{$id}'
           ORDER BY Priority DESC";
		//echo $sql;
		$result=mysql_query($sql);
		while ($row = mysql_fetch_array($result)) {
			//print_r($row);
			if (($row['FunctionOperator'])=="*=") {
				$res=$originalprice*$row['Function'];
			}
			if (($row['FunctionOperator'])=="-=") {
				$res=$originalprice-$row['Function'];
			}
		}
	}
	return $res;
}


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

/*
$sql="SELECT Subdivision.Subdivision_ID,Sub_Class.Sub_Class_ID,Subdivision.Catalogue_ID,Subdivision.Parent_Sub_ID,Subdivision.Subdivision_Name FROM Subdivision, Sub_Class 
			WHERE Sub_Class.Subdivision_ID = Subdivision.Subdivision_ID 
			AND Sub_Class.Class_ID IN (57) AND Subdivision.Checked = 1
			ORDER BY Subdivision.Parent_Sub_ID ASC,Subdivision.Subdivision_ID ASC";	
	//echo $sql;
if ($result=mysql_query($sql)) {
	while($row = mysql_fetch_array($result)) {
		$ret.=";".htmlspecialchars($row["Subdivision_Name"]).";1;".$row['Subdivision_ID'].";".htmlspecialchars($row["Parent_Sub_ID"]).";0;0;1;;;;;;;;;;;\n";
		
	}
}*/

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
					AND m.Checked=1
			ORDER BY m.Message_ID ASC, m.ItemID ASC, m.Name ASC ";
			//echo $query."<br>"; 				//AND m.code1C=''
$i=1;
if ($result=mysql_query($query)) {
	while($row = mysql_fetch_array($result)) {
		$name=htmlspecialchars($row['Name']);
		$name=str_replace("&quot;","",$name);
		$name=str_replace(".","",$name);
		$name=str_replace("-","",$name);
		$name=trim($name);
		//$name=substr($name,0,30);
		$name=mb_substr($name,0,30,'UTF-8');
		
		$name=htmlspecialchars($row['ItemID'])." ".$name;
		/*if (($row["Subdivision_ID"]==81)||
			($row["Subdivision_ID"]==141)||($row["Subdivision_ID"]==103)||
			($row["Subdivision_ID"]==138)||($row["Subdivision_ID"]==102)||
			($row["Subdivision_ID"]==139)||($row["Subdivision_ID"]==106)||
			($row["Subdivision_ID"]==109)||($row["Subdivision_ID"]==147)||
			($row["Subdivision_ID"]==140)||($row["Subdivision_ID"]==151)||
			($row["Subdivision_ID"]==153)||($row["Subdivision_ID"]==157)||
			($row["Subdivision_ID"]==161)||($row["Subdivision_ID"]==280)) {
				$name="Нож";
		}
		if ($row["Subdivision_ID"]==108) {
			$name="Клинок";
		}
		if ($row["Subdivision_ID"]==154) {
				$name="Литье";
		}
		if ($row["Subdivision_ID"]==155) {
				$name="Бруски";
		}
		if ($row["Subdivision_ID"]==131) {
				$name="Топор";
		}
		if ($row["Subdivision_ID"]==149) {
				$name="Нагайка";
		}
		if ($row["Subdivision_ID"]==162) {
				$name="Кованое изделие";
		}
		if ($row["Subdivision_ID"]==195) {
				$name="Аксессуар";
		}
		if ($row["Subdivision_ID"]==355) {
				$name="Средство обработки";
		}
		if ($row["Subdivision_ID"]==289) {
				$name="Столловый прибор";
		}
		if ($row["Subdivision_ID"]==356) {
				$name="Мультитул";
		}
		if ($row["Subdivision_ID"]==1041) {
				$name="Инструмент для резьбы по дереву";
		}
		if ($row["Subdivision_ID"]==2561) {
				$name="Туристический товар";
		}
		if ($row["Subdivision_ID"]==2562) {
				$name="Фонарь";
		}
		if ($row["Subdivision_ID"]==2563) {
				$name="Термос";
		}*/
		$discount=0;
		$discount=getDiscount($row['Message_ID'],$row["Price"]);
		$price=$row["Price"];
		
		//echo $row['Message_ID']."-".$price."-".$discount."<br>";
		
		//$ret.=";".$name.";0;".$row['Message_ID'].";".htmlspecialchars($row["Subdivision_ID"]).";шт;0;1;;".htmlspecialchars($row["ItemID"]).".;NORMAL;".htmlspecialchars($discount).";0;".htmlspecialchars($row["StockUnits"]).";;;;;\n";
		$ret.=";".$name.";0;".htmlspecialchars($row["Message_ID"]).";".htmlspecialchars($row["Subdivision_ID"]).";шт;0;1;;".htmlspecialchars($row["ItemID"]).".;NORMAL;".htmlspecialchars($discount).";0;".htmlspecialchars($row["StockUnits"]).";;;;;\n";
		
		//$ret.=";".$name.";0;".htmlspecialchars($row["ItemID"]).";".htmlspecialchars($row["Subdivision_ID"]).";шт;0;1;;".htmlspecialchars($row["ItemID"]).".;NORMAL;".htmlspecialchars($row["Price"]).";0;".htmlspecialchars($row["StockUnits"]).";;;;;\n";

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