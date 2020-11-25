
<?php

class OrderID {
	public $order_id;
	
	public function __construct($v) {
		$this->order_id=$v; //13590;
	}
}

class OrderList {
	public $orderlist=array();
	public function __construct($arr) {
		$this->orderlist=$arr;
	}
}

$token_v='9d8b8d3914e264ae2a90';
$url='http://lc.posthouse.ru/system/ws/index.php?wsdl';
//$url='http://lc.posthouse.ru/system/ws/';
//echo $url."<br>";
$client = new SoapClient($url,array('trace' => 1));
//var_dump($client->__getFunctions());
//var_dump($client->__getTypes());



$token = new SoapParam((string)$token_v, "Token");
$order_id = new SoapParam((int)154522, "OrderID");
$olist=array((string)"22019",(string)"22041");
$olistpd=array((string)"154523",(string)"154522");
$order_list_pd = new SoapParam($olistpd, "OrderNumList");
//var_dump($order_list);
try{ 
	//$response=$client->getUpdateOrderInfo($order_id,$token); //информация о заказе по номеру заказа в ПД
	//$response=$client->getOrderNumPDList($order_list,$token); // получить номера заказов в ПД по нашем номерам
	$response=$client->getOrderListStatusFull($order_id,$token); // получить список статусов почты РФ 
	//$response=$client->getOrderListStatus($order_id,$token); // получить список статусов почты РФ 
	//$response=$client->getOrderListFlag($order_list_pd,$token); // получить список статусов почты РФ 
	echo 'Request : <br/><xmp>'.$client->__getLastRequest().'</xmp><br/>';
	
	var_dump($response);
	foreach ($response as $r) {
		echo iconv(mb_detect_encoding($r,"auto"),"CP1251",$r)."<br>";
	}
} catch(SoapFault $fault){ 
	// <xmp> tag displays xml output in html 
	echo 'Request : <br/><xmp>'.$client->__getLastRequest().'</xmp><br/><br/> Error Message : <br/>';
	echo $fault->getMessage()."<br>";
	$err=$fault->getMessage();
	echo iconv("UTF-8","CP1251",$err)."<br>";
} 

//print_r($response);
/* WORK
$requestParams = array(
    'CityName' => 'Berlin',
    'CountryName' => 'Germany'
);

$client = new SoapClient('http://www.webservicex.net/globalweather.asmx?WSDL');
$response = $client->GetWeather($requestParams);

print_r($response); */

?>
