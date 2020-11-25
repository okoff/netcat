<?
// export file for Price.Ru
// only knives
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

class Netshop_ExportAvito extends Netshop {

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

    /**
     * Экспорт в формате YandexML
     * @param int раздел, который надо экспортировать (по умолчанию - весь магазин)
     */
	public function convstr_li($str) {
		return iconv("UTF-8","windows-1251//TRANSLIT",$str);
	}
	
	public function convstrw_li($str) {
		return iconv("windows-1251//TRANSLIT","UTF-8",$str);
	}
     ///////////////////////////////////////
    //Возвращает структуру для class Netshop_ExportYML
    public function GetStructureCategories($class_id, $cat_id = 1, $where = '') {
        global $db;
        $query = "SELECT *
                   FROM `Subdivision`, `Sub_Class`
                      WHERE `Sub_Class`.`Subdivision_ID` = `Subdivision`.`Subdivision_ID`
                      AND `Sub_Class`.`Class_ID` IN ($class_id)".
                ($cat_id ? " AND `Subdivision`.`Catalogue_ID`='".intval($cat_id) : NULL)."'".
                ($where ? " AND ".$where : NULL).
				"ORDER BY Parent_Sub_ID ASC";
		//echo $query;		
		
		$result = $db->get_results($query, ARRAY_A);
        if ($result) {
            foreach ($result as $value) {
                $result_array[$value['Subdivision_ID']] = $value;
            }
            return $result_array;
        } else {
            return false;
        }
    }
	public function ExportYML($section=0) {
        global $HTTP_HOST, $SUB_FOLDER;
        global $db, $nc_core;
        global $catalogue;


        if (!$this->shop_id) return false;
        $shopName = (nc_strlen($this->ShopName) > $this->_MaxNameLen) ? nc_substr($this->ShopName, 0, $this->_MaxNameLen) : $this->ShopName;
        $default_currency = $this->Currencies[$this->DefaultCurrencyID];

        header("Content-type: text/xml");
        $ret = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<priceru_feed date=\"".date("Y-m-d H:i")."\">
<shop>
	<company>Интернет-магазин &quot;Русские ножи&quot;</company>
	<url>https://russian-knife.ru/</url>
	<currencies>
		<currency id=\"RUB\" rate=\"1\"/>
	</currencies>
	<categories>
";
		/*<categories>
 <category id="1" parentId="0">Съемочная техника</category>
 <category id="2" parentId="1">Видеокамеры</category>
 <category id="3" parentId="1">Цифровые фотоаппараты</category>
 <category id="4" parentId="1">Объективы</category>
</categories>
*/

        // output categories (shop structure) ---------------------------
        if (!$section) $section = $this->_class_ids;
		
        $structure = $this->GetStructureCategories($section, $catalogue,
			"(`Subdivision`.`Subdivision_ID`=81 OR `Subdivision`.`Parent_Sub_ID`=81 OR `Subdivision`.`Subdivision_ID`=108 OR `Subdivision`.`Parent_Sub_ID`=1041) ");
		
		//print_r($structure);
        if (!$structure) return;

        $all_sections_ids = array(); // потом вытащим на основе этих данных товары

        foreach ($structure as $row) {
            
			//if (($row["Subdivision_ID"]==106)||($row["Subdivision_ID"]==140)||($row["Subdivision_ID"]==289)||($row["Subdivision_ID"]==2563)) {
				$ret .= "\t\t<category id=\"{$row['Subdivision_ID']}\"";

				if (array_key_exists($row['Parent_Sub_ID'], $structure)) {
					$ret .= " parentId=\"{$row['Parent_Sub_ID']}\"";
				}

				$ret .= ">".$this->convstrw_li(xmlspecialchars($row["Subdivision_Name"]))."</category>\n";

				$all_sections_id[] = $row["Subdivision_ID"];
			//}
        }

        $ret .= "\t</categories>\n\t<offers>";

        // GOODS CATALOGUE -----------------------------------------------
        $output = array(
                "URL" => "url",
                "Price" => "price",
                "CurrencyID" => "currencyId",
                "Subdivision_ID" => "categoryId",
                "Image" => "picture",
 //               "Vendor" => "vendor",
 //               "VendorCode" => "vendorCode",
                "Name" => "name",
                "Description" => "description",
                "SalesNotes" => "sales_notes",
        );

        // получить типы товаров
        $goods_class_ids = $this->GuessGoodsTypeIDs();

        // все разделы магазина
        $subdivision_id = join(",", $all_sections_id);

        foreach ($goods_class_ids as $class_id) {
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
                    AND m.`Subdivision_ID` IN (".$subdivision_id.")
                    AND s.`Subdivision_ID` = m.`Subdivision_ID`
                    AND s.`Sub_Class_ID` = m.`Sub_Class_ID`
                    AND u.`Subdivision_ID` = m.`Subdivision_ID`
					AND m.`status`=2 AND m.`Checked`=1
					
					AND m.`Priceru`=1
                HAVING `Price4User` > 0
				ORDER BY Message_ID ASC
                    ";
			//echo $query;
			//AND m.`Price`>999   AND NOT m.`supplier`=74
            $rows = $db->get_results($query, ARRAY_A);
			$i=0;
            foreach ((array) $rows as $row) {
				//if ($i<750) {
				$name=xmlspecialchars(strip_tags($this->convstrw_li($row['Name'])));
				if (strpos($name,",")>0) {
					$name=mb_substr($name,0,strpos($name,","));
				} 
				$ret.="			
		<Offer id=\"".$row['Message_ID']."\">
			<name>Арт. ".xmlspecialchars(strip_tags($this->convstrw_li($row['ItemID'])))." ".$name."</name>
			<description>".xmlspecialchars(strip_tags($this->convstrw_li($row['Name'])))." ".xmlspecialchars(strip_tags($this->convstr_li($row['Description'])))."</description>
			<url>https://russian-knife.ru".$row['URL']."</url>
			<picture>https://".$HTTP_HOST.$SUB_FOLDER.nc_file_path($class_id, $row["Message_ID"], "Image", "h_")."</picture>
			<price>".$row['Price']."</price>
			<currencyId>RUB</currencyId>
			<categoryId>".$row['Subdivision_ID']."</categoryId>

		</Offer>";
				} 
			
				$i=$i+1;
				//<typePrefix>Фотоаппарат</typePrefix>
				//<vendor>Canon</vendor>
				//<model>EOS 600D</model>

        }
        // ---------------------------------------------------------------

		$ret .= "\t</offers>\n</shop>\n</priceru_feed>";

        //print $this->convstr_li($ret);
		print $ret;
	}
}

//End Class Netshop_ExportYML
//---------------

$shop = new NetShop_ExportAvito();
$shop->ExportYML();
?>