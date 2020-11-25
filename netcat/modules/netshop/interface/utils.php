<?php
//header('Content-type: text/html; charset=utf-8');
function parse_incoming() {
	$return = array();
	
	if (is_array($_GET)) {
		while (list($k, $v) = each($_GET)) {
			if (is_array($_GET[$k])) {
				while (list($k2, $v2) = each($_GET[$k])) {
					//$return[$k][$k2] = $this->clean_value($v2);
					$return[$k][$k2] = $v2;
				}
			} else {
				$return[$k] = $v;
			}
		}
	}
	
	// Overwrite GET data with post data
	if( is_array($_POST) ) {
		while( list($k, $v) = each($_POST) ) {
			if ( is_array($_POST[$k]) ) {
				while( list($k2, $v2) = each($_POST[$k]) ) {
					$return[$k][ $k2] = $v2;
				}
			} else {
				$return[$k] = $v;
			}
		}
	}
	
	$return['IP_ADDRESS'] = $_SERVER['REMOTE_ADDR'];
											 
	$return['IP_ADDRESS'] = preg_replace( "/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})/", "\\1.\\2.\\3.\\4", $return['IP_ADDRESS'] );

	$return['request_method'] = ( $_SERVER['REQUEST_METHOD'] != "" ) ? strtolower($_SERVER['REQUEST_METHOD']) : strtolower($REQUEST_METHOD);
	
	return $return;
}
function sendMail ($id, $to, $fio, $barcode) {
	$from="Интернет-магазин Русские ножи <admin@russian-knife.ru>";
	$subject="Ваш заказ N{$id} в Интернет-магазин Русские ножи";//Ваш заказ N8795 в Интернет-магазин "Русские ножи"
	//$headers='Content-type: text/plain; windows-1251'."\r\n".
	$headers='Content-type: text/plain; UTF-8'."\r\n".
'From: Интернет-магазин Русские ножи <admin@russian-knife.ru>'."\r\n";
	$body="Уважаемый {$fio}!\r\n

Почтовый код вашего заказа: {$barcode}\r\n

Проверка движения заказа возможна на сайте Почты России https://www.pochta.ru/tracking \r\n
С уважением,\r\n
Интернет-магазин \"Русские ножи\"\r\n
Тел.: +7 (495) 225-54-92,  +7 (495) 225-76-84\r\n";

	//mail($to, $subject, iconv("windows-1251","utf-8",$body), $headers);
	mail($to, $subject, iconv("utf-8","windows-1251",$body), $headers);
	
}

