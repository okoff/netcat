<?php

define("NETCAT_MODULE_NETSHOP", "Netshop 2.0");
define("NETCAT_MODULE_NETSHOP_TITLE", "Netshop");
define("NETCAT_MODULE_NETSHOP_DESCRIPTION", "Netshop.");

define("NETCAT_MODULE_NETSHOP_ERROR_NO_SETTINGS", "No settings");

define("NETCAT_MODULE_NETSHOP_SHOP", "Shop");
define("NETCAT_MODULE_NETSHOP_ITEM", "Goods");
define("NETCAT_MODULE_NETSHOP_DISCOUNT", "Discrount");
define("NETCAT_MODULE_NETSHOP_DISCOUNTS", "Discounts");
define("NETCAT_MODULE_NETSHOP_COST", "COST");
define("NETCAT_MODULE_NETSHOP_ITEM_COST", "SUBTOTALS");
define("NETCAT_MODULE_NETSHOP_QTY", "Quantity");
define("NETCAT_MODULE_NETSHOP_ITEM_PRICE", "Price");
define("NETCAT_MODULE_NETSHOP_SUM", "TOTALS");
define("NETCAT_MODULE_NETSHOP_ITEM_DELETE", "Remove");
define("NETCAT_MODULE_NETSHOP_SETTINGS", "Settings");

define("NETCAT_MODULE_NETSHOP_APPLIED_DISCOUNTS", "Discounts applied to this item:");

define("NETCAT_MODULE_NETSHOP_PRICE_WITH_DISCOUNT", "Item price with discount");
define("NETCAT_MODULE_NETSHOP_PRICE_WITHOUT_DISCOUNT", "Item price without discount");


define("NETCAT_MODULE_NETSHOP_CURRENCIES", "Currencies");

define("NETCAT_MODULE_NETSHOP_DELIVERY", "Delivery");
define("NETCAT_MODULE_NETSHOP_PAYMENT", "Payment");

define("NETCAT_MODULE_NETSHOP_REFRESH", "Refresh cart");
define("NETCAT_MODULE_NETSHOP_PRICE_TYPE", "Price type");
define("NETCAT_MODULE_NETSHOP_ITEM_FORMS", "item, items, items");

define("NETCAT_MODULE_NETSHOP_FILL_REQUIRED", "Please fill all fields marked with asterik (*)");


define("NETCAT_MODULE_NETSHOP_NEXT", "Next");
define("NETCAT_MODULE_NETSHOP_BACK", "Previous");
define("NETCAT_MODULE_NETSHOP_MORE", "details");
define("NETCAT_MODULE_NETSHOP_INSTALL", "Install");

define("NETCAT_MODULE_NETSHOP_EXPORT_COMMERCEML", "Export to 1C");

define("NETCAT_MODULE_NETSHOP_IMPORT_COMMERCEML", "CommerceML data import");
define("NETCAT_MODULE_NETSHOP_IMPORT_COMMERCEML_NOT_WELL_FORMED", "Error loading XML file");
define("NETCAT_MODULE_NETSHOP_IMPORT_COMMERCEML_SCHEME_VER", "Scheme version");
define("NETCAT_MODULE_NETSHOP_IMPORT_COMMERCEML_SCHEME_VER_0", "auto");
define("NETCAT_MODULE_NETSHOP_IMPORT_COMMERCEML_SCHEME_VER_1", "1C version 7.7");
define("NETCAT_MODULE_NETSHOP_IMPORT_COMMERCEML_SCHEME_VER_2", "1C version 8.1");
define("NETCAT_MODULE_NETSHOP_IMPORT_SUBMIT", "  Import  ");
define("NETCAT_MODULE_NETSHOP_IMPORT_SOURCE_NAME", "Source");
define("NETCAT_MODULE_NETSHOP_IMPORT_SOURCE_NEW", "New source (enter source name)");
define("NETCAT_MODULE_NETSHOP_IMPORT_SOURCE_WRONG", "Wrong data source");
define("NETCAT_MODULE_NETSHOP_IMPORT_FILE", "File");
define("NETCAT_MODULE_NETSHOP_IMPORT_ACTION_NONEXISTANT", "What to do with items not in source");
define("NETCAT_MODULE_NETSHOP_IMPORT_ACTION_NONEXISTANT_DISABLE", "disable");
define("NETCAT_MODULE_NETSHOP_IMPORT_ACTION_NONEXISTANT_DELETE", "delete");
define("NETCAT_MODULE_NETSHOP_IMPORT_ACTION_NONEXISTANT_IGNORE", "leave as is");
define("NETCAT_MODULE_NETSHOP_IMPORT_AUTO_ADD_SECTIONS", "add sections without a prompt");
define("NETCAT_MODULE_NETSHOP_IMPORT_AUTO_ADD_GOODS", "add goods without a prompt");

