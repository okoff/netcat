<?php
// 23.03.2016 Elen PickPoint API



function getOrderItems($order_id,$conv=1) {
	$res="";
	$sql="SELECT Message57.ItemID,Message57.Name,Netshop_OrderGoods.Qty FROM Message57 
		INNER JOIN Netshop_OrderGoods ON (Netshop_OrderGoods.Item_ID=Message57.Message_ID)
		WHERE Netshop_OrderGoods.Order_ID=".$order_id;
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$res.="[".$row['ItemID']."] ".$row['Name']." {$row['Qty']}шт.;";
		
	}
	if ($conv==1) {
		//$res=iconv("WINDOWS-1251","UTF-8", $res);
	}
	return $res;
}

function sendReq($action,$body) {
	//echo $body."<br>";
	$tmp=array();
	$sock = fsockopen("e-solution.pickpoint.ru", 80, $errno, $errstr, 30);
 
	if (!$sock) die("$errstr ($errno)\n");
	 
	//if ($action=="login") {
	//$body=mb_convert_encoding($body, "UTF-8");
	//echo mb_detect_encoding($body); 
	//$body=iconv('CP1251','UTF-8', $body);
	//$body=iconv('UTF-8', 'UTF-8//IGNORE', $body);
	//echo mb_detect_encoding($body); 
	//echo $body."<br>"; 
	
	/*$dir="./";
	$filename="tmp.txt";
	if (!$handle = fopen($dir.$filename, 'w')) {
		echo "Не могу открыть файл";
	}
	fwrite($handle, $body);
	fclose($handle);*/
	
	fputs($sock, "POST /api/{$action} HTTP/1.1\r\n");
	fputs($sock, "Host: e-solution.pickpoint.ru\r\n");
	fputs($sock, "Accept: */*\r\n");
	fputs($sock, "Content-Type: application/json \r\n");
	fputs($sock, "Content-Length: ".strlen($body)."\r\n");
	fputs($sock, "Connection: close\r\n\r\n");
	fputs($sock, "$body");
	 
	//echo "<!--".$body."<br>-->"; 

	 
	while ($str = trim(fgets($sock, 4096)));
	 
	$body = "";
	 
	while (!feof($sock))
		$body.= fgets($sock, 4096);
	 
	fclose($sock);

	//echo "<!--br>PP Answer: ".$body."<br>\n\n\n-->";
//	$headers='Content-type: text/plain; UTF-8'."\r\n".
//'From: Интернет-магазин Русские ножи <admin@russian-knife.ru>'."\r\n";
//	mail("elena@best-hosting.ru", "PP answer", htmlspecialchars($body), $headers);
	
	if ($action=="login") {
		$tmp=createArrayAnswer($body);
	} else {
		$tmp=$body;
	}
	return $tmp;
}
function createArrayAnswer($body) {
	//$body = '{"Login":"apitest", "Password":"apitest"}';
	$res=array();
	$tmp=substr($body,1,strlen($string)-1);
	$atmp=explode(",",$tmp);
	//print_r($atmp);
	//echo "<br>";
	foreach($atmp as $a) {
		$b=explode(":",$a);
		$res[substr($b[0],1,strlen($string)-1)]=(($b[1]=="null") ? $b[1] : substr($b[1],1,strlen($string)-1));
	}

	
	return $res;
}

function ClientReturnAddress() {
	$res="";
	$res.="\"ClientReturnAddress\":"; //	”<Адрес клиентского возврата>”
	$res.="{";
	$res.="\"CityName\":\"Москва\",";
	$res.="\"RegionName\":\"Москва\",";
	$res.="\"Address\":	\"а\/я 3\",";
	$res.="\"FIO\":	\"\",";
	$res.="\"PostCode\": \"125212\",";
	$res.="\"Organisation\": \"\",";
	$res.="\"PhoneNumber\":	\"+74952255492\",";
	$res.="\"Comment\":\"\"";
	$res.="},";
	//$res=iconv("WINDOWS-1251","UTF-8", $res);
	return $res;
}

function SenderCity() {
	$res="";
	$res.="\"SenderCity\":"; //	”<Адрес клиентского возврата>”
	$res.="{";
	$res.="\"CityName\":\"Москва\",";
	$res.="\"RegionName\":\"Москва\"";
	$res.="},";
	//$res=iconv("WINDOWS-1251","UTF-8", $res);
	return $res;
}

function UnclaimedReturnAddress() {
	$sending.="\"UnclaimedReturnAddress\":";	//\"<Адрес возврата невостребованного>\"
	$sending.="{";
	$sending.="\"CityName\":\"Москва\",";
	$sending.="\"RegionName\":\"Москва\",";
	$sending.="\"Address\":\"а\/я 3\",";
	$sending.="\"FIO\":\"\",";
	$sending.="\"PostCode\":\"125212\",";
	$sending.="\"Organisation\":\"\",";
	$sending.="\"PhoneNumber\":\"+74952255492\",";
	$sending.="\"Comment\":\"\"";
	$sending.="}";
	//$sending=iconv("WINDOWS-1251","UTF-8", $sending);
	return $sending;
}

function SubEncloses($o) {
	//echo $o."<br>";
	$sending="";
	$sending.="\"SubEncloses\":";	 //<Субвложимые>
	$sending.="[";
	$sql="SELECT Netshop_OrderGoods.*,Message57.Name,Message57.ItemID FROM Netshop_OrderGoods 
		INNER JOIN Message57 ON (Message57.Message_ID=Netshop_OrderGoods.item_id)
		WHERE Order_ID=".$o;
	$i=1;
	if ($r1=mysql_query($sql)) {
		$n=mysql_num_rows($r1);
		while ($row=mysql_fetch_array($r1)) {
			//$name=addslashes($row['Name']);
			
			//echo strlen($name);
			$sending.="{";
			$sending.="\"Line\":\"{$i}\",";
			$sending.="\"ProductCode\":\"{$row['ItemID']}\",";
			$sending.="\"GoodsCode\":\"{$row['ItemID']}\",";
			$sending.="\"Name\":\"".addslashes($row['Name'])."\",";
			$sending.="\"Price\":\"{$row['ItemPrice']}\"";
			$sending.="}";
			$i=$i+1;
			$sending.=($i<$n) ? "," : "";
		}
	}

/*	
	$sending.="\"Line\":		\"<Номер>\",";
	$sending.="\"ProductCode\":	\"<Код продукта>\",";
	$sending.="\"GoodsCode\":	\"<Код товара>\",";
	$sending.="\"Name\":		\"<Наименование>\",";
	$sending.="\"Price\":		<Стоимость>"; */
	
	$sending.="]\n"; 
	//$sending=iconv("WINDOWS-1251","UTF-8", $sending);
	//return $sending;
	return "";
} 
//$body = '{"Login":"apitest", "Password":"apitest"}';
//$a=sendReq($body);
//print_r($a);
?>
