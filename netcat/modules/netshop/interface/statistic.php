<?php
// 17.01.2014 Elen
// статистика по магазину
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("utils-retail.php");
$incoming = parse_incoming();


// ------------------------------------------------------------------------------------------------

?>
<!DOCTYPE html>
<html>
<head>
	<title>Статистика</title>
	<meta content='text/html;charset=windows1251' http-equiv='content-type' />
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
		vertical-align:top;
	}
	.blck{
		border:1px solid #a0a0a0;
	}
	.blck li {
		padding:5px 0 0 0;
	}
	</style>
</head>
<body>
	
<?php
if ((isset($_SESSION['nc_token_rand'])) || ((isset($_SESSION['insideadmin'])) && ($_SESSION['insideadmin']==1))) {
	echo printMenu();
	$err="";
	if ($incoming['request_method']=="post") {
		if ($incoming['passwd']=="cnjkbwf") {
			$_SESSION['admstat']=1;
		} else {
			$_SESSION['admstat']=0;
			$err="<p style='text-align:center;'>Неверный пароль</p>";
		}
	}
	
	if ((isset($_SESSION['admstat'])) && ($_SESSION['admstat']==1)) {
?>
	<h1>Статистика</h1>
	<table cellpadding="1" cellspacing="10" border="0">
	<tr>
		<td class='blck' style='width:33%;'>
			<ul>
				<li><a href='/netcat/modules/netshop/interface/statistic-post.php'>Почта</a></li>
				<li><a href='/netcat/modules/netshop/interface/statistic-post-debt.php'>Долги почты</a></li>
				<li><a href='/netcat/modules/netshop/interface/postdeliverycost.php'>Стоимость доставки по почте</a></li>
				<li><a href='/netcat/modules/netshop/interface/statistic-deliverycost.php'>Средняя стоимость доставки</a></li>
			</ul>
			<br>
			<ul>
				<li><a href='/netcat/modules/netshop/interface/statistic-cdek-debt.php'>Долги СДЭКа</a></li>
			</ul>
		</td>
		<td class='blck' style='width:33%;'>
			<ul>
				<li><a href='/netcat/modules/netshop/interface/statistic-retail.php'>Розничные продажи</a></li>
				<li><a href='/netcat/modules/netshop/interface/statistic-complect.php'>Статистика по комплектам</a></li>
				<li><a href='/netcat/modules/netshop/interface/statistic-item.php'>Товары</a></li>				
				<li><a href='/netcat/modules/netshop/interface/selling-residue.php?supplier=5'>Списания по накладным. Остатки на складе на текущий момент</a></li>				
				<li><a href='/netcat/modules/netshop/interface/statistic-residue.php'>Статистика по непроданным товарам</a></li>				
			</ul>
		</td>
		<td class='blck' style='width:33%;'>
			<ul>
				<li><a href='/netcat/modules/netshop/interface/statistic-topsale.php'>Топ продаж</a></li>
				<li><a href='/netcat/modules/netshop/interface/statistic-vendor.php'>Сводная таблица по поставщикам</a></li>
				<li><a href='/netcat/modules/netshop/interface/statistic-vendor1.php'>Статистика по поставщику. Проданные товары</a></li>
				<li><a href='/netcat/modules/netshop/interface/statistic-vendor2.php'>Статистика по поставщику. График по месяцам</a></li>	
				<li><a href='/netcat/modules/netshop/interface/statistic-payments.php'>Статистика по поставщику. Оплаты.</a></li>
			
			</ul>
		</td>
	</tr><tr>
		<td class='blck'>
			<ul>
				<li><a href='/netcat/modules/netshop/interface/statistic-month.php'>Статистика за месяц. Сохранение остатков.</a></li>
				<li><a href='/netcat/modules/netshop/interface/statistic-month-remains.php'>Статистика за месяц. Просмотр остатков.</a></li>
				<li><a href='/netcat/modules/netshop/interface/statistic-price.php'>Статистика продаж по ценам.</a></li>
				<li><a href='/netcat/modules/netshop/interface/statistic-order.php'>Статистика по обработке заказов.</a></li>
				<li><a href='/netcat/modules/netshop/interface/statistic-top.php'>Статистика продаж по клиентам.</a></li>
				<li><a href='/netcat/modules/netshop/interface/statistic-vendor3.php'>Статистика по проданным товарам.</a></li>
				<li><a href='/netcat/modules/netshop/interface/statistic-dpmethods.php'>Статистика по способам доставки/оплаты.</a></li>
			</ul>
		</td>
		<td class='blck'>
			<ul>
				<li><a href='/netcat/modules/netshop/interface/selling-residue.php'>Таблица списаний. Остатки на складе</a></li>
				<li><a href='/netcat/modules/netshop/interface/prices.php'>Прайслисты от поставщиков</a></li>
				<li><a href='/netcat/modules/netshop/interface/rkorders.php'>Заказы поставщикам</a></li>
				<br><br>
				<li><a href='/netcat/modules/netshop/interface/statistic-search.php'>Результаты поиска</a></li>
				<li><a target='_blank' href='/netcat/modules/netshop/interface/users-with-orders.php'>Пользователи с выполненными заказами по регионам</a></li>
				<li><a target='_blank' href='/netcat/modules/netshop/interface/users-with-allorders.php'>Пользователи со всеми заказами по регионам</a></li>
		
			</ul>
		</td>
		<td class='blck'>
			<ul>
				<li><a href='/netcat/modules/netshop/interface/articul.php'>Артикул</a></li>
				<li><a href='/netcat/modules/netshop/interface/shop-details.php'>Реквизиты ООО &quot;Нарвал&quot;.</a></li>
				
			</ul>
		</td>
	</tr><tr>
		<td class='blck'>
			<ul>
				<li><a href='/netcat/modules/netshop/interface/waybills-hdredit.php'>Редактировать описание накладной</a></li>
				
			</ul>
		</td>
		<td class='blck'>
			<ul>
				<li><a href='/netcat/modules/netshop/interface/payments-list.php'>Оплаты</a></li>
				
			</ul>
		</td>
		<td class='blck'>
			<ul>
				<li><a href='/netcat/modules/netshop/interface/keep-count.php'>УЧЁТ</a></li>
			</ul>
		</td>
	</tr>
	</table>

		
		
	</ul>
<?php 
	} else {
?>
	<br clear='both'>
	<?php echo $err; ?>
	<form method="post" name="frm1" id="frm1" action="/netcat/modules/netshop/interface/statistic.php">
	<table cellpadding="0" cellspacing="5" border="0" style="width:200px; margin:0 auto;">
	<tr><td>Пароль:</td><td><input type="password" name="passwd"></td></tr>
	<tr><td colspan="2" style="text-align:center;"><input type="submit" value="Войти"></td></tr>
	</table>
	</form>
<?php		
	}
} else {
	echo "<p>У вас нет прав для просмотра этой страницы</p><p><a href='/netcat/modules/netshop/interface/login.php'>Вход</a></p>";
}
?>
</body>
</html>
<?php

mysql_close($con);
?>