// get all categries in folder configuration
function getFileList($dir) { // Returns an array of files in a directory.
	$filelist = array();
	$subdirs = array();
	if ($dirlink = @opendir($dir)) {
		// Creates an array with all file names in current directory.
		while (($file = readdir($dirlink)) !== false) {
			if ($file != "." && $file != "..") { // Hide these two special cases and files and filetypes in blacklists.
				$c = array();
				$c['name'] = $file;
				$c['type'] = "file";
				$c['writeable'] = is_writeable("{$dir}/{$file}");
				//if (checkforedit($file)) {
				//	$c['edit'] = TRUE;
				//}
				// File permissions.
				//if ($c['perms'] = fileperms("{$dir}/{$file}")) {
				//  $c['perms'] = substr(base_convert($c['perms'], 10, 8), 3);
				//}
				// $c['modified'] = filemtime("{$dir}/{$file}");
				//$c['size'] = filesize("{$dir}/{$file}");
				/*if (is_dir("{$dir}/{$file}")) {
					$c['size'] = 0;
					$c['type'] = "dir";
					if ($sublink = @opendir("{$dir}/{$file}")) {
						while (($current = readdir($sublink)) !== false) {
							if ($current != "." && $current != ".." && checkfile($current)) {
								$c['size']++;
							}
						}
						closedir($sublink);
					}
					$subdirs[] = $c;
				} else {
					$filelist[] = $c;
				}*/
				$filelist[] = $c;
			}
		}
		closedir($dirlink);
		sort($filelist);
		sort($subdirs);
		//return array_merge($subdirs, $filelist);
		return $filelist;
	} else {
		return "dirfail";
	}
}
function printCart($oid) {
	$res="<table cellpadding='2' cellspacing='0' border='1' style='width:500px;'>";
	$sql="SELECT Netshop_OrderGoods.*, Message57.ItemID AS ItemID, Message57.Name AS Name   FROM Netshop_OrderGoods 
		INNER JOIN Message57 ON (Netshop_OrderGoods.Item_ID=Message57.Message_ID)
		WHERE Order_ID=".$oid;
	//echo $sql;
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$res.="<tr><td width='70' style='font-size:8pt;'>{$row['ItemID']}</td>
			<td style='font-size:8pt;'>{$row['Name']}</td>
			<td width='30' style='font-size:8pt;'>{$row['Qty']}</td>
			<td width='70' style='font-size:8pt;'>".($row['ItemPrice']*$row['Qty'])."</td></tr>";
	}
	$res.="</table>";
	return $res;
}
function getCountCart($oid) {
	$res=0;
	$sql="SELECT Item_ID FROM Netshop_OrderGoods 
		WHERE Order_ID=".$oid;
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$res=$res+1;
	}
	return $res;
}
function getOrderCount($cid) {
	$res=0;
	$sql="SELECT Message_ID   FROM Message51 
		WHERE User_ID=".$cid;
	//echo $sql;
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$res=$res+1;
	}
	return $res;
}
function getOrderCost($oid) {
	$cost=0;
	$sql="SELECT SUM(ItemPrice*Qty) AS cost FROM Netshop_OrderGoods WHERE Order_ID=".$oid;
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$cost=$row['cost'];
	}
	$sql="SELECT Discount_Sum FROM Netshop_OrderDiscounts WHERE Order_ID=".$oid." AND Item_Type=0 AND Item_ID=0 ";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$cost=$cost-$row['Discount_Sum'];
	}
	return $cost;
}
function getOrderCostNP($oid) {
	$cost=0;
	$sql="SELECT summnp FROM Message51 WHERE Message_ID=".$oid;
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$cost=$row['summnp'];
	}
	return $cost;
}
function getOrderStatus($oid) {
	$cost=0;
	$sql="SELECT Status FROM Message51 WHERE Message_ID=".$oid;
	$result=mysql_query($sql);
	if ($row = mysql_fetch_array($result)) {
		$cost=$row['Status'];
	}
	return $cost;
}
function getOrderStatusById($id) {
	$cost="";
	$sql="SELECT ShopOrderStatus_Name FROM Classificator_ShopOrderStatus WHERE ShopOrderStatus_ID=".$id;
	$result=mysql_query($sql);
	if ($row = mysql_fetch_array($result)) {
		$cost=$row['ShopOrderStatus_Name'];
	}
	return $cost;
}
function convstr_li($str) {
	return iconv("UTF-8","windows-1251//TRANSLIT",$str);
}
function convstrw_li($str) {
	return iconv("windows-1251//TRANSLIT","UTF-8",$str);
} 
function getOrderStatusById_li($con,$oid) {
	$cost="";
	
	$sql="SELECT Classificator_ShopOrderStatus.ShopOrderStatus_Name FROM Message51 
		INNER JOIN Classificator_ShopOrderStatus ON (Classificator_ShopOrderStatus.ShopOrderStatus_ID=Message51.Status)
		WHERE Message_ID=".$oid;
	//echo $sql;
	//$sql="SELECT ShopOrderStatus_Name FROM Classificator_ShopOrderStatus WHERE ShopOrderStatus_ID=".$id;
	$result=db_query($con,$sql);
	while ($row=mysqli_fetch_array($result)) {
		$cost=$row['ShopOrderStatus_Name'];
	}
	return $cost;
}
function getOrderStatusName($oid) {
	$cost=0;
	$sql="SELECT ShopOrderStatus_Name FROM Classificator_ShopOrderStatus 
		INNER JOIN Message51 ON (Message51.Status=Classificator_ShopOrderStatus.ShopOrderStatus_ID) WHERE Message_ID=".$oid;
	$result=mysql_query($sql);
	if ($row = mysql_fetch_array($result)) {
		$cost=$row['ShopOrderStatus_Name'];
	}
	return $cost;
}
function printSellingMenu() {
	$res="<div style='width:250px;border:1px solid #a0a0a0;background:#DEFFDD;text-align:center;'>
	<p><a href='/netcat/modules/netshop/interface/selling-intro.php'>Списания по накладным</a></p>
	<!--<ul style='margin:0 0 0 20px;padding:0;font-size:8pt;'>
	<li style='margin:0 0 5px 0;'><a href='/netcat/modules/netshop/interface/selling.php'>Посмотр и сохранение списаний.</a></li>
	<li style='margin:0 0 5px 0;'><a href='/netcat/modules/netshop/interface/selling-payment.php'>Просмотр неоплаченных позиций. Сохранение оплат.</a></li>
	<li style='margin:0 0 5px 0;'><a href='/netcat/modules/netshop/interface/selling-transfer.php'>Просмотр оплат.</a></li>
	</ul-->
	</div>";
	return $res;
}

