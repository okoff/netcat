<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)) . ( strstr(__FILE__, "/") ? "/" : "\\" );

include_once ($NETCAT_FOLDER . "vars.inc.php");
global $MODULE_FOLDER;
require_once ($MODULE_FOLDER . "netshop/kxlib.php");
//подключаем файл с классом CalculatePriceDeliveryCdek
include_once($NETCAT_FOLDER."calc/CalculatePriceDeliveryCdek.php");

class NetShop {

    /**
     * ID типа магазина
     */
    var $shop_table;

    /**
     * ID магазина
     */
    var $shop_id;

    /**
     * корзинка (array) -- для добавления/удаления (сгруппировано по type, id)
     * хранится в session
     */
    var $Cart;

    /**
     * Свойства заказа
     */
    var $Order;

    /**
     * корзина: подробная информация
     */
    var $CartContents;
    var $DefaultCurrencyID;
    // -- id
    var $Currencies;
    // -- name [Classificator_ShopCurrencies]
    var $CurrencyDetails;
    // currency details
    var $Rates;
    // -- array[id] = rate
    var $DepartmentSettings;
    // cache
    var $SendMails;
    // [адрес@почты] => [индексы в CartContents]
    var $OrderID;
    // for loaded from db orders
    var $TotalDiscountSum;
    // сумма товарных скидок и скидок на корзину
    var $CartDiscounts;
    // информация примененных скидках на корзину
    var $CartDiscountSum;
    // сумма скидок на корзину
    var $CartSumBeforeCartDiscounts;
    // whoa... сумма по корзине до применения "корзинных" скидок
    var $GoodsTypeIDs; // array

    /**
     * Возвращает массив с номерам компонентов, используемых для каталога товаров
     */

    static public function get_goods_table() {
        $result = array();

        $goods = nc_Core::get_object()->modules->get_vars("netshop", "GOODS_TABLE");
        $goods = array_map('intval', explode(',', $goods));
        foreach ($goods as $v)
            if ($v)
                $result[] = $v;

        return $result;
    }