define("NETCAT_MODULE_NETSHOP_IMPORT_MAP_SECTION", "Source sections to site mapping");
define("NETCAT_MODULE_NETSHOP_IMPORT_MAP_PRICE", "Price types to content template fields mapping");
define("NETCAT_MODULE_NETSHOP_IMPORT_CREATE_SECTION", "Create new section");
define("NETCAT_MODULE_NETSHOP_IMPORT_CREATE_SECTION_PARENT", "Parent section");
define("NETCAT_MODULE_NETSHOP_IMPORT_TEMPLATE", "Content template");

define("NETCAT_MODULE_NETSHOP_IMPORT_SOURCE_TITLE", "Import data source");
define("NETCAT_MODULE_NETSHOP_IMPORT_FILE_UPLOAD_TITLE", "Upload importing file");
define("NETCAT_MODULE_NETSHOP_IMPORT_FILE_FTP_PATH", "File name in ".$SUB_FOLDER.$HTTP_ROOT_PATH."tmp/ directory");

define("NETCAT_MODULE_NETSHOP_IMPORT_XML_FILE", "File parsing");
define("NETCAT_MODULE_NETSHOP_IMPORT_CATALOGUE_STRUCTURE", "Catalogue structure import");
define("NETCAT_MODULE_NETSHOP_IMPORT_OFFERS", "Import offers data");
define("NETCAT_MODULE_NETSHOP_IMPORT_COMMODITIES_IN_CATALOGUE", "Commodities import");
define("NETCAT_MODULE_NETSHOP_IMPORT_FIELDS_AND_TAGS_COMPLIANCE", "Fields and tags compliance:");

define("NETCAT_MODULE_NETSHOP_IMPORT_IGNORE_SECTION", "Skip section");

define("NETCAT_MODULE_NETSHOP_IMPORT_DONE", "Import source processed.");

define("NETCAT_MODULE_NETSHOP_IMPORT_CACHE_CLEARED_PARTIAL", "Temporary files partly removed!");

define("NETCAT_MODULE_NETSHOP_PHP4_DOMXML_REQUIRED", "Cannot import XML data, because DOMXML library is not installed. Please contact your hosting provider to install this library.");

