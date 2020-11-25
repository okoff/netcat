<?php

/* $Id$ */
global $SUB_FOLDER, $HTTP_ROOT_PATH;

define("NETCAT_MODULE_NETSHOP_TITLE", "Интернет-магазин");
define("NETCAT_MODULE_NETSHOP_DESCRIPTION", "Интернет-магазин");

define("NETCAT_MODULE_NETSHOP_ERROR_NO_SETTINGS", "Отсутствует объект настроек в компоненте Интернет-магазин");

define("NETCAT_MODULE_NETSHOP_SHOP", "Магазин");
define("NETCAT_MODULE_NETSHOP_ITEM", "Товар");
define("NETCAT_MODULE_NETSHOP_DISCOUNT", "Cкидка");
define("NETCAT_MODULE_NETSHOP_DISCOUNTS", "Cкидки");
define("NETCAT_MODULE_NETSHOP_COST", "Стоимость");
define("NETCAT_MODULE_NETSHOP_ITEM_COST", "СТОИМОСТЬ ТОВАРОВ");
define("NETCAT_MODULE_NETSHOP_QTY", "Количество");
define("NETCAT_MODULE_NETSHOP_ITEM_PRICE", "Цена");
define("NETCAT_MODULE_NETSHOP_SUM", "ИТОГО");
define("NETCAT_MODULE_NETSHOP_ITEM_DELETE", "Удалить");

define("NETCAT_MODULE_NETSHOP_APPLIED_DISCOUNTS", "На этот товар действует скидка:");

define("NETCAT_MODULE_NETSHOP_PRICE_WITH_DISCOUNT", "Цена товара со скидкой");
define("NETCAT_MODULE_NETSHOP_PRICE_WITHOUT_DISCOUNT", "Цена товара без скидки");


define("NETCAT_MODULE_NETSHOP_CURRENCIES", "Валюты");

define("NETCAT_MODULE_NETSHOP_DELIVERY", "Доставка");
define("NETCAT_MODULE_NETSHOP_PAYMENT", "Оплата");

define("NETCAT_MODULE_NETSHOP_REFRESH", "Пересчитать корзину");
define("NETCAT_MODULE_NETSHOP_PRICE_TYPE", "Тип цен");
define("NETCAT_MODULE_NETSHOP_ITEM_FORMS", "товар, товара, товаров");

define("NETCAT_MODULE_NETSHOP_FILL_REQUIRED", "Пожалуйста, заполните все поля, отмеченные звездочкой (*)");


define("NETCAT_MODULE_NETSHOP_NEXT", "Далее");
define("NETCAT_MODULE_NETSHOP_BACK", "Назад");
define("NETCAT_MODULE_NETSHOP_MORE", "подробнее");
define("NETCAT_MODULE_NETSHOP_INSTALL", "Установить");

define("NETCAT_MODULE_NETSHOP_EXPORT_COMMERCEML", "Экспорт в 1C");

define("NETCAT_MODULE_NETSHOP_IMPORT_COMMERCEML", "Импорт данных в формате CommerceML");
define("NETCAT_MODULE_NETSHOP_IMPORT_COMMERCEML_NOT_WELL_FORMED", "Ошибка при загрузке XML-файла");
define("NETCAT_MODULE_NETSHOP_IMPORT_COMMERCEML_SCHEME_VER", "Версия схемы");
define("NETCAT_MODULE_NETSHOP_IMPORT_COMMERCEML_SCHEME_VER_0", "автоопределение");
define("NETCAT_MODULE_NETSHOP_IMPORT_COMMERCEML_SCHEME_VER_1", "1С версии 7.7");
define("NETCAT_MODULE_NETSHOP_IMPORT_COMMERCEML_SCHEME_VER_2", "1С версии 8.1");
define("NETCAT_MODULE_NETSHOP_IMPORT_SUBMIT", "  Импорт  ");
define("NETCAT_MODULE_NETSHOP_IMPORT_SOURCE_NAME", "Источник");
define("NETCAT_MODULE_NETSHOP_IMPORT_SOURCE_NEW", "Новый источник (введите название)");
define("NETCAT_MODULE_NETSHOP_IMPORT_SOURCE_WRONG", "Неверный источник данных");
define("NETCAT_MODULE_NETSHOP_IMPORT_FILE", "Файл");
define("NETCAT_MODULE_NETSHOP_IMPORT_ACTION_NONEXISTANT", "Что делать с позициями, которых нет в источнике");
define("NETCAT_MODULE_NETSHOP_IMPORT_ACTION_NONEXISTANT_DISABLE", "отключить");
define("NETCAT_MODULE_NETSHOP_IMPORT_ACTION_NONEXISTANT_DELETE", "удалить");
define("NETCAT_MODULE_NETSHOP_IMPORT_ACTION_NONEXISTANT_IGNORE", "оставить как есть");
define("NETCAT_MODULE_NETSHOP_IMPORT_AUTO_ADD_SECTIONS", "добавлять разделы без проверки");
define("NETCAT_MODULE_NETSHOP_IMPORT_AUTO_ADD_GOODS", "добавлять товары без проверки");

