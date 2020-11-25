<?php
// 21.11.2013 Elen
// создание нового клиента 
include_once ("../../../../vars.inc.php");
include_once ("utils-complect.php");
//error_reporting(E_ALL);
session_start();
// ------------------------------------------------------------------------------------------------


// get discount for an item
// @id = Message_ID in Message57

function printCrt($str, $dsc) {
	$html="";
	$tmp=array();
	$tmp=explode(";",$str);
	//print_r($tmp);
	$total=0;
	$j=0;
	$_SESSION['items']="";
	$html.="
	<table cellpadding='2' cellspacing='0' border='1'>";
	foreach($tmp as $t) {
		if ($t) {
			$itm=explode(":",$t);
			//print_r($itm);
			if ($itm[1]!=0) {
				$result = mysql_query("SELECT * FROM Message57 WHERE Message_ID=".$itm[0]);
				while($row = mysql_fetch_array($result)) {
					$discount=getDiscount($row['Message_ID'], $row['Price']);
					$html.="<tr><td>".$row['ItemID']."
					<input type='hidden' name='iid[{$j}]' id='iid[{$j}]' value='{$row['Message_ID']}'>
					<input type='hidden' name='price[{$j}]' id='price[{$j}]' value='{$row['Price']}'>
					</td>";
					//$image_arr = explode(":", $row['Image']);
					//$image_url = "/netcat_files/".$image_arr[3];
					//$html.="<td><img src='".$image_url."' width='300'></td>";
					$html.="<td>{$row['Name']} [{$row['StockUnits']}]</td>";
					$html.="<td style='text-align:right;'>{$row['Price']}</td>";
					$html.="<td><input type='text' value='".$discount."' name='itemprice[{$j}]' id='itemprice_{$j}' style='width:70px;'></td>";
					$html.="<td><input type='text' value='{$itm[1]}' name='qty[{$j}]' id='qty_{$j}' style='width:30px;'></td>";
					$total=$total+$discount*$itm[1];
					$html.="<td><a href='#' onclick='delItem({$j});'><img src='/images/icons/del.png' border='0'></a></td>";
					//$html.="<td><input type='button' value='Добавить' onclick='addToOrder(".$row['Message_ID'].", ".$row['Price'].")'></td>";
					$html.="</tr>";
					
					$_SESSION["items"].=$row['Message_ID'].":".$itm[1].":".$row['Price'].":".$discount.";";
					$j=$j+1;
				}
				
			}
		}
	}
	//print_r($_SESSION);
	$total=$total-$dsc;
	$html.="<tr>
	<td colspan='3' style='text-align:right;'>Итого:</td><td><b>{$total}</b></td><td colspan='2'>&nbsp;</td></tr>
	<tr>
	<td colspan='6' style='text-align:center;'><input type='button' onclick='recalcCart();' value='Пересчитать'>
	<input type='button'  onclick='saveCart();' value='Сохранить'>
	</td></tr>";
	$html.="</table>";
	return $html;
}
function getCartCost($str, $dsc=0) {
	$tmp=explode(";",$str);
	//print_r($tmp);
	$total=0;
	foreach($tmp as $t) {
		if ($t) {
			$itm=explode(":",$t);
			//print_r($itm);
			if ($itm[1]!=0) {
				$sql="SELECT Price FROM Message57 WHERE Message_ID={$itm[0]}";
				if ($res=mysql_query($sql)) {
					if ($row1=mysql_fetch_array($res)) {
						$price=$row1['Price'];
						
					}
				}
				$itm[2]=((trim($itm[2])=="") ? $price : $itm[2]);
				$itm[3]=((trim($itm[3])=="") ? $price : $itm[3]);
				$total=$total+$itm[3]*$itm[1];
			}
		}
	}
	$total=$total-$dsc;
	return $total;
}
function saveCartGoods($retail_id,$str) {
	$html="";
	$tmp=explode(";",$str);
	//print_r($tmp);
	$total=0;
	$j=0;
	foreach($tmp as $t) {
		if ($t) {
			$itm=explode(":",$t);
			//print_r($itm);
			//echo "<br>";
			// $itm[0] - item id 
			// $itm[1] - qty in retail order
			// $itm[2] - original price
			// $itm[3] - price in retail order with discounts
			if ($itm[1]!=0) {
				$price=0;
				$sql="SELECT Price FROM Message57 WHERE Message_ID={$itm[0]}";
				if ($res=mysql_query($sql)) {
					if ($row1=mysql_fetch_array($res)) {
						$price=$row1['Price'];
						
					}
				}
				$itm[2]=((trim($itm[2])=="") ? $price : $itm[2]);
				$itm[3]=((trim($itm[3])=="") ? $price : $itm[3]);
				//print_r($itm);
				$sql="INSERT INTO Retails_goods (retail_id, item_id, qty, originalprice, itemprice) 
					VALUES ({$retail_id}, {$itm[0]}, {$itm[1]}, {$itm[2]}, {$itm[3]})";
				//echo $sql;
				if (!mysql_query($sql)) {
					die($sql." Error: ".mysql_error());
				}
				// списание товара!
				$sql="SELECT StockUnits, status, Name,complect FROM Message57 WHERE Message_ID={$itm[0]}";
				if ($res=mysql_query($sql)) {
					if ($row1=mysql_fetch_array($res)) {
						//echo $row1["StockUnits"]."<br>";
						if ($row1['complect']!="") {
							// хватит ли товара для списания всего комплекта? 
							$rescheck=checkFullComplect($row1['complect'],$itm[1]);
							if ($rescheck!=1) {
								$html.=$rescheck;
								$html.="<p>На складе нет товара <b>".$row1['Name']."</b>. Списание не возможно.</p>";
								//return "0";
							} else {
								// списание комплекта
								if (writeoffComplect($itm[0],$row1['complect'],$itm[1],$retail_id)==1) {
									$html.="<p>Комплект <b>".$row1['Name']."</b> списан.</p>";
								}
							}
						} else {
							if ($row1["StockUnits"]<$itm[1]) {
								// ОШИБКА
								//$html.="<p>На складе нет товара ".iconv("windows-1251","utf-8",$row1['Name']).". Списание не возможно.</p>";
								$html.="<p>На складе нет товара <b>".$row1['Name']."</b>. Списание не возможно.</p>";
							} else {
								if ($row1["StockUnits"]>$itm[1]) {
									// просто обновить количество
									$sql="UPDATE Message57 SET StockUnits=StockUnits-{$itm[1]} WHERE Message_ID={$itm[0]}";
									mysql_query($sql);
									
								}
								if ($row1["StockUnits"]==$itm[1]) {
									// обновить количество и изменить статус 
									$sql="UPDATE Message57 SET StockUnits=0, status=3, special=0, new=0, Name='- {$row1['Name']}' WHERE Message_ID={$itm[0]}";
									//echo $sql."<br>";
									mysql_query($sql);
								}
								//$html.="<p>Товар ".iconv("windows-1251","utf-8",$row1['Name'])." списан.</p>";
								$html.="<p>Товар <b>".$row1['Name']."</b> списан.</p>";
								
							}
						}
					}
					$sql="INSERT INTO Writeoff (retail_id, item_id, qty) VALUES ({$retail_id}, {$itm[0]}, {$itm[1]})";
					mysql_query($sql);
				}
			}
		}
	}
	$_SESSION['items']="";
	return $html;
}
// ------------------------------------------------------------------------------------------------

