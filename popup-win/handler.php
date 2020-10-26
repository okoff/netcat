<?php
include "../vars.inc.php";

session_start();

require_once './mailer/Validator.php';
require_once './mailer/ContactMailer.php';

if (!Validator::isAjax() || !Validator::isPost()) {
	echo 'Доступ запрещен!';
	exit;
}
//var_dump($_POST);
$name = isset($_POST['name']) ? trim(strip_tags($_POST['name'])) : null;
$email = isset($_POST['email']) ? trim(strip_tags($_POST['email'])) : null;
$phone = isset($_POST['phone']) ? trim(strip_tags($_POST['phone'])) : null;
$message = isset($_POST['message']) ? trim(strip_tags($_POST['message'])) : null;
$itemid = isset($_POST['itemid']) ? trim(strip_tags($_POST['itemid'])) : '1';
$itemprice = isset($_POST['itemprice']) ? trim(strip_tags($_POST['itemprice'])) : '1';
$itemorprice = isset($_POST['itemorprice']) ? trim(strip_tags($_POST['itemorprice'])) : '1';
$itemart = isset($_POST['itemart']) ? trim(strip_tags($_POST['itemart'])) : '0-11';
$itemname = isset($_POST['itemname']) ? trim(strip_tags($_POST['itemname'])) : 'Нож Японский (дамасская сталь), береста';

if (empty($name) || empty($phone)) {
	echo 'Поля, отмеченные *, обязательны для заполнения.';
	exit;
}

if (($email!="")&&(!Validator::isValidEmail($email))) {
	echo 'E-mail не соответствует формату.';
	exit;
}

if (!Validator::isValidPhone($phone)) {
	echo 'Телефон не соответствует формату.';
	exit;
}
// save order in DB 
// 2 - fast order / type
// 5 - new order / status
	$dte = date("Y-m-d H:i:s"); 
	
	$link = mysqli_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD, $MYSQL_DB_NAME);

	if (!$link) {
		//echo "Ошибка: Невозможно установить соединение с MySQL." . PHP_EOL;
		//echo "Код ошибки errno: " . mysqli_connect_errno() . PHP_EOL;
		//echo "Текст ошибки error: " . mysqli_connect_error() . PHP_EOL;
		echo iconv("UTF-8","windows-1251//TRANSLIT", 'Ошибка подключения к базе данных.');
		exit;
	}
	mysqli_set_charset($link, "utf8");

	$utm = ""; // +OPE
	if (!empty($_SESSION["UTM"])) {
		foreach ($_SESSION["UTM"] as $key => $value) {
			$utm.=$key ."=".$value.";";
		}
	}
	$href = $_SESSION["HREF"];
	
//	error_log("handler.php utm:[".var_export($utm,true)."] ".var_export($href,true).
//		"] REQUEST_URI={".$_SERVER['REQUEST_URI']."] HTTP_REFERER=[".$_SERVER['HTTP_REFERER']."]");	
	
	//mysql_set_charset("utf8", $con);
	
	$sql = 
	"INSERT INTO Message51 (
		Subdivision_ID, Sub_Class_ID, User_ID, 
		Created, 
		ContactName, 
		Email, 
		Phone, 
		Comments, 
		utm,
		href,
		Type, Status, DeliveryCost, PaymentCost)
	VALUES (
		57, 53, 1,
		'".$dte."',
		'".$name."',
		'".$email."',
		'".$phone."',
		'".$message."',
		'".substr(htmlspecialchars($utm,ENT_QUOTES,"cp1251"),0,255)."',
		'".substr(htmlspecialchars($href,ENT_QUOTES,"cp1251"),0,255)."',		
		2, 5, 0, 0)";

	//'".iconv("windows-1251//TRANSLIT", "UTF-8", htmlspecialchars($name))."',
	$result=mysqli_query($link,$sql);
	if ($result==false) {
		die("Error MySQL request: ".mysqli_error($link)."<br>Query: ".$sql);
	}

	// get OrderID
	$sql = "SELECT Message_ID FROM Message51 WHERE Created LIKE '".$dte."' AND ContactName LIKE '".$name."' AND Email LIKE '".$email."' ";
	$result=mysqli_query($link,$sql);
	while ($row = mysqli_fetch_array($result)) {
		$order_id=$row['Message_ID'];
	}

	// save cart
	$sql="INSERT INTO Netshop_OrderGoods (Order_ID, Item_Type, Item_ID, OriginalPrice, ItemPrice, Qty)
				VALUES (".$order_id.", 57, ".$itemid.",".$itemorprice.",".ceil($itemprice).",1)";
	$result=mysqli_query($link,$sql);
	if ($result==false) {
		die("Error MySQL request: ".mysqli_error($link)."<br>Query: ".$sql);
	}
	
	//decrease stock units
	$sql="SELECT * FROM Message57 WHERE Message_ID=".$itemid;
	$res1=mysqli_query($link,$sql);
	$ok=1;
	while ($row1 = mysqli_fetch_array($res1)) {
		if ($row1["complect"]!="") {
			// списываем комплект
			//$ok=$this->writeoffComplect($item_id,$row1["complect"],$qty,$retail_id=0,$order_id);	
		} else {
			if ($row1['StockUnits']<1) {
				// ошибка списания!
				$ok=0;
			} elseif ($row1['StockUnits']==1) {
				// списываем и обновляем название и статус товара
				$sql="UPDATE Message57 SET StockUnits=0, status=3, special=0, new=0, Name='- ".addslashes($row1['Name'])."' WHERE Message_ID={$itemid}";
				$result=mysqli_query($link,$sql);
				if ($result==false) {
					die("Error MySQL request: ".mysqli_error($link)."<br>Query: ".$sql);
				}						
			} else {
				// просто списываем
				$sql="UPDATE Message57 SET StockUnits=StockUnits-1 WHERE Message_ID={$itemid}";
				$result=mysqli_query($link,$sql);
				if ($result==false) {
					die("Error MySQL request: ".mysqli_error($link)."<br>Query: ".$sql);
				}
			}
		}
		//echo $sql."<br>";
		if ($ok==1) {
			$sql="INSERT INTO Writeoff (order_id, item_id, qty, created) VALUES ({$order_id}, {$itemid}, 1, '".date("Y-m-d H:i:s")."')";
			$result=mysqli_query($link,$sql);
			if ($result==false) {
				die("Error MySQL request: ".mysqli_error($link)."<br>Query: ".$sql);
			}
		}
	}
	
	
	$sql="UPDATE Message51 SET writeoff=".$ok." WHERE Message_ID=".$order_id;
	$result=mysqli_query($link,$sql);
	if ($result==false) {
		die("Error MySQL request: ".mysqli_error($link)."<br>Query: ".$sql);
	}

	mysqli_close($link);

if (ContactMailer::send($name, $email, $phone, $message, $itemid, $itemart, $itemname, ceil($itemprice), $itemorprice, $order_id)) {
		
	echo iconv("UTF-8","windows-1251//TRANSLIT",htmlspecialchars($name) . ', ваш заказ №'.$order_id.' успешно отправлен. Наш менеджер свяжется с Вами в ближайшее время. ');
} else {
//	echo iconv("UTF-8","windows-1251//TRANSLIT",'Произошла ошибка! Не удалось отправить сообщение.');
	//echo "!!!!!!!!!!!!!";
	echo 'Произошла ошибка! Не удалось отправить сообщение.';
}
exit;