function printMenu() {
	$tmp="";
	//echo date("d",mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
	if (date("d",mktime(0, 0, 0, date("m"), date("d")+1, date("Y")))==1) {
		$sql="SELECT created FROM Stats_Goods ORDER BY id DESC LIMIT 1";
		$result=mysql_query($sql);
		if ($row = mysql_fetch_array($result)) {
			$mo=date("m",strtotime($row['created']));
		}
		if ($mo!=date("m")){
			$tmp="<p style='padding:0;margin:2px;text-align:center;'><a href='/netcat/modules/netshop/interface/statistic-month.php' style='font-weight:bold;color:#f30000;'>Сохранить статистику за месяц!</a></p>";
		}
	}
	$res="<div style='width:500px;height:140px;border:1px solid #a0a0a0;background:#DEFFDD;float:right;'>{$tmp}
	<table border='0' cellpadding='0' cellspacing='3'>
	<tr><td style='vertical-align:top;font-size:11px;'>
	<ul style='margin:0 0 0 15px;padding:0;'>
		<li style='padding:0 0 3px 0;'><a href='/netcat/modules/netshop/interface/order-list.php?start=1'>Расширенный поиск заказов</a></li>
		<li style='padding:0 0 3px 0;'><a href='/netcat/modules/netshop/interface/cdm-payments.php'>Оплаты онлайн</a></li>
		<li style='padding:0 0 3px 0;'><a href='/netcat/modules/netshop/interface/order-postfile.php'>Заказы для почты</a></li>
		<li style='padding:0 0 3px 0;'><a href='/netcat/modules/netshop/interface/order-postfile.php?action=view'>Файлы для почты</a></li>
		<li style='padding:0 0 3px 0;'><a href='/netcat/modules/netshop/interface/order-postfile.php?action=narv'>Отчеты об оплате</a></li>
		<li style='padding:0 0 3px 0;'><a href='/netcat/modules/netshop/interface/order-post-history.php'>Загрузить файл трассировки</a></li>
		<li style='padding:0 0 3px 0;'><a href='/netcat/modules/netshop/interface/pickpoint.php'>Работа с PickPoint</a></li>
		<li style='padding:0 0 3px 0;'><a href='/netcat/modules/netshop/interface/cdek.php'>Работа с СДЭК</a></li>
	</ul>
	</td><td style='vertical-align:top;font-size:11px;'>
	<ul style='margin:0 0 0 15px;padding:0;'>
		<li style='padding:0 0 3px 0;'><a href='/netcat/modules/netshop/interface/actions.php'>Акции</a></li>
		<li style='padding:0 0 3px 0;'><a href='/price-list/'>Прайс лист</a></li>
		<li style='padding:0 0 3px 0;'><a href='/users-with-orders/'>Пользователи с заказами</a></li>
		<li style='padding:0 0 3px 0;'><a href='/netcat/?catalogue=1&sub=57&cc=57'>Скидки</a></li>
		<li style='padding:0 0 3px 0;'><a href='/netcat/modules/netshop/interface/clients.php?start=1'>Клиенты</a></li>
		<li style='padding:0 0 3px 0;'><a href='/netcat/modules/netshop/interface/clients-requests.php'>Заявки</a></li>
		<li style='padding:0 0 3px 0;'><a href='/netcat/modules/netshop/interface/retail-list.php'>Розничные продажи</a></li>
	</ul>
	</td><td style='vertical-align:top;font-size:11px;'>
	<ul style='margin:0 0 0 15px;padding:0;'>
		<li style='padding:0 0 3px 0;'><a href='/netcat/modules/netshop/interface/waybills-list.php'>Накладные прихода</a></li>
		<li style='padding:0 0 3px 0;'><a href='/netcat/modules/netshop/interface/selling-intro.php'>Списания по накладным</a></li>
		<li style='padding:0 0 3px 0;'><a href='/netcat/modules/netshop/interface/statistic.php'>Статистика</a></li>
		<li style='padding:0 0 3px 0;'><a href='/netcat/modules/netshop/interface/suppliers.php'>Поставщики</a> | 
			<a href='/netcat/modules/netshop/interface/suppliers-orders.php'>Заказы</a></li>
		<li style='padding:0 0 3px 0;'><a href='/netcat/modules/netshop/interface/manufacturers.php'>Производители</a></li>
		<li style='padding:0 0 3px 0;'><a href='/netcat/modules/netshop/interface/couriers.php'>Курьеры</a></li>
		<li style='padding:0 0 3px 0;'><a href='/netcat/modules/netshop/interface/models.php'>Модели ножей и сертификаты</a></li>
		<li style='padding:0 0 3px 0;'><a href='/netcat/modules/netshop/export/evotor_nn.php'>Выгрузка товара</a></li>
	</ul>
	</td></tr></table>
	</div>";
	return $res;
}
function quot_smart($value){
	// если magic_quotes_gpc включена - используем stripslashes
	if (get_magic_quotes_gpc()) {
		$value = stripslashes($value);
	}
	// Если переменная - число, то экранировать её не нужно
	// если нет - то окружем её кавычками, и экранируем
	if (!is_numeric($value)) {
		$value = mysql_real_escape_string($value);
	}
	return $value;
}
function printUserInfo($id) {
	$res="";
	$sql="SELECT * FROM User WHERE User_ID=".$id;
	//echo $sql;
	if ($result=mysql_query($sql)) {
		while ($row = mysql_fetch_array($result)) {
			$res.="<b>{$row['Login']}</b><br>
			e-mail: {$row['Email']}<br>
			Дисконтная карта: {$row['discountcard']}<br>
			Телефон: {$row['phone']}";
		}
	}
	$res.="";
	return $res;
}

