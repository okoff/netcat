<?php
// 1C экспорт заказов для почты
// status=9
include_once ("../../../../vars.inc.php");
session_start();



$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
if (!$con) {
	die('Could not connect: ' . mysql_error());
}

mysql_select_db($MYSQL_DB_NAME, $con);
//mysql_set_charset("cp1251", $con);
mysql_set_charset("utf8", $con);

//$data=explode("&",$_SERVER['QUERY_STRING']);
//print_r($data);
while (list($k, $v) = each($_GET)) {
	$data[$k] = $v;
}
//print_r($data);
// default value
//http://test.russian-knife.ru/netcat/modules/netshop/export/1c-export-orders.php?start=20160101&stop=20160331
$dtestart=($data['start']) ? date("Y-m-d",strtotime($data['start'])) : date("Y-m-d");
$dtestop=($data['stop']) ? date("Y-m-d",strtotime($data['stop'])) : date("Y-m-d");

//echo  $dtestart."<br>";
//$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -5)).( strstr(__FILE__, "/") ? "/" : "\\" );
//include_once ($NETCAT_FOLDER."vars.inc.php");
//require_once ($ROOT_FOLDER."connect_io.php");

header("Content-type: text/xml");
$ret = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<V8Exch:_1CV8DtUD xmlns:V8Exch=\"http://www.1c.ru/V8/1CV8DtUD/\" xmlns:core=\"http://v8.1c.ru/data\" xmlns:v8=\"http://v8.1c.ru/8.1/data/enterprise/current-config\" xmlns:xs=\"http://www.w3.org/2001/XMLSchema\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">
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
//$wbid=839;
//print_r($_SERVER);
//echo $_SERVER['QUERY_STRING'];

$query = "SELECT * FROM Message51 
			WHERE DeliveryMethod=3 AND NOT Status=2 AND 
			senddate BETWEEN '{$dtestart} 00:00:00' AND '{$dtestop} 23:59:59' 
			ORDER BY Message_ID DESC";
//echo $query."<br>";
$i=1;

if ($result=mysql_query($query)) {
	while($row = mysql_fetch_array($result)) {
		$ret.="	
		<v8:DocumentObject.РеализацияТоваровУслуг>
			<v8:Date>".date("d.m.Y",strtotime($row['senddate']))."</v8:Date>
			<v8:Number>{$row['Message_ID']}</v8:Number>
			<v8:Контрагент>{$row['ContactName']}</v8:Контрагент>
			<v8:КонтрагентEmail>{$row['Email']}</v8:КонтрагентEmail>";
		$sql="SELECT Netshop_OrderGoods.*,Message57.ItemID FROM Netshop_OrderGoods 
			INNER JOIN Message57 ON (Netshop_OrderGoods.Item_ID=Message57.Message_ID)
			WHERE Order_ID=".$row['Message_ID'];
		
		if ($result1=mysql_query($sql)) {
			while($row1=mysql_fetch_array($result1)) {
				$ret.="
				<v8:Товары>
					<v8:Номенклатура>{$row1['ItemID']}</v8:Номенклатура>
					<v8:Количество>{$row1['Qty']}</v8:Количество>
					<v8:Цена>{$row1['ItemPrice']}</v8:Цена>
				</v8:Товары>";
			}
		}
		$ret.="
			<v8:Доставка>
				<v8:Сумма>{$row['DeliveryCost']}</v8:Сумма>
			</v8:Доставка>
		</v8:DocumentObject.РеализацияТоваровУслуг>\n";
		
		
			
		
		//$i=$i+1;
    }
}

$ret.="		
	</V8Exch:Data>
	<PredefinedData/>
</V8Exch:_1CV8DtUD>";
mysql_close($con);

echo $ret;
?>