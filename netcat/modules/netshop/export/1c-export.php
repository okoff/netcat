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

header("Content-type: text/xml");
$ret = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<V8Exch:_1CV8DtUD xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xs=\"http://www.w3.org/2001/XMLSchema\" xmlns:v8=\"http://v8.1c.ru/8.1/data/enterprise/current-config\" xmlns:core=\"http://v8.1c.ru/data\" xmlns:V8Exch=\"http://www.1c.ru/V8/1CV8DtUD/\">
<V8Exch:Data>\n";
/*
<yml_catalog date=\"".date("Y-m-d H:i:s")."\">
	<categories>\n";
$sql="SELECT Subdivision.Subdivision_ID,Sub_Class.Sub_Class_ID,Subdivision.Catalogue_ID,Subdivision.Parent_Sub_ID,Subdivision.Subdivision_Name FROM Subdivision, Sub_Class 
			WHERE Sub_Class.Subdivision_ID = Subdivision.Subdivision_ID 
			AND Sub_Class.Class_ID IN (57) AND Subdivision.Checked = 1
			ORDER BY Subdivision.Parent_Sub_ID ASC,Subdivision.Subdivision_ID ASC";	
	//echo $sql;
if ($result=mysql_query($sql)) {
	while($row = mysql_fetch_array($result)) {
		$ret.="		<category id='{$row['Subdivision_ID']}' parentid='".(($row['Parent_Sub_ID']==57) ? "0" : $row['Parent_Sub_ID'])."'>{$row['Subdivision_Name']}</category>\n";
	}
}
$ret.="	</categories>
	<offers>"; */

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
		
		/*//$ret.="	<Товар_{$row["Message_ID"]}>\n";
		$ret.="	<v8:CatalogObject.Номенклатура>\n";
		$ret.="		<v8:Description>".htmlspecialchars($row["Name"])."</v8:Description>\n";
		$ret.="		<v8:НаименованиеПолное>".htmlspecialchars($row["Name"])."</v8:НаименованиеПолное>\n";
		$ret.="		<v8:Артикул>".htmlspecialchars($row["ItemID"])."</v8:Артикул>\n";
		$ret.="		<v8:Комментарий>".getManufacturer($row['supplier'])."&amp;".$row['Checked']."</v8:Комментарий>\n";
	//	$ret.="		<v8:code>".$i."</v8:code>\n";
	//	$ret.="		<count>".htmlspecialchars($row["StockUnits"])."</count>\n";
	//	$ret.="		<category>".htmlspecialchars($row["Subdivision_ID"])."</category>\n";
		//$ret.="	</Товар_{$row["Message_ID"]}>\n";
		$ret.="	</v8:CatalogObject.Номенклатура>\n";*/
		
		$ret.="<v8:CatalogObject.Номенклатура>
		<v8:IsFolder>false</v8:IsFolder>
		<v8:Ref xsi:type=\"v8:CatalogRef.Номенклатура\">00000000-0000-0000-0000-000000000000</v8:Ref>
		<v8:DeletionMark>false</v8:DeletionMark>
		<v8:Parent xsi:type=\"v8:CatalogRef.Номенклатура\">00000000-0000-0000-0000-000000000000</v8:Parent>
		<v8:Code/>
		<v8:Description>".htmlspecialchars($row["Name"])."</v8:Description>
		<v8:НаименованиеПолное>".htmlspecialchars($row["Name"])."</v8:НаименованиеПолное>
		<v8:Артикул>".htmlspecialchars($row["ItemID"])."</v8:Артикул>
		<v8:ЕдиницаИзмерения xsi:type=\"v8:CatalogRef.КлассификаторЕдиницИзмерения\">e7040c1a-c657-11e3-b7d7-c86000df0d7b</v8:ЕдиницаИзмерения>
		<v8:СтавкаНДС>БезНДС</v8:СтавкаНДС>
		<v8:Комментарий>".getManufacturer($row['supplier'])."&amp;".$row['Checked']."</v8:Комментарий>
		<v8:Услуга>false</v8:Услуга>
		<v8:НоменклатурнаяГруппа xsi:type=\"v8:CatalogRef.НоменклатурныеГруппы\">e7040c1b-c657-11e3-b7d7-c86000df0d7b</v8:НоменклатурнаяГруппа>
		<v8:СтранаПроисхождения xsi:type=\"v8:CatalogRef.СтраныМира\">00000000-0000-0000-0000-000000000000</v8:СтранаПроисхождения>
		<v8:НомерГТД xsi:type=\"v8:CatalogRef.НомераГТД\">00000000-0000-0000-0000-000000000000</v8:НомерГТД>
		<v8:СтатьяЗатрат xsi:type=\"v8:CatalogRef.СтатьиЗатрат\">00000000-0000-0000-0000-000000000000</v8:СтатьяЗатрат>
		<v8:ОсновнаяСпецификацияНоменклатуры xsi:type=\"v8:CatalogRef.СпецификацииНоменклатуры\">00000000-0000-0000-0000-000000000000</v8:ОсновнаяСпецификацияНоменклатуры>
		<v8:Производитель xsi:type=\"v8:CatalogRef.Контрагенты\">00000000-0000-0000-0000-000000000000</v8:Производитель>
		<v8:Импортер xsi:type=\"v8:CatalogRef.Контрагенты\">00000000-0000-0000-0000-000000000000</v8:Импортер>
		<v8:КодТНВЭД xsi:type=\"v8:CatalogRef.КлассификаторТНВЭД\">00000000-0000-0000-0000-000000000000</v8:КодТНВЭД>
		<v8:КодОКВЭД xsi:type=\"v8:CatalogRef.КлассификаторВидовЭкономическойДеятельности\">00000000-0000-0000-0000-000000000000</v8:КодОКВЭД>
		<v8:КодОКП xsi:type=\"v8:CatalogRef.ОбщероссийскийКлассификаторПродукции\">00000000-0000-0000-0000-000000000000</v8:КодОКП>
		<v8:ВидНоменклатуры xsi:type=\"v8:CatalogRef.ВидыНоменклатуры\">96d42f58-d3b3-11e4-a367-005056a700e0</v8:ВидНоменклатуры>
		<v8:Цена>".htmlspecialchars($row["Price"])."</v8:Цена>
	</v8:CatalogObject.Номенклатура>\n";
		$i=$i+1;
    }
}

/*$ret.="</offers>
</yml_catalog>";*/
$ret.="</V8Exch:Data>
<PredefinedData/>
</V8Exch:_1CV8DtUD>";
mysql_close($con);

echo $ret;
?>