function getLastInsertID($tbl, $id="id") {
	$res="";
	$sql="SELECT {$id} FROM {$tbl} ORDER BY {$id} DESC LIMIT 1";
	//echo $sql;
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$res.=$row[0];
	}
	return $res;
}
function selectManufacturer($incoming) {
	$res="<select id='manufacturer' name='manufacturer'>
		<option value=''>---</option>";
	$sql="SELECT * FROM Classificator_Manufacturer WHERE Checked=1 ORDER BY Manufacturer_Name ASC";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		//print_r($row);
		$res.="<option value='{$row['Manufacturer_ID']}' ".(($incoming['manufacturer']==$row['Manufacturer_ID']) ? "selected" : "").">{$row['Manufacturer_Name']}</option>";
	}
	$res.="</select>";
	return $res;
}
function getManufacturer($id) {
	$res="";
	$sql="SELECT * FROM Classificator_Manufacturer WHERE Manufacturer_ID={$id} ORDER BY Manufacturer_Name ASC";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		//print_r($row);
		$res.=$row['Manufacturer_Name'];
	}
	return $res;
}
function selectSupplier($incoming,$startsta=1,$onreal=0) {
	//$dsbl=($incoming['wbstatus']==2) ? "disabled" : "";
	$dsbl=($startsta==2) ? "disabled" : "";
	$onreal=($onreal==1) ? " WHERE Onreal=1 " : "";
	$res="<select id='supplier' name='supplier' {$dsbl}>
		<option value=''>---</option>";
	$sql="SELECT * FROM Classificator_Supplier {$onreal} ORDER BY Supplier_Name ASC";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		//print_r($row);
		$res.="<option value='{$row['Supplier_ID']}' ".(($incoming['supplier']==$row['Supplier_ID']) ? "selected" : "").">{$row['Supplier_Name']}</option>";
	}
	$res.="</select>";
	return $res;
}
function selectKontragent($incoming,$startsta=1,$onreal=0) {
	$str="";
	$dsbl=($startsta==2) ? "disabled" : "";
	if (isset($incoming['supplier'])) {
		$sql="SELECT Kontragent FROM Classificator_Supplier WHERE Supplier_ID=".$incoming['supplier'];
		$result=mysql_query($sql);
		while ($row = mysql_fetch_array($result)) {
			$str=substr(str_replace(";",",",$row['Kontragent']), 0, -1); ;
		}
	}
	$res="<select id='kontragent' name='kontragent' {$dsbl}>
		<option value=''>---</option>";
	$sql="SELECT * FROM Classificator_kontragetns WHERE buyer=0 ".(($str) ? "AND kontragetns_ID IN ({$str})" : "")." ORDER BY kontragetns_Name ASC";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		//print_r($row);
		$res.="<option value='{$row['kontragetns_ID']}' ".(($incoming['kontragent']==$row['kontragetns_ID']) ? "selected" : "").">{$row['kontragetns_Name']}</option>";
	}
	$res.="</select>";
	return $res;
}
function getSupplier($id) {
	$res="";
	$sql="SELECT * FROM Classificator_Supplier WHERE Supplier_ID={$id} ORDER BY Supplier_Name ASC";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$res.=$row['Supplier_Name'];
	}
	return $res;
}
function selectSteel($incoming,$startsta=1) {
	//$dsbl=($incoming['wbstatus']==2) ? "disabled" : "";
	$steel="";
	//print_r($incoming);
	/*if ($incoming["supplier"]) {
		$sql="SELECT steel FROM Message57 WHERE supplier=".$incoming["supplier"]." GROUP BY steel";
		$result=mysql_query($sql);
		while ($row = mysql_fetch_array($result)) {
			($steel) ? $steel.=" OR steel_ID=".$row["steel"] : $steel=" WHERE steel_ID=".$row["steel"];
		}
	}*/
	$dsbl=($startsta==2) ? "disabled" : "";
	$res="<select id='steel' name='steel' {$dsbl}>
		<option value=''>---</option>";
	$sql="SELECT * FROM Classificator_steel {$steel} ORDER BY steel_Name ASC";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		//print_r($row);
		$res.="<option value='{$row['steel_ID']}' ".(($incoming['steel']==$row['steel_ID']) ? "selected" : "").">{$row['steel_Name']}</option>";
	}
	$res.="</select>";
	return $res;
}
function selectOrganiz($incoming) {
	$res="<select id='organiz' name='organiz'>
		<option value=''>---</option>";
	$sql="SELECT * FROM Classificator_Organiz WHERE Checked=1 ORDER BY Organiz_ID ASC";
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		//print_r($row);
		$res.="<option value='{$row['Organiz_ID']}' ".(($incoming['organiz']==$row['Organiz_ID']) ? "selected" : "").">{$row['Organiz_Name']}</option>";
	}
	$res.="</select>";
	return $res;
}
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
function getItemNameOnID($id) {
	//echo $id;
	$res="";
	$sql="SELECT ItemID, Name FROM Message57 WHERE Message_ID=".$id;
	$result=mysql_query($sql);
	while ($row = mysql_fetch_array($result)) {
		$res="[".$row['ItemID']."] ".$row['Name'];
	}
	return $res;
} 
function selectCategory($selected="",$prefix="",$prefix1="") {
	$rr=array();
	$html="<select name='{$prefix}category' id='{$prefix1}category'>
	<option value=''>---</option>";
	$sql="SELECT Subdivision.Subdivision_ID,Sub_Class.Sub_Class_ID,Subdivision.Catalogue_ID,Subdivision.Parent_Sub_ID,Subdivision.Subdivision_Name FROM Subdivision, Sub_Class 
			WHERE Sub_Class.Subdivision_ID = Subdivision.Subdivision_ID 
			AND Sub_Class.Class_ID IN (57) AND Subdivision.Checked = 1
			ORDER BY Subdivision.Priority";	
	if ($result=mysql_query($sql)) {
		$i=0;
		while($row = mysql_fetch_array($result)) {
			$rr[$i]=$row;
			$i=$i+1;
		}
		foreach ($rr as $r) {
			// первый уровень каталога
			if ($r['Parent_Sub_ID']==57) {
				$html.="<option value='{$r['Subdivision_ID']}:{$r['Sub_Class_ID']}' ".(($selected=="{$r['Subdivision_ID']}:{$r['Sub_Class_ID']}") ? "selected" : "").">{$r['Subdivision_Name']}</option>";
				// второй уровень каталога
				foreach ($rr as $r1) {
					if ($r1['Parent_Sub_ID']==$r['Subdivision_ID']) {
						$html.="<option value='{$r1['Subdivision_ID']}:{$r1['Sub_Class_ID']}' ".(($selected=="{$r1['Subdivision_ID']}:{$r1['Sub_Class_ID']}") ? "selected" : "").">&nbsp;&nbsp;&nbsp;&nbsp;-{$r1['Subdivision_Name']}</option>";
					}
				}
			}
		}
	}
	$html.="<option value='140:175'>Все для заточки, аксессуары</option>";
	$html.="</select>";
	return $html;
}
function selectCategory4Supplier($incoming,$prefix="") {
	//print_r($incoming);
	$cats=array();
	$i=0;
	if ($incoming["supplier"]) {
		$sql="SELECT Subdivision_ID FROM Message57 WHERE supplier=".$incoming["supplier"]." GROUP BY Subdivision_ID";
		$result=mysql_query($sql);
		while ($row = mysql_fetch_array($result)) {
			//($cats) ? $cats.=" OR Subdivision.Subdivision_ID=".$row["Subdivision_ID"] : $cats=" Subdivision.Subdivision_ID=".$row["Subdivision_ID"];
			$cats[$row["Subdivision_ID"]]=1;
			$i=$i+1;
		}
	}
	//print_r($cats);
	$rr=array();
	$html="<select name='{$prefix}category' id='category'>
	<option value=''>---</option>";
	$sql="SELECT Subdivision.Subdivision_ID,Sub_Class.Sub_Class_ID,Subdivision.Catalogue_ID,Subdivision.Parent_Sub_ID,Subdivision.Subdivision_Name FROM Subdivision, Sub_Class 
			WHERE Sub_Class.Subdivision_ID = Subdivision.Subdivision_ID 
			AND Sub_Class.Class_ID IN (57) AND Subdivision.Checked = 1 
			ORDER BY Subdivision.Priority";	
	//echo $sql;
	if ($result=mysql_query($sql)) {
		$i=0;
		while($row = mysql_fetch_array($result)) {
			$rr[$i]=$row;
			$i=$i+1;
		}
		foreach ($rr as $r) {
			// первый уровень каталога
			if ($r['Parent_Sub_ID']==57) {
				$style=" style='color:#808080;'";
				if ((isset($cats[$r['Subdivision_ID']])) && ($cats[$r['Subdivision_ID']]==1)) {
					$style="";
				}
				$html.="<option {$style} value='{$r['Subdivision_ID']}:{$r['Sub_Class_ID']}' ".(($incoming["category"]=="{$r['Subdivision_ID']}:{$r['Sub_Class_ID']}") ? "selected" : "").">{$r['Subdivision_Name']}</option>";
				// второй уровень каталога
				foreach ($rr as $r1) {
					$style=" style='color:#808080;'";
					if ($r1['Parent_Sub_ID']==$r['Subdivision_ID']) {
						if ((isset($cats[$r1['Subdivision_ID']])) && ($cats[$r1['Subdivision_ID']]==1)) {
							$style="";
						}
						$html.="<option {$style} value='{$r1['Subdivision_ID']}:{$r1['Sub_Class_ID']}' ".(($incoming["category"]=="{$r1['Subdivision_ID']}:{$r1['Sub_Class_ID']}") ? "selected" : "").">&nbsp;&nbsp;&nbsp;&nbsp;-{$r1['Subdivision_Name']}</option>";
					}
				}
			}
		}
	}
	$html.="</select>";
	return $html;
}
function getCourier($id) {
	$res="";
	if ($id) {
		$sql="SELECT * FROM Classificator_Courier WHERE Courier_ID={$id}";
		if ($result=mysql_query($sql)) {
			while($row = mysql_fetch_array($result)) {
				$res=$row['Courier_Name'];
			}
		}
	}
	return $res;
}


?>
