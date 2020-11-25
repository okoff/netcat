<?php
// 14.03.2016 Elen
// работа с CDEK отправлениями


include_once ("../../../../vars.inc.php");
include_once ("utils.php");
include_once ("utils-mysqli.php");
include_once ("utils-template.php");
session_start();

$incoming = parse_incoming();

isLogged();

$con=db_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD,$MYSQL_DB_NAME);

// ------------------------------------------------------------------------------------------------
echo printHeader("СДЭК");
echo printTopMenu($con);
?>


	<h1>Работа со СДЭК отпрaвлениями</h1>
	<ul>
		<li style='padding:0 0 7px 0;'><a href='/netcat/modules/netshop/interface/order-cdek.php'>Список заказов. Формирование файла для отправки</a></li>
		<li style='padding:0 0 7px 0;'><a href='/netcat/modules/netshop/interface/order-cdek.php?action=view'>Списки для отправки</a></li>
		<li style='padding:0 0 7px 0;'><a href='/netcat/modules/netshop/interface/cdek-work.php?action=tracksendings'>Отправленные заказы (за последние 10 дней)</a></li>
		<li style='padding:0 0 7px 0;'><a href='/netcat/modules/netshop/interface/order-cdekpaid.php'>Загрузить отчет об оплате</a></li>
	</ul>
	</ul>
<p>Отправленные заказы</p>
<?
$dte=date("Y-m-d");//."T".date("H:i:s");
$lastmonth = mktime(0, 0, 0, date("m"), date("d")-10,   date("Y"));
//echo $lastmonth;
?>
<form name="frm1" id="frm1" action="/netcat/modules/netshop/interface/cdek-work.php" method="post">
	<input type="hidden" name="action" id="action" value="tracksendings">
	<table cellpadding="2" cellspacing="0" border="1">
		<tr><td colspan="5">
		<table cellpadding="0" cellspacing="5" border="0"><tr>
				<td>с</td>
				<td><input name="min" value="<?=date("d.m.Y", $lastmonth)?>" class="datepickerTimeField"></td>
				<td>по</td>
				<td><input name="max" value="<?=date("d.m.Y")?>" class="datepickerTimeField"></td>
		</tr></table>
		</td></tr>
		<tr><td colspan="5"><input type="submit" value="Показать"></td></tr>
	</table>

<p>Проверить статус заказа: </p>

	<input type="text" name="orderid" id="orderid">
	<input type="submit" value="Проверить">
</form> 
<link type="text/css" href="/css/latest.css" rel="Stylesheet" />
<script type="text/javascript" src="/js/ui.datepicker.js"></script>
<script>
$(".datepickerTimeField").datepicker({
		changeMonth: true,
		changeYear: true,
		dateFormat: 'dd.mm.yy',
		firstDay: 1, changeFirstDay: false,
		navigationAsDateFormat: false,
		duration: 0// отключаем эффект появления
});
</script>
</body>
</html>
<?php
db_close($con);
?>
