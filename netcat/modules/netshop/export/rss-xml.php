<?

$NETCAT_FOLDER = join( strstr(__FILE__, "/") ? "/" : "\\", array_slice( preg_split("/[\/\\\]+/", __FILE__), 0, -5 ) ).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require_once ($ROOT_FOLDER."connect_io.php");
require_once ($INCLUDE_FOLDER."s_loadenv.inc.php");
require_once ($INCLUDE_FOLDER."s_e404.inc.php");

header("Content-type: text/xml");

$catalogue = GetCatalogueByHostName($HTTP_HOST.$SUB_FOLDER);
$catalogue = $catalogue["Catalogue_ID"];
if (!$catalogue) $catalogue = 1;

if (is_file($MODULE_FOLDER."netshop/".MAIN_LANG.".lang.php")) {
  require_once($MODULE_FOLDER."netshop/".MAIN_LANG.".lang.php");
  $modules_lang = "Russian";
}
else {
  require_once($MODULE_FOLDER."netshop/en.lang.php");
  $modules_lang = "English";
}

//LoadModuleEnv();
$MODULE_VARS = $nc_core->modules->load_env($modules_lang);

class Netshop_ExportYML extends Netshop {
  
  private $_CurrencyArray; 
  private $_MaxNameLen;
  
  public function __construct () {
      $this->Netshop();
      $this->_CurrencyArray = Array( 'RUR', 'RUB', 'USD', 'EUR', 'UAH');
      $this->_MaxNameLen = 20;
  }

   /**
        * Экспорт в формате YandexML
        * @param int раздел, который надо экспортировать (по умолчанию - весь магазин)
        */
  public function ExportYML ($section=0) {
    global $HTTP_HOST, $SUB_FOLDER;
    global $db;
      
    if (!$this->shop_id) return false;
    $shopName = (strlen($this->ShopName) > $this->_MaxNameLen) ? substr($this->ShopName, 0, $this->_MaxNameLen ) : $this->ShopName;
    $default_currency = $this->Currencies[$this->DefaultCurrencyID];

    header ("Content-type: text/xml");
    $ret = "<?xml version=\"1.0\" encoding=\"windows-1251\"?>
<rss version=\"2.0\">
<channel>
	<title>Русские ножи</title>
	<link>http://".$HTTP_HOST.$SUB_FOLDER."/</link>
	<description></description>\n";
                /*  <currencies>
                    <currency id=\"".$default_currency."\" rate=\"1\" />";
    foreach ( (array)$this->Currencies as $k => $v ) {
      if ( $v != $default_currency && $this->Rates[$k] && in_array($v, $this->_CurrencyArray) ) {
        $ret .= "<currency id=\"$v\" rate=\"".$this->Rates[$k]."\" />";
      }
    }

    $ret .= "</currencies>
              <categories>\n";*/

    // output categories (shop structure) ---------------------------
    if (!$section) $section = $this->shop_id;
      $structure = GetStructure($section, "Checked=1");

    $all_sections_ids = array(); // потом вытащим на основе этих данных товары

    foreach ($structure as $row) {
      //$ret .= "<category id=\"$row[Subdivision_ID]\"".
      //($row["Parent_Sub_ID"]==$section ? "":" parentId=\"$row[Parent_Sub_ID]\"").
      // ">".xmlspecialchars($row["Subdivision_Name"])."</category>\n";

      $all_sections_id[] = $row["Subdivision_ID"];
    }

    //$ret .= "</categories>\n<offers>";
/*
    // GOODS CATALOGUE -----------------------------------------------
    $output = array(
     "URL"=>"url",
     "Price"=>"price",
     "CurrencyID"=>"currencyId",
     "Subdivision_ID"=>"categoryId",
     "Image"=>"picture",
     "Name"=>"name",
     "Vendor"=>"vendor",
     "VendorCode"=>"vendorCode",
     "Description"=>"description"
    );

  

     */
	
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
                HAVING `Price4User` > 0
                    ";

      $rows = $db->get_results($query, ARRAY_A);
      foreach ( (array)$rows as $row ) {
        
        if (strlen($row["StockUnits"])) {
           $row["Available"] = ($row["StockUnits"] ? "true" : "false" );
        }
        else {
           $row["Available"] = "true";
        }

        // convert to default currency
        $row["Price"] = $this->ConvertCurrency($row["Price4User"], $row["Currency4User"]);
        // we'll need an absolute url
        $row["URL"] = "http://".$HTTP_HOST.$SUB_FOLDER."$row[URL]";

        if ($row["Image"]) { // replace to image url
          $row["Image"] = "http://" . $HTTP_HOST.$SUB_FOLDER . nc_file_path($class_id, $row["Message_ID"], "Image", "h_");
        }

        // id=\"".sprintf("%d%05d", $class_id, $row["Message_ID"])."\"";
        //if ($row['Vendor'] || $row['VendorCode']) $ret .= " type=\"vendor.model\"";
        //$ret .= " available=\"$row[Available]\"";
        //$ret .= ">\n";

        /*foreach ($output as $idx=>$tag) {
          if ($row[$idx]) {
            if ( $tag == 'name' && $row['GroupName'] != '' ) {
              $ret .= "<$tag>".xmlspecialchars(strip_tags($row['GroupName']))." - ".xmlspecialchars(strip_tags($row[$idx]))."</$tag>\n";
            } 
            else {
              $ret .= "<$tag>".xmlspecialchars(strip_tags($row[$idx]))."</$tag>\n";
            }
           }
        }*/

		if ($row['status']==2) {
			// только те ножи, которые есть на складе
			$ret .= "\t<item>\n"; 
			$ret .= "\t\t<title>{$row['Name']}&lt;br&gt;{$row['Price']} руб.</title>\n";
			$ret .= "\t\t<link>{$row['URL']}</link>\n";
			$ret .= "\t\t<title_img>{$row['Image']}</title_img>\n";
			$ret .= "\t\t<description>{$row['ItemID']}</description>\n";
			$ret .= "\t\t<pubDate></pubDate>\n";
			//$ret .= "\t\t{$row['status']}\n";
			$ret .= "\t</item>\n";
		}
			
		
      }
    }
    // ---------------------------------------------------------------

    $ret .= "
</channel>
</rss>";
    print $ret;
    // return $ret;
   }

} //End Class Netshop_ExportYML


//---------------

$shop = new NetShop_ExportYML();
$shop->ExportYML();

?>