<?php
// 2019/08/01 Elen 
// Add/change delivery address

include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once("CalculatePriceDeliveryCdek.php");
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
?>

<!DOCTYPE html>
<html>
	<head>
		<title>Расчёт стоимости доставки СДЭК</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link type="text/css" href="/js/jquery-ui-1.8.21.custom.css" rel="stylesheet" />
		<script src="/js/jquery-1.7.2.min.js" type="text/javascript"></script>
		<script src="/js/jquery-ui-1.8.21.custom.min.js" type="text/javascript"></script>
	<style type="text/css">
	.ui-autocomplete-loading {
	  background: #FFF right center no-repeat;
	}
	#city { width: 25em; }
	#log { height: 200px; width: 600px; overflow: auto; }
	</style>
	<script type="text/javascript">
	/**
	 * подтягиваем список городов ajax`ом, данные jsonp в зависмости от введённых символов
	 */
	$(function() {
	  $("#city").autocomplete({
	    source: function(request,response) {
	      $.ajax({
	        url: "https://api.cdek.ru/city/getListByTerm/jsonp.php?callback=?",
	        dataType: "jsonp",
	        data: {
	        	q: function () { return $("#city").val() },
	        	name_startsWith: function () { return $("#city").val() }
	        },
	        success: function(data) {
	          response($.map(data.geonames, function(item) {
	            return {
	              label: item.name,
	              value: item.name,
	              id: item.id
	            }
	          }));
	        }
	      });
	    },
	    minLength: 1,
	    select: function(event,ui) {
	    	console.log("Yep!");
	    	$('#receiverCityId').val(ui.item.id);
	    }
	  });
	  
	});
	</script>
	<script type="text/javascript" id="ISDEKscript" src="/calc/widget/widjet.js"></script>		
	</head>
	
	<body>


		
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	//подключаем файл с классом CalculatePriceDeliveryCdek


	try {

		//создаём экземпляр объекта CalculatePriceDeliveryCdek
		$calc = new CalculatePriceDeliveryCdek();
		
		//Авторизация. Для получения логина/пароля (в т.ч. тестового) обратитесь к разработчикам СДЭК -->
		//$calc->setAuth('authLoginString', 'passwordString');
		$calc->setAuth('966b523203ee0dd09485f1af91c40bf3', 'a082f619378a85387c0cedc22dd1fd6b');
		
		//устанавливаем город-отправитель
		$calc->setSenderCityId($_REQUEST['senderCityId']);
		//устанавливаем город-получатель
		$calc->setReceiverCityId($_REQUEST['receiverCityId']);
		//устанавливаем дату планируемой отправки
		$calc->setDateExecute($_REQUEST['dateExecute']);
		
		//задаём список тарифов с приоритетами
		$calc->addTariffPriority($_REQUEST['tariffList1']);
		$calc->addTariffPriority($_REQUEST['tariffList2']);
		
		//устанавливаем тариф по-умолчанию
		$calc->setTariffId('137');
			
		//устанавливаем режим доставки
		$calc->setModeDeliveryId($_REQUEST['modeId']);
		//добавляем места в отправление
		$calc->addGoodsItemBySize($_REQUEST['weight1'], $_REQUEST['length1'], $_REQUEST['width1'], $_REQUEST['height1']);
		//$calc->addGoodsItemByVolume($_REQUEST['weight2'], $_REQUEST['volume2']);
		
		if ($calc->calculate() === true) {
			$res = $calc->getResult();
			echo '<br><br><hr><br>';
			//var_dump($_REQUEST);
			echo 'Цена доставки: ' . $res['result']['price'] . 'руб.<br />';
			echo 'Срок доставки: ' . $res['result']['deliveryPeriodMin'] . '-' . 
									 $res['result']['deliveryPeriodMax'] . ' дн.<br />';
			echo 'Планируемая дата доставки: c ' . $res['result']['deliveryDateMin'] . ' по ' . $res['result']['deliveryDateMax'] . '.<br />';
			//echo 'id тарифа, по которому произведён расчёт: ' . $res['result']['tariffId'] . '.<br />';
			if(array_key_exists('cashOnDelivery', $res['result'])) {
				echo 'Ограничение оплаты наличными, от (руб): ' . $res['result']['cashOnDelivery'] . '.<br />';
			}
		} else {
			$err = $calc->getError();
			if( isset($err['error']) && !empty($err) ) {
				//var_dump($err);
				foreach($err['error'] as $e) {
					echo 'Код ошибки: ' . $e['code'] . '.<br />';
					echo 'Текст ошибки: ' . $e['text'] . '.<br />';
				}
			}
		}
		
		//раскомментируйте, чтобы просмотреть исходный ответ сервера
		// var_dump($calc->getResult());
		// var_dump($calc->getError());
		echo '<br>
		<form method="post" action="order_address_save.php">
			<input name="oid" id="oid" value="'.$incoming['oid'].'" hidden />
			<input name="modeId" id="modeId" value="'.$incoming['modeId'].'" hidden />	
			<input name="city" id="city" value="'.$incoming['city'].'" hidden />
			<input name="price" id="price" value="'.$res['result']['price'].'" hidden />
			<input name="receiverCityId" id="receiverCityId" value="'.$incoming['receiverCityId'].'" hidden />
			<input type="submit" value="Сохранить">
		</form>
		';

	} catch (Exception $e) {
		echo 'Ошибка: ' . $e->getMessage() . "<br />";
	}

}

