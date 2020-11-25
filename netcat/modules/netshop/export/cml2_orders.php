<?php

// make user's undivine
@ignore_user_abort(true);

// load system
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -5)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require_once ($ROOT_FOLDER."connect_io.php");

$MODULE_VARS = $nc_core->modules->get_module_vars();

if (is_file($MODULE_FOLDER."netshop/".MAIN_LANG.".lang.php")) {
    require_once($MODULE_FOLDER."netshop/".MAIN_LANG.".lang.php");
} else {
    require_once($MODULE_FOLDER."netshop/en.lang.php");
}

@set_time_limit(0);

include_once($INCLUDE_FOLDER."index.php");

// system superior object
$nc_core = nc_Core::get_object();

if (!function_exists('xmlspecialchars')):

    function xmlspecialchars($text) {
        return str_replace('&#039;', '&apos;', htmlspecialchars($text, ENT_QUOTES));
    }

endif;

class Netshop_ExportCML2 extends Netshop {

    var $attachment;

    function Netshop_ExportCML2() {
        $this->Netshop();

        $this->attachment = true;
    }

    /**
     * Biztalk export
     */
    function ExportCML($order_id) {
        if (!int($order_id)) return false;

        $this->LoadOrder($order_id);
        $this->CartContents();
        $nc_core = nc_Core::get_object();
        // работает только если один и тот же " каталог товаров" в 1С
        list($ext_company_id, $ext_catalogue_id) = explode(" ",
                        value1("SELECT external_id
				FROM Netshop_ImportSources 
				WHERE source_id='".$this->CartContents[0]["ImportSourceID"]."'"));

        /* $user_data = $nc_core->db->get_row("SELECT *
          FROM `User`
          WHERE `User_ID` = '".$this->Order['User_ID']."'", ARRAY_A); */

        // set headers
        if ($this->attachment) {
            header("Content-Type: Aplication/xml-file");
            header("Content-Disposition: attachment; filename=".$this->OrderID."-order.xml");
        }

        echo '<?xml version="1.0" encoding="'.$nc_core->NC_CHARSET.'"?>'.PHP_EOL;
        echo '<КоммерческаяИнформация ВерсияСхемы="2.03" ДатаФормирования="'.date("Y-m-d").'T'.date("H:i:s").'">'.PHP_EOL;

        $order_timestamp = timestamp($this->Order["Created"]);
        $order_date = strftime("%Y-%m-%d", $order_timestamp);
        $order_time = strftime("%H:%M:%S", $order_timestamp);

        $currency = $this->Currencies[$this->Order["OrderCurrency"]];
        if ($currency == "RUR") $currency = "руб.";

        echo '  <Документ>'.PHP_EOL;
        echo '    <Ид>'.$this->Order['Message_ID'].'</Ид>'.PHP_EOL;
        echo '    <Номер>'.$this->Order['Message_ID'].'</Номер>'.PHP_EOL;
        echo '    <Дата>'.$order_date.'</Дата>'.PHP_EOL;
        echo '    <ХозОперация>Заказ товара</ХозОперация>'.PHP_EOL;
        echo '    <Роль>Продавец</Роль>'.PHP_EOL;
        echo '    <Валюта>'.$currency.'</Валюта>'.PHP_EOL;
        echo '    <Курс>1</Курс>'.PHP_EOL;
        echo '    <Сумма>'.$this->CartSum().'</Сумма>'.PHP_EOL;

        $contragent = xmlspecialchars($this->Order['CompanyName'] ? $this->Order['CompanyName'] : $this->Order['ContactName']);

        echo '    <Контрагенты>'.PHP_EOL;
        echo '      <Контрагент>'.PHP_EOL;
        echo '        <Ид>'.$this->Order['User_ID'].'</Ид>'.PHP_EOL;
        echo '        <Наименование>'.$contragent.'</Наименование>'.PHP_EOL;
        echo '        <Роль>Покупатель</Роль>'.PHP_EOL;
        if (isset($user_data['FullName']))
                echo '        <ПолноеНаименование>'.$user_data['FullName'].'</ПолноеНаименование>'.PHP_EOL;
        echo '        <АдресРегистрации>'.PHP_EOL;
        echo '          <Представление>'.xmlspecialchars($this->Order['Address']).'</Представление>'.PHP_EOL;
        /* echo '<АдресноеПоле>'.PHP_EOL;
          echo '<Тип>Почтовый индекс</Тип>'.PHP_EOL;
          echo '<Значение>------</Значение>'.PHP_EOL;
          echo '</АдресноеПоле>'.PHP_EOL;
          echo '<АдресноеПоле>'.PHP_EOL;
          echo '<Тип>Улица</Тип>'.PHP_EOL;
          echo '<Значение>---</Значение>'.PHP_EOL;
          echo '</АдресноеПоле>'.PHP_EOL; */
        echo '        </АдресРегистрации>'.PHP_EOL;
        //echo '<Контакты></Контакты>'.PHP_EOL;
        /* echo '<Представители>'.PHP_EOL;
          echo '<Представитель>'.PHP_EOL;
          echo '<Контрагент>'.PHP_EOL;
          echo '<Отношение>Контактное лицо</Отношение>'.PHP_EOL;
          echo '<Ид>-------------------------</Ид>'.PHP_EOL;
          echo '<Наименование>Петр Петров</Наименование>'.PHP_EOL;
          echo '</Контрагент>'.PHP_EOL;
          echo '</Представитель>'.PHP_EOL;
          echo '</Представители>'.PHP_EOL; */
        echo '      </Контрагент>'.PHP_EOL;
        echo '    </Контрагенты>'.PHP_EOL;
        echo '    <Время>'.$order_time.'</Время>'.PHP_EOL;

        if ($this->Order['Comments'])
                echo '    <Комментарий>'.xmlspecialchars($this->Order['Comments']).'</Комментарий>'.PHP_EOL;

        $cart_discount_ratio = 1;
        // calculate percent of cart discount
        if ($this->CartDiscountSum) {
            $cart_discount_ratio = 1 - ($this->CartDiscountSum / $this->CartFieldSum('ItemPrice'));
        }

        echo '    <Товары>'.PHP_EOL;

        foreach ($this->CartContents as $item) {
            $item_ext_id = nc_preg_replace("/^ID/", "", $item["ItemID"]);
            if (!$item_ext_id)
                    $item_ext_id = "g$item[Class_ID]_$item[Message_ID]";

            echo '      <Товар>'.PHP_EOL;
            echo '        <Ид>'.$item_ext_id.'</Ид>'.PHP_EOL;
            echo '        <ИдКаталога>'.$ext_catalogue_id.'</ИдКаталога>'.PHP_EOL;
            echo '        <Наименование>'.$item["Name"].'</Наименование>'.PHP_EOL;
            echo '        <БазоваяЕдиница Код="796" НаименованиеПолное="Штука" МеждународноеСокращение="PCE">шт</БазоваяЕдиница>'.PHP_EOL;
            echo '        <ЦенаЗаЕдиницу>'.($item["ItemPrice"] * $cart_discount_ratio).'</ЦенаЗаЕдиницу>'.PHP_EOL;
            echo '        <Количество>'.$item["Qty"].'</Количество>'.PHP_EOL;
            echo '        <Сумма>'.($item["ItemPrice"] * $item["Qty"] * $cart_discount_ratio).'</Сумма>'.PHP_EOL;
            echo '        <ЗначенияРеквизитов>'.PHP_EOL;
            echo '          <ЗначениеРеквизита>'.PHP_EOL;
            echo '            <Наименование>ВидНоменклатуры</Наименование>'.PHP_EOL;
            echo '            <Значение>Товар</Значение>'.PHP_EOL;
            echo '          </ЗначениеРеквизита>'.PHP_EOL;
            echo '          <ЗначениеРеквизита>'.PHP_EOL;
            echo '            <Наименование>ТипНоменклатуры</Наименование>'.PHP_EOL;
            echo '            <Значение>Товар</Значение>'.PHP_EOL;
            echo '          </ЗначениеРеквизита>'.PHP_EOL;
            echo '        </ЗначенияРеквизитов>'.PHP_EOL;

            $vat = $item['VAT'] ? $item['VAT'] : $this->VAT;
            if ($vat) {
                // $vat
                // ($item['ItemPrice']*$vat/100)
            }

            echo '      </Товар>'.PHP_EOL;
        }

        // включить стоимость доставки в счет
        if ($this->Order['DeliveryCost']) {
            echo '      <Товар>'.PHP_EOL;
            echo '        <Ид>ORDER_DELIVERY</Ид>'.PHP_EOL;
            echo '        <Наименование>dlvr'.$this->Order['DeliveryMethod'].'</Наименование>'.PHP_EOL;
            echo '        <БазоваяЕдиница Код="796" НаименованиеПолное="Штука" МеждународноеСокращение="PCE">шт</БазоваяЕдиница>'.PHP_EOL;
            echo '        <ЦенаЗаЕдиницу>'.$this->Order['DeliveryCost'].'</ЦенаЗаЕдиницу>'.PHP_EOL;
            echo '        <Количество>1</Количество>'.PHP_EOL;
            echo '        <Сумма>'.$this->Order['DeliveryCost'].'</Сумма>'.PHP_EOL;
            echo '        <ЗначенияРеквизитов>'.PHP_EOL;
            echo '          <ЗначениеРеквизита>'.PHP_EOL;
            echo '            <Наименование>ВидНоменклатуры</Наименование>'.PHP_EOL;
            echo '            <Значение>Услуга</Значение>'.PHP_EOL;
            echo '          </ЗначениеРеквизита>'.PHP_EOL;
            echo '          <ЗначениеРеквизита>'.PHP_EOL;
            echo '            <Наименование>ТипНоменклатуры</Наименование>'.PHP_EOL;
            echo '            <Значение>Услуга</Значение>'.PHP_EOL;
            echo '          </ЗначениеРеквизита>'.PHP_EOL;
            echo '        </ЗначенияРеквизитов>'.PHP_EOL;
            echo '      </Товар>'.PHP_EOL;
        }

        echo '    </Товары>'.PHP_EOL;
        echo '  </Документ>'.PHP_EOL;
        echo '</КоммерческаяИнформация>'.PHP_EOL;
    }

}

if (!($perm->isSupervisor() || $perm->isGuest())) {
    die("NO RIGHTS");
}

//LoadModuleEnv();
$MODULE_VARS = $nc_core->modules->get_module_vars();

$catalogue = $nc_core->catalogue->get_by_host_name($HTTP_HOST);
$catalogue = $catalogue["Catalogue_ID"];
if (!$catalogue) $catalogue = 1;

$shop = new NetShop_ExportCML2();
$shop->ExportCML($_GET["order_id"]);
?>
