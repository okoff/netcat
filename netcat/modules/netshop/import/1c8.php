<?php

/**
 * Интерфейс для автоматического импорта данных из 1C8
 */
// make user's undivine
@ignore_user_abort(true);

// load system
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -5)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require_once ($ROOT_FOLDER."connect_io.php");

$MODULE_VARS = $nc_core->modules->get_module_vars();

if (is_file($MODULE_FOLDER."netshop/".$nc_core->lang->detect_lang(1).".lang.php")) {
    require_once($MODULE_FOLDER."netshop/".$nc_core->lang->detect_lang(1).".lang.php");
} else {
    require_once($MODULE_FOLDER."netshop/en.lang.php");
}

// log status
$log_1c = true;
// log file
$log_file = $GLOBALS['TMP_FOLDER']."1c8.log";
// current date
$date = date("Y-m-d H:i:s");
// zip [yes/no]
//$zip = ($MODULE_VARS['netshop']['1C_ZIP'] ? 'yes' : 'no');
$zip = 'yes';
// cookie's name
$cookie = "nc-import-cookie";
// import path
$import_path = $GLOBALS['TMP_FOLDER'];
// import filesname
#$import_file = uniqid("import");
// file limit
$file_limit = 1024 * 50; //1024 * 1024 * 1;
// shop secret key
$secret_name = $MODULE_VARS['netshop']['SECRET_NAME'];
$secret_key = $MODULE_VARS['netshop']['SECRET_KEY'];

/**
 * Server authorization
 */
if (
        !isset($_SERVER['PHP_AUTH_USER']) ||
        !(
        $_SERVER['PHP_AUTH_USER'] == $secret_name &&
        $_SERVER['PHP_AUTH_PW'] == $secret_key
        )
) {
    // sen auth headers
    header('WWW-Authenticate: Basic realm="Authorization required"');
    header('HTTP/1.0 401 Unauthorized');
    // log message
    if ($log_1c) file_put_contents($log_file, "wrong key".PHP_EOL, FILE_APPEND);
    // print message
    echo "WRONG KEY";
    // halt
    exit;
}

/**
 * STEP 1: checkauth
 */
if ($_GET['mode'] == 'checkauth') {
    // delimiter
    $prefix = "=======================================================";
    // log message
    if ($log_1c)
            file_put_contents($log_file, $prefix.PHP_EOL.$date.' - ['.$_GET['type'].'] checkauth'.PHP_EOL, FILE_APPEND);
    // status message
    echo 'success'.PHP_EOL;
    echo $cookie.PHP_EOL;
    echo uniqid();
    // halt
    exit;
}

/**
 * STEP 2: init
 */
if ($_GET['mode'] == 'init') {
    // log info
    if ($log_1c)
            file_put_contents($log_file, $date.' - ['.$_GET['type'].'] init transfer'.PHP_EOL, FILE_APPEND);
    // status message
    echo "zip=".$zip.PHP_EOL;
    echo "file_limit=".$file_limit;
}


/**
 * STEP 3: save file (import)
 */
if ($_GET['mode'] == 'file' && $_GET['type'] == 'catalog') {
    // imported file name
    $_file = $_GET['type'].'-'.$_COOKIE[$cookie].($zip == 'yes' ? '.zip' : '-'.$_GET['filename']);
    // log info
    if ($log_1c)
            file_put_contents($log_file, $date.' - ['.$_GET['type'].'] save file "'.$_file.'"'.PHP_EOL, FILE_APPEND);
    // save file or part of file
    $size = file_put_contents($import_path.$_file, $HTTP_RAW_POST_DATA, FILE_APPEND);
    // file size info
    file_put_contents($log_file, $date.' - ['.$_GET['type'].'] '.$size.' saved'.PHP_EOL, FILE_APPEND);
    // status message
    echo "success";
}

// import orders file (can export)
if ($_GET['mode'] == 'file' && $_GET['type'] == 'sale') {
    // status message
    echo "failure".PHP_EOL;
    echo "not supported";
}

