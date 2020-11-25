<?php


function isLogged() {
	//print_r($_SESSION);
	if ((isset($_SESSION['nc_token_rand'])) || ((isset($_SESSION['insideadmin'])) && ($_SESSION['insideadmin']==1))) {
		return true;
	} else {
		die("<p>У вас нет прав для просмотра этой страницы.</p><p><a href='/netcat/modules/netshop/interface/login.php'>Вход</a></p>");
	}
}

function printTopMenu($con) {
	$tmp="";
//	echo date("d",mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
	if (date("d",mktime(0, 0, 0, date("m"), date("d")+1, date("Y")))==1) {
		$sql="SELECT created FROM Stats_Goods ORDER BY id DESC LIMIT 1";
		$result=mysqli_query($con,$sql);
		if ($row = mysqli_fetch_array($result)) {
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
		<!--li style='padding:0 0 3px 0;'><a href='/netcat/modules/netshop/interface/order-post-history.php'>Загрузить файл трассировки</a></li-->
		<li style='padding:0 0 3px 0;'><a href='/netcat/modules/netshop/interface/pickpoint.php'>Работа с PickPoint</a></li>
		<li style='padding:0 0 3px 0;'><a href='/netcat/modules/netshop/interface/cdek.php'>Работа сo СДЭК</a></li>
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
	</ul>
	</td></tr></table>
	</div>";
	return $res;
}

function printHeader($title="") {
	$res="<!DOCTYPE html>
<html>
<head>
	<title>Администривание. {$title}</title>
	<meta content=\"text/html;charset=windows1251\" http-equiv=\"content-type\" />
	<style>
	body, td {
		font-size:10pt;
		font-family:Tahoma;
	}
	</style>
	<script language=\"Javascript\" type=\"text/javascript\" src=\"/js/jquery.js\"></script>
</head>
<body>";

	return $res;
}
function printFooter() {
	$res="</body>
	</html>";
	return $res;
}
?>
