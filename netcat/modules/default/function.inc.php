<?
/* $Id: function.inc.php 5319 2011-09-08 14:08:57Z andrey $ */
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );

include_once ($NETCAT_FOLDER."vars.inc.php");

if (!class_exists("nc_System")) die("Unable to load file.");
global $MODULE_FOLDER;


function your_func () {
}

function printBillCdmBank($order_id,$order_sum=0) {
	global $db,$SKEY_CDM;
	
	$lmi_desc="";
	
	$r = $db->get_results("SELECT `Message51`.*, `Classificator_ShopOrderStatus`.`ShopOrderStatus_Name`, `Message56`.`Name` AS `DeliveryName`,
				`Message55`.`Name` AS `PaymentName`
				FROM `Message51`
				LEFT JOIN `Message56` ON (`Message51`.`DeliveryMethod` = `Message56`.`Message_ID`)
				LEFT JOIN `Message55` ON (`Message51`.`PaymentMethod` = `Message55`.`Message_ID`)
				LEFT JOIN `Classificator_ShopOrderStatus` ON (`Message51`.`Status` = `Classificator_ShopOrderStatus`.`ShopOrderStatus_ID`)
				WHERE `Message51`.`Message_ID`=".intval($order_id)." ORDER BY Created DESC", ARRAY_A); 
	if (!empty($r)) {
		$inorder="";
		$order_sum=0;
		foreach($r AS $row) {
			$order_sum=0;
			$j++;
			
			$order_sum = $order_sum + $row['DeliveryCost'];
			
			// состав заказа
			$res1 = $db->get_results("SELECT `Netshop_OrderGoods`.*, `Message57`.`Name`, `Message57`.`ItemID`
				FROM `Netshop_OrderGoods`
				INNER JOIN `Message57` ON (`Message57`.`Message_ID` = `Netshop_OrderGoods`.`Item_ID`)
				WHERE `Order_ID`=".$row['Message_ID']." ORDER BY ItemPrice ASC", ARRAY_A); 
			if (!empty($res1)) {
				$k=1;
				$inorder="<p><b>".convstr("Оплата заказа")."</b></p>";
				//$inorder.=((isset($row['barcode'])) && (strlen($row['barcode'])>3)) ? "<p>Баркод отправления: <b>{$row['barcode']}</b></p><br>" : "";
				//$inorder.=(isset($row['weight'])) ? "<p>Вес отправления: {$row['weight']} кг.</p><br>" : "";
				$inorder.="<table cellpadding='3' cellspacing='0' border='1' class='tbl_order' width='100%' style='background:#fff;'>\n";
				$inorder.="<tr><td>#</td><td>".convstr("Наименование")."</td>
					<td>".convstr("Цена")."</td>
					<td>".convstr("Заказано")."</td>
					<td>".convstr("Сумма")."</td></tr>";
				foreach($res1 AS $row1) {
					$order_sum = $order_sum + $row1['ItemPrice']*$row1['Qty'];
					$inorder.="<tr>
									<td>".$k."</td><td><b>[{$row1['ItemID']}]</b> ".$row1['Name']."</td>
									<td>".$row1['ItemPrice']." ".convstr("руб.")."</td>
									<td>".$row1['Qty']." ".convstr("шт.")."</td>
									<td align='right'>".$row1['ItemPrice']*$row1['Qty']." ".convstr("руб.")."</td>
							</tr>";
					
					$lmi_desc.="<input type='hidden' name='LMI_SHOPPINGCART.ITEMS[".($k-1)."].NAME' id='LMI_SHOPPINGCART.ITEMS[".($k-1)."].NAME' value='".$row1['ItemID']." ".$row1['Name']."'>\n";
					$lmi_desc.="<input type='hidden' name='LMI_SHOPPINGCART.ITEMS[".($k-1)."].QTY' id='LMI_SHOPPINGCART.ITEMS[".($k-1)."].QTY' value='".$row1['Qty']."'>\n";
					$lmi_desc.="<input type='hidden' name='LMI_SHOPPINGCART.ITEMS[".($k-1)."].PRICE' id='LMI_SHOPPINGCART.ITEMS[".($k-1)."].PRICE' value='".$row1['ItemPrice']."'>\n";
					$lmi_desc.="<input type='hidden' name='LMI_SHOPPINGCART.ITEMS[".($k-1)."].TAX' id='LMI_SHOPPINGCART.ITEMS[".($k-1)."].TAX' value='no_vat'>\n";
/*
строка
Наименование позиции в чеке
LMI_SHOPPINGCART.ITEMS[N].QTY
число
Кол-во товара
LMI_SHOPPINGCART.ITEMS[N].PRICE
число
Стоимость одной единицы
LMI_SHOPPINGCART.ITEMS[N].TAX
строка
Ставка НДС, допустимые значения:
vat18 - НДС 18%
vat10 - НДС 10%
vat118 - НДС по формуле 18/118
vat110 - НДС по формуле 10/110
vat0 - НДС 0%
no_vat - НДС не облагается"; */
					
					$k=$k+1;
					
					
				}
				$lmi_desc.="<input type='hidden' name='LMI_SHOPPINGCART.ITEMS[".($k-1)."].NAME' id='LMI_SHOPPINGCART.ITEMS[".($k-1)."].NAME' value='".convstr("Доставка")."'>\n";
				$lmi_desc.="<input type='hidden' name='LMI_SHOPPINGCART.ITEMS[".($k-1)."].QTY' id='LMI_SHOPPINGCART.ITEMS[".($k-1)."].QTY' value='1'>\n";
				$lmi_desc.="<input type='hidden' name='LMI_SHOPPINGCART.ITEMS[".($k-1)."].PRICE' id='LMI_SHOPPINGCART.ITEMS[".($k-1)."].PRICE' value='".$row['DeliveryCost']."'>\n";
				$lmi_desc.="<input type='hidden' name='LMI_SHOPPINGCART.ITEMS[".($k-1)."].TAX' id='LMI_SHOPPINGCART.ITEMS[".($k-1)."].TAX' value='no_vat'>\n";

				$inorder.="<tr><td colspan='4' align='right'>".convstr("ИТОГО:")."</td><td align='right'>".$order_sum." ".convstr("руб.")."</td></tr>";
				$inorder.="<tr><td colspan='4' align='right'>".convstr("Доставка")." (".$row['DeliveryName']."):</td><td align='right'>".$row['DeliveryCost']." ".convstr("руб.")."</td></tr>";
				$inorder.="<tr><td colspan='4' align='right'><b>".convstr("ИТОГО С ДОСТАВКОЙ:")."</b></td><td align='right'><b>".$order_sum." ".convstr("руб.")."</b></td></tr>";
				$inorder.="</table>";
				
				
			}
		}
	}
	$res="";
	
	$terminal="10007777";
	$dte=gmdate("YmdHis");
	$merchant="123456789012345";
	$merch_name="ООО Pусская компания";
	$merch_url="http://".$_SERVER['SERVER_NAME'];
	$desc="Оплата заказа №".$order_id;
	$email="";
	$trtype=1;
	$nonce = substr(sha1(rand(0,getrandmax())),rand(0,24),16);
	$backref="http://".$_SERVER['SERVER_NAME']; //."/netcat/modules/netshop/payment/response/cdmbank.php";
	
	$order_id1=$order_id;
	if (strlen($order_id)<6) {
		$order_id1="0".$order_id;
	}
	
	$macdata=strlen($order_sum).$order_sum.
				"3643".
				strlen($order_id1).$order_id1.
				(strlen($desc)).$desc.
				(strlen($merch_name)).$merch_name.
				strlen($merch_url).$merch_url.
				strlen($merchant).$merchant.
				strlen($terminal).$terminal.
				(($email=="") ? "-" : strlen($email).$email).
				strlen($trtype).$trtype.
				strlen($dte).$dte.
				strlen($nonce).$nonce.
				strlen($backref).$backref;
	
	
	$skey = $SKEY_CDM; //'466B3FE46B9D6030B322EEFAB03BE966';
	//$res.= $NETCAT_FOLDER."<br>".$macdata."<br>".$skey;
	//$skey = '00112233445566778899AABBCCDDEEFF';
	$mac=hash_hmac('sha1',$macdata,hex2bin($skey)); 
	
$res.="
{$inorder}<br><br>
<!--
<script type='text/javascript' src='https://paymaster.ru/ru-RU/widget/Basic/1?LMI_MERCHANT_ID=91369a39-10cb-4e90-9bd6-5025b69d607e&LMI_PAYMENT_NO={$order_id}&LMI_PAYMENT_AMOUNT={$order_sum}&LMI_PAYMENT_DESC={$desc}&LMI_CURRENCY=RUB'></script>
-->

<form id=pay name='pay' method='POST' action='https://paymaster.ru/Payment/Init'>
	<input type='hidden' name='LMI_MERCHANT_ID' id='LMI_MERCHANT_ID' value='91369a39-10cb-4e90-9bd6-5025b69d607e'>
	<input type='hidden' name='LMI_PAYMENT_AMOUNT' id='LMI_PAYMENT_AMOUNT' value='{$order_sum}'>
	<input type='hidden' name='LMI_CURRENCY' id='LMI_CURRENCY' value='RUB'>
	<input type='hidden' name='LMI_PAYMENT_NO' id='LMI_PAYMENT_NO' value='{$order_id}'>
	<input type='hidden' name='LMI_PAYMENT_DESC' id='LMI_PAYMENT_DESC' value='".convstr($desc)."'>
	".$lmi_desc."
	<input type='submit' value='".convstr("Оплатить")."'>
";
/*
<form ACTION='https://3dst.sdm.ru/cgi-bin/cgi_link' METHOD='POST'>
<input type='hidden' name='AMOUNT' id='AMOUNT' value='".$order_sum."'>
<input type='hidden' name='CURRENCY' id='CURRENCY' value='643'>
<input type='hidden' name='ORDER' id='ORDER' value='".$order_id1."'>
<input type='hidden' name='DESC' id='DESC' value='".$desc."'>
<input type='hidden' name='TERMINAL' id='TERMINAL' value='".$terminal."'>
<input type='hidden' name='TRTYPE' id='TRTYPE' value='".$trtype."'>
<input type='hidden' name='MERCH_NAME' id='MERCH_NAME' VALUE='".$merch_name."'>
<input TYPE='hidden' NAME='MERCH_URL' ID='MERCH_URL' VALUE='".$merch_url."'>
<input TYPE='hidden' NAME='MERCHANT' ID='MERCHANT' VALUE='".$merchant."'>
<input TYPE='hidden' NAME='EMAIL' ID='EMAIL' VALUE=''>
<input TYPE='hidden' NAME='TIMESTAMP' ID='TIMESTAMP' VALUE='".$dte."'>
<input TYPE='hidden' NAME='MERCH_GMT' ID='MERCH_GMT' VALUE='+3'>
<input TYPE='hidden' NAME='NONCE' ID='NONCE' VALUE='".$nonce."'>
<input TYPE='hidden' NAME='BACKREF' ID='BACKREF' VALUE='".$backref."'>
<input TYPE='hidden' NAME='P_SIGN' ID='P_SIGN' VALUE='".$mac."'>
<input type='hidden' name='KEY' id='KEY' value='".$skey."'>
<input TYPE='hidden' NAME='MAC_DATA' SIZE='100' VALUE='".$macdata1."'>
<input TYPE='hidden' NAME='MAC' SIZE='40' VALUE='".$mac."'/>";*/

$res.="<div><!--input type='submit' value='".convstr("Оплатить")."'--></div>
</form><br>";

	return $res;
}

function showPaymentMethod($order_id) {
	$res="";
	
	$res.="<p class='hh2'>".convstr("Оплата заказа №").$order_id."</p>";
	$res.=printBillCdmBank($order_id);
	
	return $res;
}
function convstr($str) {
	return iconv("UTF-8","windows-1251//TRANSLIT",$str);
} 
function knifeShowUserOrders () {
	//global $db, $AUTH_USER_ID;
	global $db, $perm, $nc_core;
    //global $AUTHORIZE_BY, $AUTH_TYPE, $ADMIN_AUTHTYPE;
    //global $PHP_AUTH_USER, $PHP_AUTH_PW, $PHP_AUTH_SID, $PHP_AUTH_LANG;
    //global $AUTHORIZATION_TYPE, $MODULE_VARS, $ADMIN_AUTHTIME, $HTTP_HOST, $SUB_FOLDER;
    global $AUTH_USER_ID, $AUTH_USER_GROUP;
    global $sname, $current_user, $catalogue;
	
	//print_r($nc_core);
	
	$userID=0;
	$j=0;
	$result="";
	$inorder="";
	$orderSum=0;
	
	$userID=$AUTH_USER_ID;
	
	$result.=iconv("UTF-8","windows-1251//TRANSLIT","<p class='hh2'>Ваши заказы</p>\n");
	
	$result.="<script type='text/javascript'>
	function showOrder(orderId) {
		//alert(document.getElementById('ord'+orderId).style.display);
		if (document.getElementById('ord'+orderId).style.display=='none') {
			document.getElementById('ord'+orderId).style.display='block';
		} else {
			document.getElementById('ord'+orderId).style.display='none';
		}
	}
	</script>
	
	<div id='order'>
	
	</div>";
	
	// get a secret key
	$secret_key='GKoKoCxEDPBrHUSC';
	
	$res = $db->get_results("SELECT `Message51`.*, `Classificator_ShopOrderStatus`.`ShopOrderStatus_Name`, `Message56`.`Name` AS `DeliveryName`,
				`Message55`.`Name` AS `PaymentName`
				FROM `Message51`
				LEFT JOIN `Message56` ON (`Message51`.`DeliveryMethod` = `Message56`.`Message_ID`)
				LEFT JOIN `Message55` ON (`Message51`.`PaymentMethod` = `Message55`.`Message_ID`)
				LEFT JOIN `Classificator_ShopOrderStatus` ON (`Message51`.`Status` = `Classificator_ShopOrderStatus`.`ShopOrderStatus_ID`)
				WHERE `Message51`.`User_ID`=".$userID." ORDER BY Created DESC", ARRAY_A); 
	if (!empty($res)) {
		$inorder="";
		$orderSum=0;
		$result.="<table cellpadding='3' cellspacing='0' border='1' class='tbl_order'>\n";
		$result.="<tr><td>#</td>
			<td><b>".convstr("№ заказа")."</b></td>
			<td><b>".convstr("Дата заказа")."</b></td>
			<td><b>".convstr("Статус заказа")."</b></td>
			<td><b>".convstr("Способ доставки")."</b></td>
			<td><b>".convstr("Способ оплаты")."</b></td>
			<td><b>".convstr("Сумма заказа")."</b></td>
			<td><b>".convstr("Действие с заказом")."</b></td></tr>";
		foreach($res AS $row) {
			$orderSum=0;
			$j++;
			
			$orderSum = $orderSum + $row['DeliveryCost'];
			
			// состав заказа
			$res1 = $db->get_results("SELECT `Netshop_OrderGoods`.*, `Message57`.`Name`, `Message57`.`ItemID`
				FROM `Netshop_OrderGoods`
				INNER JOIN `Message57` ON (`Message57`.`Message_ID` = `Netshop_OrderGoods`.`Item_ID`)
				WHERE `Order_ID`=".$row['Message_ID']." ORDER BY ItemPrice ASC", ARRAY_A); 
			if (!empty($res1)) {
				$k=1;
				$inorder="";
				$inorder.=((isset($row['barcode'])) && (strlen($row['barcode'])>3)) ? convstr("<p>Баркод отправления: ")."<b>{$row['barcode']}</b></p><br>" : "";
				$inorder.=((isset($row['pickpoint_barcode'])) && (strlen($row['pickpoint_barcode'])>3)) ? convstr("<p>Баркод отправления: ")."<b>{$row['pickpoint_barcode']}</b></p><br>" : "";
				$inorder.=((isset($row['cdek_barcode'])) && (strlen($row['cdek_barcode'])>3)) ? convstr("<p>Баркод отправления: ")."<b>{$row['cdek_barcode']}</b></p><br>" : "";
				$inorder.=(isset($row['weight'])) ? convstr("<p>Вес отправления: {$row['weight']} г.</p><br>") : "";
				$inorder.="<table cellpadding='3' cellspacing='0' border='1' class='tbl_order' width='100%' style='background:#fff;'>\n";
				$inorder.="<tr><td>#</td>
					<td>".convstr("Наименование")."</td>
					<td>".convstr("Цена")."</td>
					<td>".convstr("Заказано")."</td>
					<td>".convstr("Сумма")."</td></tr>";
				foreach($res1 AS $row1) {
					$orderSum = $orderSum + $row1['ItemPrice']*$row1['Qty'];
					$inorder.="<tr><td>".$k."</td><td><b>[{$row1['ItemID']}]</b> ".$row1['Name']."</td><td>".$row1['ItemPrice']." ".convstr("руб").".</td>
						<td>".$row1['Qty']." ".convstr("шт.")."</td><td align='right'>".$row1['ItemPrice']*$row1['Qty']." ".convstr("руб.")."</td></tr>";
					$k=$k+1;
				}
				$inorder.="<tr><td colspan='4' align='right'>".convstr("ИТОГО").":</td><td align='right'>".$orderSum." ".convstr("руб.")."</td></tr>";
				$inorder.="<tr><td colspan='4' align='right'>".convstr("Доставка")." (".$row['DeliveryName']."):</td><td align='right'>".$row['DeliveryCost']." ".convstr("руб").".</td></tr>";
				$inorder.="<tr><td colspan='4' align='right'><b>".convstr("ИТОГО С ДОСТАВКОЙ").":</b></td><td align='right'><b>".$orderSum." ".convstr("руб").".</b></td></tr>";
				$inorder.="</table>";
				//$inorder.=mb_detect_encoding($inorder);
				//$inorder=iconv("UTF-8","windows-1251//TRANSLIT",$inorder);
			}
			
			//$result.=md5($this->shop->secret_key.$this->shop->OrderID);
			
			$result.="<tr><td>".$j."</td><td align='right'><b><a style='cursor:pointer' onclick='showOrder(".$row['Message_ID'].")'>".$row['Message_ID']."</a></b>&nbsp;&nbsp;&nbsp;</td>
				<td>".date("d.m.Y", strtotime($row['Created']))."</td>
				<td>".$row['ShopOrderStatus_Name'].(($row['paid']==1) ? "<br>".convstr("оплачен") : "")."</td>
				<td>".$row['DeliveryName']."</td>
				<td>".$row['PaymentName'];
			if ($row['PaymentMethod']==5) {
				$auto=md5($secret_key.$row['Message_ID']);
				$result.="<br><a target='_blank' href='/netcat/modules/netshop/post.php?action=print_bill&system=sberbank&mode=print_bill&order_id={$row['Message_ID']}&key={$auto}'>".convstr("Распечатать квитанцию")."</a>";
			}	
			//if ($row['PaymentMethod']==15) {
			// пластиковая карта
			if ($row['PaymentMethod']==1) {
				//$result.=$nc_core->payment->print_bill();
				//$this->Payment->print_bill();
				if ((isset($_GET['k']))&&($_GET['k']!="")&&($_GET['k']==md5($row['Message_ID']))) {
					$result.=printBillCdmBank($row['Message_ID'],$orderSum);
				} else {
					if ($row['paid']!=1) {
						$result.="<br><a href='/profile/profile_".$userID.".html?k=".md5($row['Message_ID'])."'>".convstr("Оплатить онлайн")."</a>";
					}
				}
				//$result.="<br><a target='_blank' href='/netcat/modules/netshop/post.php?action=print_bill&system=cdmbank&mode=print_bill&order_id={$row['Message_ID']}'>Оплатить VISA/Master Card</a>";
			}	
			$result.="</td>
				<td align='right' style='white-space:nowrap;'>".$orderSum." ".convstr("руб.")."</td>
				<td align='center'><a style='cursor:pointer' onclick='showOrder(".$row['Message_ID'].")'>".convstr("просмотр")."</a></td>
			</tr>
			<tr>
				<td colspan='8' style='padding:0; border-top:0; border-bottom:0;'>
					<div style='display:none; width:96%; margin:0 auto;' id='ord".$row['Message_ID']."'>
					<br />
					<p><b>".convstr("Состав заказа:")."</b></p><br />
					
					".$inorder."
					<br />
					</div>
				</td>
			</tr>";
// 				INNER JOIN `Message56` ON (`Message51`.`DeliveryMethod` = `Message56`.`Message_ID`)

			/*
			состав заказа
			$res1 = $db->get_results("SELECT *
				FROM `Netshop_OrderGoods`
				WHERE `Order_ID`=".$row['Message_ID']." ORDER BY ItemPrice ASC", ARRAY_A); 
			if (!empty($res1)) {
				foreach($res1 AS $row1) {
					$result.="<tr><td>".$row1['Order_ID']."</td><td>".$row1['Item_ID']."</td></tr>";
				}
			}*/
		}
		$result.="</table><br />";
	}
	//echo mb_detect_encoding($result);
	//$r=iconv("UTF-8","windows1251",$result);
	return $result;
}
?>
