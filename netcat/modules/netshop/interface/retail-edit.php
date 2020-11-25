<?php
// 21.11.2013 Elen
// создание нового клиента 
include_once ("../../../../vars.inc.php");
//error_reporting(E_ALL);
session_start();
// ------------------------------------------------------------------------------------------------


// get discount for an item
// @id = Message_ID in Message57

function printCrt($str,$id,$discountCart=0) {
	$html="";
	$tmp=array();
	$tmp=explode(";",$str);
	//print_r($tmp);
	$total=0;
	$j=0;
	$_SESSION['items']="";
	//echo $discountCart;
	$html.="
	<table cellpadding='2' cellspacing='0' border='1'>";
	foreach($tmp as $t) {
		if ($t) {
			$itm=explode(":",$t);
			//print_r($itm);
			if ($itm[0]==$id) {
				if ($itm[2]!=0) {
					$result = mysql_query("SELECT * FROM Message57 WHERE Message_ID=".$itm[1]);
					while($row = mysql_fetch_array($result)) {
						//$discount=getDiscount($row['Message_ID'], $row['Price']);
						$html.="<tr><td>".$row['ItemID']."
						<input type='hidden' name='iid[{$j}]' id='iid[{$j}]' value='{$row['Message_ID']}'>
						<input type='hidden' name='price[{$j}]' id='price[{$j}]' value='{$itm[3]}'>
						</td>";
						//$image_arr = explode(":", $row['Image']);
						//$image_url = "/netcat_files/".$image_arr[3];
						//$html.="<td><img src='".$image_url."' width='300'></td>";
						$html.="<td>{$row['Name']} [{$row['StockUnits']}]</td>";
						$html.="<td style='text-align:right;'>{$row['Price']}</td>";
						$html.="<td><input type='text' value='{$itm[4]}' name='itemprice[{$j}]' id='itemprice_{$j}' style='width:70px;'></td>";
						$html.="<td><input type='text' value='{$itm[2]}' name='qty[{$j}]' id='qty_{$j}' style='width:30px;'></td>";
						$total=$total+$itm[4]*$itm[2];
						$html.="<td><a href='#' onclick='delItem({$j});'><img src='/images/icons/del.png' border='0'></a></td>";
						//$html.="<td><input type='button' value='Добавить' onclick='addToOrder(".$row['Message_ID'].", ".$row['Price'].")'></td>";
						$html.="</tr>";
						
						$_SESSION["items"].=$id.":".$row['Message_ID'].":".$itm[2].":".$itm[3].":".$itm[4].";";
						$j=$j+1;
					}
					
				}
			}
		}
	}
	//print_r($_SESSION);
	$html.="<tr>
	<td colspan='3' style='text-align:right;'>Итого:</td><td><b>".($total-$discountCart)."</b></td><td colspan='2'>&nbsp;</td></tr>
	<tr>
	<td colspan='6' style='text-align:center;'><input type='button' onclick='recalcCart();' value='Пересчитать'>
	<input type='button'  onclick='saveCart();' value='Сохранить'>
	</td></tr>";
	$html.="</table>";
	return $html;
}
function getCartCost($str,$id) {
	$tmp=explode(";",$str);
	//print_r($tmp);
	$total=0;
	foreach($tmp as $t) {
		if ($t) {
			$itm=explode(":",$t);
			//print_r($itm);
			if ($itm[0]==$id) {
				if ($itm[2]!=0) {
					$total=$total+$itm[4]*$itm[2];
				}
			}
		}
	}
	return $total;
}
function saveCartGoods($retail_id,$str) {
	$html="";
	$tmp=explode(";",$str);
	//echo "<br>{$str}<br>";
	//print_r($tmp);
	$total=0;
	$j=0;
	foreach($tmp as $t) {
		if ($t) {
			$itm=explode(":",$t);
			//print_r($itm);
			// $itm[0] - retail id 
			// $itm[1] - item id 
			// $itm[2] - qty in retail order
			// $itm[3] - original price
			// $itm[4] - price in retail order with discounts
			if ($itm[0]==$retail_id) {
				if ($itm[1]!=0) {
					$sql="INSERT INTO Retails_goods (retail_id, item_id, qty, originalprice, itemprice) 
						VALUES ({$retail_id}, {$itm[1]}, {$itm[2]}, {$itm[3]}, {$itm[4]})";
					//echo $sql;
					if (!mysql_query($sql)) {
						die($sql." Error: ".mysql_error());
					}
					// списание товара!
					$sql="SELECT StockUnits, status, Name, ItemID FROM Message57 WHERE Message_ID={$itm[1]}";
					if ($res=mysql_query($sql)) {
						if ($row1=mysql_fetch_array($res)) {
							//echo $row1["StockUnits"]."<br>";
							if ($row1["StockUnits"]<$itm[2]) {
								// ОШИБКА
								//$html.="<p>На складе нет товара ".iconv("windows-1251","utf-8",$row1['Name']).". Списание не возможно.</p>";
								$html.="<p>На складе нет товара ".$row1['Name'].". Списание не возможно.</p>";
							} else {
								if ($row1["StockUnits"]>$itm[2]) {
									// просто обновить количество
									$sql="UPDATE Message57 SET StockUnits=StockUnits-{$itm[2]} WHERE Message_ID={$itm[1]}";
									mysql_query($sql);
									
								}
								if ($row1["StockUnits"]==$itm[2]) {
									// обновить количество и изменить статус 
									$sql="UPDATE Message57 SET StockUnits=0, status=3, special=0, new=0, Name='- {$row1['Name']}' WHERE Message_ID={$itm[1]}";
									//echo $sql."<br>";
									mysql_query($sql);
								}
								//$html.="<p>Товар ".iconv("windows-1251","utf-8",$row1['Name'])." списан.</p>";
								$html.="<p>Товар [{$row1['ItemID']}] ".$row1['Name']." списан.</p>";
								
							}
						}
						$sql="INSERT INTO Writeoff (retail_id, item_id, qty) VALUES ({$retail_id}, {$itm[1]}, {$itm[2]})";
						mysql_query($sql);
					}
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
(isset($_SESSION['items'])) ? $cart=printCrt($_SESSION['items']) : $cart="";
//print_r($incoming);
//echo "<br>";
//print_r($_SESSION);
$show=1;

switch ($incoming['action']) {
	case "item-add":
		$_SESSION['items']="";
		for ($j=0;$j<count($incoming['iid']); $j++) {
			if ($incoming['qty'][$j]) {
				$_SESSION['items'].=$incoming["id"].":".$incoming['iid'][$j].":".$incoming['qty'][$j].":".$incoming['price'][$j].":".$incoming['itemprice'][$j].";";
			}
		}
		
		$_SESSION['items'].=$incoming["id"].":".$incoming['srch_articul'].":1:".$incoming['srch_price'].":".$incoming['srch_itemprice'].";";
		//print_r($_SESSION);
		$cart=printCrt($_SESSION['items'],$incoming['id'],$incoming['discount']);
		$incoming['articul']="";
		break;
	case "item-search":
		$_SESSION['items']="";
		for ($j=0;$j<count($incoming['iid']); $j++) {
			if ($incoming['qty'][$j]) {
				$_SESSION['items'].=$incoming["id"].":".$incoming['iid'][$j].":".$incoming['qty'][$j].":".$incoming['price'][$j].":".$incoming['itemprice'][$j].";";
			}
		}
		//print_r($_SESSION);
		$cart=printCrt($_SESSION['items'],$incoming['id'],$incoming['discount']);
		
		if ($incoming['art']!="") {
			$search.="
			<input type='hidden' value='{$cid}' name='cid' id='cid'>
			<input type='hidden' value='' name='srch_articul' id='srch_articul'>
			<input type='hidden' value='' name='srch_price' id='srch_price'>
			<input type='hidden' value='' name='srch_itemprice' id='srch_itemprice'>
			<table>";
			$result = mysql_query("SELECT * FROM Message57 WHERE ItemID LIKE '%".$incoming['art']."%' ORDER BY ItemID ASC");
			while($row = mysql_fetch_array($result)) {
				$search.="<tr><td colspan='2'><b>[{$row['ItemID']}]</b> {$row['Name']}</td><tr>";
				$search.="<tr><td>На складе: {$row['StockUnits']}</td><td><b>{$row['Price']}</b> руб.</td><tr>";
				$image_arr = explode(":", $row['Image']);
				$image_url = "/netcat_files/".$image_arr[3];
				$search.="<tr><td><img src='".$image_url."' width='300'></td>";
				$search.="<td><input type='button' value='Добавить' onclick='addToOrder(".$row['Message_ID'].", ".$row['Price'].", ".getDiscount($row['Message_ID'], $row['Price']).")'></td>";
				$search.="</tr>";
			}
			$search.="</table>
			</fieldset>";
		}
		//$cart=printCrt($_SESSION['items'],$incoming['id'],$incoming['discount']);
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
						<td><a href='/netcat/modules/netshop/interface/retail-edit.php?cid={$uid}'><img src='/images/icons/basket_put.png' title='создать заказ'></a></td></tr>";
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
						<td><a href='/netcat/modules/netshop/interface/retail-edit.php?cid={$uid}'>Создать чек для этого клиента</a></td></tr>";
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
							$userinfo.="<p><a href='/netcat/modules/netshop/interface/retail-edit.php?cid={$row1['User_ID']}'>Создать чек для этого клиента</a></p>";
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
		header("Location:/netcat/modules/netshop/interface/retail-edit.php?cid=0");
		break;
	case "cart-recalc":
		//print_r($incoming);
		$_SESSION['items']="";
		for ($j=0;$j<count($incoming['iid']); $j++) {
			if ($incoming['qty'][$j]) {
				$_SESSION['items'].=$incoming["id"].":".$incoming['iid'][$j].":".$incoming['qty'][$j].":".$incoming['price'][$j].":".$incoming['itemprice'][$j].";";
			}
		}
		//print_r($_SESSION);
		$cart=printCrt($_SESSION['items'],$incoming['id'],$incoming['discount']);
		break;
	case "cart-save":
		//print_r($incoming);
		$_SESSION['items']="";
		for ($j=0;$j<count($incoming['iid']); $j++) {
			if ($incoming['qty'][$j]) {
				$_SESSION['items'].=$incoming["id"].":".$incoming['iid'][$j].":".$incoming['qty'][$j].":".$incoming['price'][$j].":".$incoming['itemprice'][$j].";";
			}
		}
		($cid==0) ? $cid=-1 : "";
		//print_r($_SESSION);
		$summ=getCartCost($_SESSION['items'],$incoming["id"]);
		// cancel old check form Retails
		$sql="SELECT * FROM Retails_goods WHERE deleted=0 AND retail_id=".$incoming["id"];
		if ($result=mysql_query($sql)) {
			while ($row = mysql_fetch_array($result)) { 
				$sql="SELECT StockUnits, status, Name, ItemID FROM Message57 WHERE Message_ID={$row['item_id']}";
				if ($res=mysql_query($sql)) {
					if ($row1=mysql_fetch_array($res)) {
						//echo $row1["StockUnits"]."<br>";
						if ($row1["StockUnits"]>0) {
							// просто обновить количество
							$sql="UPDATE Message57 SET StockUnits=".($row1['StockUnits']+$row['qty'])." WHERE Message_ID={$row['item_id']}";
							//echo $sql."<br>";
							mysql_query($sql);
						}
						if ($row1["StockUnits"]==0) {
							// обновить количество и изменить статус 
							$sql="UPDATE Message57 SET StockUnits={$row['qty']}, status=2, Name='".substr($row1['Name'], 2)."' WHERE Message_ID={$row['item_id']}";
							//echo $sql."<br>";
							mysql_query($sql);
						}
						$html.="<p>Списание по товару [{$row1['ItemID']}] ".$row1['Name']." отменено.</p>";
						$sql="UPDATE Retails SET edited='".date("Y-m-d")."' WHERE id={$incoming['id']}";
						//echo $sql."<br>";
						if (!mysql_query($sql)) {
							echo("Error:".$sql."<br>");
						}
						$sql="UPDATE Retails_goods SET deleted=1 WHERE retail_id={$incoming['id']} AND item_id={$row['item_id']}";
						//echo $sql."<br>";
						mysql_query($sql);
					}
				}
			}
		}
		
		// update check
		($incoming['cash']==1) ? $cash=1 : $cash=0;
		($incoming['paid']==1) ? $paid=1 : $paid=0;
		($incoming['closed']==1) ? $closed=1 : $closed=0;
		($incoming['fixed']==1) ? $fixed=1 : $fixed=0;
		($incoming['summ1']!="") ? $summ1=$incoming['summ1'] : $summ1=0;
		$dte=date("Y-m-d H:i:s");
		$sql="UPDATE Retails SET user_id={$cid}, 
			cash={$cash}, paid={$paid}, closed={$closed}, summ={$summ}, edited='".$dte."',fixed={$fixed},summ1={$summ1},comments='".quot_smart($incoming["comment"])."' 
			WHERE id={$incoming['id']}";

		if (mysql_query($sql)) {	
			$html.="<h2>Заказ сохранен</h2>
			<p><a href='/netcat/modules/netshop/interface/retail-list.php'>Перейти в список продаж</a></p>";
		} else {
			die($sql."Ошибка: ".mysql_error());
		}
		
		$html.=saveCartGoods($incoming['id'], $_SESSION['items']);
		$_SESSION['items']="";
		$show=0;
		break;
	default: 
		if (isset($incoming['id'])){
			//($incoming['id']) ? $_SESSION['id']=$incoming['id'] : "";
			if ($incoming['start']==1) {
				$_SESSION['items']="";
				$sql="SELECT * FROM Retails WHERE id={$incoming['id']}";
				if ($result=mysql_query($sql)) {
					if ($chrow = mysql_fetch_array($result)) { 
						//print_r($chrow);
						$incoming['cid']=$chrow['user_id'];
						$incoming['cash']=$chrow['cash'];
						$incoming['paid']=$chrow['paid'];
						$incoming['closed']=$chrow['closed'];
						$incoming['fixed']=$chrow['fixed'];
						$incoming['comments']=$chrow['comments'];
						$incoming['summ1']=$chrow['summ1'];
						$incoming['created']=$chrow['created'];
						$incoming['discount']=$chrow['discount'];
						if ($chrow['user_id']!=-1) {
							$userinfo=printUserInfo($chrow['user_id']);
						}
					}
				}
			
				$sql="SELECT * FROM Retails_goods WHERE retail_id={$incoming['id']} AND deleted=0";
				if ($result=mysql_query($sql)) {
					while ($row = mysql_fetch_array($result)) { 
						$_SESSION['items'].=$incoming['id'].":".$row['item_id'].":".$row['qty'].":".$row['originalprice'].":".$row['itemprice'].";";
					}
				}
			}
			//($incoming['discount']!="") ? $_SESSION['discount']=$incoming['discount'] : "";	
			//print_r($incoming);
			//if (isset($_SESSION['items'])) {
				$cart=printCrt($_SESSION['items'],$incoming['id'],$incoming['discount']);
			//}
			
		}
		break;
}
//echo "<br>";
//print_r($_SESSION);
?>
<!DOCTYPE html>
<html>
<head>
	<title>Продажи. Редактирование чека #<?php echo $incoming['id']; ?>.</title>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
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
			document.getElementById('srch_articul').value=mesid;
			document.getElementById('srch_price').value=price;
			document.getElementById('srch_itemprice').value=price;
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
<h1>Чек #<?php echo $incoming['id']; ?> 
<?php echo ((isset($incoming['created'])) ? " от ".date("d.m.Y",strtotime($incoming['created'])) : "" ); ?>
</h1>

<?php
if ($show) {
?>
<p><a href='/netcat/modules/netshop/interface/order-printcheck.php?rid=<?php echo $incoming['id']; ?>' target='_blank'>РАСПЕЧАТАТЬ ТОВАРНЫЙ ЧЕК</a></p>
<form id="frm" name="frm" action="/netcat/modules/netshop/interface/retail-edit.php" method="post">
<input type="hidden" id="action" name="action" value="">
<input type="hidden" id="id" name="id" value="<?php echo $incoming['id']; ?>">
<table cellpadding='2' cellspacing='0' border='1'>
<tr><td style='width:15%;'><input type='checkbox' name='cash' id='cash' value='1' <?php echo (($incoming['cash']==1) ? "checked" : ""); ?>>Наличные</td>
	<td style='width:15%;'><input type='checkbox' name='paid' id='paid' value='1' <?php echo (($incoming['paid']==1) ? "checked" : ""); ?>>Оплачено</td>
	<td style='width:15%;'><input type='checkbox' name='closed' id='closed' value='1' <?php echo (($incoming['closed']==1) ? "checked" : ""); ?>>Закрыт</td>
	<td style='width:15%;'><input type='checkbox' name='fixed' id='fixed' value='1' <?php echo (($incoming['fixed']==1) ? "checked" : ""); ?>>Касса</td>
	<td style='width:20%;'>Сумма нал.</td>
	<td style='width:20%;'><input type='text' name='summ1' id='summ1' size='10' <?php echo $incoming["summ1"];  ?>></td>
</tr>
<tr><td colspan='6'>Комментарий<br>
<input type='text' name='comment' id='comment' size='100' value='<?php echo $incoming['comments']; ?>'></td></tr>
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
	<td><input type="text" value="<?php echo ((isset($incoming['discount'])) ? $incoming['discount'] : "" ); ?>" id="discount" name="discount" style="text-align:right;width:50px;">руб.</td>
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
}
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