define("NETCAT_MODULE_NETSHOP_IMPORT_MAP_SECTION", "Укажите соответствие разделов источника разделам интернет-магазина");
define("NETCAT_MODULE_NETSHOP_IMPORT_MAP_PRICE", "Укажите соответствие типов цен полям шаблонов");
define("NETCAT_MODULE_NETSHOP_IMPORT_CREATE_SECTION", "Создать новый раздел");
define("NETCAT_MODULE_NETSHOP_IMPORT_CREATE_SECTION_PARENT", "Родительский раздел");
define("NETCAT_MODULE_NETSHOP_IMPORT_TEMPLATE", "Компонент");

define("NETCAT_MODULE_NETSHOP_IMPORT_SOURCE_TITLE", "Источник импортируемых данных");
define("NETCAT_MODULE_NETSHOP_IMPORT_FILE_UPLOAD_TITLE", "Загрузка файла с данными");
define("NETCAT_MODULE_NETSHOP_IMPORT_FILE_FTP_PATH", "Имя файла в директории ".$SUB_FOLDER.$HTTP_ROOT_PATH."tmp/");

define("NETCAT_MODULE_NETSHOP_IMPORT_XML_FILE", "Обработка импортируемого файла");
define("NETCAT_MODULE_NETSHOP_IMPORT_CATALOGUE_STRUCTURE", "Импорт структуры каталога");
define("NETCAT_MODULE_NETSHOP_IMPORT_OFFERS", "Импорт пакета предложений");
define("NETCAT_MODULE_NETSHOP_IMPORT_COMMODITIES_IN_CATALOGUE", "Импорт объектов в каталог");
define("NETCAT_MODULE_NETSHOP_IMPORT_FIELDS_AND_TAGS_COMPLIANCE", "Соответсвие XML-тегов полям в компоненте:");

define("NETCAT_MODULE_NETSHOP_IMPORT_IGNORE_SECTION", "Не вносить в каталог");

define("NETCAT_MODULE_NETSHOP_IMPORT_DONE", "Обработка источника завершена");

define("NETCAT_MODULE_NETSHOP_IMPORT_CACHE_CLEARED_PARTIAL", "Временные файлы удалены не полностью!");

define("NETCAT_MODULE_NETSHOP_PHP4_DOMXML_REQUIRED", "Импорт данных в формате XML невозможен, поскольку на сервере отсутствует библиотека DOMXML. Пожалуйста, обратитесь к Вашему хостинг-провайдеру для установки данной библиотеки.");

