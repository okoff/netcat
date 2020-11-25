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

class Netshop_criteoXML extends Netshop {

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

	public function getVendorById($id) {
		global $db, $nc_core;
		$res="";
		$query="SELECT * FROM Classificator_Manufacturer WHERE Manufacturer_ID=".$id;
		$rows = $db->get_results($query, ARRAY_A);
        foreach ((array) $rows as $row) {
			$res=$row['Manufacturer_Name'];
		}
		return $res;
	}
	
	public function convstr($str) {
		return iconv("windows-1251//TRANSLIT","UTF-8",$str);
	}
	public function convstrw($str) {
		return iconv("UTF-8","windows-1251//TRANSLIT",$str);
	}
	
    /**
     * Экспорт в формате YandexML
     * @param int раздел, который надо экспортировать (по умолчанию - весь магазин)
     */
    public function ExportXML($section=0) {
        global $HTTP_HOST, $SUB_FOLDER;
        global $db, $nc_core;
        global $catalogue;


        if (!$this->shop_id) return false;
        $shopName = $this->ShopName;
        $default_currency = $this->Currencies[$this->DefaultCurrencyID];

        header("Content-type: text/xml");
        $ret="<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		$ret.="<products>\n";

		$class_id=57;
/*
2562 - Фонари
356  - Мультитулы
140  - Все для заточки (Алмазные бруски, Водные камни, точильные наборы)
- Коробки и футляры
- Подставки под ножи
162  - Доспехи, кованые изделия
2563 - Термосы
*/
        
            $query = "SELECT m.*,
                         ShopCurrency_Name AS CurrencyID,
                         CONCAT(u.Hidden_URL, s.EnglishName, '_', m.Message_ID, '.html') as URL,
                         IFNULL(m.$this->PriceColumn, parent.$this->PriceColumn) as Price4User,
                         IF(m.$this->PriceColumn IS NULL, parent.$this->CurrencyColumn, m.$this->CurrencyColumn) as Currency4User

                FROM (`Message".$class_id."` as m, `Subdivision` as u, `Sub_Class` as s)
                  LEFT JOIN Message".$class_id." as parent
                    ON (m.`Parent_Message_ID` != 0 AND m.`Parent_Message_ID` = parent.`Message_ID`)
                  LEFT JOIN `Classificator_ShopCurrency`
                    ON Classificator_ShopCurrency.`ShopCurrency_ID` = m.`Currency`
                WHERE m.`Checked` = 1
                    AND m.`Subdivision_ID` IN (2562,356,140,2563)
                    AND s.`Subdivision_ID` = m.`Subdivision_ID`
                    AND s.`Sub_Class_ID` = m.`Sub_Class_ID`
                    AND u.`Subdivision_ID` = m.`Subdivision_ID`
					AND m.`status`=2 AND m.`Checked`=1
                HAVING `Price4User` > 1000
                    ";
			//echo $query."<br>";
            $rows = $db->get_results($query, ARRAY_A);
            foreach ((array) $rows as $row) {
/*			
<products>
<product id=”B789465”>
<name>Гарри Поттер 7</name>
<bigimage>http://partner/big1.jpg</bigimage>
<producturl>http://partner/1.htm</producturl>
<description>Последняя книга из саги про Гарри Поттера</description>
<price>20.99</price>
<retailprice>34.99</retailprice>
<discount>40</discount>
<categoryid1>Книги</categorydi1>
<extra_brand>Bloomsbury</extra_brand>
<instock>1</instock>
</product>
...
</products>
*/
                if (strlen($row["StockUnits"])) {
                    $row["Available"] = ($row["StockUnits"] ? "true" : "false" );
                } else {
                    $row["Available"] = "true";
                }

                // convert to default currency
                $row["Price"] = $this->ConvertCurrency($row["Price4User"], $row["Currency4User"]);
                // we'll need an absolute url
                $row["URL"] = "http://".$HTTP_HOST.$SUB_FOLDER."$row[URL]";
                $row["CurrencyID"] = $row["CurrencyID"] ? $row["CurrencyID"] : $default_currency;

                if ($row["Image"]) { // replace to image url
                    $row["Image"] = "http://".$HTTP_HOST.$SUB_FOLDER.nc_file_path($class_id, $row["Message_ID"], "Image", "h_");
                }
				
				$name=str_replace("\"", "&quot;", $row['Name']);
				$name=str_replace("&", "&amp;", $name);
				$name=str_replace("<", "&gt;", $name);
				$name=str_replace(">", "&lt;", $name);
				
				$details=str_replace("\"", "&quot;", $row['Details']);
				$details=str_replace("&", "&amp;", $details);
				$details=str_replace("<", "&lt;", $details);
				$details=str_replace(">", "&gt;", $details);

                $ret .= "<product id=\"".$row["ItemID"]."\">\n";
				$ret.="<name>".$name."</name>\n";
				$ret.="<bigimage>".$row['Image']."</bigimage>\n";
				$ret.="<producturl>".$row['URL']."</producturl>\n";
				$ret.="<description>".$details."</description>\n";
				$ret.="<price>".$row['Price']."</price>\n";
				$ret.="<retailprice>".$row['Price']."</retailprice>\n";
				$ret.="<discount>0</discount>\n";
                $ret.="<categoryid1>".$this->convstrw("Туристические товары")."</categoryid1>\n";
				$ret.="<extra_brand>".$this->getVendorById($row['Vendor'])."</extra_brand>\n";
				$ret.="<instock>".$row['StockUnits']."</instock>\n";
				
                $ret .= "</product>\n";
            }
        // ---------------------------------------------------------------

        $ret .= "</products>";
        print $ret;
        // return $ret;
    }

}

//End Class Netshop_ExportYML
//---------------

$shop = new NetShop_criteoXML();
$shop->ExportXML();
?>