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
function getKotnragetn1c($id) {
	$res="00000000-0000-0000-0000-000000000000";
	$sql="SELECT * FROM Classificator_kontragetns WHERE kontragetns_ID={$id} ORDER BY kontragetns_Name ASC";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		//print_r($row);
		$res.=$row['code1c'];
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
<V8Exch:_1CV8DtUD xmlns:V8Exch=\"http://www.1c.ru/V8/1CV8DtUD/\" xmlns:core=\"http://v8.1c.ru/data\" xmlns:v8=\"http://v8.1c.ru/8.1/data/enterprise/current-config\" xmlns:xs=\"http://www.w3.org/2001/XMLSchema\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">
	<V8Exch:Data>
		<v8:DocumentObject.ПоступлениеТоваровУслуг>\n";
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
//$wbid=839;
//print_r($_SERVER);
//echo $_SERVER['QUERY_STRING'];
$data=explode("&",$_SERVER['QUERY_STRING']);
//print_r($data);
// default value
$dtestart=date("Y-m-d");
$dtestop=date("Y-m-d");
$org=1;
$inn="";
if (is_array($data)) {
	if ($data[0]) {
		$tmp0=explode("=",$data[0]);
		$dtestart=$tmp0[1];
	}
	if ($data[1]) {
		$tmp1=explode("=",$data[1]);
		$dtestop=$tmp1[1];
	}
	if ($data[2]) {
		$tmp2=explode("=",$data[2]);
		$org=intval($tmp2[1]);
	}
	if ($data[3]) {
		$tmp3=explode("=",$data[3]);
		$inn=$tmp3[1];
	}
}
$query = "SELECT Waybills.*,Classificator_Supplier.INN FROM Waybills 
			LEFT JOIN Classificator_Supplier ON (Classificator_Supplier.Supplier_id=Waybills.vendor_id)
			WHERE created BETWEEN '".date("Y-m-d", strtotime($dtestart))." 00:00:00' AND '".date("Y-m-d", strtotime($dtestop))." 23:59:59' 
			".(($org) ? " AND organiz_id={$org} " : "")." 
			".(($inn) ? " AND INN LIKE '{$inn}' " : "");
//			WHERE id={$wbid}";
//echo $query."<br>";
$i=1;
if ($result=mysql_query($query)) {
	while($row = mysql_fetch_array($result)) {
		$wbid=$row['id'];
		//$ret.="	<Товар_{$row["Message_ID"]}>\n";
		//$code1c=$row['code1c'];
		$ret.="		<v8:Ref>00000000-0000-0000-0000-000000000000</v8:Ref>
			<v8:DeletionMark>false</v8:DeletionMark>
			<v8:Date>".date("Y-m-d",strtotime($row['created']))."T00:00:00</v8:Date>
			<v8:Number/>
			<v8:Posted>false</v8:Posted>
			<v8:ВидОперации>Товары</v8:ВидОперации>
			<v8:Организация xsi:type=\"v8:CatalogRef.Организации\">c2c17b81-aba0-11e4-957f-005056a700e0</v8:Организация>
			<v8:Склад xsi:type=\"v8:CatalogRef.Склады\">e7040c13-c657-11e3-b7d7-c86000df0d7b</v8:Склад>
			<v8:ПодразделениеОрганизации xsi:type=\"v8:CatalogRef.ПодразделенияОрганизаций\">00000000-0000-0000-0000-000000000000</v8:ПодразделениеОрганизации>
			<v8:Контрагент xsi:type=\"v8:CatalogRef.Контрагенты\">".(($row['INN']) ? $row['INN'] : "")."</v8:Контрагент>
			<v8:ДоговорКонтрагента xsi:type=\"v8:CatalogRef.ДоговорыКонтрагентов\">00000000-0000-0000-0000-000000000000</v8:ДоговорКонтрагента>
			<v8:СпособЗачетаАвансов>Автоматически</v8:СпособЗачетаАвансов>
			<v8:СчетУчетаРасчетовСКонтрагентом xsi:type=\"v8:ChartOfAccountsRef.Хозрасчетный\">fb01356f-c657-11e3-b7d7-c86000df0d7b</v8:СчетУчетаРасчетовСКонтрагентом>
			<v8:СчетУчетаРасчетовПоАвансам xsi:type=\"v8:ChartOfAccountsRef.Хозрасчетный\">fb01361e-c657-11e3-b7d7-c86000df0d7b</v8:СчетУчетаРасчетовПоАвансам>
			<v8:СчетУчетаРасчетовПоТаре xsi:type=\"v8:ChartOfAccountsRef.Хозрасчетный\">00000000-0000-0000-0000-000000000000</v8:СчетУчетаРасчетовПоТаре>
			<v8:ВалютаДокумента xsi:type=\"v8:CatalogRef.Валюты\">238c8a14-c658-11e3-b7d7-c86000df0d7b</v8:ВалютаДокумента>
			<v8:СчетНаОплатуПоставщика>00000000-0000-0000-0000-000000000000</v8:СчетНаОплатуПоставщика>
			<v8:НомерВходящегоДокумента/>
			<v8:ДатаВходящегоДокумента>0001-01-01T00:00:00</v8:ДатаВходящегоДокумента>
			<v8:Грузоотправитель xsi:type=\"v8:CatalogRef.Контрагенты\">00000000-0000-0000-0000-000000000000</v8:Грузоотправитель>
			<v8:Грузополучатель xsi:type=\"v8:CatalogRef.Контрагенты\">00000000-0000-0000-0000-000000000000</v8:Грузополучатель>
			<v8:Ответственный xsi:type=\"v8:CatalogRef.Пользователи\">00000000-0000-0000-0000-000000000000</v8:Ответственный>
			<v8:Комментарий>804</v8:Комментарий>
			<v8:КратностьВзаиморасчетов>1</v8:КратностьВзаиморасчетов>
			<v8:КурсВзаиморасчетов>1</v8:КурсВзаиморасчетов>
			<v8:НДСВключенВСтоимость>true</v8:НДСВключенВСтоимость>
			<v8:СуммаВключаетНДС>false</v8:СуммаВключаетНДС>
			<v8:СуммаДокумента>130100</v8:СуммаДокумента>
			<v8:ТипЦен xsi:type=\"v8:CatalogRef.ТипыЦенНоменклатуры\">00000000-0000-0000-0000-000000000000</v8:ТипЦен>
			<v8:РучнаяКорректировка>false</v8:РучнаяКорректировка>
			<v8:УдалитьУчитыватьНДС>false</v8:УдалитьУчитыватьНДС>
			<v8:УдалитьПредъявленСчетФактура>false</v8:УдалитьПредъявленСчетФактура>
			<v8:УдалитьНомерВходящегоСчетаФактуры/>
			<v8:УдалитьДатаВходящегоСчетаФактуры>0001-01-01T00:00:00</v8:УдалитьДатаВходящегоСчетаФактуры>
			<v8:УдалитьНДСПредъявленКВычету>false</v8:УдалитьНДСПредъявленКВычету>
			<v8:УдалитьКодВидаОперации/>
			<v8:УдалитьКодСпособаПолучения>0</v8:УдалитьКодСпособаПолучения>
			<v8:КодВидаТранспорта>  </v8:КодВидаТранспорта>
			<v8:НДСНеВыделять>true</v8:НДСНеВыделять>\n";
		
		$sql1="SELECT Waybills_goods.*,Message57.ItemID FROM Waybills_goods 
			LEFT JOIN Message57 ON (Waybills_goods.item_id=Message57.Message_ID)
		WHERE waybill_id={$wbid} AND deleted=0 ORDER BY id ASC";
		//echo $sql1;
		if ($result1=mysql_query($sql1)) {
			while($row1 = mysql_fetch_array($result1)) {
				$ret.="				<v8:Товары>
					<v8:Номенклатура xsi:type=\"v8:CatalogRef.Номенклатура\">".(($row1['ItemID']) ? $row1['ItemID'] : "")."</v8:Номенклатура>
					<v8:КоличествоМест>0</v8:КоличествоМест>
					<v8:ЕдиницаИзмерения xsi:type=\"v8:CatalogRef.КлассификаторЕдиницИзмерения\">e7040c1a-c657-11e3-b7d7-c86000df0d7b</v8:ЕдиницаИзмерения>
					<v8:Коэффициент>1</v8:Коэффициент>
					<v8:Количество>{$row1['qty']}</v8:Количество>
					<v8:Цена>{$row1['originalprice']}</v8:Цена>
					<v8:Сумма>".($row1['originalprice']*$row1['qty'])."</v8:Сумма>
					<v8:СтавкаНДС>БезНДС</v8:СтавкаНДС>
					<v8:СуммаНДС>0</v8:СуммаНДС>
					<v8:СчетУчета xsi:type=\"v8:ChartOfAccountsRef.Хозрасчетный\">fb0135f1-c657-11e3-b7d7-c86000df0d7b</v8:СчетУчета>
					<v8:СчетУчетаНДС xsi:type=\"v8:ChartOfAccountsRef.Хозрасчетный\">fb013634-c657-11e3-b7d7-c86000df0d7b</v8:СчетУчетаНДС>
					<v8:НомерГТД xsi:type=\"v8:CatalogRef.НомераГТД\">00000000-0000-0000-0000-000000000000</v8:НомерГТД>
					<v8:СтранаПроисхождения xsi:type=\"v8:CatalogRef.СтраныМира\">00000000-0000-0000-0000-000000000000</v8:СтранаПроисхождения>
					<v8:ЦенаВРознице>{$row1['itemprice']}</v8:ЦенаВРознице>
					<v8:СуммаВРознице>".($row1['itemprice']*$row1['qty'])."</v8:СуммаВРознице>
					<v8:СтавкаНДСВРознице/>
					<v8:ОтражениеВУСН>Принимаются</v8:ОтражениеВУСН>
					<v8:Контрагент xsi:type=\"v8:CatalogRef.Контрагенты\">{$code1c}</v8:Контрагент>
					<v8:ДоговорКонтрагента xsi:type=\"v8:CatalogRef.ДоговорыКонтрагентов\">00000000-0000-0000-0000-000000000000</v8:ДоговорКонтрагента>
					<v8:СчетРасчетов xsi:type=\"v8:ChartOfAccountsRef.Хозрасчетный\">00000000-0000-0000-0000-000000000000</v8:СчетРасчетов>
					<v8:СпособУчетаНДС>ПринимаетсяКВычету</v8:СпособУчетаНДС>
				</v8:Товары>\n";
				
				
			
			}
		}
		//$i=$i+1;
    }
}

/*$ret.="</offers>
</yml_catalog>";*/
$ret.="		</v8:DocumentObject.ПоступлениеТоваровУслуг>
	</V8Exch:Data>
	<PredefinedData/>
</V8Exch:_1CV8DtUD>";
mysql_close($con);
//echo mb_detect_encoding($ret);
echo $ret;
?>