define("NETCAT_MODULE_NETSHOP_IMPORT_1C_LINK", "Для автоматической выгрузки данного источника данных на сайт:
<ol>
 <li>В 1С откройте меню <b>Сервис - Обмен данными в формате CommerceML - Выгрузка пакета коммерческих предложений</b></li>
 <li>Отметьте пункт <b>Отправить на сайт</b> и нажмите на многоточие (<b>...</b>)
 <li>В диалоговом окне нажмите <b>Новая строка</b>, введите наименование сайта.
     <br>В поле <b>Адрес</b> укажите:
     <br><b style='background:#DFDFDF'>%s</b>
     <br>Поля <b>Имя пользователя</b> и <b>Пароль доступа</b> оставьте пустыми.
</ol>
<b>Обратите внимание:</b> вновь созданные в 1С разделы не будут добавлены на
сайт, пока Вы снова не загрузите файл вручную через данный интерфейс.
Подробнее см. в документации к модулю.");

define("NETCAT_MODULE_NETSHOP_IMPORT_1C8_LINK", "Для автоматической выгрузки этого источника данных на сайт:
<ol>
 <li>В 1С8 откройте меню <b>Сервис</b> - <b>Обмен данными с WEB-сайтом</b> - <b>Настройка обмена данными с WEB-сайтом</b>;</li>
 <li>Отметьте пункт <b>Создать новую настройку обмена с WEB-сайтом</b> и нажмите <b>Далее</b>;</li>
 <li>В диалоговом окне укажите желаемые настройки обмена данными:
     <br>В поле <b>Адрес сайта</b> укажите:
     <br><b style='background:#DFDFDF'>%s</b>
     <br>Поля <b>Пользователь</b> и <b>Пароль</b> заполните в соответствии с настройками модуля Интернет-магазин (<b>SECRET_NAME</b> и <b>SECRET_KEY</b>).</li>
</ol>
<b>Обратите внимание:</b> вновь созданные в 1С8 разделы не будут добавлены на
сайт, пока Вы снова не загрузите файл вручную через данный интерфейс.
Подробнее см. в документации к модулю.");

define("NETCAT_MODULE_NETSHOP_DISCOUNT_EDIT", "Редактирование скидки");
define("NETCAT_MODULE_NETSHOP_DISCOUNT_MANUAL", "Размер скидки был указан оператором вручную");
define("NETCAT_MODULE_NETSHOP_APPLIES_TO_GOODS", "к отдельным товарам");
define("NETCAT_MODULE_NETSHOP_APPLIES_TO_CART", "ко всей корзине");

define("NETCAT_MODULE_NETSHOP_DISCOUNT_SELECT_FIELD", "выберите поле...");

define("NETCAT_MODULE_NETSHOP_CUSTOMER", "Заказчик");
define("NETCAT_MODULE_NETSHOP_ORDER_EDIT", "Заказ №%s от %%d.%%m.%%Y");
define("NETCAT_MODULE_NETSHOP_SHOW_ORDER_STATUS", "Показать только заказы со статусом");
define("NETCAT_MODULE_NETSHOP_ORDER_NEW", "новый");

define("NETCAT_MODULE_NETSHOP_EQUALS", "равно");
define("NETCAT_MODULE_NETSHOP_MULTIPLY", "умножить");
define("NETCAT_MODULE_NETSHOP_ADD", "прибавить");
define("NETCAT_MODULE_NETSHOP_SUBSTRACT", "вычесть");

define("NETCAT_MODULE_NETSHOP_OR", "или");


define("NETCAT_MODULE_NETSHOP_ITEM_MINIMAL_PRICE_REACHED", "При расчете скидки достигнут предел минимальной цены товара (%s)");
define("NETCAT_MODULE_NETSHOP_CART_MINIMAL_PRICE_REACHED", "При расчете скидки достигнут предел минимальной стоимости товаров в корзине (%s)");


define("NETCAT_MODULE_NETSHOP_SHOP_SETTINGS", "Настройки интернет-магазина");
define("NETCAT_MODULE_NETSHOP_DEPARTMENT_SETTINGS", "Настройки раздела интернет-магазина");
define("NETCAT_MODULE_NETSHOP_CURRENCY_SETTINGS", "Курсы валют");

// Эти настройки по умолчанию (применяются, если не указаны соотв. настройки валют)
define("NETCAT_MODULE_NETSHOP_CURRENCY_FORMAT", "%s #"); // # - знак валюты
define("NETCAT_MODULE_NETSHOP_CURRENCY_DECIMALS", 0); // количество знаков после запятой
define("NETCAT_MODULE_NETSHOP_CURRENCY_DEC_POINT", ","); // разделитель целой и дробной части числа
define("NETCAT_MODULE_NETSHOP_CURRENCY_THOUSAND_SEP", ""); // разделитель групп разрядов (оставьте пустым!)
// скрипт получения курсов валют:
define("NETCAT_MODULE_NETSHOP_CURRENCY_VAR_NOT_SET", "Не указано значение переменной %s");
define("NETCAT_MODULE_NETSHOP_CURRENCY_NOTHING_TO_FETCH", "Все курсы валют определены вручную");
define("NETCAT_MODULE_NETSHOP_CURRENCY_FETCH_NOTFOUND", "Не удалось получить источник курсов валют");
define("NETCAT_MODULE_NETSHOP_CURRENCY_FETCH_PARSING_ERROR", "Курсы валют не получены (ошибка при обработке источника)");
define("NETCAT_MODULE_NETSHOP_CURRENCY_FETCH_OK", "Получены курсы валют: %s");

define("NETCAT_MODULE_NETSHOP_ERROR_CART_EMPTY", "Невозможно оформить заказ, поскольку корзина пуста");

define("NETCAT_MODULE_NETSHOP_EMAIL_TO_MANAGER_HEADER", "Заказ с сайта %s");


define("NETCAT_MODULE_NETSHOP_PAYMENT_NO_SETTINGS", "Не указаны настройки платежной системы %s");
define("NETCAT_MODULE_NETSHOP_PAYMENT_NO_CURRENCY", "Не указана валюта магазина");
// №, название магазина
define("NETCAT_MODULE_NETSHOP_PAYMENT_DESCRIPTION", "Оплата заказа №%s (%s)");
define("NETCAT_MODULE_NETSHOP_PAYMENT_SUBMIT", "Оплатить");

// название платежной системы, сумма, дата, номер транзакции, id покупателя
define("NETCAT_MODULE_NETSHOP_PAYMENT_LOG", "Оплачено через %s: %s %s (ID транзакции: %s, ID покупателя: %s)");
define("NETCAT_MODULE_NETSHOP_PAYED_ON", "Оплачено %d.%m.%Y");
define("NETCAT_MODULE_NETSHOP_PAYMENT_DOCUMENT", "платежный документ № ");


define("NETCAT_MODULE_NETSHOP_CART_EMPTY", "Ваша корзина пуста");
define("NETCAT_MODULE_NETSHOP_CART_CONTENTS", "<a href='%s'>в Вашей корзине</a>: %s на сумму <b>%s</b>");
define("NETCAT_MODULE_NETSHOP_CART_CHECKOUT", "Оформить заказ");

define("NETCAT_MODULE_NETSHOP_NO_RIGTHS", "У Вас нет прав для доступа к данной информации");

define("NETCAT_MODULE_NETSHOP_SETUP", "Установка модуля на сайт");
define("NETCAT_MODULE_NETSHOP_SETUP_ON_SITE", "На какой сайт Вы хотите установить интернет-магазин?");
define("NETCAT_MODULE_NETSHOP_SETUP_EVERYWHERE", "Интернет-магазин установлен на всех сайтах в системе.");
define("NETCAT_MODULE_NETSHOP_SETUP_SHOP_SETTINGS_REDIRECT", "После нажатия кнопки &laquo;OK&raquo; вы будете переадресованы на страницу редактирования настроек интернет-магазина. Пожалуйста, заполните все необходимые поля и нажмите кнопку &laquo;Добавить&raquo;, иначе модуль не будет работать на указанном сайте.");

define("NETCAT_MODULE_NETSHOP_PREV_ORDERS_SUM", "Сумма пред. покупок");
define("NETCAT_MODULE_NETSHOP_NOT_REGISTERED_USER", "Незарегистрированный пользователь");

define("NETCAT_MODULE_NETSHOP_NETSHOP", "Интернет-магазин");
define("NETCAT_MODULE_NETSHOP_GOODS_CATALOGUE", "Каталог товаров");
define("NETCAT_MODULE_NETSHOP_CART", "Корзина");
define("NETCAT_MODULE_NETSHOP_MAKE_ORDER", 'Оформление заказа');
define("NETCAT_MODULE_NETSHOP_EURO", "евро");
define("NETCAT_MODULE_NETSHOP_EUROCENT", "евроцент, евроцента, евроцентов");
define("NETCAT_MODULE_NETSHOP_USD", "доллар, доллара, долларов");
define("NETCAT_MODULE_NETSHOP_CENT", "цент, цента, центов");
define("NETCAT_MODULE_NETSHOP_RUR", "рубль, рубля, рублей");
define("NETCAT_MODULE_NETSHOP_COPECK", "копейка, копейки, копеек");
define("NETCAT_MODULE_NETSHOP_CB_RATES", 'Курсы валют ЦБ');
define("NETCAT_MODULE_NETSHOP_PRICE_GROUPS", 'Цены для разных групп пользователей');
define("NETCAT_MODULE_NETSHOP_DELIVERY_METHODS", 'Способы доставки');
define("NETCAT_MODULE_NETSHOP_BY_COURIER", "Курьером");
define("NETCAT_MODULE_NETSHOP_PAYMENT_METHODS", 'Способы оплаты');
define("NETCAT_MODULE_NETSHOP_CREDIT_CARD", "Пластиковая карта");
define("NETCAT_MODULE_NETSHOP_CREDIT_CARD_DESCRIPTION", "VISA, MasterCard, EuroCard, JCB, DCL (через систему ASSIST)");
define("NETCAT_MODULE_NETSHOP_YANDEX_MONEY", "Яндекс.Деньги");
define("NETCAT_MODULE_NETSHOP_WEBMONEY", "Webmoney");
define("NETCAT_MODULE_NETSHOP_CASHLESS", "Безналичный расчет");
define("NETCAT_MODULE_NETSHOP_SBERBANK", "Через Сбербанк");
define("NETCAT_MODULE_NETSHOP_CASH", "Наличными");
define("NETCAT_MODULE_NETSHOP_EMAIL_TEMPLATES", 'Шаблоны писем');
define("NETCAT_MODULE_NETSHOP_ORDER_EMAIL_HEADER", "Ваш заказ в %SHOP_SHOPNAME%");

define("NETCAT_MODULE_NETSHOP_UNITS", "Единицы измерения");
define("NETCAT_MODULE_NETSHOP_PCS", "шт");
define("NETCAT_MODULE_NETSHOP_ORDER_STATUS", "Статусы заказов");
define("NETCAT_MODULE_NETSHOP_ACCEPTED", "принят");
define("NETCAT_MODULE_NETSHOP_REJECTED", "отклонен");
define("NETCAT_MODULE_NETSHOP_PAYED", "оплачен");
define("NETCAT_MODULE_NETSHOP_DONE", "завершен");

define("NETCAT_MODULE_NETSHOP_FULL_NAME", "ФИО");
define("NETCAT_MODULE_NETSHOP_SETTINGS", "Настройки");



define("NETCAT_MODULE_NETSHOP_ORDER_EMAIL_BODY", "Уважаемый %CUSTOMER_CONTACTNAME%!

Ваш заказ принят к обработке.

%CART_CONTENTS%
%CART_DISCOUNTS%
%CART_DELIVERY%%CART_PAYMENT%

ИТОГО: %CART_COUNT% на сумму %CART_SUM%

Для того, чтобы уточнить Ваш заказ, с Вами в самое ближайшее время свяжутся
наши менеджеры.


С уважением,                     Тел.: %SHOP_PHONE%
%SHOP_SHOPNAME%");


define("NETCAT_MODULE_NETSHOP_NO_PREV_ORDERS_STATUS_ID", "В настройках модуля &quot;Интернет-магазин&quot; не установлен параметр PREV_ORDERS_SUM_STATUS. Подробнее см. в документации по модулю.");



$GLOBALS["NETSHOP_MONTHS_GENETIVE"] = array('', 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря');

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

define("NETCAT_MODULE_NETSHOP_RESPONSE_STAT_MESSAGE", "Статус заказа в системе");
define("NETCAT_MODULE_NETSHOP_RESPONSE_COMMENT", "пользовательский комментарий");
define("NETCAT_MODULE_NETSHOP_ORDERS_NUMBER", "Заказ №");
define("NETCAT_MODULE_NETSHOP_TRANSACTION_NUMBER", "номер транзакции в системе");
define("NETCAT_MODULE_NETSHOP_TELEPHONE_NUMBER", "Введите номер Вашего кошелька в системе QIWI");
define("NETCAT_MODULE_NETSHOP_NO_PAYMENT_SYSTEM", "Платежная система не найдена");
if (!function_exists("netshop_language_count")) {

    // ---------------------------------------------------------------------------
    // возвращает $word в форме, соответствующей числу $num
    // array (рубль, рубля, рублей) || string "рубль, рубля, рублей"
    function netshop_language_count($num, $words) {
        if (!is_array($words)) $words = nc_preg_split("/,\s*/", $words);

        // x5 to x0, 11 to 14
        if (nc_preg_match("/(?:[5-90]|1[1-4])$/", $num))
                return ($words[2] ? $words[2] : $words[0]);

        // 1, x1 (except eleven)
        if (nc_preg_match("/1$/", $num)) return $words[0];

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
            return "Это не деньги: $sum";
        endif;
        // Меняем запятую, если она есть, на точку
        $sum = str_replace(',', '.', $sum);
        if ($sum >= 1000000000):
            return "Максимальная сумма &#151 один миллиард минус одна копейка";
        endif;
        // Обработка копеек
        $rub = floor($sum);
        if ($copecks_string) {
            $k = 100 * round($sum - $rub, 2);
            $kop = sprintf("%02d", $k);
            $kop.=" ".netshop_language_count($k, $copecks_string);
        }
        // Выясняем написание слова 'рубль'
        /*
          $one = substr($rub, -1);
          $two = substr($rub, -2);
          if ($two>9&&$two<21):
          $namerub=$currency_names[$currency]["рублей"];

          elseif ($one==1):
          $namerub=$currency_names[$currency]["рубль"];

          elseif ($one>1&&$one<5):
          $namerub=$currency_names[$currency]["рубля"];

          else:
          $namerub=$currency_names[$currency]["рублей"];

          endif;
         */
        $namerub = netshop_language_count($sum, $currency_string);


        if ($rub == "0"):
            return "Ноль ".$namerub." $kop";
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
        if ($two > 9 && $two < 21):

            $name1000 = " тысяч";
        elseif ($one == 1):

            $name1000 = " тысяча";
        elseif ($one > 1 && $one < 5):

            $name1000 = " тысячи";
        else:

            $name1000 = " тысяч";
        endif;
        $numt = _numberw($ticha);
        if ($one == 1 && $two != 11):
            $numt = str_replace('один', 'одна', $numt);
        endif;
        if ($one == 2):
            $numt = str_replace('два', 'две', $numt);
            $numt = str_replace('двед', 'двад', $numt);
        endif;
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
        if ($two > 9 && $two < 21):

            $name1000000 = " миллионов";
        elseif ($one == 1):

            $name1000000 = " миллион";
        elseif ($one > 1 && $one < 5):

            $name1000000 = " миллиона";
        else:

            $name1000000 = " миллионов";
        endif;
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
                $d[0] = "сто";
                break;
            case 2:
                $d[0] = "двести";
                break;
            case 3:
                $d[0] = "триста";
                break;
            case 4:
                $d[0] = "четыреста";
                break;
            case 5:
                $d[0] = "пятьсот";
                break;
            case 6:
                $d[0] = "шестьсот";
                break;
            case 7:
                $d[0] = "семьсот";
                break;
            case 8:
                $d[0] = "восемьсот";
                break;
            case 9:
                $d[0] = "девятьсот";
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
                            $d[1] = "десять";
                            break;
                        case 11:
                            $d[1] = "одиннадцать";
                            break;
                        case 12:
                            $d[1] = "двенадцать";
                            break;
                        case 13:
                            $d[1] = "тринадцать";
                            break;
                        case 14:
                            $d[1] = "четырнадцать";
                            break;
                        case 15:
                            $d[1] = "пятнадцать";
                            break;
                        case 16:
                            $d[1] = "шестнадцать";
                            break;
                        case 17:
                            $d[1] = "семнадцать";
                            break;
                        case 18:
                            $d[1] = "восемнадцать";
                            break;
                        case 19:
                            $d[1] = "девятнадцать";
                            break;
                    };
                }
                break;
            case 2:
                $d[1] = "двадцать";
                break;
            case 3:
                $d[1] = "тридцать";
                break;
            case 4:
                $d[1] = "сорок";
                break;
            case 5:
                $d[1] = "пятьдесят";
                break;
            case 6:
                $d[1] = "шестьдесят";
                break;
            case 7:
                $d[1] = "семьдесят";
                break;
            case 8:
                $d[1] = "восемьдесят";
                break;
            case 9:
                $d[1] = "девяносто";
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
                    $d[2] = "один";
                    break;
                case 2:
                    $d[2] = "два";
                    break;
                case 3:
                    $d[2] = "три";
                    break;
                case 4:
                    $d[2] = "четыре";
                    break;
                case 5:
                    $d[2] = "пять";
                    break;
                case 6:
                    $d[2] = "шесть";
                    break;
                case 7:
                    $d[2] = "семь";
                    break;
                case 8:
                    $d[2] = "восемь";
                    break;
                case 9:
                    $d[2] = "девять";
                    break;
            }
        endif;

        return $d[0].' '.$d[1].' '.$d[2];
    }

}
?>