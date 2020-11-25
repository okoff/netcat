<?php
// 22.05.2014 Elen
// печать заказа для курьера
include_once ("../../../../vars.inc.php");
session_start();

// проверка авторизации ---------------------------------------------------------------------------
//if (($_SESSION['insideadmin']!=1) && (!isset($_SESSION['nc_token_rand']))) {
//	$url="/netcat/modules/netshop/interface/login.php?jump=".$_SERVER['SCRIPT_NAME'];
//	die('<script type="text/javascript">window.location.href="' . $url . '";</script>');
//}
// ------------------------------------------------------------------------------------------------
include_once ("utils.php");
include_once ("utils-numtotext.php");
$incoming = parse_incoming();
$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
if (!$con) {
	die('Could not connect: ' . mysql_error());
}

mysql_select_db($MYSQL_DB_NAME, $con);
//mysql_set_charset("cp1251", $con);
mysql_set_charset("utf8", $con);
$where="";
//print_r($incoming);
if ((!isset($incoming['id']))||(!intval($incoming['id']))) {
	die('Неверный номер заказа');
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<title>Заказы</title>
	<style>
	body, td {
		font-size:12pt;
		font-family:Tahoma;
	}
	body {
		margin:0 10px;
		padding:0;
	}
	</style>
</head>
<body>

<?php
	$dlvcost=0;
//if ((isset($_SESSION['nc_token_rand'])) || ((isset($_SESSION['insideadmin'])) && ($_SESSION['insideadmin']==1))) {
	$sql="SELECT * FROM Message51 
		WHERE Message_ID=".intval($incoming['id']);
	//echo $sql;
	if ($result=mysql_query($sql)) {
		while($row = mysql_fetch_array($result)) { 
		if ($row['paid']!=1) {
?>
<table cellpadding='0' cellspacing='0' border='0' style='width:800px;'> 
<tr><td style='vertical-align:center;'>
<h2 style='margin:0;padding:0;'>Заказ №<?php echo $row['Message_ID']; ?> от <?php echo date("d.m.Y", strtotime($row['Created'])); ?></h2>
</td><td style='vertical-align:center;font-size:10pt;text-align:right;'>
<b>Магазин Русские Ножи</b><br>
http://www.russian-knife.ru<br>
Тел.: +7 495 225-54-92
</td>
</tr></table><br>
<table cellpadding='2' cellspacing='0' border='0'>
	<tr><td style='text-align:right;'>ФИО получателя:</td><td style='font-size:14pt;'><?php echo $row['ContactName']; ?></td></tr>
	<tr><td style='text-align:right;'>Контактный телефон:</td><td style='font-size:20pt;font-weight:bold;'><?php echo $row['Phone']; ?></td></tr>
	<tr><td style='text-align:right;'>E-mail:</td><td style='font-size:14pt;'><?php echo $row['Email']; ?></td></tr>
	<tr><td style='text-align:right;'>Город:</td><td style='font-size:14pt;'><?php echo $row['Town']; ?></td></tr>
	<!--tr><td style='text-align:right;'>Индекс:</td><td style='font-size:14pt;'><?php echo $row['PostIndex']; ?></td></tr-->
	<tr><td style='text-align:right;'>Улица:</td><td style='font-size:14pt;'><?php echo $row['Street']; ?></td></tr>
	<tr><td style='text-align:right;'>Дом, корпус:</td><td style='font-size:14pt;'><?php echo $row['House']; ?></td></tr>
	<tr><td style='text-align:right;'>Номер квартиры (офиса):</td><td style='font-size:14pt;'><?php echo $row['Flat']; ?></td></tr>
</table><br>

<?php
	$sql="SELECT Netshop_OrderGoods.*, Message57.ItemID AS ItemID, Message57.Name AS Name   FROM Netshop_OrderGoods 
		INNER JOIN Message57 ON (Netshop_OrderGoods.Item_ID=Message57.Message_ID)
		WHERE Order_ID=".$row['Message_ID'];
	//echo $sql;
	$itog=0;
	$summa=0;
	$result1=mysql_query($sql);
?>
<table cellpadding='2' cellspacing='0' border='1' style='width:800px;'>
	<tr>
		<td style='font-weight:bold;text-align:center;font-size:10pt;' colspan='2'>Товар</td>
		<td style='font-weight:bold;text-align:center;font-size:10pt;'>Цена</td>
		<td style='font-weight:bold;text-align:center;font-size:10pt;'>Скидка</td>
		<td style='font-weight:bold;text-align:center;font-size:10pt;'>Цена со скидкой</td>
		<td style='font-weight:bold;text-align:center;font-size:10pt;'>Количество</td>
		<td style='font-weight:bold;text-align:center;font-size:10pt;'>Стоимость</td>
	</tr>
<?
	while ($row1 = mysql_fetch_array($result1)) {
	?>
		<tr>
			<td width='70' style='font-size:10pt;'><?php echo $row1['ItemID']; ?></td>
			<td style='font-size:10pt;'><?php echo $row1['Name']; ?></td>
			<td style='width:60px;text-align:right;padding-right:10px;font-size:10pt;'><?php echo $row1['OriginalPrice']; ?></td>
			<td style='width:60px;text-align:right;padding-right:10px;font-size:10pt;'><?php echo ($row1['OriginalPrice']-$row1['ItemPrice']); ?></td>
			<td style='width:60px;text-align:right;padding-right:10px;font-size:10pt;'><?php echo $row1['ItemPrice']; ?></td>
			<td style='width:60px;text-align:center;padding-right:10px;font-size:10pt;'><?php echo $row1['Qty']; ?></td>
			<td style='width:60px;text-align:right;padding-right:10px;font-size:10pt;'><?php echo ($row1['ItemPrice']*$row1['Qty']); ?></td>
		</tr>
<?php
		$itog=$itog+$row1['ItemPrice']*$row1['Qty'];
		$summa=$summa+$row1['ItemPrice']*$row1['Qty'];
	}
?>
		<tr><td colspan='6' style='font-size:10pt;'><b>СТОИМОСТЬ ТОВАРОВ</b></td><td style='width:60px;text-align:right;padding-right:10px;font-size:10pt;'><b><?php echo $itog; ?></b></td></tr>
		<tr><td colspan='6' style='font-size:10pt;'>Способ доставки:
<?php
	$sql="SELECT * FROM Message56 WHERE Message_ID=".$row['DeliveryMethod'];
	$result1=mysql_query($sql);
	while ($row1 = mysql_fetch_array($result1)) {
		echo $row1['Name'];
	}
	
	$itog=$itog+$row['DeliveryCost'];
	$dlvcost=$row['DeliveryCost'];
?>
		</td>
		<td style='width:60px;text-align:right;padding-right:10px;font-size:10pt;'><b><?php echo $row['DeliveryCost']; ?></b></td></tr>
		
<?php
$sql="SELECT * FROM Netshop_OrderDiscounts WHERE Order_ID=".$row['Message_ID']." AND Item_ID=0";
	$result1=mysql_query($sql);
	while ($row1 = mysql_fetch_array($result1)) {
		echo "<tr><td colspan='6' style='font-size:10pt;'>Скидка:".$row1['Discount_Name']."</td>
		<td style='width:60px;text-align:right;padding-right:10px;font-size:10pt;'><b>".$row1['Discount_Sum']."</b></td></tr>";
		$itog=$itog-$row1['Discount_Sum'];
	}
	
?>
		<tr><td colspan='6' style='font-size:10pt;'><b>ИТОГО</b></td><td style='width:60px;text-align:right;padding-right:10px;font-size:10pt;'><b><?php echo $itog; ?></b></td></tr>
		
	</table><br>
	<textarea style="width:800px; height:100px;font-size:14pt;"></textarea>
	<br><br>
	<table cellpadding='2' cellspacing='0' border='0' style='width:800px;border-top:3px double #000;'>
	<tr><td colspan='2'><br><br></td></tr>
	<tr><td style='width:50%;'>
		<p>Заказ <b>№<?php echo $row['Message_ID']; ?></b> от <?php echo date("d.m.Y", strtotime($row['Created'])); ?><br>
		ФИО получателя: <?php echo $row['ContactName']; ?><br>
		Стоимость товаров: <b><?php echo ($itog-$dlvcost); ?></b> руб.<br>
		ИТОГО: <b><?php echo $itog; ?></b> руб.</p>
		<?php
		if ($row['paid']==1) {
			echo "<p>ОПЛАЧЕНО</p>";
		}
		?>
		</td>
	<td  style='width:50%;'>
		<p>Дата:_________________________________</p>
		<p>Принял: _____________________/__________________</p>
	</td>
	</tr>
	</table>
<?php			

} else {
// ЗАКАЗ ОПЛАЧЕН ----------------------------------------------------------------------------------



?>
<h2 style='margin:0;padding:0;font-size:12pt;'>Расходная накладная №<?php echo $row['Message_ID']; ?> от <?php echo date("d.m.Y", strtotime($row['Created'])); ?></h2>
<hr>
<p style='font-size:10pt;'>Поставщик: <b>ООО &quot;РУССКАЯ КОМПАНИЯ&quot;, ИНН: 7722328347, Адрес: 111033, Москва г, Таможенный пр, дом № 6, корпус 3, Телефон: 495 225-54-92</b></p>
<p style='font-size:10pt;'>Покупатель: <b><?php echo $row['ContactName']; ?></b></p>
<p style='font-size:10pt;'>Контактный телефон: <b><?php echo $row['Phone']; ?></b></p>
<p style='font-size:10pt;'>Адрес: <b><?php echo $row['Town']; ?>, ул.<?php echo $row['Street']; ?>, д.<?php echo $row['House']; ?>, кв.<?php echo $row['Flat']; ?></b></p>


<table cellpadding='2' cellspacing='0' border='1' style='width:800px;'>
	<tr>
		<td style='font-weight:bold;text-align:center;font-size:10pt;'>№</td>
		<td style='font-weight:bold;text-align:center;font-size:10pt;'>Артикул</td>
		<td style='font-weight:bold;text-align:center;font-size:10pt;'>Товар</td>
		<td style='font-weight:bold;text-align:center;font-size:10pt;' colspan='2'>Мест</td>
		<td style='font-weight:bold;text-align:center;font-size:10pt;' colspan='2'>Количество</td>
		<td style='font-weight:bold;text-align:center;font-size:10pt;'>Цена</td>
		<td style='font-weight:bold;text-align:center;font-size:10pt;'>Сумма</td>
		<td style='font-weight:bold;text-align:center;font-size:10pt;'>Скидка</td>
		<td style='font-weight:bold;text-align:center;font-size:10pt;'>Всего</td>
		<td style='font-weight:bold;text-align:center;font-size:10pt;'>Номер ГТД<hr>Страна происхождения</td>
	</tr>
<?php
	$sql="SELECT Netshop_OrderGoods.*, Message57.ItemID AS ItemID, Message57.Name AS Name,Classificator_Country.Country_Name   FROM Netshop_OrderGoods 
		INNER JOIN Message57 ON (Netshop_OrderGoods.Item_ID=Message57.Message_ID)
		LEFT JOIN 	Classificator_Country ON (Country_ID=Message57.country)
		WHERE Order_ID=".$row['Message_ID'];
	$itog=0;
	//echo $sql;
	$summa=0;
	$result1=mysql_query($sql);
	//var_dump($result1);
	$j=1;
	while ($row1 = mysql_fetch_array($result1)) {
	?>
		<tr>
			<td style='font-size:10pt;'><?php echo $j; ?></td>
			<td style='font-size:10pt;'><?php echo $row1['ItemID']; ?></td>
			<td style='font-size:10pt;'><?php echo $row1['Name']; ?></td>
			<td style='font-size:10pt;width:50px;'>&nbsp;</td>
			<td style='font-size:10pt;'>шт.</td>			
			<td style='font-size:10pt;width:50px;text-align:center;'><?php echo $row1['Qty']; ?></td>
			<td style='font-size:10pt;'>шт.</td>
			<td style='width:50px;text-align:right;padding-right:10px;font-size:10pt;'><?php echo $row1['OriginalPrice']; ?></td>
			<td style='width:50px;text-align:right;padding-right:10px;font-size:10pt;'><?php echo ($row1['OriginalPrice']*$row1['Qty']); ?></td>
			<td style='width:50px;text-align:right;padding-right:10px;font-size:10pt;'><?php echo ($row1['OriginalPrice']-$row1['ItemPrice']); ?></td>
			<td style='width:50px;text-align:right;padding-right:10px;font-size:10pt;'><?php echo ($row1['ItemPrice']*$row1['Qty']); ?></td>
			<td style='font-size:10pt;'><?=$row1['Country_Name']?></td>
		</tr>
<?php
		$itog=$itog+$row1['ItemPrice']*$row1['Qty'];
		$summa=$summa+$row1['ItemPrice']*$row1['Qty'];
		
		$j=$j+1;
	}

	/*$sql="SELECT * FROM Message56 WHERE Message_ID=".$row['DeliveryMethod'];
	$result1=mysql_query($sql);
	while ($row1 = mysql_fetch_array($result1)) {
		//echo $row1['Name'];
	}*/
	
	$itog=$itog+$row['DeliveryCost'];
	$dlvcost=$row['DeliveryCost'];

?>
		<tr>
			<td style='font-size:10pt;'><?php echo $j; ?></td>
			<td style='font-size:10pt;'>Доставка</td>
			<td style='font-size:10pt;'>Доставка</td>
			<td style='font-size:10pt;width:50px;'>&nbsp;</td>
			<td style='font-size:10pt;'>&nbsp;</td>			
			<td style='font-size:10pt;width:50px;text-align:center;'>1</td>
			<td style='font-size:10pt;'>шт.</td>
			<td style='width:50px;text-align:right;padding-right:10px;font-size:10pt;'><?php echo $dlvcost; ?></td>
			<td style='width:50px;text-align:right;padding-right:10px;font-size:10pt;'><?php echo $dlvcost; ?></td>
			<td style='width:50px;text-align:right;padding-right:10px;font-size:10pt;'>0</td>
			<td style='width:50px;text-align:right;padding-right:10px;font-size:10pt;'><?php echo $dlvcost; ?></td>
			<td>&nbsp;</td>
		</tr>

<?php
$sql="SELECT * FROM Netshop_OrderDiscounts WHERE Order_ID=".$row['Message_ID']." AND Item_ID=0";
	$result1=mysql_query($sql);
	while ($row1 = mysql_fetch_array($result1)) {
		echo "<tr><td colspan='10' style='font-size:10pt;'><b>Скидка:</b></td>
		<td style='width:50px;text-align:right;padding-right:10px;font-size:10pt;'><b>".$row1['Discount_Sum']."</b></td><td>&nbsp;</td></tr>";
		$itog=$itog-$row1['Discount_Sum'];
	}
	
?>		
		<tr><td colspan='10' style='font-size:10pt;text-align:right;'><b>Итого:</b></td>
			<td style='width:50px;text-align:right;padding-right:10px;font-size:10pt;'><b><?php echo $itog; ?></b></td>
			<td>&nbsp;</td>
		</tr><tr><td colspan='10' style='font-size:10pt;text-align:right;'><b>Без налога (НДС)</b></td>
			<td colspan="2">&nbsp;</td>
		</tr>
	</table>
	
	<p style='font-size:10pt;'>Всего наименований <?=$j?>, на сумму <?=$itog?><br>
<?php			
	$mt = new ManyToText();
	echo $mt->Convert($itog);
?></p>
	<hr>
	<br>
	<table cellpadding='2' cellspacing='0' border='0' style='width:800px;'>
	<tr><td style='width:40%;font-size:10pt;'>
		Отпустил: _______________________________________
		</td>
		<td style='font-size:10pt;'>
		Получил: _______________________________________
		</td>
	</tr>
	</table>
	<br><br>
	<br><br>
	<br><br>
	<textarea style="width:800px; height:100px;font-size:14pt;"></textarea>
	<br><br>
<?
		}
	}
}
	
//} else { 
//	echo "<p>У вас нет прав для просмотра этой страницы</p><p><a href='/netcat/modules/netshop/interface/login.php?jump={$_SERVER['REQUEST_URI']}'>Вход</a></p>";
//}
?>
</body>
</html>
<?php

mysql_close($con);
?>