include_once ("utils.php");

$incoming = parse_incoming();
$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
if (!$con) {
	die('Could not connect: ' . mysql_error());
}

mysql_select_db($MYSQL_DB_NAME, $con);
//mysql_set_charset("cp1251", $con);
mysql_set_charset("utf8", $con);

$userinfo="";
$cart="";
$search="";
(isset($_SESSION['cid'])) ? $cid=$_SESSION['cid'] : $cid=0;
(isset($_SESSION['items'])) ? $cart=printCrt($_SESSION['items'],$_SESSION['discount']) : $cart="";
if ($incoming['dscpro']!="") {
	$_SESSION['discount']=getCartCost($_SESSION['items'])*($incoming['dscpro']/100);
}
($incoming['dscrub']!="") ? $_SESSION['discount']=$incoming['dscrub'] : "";	
//print_r($incoming);
//echo "<br>";
//print_r($_SESSION);
switch ($incoming['subaction']) {
	case "copyord":
		//print_r($incoming);
		if ((isset($incoming['oid']))&&(is_numeric($incoming['oid']))) {
			$_SESSION['copyord']=intval($incoming['oid']);
			$sql="SELECT User_ID FROM Message51 WHERE Message_ID=".intval($incoming['oid']);
			//echo $sql;
			$result=mysql_query($sql);
			while($row = mysql_fetch_array($result)) {
				$_SESSION['cid']=$row['User_ID'];
			}
			$sql="SELECT * FROM Netshop_OrderGoods WHERE Order_ID=".intval($incoming['oid']);
			$_SESSION['ordid']=intval($incoming['oid']);
			$result=mysql_query($sql);
			$_SESSION['items']="";
			while($row = mysql_fetch_array($result)) {
				$_SESSION['items'].=$row['Item_ID'].":".$row['Qty'].":".$row['ItemPrice'].":".$row['OriginalPrice'].";";
				//print_r($row);
				//echo "<br>";
			}
			
			
			$userinfo=printUserInfo($_SESSION['cid']);
			$cart=printCrt($_SESSION['items'], $_SESSION['discount']);
		}
		break;
	default:break;
} 
switch ($incoming['action']) {
	case "item-add":
		$_SESSION['items'].=$incoming['articul'].":1:".$incoming['price'].":".$incoming['itemprice'].";";
		$cart=printCrt($_SESSION['items'], $_SESSION['discount']);
		$incoming['articul']="";
		break;
	case "item-search":
		if ($incoming['art']!="") {
			$search.="
			<input type='hidden' value='{$cid}' name='cid' id='cid'>
			<input type='hidden' value='' name='articul' id='articul'>
			<input type='hidden' value='' name='price' id='price'>
			<input type='hidden' value='' name='itemprice' id='itemprice'>
			<table>";
			$result = mysql_query("SELECT * FROM Message57 WHERE ItemID LIKE '%".$incoming['art']."%' ORDER BY ItemID ASC");
			while($row = mysql_fetch_array($result)) {
				$search.="<tr><td colspan='2'><b>[{$row['ItemID']}]</b> {$row['Name']}</td><tr>";
				$search.="<tr><td>На складе: {$row['StockUnits']}</td><td>".getDiscount($row['Message_ID'], $row['Price'])." <b>{$row['Price']}</b> руб.</td><tr>";
				$image_arr = explode(":", $row['Image']);
				$image_url = "/netcat_files/".$image_arr[3];
				$search.="<tr><td><img src='".$image_url."' width='300'></td>";
				$search.="<td><input type='button' value='Добавить' onclick='addToOrder(".$row['Message_ID'].", ".$row['Price'].", ".getDiscount($row['Message_ID'], $row['Price']).")'></td>";
				$search.="</tr>";
			}
			$search.="</table>
			</fieldset>";
		}
		break;
	case "cli-search":
		if ($incoming['request_method']=="post") {
			$tmp="";
			//print_r($incoming);
			$tmp.=((isset($incoming['sphone'])) && ($incoming['sphone']!="")) ? "phone LIKE '%{$incoming['sphone']}%'" : "";
			$tmp.=((isset($incoming['slogin'])) && ($incoming['slogin']!="")) ? ((strlen($tmp)>0) ? " OR " : "")."Login LIKE '%{$incoming['slogin']}%'" : "";
			$tmp.=((isset($incoming['semail'])) && ($incoming['semail']!="")) ? ((strlen($tmp)>0) ? " OR " : "")."Email LIKE '%{$incoming['semail']}%'" : "";
			$tmp.=((isset($incoming['sdiscountcard'])) && ($incoming['sdiscount']!="")) ? ((strlen($tmp)>0) ? " OR " : "")."discountcard LIKE '%{$incoming['sdiscountcard']}%'" : "";
			$sql="SELECT * FROM User ".((strlen($tmp)>0) ? " WHERE ".$tmp : "")." ORDER BY Login ASC";//WHERE phone LIKE '%{$incoming['phone']}%' OR Login LIKE '%{$incoming['login']}%' OR Email LIKE '%{$incoming['email']}%' ".(($incoming['discountcard']) ? " OR discountcard LIKE '{$incoming['discountcard']}'" : "");
			//echo $sql;
			if ($result=mysql_query($sql)) {
				$userinfo="<table cellpading='2' cellspacing='0' border='1'>
				<tr><td>ФИО</td><td>e-mail</td><td>Телефон</td><td>Диск. карта</td><td></td></tr>";
				while ($row = mysql_fetch_array($result)) { 
					$uid=$row['User_ID'];
					if ($uid) {
						$userinfo.="<tr><td><a href='/netcat/modules/netshop/interface/clients.php?action=history&cid={$uid}'>{$row['Login']}</a></td>
						<td>{$row['Email']}</td> 
						<td>{$row['phone']}</td> 
						<td>{$row['discountcard']}</td>
						<td><a href='/netcat/modules/netshop/interface/retail-addnew.php?cid={$uid}'><img src='/images/icons/basket_put.png' title='создать заказ'></a></td></tr>";
					}
				}
				$userinfo.="</table>";
			}
		}
		break;
	case "cli-add":
		if ($incoming['request_method']=="post") {
			$sql="SELECT * FROM User WHERE Email LIKE '{$incoming['email']}' ".
				(($incoming['discountcard']) ? " OR discountcard LIKE '{$incoming['discountcard']}'" : "")." ".
				(($incoming['phone']) ? " OR phone LIKE '{$incoming['phone']}'" : "")." ".
				(($incoming['login']) ? " OR Login LIKE '{$incoming['login']}'" : "");
			if ($result=mysql_query($sql)) {
				$userinfo.="<table cellpading='2' cellspacing='0' border='1'>";
				while ($row = mysql_fetch_array($result)) { 
					$uid=$row['User_ID'];
					if ($uid) {
						$userinfo.="<tr><td><a href='/netcat/modules/netshop/interface/clients.php?action=history&cid={$uid}'>{$row['Login']}</a></td>
						<td>{$row['Email']}</td> 
						<td>{$row['phone']}</td> 
						<td>{$row['discountcard']}</td>
						<td><a href='/netcat/modules/netshop/interface/retail-addnew.php?cid={$uid}'>Создать чек для этого клиента</a></td></tr>";
					}
				}
				$userinfo.="</table>";
			}
			if (!$uid) {
				$sql="INSERT INTO User (Email, Login,Password,PermissionGroup_ID, Checked, Language,Confirmed,Catalogue_ID,InsideAdminAccess, Created,discountcard,phone) 
						VALUES ('{$incoming['email']}','{$incoming['login']}','111',2,1,'Russian',1, 1,0,'".date("Y-m-d H:i:s")."','{$incoming['discountcard']}','{$incoming['phone']}')";
				if (mysql_query($sql)) {
					$userinfo.="Клиент создан<br>";
					$sql="SELECT User_ID FROM User WHERE Email LIKE '{$incoming['email']}' ORDER BY User_ID DESC LIMIT 1";
					if ($result1=mysql_query($sql)) {
						if ($row1 = mysql_fetch_array($result1)) { 
							$userinfo.="<p><a href='/netcat/modules/netshop/interface/retail-addnew.php?cid={$row1['User_ID']}'>Создать чек для этого клиента</a></p>";
						}
					}
				} else {
					die($sql."Ошибка: ".mysql_error());
				}
			}
		}
		break;
	case "cli-anonim":
		$_SESSION['cid']=-1;
		header("Location:/netcat/modules/netshop/interface/retail-addnew.php?cid=0");
		break;
	case "cart-recalc":
		//print_r($incoming);
		$_SESSION['items']="";
		for ($j=0;$j<count($incoming['iid']); $j++) {
			if ($incoming['qty'][$j]) {
				$_SESSION['items'].=$incoming['iid'][$j].":".$incoming['qty'][$j].":".$incoming['price'][$j].":".$incoming['itemprice'][$j].";";
			}
		}
			
		$cart=printCrt($_SESSION['items'], $_SESSION['discount']);
		break;
	case "cart-save":
		//print_r($incoming);
		$_SESSION['items']="";
		for ($j=0;$j<count($incoming['iid']); $j++) {
			if ($incoming['qty'][$j]) {
				$_SESSION['items'].=$incoming['iid'][$j].":".$incoming['qty'][$j].":".$incoming['price'][$j].":".$incoming['itemprice'][$j].";";
			}
		}
		($cid==0) ? $cid=-1 : "";
		//print_r($_SESSION);
		$summ=getCartCost($_SESSION['items'], $_SESSION['discount']);
		//echo $summ;
		($incoming['cash']==1) ? $cash=1 : $cash=0;
		($incoming['paid']==1) ? $paid=1 : $paid=0;
		($incoming['closed']==1) ? $closed=1 : $closed=0;
		($incoming['fixed']==1) ? $fixed=1 : $fixed=0;
		$dte=date("Y-m-d H:i:s");
		$sql="INSERT INTO Retails (user_id, cash, paid, closed, summ, created, comments, discount,fixed,summ1,order_id) 
			VALUES ({$cid}, {$cash}, {$paid}, {$closed}, {$summ}, '".$dte."', '".quot_smart($incoming['comment'])."', 
			".(($_SESSION['discount']) ? $_SESSION['discount'] : 0 ).",{$fixed},".(($incoming['summ1']) ? $incoming['summ1'] : 0).",
			".(($_SESSION['copyord']) ? intval($_SESSION['copyord']) : "NULL" ).")";

		if (mysql_query($sql)) {
			$_SESSION['retailok']=1;
			//header("Location:/netcat/modules/netshop/interface/retail-list.php");
			
		} else {
			die($sql."Ошибка: ".mysql_error());
		}
		$sql="SELECT id FROM Retails ORDER BY id DESC LIMIT 1";
		$retail_id=0;
		if ($result1=mysql_query($sql)) {
			if ($row1 = mysql_fetch_array($result1)) { 
				$retail_id=$row1['id'];
			}
		}
		$ressave=saveCartGoods($retail_id, $_SESSION['items']);
		//echo "!!!".$ressave."<br>";
		if ($ressave!="0") {
			$html.=$ressave;
			$html.="<h2>Заказ сохранен</h2>
			<p><a href='/netcat/modules/netshop/interface/retail-list.php'>Перейти в список продаж</a></p>
			";
		} else {
			$_SESSION['retailok']=0;
			$html.="<p style='color:#f30000;font-weight:bold;'>Заказ сохранен, но комплект не списан</p>
			<p><a href='/netcat/modules/netshop/interface/retail-list.php'>Перейти в список продаж</a></p>";
		}
		
		if ((isset($_SESSION['ordid']))&&($_SESSION['ordid'])) {
			$sql="SELECT * FROM Message51 WHERE Message_ID=".intval($_SESSION['ordid']);
			if ($result=mysql_query($sql)) {
				if ($row = mysql_fetch_array($result)) { 
					if ($row['writeoff']==1) {
						// отменить списание
						$html.="<p>Отменяем списание по заказу ".intval($_SESSION['ordid'])."</p>";
						$sql="SELECT * FROM Netshop_OrderGoods WHERE Order_ID=".intval($_SESSION['ordid']);
						if ($result=mysql_query($sql)) {
							while($row = mysql_fetch_array($result)) {
								$sql="SELECT StockUnits, status, Name,complect FROM Message57 WHERE Message_ID={$row['Item_ID']}";
								if ($res=mysql_query($sql)) {
									if ($row1=mysql_fetch_array($res)) {
										if ($row1["complect"]!="") {
											$arset=explode(";",$row1['complect']);
											//print_r($arset);
											foreach($arset as $ars) {
												$ars=str_replace(" ","",$ars);
												if ($ars!="") {
													$t=explode(":",$ars);
													$sql="SELECT Message_ID,StockUnits,ItemID,status, Name,complect FROM Message57 WHERE ItemID LIKE '{$t[0]}'";
													//echo $sql;
													if ($res1=mysql_query($sql)) {
														if ($row2=mysql_fetch_array($res1)) {
															if ($row2['StockUnits']>0) {
																// просто обновить количество
																$sql="UPDATE Message57 SET StockUnits=".($row2['StockUnits']+$t[1])." WHERE Message_ID={$row2['Message_ID']}";
																//echo $sql."<br>";
																mysql_query($sql);
															}
															if ($row2["StockUnits"]==0) {
																// обновить количество и изменить статус 
																$sql="UPDATE Message57 SET StockUnits={$t[1]}, status=2, Name='".substr($row2['Name'], 2)."' WHERE Message_ID={$row2['Message_ID']}";
																//echo $sql."<br>";
																mysql_query($sql);
															}
															echo "Списание по товару [{$row2['ItemID']}] ".iconv("windows-1251","utf-8",$row2['Name'])." в комплекте отменено.<br>";
															$sql="UPDATE Message51 SET writeoff=0 WHERE Message_ID={$incoming['id']}";
															//echo $sql."<br>";
															mysql_query($sql);
														}
													}
												}
											}
										} else {
											//echo $row1["StockUnits"]."<br>";
											if ($row1["StockUnits"]>0) {
												// просто обновить количество
												$sql="UPDATE Message57 SET StockUnits=StockUnits+{$row['Qty']} WHERE Message_ID={$row['Item_ID']}";
												//echo $sql."<br>";
												mysql_query($sql);
											}
											if ($row1["StockUnits"]==0) {
												// обновить количество и изменить статус 
												$sql="UPDATE Message57 SET StockUnits={$row['Qty']}, status=2, Name='".substr($row1['Name'], 2)."' WHERE Message_ID={$row['Item_ID']}";
												//echo $sql."<br>";
												mysql_query($sql);
											}
											echo "Списание по товару ".iconv("windows-1251","utf-8",$row1['Name'])." отменено.<br>";
											$sql="UPDATE Message51 SET writeoff=0 WHERE Message_ID={$incoming['id']}";
											//echo $sql."<br>";
											mysql_query($sql);
										}
									}
								}
							}
						} else {
							die("Ошибка: {$sql} " . mysql_error());
						}
						// отмена списания ------------------------------------
					}
				}
			}
			// закрыть заказ. Статус=выполнен,чек=№чека
			$sql="UPDATE Message51 SET retail_id={$retail_id},Status=4,closed=1,writeoff=0 WHERE Message_ID=".intval($_SESSION['ordid']);
			if (!mysql_query($sql)) {
				die($sql."Ошибка: ".mysql_error());
			}
			$_SESSION['ordid']="";
		}
		$_SESSION['items']="";
		$_SESSION['copyord']="";
		$_SESSION['cid']="";
		break;
	default: 
		if ((isset($incoming['start'])) && ($incoming['start'])) {
			$_SESSION['items']="";
			$_SESSION['discount']="";
		}
		if (isset($incoming['cid'])) {
			$_SESSION['cid']=$incoming['cid'];
			$userinfo=printUserInfo($incoming['cid']);
		}
		if (isset($_SESSION['cid'])) {
			$userinfo=printUserInfo($_SESSION['cid']);
		}
		if (isset($_SESSION['items'])) {
			$cart=printCrt($_SESSION['items'],$_SESSION['discount']);
		}
		break;
}
//print_r($_SESSION);
if (isset($_SESSION['cid'])) {
	$userinfo=printUserInfo($_SESSION['cid']);
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Продажи. Создание нового чека.</title>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
<?php 
if ($_SESSION['retailok']==1) {
	$_SESSION['retailok']=0;
	echo "
	<script type=\"text/javascript\">
		setTimeout('window.location.replace(\"/netcat/modules/netshop/interface/retail-list.php\")', 3000);
	</script>";
}
?>
	<script type="text/javascript">
		function showAnonim() {
			document.getElementById("addclient").style.display="none";
			document.getElementById("findclient").style.display="none";
			document.getElementById('action').value='cli-anonim';
			document.getElementById("frm").submit();
		}
		function showAddNewClient() {
			document.getElementById("addclient").style.display="block";			
			document.getElementById("findclient").style.display="none";
		}
		function submitAddClient() {
			document.getElementById('action').value='cli-add';
			document.getElementById("frm").submit();
		}
		function showFindClient() {
			document.getElementById("findclient").style.display="block";
			document.getElementById("addclient").style.display="none";
		}
		function submitFindClient() {
			document.getElementById('action').value='cli-search';
			document.getElementById("frm").submit();
		}
		function addToOrder(mesid, price) {
			//alert(mesid);
			document.getElementById('action').value='item-add';			
			document.getElementById('articul').value=mesid;
			document.getElementById("frm").submit();
		}
		function delItem(itemid) {
			document.getElementById('qty_'+itemid).value=0;
			document.getElementById('action').value='cart-recalc';
			document.getElementById('frm').submit();
		}
		function recalcCart() {
			document.getElementById('action').value='cart-recalc';
			document.getElementById('frm').submit();
		}
		function saveCart() {
			document.getElementById('action').value='cart-save';
			document.getElementById('frm').submit();
		}
		function searchItem() {
			document.getElementById('action').value='item-search';
			document.getElementById('frm').submit();
		}
		function showCopy() {
			if (document.getElementById('copyfromorder').style.display=='none') {
				document.getElementById('copyfromorder').style.display='block';
			} else {
				document.getElementById('copyfromorder').style.display='none';
			}
		}
	</script>
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	#addclient, #findclient {
		padding-left:50px;
	}
	</style>