define("NETCAT_MODULE_NETSHOP_IMPORT_1C_LINK", "To export this source to site from &#039;1C&#039; in batch mode:
<ol>
<li>In 1C, open <b>Service - Data exchange in CommerceML format - Unloading of commercial proposal packet
</b></li>
<li>Check item <b>Send to the site</b> and press ellipsis (<b>...</b>)
<li>In dialog window press <b>New line</b> and enter site name.
<br>Put the following string to the <b>Address</b> field:
<br><b style='background:#DFDFDF'>%s</b>
<br>Leave fields <b>Username</b> and <b>Password</b> blank.
</ol>
<b>NB:</b> all new 1C sections won't be added to site until you upload XML file
using this interface. Please read module documentation for details.");

define("NETCAT_MODULE_NETSHOP_IMPORT_1C8_LINK", "To export this source to site from &#039;1C8&#039; in batch mode:
<ol>
<li>Into the 1С8 open menu <b>Сервис</b> - <b>Data exchange with WEB-site</b> - <b>Data exchange settings with WEB-site</b>;</li>
<li>Check item <b>Create new exchange settings with WEB-site</b> and press <b>Далее</b>;</li>
<li>In dialog window:
<br>Put the following string to the <b>site address</b> field:
<br><b style='background:#DFDFDF'>%s</b>
<br>Fields <b>User</b> and <b>Password</b> set as (SECRET_NAME и SECRET_KEY).</li>
</ol>
<b>Note:</b> Subdivisions recreated in 1С8 will not be added on site untill you download the file manually using this interface. For detailed information see module documentation.");

define("NETCAT_MODULE_NETSHOP_DISCOUNT_EDIT", "Discount edit");
define("NETCAT_MODULE_NETSHOP_DISCOUNT_MANUAL", "Discount amount was set manually by the manager");
define("NETCAT_MODULE_NETSHOP_APPLIES_TO_GOODS", "to goods");
define("NETCAT_MODULE_NETSHOP_APPLIES_TO_CART", "to the cart in whole");

define("NETCAT_MODULE_NETSHOP_DISCOUNT_SELECT_FIELD", "select a field...");

define("NETCAT_MODULE_NETSHOP_CUSTOMER", "Customer");
define("NETCAT_MODULE_NETSHOP_ORDER_EDIT", "Order №%s of %%Y-%%m-%%d.");
define("NETCAT_MODULE_NETSHOP_SHOW_ORDER_STATUS", "Show only orders with the status");
define("NETCAT_MODULE_NETSHOP_ORDER_NEW", "new");

define("NETCAT_MODULE_NETSHOP_EQUALS", "equals");
define("NETCAT_MODULE_NETSHOP_MULTIPLY", "multiply");
define("NETCAT_MODULE_NETSHOP_ADD", "add");
define("NETCAT_MODULE_NETSHOP_SUBSTRACT", "substract");

define("NETCAT_MODULE_NETSHOP_OR", "or");


define("NETCAT_MODULE_NETSHOP_ITEM_MINIMAL_PRICE_REACHED", "Minimal price was reached when applying discounts to this item (%s)");
define("NETCAT_MODULE_NETSHOP_CART_MINIMAL_PRICE_REACHED", "Minimal price was reached when applying discounts to the cart (%s)");

define("NETCAT_MODULE_NETSHOP_SHOP_SETTINGS", "Netshop settings");
define("NETCAT_MODULE_NETSHOP_DEPARTMENT_SETTINGS", "Netshop section settings");
define("NETCAT_MODULE_NETSHOP_CURRENCY_SETTINGS", "Currency rates");

// Эти настройки по умолчанию (применяются, если не указаны соотв. настройки валют)
define("NETCAT_MODULE_NETSHOP_CURRENCY_FORMAT", "# %s"); // # - знак валюты
define("NETCAT_MODULE_NETSHOP_CURRENCY_DECIMALS", 2); // количество знаков после запятой
define("NETCAT_MODULE_NETSHOP_CURRENCY_DEC_POINT", "."); // разделитель целой и дробной части числа
define("NETCAT_MODULE_NETSHOP_CURRENCY_THOUSAND_SEP", ","); // разделитель групп разрядов (оставьте пустым!)
// скрипт получения курсов валют:
define("NETCAT_MODULE_NETSHOP_CURRENCY_VAR_NOT_SET", "%s is not set");
define("NETCAT_MODULE_NETSHOP_CURRENCY_NOTHING_TO_FETCH", "All currency rates where set manually");
define("NETCAT_MODULE_NETSHOP_CURRENCY_FETCH_NOTFOUND", "Error while trying to get currency rate sources");
define("NETCAT_MODULE_NETSHOP_CURRENCY_FETCH_PARSING_ERROR", "Error while trying to parse currency rate sources");
define("NETCAT_MODULE_NETSHOP_CURRENCY_FETCH_OK", "Currency rates fetched: %s");

define("NETCAT_MODULE_NETSHOP_ERROR_CART_EMPTY", "Cannot make an order because the cart is empty");

define("NETCAT_MODULE_NETSHOP_EMAIL_TO_MANAGER_HEADER", "Order from %s");


define("NETCAT_MODULE_NETSHOP_PAYMENT_NO_SETTINGS", "No settings for %s");
define("NETCAT_MODULE_NETSHOP_PAYMENT_NO_CURRENCY", "The shop currency isn't specified");
// №, название магазина
define("NETCAT_MODULE_NETSHOP_PAYMENT_DESCRIPTION", "Order No. %s payment (%s)");
define("NETCAT_MODULE_NETSHOP_PAYMENT_SUBMIT", "Make a payment");

// название платежной системы, сумма, дата, номер транзакции, id покупателя
define("NETCAT_MODULE_NETSHOP_PAYMENT_LOG", "Payed via %s: %s %s (transcation ID: %s, user ID: %s)");
define("NETCAT_MODULE_NETSHOP_PAYED_ON", "Payed on %Y-%m-%d");
define("NETCAT_MODULE_NETSHOP_PAYMENT_DOCUMENT", "document No. ");


define("NETCAT_MODULE_NETSHOP_CART_EMPTY", "Your cart is empty");
define("NETCAT_MODULE_NETSHOP_CART_CONTENTS", "<a href='%s'>In the cart</a>: %s, <b>%s</b>");
define("NETCAT_MODULE_NETSHOP_CART_CHECKOUT", "Checkout");

define("NETCAT_MODULE_NETSHOP_NO_RIGTHS", "You have no rights to access this information");

define("NETCAT_MODULE_NETSHOP_SETUP", "Module setup");
define("NETCAT_MODULE_NETSHOP_SETUP_ON_SITE", "Which site you want to install module to?");
define("NETCAT_MODULE_NETSHOP_SETUP_EVERYWHERE", "Module is installed on all sites in the system.");
define("NETCAT_MODULE_NETSHOP_SETUP_SHOP_SETTINGS_REDIRECT", "After you click &quot;OK&quot; the system will redirect you to the settings of the Netshop on the selected site. Please fill all required fields and press the &quot;Add&quot; button, otherwise the module won't work on that site.");

define("NETCAT_MODULE_NETSHOP_PREV_ORDERS_SUM", "Previous orders sum");
define("NETCAT_MODULE_NETSHOP_NOT_REGISTERED_USER", "Unregistered user");

define("NETCAT_MODULE_NETSHOP_NETSHOP", "Netshop");
define("NETCAT_MODULE_NETSHOP_GOODS_CATALOGUE", "Goods catalogue");
define("NETCAT_MODULE_NETSHOP_CART", "Cart");
define("NETCAT_MODULE_NETSHOP_MAKE_ORDER", 'Make an order');
define("NETCAT_MODULE_NETSHOP_EURO", "Euro");
define("NETCAT_MODULE_NETSHOP_EUROCENT", "eurocent, eurocents, eurocents");
define("NETCAT_MODULE_NETSHOP_USD", "dollar, dollars, dollars");
define("NETCAT_MODULE_NETSHOP_CENT", "cent, cents, cents");
define("NETCAT_MODULE_NETSHOP_RUR", "rouble, roubles, roubles");
define("NETCAT_MODULE_NETSHOP_COPECK", "copeck, copecks, copecks");
define("NETCAT_MODULE_NETSHOP_CB_RATES", 'Official currency rates');
define("NETCAT_MODULE_NETSHOP_PRICE_GROUPS", 'Price types for user groups');
define("NETCAT_MODULE_NETSHOP_DISCOUNTS", "Discounts");
define("NETCAT_MODULE_NETSHOP_DELIVERY_METHODS", 'Delivery methods');
define("NETCAT_MODULE_NETSHOP_BY_COURIER", "By courier");
define("NETCAT_MODULE_NETSHOP_PAYMENT_METHODS", 'Payment methods');
define("NETCAT_MODULE_NETSHOP_CREDIT_CARD", "Credit card");
define("NETCAT_MODULE_NETSHOP_CREDIT_CARD_DESCRIPTION", "VISA, MasterCard, EuroCard, JCB, DCL (via ASSIST.RU payment system)");
define("NETCAT_MODULE_NETSHOP_YANDEX_MONEY", "Yandex.Money");
define("NETCAT_MODULE_NETSHOP_WEBMONEY", "Webmoney");
define("NETCAT_MODULE_NETSHOP_CASHLESS", "Bank transfer");
define("NETCAT_MODULE_NETSHOP_SBERBANK", "Via Sberbank");
define("NETCAT_MODULE_NETSHOP_CASH", "In cash");
define("NETCAT_MODULE_NETSHOP_EMAIL_TEMPLATES", 'Mail templates');
define("NETCAT_MODULE_NETSHOP_ORDER_EMAIL_HEADER", "Your order in %SHOP_SHOPNAME%");

define("NETCAT_MODULE_NETSHOP_UNITS", "Units");
define("NETCAT_MODULE_NETSHOP_PCS", "pcs");
define("NETCAT_MODULE_NETSHOP_ORDER_STATUS", "Order status");
define("NETCAT_MODULE_NETSHOP_ACCEPTED", "accepted");
define("NETCAT_MODULE_NETSHOP_REJECTED", "declined");
define("NETCAT_MODULE_NETSHOP_PAYED", "payed");
define("NETCAT_MODULE_NETSHOP_DONE", "finished");

define("NETCAT_MODULE_NETSHOP_FULL_NAME", "Full Name");


define("NETCAT_MODULE_NETSHOP_ORDER_EMAIL_BODY", "Hello %CUSTOMER_CONTACTNAME%,

Thank you for placing your order.

%CART_CONTENTS%
%CART_DISCOUNTS%
%CART_DELIVERY%%CART_PAYMENT%

TOTALS: %CART_COUNT% to the sum of %CART_SUM%

Our managers will contact you shortly to specify some order details.


Yours sincerely,                    Phone: %SHOP_PHONE%
%SHOP_SHOPNAME%");


define("NETCAT_MODULE_NETSHOP_NO_PREV_ORDERS_STATUS_ID", "PREV_ORDERS_SUM_STATUS option is not set in the NetShop module settings. For details, please refer to module documentation.");



$GLOBALS["NETSHOP_MONTHS_GENETIVE"] = array('', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');

define("NETCAT_MODULE_NETSHOP_1C_ID", "Ид");
define("NETCAT_MODULE_NETSHOP_1C_CLASSIFICATOR_ID", "ИдКлассификатора");
define("NETCAT_MODULE_NETSHOP_1C_NAME", "Наименование");
define("NETCAT_MODULE_NETSHOP_1C_PRICE", "Цена");
define("NETCAT_MODULE_NETSHOP_1C_PRICES", "Цены");
define("NETCAT_MODULE_NETSHOP_1C_PRICE_TYPE", "ТипЦены");
define("NETCAT_MODULE_NETSHOP_1C_PRICES_TYPE", "ТипыЦен");
define("NETCAT_MODULE_NETSHOP_1C_PRICE_TYPE_ID", "ИдТипаЦены");
define("NETCAT_MODULE_NETSHOP_1C_PRICE_UNIT", "ЦенаЗаЕдиницу");
define("NETCAT_MODULE_NETSHOP_1C_CURRENCY", "Валюта");
define("NETCAT_MODULE_NETSHOP_1C_CURRENCY_DEFAULT", "руб");
define("NETCAT_MODULE_NETSHOP_1C_GROUP", "Группа");
define("NETCAT_MODULE_NETSHOP_1C_GROUPS", "Группы");
define("NETCAT_MODULE_NETSHOP_1C_PRODUCT_CHARS", "ХарактеристкиТовара");
define("NETCAT_MODULE_NETSHOP_1C_PRODUCT_CHAR", "ХарактеристкаТовара");
define("NETCAT_MODULE_NETSHOP_1C_VALUE", "Значение");
define("NETCAT_MODULE_NETSHOP_1C_REC_VALUES", "ЗначенияРеквизитов");
define("NETCAT_MODULE_NETSHOP_1C_REC_VALUE", "ЗначениеРеквизита");
define("NETCAT_MODULE_NETSHOP_1C_TAX", "СтавкаНалога");
define("NETCAT_MODULE_NETSHOP_1C_TAXES", "СтавкиНалогов");
define("NETCAT_MODULE_NETSHOP_1C_RATE", "Ставка");
define("NETCAT_MODULE_NETSHOP_1C_BASE_UNIT", "БазоваяЕдиница");
define("NETCAT_MODULE_NETSHOP_1C_IMG", "Картинка");
define("NETCAT_MODULE_NETSHOP_1C_QTY", "Количество");

define("NETCAT_MODULE_NETSHOP_RESPONSE_STAT_MESSAGE", "Order status in the system");
define("NETCAT_MODULE_NETSHOP_RESPONSE_COMMENT", "User comment");
define("NETCAT_MODULE_NETSHOP_ORDERS_NUMBER", "Order #");
define("NETCAT_MODULE_NETSHOP_TRANSACTION_NUMBER", "Transaction number in the system");
define("NETCAT_MODULE_NETSHOP_TELEPHONE_NUMBER", "Enter the number of your QIWI e-wallet");
define("NETCAT_MODULE_NETSHOP_NO_PAYMENT_SYSTEM", "Payment system is not found");

define("NETCAT_MODULE_NETSHOP_ERROR_ASSIST", "Enter identificator in ASSIST");
define("NETCAT_MODULE_NETSHOP_ERROR_PAYPAL_MAIL", "Fill &#034;Paypal Log-in Email&#034; field and choose the shop currency");
define("NETCAT_MODULE_NETSHOP_ERROR_PAYPAL_RATES", "You should get the currency rates");
define("NETCAT_MODULE_NETSHOP_ERROR_QIWI", "Enter the shop number and QIWI password");
define("NETCAT_MODULE_NETSHOP_ERROR_MAIL", "Enter the shop number, the shop key, and cryptographic hash of the key for Money@mail.ru");
define("NETCAT_MODULE_NETSHOP_ERROR_ROBOKASSA", "Enter Log-in, password #1 and password #2 for Robokassa");
define("NETCAT_MODULE_NETSHOP_ERROR_WEBMONEY", "Enter seller's e-wallet number and secret key for Webmoney");
define("NETCAT_MODULE_NETSHOP_ERROR_YANDEX", "Choose settings in Yandex.Money");
define("NETCAT_MODULE_NETSHOP_ERROR_PAYMASTER", "Enter shop identificator and secret word for Paymaster");

if (!function_exists("netshop_language_count")) {

    // ---------------------------------------------------------------------------
    // возвращает $word в форме, соответствующей числу $num
    // array (рубль, рубля, рублей) || string "рубль, рубля, рублей"
    function netshop_language_count($num, $words) {
        if (!is_array($words)) $words = nc_preg_split("/,\s*/", $words);

        // x5 to x0, 11 to 14
        if (preg_match("/(?:[5-90]|1[1-4])$/", $num))
                return ($words[2] ? $words[2] : $words[0]);

        // 1, x1 (except eleven)
        if (preg_match("/1$/", $num)) return $words[0];

        // 2..4, x2..x4 (except for 12..14)
        return ($words[1] ? $words[1] : $words[0]);
    }

    //---------------------------------------
    // Achtung! работает только для "мужских" валют (рубль, доллар, евро)
    // для других - нет (напр., гривна)
    function netshop_language_in_words($sum, $currency_string="", $copecks_string="") {
        // Проверка ввода
        $sum = str_replace(' ', '', $sum);
        $sum = trim($sum);
        if ((!(preg_match('/^[0-9]*'.'[,\.]'.'[0-9]*$/', $sum) || preg_match('/^[0-9]+$/', $sum))) || ($sum == '.') || ($sum == ',')) :
            return "Not a money: $sum";
        endif;
        // Меняем запятую, если она есть, на точку
        $sum = str_replace(',', '.', $sum);
        if ($sum >= 1000000000):
            return "Maximum sum is one billion plus-minus a copeck";
        endif;
        // Обработка копеек
        $rub = floor($sum);
        if ($copecks_string) {
            $k = 100 * round($sum - $rub, 2);
            $kop = sprintf("%02d", $k);
            $kop.=" ".netshop_language_count($k, $copecks_string);
        }

        $namerub = netshop_language_count($sum, $currency_string);


        if ($rub == "0"):
            return "Zero ".$namerub." $kop";
        endif;
        //----------Сотни
        $sotni = substr($rub, -3);
        $nums = _numberw($sotni);
        if ($rub < 1000):
            return /* ucfirst */(trim("$nums $namerub $kop"));
        endif;
        //----------Тысячи
        if ($rub < 1000000):
            $ticha = substr(str_pad($rub, 6, "0", STR_PAD_LEFT), 0, 3);
        else:
            $ticha = substr($rub, strlen($rub) - 6, 3);
        endif;
        $one = substr($ticha, -1);
        $two = substr($ticha, -2);

        $name1000 = " thousand";

        $numt = _numberw($ticha);

        if ($ticha != '000'):
            $numt.=$name1000;
        endif;

        if ($rub < 1000000):
            return /* ucfirst */(trim("$numt $nums $namerub $kop"));
        endif;

        //----------Миллионы

        $million = substr(str_pad($rub, 9, "0", STR_PAD_LEFT), 0, 3);
        $one = substr($million, -1);
        $two = substr($million, -2);
        $name1000000 = " million";

        $numm = _numberw($million);
        $numm.=$name1000000;

        return /* ucfirst */(trim("$numm $numt $nums $namerub $kop"));
    }

    //Функция перевода цифр в сумму прописью. Подаете цифру (разделитель рублей и копеек - точка или запятая, максимальная сумма - миллиард рублей), на выходе у функции - сумма прописью.
    //Юрий Денисенко, denik@aport.ru  http://poligraf.h1.ru

    function _numberw($c) {
        $c = str_pad($c, 3, "0", STR_PAD_LEFT);
        //---------сотни
        switch ($c[0]) {
            case 0:
                $d[0] = "";
                break;
            case 1:
                $d[0] = "one hundred";
                break;
            case 2:
                $d[0] = "two hundred";
                break;
            case 3:
                $d[0] = "three hundred";
                break;
            case 4:
                $d[0] = "four hundred";
                break;
            case 5:
                $d[0] = "five hundred";
                break;
            case 6:
                $d[0] = "six hundred";
                break;
            case 7:
                $d[0] = "seven hundred";
                break;
            case 8:
                $d[0] = "eight hundred";
                break;
            case 9:
                $d[0] = "nine hundred";
                break;
        }
        //--------------десятки
        switch ($c[1]) {
            case 0:
                $d[1] = "";
                break;
            case 1: {
                    $e = $c[1].$c[2];
                    switch ($e) {
                        case 10:
                            $d[1] = "ten";
                            break;
                        case 11:
                            $d[1] = "eleven";
                            break;
                        case 12:
                            $d[1] = "twelve";
                            break;
                        case 13:
                            $d[1] = "thirteen";
                            break;
                        case 14:
                            $d[1] = "fourteen";
                            break;
                        case 15:
                            $d[1] = "fifteen";
                            break;
                        case 16:
                            $d[1] = "sixteen";
                            break;
                        case 17:
                            $d[1] = "seventeen";
                            break;
                        case 18:
                            $d[1] = "eighteen";
                            break;
                        case 19:
                            $d[1] = "nineteen";
                            break;
                    };
                }
                break;
            case 2:
                $d[1] = "twenty";
                break;
            case 3:
                $d[1] = "thirty";
                break;
            case 4:
                $d[1] = "fourty";
                break;
            case 5:
                $d[1] = "fifty";
                break;
            case 6:
                $d[1] = "sixty";
                break;
            case 7:
                $d[1] = "seventy";
                break;
            case 8:
                $d[1] = "eighty";
                break;
            case 9:
                $d[1] = "ninety";
                break;
        }
        //--------------единицы
        $d[2] = "";
        if ($c[1] != 1):
            switch ($c[2]) {
                case 0:
                    $d[2] = "";
                    break;
                case 1:
                    $d[2] = "one";
                    break;
                case 2:
                    $d[2] = "two";
                    break;
                case 3:
                    $d[2] = "three";
                    break;
                case 4:
                    $d[2] = "four";
                    break;
                case 5:
                    $d[2] = "five";
                    break;
                case 6:
                    $d[2] = "six";
                    break;
                case 7:
                    $d[2] = "seven";
                    break;
                case 8:
                    $d[2] = "eight";
                    break;
                case 9:
                    $d[2] = "nine";
                    break;
            }
        endif;

        return $d[0].' '.$d[1].($d[1] && $d[2] ? '-' : ($d[0] && !$d[1] ? 'and ' : ' ')).$d[2];
    }

}
?>