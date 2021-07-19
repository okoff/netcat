<?

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -5)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require_once ($ROOT_FOLDER."connect_io.php");

header("Content-type: text/xml");
$catalogue = $nc_core->catalogue->get_by_host_name($HTTP_HOST);
$catalogue = $catalogue["Catalogue_ID"];
if (!$catalogue) $catalogue = 1;

if (is_file($MODULE_FOLDER."netshop/".MAIN_LANG.".lang.php")) {
    require_once($MODULE_FOLDER."netshop/".MAIN_LANG.".lang.php");
    $modules_lang = "Russian";
} else {
    require_once($MODULE_FOLDER."netshop/en.lang.php");
    $modules_lang = "English";
}

//LoadModuleEnv();
$MODULE_VARS = $nc_core->modules->load_env($modules_lang);

class Netshop_ExportYML extends Netshop {

    private $_CurrencyArray;
    private $_MaxNameLen;
    private $_class_ids;

    public function __construct() {

        $this->Netshop();
        $this->_CurrencyArray = Array('RUR', 'RUB', 'USD', 'EUR', 'UAH');
        $this->_MaxNameLen = 20;
        $nc_core = nc_Core::get_object();
        $this->_class_ids = $nc_core->modules->get_vars('netshop', 'GOODS_TABLE');
    }
	public function convstr_li($str) {
		return iconv("UTF-8","windows-1251//TRANSLIT",$str);
	}
	
	public function convstrw_li($str) {
		return iconv("windows-1251//TRANSLIT","UTF-8",$str);
	}
    /**
     * Экспорт в формате YandexML
     * @param int раздел, который надо экспортировать (по умолчанию - весь магазин)
     */
    public function ExportYML($section=0) {
        global $HTTP_HOST, $SUB_FOLDER;
        global $db, $nc_core;
        global $catalogue;

        if (!$this->shop_id) return false;
//      $shopName = (nc_strlen($this->ShopName) > $this->_MaxNameLen) ? nc_substr($this->ShopName, 0, $this->_MaxNameLen) : $this->ShopName;
		$shopName = $this->ShopName;
        $default_currency = $this->Currencies[$this->DefaultCurrencyID];

        header("Content-type: text/xml");
        $ret = "<?xml version=\"1.0\" encoding=\"".$nc_core->NC_CHARSET."\"?>
<yml_catalog>\n<shop>\n<offers>\n";

        // получить типы товаров
        $goods_class_ids = $this->GuessGoodsTypeIDs();

        // все разделы магазина
        $subdivision_id = join(",", $all_sections_id);

        foreach ($goods_class_ids as $class_id) {
            $query = 
				"SELECT 
					m.*,
                    IFNULL(m.$this->PriceColumn, parent.$this->PriceColumn) as Price4User,
                    IF(m.$this->PriceColumn IS NULL, parent.$this->CurrencyColumn, m.$this->CurrencyColumn) as Currency4User
                FROM (`Message".$class_id."` as m, `Subdivision` as u, `Sub_Class` as s)
                  LEFT JOIN Message".$class_id." as parent
                    ON (m.`Parent_Message_ID` != 0 AND m.`Parent_Message_ID` = parent.`Message_ID`)
                  LEFT JOIN `Classificator_ShopCurrency`
                    ON Classificator_ShopCurrency.`ShopCurrency_ID` = m.`Currency`
                WHERE m.`toozon`=1
                    AND s.`Subdivision_ID` = m.`Subdivision_ID`
                    AND s.`Sub_Class_ID` = m.`Sub_Class_ID`
                    AND u.`Subdivision_ID` = m.`Subdivision_ID`
					AND m.`Checked`=1
                HAVING `Price4User` > 0
                    ";
//echo $query;
            $rows = $db->get_results($query, ARRAY_A);
            foreach ((array) $rows as $row) {
                // convert to default currency
                $row["Price"] = $this->ConvertCurrency($row["Price4User"], $row["Currency4User"]);
				$offer = sprintf("%d%05d", $class_id, $row["Message_ID"]);
				$row["offer"] = $offer;
				$ret .= "<offer id=\"".$offer."\">\n";
				$ret .= "<price>".$row["Price"]."</price>\n";
				$ret .= "<oldprice>".$row["Price"]."</oldprice>\n";
				$ret .= "<premium_price>".$row["Price"]."</premium_price>\n";
				$ret .= "<outlets>\n";
				$ret .= "<outlet instock=\"".$row["StockUnits"]."\" warehouse_name=\"склад 1\"></outlet>\n";
				$ret .= "</outlets>\n";
				$ret .= "</offer>\n";
            }
        }
        // ---------------------------------------------------------------

        $ret .= "</offers>\n</shop>\n</yml_catalog>";
        print $ret;
    }

}

//End Class Netshop_ExportYML
//---------------

$shop = new NetShop_ExportYML();
$shop->ExportYML();
?>