    /**
     * constructor
     * @var array массив с товарами, которые нужно положить в корзину вида [type_id][id]=qty
     */
    function NetShop($put_to_cart = false) {
        // Произвести авторизацию, если модуль интернет-магазина загрузился
        // раньше, чем произошла авторизация.
        // (От группы пользователей могут зависеть скидки.)

        global $AUTH_USER_ID, $AUTH_USER_GROUP;

        // map module settings to the object
        foreach ((array) $GLOBALS["MODULE_VARS"]["netshop"] as $k => $v) {
            $this->{strtolower($k)} = $v;
        }

        // определить ID магазина в этом сайте
        $this->shop_id = GetSubdivisionByType($this->shop_table, "Subdivision_ID");

        if (!$this->shop_id) {
            return false;
        }

        // Сохранить корзину в сессии
        if ($_SESSION["cart_$this->shop_id"])
            $this->Cart = $_SESSION["cart_$this->shop_id"];
        $_SESSION["cart_$this->shop_id"] = &$this->Cart;

        // настройки интернет-магазина -----------------------------------------
        $row = row("SELECT * FROM Message$this->shop_table WHERE Subdivision_ID=$this->shop_id");
        if (!$row["Message_ID"]) {
            print NETCAT_MODULE_NETSHOP_TITLE.": ".NETCAT_MODULE_NETSHOP_ERROR_NO_SETTINGS."<br>";
            return false;
        }

        $stoplist = array("Message_ID", "User_ID", "Subdivision_ID",
            "Sub_Class_ID", "Priority", "Checked", "TimeToDelete",
            "TimeToUncheck", "IP", "UserAgent", "Parent_Message_ID",
            "Created", "LastUpdated", "LastUser_ID", "LastIP",
            "LastUserAgent", "Keyword");

        foreach ($row as $k => $v) {
            if (!in_array($k, $stoplist))
                $this->$k = $v;
        }

        // Настройки раздела имеют приоритет над настройками магазина
        // ----------------------------------------------------------------------
        // курсы валют
        $res = q("SELECT ShopCurrency_ID as Currency_ID, ShopCurrency_Name as Currency_Name
                FROM Classificator_ShopCurrency");

        while (list($cid, $c) = mysql_fetch_row($res)) {
            $this->Currencies[$cid] = $c;
        }

        // курсы валют ЦБ + Внутренние курсы
        $res = q("SELECT Currency, Rate
                FROM Message$this->official_rates_table
                WHERE Subdivision_ID=$this->shop_id
                ORDER BY Date DESC
                LIMIT " . count($this->Currencies)); // пїЅ GROUP пїЅпїЅпїЅ-пїЅпїЅ пїЅпїЅ пїЅпїЅ
        while (list($cid, $rate) = mysql_fetch_row($res)) {
            if (!$this->Rates[$cid])
                $this->Rates[$cid] = $rate;
        }

        // внутренние курсы имеют приоритет над официальными курсами
        $res = q("SELECT *
                FROM Message$this->currency_rates_table
                WHERE Subdivision_ID=$this->shop_id
                  AND Checked=1");


        while ($row = mysql_fetch_assoc($res)) {
            // If rate is set explicitly, it overrides automatically fetched rate
            if ($row["Rate"])
                $this->Rates[$row["Currency"]] = $row["Rate"];
            $this->CurrencyDetails[$this->Currencies[$row["Currency"]]] = $row;
        }


        if ($AUTH_USER_ID && $AUTH_USER_GROUP && $this->price_rules_table) {
            $col = value1("SELECT ActivePriceColumn
                        FROM Message$this->price_rules_table
                        WHERE UserGroup='$AUTH_USER_GROUP'
                          AND Subdivision_ID = $this->shop_id
                        LIMIT 1");

            $this->SetPriceColumn(($col ? $col : "Price"));
        } else {
            $this->SetPriceColumn("Price");
        }

        // units
        $res = q("SELECT * FROM Classificator_ShopUnits");
        while (list($id, $name) = mysql_fetch_row($res)) {
            $this->Units[$id] = $name;
        }

        // Положить товары в корзину
        if ($put_to_cart) {
            $this->CartPut($put_to_cart);
        }

        // мэп вэриэблез
        $this->CartContents();
        $this->MapVariables();
    }

    /**
     *  Установить колонку с ценами и валютами
     */
    function SetPriceColumn($col) {
        // Может меняться e.g. в зависимости от группы пользователя или других факторов
        $this->PriceColumn = $col;
        $this->CurrencyColumn = "Currency" . (str_replace("Price", "", $this->PriceColumn));
    }

    function CartDiscountSum() {
        return $this->CartDiscountSum;
    }

    /**
     * Сумма покупок, совершенных пользователем $user_id
     * @param int id пользователя, по умолчанию - id залогинившегося пользователя
     * @return float
     */
    function PrevOrdersSum($user_id = 0) {
        global $AUTH_USER_ID;
        // cache results in array:
        static $prev_order_sum;

        if ($prev_order_sum[$user_id]) {
            return $prev_order_sum[$user_id];
        }

        if (!$user_id)
            $user_id = $AUTH_USER_ID;

        if (!int($user_id))
            return 0;

        // PREV_ORDERS_SUM_STATUS_ID должен быть в числом или строкой в формате "1,2,3"
        if ($this->prev_orders_sum_status_id) {
            if (!preg_match("/^\s*\d+(?:\s*,\s*\d+)*\s*$/", $this->prev_orders_sum_status_id)) {
                trigger_error(NETCAT_MODULE_NETSHOP_NO_PREV_ORDERS_STATUS_ID, E_USER_WARNING);
                return 0;
            }
        }

        global $db;
        $sum = $db->get_var("SELECT SUM(o.ItemPrice * o.Qty)
                             FROM Netshop_OrderGoods as o,
                                  Message$this->order_table as m
                            WHERE m.User_ID=$user_id
                              AND m.Status IN ($this->prev_orders_sum_status_id)
                              AND m.Message_ID=o.Order_ID");

        // consider cart discounts also
        $cart_discounts = $db->get_var("SELECT SUM(d.Discount_Sum)
                                        FROM Message$this->order_table as m,
                                             Netshop_OrderDiscounts as d
                                       WHERE m.User_ID=$user_id
                                         AND m.Status IN ($this->prev_orders_sum_status_id)
                                         AND m.Message_ID = d.Order_ID
                                         AND d.Item_Type=0");

        $prev_order_sum[$user_id] = $sum - $cart_discounts;

        return $prev_order_sum[$user_id];
    }

    /**
     * Определить глобальные переменные (так типа удобнее с неткетовскими шаблонами)
     */
    function MapVariables() {
        global $NETSHOP, $SUB_FOLDER;
        $NETSHOP["Netshop_TotalPrice"] = $this->FormatCurrency($this->CartSum());
        $NETSHOP["Netshop_ItemCount"] = $this->CartCount();
        $NETSHOP["Netshop_CartContents"] = &$this->CartContents;
        $NETSHOP["Netshop_CartDiscountSum"] = $this->FormatCurrency($this->CartDiscountSum);

        // static:
        if (!$NETSHOP["Netshop_CartURL"]) {
            $row = GetSubdivisionByType($this->cart_table, "Hidden_URL, s.Subdivision_Name");
            $NETSHOP["Netshop_CartURL"] = $SUB_FOLDER . $row["Hidden_URL"];
            $NETSHOP["Netshop_CartName"] = $row["Subdivision_Name"];

            // links to order template
            $row = GetTemplateByType($this->order_table, $this->shop_id, "c.EnglishName, s.Hidden_URL, c.Sub_Class_Name");
			
			$NETSHOP["Netshop_OrderURL"] = $SUB_FOLDER . "$row[Hidden_URL]add_$row[EnglishName].html";
			
        }
		
			
        // "В корзине ... товаров на сумму ... "
        if ($NETSHOP["Netshop_ItemCount"]) {
            $NETSHOP["Netshop_CartSum"] =
                    sprintf(NETCAT_MODULE_NETSHOP_CART_CONTENTS, $NETSHOP["Netshop_CartURL"], "$NETSHOP[Netshop_ItemCount] " . netshop_language_count($NETSHOP["Netshop_ItemCount"], NETCAT_MODULE_NETSHOP_ITEM_FORMS), $NETSHOP["Netshop_TotalPrice"]);
            $NETSHOP["Netshop_OrderLink"] = "<a href='$NETSHOP[Netshop_OrderURL]'>" . NETCAT_MODULE_NETSHOP_CART_CHECKOUT . "</a>"; //$row["Sub_Class_Name"];
        } else {
            $NETSHOP["Netshop_CartSum"] = NETCAT_MODULE_NETSHOP_CART_EMPTY;
            $NETSHOP["Netshop_OrderLink"] = "";
        }

        foreach ($GLOBALS["NETSHOP"] as $k => $v) {
            $GLOBALS[$k] = &$GLOBALS["NETSHOP"][$k];
        }
    }

    /**
     * добавление товара в корзину
     * (удаление, если количество = 0)
     * @param array  [$type_id][$id] = $new_qty
     * @param string mode ("add": qty=qty+new_qty; otherwise: qty=new_qty)
     */
    function CartPut($array, $mode = "", $custom_params = array()) {
        if (!$this->shop_id) {
            return false;
        }

        foreach ((array) $array as $type_id => $arr) {
            $component = new nc_Component($type_id);
            $fields = $component->get_fields();
            $typeof_unit = 'intval';
            foreach ($fields as $k => $v) {
                if ($v['name'] == 'StockUnits' && $v['type'] == 7)
                    $typeof_unit = 'doubleval';
            }

            $type_id = intval($type_id);

            foreach ((array) $arr as $id => $qty) {
                $id = intval($id);
                $qty = str_replace(",", ".", $qty);
                $qty = call_user_func($typeof_unit, $qty);
				
                if ($qty <= 0) {
                    unset($this->Cart["goods"][$type_id][$id]);

                    if (!count($this->Cart["goods"][$type_id])) {
                        unset($this->Cart["goods"][$type_id]);
                    }
                } else {
                    if ($mode == "add") {
                        $this->Cart["goods"][$type_id][$id] = array(
                                "Qty" => $this->Cart["goods"][$type_id][$id]["Qty"] + $qty,
                                "cart_params" => (array) $custom_params);
                    } else {
                        $this->Cart["goods"][$type_id][$id] = array(
                                "Qty" => $qty,
                                "cart_params" => $this->Cart["goods"][$type_id][$id]['cart_params']);
                    }
                }
            }
        }

        $_SESSION["cart_$this->shop_id"] = &$this->Cart;
        return true;
    }

    /**
     * Содержимое корзины
     * @param bool заказ из БД (true) / товары из корзины (default)
     * возвращает массив
     *    Type_ID     -- id таблицы message
     *    Qty         -- количество
     *    ItemPrice  -- цена единицы
     *    TotalPrice -- стоимость (цена*количество)
      + свойства товара
     */
    function CartContents() {
        if (!$this->CartCount())
            return;
        $ret = array();

        // получить данные о товарах
        foreach ($this->Cart["goods"] as $type_id => $arr) {
            $res = q("SELECT m.*,

                          IFNULL(m.$this->PriceColumn, parent.$this->PriceColumn) as Price4User,
                          IF(m.$this->PriceColumn IS NULL, parent.$this->CurrencyColumn, m.$this->CurrencyColumn) as Currency4User,

                          IFNULL(m.PriceMinimum, parent.PriceMinimum) as PriceMinimum,

                          IF(m.Keyword IS NULL OR m.Keyword = '', CONCAT(u.Hidden_URL, s.EnglishName, '_', m.Message_ID, '.html'),
                                                CONCAT(u.Hidden_URL, m.Keyword, '.html')) as URL

                     FROM (Message57 as m,
                           Subdivision as u,
                           Sub_Class as s)

                     LEFT JOIN Message57 as parent
                            ON (m.Parent_Message_ID != 0 AND m.Parent_Message_ID = parent.Message_ID)

                     WHERE m.Message_ID IN (" . join(",", array_keys($arr)) . ")
                       AND s.Sub_Class_ID = m.Sub_Class_ID
                       AND u.Subdivision_ID = m.Subdivision_ID
                     ");
			/*echo "SELECT m.*,

                          IFNULL(m.$this->PriceColumn, parent.$this->PriceColumn) as Price4User,
                          IF(m.$this->PriceColumn IS NULL, parent.$this->CurrencyColumn, m.$this->CurrencyColumn) as Currency4User,

                          IFNULL(m.PriceMinimum, parent.PriceMinimum) as PriceMinimum,

                          IF(m.Keyword IS NULL OR m.Keyword = '', CONCAT(u.Hidden_URL, s.EnglishName, '_', m.Message_ID, '.html'),
                                                CONCAT(u.Hidden_URL, m.Keyword, '.html')) as URL

                     FROM (Message57 as m,
                           Subdivision as u,
                           Sub_Class as s)

                     LEFT JOIN Message57 as parent
                            ON (m.Parent_Message_ID != 0 AND m.Parent_Message_ID = parent.Message_ID)

                     WHERE m.Message_ID IN (" . join(",", array_keys($arr)) . ")
                       AND s.Sub_Class_ID = m.Sub_Class_ID
                       AND u.Subdivision_ID = m.Subdivision_ID";*/
            while ($row = mysql_fetch_assoc($res)) {
                // Заказ, загруженный при помощи LoadOrder:
                if ($arr[$row["Message_ID"]]["ItemPrice"]) {
                    $price = $arr[$row["Message_ID"]]["ItemPrice"]; // with discounts
                    $original_price = $arr[$row["Message_ID"]]["OriginalPrice"]; // without discounts
                } else { // еще не записанный заказ:
                    $price = $this->ConvertCurrency($row["Price4User"], $row["Currency4User"]);
                    $original_price = $price; // discounts haven't been applied yet
                }

                $qty = $arr[$row["Message_ID"]]["Qty"];

                $ret[] = array_merge($row, array("Class_ID" => $type_id,
                        // to use with 'cart$RowID'
                        "RowID" => "[$type_id][$row[Message_ID]]",
                        "Qty" => $qty,
                        "Units" => $this->Units[$row["Units"]],
                        // PriceMinumum is stored to the SHOP CURRENCY:
                        "PriceMinimum" => $this->ConvertCurrency($row["PriceMinimum"], $row["CurrencyMinimum"]),
                        // Formatted prices:
                        "ItemPriceF" => $this->FormatCurrency($price),
                        "TotalPriceF" => $this->FormatCurrency($price * $qty),
                        // Raw prices in default currency
                        "ItemPrice" => $price,
                        "TotalPrice" => $price * $qty,
                        // Исходные цены (order from db)
                        "OriginalPrice" => $original_price,
                        "OriginalPriceF" => $this->FormatCurrency($original_price),
                        "Discounts" => $arr[$row["Message_ID"]]["Discounts"],
						"StockUnits" => $row["StockUnits"],
                        'cart_params' => $arr[$row["Message_ID"]]['cart_params']));

                // коллекционируем адреса, потом сделаем рассылку
                $manager_email = $this->GetDepartmentSetting("ManagerEmail", $type_id, $row["Message_ID"], $row["Subdivision_ID"]);
                $this->SendMails[$manager_email][] = sizeof($ret) - 1;
            } // of (foreach row)
        }

        $this->CartContents = $ret;
        if (!$this->OrderID)
            $this->ApplyDiscounts();
        $this->MapVariables();
        return $ret;
    }

    /**
     *  Сумма по полю $field (в массиве, полученном в CartContents)
     */
    function CartFieldSum($field) {
        //      if (!$this->CartContents) $this->CartContents();
		
        $sum = 0;

        for ($i = 0; $i < count($this->CartContents); $i++) {
			//echo $i." ".$field." ".$this->CartContents[$i][$field]."<br>";
            $sum += $this->CartContents[$i][$field] * $this->CartContents[$i]["Qty"];
        }

        return $sum;
    }

    /**
     * Сумма по корзине
     */
    function CartSum() {
        $sum = $this->CartFieldSum("ItemPrice");
		//echo $sum;
        //$sum = $this->CartFieldSum("OriginalPrice");
        $sum = $sum - $this->CartDiscountSum;
		//echo $this->CartDiscountSum;
        if ($this->Order) {
            $sum += $this->Order["PaymentCost"];
            $sum += $this->Order["DeliveryCost"];
        }
        return $sum;
    }

    /**
     * Информация о скидках, которые могут быть применены к товару
     * (для текущего пользователя)
     *
     * @param integer ID шаблона товара
     * @param integer ID товара
     * @return array массив с информацией о скидках, которые могут быть применены к товару
     *   содержит следующие элементы:
     *     Name, Description, UserGroups, Goods, ValidFrom, ValidTo, Condition,
     *     Function, FunctionDestination, FunctionOperator, StopItem
     */
    function ItemDiscountList($subdivision_id, $goods_class_id) {
        global $db, $current_user;
        $subdivision_id = intval($subdivision_id);
        $goods_class_id = intval($goods_class_id);
        $discounts = $db->get_results(
                "SELECT Name, Description, UserGroups, Goods, ValidFrom, ValidTo, `Condition`,
                 Function, FunctionDestination, FunctionOperator, StopItem
            FROM Message{$this->discount_table}
           WHERE AppliesTo = 1
             AND (Subdivisions IS NULL OR Subdivisions='' OR FIND_IN_SET('$subdivision_id', Subdivisions))
             AND (GoodsTypes IS NULL OR GoodsTypes='' OR GoodsTypes = '$goods_class_id')
             AND (UserGroups IS NULL OR UserGroups='' OR FIND_IN_SET('" . $current_user['PermissionGroup_ID'] . "', UserGroups))
             AND ((ValidFrom IS NULL AND ValidTo IS NULL) OR
                  (ValidFrom <= NOW() AND ValidTo >= NOW()))
             AND (TypeOfPrice IS NULL OR TypeOfPrice='' OR FIND_IN_SET('{$this->PriceColumn}', TypeOfPrice))
             AND Checked = 1
           ORDER BY Priority DESC", ARRAY_A);
		
		//print_r($discounts);
        return (array) $discounts;
    }

    /**
     * Получить скидку для товара в абсолютном исчислении
     *
     * Данный метод учитывает только скидки, которые применяются к
     * товарам (не к корзине) и у которых не указано условие примения
     * Cкидки, в результате которых изменяется количество, а не стоимость
     * товара, не учитываются.
     *
     * Внимание! Скидка возвращается в основной валюте!
     *
     * Пример использования:
     *  $shop->ItemDiscountSum($sub, $classID, $f_RowID, $Price, $Currency)
     *
     * @param integer ID шаблона товара
     * @param integer ID товара
     * @param double цена товара в основной валюте
     * @return mixed скидка на товар в абсолютном исчислении в основной валюте.
     *   Отрицательное число означает наценку.
     * РАБОТАЕТ ПРАВИЛЬНО
     */
    function ItemDiscountSum($subdivision_id, $goods_class_id, $goods_id, $price, $currency) {
        static $discounts;

        if (!isset($discounts[$subdivision_id][$goods_class_id])) {
            $discounts[$subdivision_id][$goods_class_id] =
                    $this->ItemDiscountList($subdivision_id, $goods_class_id);
        }

        $price_with_discounts = $this->ConvertCurrency($price, $currency);

        foreach ($discounts[$subdivision_id][$goods_class_id] as $discount) {
            // сложные скидки (когда изменяется количество или когда указано условие) не учитываются!
            if ($discount["Condition"])
                continue;
            if ($discount["FunctionDestination"] != '[TotalPrice]')
                continue;

            // относится ли скидка к данному товару:
            if ($discount["Goods"] && strpos(",$discount[Goods],", "$goods_class_id:$goods_id,") === FALSE)
                continue;

            $tmp = @eval("return (\$price_with_discounts $discount[FunctionOperator] $discount[Function]);");
			//echo $tmp;
            if ($tmp)
                $price_with_discounts = $tmp;
        }

        return ($price - $price_with_discounts);
    }

    /**
     * Find Eligible Discounts
     */
    function ApplyDiscounts() {
        if (!$this->CartCount())
            return;

        //      $mysql_collation = ((double)mysql_get_server_info() >= 4.1 ? "_cp1251 ":"");
        $this->TotalDiscountSum = 0;

        $cart_goods_types = array_keys($this->Cart['goods']);

        foreach ($cart_goods_types as $g) {
            $goods_types[] = "FIND_IN_SET($mysql_collation'$g', GoodsTypes)";
        }
        $goods_types = join(" OR ", $goods_types);
        global $AUTH_USER_ID;
        // подходящие нам на первый взгляд (без проверки goods_id, conditions) скидки
        $discounts = assoc_array("
SELECT * FROM Message$this->discount_table as a, User_Group as ug
WHERE 1 AND
(
  (
    a.AppliesTo=2 /* whole cart */ AND
    (a.UserGroups = '' OR FIND_IN_SET(ug.PermissionGroup_ID, a.UserGroups))
  )
  OR
  (
    /* discounts applied to goods */ (a.GoodsTypes = '' OR $goods_types) AND
    (a.UserGroups = '' OR FIND_IN_SET(ug.PermissionGroup_ID, a.UserGroups))
  )
) AND ( /* common part for both cart and goods discounts */
  (
    (a.ValidFrom IS NULL  AND a.ValidTo IS NULL)  OR
    (a.ValidFrom <= NOW() AND a.ValidTo >= NOW())
  ) AND
  (a.TypeOfPrice = '' OR FIND_IN_SET('$this->PriceColumn', a.TypeOfPrice)) AND
  Checked = 1
)
GROUP BY a.Message_ID ORDER BY a.AppliesTo, a.Priority DESC
");


        foreach ($this->CartContents as $idx => $row) {
            foreach ($discounts as $discount) {
                if ($discount["AppliesTo"] == 1) { // aplies to goods
                    // check: subdivision (applies to this subdivision or it's parent)
                    if ($discount["Subdivisions"]) {
                        if (strpos(",$discount[Subdivisions],", ",$row[Subdivision_ID],") === false) { // no exact match // check if this item is child of these subdivisions
                            $got_it = false;

                            $parent = $row["Subdivision_ID"];

                            do {
                                $prev_parent = $parent;
                                $parent = $parent_cache[$prev_parent] ? $parent_cache[$prev_parent] : value1("SELECT Parent_Sub_ID FROM Subdivision WHERE Subdivision_ID = $parent");
                                $parent_cache[$prev_parent] = $parent;

                                if (strpos(",$discount[Subdivisions],", ",$parent,") !== false) {
                                    $got_it = true;
                                    break; // exit while
                                }
                            } while ($parent && $parent != $this->shop_id);

                            if (!$got_it)
                                continue; // next discount
                        }
                    }

                    // check: goods ids
                    if ($discount["Goods"]) {
                        $tp = ",$discount[Goods],";
                        if (strpos($tp, ",$row[Class_ID]:$row[Message_ID],") === false &&
                                strpos($tp, ",$row[Class_ID]:$row[Parent_Message_ID],") === false) {
                            continue; // next discount
                        }
                    }

                    // parse condition into evaluable code
                    if (!$discount["cCondition"]) { // not "parsed" yet
                        $discount["Condition"] = str_replace("[PrevOrdersSum]", '$this->PrevOrdersSum()', $discount["Condition"]);

                        foreach (array("Condition", "Function") as $k) {
                            $discount["c$k"] = nc_preg_replace("/\[(\w+)\]/", "\$row[$1]", $discount[$k]);
                            // replace single '=' to double '=='
                            $discount["c$k"] = nc_preg_replace("/([^=<>]+)=([^=]+)/", "$1==$2", $discount["c$k"]);
                            $discount["c$k"] = str_replace(",", ".", $discount["c$k"]);
                        }
                    }

                    // if there's a condition, evaluate it to determine whether the discount is eligible
                    if ($discount["cCondition"]) {
                        if (!@eval("return $discount[cCondition];")) {
                            continue;
                        } // goto next discount
                    }


                    if ($new_value = @eval("return \$row$discount[FunctionDestination] $discount[FunctionOperator] $discount[cFunction];")) {
                        // that's for short
                        $cart = &$this->CartContents[$idx];

                        // changing price
                        if ($discount["FunctionDestination"] == "[TotalPrice]") {
                            $old_price = $cart["TotalPrice"];

                            // check: minimal price reached
                            if ((double) $cart["PriceMinimum"] > $new_value / $cart["Qty"]) {
                                $minimal_price_reached = true;
                                $new_value = $this->round($cart["PriceMinimum"]) * $cart["Qty"];
                                $cart["ItemPrice"] = $cart["PriceMinimum"];
                                $cart["TotalPrice"] = $new_value;
                            } else {
                                $minimal_price_reached = false;
                                $cart["TotalPrice"] = $this->round($new_value);
                                $cart["ItemPrice"] = $this->round($new_value / $cart["Qty"]);
                            }

                            // двойной пересчёт! (коррекция копеек)
                            if ($cart["TotalPrice"] != $cart["ItemPrice"] * $cart["Qty"]) {
                                // для любопытных: это для того, чтобы избежать видимого несоответствия
                                // цены и стоимости из-за округления.
                                $cart["TotalPrice"] = $cart["ItemPrice"] * $cart["Qty"];
                            }
                        }

                        // changing qty
                        if ($discount["FunctionDestination"] == "[Qty]") {
                            $old_price = $cart["ItemPrice"] * $new_value;

                            $cart["Qty"] = $new_value;
                            $cart["ItemPrice"] = $this->round($cart["TotalPrice"] / $new_value);

                            // check: minimal price reached
                            if ((double) $cart["PriceMinimum"] > $cart["ItemPrice"]) {
                                $minimal_price_reached = true;
                                $cart["ItemPrice"] = $this->round($cart["PriceMinimum"]);
                            }

                            $cart["TotalPrice"] = $cart["ItemPrice"] * $new_value;
                        }
                    } // of "apply 'function'"
                    else {
                        continue; // next discount
                    }

                    // Formatted prices:
                    $cart["ItemPriceF"] = $this->FormatCurrency($cart["ItemPrice"]);
                    $cart["TotalPriceF"] = $this->FormatCurrency($cart["ItemPrice"] * $cart["Qty"]);

                    // total discount sum
                    $discount_sum = $old_price - $cart["TotalPrice"];
                    $this->TotalDiscountSum += $discount_sum; // cart-wide discount sum
                    $cart["DiscountSum"] += $discount_sum; // this item discount sum
                    // discount info
                    $cart["Discounts"][] = array("Sum" => $discount_sum,
                        "SumF" => $this->FormatCurrency($discount_sum),
                        "Discount_ID" => $discount["Message_ID"],
                        "Name" => $discount["Name"],
                        "Description" => $discount["Description"],
                        "PriceMinimum" => $minimal_price_reached);

                    if ($discount["StopItem"] || $minimal_price_reached)
                        break; // go to next goods
                } // of "discount applies to goods"
            } // of "foreach discounts"
        } // of "foreach cartcontents"
        // CART-LEVEL DISCOUNTS  - - - - - - - - - - - - - - - - - - - - - - - - - - - -v
        $minimal_price_reached = false;
        $minimal_sum = $this->CartFieldSum('PriceMinimum');

        foreach ($discounts as $discount) {
            $this->CartSumBeforeCartDiscounts = $this->CartFieldSum('ItemPrice');

            if ($discount["AppliesTo"] == 2) { // aplies to cart
                if ($discount["FunctionDestination"] != "[TotalPrice]")
                    continue; // only cost can be changed
// parse condition into evaluable code
                if (!$discount["cCondition"]) { // not "parsed" yet
                    $discount["Condition"] = str_replace("[PrevOrdersSum]", '$this->PrevOrdersSum()', $discount["Condition"]);

                    foreach (array("Condition", "Function") as $k) {
                        $discount["c$k"] = str_replace("[Qty]", $this->CartCount(), $discount[$k]);
                        $discount["c$k"] = str_replace("[TotalPrice]", "(\$this->CartSumBeforeCartDiscounts - \$this->CartDiscountSum)", $discount["c$k"]);
                        $discount["c$k"] = nc_preg_replace("/\[(\w+)\]/", "\$this->CartFieldSum('$1')", $discount["c$k"]);
                        // replace single '=' to double '=='
                        $discount["c$k"] = nc_preg_replace("/([^=<>]+)=([^=]+)/", "$1==$2", $discount["c$k"]);
                    }

                    $discount["FunctionOperator"] = str_replace("=", "", $discount["FunctionOperator"]);
                }

                // check condition (if any)
                if ($discount["cCondition"]) {
                    if (!@eval("return $discount[cCondition];")) {
                        continue;
                    } // goto next discount
                }

                $old_value = $this->CartSumBeforeCartDiscounts - $this->CartDiscountSum;

                if ($new_value = @eval("return \$old_value $discount[FunctionOperator] $discount[cFunction];")) {
                    // minimal price reached???
                    if ($minimal_sum > $new_value) {
                        $minimal_price_reached = true;
                        $new_value = $minimal_sum;
                    }

                    $discount_sum = $this->round($old_value - $new_value);

                    $this->TotalDiscountSum += $discount_sum;
                    $this->CartDiscountSum += $discount_sum;
                    $this->CartDiscounts[] = array("Sum" => $discount_sum,
                        "SumF" => $this->FormatCurrency($discount_sum),
                        "Discount_ID" => $discount["Message_ID"],
                        "Name" => $discount["Name"],
                        "Description" => $discount["Description"],
                        "PriceMinimum" => $minimal_price_reached);
                }

                if ($discount["StopCart"] || $minimal_price_reached)
                    break; // done with discounts
            } // of "applies to cart"
        } // of "each discount"
    }

// of "function applydiscounts"

    /**
     * Перевод в [базовую] валюту
     */
    function ConvertCurrency($sum, $from_currency_id, $to_currency = "") {
        if (!$to_currency)
            $to_currency = $this->DefaultCurrencyID;

        // someone might pass Currency_Name instead of Currency_ID by mistake:
        // and i've made such example in docs...
        $to_currency_id = is_numeric($to_currency) ? $to_currency : $this->CurrencyDetails[$to_currency]["Currency"];

        if (!$sum || $from_currency_id == $to_currency_id || !$this->Rates[$from_currency_id])
            return $sum;
        if (!$this->Rates[$to_currency_id])
            $this->Rates[$to_currency_id] = 1;

        // -----------------vvvv т.е. кросс-курс валюты
        $sum = $sum * ($this->Rates[$from_currency_id] / $this->Rates[$to_currency_id]);

        if ($this->CurrencyConversionPercent) {
            $sum = $sum * (100 + $this->CurrencyConversionPercent) / 100;
        }

        // округлить до знака, указанного в настройках
        $sum = $this->round($sum);
        return $sum;
    }

    /**
     * количество товаров в корзине
     */
    function CartCount() {
        $count = 0;
        foreach ((array) $this->Cart["goods"] as $row) {
            $count += count($row);
        }
        return $count;
    }
	// check items in cart
	function CheckCart() {
		$return=1;
		foreach ((array) $this->Cart["goods"] as $item) {
			foreach ($item as $type_id => $arr) {
				//echo $type_id;
				//print_r($arr);
				$sql="SELECT status,StockUnits,complect FROM Message57 WHERE Message_ID=".$type_id;
				//echo $sql."<br>";
				$res=q($sql);
				while ($row = mysql_fetch_assoc($res)) {
					//echo $row["StockUnits"]."<br>";
					if ($row["StockUnits"]==0) {
						$return=0;
						break;
					}
					if ($row["complect"]!="") {
						$itemco = explode(";", $row["complect"]);
						//print_r($itemco);
						for ($j=0; $j<count($itemco); $j++) {
							if ($itemco[$j]!="") {
								$tmp = explode(":", $itemco[$j]);
								//print_r($tmp);
								$sql="SELECT StockUnits FROM Message57 WHERE ItemID LIKE '".$tmp[0]."'";
								$res1=q($sql);
								while ($row1 = mysql_fetch_assoc($res1)) {
									if ($row1["StockUnits"]<$tmp[1]) {
										$return=0;
										break;
									}
								}
							}
						}
					}
				}
			}
		}
		return $return;
	}
    /**
     * Форматрирование валюты
     */
    function FormatCurrency($sum, $currency = "", $no_nbsp = false, $font_size = false) {
        // currency_id supplied:
        if (is_numeric($currency)) {
            $currency = $this->Currencies[$currency];
        }
        if (!$currency)
            $currency = $this->Currencies[$this->DefaultCurrencyID];
        $params = &$this->CurrencyDetails[$currency];

        if ($params) {
            $currency = $params["NameShort"];
        }

        if ($params["ThousandSep"] == '[space]') {
            $params["ThousandSep"] = ' ';
        }

        $ret = number_format($sum, $params["Decimals"] ? $params["Decimals"] : NETCAT_MODULE_NETSHOP_CURRENCY_DECIMALS, $params["DecPoint"] ? $params["DecPoint"] : NETCAT_MODULE_NETSHOP_CURRENCY_DEC_POINT, $params["ThousandSep"] ? $params["ThousandSep"] : NETCAT_MODULE_NETSHOP_CURRENCY_THOUSAND_SEP
        );

        $ret = sprintf(str_replace("#", $currency, $params["Format"] ? $params["Format"] : NETCAT_MODULE_NETSHOP_CURRENCY_FORMAT
                ), $ret);

        if (!$no_nbsp) {
            $ret = str_replace(" ", "&nbsp;", $ret);
        }
        if ($font_size) {
            $ret = "<font size='$font_size'>$ret</font>";
        }

		$ret = str_replace("RUR", "руб.", $ret);
        return $ret;
    }

    /**
     * Получить массив со способами (оплаты | доставки), удовлетворяющими условиям
     */
    function EligibleMethodsOf($what, $count_sum = true) {
		
		if ($what=="Delivery") {
			$table_id=56;
		} else if ($what=="Payment") {
			$table_id=55;
		} else {		
			$table_id = $this->{"{$what}_methods_table"};
		}
        if (!$table_id) {
            trigger_error("Unknown additional cost '$what', check shop settings", E_USER_ERROR);
            return 0;
        }

        //$res = q("SELECT * FROM Message$table_id
        //        WHERE Subdivision_ID=$this->shop_id
        //          AND Checked=1
        //        ORDER BY Priority DESC"); 

		$res = q("SELECT * FROM Message$table_id
                WHERE Subdivision_ID=57
                  AND Checked=1
                ORDER BY Priority DESC");

        $ret = array();

        if ($count_sum)
            $sum = $this->CartSum();

        while ($row = mysql_fetch_assoc($res)) {
            if ($row["Condition"]) {
                $condition = str_replace("[Qty]", $this->CartCount(), $row["Condition"]);
                $condition = str_replace("[TotalPrice]", "\$this->CartSum()", $condition);
                $condition = nc_preg_replace("/\[(\w+)\]/", "\$this->CartFieldSum('$1')", $condition);
                // replace single '=' to double '=='
                $condition = nc_preg_replace("/([^=<>]+)=([^=]+)/", "$1==$2", $condition);

                // check condition (if any)
                if ($condition) {
                    if (!@eval("return $condition;")) {
                        continue;
                    } // goto next method
                }
            }

            if ($count_sum) {
                $row["Sum"] = 0;
                if ($row["Multiplier"])
                    $row["Sum"] = $sum * ($row["Multiplier"] - 1); // relative sum
                $row["Sum"] += $row["Cost"]; // absolute sum
                $row["Sum"] = $this->round($row["Sum"]);
            }

            $ret[$row["Message_ID"]] = $row;
        }

        return $ret;
    }

    /**
     * Округлить до знака, как указано в настройках валюты (если не указано - до 2 знаков после зпт)
     */
    function round($sum, $currency_symbol = "") {
        if (!$currency_symbol)
            $currency_symbol = $this->Currencies[$this->DefaultCurrencyID];

        $def_currency_settings = $this->CurrencyDetails[$currency_symbol];

        $sum = round($sum, strlen($def_currency_settings["Decimals"]) ?
                        $def_currency_settings["Decimals"] : 2);
        return $sum;
    }

    /**
     * Опустошить корзину
     */
    function ClearCartContents() {
        unset($this->CartContents);
        unset($this->Cart);
        unset($_SESSION["cart_$this->shop_id"]);
        $this->MapVariables();
    }

	// списание комплекта
	function writeoffComplect($complect_id,$strcomplect,$qty,$retail_id=0,$order_id=0) {
		//echo "writeoffComplect<br>";
		$ok=1;
		$arset=explode(";",$strcomplect);
		foreach($arset as $ars) {
			$ars=str_replace(" ","",$ars);
			if ($ars!="") {
				$t=explode(":",$ars);
				//print_r($t);
				//echo "<br>";
				$sql="SELECT Message_ID,StockUnits,status,Name,complect FROM Message57 WHERE ItemID LIKE '{$t[0]}'";
				//echo $sql."<br>"; 
				if ($res1=q($sql)) {
					if ($row2=mysql_fetch_array($res1)) {
						//echo $row2['Message_ID']."-".$row2['StockUnits']."-".$t[1]."-".$qty."<br>";
						// проверить количество на складе. Если больше, чем в комплекте - списать. Иначе - ошибка
						if ($row2['StockUnits']<$t[1]*$qty) {
							$ok=0;
						} else {
							if ($row2["StockUnits"]>$t[1]*$qty) {
								// просто обновить количество
								$sql="UPDATE Message57 SET StockUnits=StockUnits-".($t[1]*$qty)." WHERE Message_ID={$row2['Message_ID']}";
								//echo $sql."<br>";
								q($sql);
								
							}
							if ($row2["StockUnits"]==$t[1]*$qty) {
								// обновить количество и изменить статус 
								$sql="UPDATE Message57 SET StockUnits=0, status=3, special=0, new=0, Name='- {$row2['Name']}' WHERE Message_ID={$row2['Message_ID']}";
								//echo $sql."<br>";
								q($sql);
								// списать набор, если кол-во ножей в нем=0
								$sql="UPDATE Message57 SET StockUnits=0, status=3, special=0, new=0, Name=concat('- ',Name) WHERE Message_ID={$complect_id}";
								//echo $sql."<br>";
								q($sql);
							}
							// запись в лог о том, что списали текущую позицию
							$sqloff="";
							$sqloff="INSERT INTO Complects_off 
								(complect_id, item_id, qty, created, retail_id, order_id)
								VALUES
								({$complect_id}, {$row2['Message_ID']},".($t[1]*$qty).",'".date("Y-m-d H:i:s")."',{$retail_id},{$order_id})";
							//echo $sqloff."<br>";
							q($sqloff);
						}
					}
				}
			}
		}
		return 1;
	}

	// уменьшаем количество товара на складе. 
	// меняем статус, если необходимо
	function decreaseStockUnits($order_id,$item_id,$qty){
		$sql="SELECT * FROM Message57 WHERE Message_ID=".$item_id;
		$res1=q($sql);
		$ok=1;
		while ($row1 = mysql_fetch_assoc($res1)) {
			if ($row1["complect"]!="") {
				// списываем комплект
				$ok=$this->writeoffComplect($item_id,$row1["complect"],$qty,$retail_id=0,$order_id);	
			} else {
				if ($row1['StockUnits']<$qty) {
					// ошибка списания!
					$ok=0;
				} elseif ($row1['StockUnits']==$qty) {
					// списываем и обновляем название и статус товара
					$sql="UPDATE Message57 SET StockUnits=0, status=3, special=0, new=0, Name='- ".addslashes($row1['Name'])."' WHERE Message_ID={$item_id}";
					q($sql);						
				} else {
					// просто списываем
					$sql="UPDATE Message57 SET StockUnits=StockUnits-{$qty} WHERE Message_ID={$item_id}";
					q($sql);
				}
			}
			//echo $sql."<br>";
			if ($ok==1) {
				$sql="INSERT INTO Writeoff (order_id, item_id, qty, created) VALUES ({$order_id}, {$item_id}, {$qty}, '".date("Y-m-d H:i:s")."')";
				q($sql);
			}
		}
		
		$sql="UPDATE Message51 SET writeoff=".$ok." WHERE Message_ID=".$order_id;
		q($sql);
		//echo $sql."<br>";
		return $ok;
	}
    /**
     * сохранение содержимого заказа при его оформлении + оповещение менеджеров
     * + e-mail покупателю
     */
    function SaveOrder($order_id) {
        global $HTTP_HOST, $SUB_FOLDER, $HTTP_ROOT_PATH;

		//print_r($_POST);
        // system superior object
        $nc_core = nc_Core::get_object();

        if (!int($order_id) || !$this->CartCount())
            return false;
        // get cart contents:

        $this->OrderID = $order_id;

        $payment_method_info = $this->EligibleMethodsOf('payment', 1);
        $payment_method_info = $payment_method_info[$_POST["f_PaymentMethod"]];

        $delivery_method_info = $this->EligibleMethodsOf('delivery', 1);
        $delivery_method_info = $delivery_method_info[$_POST["f_DeliveryMethod"]];
		
		$utm = ""; // +OPE
		$href = "";
		if (!empty($_SESSION["UTM"])) {
			foreach ($_SESSION["UTM"] as $key => $value) {
				$utm.=$key ."=".$value.";";
			}
			$href = $_SESSION["HREF"];
			//error_log("utm:[".var_export($utm,true)."] ".var_export($href,true));
		}
		
		// просто сохраняем заказ. 
		q("UPDATE Message$this->order_table
            SET ContactName='".htmlspecialchars($_POST['f_ContactName'],ENT_QUOTES,"cp1251")."',
                Email='".htmlspecialchars($_POST['f_Email'],ENT_QUOTES,"cp1251")."',
                country='".htmlspecialchars($_POST['f_country'],ENT_QUOTES,"cp1251")."',
                Region='".htmlspecialchars($_POST['f_Region'],ENT_QUOTES,"cp1251")."',
                Town='".htmlspecialchars($_POST['f_Town'],ENT_QUOTES,"cp1251")."',
                PostIndex='".htmlspecialchars($_POST['f_PostIndex'],ENT_QUOTES,"cp1251")."',
                Street='".htmlspecialchars($_POST['f_Street'],ENT_QUOTES,"cp1251")."',
                House='".htmlspecialchars($_POST['f_House'],ENT_QUOTES,"cp1251")."',
                Flat='".htmlspecialchars($_POST['f_Flat'],ENT_QUOTES,"cp1251")."',
                Comments='".htmlspecialchars($_POST['f_Comments'],ENT_QUOTES,"cp1251")."',
                Phone='".htmlspecialchars($_POST['f_Phone'],ENT_QUOTES,"cp1251")."',
                mphone='".htmlspecialchars($_POST['f_mphone'],ENT_QUOTES,"cp1251")."',
				utm='".substr(htmlspecialchars($utm,ENT_QUOTES,"cp1251"),0,255)."',
				href='".substr(htmlspecialchars($href,ENT_QUOTES,"cp1251"),0,255)."'
          WHERE Message_ID=".$order_id);
		
		// проверяем с какого сайта идет заказ
        q("UPDATE Message$this->order_table
            SET OrderCurrency=$this->DefaultCurrencyID,
                PaymentCost='$payment_method_info[Sum]',
                DeliveryCost='$delivery_method_info[Sum]',
				FromWhere=".$GLOBALS[catalogue]."
          WHERE Message_ID='$order_id'");

		$this->Order["PaymentCost"] = $payment_method_info["Sum"];
        $this->Order["DeliveryCost"] = $delivery_method_info["Sum"];
		
		// доставка по почте
		if ($GLOBALS["f_DeliveryMethod"]==3) {
			$postcost=$this->Order["DeliveryCost"];
			$temp=$this->CartSum()-$postcost;
			//if ($_POST["f_PaymentMethod"]==7) { // наложенный платеж
				
				$sql="SELECT cost FROM Netshop_Postdeliverycost WHERE min<={$temp} AND max>={$temp}";
				$res1=q($sql);
				while ($row1 = mysql_fetch_assoc($res1)) {
					$postcost=$row1['cost'];
				}
				q("UPDATE Message{$this->order_table} 
					SET DeliveryCost='{$postcost}'
				  WHERE Message_ID='{$order_id}'");
				$this->Order["DeliveryCost"] = $postcost;
			//} else {
				// по почте с предоплатой отправляем бесплатно, если заказ дороже 5000
				/*if ($temp>4999) {
					$postcost=0;
				} else {
					$postcost=200;
				}
				q("UPDATE Message{$this->order_table} 
					SET DeliveryCost='{$postcost}'
				  WHERE Message_ID='{$order_id}'");
				$this->Order["DeliveryCost"] = $postcost; */
			//}
		}
		// курьер в пределах мкад для заказа больше 10 000 бесплатно
		/*if ($GLOBALS["f_DeliveryMethod"]==1) {
			$dlvcost=0;
			if (($this->CartSum()-$this->Order["DeliveryCost"])>9999) {
				$dlvcost=0;
				q("UPDATE Message{$this->order_table} 
					SET DeliveryCost='{$dlvcost}'
				  WHERE Message_ID='{$order_id}'");
				$this->Order["DeliveryCost"] = $dlvcost;
			} 
		} */
		// PickPoint сохранение пункта доставки
		$dlvcost=0;
		//if ($_POST["f_pickpoint_id"]) {
		if ($GLOBALS["f_DeliveryMethod"]==8) {
			// 26/09/2017 отменяем бесплатную доставку по указанию Красникова А.
			// 13/12/2017 Пичугин если оплата онлайн и заказ дороже 2тыс
			// 24/10/2018 Пичугин если оплата онлайн и стоимость больше 5000
			//if (($this->CartSum()-$this->Order["DeliveryCost"])>2499) { //&&(($GLOBALS["f_PaymentMethod"]==1)||($GLOBALS["f_PaymentMethod"]==5))) {
			if ((($this->CartSum()-$this->Order["DeliveryCost"])>4999) && ($GLOBALS["f_PaymentMethod"]==1)) {
				$dlvcost=0;
			} else {
				$dlvcost=$delivery_method_info["Sum"]*$_POST["f_pickpoint_coef"];	
			}
			$pp_coef = 1;
			if (isset($_POST["f_pickpoint_coef"])) {
				$pp_coef = $_POST["f_pickpoint_coef"];
			}
			$delivery_method_info["Sum"]=$dlvcost;
			$this->Order["DeliveryCost"] = $dlvcost;
			q("UPDATE Message{$this->order_table}
				SET DeliveryCost='{$dlvcost}',
				pickpoint_id='".htmlspecialchars($_POST['f_pickpoint_id'],ENT_QUOTES,"cp1251")."',
				pickpoint_address='".htmlspecialchars($_POST['f_pickpoint_address'],ENT_QUOTES,"cp1251")."',
				pickpoint_coef='".htmlspecialchars($pp_coef,ENT_QUOTES,"cp1251")."',
				pickpoint_zone='".htmlspecialchars($_POST['f_pickpoint_zone'],ENT_QUOTES,"cp1251")."'
			  WHERE Message_ID='{$order_id}'");
		}
		// CDEK
		if ($GLOBALS["f_DeliveryMethod"]==9) {
		$calc1 = new CalculatePriceDeliveryCdek();
			//Авторизация. Для получения логина/пароля (в т.ч. тестового) обратитесь к разработчикам СДЭК -->
			$calc1->setAuth('966b523203ee0dd09485f1af91c40bf3', 'a082f619378a85387c0cedc22dd1fd6b');
			//устанавливаем город-отправитель
			$calc1->setSenderCityId(44);
			
			//print_r($_SESSION);
			//print_r($_REQUEST);
			//устанавливаем город-получатель
			if ($_REQUEST['receiverCityId']!="") {
				$receiverCityId=intval($_REQUEST['receiverCityId']);
				
			} else {
				return false;
				// найти город по прошлому заказу
				//$sql="SELECT cdek_cityid FROM Message{$this->order_table} WHERE User_ID=".$this->Order['User_ID']." ORDER BY Message_ID DESC LIMIT 1,1";
				/*$sql="SELECT cdek_cityid FROM Message{$this->order_table} WHERE  Email='".htmlspecialchars($_POST['f_Email'],ENT_QUOTES,"cp1251")."' ORDER BY Message_ID DESC LIMIT 1,1";
				echo $sql."<br>";
				$res1=q($sql);
				while ($row1 = mysql_fetch_assoc($res1)) {
					$receiverCityId=$row1['cdek_cityid'];
				}*/
			}
			//echo $receiverCityId."<br>";
			$calc1->setReceiverCityId($receiverCityId);
			//устанавливаем тариф по умолчанию 
			$calc1->setTariffId('137');
			//устанавливаем режим доставки (склад-курьер)
			$calc1->setModeDeliveryId(3);
			//добавляем места в отправление
			$calc1->addGoodsItemByVolume(0.5,0.01);
			
			
			if ($calc1->calculate() === true) {
				// change cost
				$res = $calc1->getResult();
				//if ((($this->CartSum()-$this->Order["DeliveryCost"])>1999)&&(($GLOBALS["f_PaymentMethod"]==1)||($GLOBALS["f_PaymentMethod"]==5))) {
				//	$dlvcost=0;
				//} else {	
				//print_r($res);
					$dlvcost=$res['result']['price'];
				//}
				$delivery_method_info["Sum"]=$dlvcost;
				// 2018/10/24 пичугин
				if ((($this->CartSum()-$this->Order["DeliveryCost"])>4999) && ($GLOBALS["f_PaymentMethod"]==1)) {
					$dlvcost = 0;
				}
				$this->Order["DeliveryCost"] = $dlvcost;
				q("UPDATE Message{$this->order_table}
					SET DeliveryCost='{$dlvcost}',
					cdek_cityid='{$receiverCityId}',
					cdek_perioddlv='".htmlspecialchars($res['result']['deliveryPeriodMin'],ENT_QUOTES,"cp1251")."-".htmlspecialchars($res['result']['deliveryPeriodMax'],ENT_QUOTES,"cp1251")."'
				  WHERE Message_ID='{$order_id}'");
				
				
				/*echo 'Цена доставки: ' . $res['result']['price'] . 'руб.<br />';
				echo 'Срок доставки: ' . $res['result']['deliveryPeriodMin'] . '-' . 
										 $res['result']['deliveryPeriodMax'] . ' дн.<br />';
				echo 'Планируемая дата доставки: c ' . $res['result']['deliveryDateMin'] . ' по ' . $res['result']['deliveryDateMax'] . '.<br />';
				echo 'id тарифа, по которому произведён расчёт: ' . $res['result']['tariffId'] . '.<br />';
				if(array_key_exists('cashOnDelivery', $res['result'])) {
					echo 'Ограничение оплаты наличными, от (руб): ' . $res['result']['cashOnDelivery'] . '.<br />';
				}*/
			} else {
				$err = $calc1->getError();
				//var_dump($err);
				// send letter to elena@best-hosting.ru
				$mailer1 = new CMIMEMail();
				$mailer1->mailbody(strip_tags($err['code']." ".$err['text'])); // plain/text email
				$mailer1->send("elena@best-hosting.ru", $this->MailFrom, $this->MailFrom, "Error CDEK", $this->ShopName);
				/*
				if( isset($err['error']) && !empty($err) ) {
					var_dump($err);
					foreach($err['error'] as $e) {
						echo 'Код ошибки: ' . $e['code'] . '.<br />';
						echo 'Текст ошибки: ' . $e['text'] . '.<br />';
					}
				}*/
			}
			
		}
       
		// 2018/10/24 if payment =1 (online) then deliverycost=0;
		// except courier in moscow = 1
		if (($GLOBALS["f_PaymentMethod"] == 1) && ($GLOBALS["f_DeliveryMethod"]!=1) && ($GLOBALS["f_DeliveryMethod"]!=7) && (($this->CartSum()-$this->Order["DeliveryCost"])>4999)) {
			$this->Order["DeliveryCost"] = 0;
			q("UPDATE Message{$this->order_table}
						SET DeliveryCost=0
					WHERE Message_ID=".$order_id);	
		}

        // get cart contents and save it
        if (!$this->CartContents)
            $this->CartContents();

		$bodycart="";
		
        foreach ($this->CartContents as $row) {
            q("INSERT INTO Netshop_OrderGoods
                   SET Order_ID=".$order_id.",
                       Item_Type='".$row['Class_ID']."',
                       Item_ID='".$row['Message_ID']."',
                       Qty='".$row['Qty']."',
                       OriginalPrice='".$row['OriginalPrice']."',
                       ItemPrice='".$row['ItemPrice']."'
                  ");
			// decrease item countstock
			$ok=$this->decreaseStockUnits($order_id,$row["Message_ID"],$row['Qty']);
			$bodycart.="#".$row["Message_ID"]." [".$row['ItemID']."] ".$row['Qty']." ".(($ok==1) ? "списано" : "-")."\n";
            // save discount info for item
            foreach ((array) $row["Discounts"] as $discount) {
                q("INSERT INTO Netshop_OrderDiscounts
                      SET Order_ID='$order_id',
                          Item_Type='$row[Class_ID]',
                          Item_ID='$row[Message_ID]',
                          Discount_ID='$discount[Discount_ID]',
                          Discount_Name='" . mysql_real_escape_string($discount["Name"]) . "',
                          Discount_Description='" . mysql_real_escape_string($discount["Description"]) . "',
                          PriceMinimum='" . (int) $discount["PriceMinimum"] . "',
                          Discount_Sum='$discount[Sum]'
                ");
            }
        }

        // save discount info for the cart
        foreach ((array) $this->CartDiscounts as $discount) {
            q("INSERT INTO Netshop_OrderDiscounts
                   SET Order_ID='$order_id',
                       Item_Type=0,
                       Item_ID=0,
                       Discount_ID='$discount[Discount_ID]',
                       Discount_Name='" . mysql_real_escape_string($discount["Name"]) . "',
                       Discount_Description='" . mysql_real_escape_string($discount["Description"]) . "',
                       PriceMinimum='" . (int) $discount["PriceMinimum"] . "',
                       Discount_Sum='$discount[Sum]'
            ");
        }

        // SEND EMAILS ======================================================
        // check if smtp is configured (windows/demo)
        if (!(ini_get("SMTP") == 'localhost' && !ini_get("sendmail_path"))) {
            include_once("$GLOBALS[ADMIN_FOLDER]/mail.inc.php");
//         include_once('Mail.php');

             //$header = sprintf(NETCAT_MODULE_NETSHOP_EMAIL_TO_MANAGER_HEADER,decode_host($GLOBALS["DOMAIN_NAME"]));
			if ($GLOBALS["DOMAIN_NAME"]=="xn--e1acecguhljau.xn--p1ai") {
			    $header = sprintf(NETCAT_MODULE_NETSHOP_EMAIL_TO_MANAGER_HEADER,"русскиеножи.рф");
			} else {
			    $header = sprintf(NETCAT_MODULE_NETSHOP_EMAIL_TO_MANAGER_HEADER,$GLOBALS["DOMAIN_NAME"]);
			}
            
            foreach ((array) $this->SendMails as $to => $row) {
                $body = "";
                $sum = 0;

                $res = q("SELECT Field_Name, Description
                      FROM Field
                      WHERE Class_ID=$this->order_table
                        AND TypeOfEdit_ID=1
                      ORDER BY Priority");
                while ($row2 = mysql_fetch_assoc($res)) {
                    $body .= "$row2[Description]: ";

                    if ($row2["Field_Name"] == "DeliveryMethod") {
                        $body .= $delivery_method_info["Name"];
                    } else if ($row2["Field_Name"] == "PaymentMethod") {
                        $body .= $payment_method_info["Name"];
                    } elseif ($row2["Field_Name"] == "DeliveryCost") {
                        if ($delivery_method_info["Sum"]) {
							$body .= $this->FormatCurrency($delivery_method_info["Sum"], "", true);
                        }
                    } else {
                        $body .= $GLOBALS["f_$row2[Field_Name]"];
                    }

                    $body .= "\n";
                }
				 // Elen 05.02.2013 --------------------------------------------------------------------------------
				// $f_PostIndex, $f_country, $f_Region, $f_Town, $f_Street, $f_House, $f_Flat
				$address.=((strlen($_POST["f_PostIndex"])>0) ? htmlspecialchars($_POST["f_PostIndex"],ENT_QUOTES,"cp1251").", " : "");
				$temp = "";
				$town=trim($_POST["f_Town"]);
				$townarr=explode(",",$town);
				//echo count($townarr)."<br>".$townarr[count($townarr)-1]."<br>";
				
				//print_r($townarr);
				if (count($townarr)>1) {
					for ($j=(count($townarr)-1);$j>=0;$j--) {
						if (trim($townarr[$j])!=trim($townarr[$j+1])) {
							//echo "Россия".htmlspecialchars($townarr[$j],ENT_QUOTES,"cp1251");
							$temp.=((trim(htmlspecialchars($townarr[$j],ENT_QUOTES,"cp1251"))=="Россия") ? "" : $townarr[$j].", ");	
						}						
					}
				} else {
					$temp=((strlen($_POST['f_Region'])>1) ? trim($_POST['f_Region']).", " : "").trim($town).", ";
				}
				$address.=htmlspecialchars($temp,ENT_QUOTES,"cp1251");
				$address.=((strlen($_POST["f_Street"])>0) ? "ул. ".htmlspecialchars($_POST["f_Street"],ENT_QUOTES,"cp1251") : "").", ";
				$address.=((strlen($_POST["f_House"])>0) ? "д. ".htmlspecialchars($_POST["f_House"],ENT_QUOTES,"cp1251") : "").", ";
				$address.=(strlen($_POST["f_Flat"])>0) ? "кв. ".htmlspecialchars($_POST["f_Flat"],ENT_QUOTES,"cp1251") : "";
				/*$address.= //((strlen($_POST["f_PostIndex"])>0) ? htmlspecialchars($_POST["f_PostIndex"],ENT_QUOTES,"cp1251").", " : "").
				//(strlen($GLOBALS["f_country"])>0) ? $address.=$GLOBALS["f_country"].", " : $address.="";
				//((strlen($_POST["f_Region"])>0) ? htmlspecialchars($_POST["f_Region"],ENT_QUOTES,"cp1251").", "                 : "").
				((strlen($_POST["f_Town"])>0)   ? htmlspecialchars($_POST["f_Town"],ENT_QUOTES,"cp1251").", "                   : "").
				((strlen($_POST["f_Street"])>0) ? "ул. ".htmlspecialchars($_POST["f_Street"],ENT_QUOTES,"cp1251").", "          : "").
				((strlen($_POST["f_House"])>0)  ? "д. ".htmlspecialchars($_POST["f_House"],ENT_QUOTES,"cp1251").", "            : "").
				((strlen($_POST["f_Flat"])>0)   ? "кв. ".htmlspecialchars($_POST["f_Flat"],ENT_QUOTES,"cp1251")." "             : "");*/

				// save full address
				q("UPDATE Message51 SET Address='{$address}' WHERE Message_ID='{$order_id}'");
				$body.="Полный адрес доставки: ".htmlspecialchars_decode($address)."\n\n";
	
				if ($_POST["f_pickpoint_id"]) {
					$body.="Терминал PickPoint: ".$_POST["f_pickpoint_id"]."\n";
					$body.="Адрес доставки: ".$_POST["f_pickpoint_address"]."\n";
					$body.="Тарифная зона: ".$_POST["f_pickpoint_zone"]."\n";
					$body.="Коэффициент: ".$_POST["f_pickpoint_coef"]."\n";
				}
                $body .= str_repeat("-", 75) . "\n";

                $order_mail_subject_lenght = $nc_core->modules->get_vars("netshop", "ORDER_MAIL_NAME_LENGHT") ? $nc_core->modules->get_vars("netshop", "ORDER_MAIL_NAME_LENGHT") : 35;

                foreach ($row as $i) {
                    $item_id = ($this->CartContents[$i]["ItemID"] ? "[" . $this->CartContents[$i]["ItemID"] . "]" : "");

                    $body .= ( $item_id ? str_pad($item_id, 15) : "") .
                            str_pad(nc_substr(strip_tags($this->CartContents[$i]["Name"]), 0, $order_mail_subject_lenght), $order_mail_subject_lenght) . "  " .
                            str_pad($this->CartContents[$i]["Qty"] . " " . $this->CartContents[$i]["Units"], 10, " ") .
                            $this->FormatCurrency($this->CartContents[$i]["TotalPrice"], "", true) .
                            "\n";
                    $sum += $this->CartContents[$i]["TotalPrice"];
                }

                $body .= str_pad('Доставка', $order_mail_subject_lenght) . "  " .
                $this->FormatCurrency($this->Order["DeliveryCost"], "", true) .
                "\n";
               
				
                $body .= str_repeat("-", 75) . "\n" .
                        NETCAT_MODULE_NETSHOP_SUM . ": " .
                        $this->FormatCurrency($this->CartSum(), "", true) . "\n\n# $this->OrderID";
                // make link for order
                // links to order template
                $order_tpl = GetTemplateByType($this->order_table, $this->shop_id, "c.Subdivision_ID, c.Sub_Class_ID");
                if ($order_tpl) {
                    //$body .= "\nhttp://russian-knife.ru". $SUB_FOLDER . $HTTP_ROOT_PATH . "message.php?catalogue=1&sub=$order_tpl[Subdivision_ID]&cc=$order_tpl[Sub_Class_ID]&message=$this->OrderID&curPos=0\n";
					$body .= "\nhttp://russian-knife.ru". $SUB_FOLDER . $HTTP_ROOT_PATH . "message.php?catalogue=1&sub=57&cc=53&message={$this->OrderID}&curPos=0\n";
				}

                $mailer = new CMIMEMail();
                $mailer->mailbody(strip_tags($body));
                //$mailer->send($to, $this->MailFrom, $this->MailFrom, $header, $this->ShopName);
                //$mailer->send("admin@russian-knife.ru", $this->MailFrom, $this->MailFrom, $header, $this->ShopName);
                //$mailer->send("elena@best-hosting.ru", $this->MailFrom, $this->MailFrom, $header, $this->ShopName);
				
				$body.="\n\n".$decrstr;
				$body.="\n\n".$bodycart;
				$mailer->mailbody(strip_tags($body));
				$mailer->send("admin@russian-knife.ru", $this->MailFrom, $this->MailFrom, $header, $this->ShopName);
                $mailer->send("elena@best-hosting.ru", $this->MailFrom, $this->MailFrom, $header, $this->ShopName);
            }

            // EMAIL CUSTOMER
			//57 = $this->shop_id
			//53 = $this->email_template_table
			//if ($this->email_template_table) {
                $email_template = row("SELECT * FROM Message53
                                WHERE Subdivision_ID=57
                                  AND Keyword='OrderConfirmation'");

                if ($email_template && $_POST["f_Email"]) { // we've got what and where to mail
                    // prepare variables
                    $vars = array();

                    $vars["ORDER_ID"] = $this->OrderID;

                    // CUSTOMER
                    foreach ($_POST as $var => $value) {
                        if (nc_substr($var, 0, 2) == "f_") { // properties of the ORDER just posted
                            if (!is_array($value))
                                $vars[("CUSTOMER_" . strtoupper(nc_substr($var, 2)))] = $value;
                        }
                    }

                    // SHOP
                    foreach ($this as $var => $value) {
                        if (!is_array($value) && !is_object($value))
                            $vars["SHOP_" . strtoupper($var)] = $value;
                    }

                    // CART: contents, discounts, delivery, payment, count, sum
					$dtime=date("Y-m-d H:i:s");
                    /*
					// 29.01.2016 Elen create a cart table for html letter  
					$vars["CART_CONTENTS"] .= "
					<div style='color:#333333;font-family:Tahoma;font-size:12px;'>Состав заказа:</div>
					<table cellpadding='2' cellspacing='0' border='1' width='90%' style='margin:0 auto;'>";
					foreach ($this->CartContents as $item) {
                        $item_id = ($item["ItemID"] ? "[$item[ItemID]]" : "");

                        $vars["CART_CONTENTS"] .= "<tr>
								<td><div style='color:#333333;font-family:Tahoma;font-size:12px;'>".($item_id ? str_pad($item_id, 15) : "")." ".$item["Name"]."</div></td>
								<td><div style='color:#333333;font-family:Tahoma;font-size:12px;'>".$this->FormatCurrency($item["ItemPrice"], "", true)."</div></td>
                                <td><div style='color:#333333;font-family:Tahoma;font-size:12px;'>".$item[Qty]." ".$item[Units]."</div></td>
                                <td><div style='color:#333333;font-family:Tahoma;font-size:12px;'>".$this->FormatCurrency($item["TotalPrice"], "", true)."</div></td>
                            </tr>";
								
						// 13.09.2013 Elen
						// Create history for $this->OrderID order
						//print_r($item);
						q("INSERT INTO Netshop_OrderHistory (Order_ID, Item_Type, Item_ID, Qty, OriginalPrice, ItemPrice, created,orderstatus_id) VALUES
							({$this->OrderID}, 57, {$item["Message_ID"]}, {$item[Qty]}, {$item["ItemPrice"]}, {$item["ItemPrice"]}, '{$dtime}',5)");
                    }
					$vars["CART_CONTENTS"].="</table>";  */
                    
					$vars["CART_CONTENTS"] = str_repeat("-", 78) . "\n";
                    foreach ($this->CartContents as $item) {
                        $item_id = ($item["ItemID"] ? "[$item[ItemID]]" : "");

                        $vars["CART_CONTENTS"] .= ( $item_id ? str_pad($item_id, 15) : "") .
                                str_pad(nc_substr($item["Name"], 0, $order_mail_subject_lenght), 30) . "  " .
                                $this->FormatCurrency($item["ItemPrice"], "", true) .
                                " x $item[Qty] $item[Units] = " .
                                $this->FormatCurrency($item["TotalPrice"], "", true) .
                                "\n";
								
						// 13.09.2013 Elen
						// Create history for $this->OrderID order
						//print_r($item);
						q("INSERT INTO Netshop_OrderHistory (Order_ID, Item_Type, Item_ID, Qty, OriginalPrice, ItemPrice, created,orderstatus_id) VALUES
							({$this->OrderID}, 57, {$item["Message_ID"]}, {$item[Qty]}, {$item["ItemPrice"]}, {$item["ItemPrice"]}, '{$dtime}',5)");
                    }

                    $vars["CART_CONTENTS"] .= str_repeat("-", 78) . "\n";

                    // CART: discounts
                    foreach ((array) $this->CartDiscounts as $discount) {
                        $vars["CART_DISCOUNTS"] .= "* $discount[Name]: " .
                                $this->FormatCurrency($discount["Sum"], "", 1) . "\n";
                    }

                    // CART: delivery
                    //if ($delivery_method_info["Sum"]) {
                        //$vars["CART_DELIVERY"] = "\n" . iconv("cp1251", "UTF-8", NETCAT_MODULE_NETSHOP_DELIVERY) .
                        $vars["CART_DELIVERY"] = "\n" . NETCAT_MODULE_NETSHOP_DELIVERY .
                                " - $delivery_method_info[Name]: " .
                                $this->FormatCurrency($this->Order["DeliveryCost"], "", true);
                    //}

                    // CART: payment
                    //if ($payment_method_info["Sum"]) {
                        $vars["CART_PAYMENT"] = "\n" . NETCAT_MODULE_NETSHOP_PAYMENT .
                                " - $payment_method_info[Name]: " .
                                $this->FormatCurrency($payment_method_info["Sum"], "", true);
                    //}

                    // CART: count
                    $vars["CART_COUNT"] = $GLOBALS["NETSHOP"]["Netshop_ItemCount"] . " " .
                            netshop_language_count($GLOBALS["NETSHOP"]["Netshop_ItemCount"], NETCAT_MODULE_NETSHOP_ITEM_FORMS);

                    // CART: sum
                    $vars["CART_SUM"] = $this->FormatCurrency($this->CartSum(), "", true);

					$vars["DT_DELIVERY"] = "";
					$date = new DateTime(); 
					switch ((int)$GLOBALS["f_DeliveryMethod"]) {
						case 1:
							$date = $date->modify('+4 days')->format('d.m.Y');
							$vars["DT_DELIVERY"] = "Ваш заказ будет доставлен в течение 4 дней до ".$date; 
							break;
						case 2:
							$date = $date->modify('+3 days')->format('d.m.Y');
							$vars["DT_DELIVERY"] = "Вы можете забрать товар из магазина в течение 3 дней до ".$date; 
							break;
						case 3:
							$date = $date->modify('+14 days')->format('d.m.Y');
							$vars["DT_DELIVERY"] = "Ваш заказ будет доставлен в течение 14 дней до ".$date; 
							break;
						default:
							$date = $date->modify('+7 days')->format('d.m.Y');
							$vars["DT_DELIVERY"] = "Ваш заказ будет доставлен в течение 7 дней до ".$date;
							break;
					}
                    //foreach (array("LetterTitle", "Body") as $what) {
                    //    nc_preg_match_all("/%([\w]+)%/", $email_template[$what], $regs);
                    //    foreach ($regs[1] as $var) {
                    //        $email_template[$what] = str_replace("%$var%", $vars[strtoupper($var)], $email_template[$what]);
                    //    }
                    //}
					
					foreach (array("Title", "Body") as $what) {
                        nc_preg_match_all("/%([\w]+)%/", $email_template[$what], $regs);
                        foreach ($regs[1] as $var) {
                            //$email_template[$what] = str_replace("%$var%", iconv("cp1251", "UTF-8", $vars[strtoupper($var)]), $email_template[$what]);
                            $email_template[$what] = str_replace("%$var%", $vars[strtoupper($var)], $email_template[$what]);
                        }
                    }

                    $mailer = new CMIMEMail();
                    $mailer->mailbody(strip_tags($email_template["Body"])); // plain/text email
                    //$mailer->mailbody("",$email_template["Body"]);
                    //$mailer->send($GLOBALS["f_Email"], $this->MailFrom, $this->MailFrom, $email_template["LetterTitle"], $this->ShopName);
                    $mailer->send($GLOBALS["f_Email"], $this->MailFrom, $this->MailFrom, $email_template["Title"], $this->ShopName);
                    //$mailer->send("elena@best-hosting.ru", $this->MailFrom, $this->MailFrom, $email_template["Title"], $this->ShopName);
					$mailer->send("admin@russian-knife.ru", $this->MailFrom, $this->MailFrom, $email_template["Title"], $this->ShopName);
                }
				
            //}
        } // of "send emails"
        // ------------------------------------------------------------------

        if ($payment_method_info["Interface"]) {
            $this->Payment($payment_method_info["Interface"], 'create_bill');
        }

        $this->ClearCartContents();

        return true;
    }
    
    /**
     * Загрузить заказ
     */
    function LoadDisplayOrder($order_id) {
        if (!int($order_id))
            return false;
        $items = $item_types = array();
        $orders_classID = $GLOBALS['classID'];
        $Cart = array(
            'order' => array(),
            'items' => array(),
            'total' => 0
            );
        
        $res = q("SELECT * FROM `Message$orders_classID` WHERE `Message_ID`='$order_id'");
        while ($row = mysql_fetch_assoc($res)) {            
            $Cart['order'] = $row;
        }

        $res = q("SELECT * FROM `Netshop_OrderGoods` WHERE `Order_ID`='$order_id'");
        while ($row = mysql_fetch_assoc($res)) {            
            $items[$row['Item_Type']][] = $row;
            $item_types[$row['Item_Type']][] = $row['Item_ID'];
        }

        foreach ($items as $item_id => $item) {
            $res = q("SELECT m.*,   
                IF(m.Keyword IS NULL OR m.Keyword = '', CONCAT(u.Hidden_URL, s.EnglishName, '_', m.Message_ID, '.html'),
                                      CONCAT(u.Hidden_URL, m.Keyword, '.html')) as URL
                FROM (Message$item_id as m,
                 Subdivision as u,
                 Sub_Class as s)
                WHERE m.Message_ID IN (".join($item_types[$item_id], ', ').")
                AND s.Sub_Class_ID = m.Sub_Class_ID
                AND u.Subdivision_ID = m.Subdivision_ID
                ");

            while ($row = mysql_fetch_assoc($res)) { 
                $Cart['items'][] = $row;
                $Cart['total'] += $row['Price'];
            }
        }

        return $Cart;
    }
    
    /**
     * Загрузить заказ
     */
    function LoadOrder($order_id) {
        if (!int($order_id))
            return false;
        unset($this->Cart);
        $this->CartDiscountSum = 0;
        $this->CartDiscounts = array();
        $this->TotalDiscountSum = 0;
        $this->CartSumBeforeDiscounts = 0;

        $res = q("SELECT * FROM Netshop_OrderGoods WHERE Order_ID=$order_id");

        while ($row = mysql_fetch_assoc($res)) {
            $this->Cart["goods"][$row["Item_Type"]][$row["Item_ID"]] =
                    array("Qty" => $row["Qty"],
                        "OriginalPrice" => $row["OriginalPrice"],
                        "ItemPrice" => $row["ItemPrice"]);


            $this->CartSumBeforeDiscounts += $row["OriginalPrice"] * $row["Qty"];
        }

        $res = q("SELECT * FROM Netshop_OrderDiscounts WHERE Order_ID=$order_id");

        while ($row = mysql_fetch_assoc($res)) {
            $this->TotalDiscountSum += $row["Discount_Sum"];

            $discount = array("Sum" => $row["Discount_Sum"],
                "SumF" => $this->FormatCurrency($row["Discount_Sum"]),
                "Discount_ID" => $row["Discount_ID"],
                "Name" => $row["Discount_Name"],
                "Description" => $row["Discount_Description"],
                "PriceMinimum" => $row["PriceMinimum"]
            );

            if ($row["Item_ID"]) {
                $this->Cart["goods"][$row["Item_Type"]][$row["Item_ID"]]["Discounts"][] = $discount;
            } else { // cart discount
                $this->CartDiscounts[] = $discount;
                $this->CartDiscountSum += $row["Discount_Sum"];
            }
        }

        // order properties
        $this->Order = row("SELECT * FROM Message$this->order_table WHERE Message_ID=$order_id");
        $this->OrderID = $order_id;

        $this->CartContents();
    }

    /**
     * Получить параметр $setting раздела магазина по type/id товара или sub_id
     */
    function GetDepartmentSetting($setting, $goods_type_id = "", $goods_id = "", $sub_id = "") {
        global $db;
        if (!$sub_id && int($goods_type_id) && int($goods_id))
            $sub_id = value1("SELECT Subdivision_ID
                             FROM Message$goods_type_id as m
                             WHERE Message_ID = $goods_id");

        $setting = $db->escape($setting);
        $sub_id = intval($sub_id);
        if (!$sub_id)
            return false;

        // (для кэширования) сюда мы положим ID, для которых будет работать найденное значение
        $sections = array();

        do {
            if ($this->DepartmentSettings[$sub_id][$setting]) {
                return $this->DepartmentSettings[$row["Subdivision_ID"]][$setting];
            }

            $row = row("SELECT m.`" . $setting . "`, s.Parent_Sub_ID
                     FROM Subdivision as s
                       LEFT JOIN `Message" . $this->department_table . "` as m
                            ON (s.Subdivision_ID=m.Subdivision_ID)
                     WHERE s.Subdivision_ID='" . $sub_id . "'");

            $sections[] = $row["Subdivision_ID"];
            $value = $row[$setting];

            if ($value) {
                break;
            } // есть значенье! stop
            $sub_id = $row["Parent_Sub_ID"]; // следующий: родитель
        } while ($sub_id);

        // defaults to shop setting
        if (!$value)
            $value = $this->$setting;

        // fill cache
        foreach ($sections as $id) {
            $this->DepartmentSettings[$id][$setting] = $value;
        }

        return $value;
    }

    // =======================================================================

    /**
     * оплата
     */
    function Payment($system, $stage, $to_string = false) {
        global $nc_core;

        if (!preg_match("/^\w+$/", $system)) {
            trigger_error("Incorrect " . htmlspecialchars($system), E_USER_ERROR);
            return false;
        }

        $file_path = $nc_core->MODULE_FOLDER . "netshop/payment/" . $system . ".php";

        if (!file_exists($file_path)) {
            trigger_error(htmlspecialchars($system) . " NOT FOUND", E_USER_ERROR);
            return false;
        }

        include_once($file_path);
        $payment = 'Payment_' . $system;
        $payment = new $payment($this);
        if ($payment) {
            return $payment->$stage($to_string);
        }
    }

    function PrintCart() {
        global $SUB_FOLDER, $HTTP_ROOT_PATH, $HTTP_HOST;
        if (!$this->CartCount()) {
            return NETCAT_MODULE_NETSHOP_CART_EMPTY;
        }

		$makeorder=1; // разрешаем сделать заказ
		
        $has_item_discounts = ($this->TotalDiscountSum != $this->CartDiscountSum);

        $ret = "<form method=post action='" . $SUB_FOLDER . $HTTP_ROOT_PATH . "modules/netshop/post.php' class=cart_contents id=netshop_cart_contents>
        <input type=hidden name=redirect_url value='$_SERVER[REQUEST_URI]'>";

/*        if ($has_item_discounts) {
            $ret .= "
<script>
var ns_track_x, ns_track_y; // coordinates of the mouse
var ns_discount_shown = false;

function netshop_show_discount(discount_names_array, full_price, final_price)
{
   var div = document.getElementById('netshop_discount_div'),
       txt = '" . NETCAT_MODULE_NETSHOP_APPLIED_DISCOUNTS . "<p>';

   for (var i in discount_names_array)
   {
      txt += '&mdash; '+discount_names_array[i] + '<br>';
   }

   txt += '</p>" . NETCAT_MODULE_NETSHOP_PRICE_WITHOUT_DISCOUNT . ": '+full_price
        + '<br>" . NETCAT_MODULE_NETSHOP_PRICE_WITH_DISCOUNT . ": '+final_price;

   // snap to cursor pos
   div.style.top = ns_track_y + 5 + 'px';
   div.style.left= ns_track_x + 5 + 'px';
   ns_discount_shown = true;

   div.innerHTML = txt;
   div.style.display = '';
}

function netshop_hide_discount()
{
   document.getElementById('netshop_discount_div').style.display = 'none';
   ns_discount_shown = false;
}

function netshop_track_mouse(event)
{
   if (!event) event = window.event; // MSIE
   ns_track_x = (event.x != undefined) ? event.x  + document.body.scrollLeft : event.pageX;
   ns_track_y = (event.y != undefined) ? event.y  + document.body.scrollTop  : event.pageY;

   if (ns_discount_shown)
   {
      var div = document.getElementById('netshop_discount_div');
      div.style.top = ns_track_y + 5 + 'px';
      div.style.left= ns_track_x + 5 + 'px';
   }
}
</script>
<div id=netshop_discount_div style='display:none; position: absolute'></div>"; 
        } */

        $ret .= "<table border=0 cellspacing=0 cellpadding=0 width=100%>
         <tr>
          <th class=name width=35%>" . NETCAT_MODULE_NETSHOP_ITEM . "</th>" .
                ($has_item_discounts ? "<th>" . NETCAT_MODULE_NETSHOP_DISCOUNT . "</th>" : "") .
                "<th>" . NETCAT_MODULE_NETSHOP_ITEM_PRICE . "</th>" .
                "<th width=10%>" . NETCAT_MODULE_NETSHOP_QTY . "</th>" .
                "<th>" . NETCAT_MODULE_NETSHOP_COST . "</th>" .
                "<th>" . NETCAT_MODULE_NETSHOP_ITEM_DELETE . "</th></tr>";

        $i = 0;
        foreach ($this->CartContents as $row) {
            $ret .= "<tr class=" . (++$i % 2 ? "odd" : "even") . " align=center>
          <td class=name>[{$row['ItemID']}] <a href='$row[URL]' target=_blank>$row[Name]</a>";

            if ($has_item_discounts) {
                if ($row["OriginalPrice"] - $row["ItemPrice"]) { // has discount
                    $js_discount_names = array();
                    foreach ($row["Discounts"] as $discount) {
                        $js_discount_names[] = addcslashes($discount["Name"], "'\n");
                    }

                    $js_discount_names = "['" . join("', '", $js_discount_names) . "']";

                    /*$ret .= "<td><a href='javascript:void(0)' " .
                            "onmouseover=\"netshop_show_discount($js_discount_names, '$row[OriginalPriceF]', '$row[ItemPriceF]')\" " .
                            "onmouseout='netshop_hide_discount()'
							title=\"{$discount["Name"]}\">" .
                            $this->FormatCurrency(($row["OriginalPrice"] - $row["ItemPrice"])*$row["Qty"]) .
                            "</a></td>";*/
					$ret .= "<td><a href='#' title=\"{$discount["Name"]}\">" .
                            $this->FormatCurrency(($row["OriginalPrice"] - $row["ItemPrice"])*$row["Qty"]) .
                            "</a></td>";
                } else {
                    $ret .= "<td>&mdash;</td>";
                }
            }
			$ret .= "<td>$row[ItemPriceF]</td>
                  <td class=qty><input type=text size=2 name='cart$row[RowID]' value='$row[Qty]'> $row[Units] "
				  .(($row['Qty']>$row['StockUnits']) ? "<br><b>В наличии только {$row['StockUnits']}&nbsp;{$row[Units]}</b>" : "").
				  "<!--br> в наличии {$row['StockUnits']} {$row[Units]}--></td>
                  <td>$row[TotalPriceF]</td>
                  <td><input type=checkbox name='cart$row[RowID]' value=-1></td>
                 </tr>";
			if ($row['Qty']>$row['StockUnits']) {
				$makeorder=0;
			}
        }

        if ($this->CartDiscounts) {
            foreach ($this->CartDiscounts as $discount) {
                $ret .= "<tr align=center class=cart_discount><td colspan=" .
                        ($has_item_discounts ? 4 : 3) . " class=name>
                     <b>$discount[Name]</b>" .
                        ($discount["Description"] ? "<br>$discount[Description]" : "") .
                        "</td><td>" . ($discount["Sum"] > 0 ? "-" : "") . "$discount[SumF]</td>
                     <td>&nbsp;</td></tr>\n";
            }
        }

        $ret .= "<tr align=center class=totals><td colspan=" .
                ($has_item_discounts ? 4 : 3) . " class=name>" . NETCAT_MODULE_NETSHOP_SUM . "</td><td>" .
                ($this->FormatCurrency($this->CartSum())) . "</td><td>&nbsp;</td></tr>";

        if (ini_get("session.use_trans_sid")) {
            $sname = session_name();
            $sid = "?$sname=$GLOBALS[$sname]";
        } else {
            $sid = "";
        }

        $ret .= "</table>
       <div class=cart_buttons>
         <input type=submit value='" . NETCAT_MODULE_NETSHOP_REFRESH . "'>
         <input type=button ".(($makeorder==0) ? "disabled" : "")."
          onclick='window.location=\"http://" . $HTTP_HOST . "{$GLOBALS[NETSHOP][Netshop_OrderURL]}$sid\"'
          value='" . NETCAT_MODULE_NETSHOP_CART_CHECKOUT . "'>
         <noscript>{$GLOBALS[NETSHOP][Netshop_OrderLink]}</noscript>
       </div>
      </form>";

        if ($has_item_discounts) {
            $ret .= "<script>document.getElementById('netshop_cart_contents').onmousemove = netshop_track_mouse;</script>";
        }

		//$ret.="<br><br><img src='/images/MC_brand_103x65.png' height='33'>&nbsp;&nbsp;";
		//$ret.="<img src='/images/VISA.png'>&nbsp;&nbsp;";
		//$ret.="<img src='/images/SDM.gif'>";
		
        return $ret;
    }
	
	// просмотр корзины после оформления заказа
	function PrintCartView($order_id) {
        global $SUB_FOLDER, $HTTP_ROOT_PATH, $HTTP_HOST;
		
		$this->LoadOrder($order_id);

		$res = q("SELECT * FROM Message51 WHERE Message_ID=$order_id");
		$deliverycost=0;
        while ($row = mysql_fetch_assoc($res)) {
			$deliverycost=$row["DeliveryCost"];
        }
		
        $has_item_discounts = ($this->TotalDiscountSum != $this->CartDiscountSum);

        $ret="<br><br><p class='hh3'><b>Ваш заказ:</b></p>";
        $ret .= "<table border='0' cellspacing='0' cellpadding='0' width='100%'>
		<tr>
			<th style='width:60%;font-size:80%;'>" . NETCAT_MODULE_NETSHOP_ITEM . "</th>" .
            ($has_item_discounts ? "<th style='font-size:80%;'>" . NETCAT_MODULE_NETSHOP_DISCOUNT . "</th>" : "") .
            "<th style='width:10%;font-size:80%;'>" . NETCAT_MODULE_NETSHOP_ITEM_PRICE . "</th>" .
            "<th style='width:10%;font-size:80%;'>" . NETCAT_MODULE_NETSHOP_QTY . "</th>" .
            "<th style='width:10%;font-size:80%;'>" . NETCAT_MODULE_NETSHOP_COST . "</th></tr>";

        $i = 0;
        foreach ($this->CartContents as $row) {
            $ret .= "<tr>
			<td style='border-bottom: 1px solid #DBDBDB; padding: 5px;'>[{$row['ItemID']}] <a href='$row[URL]' target=_blank>$row[Name]</a>";

            if ($has_item_discounts) {
                if ($row["OriginalPrice"] - $row["ItemPrice"]) { // has discount
                    $js_discount_names = array();
                    foreach ($row["Discounts"] as $discount) {
                        $js_discount_names[] = addcslashes($discount["Name"], "'\n");
                    }

                    $js_discount_names = "['" . join("', '", $js_discount_names) . "']";

					$ret .= "<td style='text-align:center;border-bottom: 1px solid #DBDBDB; padding: 5px;'><a href='#' title=\"{$discount["Name"]}\">" .
                            $this->FormatCurrency(($row["OriginalPrice"] - $row["ItemPrice"])*$row["Qty"]) .
                            "</a></td>";
                } else {
                    $ret .= "<td>&mdash;</td>";
                }
            }
			$ret .= "<td style='text-align:center;border-bottom: 1px solid #DBDBDB; padding: 5px;'>$row[ItemPriceF]</td>
                  <td style='text-align:center;border-bottom: 1px solid #DBDBDB; padding: 5px;'>$row[Qty] $row[Units]</td>
                  <td style='text-align:center;border-bottom: 1px solid #DBDBDB; padding: 5px;'>$row[TotalPriceF]</td>
                
                 </tr>";

        }

        if ($this->CartDiscounts) {
            foreach ($this->CartDiscounts as $discount) {
                $ret .= "<tr><td colspan=".($has_item_discounts ? 4 : 3).">
                     <b>$discount[Name]</b>" .
                        ($discount["Description"] ? "<br>$discount[Description]" : "") .
                        "</td><td>" . ($discount["Sum"] > 0 ? "-" : "") . "$discount[SumF]</td>
                     <td>&nbsp;</td></tr>\n";
            }
        }

        $ret.="<tr><td colspan=".($has_item_discounts ? 4 : 3)." style='text-align:right;border-bottom: 2px solid #DBDBDB; padding: 5px;'><b>" . NETCAT_MODULE_NETSHOP_DELIVERY . ":</b></td>
				<td style='text-align:center;border-bottom:2px solid #DBDBDB;padding:5px;'><b>".$deliverycost."</b> руб.</td></tr>";
		$ret .= "<tr><td colspan=".($has_item_discounts ? 4 : 3)." style='background:#DBDBDB;text-align:right;padding:5px;'><b>" . NETCAT_MODULE_NETSHOP_SUM . ":</b></td>
				<td style='background:#DBDBDB;text-align:center;padding:5px;'><b>".($this->FormatCurrency($this->CartSum()))."</b></td></tr>";

        $ret .= "</table>";

		

        return $ret;
    }

	// форма оформления заказа
    function PrintOrderForm() {
        global $SUB_FOLDER, $HTTP_ROOT_PATH, $db;
        if (!$this->CartCount()) {
            return NETCAT_MODULE_NETSHOP_ERROR_CART_EMPTY;
        }
        $ret = "";
		$ret.='<script type="text/javascript">
			function onDeliveryClick8() {
				document.getElementById("rbPayment5").disabled=false;
				//document.getElementById("rbPayment15").disabled=false;
				
				document.getElementById("rbPayment6").disabled=true;
				document.getElementById("rbPayment7").disabled=true;
				//document.getElementById("rbPayment8").disabled=true;
				//document.getElementById("rbPayment9").disabled=true;
				document.getElementById("btn_calc").disabled=true;
			}
			function onDeliveryClick9() {
				var x = document.getElementById("f_country").value;
				//console.log(x);
				if (x==165) {
					document.getElementById("rbPayment1").disabled=false;
					document.getElementById("rbPayment5").disabled=false;
					document.getElementById("rbPayment6").disabled=true;
					document.getElementById("rbPayment7").disabled=false;
					//document.getElementById("rbPayment8").disabled=true;
					//document.getElementById("rbPayment9").disabled=true;
					document.getElementById("btn_calc").disabled=false;
				} else {
					document.getElementById("rbPayment1").disabled=false;
					document.getElementById("rbPayment5").disabled=true;
					document.getElementById("rbPayment6").disabled=true;
					document.getElementById("rbPayment7").disabled=true;
					//document.getElementById("rbPayment8").disabled=true;
					//document.getElementById("rbPayment9").disabled=true;
					document.getElementById("btn_calc").disabled=false;
				}
				
			}
			function onDeliveryClick7() {
				document.getElementById("rbPayment1").disabled=false;
				document.getElementById("rbPayment5").disabled=false;
				document.getElementById("rbPayment6").disabled=true;
				document.getElementById("rbPayment7").disabled=false;
				document.getElementById("btn_calc").disabled=true;
			}
			function onDeliveryClick3() {
				document.getElementById("rbPayment1").disabled=false;
				document.getElementById("rbPayment5").disabled=false;
				document.getElementById("rbPayment6").disabled=true;
				document.getElementById("rbPayment7").disabled=false;
				//document.getElementById("rbPayment8").disabled=true;
				document.getElementById("btn_calc").disabled=true;
			}
			function onDeliveryClick() {
				document.getElementById("rbPayment1").disabled=false;
				document.getElementById("rbPayment5").disabled=false;
				document.getElementById("rbPayment6").disabled=false;
				document.getElementById("rbPayment7").disabled=true;
				document.getElementById("btn_calc").disabled=true;
			}
			function clearFrm() {
				document.getElementById("f_ContactName").value="";
				document.getElementById("f_Phone").value="";
				document.getElementById("f_mphone").value="+7";
				document.getElementById("f_Email").value="";
				document.getElementById("f_Region").value="";
				document.getElementById("f_Town").value="";
				document.getElementById("f_PostIndex").value="";
				document.getElementById("f_Street").value="";
				document.getElementById("f_House").value="";
				document.getElementById("f_Flat").value="";
			}
			function previewCalc() {
				document.getElementById("actn").value="preview";
				document.getElementById("frm1").submit();
			}
			function changeCountry() {
				//alert(document.getElementById("country").value);
				var x = document.getElementById("f_country").value;
				//console.log(x);
				if (x!=165) {
					document.getElementById("rbDelivery9").disabled=false;
					document.getElementById("rbDelivery1").disabled=true;
					document.getElementById("rbDelivery2").disabled=true;
					document.getElementById("rbDelivery3").disabled=true;
					document.getElementById("rbDelivery5").disabled=true;
					document.getElementById("rbDelivery6").disabled=true;
					document.getElementById("rbDelivery7").disabled=true;
					document.getElementById("rbDelivery8").disabled=true;
					
					document.getElementById("rbPayment1").disabled=false;
					document.getElementById("rbPayment6").disabled=true;
					document.getElementById("rbPayment7").disabled=true;
				} else {
					for (j=1;j<10;j++) {
						document.getElementById("rbDelivery"+j).disabled=false;
					}
					for (j=5;j<10;j++) {
						if (j!=8) {
							document.getElementById("rbPayment"+j).disabled=false;
						}
					}
				}
			}
	/**
	 * подтягиваем список городов ajax`ом, данные jsonp в зависмости от введённых символов
	 */
	$(function() {
	  $("#f_Town").autocomplete({
	    source: function(request,response) {
	      $.ajax({
	        url: "https://api.cdek.ru/city/getListByTerm/jsonp.php?callback=?",
	        dataType: "jsonp",
	        data: {
	        	q: function () { return $("#f_Town").val() },
	        	name_startsWith: function () { return $("#f_Town").val() }
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
	    	//console.log("Yep!");
	    	$(\'#receiverCityId\').val(ui.item.id);
	    }
	  });
	  
	});
		</script>';
		$ret.=($_POST['posting']=="") ? "<p style='text-align:right;'><a href='#' onclick='clearFrm();'>Очистить форму</a></p>" : "";
		//print_r($this);
		//echo "<br><br>";//.$this->Cart;
		//print_r($this->CartContents); //Subdivision_ID=153 метательные ножи
		$pptrue = true; // разрешено выбрать доставку через PickPoint
		foreach ($this->CartContents as $ccnt) {
			if ($ccnt['Subdivision_ID']==153) {
				$pptrue=false;
				break;
			}
		}
        if ($GLOBALS['warnText']) {
            $ret .= "<p class=netshop_error>" . $GLOBALS['warnText'] . "</p>";
        }

        /*$ret .= "<div class='order_form'>
              <form method='post' action='" . $SUB_FOLDER . $HTTP_ROOT_PATH . "add.php'>
               <input name='cc' type='hidden' value='{$GLOBALS[cc]}'>
               <input name='sub' type='hidden' value='{$GLOBALS[sub]}'>
               <input name='catalogue' type='hidden' value='{$GLOBALS[catalogue]}'>
               <input type='hidden' name='posting' value='1'>";*/
		/*
		1 online credit card
		5 Через Сбербанк
		6 Наличными (курьеру или самовывоз)
		7 Наложенный платеж (почта РФ)
		8 оплата Яндекс.Деньгами
		9 оплата Webmoney
		15 cdm bank
		*/
		/*  // печать данных из javascript
			console.log(key);
			console.log(document.getElementsByName("f_DeliveryMethod")[key].value); */ 

			   
		$ret .= "<div class='order_form'>
	<form method='post' action='" . $SUB_FOLDER . $HTTP_ROOT_PATH . "add.php' id='frm1'>
		<input name='cc' type='hidden' value='53'>
		<input name='sub' type='hidden' value='57'>
		<input name='catalogue' type='hidden' value='{$GLOBALS[catalogue]}'>
		<input type='hidden' name='posting' value='1'>
		<input type='hidden' name='actn' id='actn' value=''>
		<input name='senderCityId' value='44' hidden />
		<input name='receiverCityId' id='receiverCityId' value='".(($_POST['receiverCityId']!="") ? $_POST['receiverCityId'] : "" )."' hidden />";
// senderCityId и receiverCityId - для СДЭК
			   
        $res = q("SELECT *
                FROM Field
                WHERE Class_ID=$this->order_table
                  AND TypeOfEdit_ID=1
                ORDER BY Priority");
		$postcost=0; //стоимость доставки по почте
        // $GLOBALS["current_user"]
		$availablepay=0; // доступный способ оплаты
		$availablepay1=0; // доступный способ оплаты
        while ($row = mysql_fetch_assoc($res)) {
            // Payment and Delivery
            if (preg_match("/^(Payment|Delivery)Method$/", $row["Field_Name"], $regs)) {
                $what = $regs[1];
				//echo $what; 
                $methods = $this->EligibleMethodsOf(strtolower($what), 1);
                if (preg_match("/^Payment$/", $what))
                    $methodtable = $this->payment_methods_table;
                if (preg_match("/^Delivery$/", $what))
                    $methodtable = $this->delivery_methods_table;
                $count_methods = $db->get_var("SELECT COUNT(`Message_ID`) FROM `Message" . $methodtable . "` WHERE `Checked`=1");
				//echo $count_methods;
                if ($count_methods) {
					$ret.="<div class='form-group'>";
					$ret.="<div style='border:1px solid #c0c0c0;border-radius: 6px !important;box-shadow: 0px 0px 6px rgba(0, 0, 0, 0.102);padding:10px;'>";
                    $ret .= "<b>$row[Description]</b>" . ($row["NotNull"] ? " (*)" : "") . ":<br>\n";
                    $value = htmlspecialchars($_POST['posting'] ? $_POST["f_" . $row["Field_Name"]] : $GLOBALS["current_user"][$row["Field_Name"]]);
                    foreach ($methods as $i => $method) {
						$d=(($methodtable==56)&&($method["Message_ID"]==8)&&($pptrue==false)) ? " disabled " : "";
						$d1=($GLOBALS['resdlv']!="") ? (($methodtable==55) ? ((($method["Message_ID"]==$availablepay)||($method["Message_ID"]==$availablepay1)||($method["Message_ID"]==$availablepay2)) ? " " : " disabled ") : "") : "";
						//$d1=""; //(($methodtable==56)&&($method["Message_ID"]==15)) ? " disabled " : "";
						if (($methodtable==56)&&($method["Message_ID"]==3)) {
							$postcost=$method["Sum"];
							$sql="SELECT cost FROM Netshop_Postdeliverycost WHERE min<={$this->CartSum()} AND max>={$this->CartSum()}";
							$res1=q($sql);
							while ($row1 = mysql_fetch_assoc($res1)) {
								$postcost=$row1['cost'];
								$postcostprint=$row1['cost']." руб.";
							}
						}
						//pickpoint
						if (($methodtable==56)&&($method["Message_ID"]==8)) {
							$postcost=$method["Sum"];
							$postcostprint="вычисляется при оформлении";
							//if($this->CartSum()>2499) {
							//	$postcost="";
							//}
						}
						//echo $d.";".$methodtable.";".$method["Message_ID"].";".$pptrue."<br>";
						//echo $postcost;
						//echo "<br>".$_POST['f_country'];
						if ((isset($_POST['f_country']))&&($_POST['f_country']!=165)) {
							if ($methodtable==56) {
								$ret.= "<input type='radio' ". 
									(($method["Message_ID"]==9) ? "checked " : "disabled"). 
									" name='f_{$what}Method'
							   value='$method[Message_ID]'
							   id='rb$what$method[Message_ID]' > 
							   <label for='rb$what$method[Message_ID]'>
							   $method[Name] " .
									($method["Sum"] ? (($methodtable==56) ? ((($method["Message_ID"]==3)||($method["Message_ID"]==8)) ? "({$postcostprint})" : "(".$this->FormatCurrency($method["Sum"]).")" ) : "") : "") .
									"</label>";
							}
							if ($methodtable==55) {
								$ret.= "<input type='radio' ". 
									(($method["Message_ID"]==1) ? "checked " : "disabled"). 
									" name='f_{$what}Method'
							   value='$method[Message_ID]'
							   id='rb$what$method[Message_ID]' > 
							   <label for='rb$what$method[Message_ID]'>
							   $method[Name] " .
									($method["Sum"] ? (($methodtable==56) ? ((($method["Message_ID"]==3)||($method["Message_ID"]==8)) ? "({$postcostprint})" : "(".$this->FormatCurrency($method["Sum"]).")" ) : "") : "") .
									"</label>";
							}
						} else {
                        $ret.= "<input type='radio' " . ($method["Message_ID"] == $value ? "checked " : "") . "name='f_{$what}Method'
            	           value='$method[Message_ID]'
            	           id='rb$what$method[Message_ID]'{$d}{$d1}".(($methodtable==56) ? ((($method["Message_ID"]==3)||($method["Message_ID"]==9)||($method["Message_ID"]==8)||($method["Message_ID"]==7)) ? " onclick='on{$what}Click{$method["Message_ID"]}();'" : " onclick='on{$what}Click();'") : "")."
						   
						   > <label for='rb$what$method[Message_ID]'>
            	           $method[Name] " .
                                ($method["Sum"] ? (($methodtable==56) ? ((($method["Message_ID"]==3)||($method["Message_ID"]==8)) ? "({$postcostprint})" : "(".$this->FormatCurrency($method["Sum"]).")" ) : "") : "") .
                                "</label>";
						} 
						
						//if ($method["Description"]) {
							//$ret.=(!$d) ? $method['Description']."<br>" : "<br>";
							if (($methodtable==56)&&($method["Message_ID"]==9)) {
								if ($GLOBALS['resdlv']=="") {
									$ret.="&nbsp;&nbsp;<input type='button' value='Рассчитать стоимость доставки СДЭК' id='btn_calc' onclick='previewCalc();'>";
									if ((isset($_POST))&&($GLOBALS['f_Town']!="")&&($_POST['receiverCityId']=="")) {
										$ret.="<span style='color:#000;'>&nbsp;&nbsp;Доставка СДЭК с город {$GLOBALS['f_Town']} не производится!</span>";
									}
								} else {
									$ret.="<span style='color:#000;'>&nbsp;&nbsp;Стоимость доставки: ".$GLOBALS['resdlv']['result']['price']."руб.</span>";
									$availablepay=5;
									$availablepay1=7;
									$availablepay2=1;
									/*echo 'Срок доставки: ' . $res['result']['deliveryPeriodMin'] . '-' . 
															 $res['result']['deliveryPeriodMax'] . ' дн.<br />';
									echo 'Планируемая дата доставки: c ' . $res['result']['deliveryDateMin'] . ' по ' . $res['result']['deliveryDateMax'] . '.<br />';
									echo 'id тарифа, по которому произведён расчёт: ' . $res['result']['tariffId'] . '.<br />';
									if(array_key_exists('cashOnDelivery', $res['result'])) {
										echo 'Ограничение оплаты наличными, от (руб): ' . $res['result']['cashOnDelivery'] . '.<br />';
									}*/
								}
							}
							if (($methodtable==56)&&($method["Message_ID"]==8)) {
								if ($pptrue==false) {
									$ret.="<span style='color:#f30000;'>По правилам PickPoint спортивные метательные ножи запрещены к перевозке!</span>";
								} else { 
									$ret.="<script type=\"text/javascript\" src=\"https://pickpoint.ru/select/postamat.js\" charset=\"utf-8\"></script>
	<div style=\"display:block;width:200px;height:20px;float:right; margin:0 320px 0 0;\"><a href=\"#\" onclick=\"PickPoint.open(my_function, {ikn: '9990394012', fromcity:'Москва'});return false\">Выбрать постамат PickPoint</a></div>
	<div id=\"address\"></div>
	<!-- в это поле поместится ID постамата или пункта выдачи -->
	<input type=\"hidden\" name=\"f_pickpoint_id\" id=\"pickpoint_id\" value=\"\" />
	<input type=\"hidden\" name=\"f_pickpoint_coef\" id=\"pickpoint_coef\" value=\"\" />
	<input type=\"hidden\" name=\"f_pickpoint_zone\" id=\"pickpoint_zone\" value=\"\" />
	<input type=\"hidden\" name=\"f_pickpoint_address\" id=\"pickpoint_address\" value=\"\" />
	<script type=\"text/javascript\">
	function my_function(result){
	// устанавливаем в скрытое поле ID терминала
	//console.log(result);
	document.getElementById('pickpoint_id').value=result['id'];
	coeff=1;
	if (result['coeff']==null) {
		document.getElementById('pickpoint_coef').value=1;
	} else {
		coeff=result['coeff'];
		document.getElementById('pickpoint_coef').value=result['coeff'];
	}
	
	//console.log(coeff);
	document.getElementById('pickpoint_zone').value=result['zone'];
	document.getElementById('pickpoint_address').value=result['name']+result['address'];
	// показываем пользователю название точки и адрес доставки
	document.getElementById('address').innerHTML=result['name']+'<br />'+result['address']+
	'<br />Стоимость доставки: '+(coeff*".($postcost).")+'руб.';
	}
	</script>";	
								}
							
							} else {
								$ret.=$method['Description']."<br />\n";
							}
						//} else {
						//	$ret .= "";
						//}
						$ret.="<div style='height:3px;'></div>\n";
                    }
					$ret.="</div></div>";
               }
            } else {
				//print_r($GLOBALS["current_user"]);
				//			echo "<br>";
                $value = $_POST['posting'] ? $_POST["f_" . $row["Field_Name"]] : $GLOBALS["current_user"][$row["Field_Name"]];
				if ($_POST['posting']=="") {
					// select data from last order
					$sql="SELECT * FROM Message{$this->order_table} WHERE User_ID=".$GLOBALS["current_user"]["User_ID"]." ORDER BY Message_ID DESC LIMIT 0,1";
					$res2=q($sql);
					$u=array();
					while ($row2=mysql_fetch_assoc($res2)) {
						$u["ContactName"]=$row2["ContactName"];
						$u["Phone"]=$row2["Phone"];
						$u["Email"]=$row2["Email"];
						$u["Region"]=$row2["Region"];
						$u["Town"]="";//$row2["Town"]; // обязательно для заполнения
						$u["PostIndex"]=$row2["PostIndex"];
						$u["Street"]=$row2["Street"];
						$u["House"]=$row2["House"];
						$u["Flat"]=$row2["Flat"];
						$u["mphone"]=$row2["mphone"];
					}
				}	
				$ret.="<div class='form-group'>";
				switch ($row["TypeOfData_ID"]) {
                    case 1:
                        # String
						($value=="") ? $value=$u[$row["Field_Name"]] : "";
						if ($row["Field_Name"]=="Town") {
							$ret.="
							<div class='form-group'><div class='ui-widget'>
							<label for='f_Town' style='' id='nc_capfld_1113'>Город (*):</label>
<input id='f_Town' name='f_Town' maxlength='255' size='50' type='text' value='{$value}' class='form-control'  /></div></div>";
						} else {					
							$ret.= nc_string_field("$f_$row[Field_Name]", "", $classID, 1, $value) . "\n";
							if ($row['Field_Name']=="mphone") {
								$ret.="<br><br>";
							}
						}
                        break;

                    case 2:
                        # Int
                        $ret.= nc_int_field("$row[Field_Name]", "", $classID, 1) . "\n";
                        break;

                    case 3:
                        # Text
                        $ret.= nc_text_field("$row[Field_Name]", "", $classID, 1, false, $value) . "\n";
                        break;

                    case 4:
                        # List
						//$ret.= nc_list_field("$row[Field_Name]", "", $classID, 1) . "\n";
						if (!isset($value)) {
							$value=165;
						}
						$ret.="<div class='form-group'><span style='' id='nc_capfld_1113'>Страна (*):</span>
<select name='f_country' id='f_country' onchange='changeCountry();'>
<option value='12' id='f111312' ".(($value==12) ? "selected" : "").">Армения</option>
<option value='19' id='f111319' ".(($value==19) ? "selected" : "").">Беларусь</option>
<option value='83' id='f111383' ".(($value==83) ? "selected" : "").">Казахстан</option>
<option value='92' id='f111392' ".(($value==92) ? "selected" : "").">Киргизия</option>
<option value='165' id='f1113165' ".(($value==165) ? "selected" : "").">Россия</option>
</select>
</div>";
                        break;

                    case 5:
                        # Bool
                        $ret.= "<div style='padding:10px 10px 10px 50px;'>".nc_bool_field("$row[Field_Name]", "", $classID, 1) . " 
							<a target='_blank' href='/about/agreement/'>Согласие на обработку и хранение персональных данных</a></div>\n";
                        break;

                    case 6:
                        # File
                        $ret.= nc_file_field("$row[Field_Name]", "", $classID, 1) . "\n";
                        break;

                    case 7:
                        # Float
                        $ret.= nc_float_field("$row[Field_Name]", "", $classID, 1) . "\n";
                        break;

                    case 8:
                        # DateTime
                        $ret.= nc_date_field("$row[Field_Name]", "", $classID, 1) . "\n";
                        break;

                    case 9:
                        # Relation
                        $ret.= nc_related_field("$row[Field_Name]") . "\n";
                        break;

                    case 10:
                        # Multiselect
                        $ret.= nc_multilist_field("$row[Field_Name]", "", "", $classID, 1) . "\n";
                        break;
                }
            }

            //$ret .="<br clear='both' /><br />";
            $ret .="</div>\n\n";
        }

        $ret .= "<div class='form-group'><div class='order_buttons'><input class='btn btn-success' onClick=\"ym(2308948, 'reachGoal', 'justorder'); return true;\" type='submit' title=\"" . NETCAT_MODULE_NETSHOP_CART_CHECKOUT . "\" value=\"" . NETCAT_MODULE_NETSHOP_CART_CHECKOUT . "\"></div></div>";

        $ret .= "</form></div>";
        return $ret;
    }

    function GuessGoodsTypeIDs() {
        return NetShop::get_goods_table();
    }

    function GetBestsellers($type_ids = "", $number = 5, $section = false) {
        if ($type_ids) {
            $type_ids = nc_preg_split("/,\s*/", $type_ids);
        } else if (!$this->GoodsTypeIDs) {
            $this->GoodsTypeIDs = $this->GuessGoodsTypeIDs();
            $type_ids = &$this->GoodsTypeIDs;
        }
        
        if (!$type_ids) return array();

        if ($section) {
            $structure = GetStructure($section, "", "get_children");
            $all_children = array_keys($structure);
            foreach ($structure as $row) {
                if ($row["Children"])
                    $all_children = array_merge($all_children, $row["Children"]);
            }
            if ($all_children) {
                $subdivisions_qry = " AND m.Subdivision_ID IN ($section, " . join(", ", $all_children) . ")";
            } else {
                $subdivisions_qry = " AND m.Subdivision_ID = '" . $section > "'";
            }
        } else {
            $subdivisions_qry = "";
        }

        $q = array();
        foreach ($type_ids as $type_id) {
            int($type_id);

            $q[] = "SELECT $type_id as Type_ID,
                        m.Message_ID,
                        m.Name,
                        m.Description,
                        m.Price,
                        m.Currency,
                        m.Image,

                        CONCAT(sd.Hidden_URL, sc.EnglishName, '_',
                               m.Message_ID, '.html') as URL,

                        (IF (m.TopSellingMultiplier IS NOT NULL,
                             SUM(o.Qty)*m.TopSellingMultiplier,
                             SUM(o.Qty)) +
                         IF (m.TopSellingAddition IS NOT NULL,
                             m.TopSellingAddition, 0)
                        ) as Rating

                 FROM Message$type_id as m,
                      Netshop_OrderGoods as o,
                      Subdivision as sd,
                      Sub_Class as sc

                 WHERE o.Item_Type=$type_id
                   AND o.Item_ID = m.Message_ID
                   AND m.Checked = 1
                   $subdivisions_qry

                   AND sd.Subdivision_ID = m.Subdivision_ID
                   AND sc.Class_ID = $type_id
                   AND sc.Subdivision_ID = m.Subdivision_ID

                 GROUP BY o.Item_ID";
        }
        $res = q(join(" UNION ", $q) . " ORDER BY Rating DESC LIMIT $number");
        $ret = array();
        while ($row = mysql_fetch_assoc($res)) {
            $ret[] = $row;
        }
        return $ret;
    }

    public function check_payment_errors($payment_method) {
        $error = false;
        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;
        switch ($payment_method) {
            case 'assist':
                if (!$this->AssistShopId) {
                    $error = NETCAT_MODULE_NETSHOP_ERROR_ASSIST;
                }
                break;

            case 'paypal':
                if (!$this->PaypalBizMail || !$this->Currencies[$this->DefaultCurrencyID]) {
                    $error = NETCAT_MODULE_NETSHOP_ERROR_PAYPAL_MAIL;
                    break;
                }
                $rates_table = $nc_core->modules->get_vars('netshop', 'OFFICIAL_RATES_TABLE');
                $SQL = "SELECT Rate
                            FROM Message{$rates_table}
                                WHERE Currency=2";
                if ($this->Currencies[$this->DefaultCurrencyID] != "USD" && !$db->get_var($SQL)) {
                    $error = NETCAT_MODULE_NETSHOP_ERROR_PAYPAL_RATES;
                }
                break;

            case 'qiwi':
                if (!$this->QiwiFrom || !$this->QiwiPwd) {
                    $error = NETCAT_MODULE_NETSHOP_ERROR_QIWI;
                }
                break;

            case 'mail':
                if (!$this->MailShopID || !$this->MailHash || !$this->MailSecretKey) {
                    $error = NETCAT_MODULE_NETSHOP_ERROR_MAIL;
                }
                break;

            case 'robokassa':
                if (!$this->RobokassaLogin || !$this->RobokassaPass1 || !$this->RobokassaPass2) {
                    $error = NETCAT_MODULE_NETSHOP_ERROR_ROBOKASSA;
                }
                break;
            case 'webmoney':
                if (!$this->WebmoneyPurse || !$this->WebmoneySecretKey) {
                    $error = NETCAT_MODULE_NETSHOP_ERROR_WEBMONEY;
                }
                break;
            case 'paycash_email':
                if (!$this->PayCashSettings) {
                    $error = NETCAT_MODULE_NETSHOP_ERROR_YANDEX;
                }
                break;
            case 'paymaster':
                if (!$this->PaymasterID || !$this->PaymasterWord) {
                    $error = NETCAT_MODULE_NETSHOP_ERROR_PAYMASTER;
                }
                break;
        }
        return $error;
    }

}