/**
 * STEP 4: import file(s)
 */
if ($_GET['mode'] == 'import' && $_GET['type'] == 'catalog') {

    @set_time_limit(0);

    include_once($INCLUDE_FOLDER."index.php");
    require_once($MODULE_FOLDER."netshop/import/nc_netshop_cml2parser.class.php");

    global $_UTFConverter;
    if (!$_UTFConverter) {
        // set variable
        $_UTFConverter = false;
        // allow_call_time_pass_reference need in php.ini for utf8 class, check before construct!
        if (!( extension_loaded("mbstring") || extension_loaded("iconv") )) {
            include_once($INCLUDE_FOLDER."lib/utf8/utf8.class.php");
            // CP1251 - constant from utf8.class.php file
            $_UTFConverter = new utf8(CP1251);
        }
    }

    switch ($_GET['filename']) {
        case 'import.xml':
            //if ($zip) {
            // zipped file
            $zip_file = $_GET['type'].'-'.$_COOKIE[$cookie].'.zip';

            // init unzip
            $zip = new ZipArchive;
            // open zip
            $res = $zip->open($import_path.$zip_file);
            // extract zip
            if ($res === TRUE) {
                $zip->extractTo($import_path);
                $zip->close();
                // log message
                file_put_contents($log_file, $date.' - ['.$_GET['type'].'] unzip OK'.PHP_EOL, FILE_APPEND);
                // remove file
                unlink($import_path.$zip_file);
            } else {
                // log message
                file_put_contents($log_file, $date.' - ['.$_GET['type'].'] unzip FAIL'.PHP_EOL, FILE_APPEND);
                // halt
                exit;
            }
            //}
            //else {
            // log info
            //	file_put_contents($log_file, $date.' - ['.$_GET['type'].'] process "'.$_GET['filename'].'"'.PHP_EOL, FILE_APPEND);
            //}
            // cml2 class
            require_once($MODULE_FOLDER."netshop/import/cml2.class.php");
            $cml2 = new cml2();

            // get catalog properties
            $catalog_properties = $cml2->get_catalog_properties($import_path.$_GET['filename']);

            // source data
            $external_id = $catalog_properties['ИдКлассификатора']." ".$catalog_properties['Ид'];

            // log info
            file_put_contents($log_file, $date.' - ['.$_GET['type'].'] classifier "'.$catalog_properties['ИдКлассификатора'].'"'.PHP_EOL, FILE_APPEND);
            file_put_contents($log_file, $date.' - ['.$_GET['type'].'] catalog "'.$catalog_properties['Ид'].'"'.PHP_EOL, FILE_APPEND);

            // shop data
            $source_data = $db->get_row("SELECT *
                                FROM `Netshop_ImportSources`
                                WHERE `external_id` = '".$db->escape($external_id)."'", ARRAY_A);

            if (!empty($source_data)) {
                // log info
                file_put_contents($log_file, $date.' - ['.$_GET['type'].'] source "'.$source_data['name'].'"'.PHP_EOL, FILE_APPEND);
            } else {
                // log info
                file_put_contents($log_file, $date.' - ['.$_GET['type'].'] SOURCE NOT FOUND'.PHP_EOL, FILE_APPEND);
                // status message
                echo "failure".PHP_EOL;
                echo "SOURCE NOT FOUND";
                // halt
                exit;
            }

            // construct parser
            $nc_netshop_cml2parser = new nc_netshop_cml2parser($db, $_UTFConverter, $source_data['source_id'], $source_data['catalogue_id'], $_GET['filename'], true);

            // init parser if not cached
            if (!$nc_netshop_cml2parser->cache_data_exist()) {
                // get classifier data
                $nc_netshop_cml2parser->get_classifier_data();

                // get catalogue data & check source
                if ($nc_netshop_cml2parser->get_catalogue_data()) {
                    // check actual catalog && update source
                    if (!$nc_netshop_cml2parser->update_sources()) exit;
                }
            }

            // directory structure
            $nc_netshop_cml2parser->import_classifier_data();
            // import commodities
            $nc_netshop_cml2parser->import_catalogue_data();

            // found unmapped elements
            if ($nc_netshop_cml2parser->not_mapped_sections) {
                // log info
                file_put_contents($log_file, $date.' - ['.$_GET['type'].'] UNMAPPED groups'.PHP_EOL, FILE_APPEND);
                // status message
                echo "failure".PHP_EOL;
                echo "UNMAPPED groups";
                // halt
                exit;
            }
            if ($nc_netshop_cml2parser->not_mapped_fields) {
                // log info
                file_put_contents($log_file, $date.' - ['.$_GET['type'].'] UNMAPPED catalog fields'.PHP_EOL, FILE_APPEND);
                // status message
                echo "failure".PHP_EOL;
                echo "UNMAPPED catalog fields";
                // halt
                exit;
            }

            if ($nc_netshop_cml2parser->everything_clear) {
                // count cached data
                $cache_count = $nc_netshop_cml2parser->cache_data_count();
                // erase cached data
                $cache_clear = $nc_netshop_cml2parser->cache_data_destroy();
            }

            break;
        case 'offers.xml':
            // log info
            file_put_contents($log_file, $date.' - ['.$_GET['type'].'] process "'.$_GET['filename'].'"'.PHP_EOL, FILE_APPEND);

            // cml2 class
            require_once($MODULE_FOLDER."netshop/import/cml2.class.php");
            $cml2 = new cml2();

            // get offers properties
            $offers_properties = $cml2->get_offers_properties($import_path.$_GET['filename']);

            // source data
            $external_id = $offers_properties['ИдКлассификатора']." ".$offers_properties['ИдКаталога'];

            // log info
            file_put_contents($log_file, $date.' - ['.$_GET['type'].'] classifier "'.$offers_properties['ИдКлассификатора'].'"'.PHP_EOL, FILE_APPEND);
            file_put_contents($log_file, $date.' - ['.$_GET['type'].'] catalog "'.$offers_properties['ИдКаталога'].'"'.PHP_EOL, FILE_APPEND);

            // shop data
            $source_data = $db->get_row("SELECT *
				FROM `Netshop_ImportSources`
				WHERE `external_id` = '".$db->escape($external_id)."'", ARRAY_A);

            if (!empty($source_data)) {
                // log info
                file_put_contents($log_file, $date.' - ['.$_GET['type'].'] source "'.$source_data['name'].'"'.PHP_EOL, FILE_APPEND);
            } else {
                // log info
                file_put_contents($log_file, $date.' - ['.$_GET['type'].'] SOURCE NOT FOUND'.PHP_EOL, FILE_APPEND);
                // status message
                echo "failure".PHP_EOL;
                echo "SOURCE NOT FOUND";
                // halt
                exit;
            }

            // construct parser
            $nc_netshop_cml2parser = new nc_netshop_cml2parser($db, $_UTFConverter, $source_data['source_id'], $source_data['catalogue_id'], $_GET['filename'], true);

            // init parser if not cached
            if (!$nc_netshop_cml2parser->cache_data_exist()) {
                // get catalogue data & check source
                #if ( $nc_netshop_cml2parser->get_catalogue_data() ) {
                #	// check actual catalog && update source
                #	if ( !$nc_netshop_cml2parser->update_sources() ) exit;
                #}
                // get offers data
                $nc_netshop_cml2parser->get_offers_data();
            }

            // import offers
            $nc_netshop_cml2parser->import_offers_data();

            // if not mapping elements - show dialog
            if (!$quite) {
                if ($nc_netshop_cml2parser->not_mapped_packets) {
                    // log info
                    file_put_contents($log_file, $date.' - ['.$_GET['type'].'] UNMAPPED offers fields'.PHP_EOL, FILE_APPEND);
                    // status message
                    echo "failure".PHP_EOL;
                    echo "UNMAPPED offers fields";
                    // halt
                    exit;
                }
            }

            if ($nc_netshop_cml2parser->everything_clear) {
                // count cached data
                $cache_count = $nc_netshop_cml2parser->cache_data_count();
                // erase cached data
                $cache_clear = $nc_netshop_cml2parser->cache_data_destroy();
            }

            // log info
            file_put_contents($log_file, $date.' - ['.$_GET['type'].'] '.$_GET['filename'].PHP_EOL, FILE_APPEND);
            break;
    }

    // status message
    echo "success";
}

/**
 * STEP X: sales
 */
if ($_GET['mode'] == 'query' && $_GET['type'] == 'sale') {

    // make user's undivine
    @ignore_user_abort(true);
    include_once($MODULE_FOLDER."/netshop/function.inc.php");
    $MODULE_VARS = $nc_core->modules->get_module_vars();

    @set_time_limit(0);

    // system superior object
    $nc_core = nc_Core::get_object();

    if (!function_exists('xmlspecialchars')):

        function xmlspecialchars($text) {
            return str_replace('&#039;', '&apos;', htmlspecialchars($text, ENT_QUOTES));
        }

    endif;

    $catalogue = $nc_core->catalogue->get_by_host_name($HTTP_HOST);
    $catalogue = $catalogue["Catalogue_ID"];
    if (!$catalogue) $catalogue = 1;
    $where_status = ($MODULE_VARS['netshop']['1C_EXPORT_ORDERS_STATUS'] ? "AND m.`Status` IN (".$MODULE_VARS['netshop']['PREV_ORDERS_SUM_STATUS_ID'].")" : "");
    //$where_status .= ($MODULE_VARS['netshop']['1C_EXPORT_ORDERS_WITH_LATTER'] ? "AND og.`Order_ID`>0"/*.$nc_core->get_settings('last_exported_order', 'netshop')*/ : "");
    $orders_arr = $db->get_col("SELECT DISTINCT og.`Order_ID`
                              FROM `Netshop_OrderGoods` as og, `Message".$MODULE_VARS['netshop']['ORDER_TABLE']."` as m
                              WHERE og.`Order_ID`=m.`Message_ID` ".$where_status." ORDER BY og.`Order_ID`");

    if (!($orders_arr)) {
        // status message
        echo "failure".PHP_EOL;
        echo "NO ORDERS";
        exit;
    }

    // set headers
    header("Content-type: text/xml; charset=windows-1251");

    ob_start();

    echo '<?xml version="1.0" encoding="windows-1251"?>'.PHP_EOL;
    echo '<КоммерческаяИнформация ВерсияСхемы="2.05" ДатаФормирования="'.date("Y-m-d").'">'.PHP_EOL;

    foreach ($orders_arr as $order_id) {

        $shop = new Netshop();
        $shop->LoadOrder($order_id);

        // работает только если один и тот же " каталог товаров" в 1С
        $ext_ids = $db->get_var("SELECT external_id
                             FROM Netshop_ImportSources
                             WHERE source_id='".$shop->CartContents[0]["ImportSourceID"]."'
                             AND catalogue_id='".$catalogue."'");

        list($ext_company_id, $ext_catalogue_id) = explode(" ", $ext_ids);

        $user_data = $nc_core->db->get_row("SELECT *
                                        FROM `User`
                                        WHERE `User_ID` = '".$shop->Order['User_ID']."'", ARRAY_A);

        $order_timestamp = timestamp($shop->Order["Created"]);
        $order_date = strftime("%Y-%m-%d", $order_timestamp);
        $order_time = strftime("%H:%M:%S", $order_timestamp);
        $currency = $shop->Currencies[$shop->Order["OrderCurrency"]];
        if ($currency == "RUR") $currency = "руб";

        echo '  <Документ>'.PHP_EOL;
        echo '    <Ид>'.$shop->Order['Message_ID'].'</Ид>'.PHP_EOL;
        echo '    <Номер>'.$shop->Order['Message_ID'].'</Номер>'.PHP_EOL;
        echo '    <Дата>'.$order_date.'</Дата>'.PHP_EOL;
        echo '    <ХозОперация>Заказ товара</ХозОперация>'.PHP_EOL;
        echo '    <Роль>Продавец</Роль>'.PHP_EOL;
        echo '    <Валюта>'.$currency.'</Валюта>'.PHP_EOL;
        echo '    <Курс>1</Курс>'.PHP_EOL;
        echo '    <Сумма>'.$shop->CartSum().'</Сумма>'.PHP_EOL;

        $contragent = xmlspecialchars($shop->Order['CompanyName'] ? $shop->Order['CompanyName'] : $shop->Order['ContactName']);

        echo '    <Контрагенты>'.PHP_EOL;
        echo '      <Контрагент>'.PHP_EOL;
        echo '        <Ид>'.$shop->Order['User_ID'].'</Ид>'.PHP_EOL;
        echo '        <Наименование>'.$contragent.'</Наименование>'.PHP_EOL;
        echo '        <Роль>Покупатель</Роль>'.PHP_EOL;
        if (isset($user_data['FullName']))
                echo '        <ПолноеНаименование>'.$user_data['FullName'].'</ПолноеНаименование>'.PHP_EOL;
        echo '        <АдресРегистрации>'.PHP_EOL;
        echo '          <Представление>'.xmlspecialchars($shop->Order['Address'] ? $shop->Order['Address'] : $user_data['FullName']).'</Представление>'.PHP_EOL;
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

        if ($shop->Order['Comments'])
                echo '    <Комментарий>'.xmlspecialchars($shop->Order['Comments']).'</Комментарий>'.PHP_EOL;

        $cart_discount_ratio = 1;
        // calculate percent of cart discount
        if ($shop->CartDiscountSum) {
            $cart_discount_ratio = 1 - ($shop->CartDiscountSum / $shop->CartFieldSum('ItemPrice'));
        }

        echo '    <Товары>'.PHP_EOL;

        foreach ($shop->CartContents as $item) {
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

            $vat = $item['VAT'] ? $item['VAT'] : $shop->VAT;
            if ($vat) {
                // $vat
                // ($item['ItemPrice']*$vat/100)
            }

            echo '      </Товар>'.PHP_EOL;
        }

        // включить стоимость доставки в счет
        if ($shop->Order['DeliveryCost']) {
            echo '      <Товар>'.PHP_EOL;
            echo '        <Ид>ORDER_DELIVERY</Ид>'.PHP_EOL;
            echo '        <Наименование>dlvr'.$shop->Order['DeliveryMethod'].'</Наименование>'.PHP_EOL;
            echo '        <БазоваяЕдиница Код="796" НаименованиеПолное="Штука" МеждународноеСокращение="PCE">шт</БазоваяЕдиница>'.PHP_EOL;
            echo '        <ЦенаЗаЕдиницу>'.$shop->Order['DeliveryCost'].'</ЦенаЗаЕдиницу>'.PHP_EOL;
            echo '        <Количество>1</Количество>'.PHP_EOL;
            echo '        <Сумма>'.$shop->Order['DeliveryCost'].'</Сумма>'.PHP_EOL;
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
        //$last_order = $shop->OrderID;
    }

    echo '</КоммерческаяИнформация>'.PHP_EOL;
    //$nc_core->set_settings('last_exported_order', $last_order, 'netshop');
    $buffer = ob_get_contents();
    ob_end_clean();
    echo $nc_core->utf8->utf2win($buffer);
}


/**
 * STEP X: sales - confirmation
 */
if ($_GET['mode'] == 'success' && $_GET['type'] == 'sale') {
    // status message
    echo "success";
}
?>