</head>
<body>
	
<?php
if ((isset($_SESSION['nc_token_rand'])) || ((isset($_SESSION['insideadmin'])) && ($_SESSION['insideadmin']==1))) {
	//print_r($incoming);
	

	echo printMenu();

?>
<h1>Новый чек</h1>
<div style='border:solid 1px #909090;width:500px; padding:5px;'>
<a href="#" onclick="showCopy();">Скопировать из заказа</a><br>
<div id="copyfromorder" style='display:none;'>
	<form id="frm1" name="frm1" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
	<input type='hidden' name='subaction' id='subaction' value='copyord'>
	<input type='text' value='' name='oid' id='oid'>
	<input type='submit' value='Скопировать'>
	</form><br>
</div>
</div><br>
<form id="frm" name="frm" action="/netcat/modules/netshop/interface/retail-addnew.php" method="post">
<input type="hidden" id="action" name="action" value="">
<table cellpadding='2' cellspacing='0' border='1'>
<tr><td style='width:15%;'><input type='checkbox' name='cash' id='cash' value='1' checked>Наличные</td>
	<td style='width:15%;'><input type='checkbox' name='paid' id='paid' value='1' checked>Оплачено</td>
	<td style='width:15%;'><input type='checkbox' name='closed' id='closed' value='1' checked>Закрыт</td>
	<td style='width:15%;'><input type='checkbox' name='fixed' id='fixed' value='1' <?php echo (($incoming["fixed"]) ? "checked" : ""); ?>>Касса</td>
	<td style='width:20%;'>Сумма нал.</td>
	<td style='width:20%;'><input type='text' name='summ1' id='summ1' size='10'></td>
</tr>
<tr>
	<td colspan='6'>Комментарий<br>
	<input type='text' name='comment' id='comment' size='100'></td></tr>
</table>
<br class="clear">
<h3>Клиент:</h3>
<table cellpadding="0" cellspacing="3" border="0">
<tr><td style="vertical-align:top;">
<?php
echo $userinfo;
?>
</td><td style="vertical-align:top;">
<input type="radio" name="utype" id="utype0" value="0" onclick="showAnonim();" <?php echo ((($_SESSION['cid']==-1) || ($_SESSION['cid']=="")) ? "checked" : ""); ?>><label for="utype0">Розничный покупатель</label><br>
<input type="radio" name="utype" id="utype1" value="1" onclick="showAddNewClient();"><label for="utype1">Добавить нового клиента</label><br>
<div id="addclient" style="display:none;">
	<b>Новый клиент</b>
	<table cellpadding="0" cellspacing="2" border="0">
		<tr><td>ФИО</td><td><input type="text" name="login" id="login" value=""></td></tr>
		<tr><td>e-mail</td><td><input type="text" name="email" id="email" value=""></td></tr>
		<tr><td>Дисконтная карта</td><td><input type="text" name="discountcard" id="discountcard" value=""></td></tr>
		<tr><td>Телефон</td><td><input type="text" name="phone" id="phone" value=""></td></tr>
		<tr><td colspan='2'><input type="button" value="Сохранить" onclick="submitAddClient();"></td></tr>
	</table>
</div>
<input type="radio" name="utype" id="utype2" value="2" onclick="showFindClient();"><label for="utype2">Найти клиента</label><br>
<div id="findclient" style="display:none;">
<b>Поиск клиента</b>
<table cellpadding="0" cellspacing="2" border="0">
	<tr><td>ФИО</td><td><input type="text" name="slogin" id="slogin" value="<?php echo ((isset($incoming['login'])) ? $incoming['login'] : ""); ?>"></td></tr>
	<tr><td>e-mail</td><td><input type="text" name="semail" id="semail" value="<?php echo ((isset($incoming['email'])) ? $incoming['email'] : ""); ?>"></td></tr>
	<tr><td>Дисконтная карта</td><td><input type="text" name="sdiscountcard" id="sdiscountcard" value="<?php echo ((isset($incoming['discountcard'])) ? $incoming['discountcard'] : ""); ?>"></td></tr>
	<tr><td>Телефон</td><td><input type="text" name="sphone" id="sphone" value="<?php echo ((isset($incoming['phone'])) ? $incoming['phone'] : ""); ?>"></td></tr>
	<tr><td colspan='2'><input type="button" value="Найти" onclick="submitFindClient();"></td></tr>
</table>
</div>

</td></tr>
</table>
<h3>Корзина</h3>
<table cellpadding='2' cellspacing='0' border='1'>
<tr>
	<td>Скидка на сумму по корзине:</td>
	<td><input type="text" value="<?php echo ((isset($incoming['dscpro'])) ? $incoming['dscpro'] : "" ); ?>" id="dscpro" name="dscpro" style="text-align:right;width:50px;">%</td>
	<td><input type="text" value="<?php echo ((isset($incoming['dscrub'])) ? $incoming['dscrub'] : "" ); ?>" id="dscrub" name="dscrub" style="text-align:right;width:50px;">руб.</td>
	<td><input type="button" value="Применить" onclick="recalcCart();"></td>
</tr>
</table>
<table>
<tr>
<td style='vertical-align:top;'><?php echo $cart; ?></td>
<td style='vertical-align:top;padding-left:20px;'>
<p><b>Добавление товара</b></p>
	<input type='hidden' value='<?php echo $cid; ?>' name='cid' id='cid'>
	<label for='art'>Артикул:</label><input type='text' id='art' name='art' value=''>
	<input type='button' value='Найти' onclick="searchItem();">

<?php echo $search; ?>
</td>
</tr>
</table>

</form>
<?php
	echo $html;
} else {
	echo "<p>У вас нет прав для просмотра этой страницы</p><p><a href='/netcat/modules/netshop/interface/login.php'>Вход</a></p>";
}
?>
</body>
</html>
<?php

mysql_close($con);
?>
