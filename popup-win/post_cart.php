<?php

error_reporting(E_ALL ^ E_NOTICE);

include_once ("../vars.inc.php");

$client_url = urldecode("http://".$HTTP_HOST.$REQUEST_URI);
$parsed_url = parse_url($client_url);

require_once ($ROOT_FOLDER.'connect_io.php');

$current_catalogue = $nc_core->catalogue->get_by_host_name($parsed_url['host']);
$catalogue = $current_catalogue["Catalogue_ID"];
if (!$catalogue) $catalogue = 1; // first site

$MODULE_VARS = $nc_core->modules->get_module_vars();
require_once($MODULE_FOLDER."netshop/function.inc.php");

if (is_file($MODULE_FOLDER."netshop/".MAIN_LANG.".lang.php")) {
    require_once($MODULE_FOLDER."netshop/".MAIN_LANG.".lang.php");
} else {
    require_once($MODULE_FOLDER."netshop/en.lang.php");
}

$shop = new Netshop();
if (!isset($shop) || !is_object($shop)) die("Не удалось инициализировать магазин<br>");

if ($_POST["cart"]) {
	if ($shop->CartPut($_POST["cart"], $_POST["cart_mode"], $_POST['nc_cart_params'])) {
	} else {
		die("Не добавилось :(<br>");
	}
	foreach ($_POST["cart"] as $key => $arr) {
		foreach ($arr as $id => $cnt) {
			$added = $id;
			break;
		}
		break;
	}
	$html = 
"<style>
#quickadd-table th, td {
  padding: 3px 3px 0px 0px;
}
</style>
	 <table id='quickadd-table'>
<tr>
<th>Наименование</th>
<th>Количество</th>
<th>Цена</th>
<th>Сумма</th>
<tr>";
	$qty = 0;
	$sum = 0;
	foreach ($shop->CartContents() as $row) {
		$qty += $row["Qty"];
		$sum += $row["ItemPrice"]*$row["Qty"];
		if ($added && $row["Message_ID"] == $added) {
			$highlight = " style='color:green;'";
		}
		$html .= 
		   "<tr>
		   <td".$highlight.">".$row["Name"]."</td>
		   <td>".$row["Qty"]." ".$row["Units"]."</td>
		   <td>".$row["ItemPrice"]."</td>
		   <td>".($row["ItemPrice"]*$row["Qty"])."</td>
		   </tr>";
		$highlight = "";
	}
	$html .= 
	   "<tr><td></td></tr><tr>
	   <td><b>Всего:</b></td>
	   <td><b>".$qty."</b></td>
	   <td><b>Итого:</b></td>
	   <td><b>".$shop->FormatCurrency($sum)."</b></td>
	   </tr>
</table>";

	echo "CartOk".$html;
	
} else {
	echo 'Произошла ошибка! Не удалось отправить сообщение.';
}
exit;
?>