?>
			
			
<script type="text/javascript">
    var widjet = new ISDEKWidjet({
        defaultCity: 'Москва',
        cityFrom: 'Москва',
        link: 'forpvz'
    });
	widjet.binders.add(choosePVZ, 'onChoose');

    function choosePVZ(wat) {
        alert('Доставка в город ' + wat.cityName +
            "\nдо ПВЗ с кодом: " + wat.id + ', цена '+wat.price +' руб.'
        );
		$('#cdek_pvzid').val(wat.id);
		$('#city').val(wat.cityName);
		$('#receiverCityId').val(wat.city);
		$('#modeId').val(4);
        console.log('Выбран ПВЗ ', wat);
		
    }
</script>
<br>
<form action="order_address_save.php" id="cdek" method="POST" />
	<h3>Выбор ПВЗ для заказа <?=$incoming['oid']?></h3>
		Город-отправитель: Москва<br />
		<label for="city"><b>Город-получатель *: </b></label>
		<div class="ui-widget" style="display: inline-block;">
			  <input id="city" name="city" />
			  <br />
		</div>
		<input name="oid" value="<?=$incoming['oid']?>" hidden /> <!-- # заказа -->
		<input name="senderCityId" value="44" hidden /> <!-- Город-отправитель, Москва -->
		<input name="receiverCityId" id="receiverCityId" value="" hidden /> <!-- Город-получатель -->
		<input name="receiverCityName" id="receiverCityName" value="" hidden /> <!-- Город-получатель -->
		
		<input name="tariffList1" value="10" hidden />
		<input name="tariffList2" value="137" hidden />
		<br><br>
		Режим доставки:
		<select name="modeId">
			<option value="3" selected>склад-дверь</option> <!-- режим доставки, склад-дверь -->
			<option value="4">склад-склад</option> <!-- режим доставки, склад-дверь -->
		</select>
		<input name="dateExecute" value="<?=date("Y-m-d")?>" hidden /> <!-- Дата доставки -->
			
		<input name="weight1" value="0.5" hidden /> <!-- Вес места, кг.  -->
		<input name="length1" value="10" hidden /> <!-- Длина места, см. -->
		<input name="width1" value="10" hidden /> <!-- Ширина места, см. -->
		<input name="height1" value="5" hidden /> <!-- Высота места, см. -->			
		
		<input name="weight2" value="0.3" hidden /> <!-- Вес места, кг.--> 
		<input name="volume2" value="0.1" hidden /> <!-- объём места, длина*ширина*высота. -->
		
	<div id="forpvz" style="height:600px;"></div>
	<input type="hidden" name="cdek_pvzid" id="cdek_pvzid" value="">
	<br>

	<input type="submit" value="Посчитать">
</form>
		
	</body>
	</html>