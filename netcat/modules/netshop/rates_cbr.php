<?php

/* $Id: rates_cbr.php 6208 2012-02-10 10:21:43Z denis $ */

/**
 * Получение курсов валют
 * НА ВХОДЕ ДОЛЖНА БЫТЬ ПЕРЕМЕННАЯ catalogue!
 * -------------------------------------------------------------------------
 */
define("MAIN_LANG", "ru");

// из prevNetshop 2 -------------------
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require_once ($ROOT_FOLDER."connect_io.php");

//require_once("kxlib.php");

if (is_file($MODULE_FOLDER."netshop/".MAIN_LANG.".lang.php")) {
    require_once($MODULE_FOLDER."netshop/".MAIN_LANG.".lang.php");
    $modules_lang = "Russian";
} else {
    require_once($MODULE_FOLDER."netshop/en.lang.php");
    $modules_lang = "English";
}

//LoadModuleEnv();
$MODULE_VARS = $nc_core->modules->load_env($modules_lang);
// -----------------------------------------

extract($MODULE_VARS["netshop"]);

// Проверить, все ли необходимые настройки у нас есть
$check_settings = array("SHOP_TABLE", "CURRENCY_RATES_TABLE",
        "OFFICIAL_RATES_TABLE", /* "RATES_SOURCE", */
        "catalogue");

if ($_GET["key"] != md5($SECRET_KEY)) die("Wrong key!");

foreach ($check_settings as $var) {
    if (!$$var) {
        die(sprintf(NETCAT_MODULE_NETSHOP_CURRENCY_VAR_NOT_SET, $var));
    }
}

// -----------------------------
// настройки магазина
$shop_id = GetSubdivisionByType($SHOP_TABLE, "Subdivision_ID");
if (!$shop_id) die("NO SHOP ID");

$rates_template_id = value1("SELECT c.Sub_Class_ID
                             FROM Sub_Class as c, Subdivision as s
                             WHERE c.Class_ID = $OFFICIAL_RATES_TABLE
                               AND c.Subdivision_ID = $shop_id
                               AND c.Subdivision_ID = s.Subdivision_ID
                             LIMIT 1");

// -----------------------------
// 1. Определить список валют, которые мы хотим получить
//    (те валюты, которые есть в справочнике, но курсы их не указаны)
$res = q("SELECT c.ShopCurrency_ID, c.ShopCurrency_Name
          FROM Classificator_ShopCurrency as c
               LEFT JOIN
               Message$CURRENCY_RATES_TABLE as m
               ON (c.ShopCurrency_ID=m.Currency)
          WHERE m.Rate IS NULL");

if (!mysql_num_rows($res)) { // собссно делать-то ничего и не надо
    die(NETCAT_MODULE_NETSHOP_CURRENCY_NOTHING_TO_FETCH);
}

// 2. Сделать запрос к источнику
//---
$RATES_SOURCE = "http://www.cbr.ru/scripts/XML_daily.asp?date_req=%d%%2F%m%%2F%Y";
//---
$RATES_SOURCE = strftime($RATES_SOURCE); // подставить дату, если необходимо
$src = @join('', @file($RATES_SOURCE)) or die(NETCAT_MODULE_NETSHOP_CURRENCY_FETCH_NOTFOUND);
// -------------------------- SOURCE-SPECIFIC ---------------------------
// 3. Извлечь курсы валют. Просигналить, если не получилось.
// дата
if (preg_match("#<ValCurs\s+Date=\"(\d+)[./-](\d+)[./-](\d+)\"#", $src, $regs)) {
    $src_date = "$regs[3]-$regs[2]-$regs[1]";
} else {
    die(NETCAT_MODULE_NETSHOP_CURRENCY_FETCH_PARSING_ERROR);
}

// курсы валют
$rates = array();

while (list($currency_id, $currency_symbol) = mysql_fetch_row($res)) {
    if (preg_match("#
	<CharCode>($currency_symbol)</CharCode>\s*
	<Nominal>(\d+)</Nominal>\s*
	<Name>.+?</Name>\s*
	<Value>([\d,]+)</Value>
    #xs", $src, $regs)) {
        $regs[3] = (double) str_replace(",", ".", $regs[3]); // , to .
        $rates[$currency_id] = $regs[3] / $regs[2];
    }
}

if (!$rates) {
    die(NETCAT_MODULE_NETSHOP_CURRENCY_FETCH_PARSING_ERROR);
}

// ----------------------------------------------------------------------
// 4. Записать курсы
//print_r($rates);

foreach ($rates as $id => $rate) {

    $where = "WHERE Date='$src_date'
              AND Currency=$id
              AND Sub_Class_ID=$rates_template_id";

    if (value1("SELECT COUNT(Message_ID) FROM Message$OFFICIAL_RATES_TABLE $where")) {
        q("UPDATE Message$OFFICIAL_RATES_TABLE SET Rate=$rate $where");
    } else {
        q("INSERT Message$OFFICIAL_RATES_TABLE
         SET Date='$src_date', Currency=$id, Rate=$rate,
             Subdivision_ID=$shop_id, Sub_Class_ID=$rates_template_id");
    }
}

// 5. Удалить старые курсы валют
if (int($RATES_DAYS_TO_KEEP)) {
    q("DELETE FROM Message$OFFICIAL_RATES_TABLE
      WHERE Date <= DATE_SUB(CURDATE(),INTERVAL $RATES_DAYS_TO_KEEP DAY)");
}

// Done.
printf(NETCAT_MODULE_NETSHOP_CURRENCY_FETCH_OK, join(", ", array_keys($rates)));
?>