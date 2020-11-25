<?php

/* $Id: collection.php 5700 2011-11-17 07:33:57Z gaika $ */

/**
 * Список ссылок, обрабатываемых в текущей задаче.
 */
class nc_search_indexer_link_collection extends nc_search_data_persistent_collection {

  protected $data_class = 'nc_search_indexer_link';
  protected $index_by = 'url';
  
  /**
   * 
   */
  public function __construct() {
    $this->select_from_database("SELECT * FROM `%t%`");
  }
  
  /**
   *
   * @param array $urls
   * @param string $referrer
   * @return array array with link IDs
   */
  public function add_links(array $urls, $referrer = null) {
    $link_ids = array();
    foreach ($urls as $url) { $link_ids[] = $this->add_link($url, $referrer); }
    return $link_ids;
  }
  
  /**
   * Создает объект nc_search_indexer_link и добавляет его в коллекцию
   * @param string $url
   * @param string  $referrer
   * @return integer Link ID
   */
  public function add_link($url, $referrer = null) {
    $full_url = $this->resolve_link($url, $referrer);
    
    // считать URL’ы с "www." и без него синонимами (просто выкинуть "www.",
    // к сожалению, нельзя — не у всех правильно настроен сервер)
    $has_www = strpos($full_url, "://www.");
    $full_url_with_www =    $has_www ? $full_url : str_replace("://", "://www.", $full_url);
    $full_url_without_www = $has_www ? str_replace("://www.", "://", $full_url) : $full_url;

    if ($this->offsetExists($full_url_with_www)) {
      $link = $this->offsetGet($full_url_with_www);
    }
    elseif ($this->offsetExists($full_url_without_www)) {
      $link = $this->offsetGet($full_url_without_www);
    }
    else {
      $link = new nc_search_indexer_link(array("url" => $full_url));
      $link->save();
      $this->add($link);
      
      if (nc_search::will_log(nc_search::LOG_PARSER_DOCUMENT_LINKS)) {
        nc_search::log(nc_search::LOG_PARSER_DOCUMENT_LINKS, 
                       "Added link to the queue: " . nc_search_util::decode_url($full_url));
      }
    }

    return $link->get_id();
  }
  
  /**
   * Получить абсолютный URL
   * @param string $href
   * @param string $referrer
   * @return string
   * @throws nc_search_exception
   */
  protected function resolve_link($href, $referrer = null) {
    $href_parts = parse_url($href);
    if (!is_array($href_parts)) { $href_parts = array(); } // $href == "#"
    $result_parts = $href_parts;
    
    if (!isset($href_parts["host"])) { // path with no host name
		if ($referrer == 'http:///')  { return false; }
		if ($referrer == 'http://') { return false; }
      $referrer_parts = parse_url($referrer);
      if (!$referrer_parts || !isset($referrer_parts["host"])) {
        throw new nc_search_exception("Cannot resolve full URL: '$href' (no referrer)");
      }
      
      foreach (array("scheme", "host", "port", "path") as $p) {
        if (isset($referrer_parts[$p]) && !isset($href_parts[$p])) { 
          $result_parts[$p] = $referrer_parts[$p]; 
        }
      }
      
      if ($result_parts["path"][0] != "/") { // relative path
        $referrer_dir = (substr($referrer_parts["path"], -1) == '/') ? // referrer path ends with '/'
                           $referrer_parts["path"] :
                           dirname($referrer_parts["path"]) . "/";
        $result_parts["path"] = $referrer_dir . $result_parts["path"];
      }
    } // end of "path with no host name"

    // "http://mysite.org" → "http://mysite.org/"
    if (!isset($result_parts["path"])) { $result_parts["path"] = "/"; }
    
    // get rid of "./", "../"
    if (strpos($result_parts["path"], "./") !== false) {
      $path_fragments = array();
      foreach (explode("/", $result_parts["path"]) as $part) {
        if ($part == '.' || $part == '') { continue; }
        if ($part == '..') { array_pop($path_fragments); }
        else { $path_fragments[] = $part; }
      }
      $path = join("/", $path_fragments);
      if (substr($href_parts["path"], -1) == '/') { $path .= "/"; }
      if ($path[0] != '/') { $path = "/$path"; }
      $result_parts["path"] = $path;
    }
    
    // Производится сортировка параметров для того, чтобы не запрашивать страницу
    // дважды, если в ссылках на неё параметры перечислены в разном порядке, например:
    // /sub/?tag=22&curPos=10 и /sub/?curPos=10&tag=22 будут считаться одной страницей
    // Параметр модуля: IndexerNormalizeLinks
    if (isset($result_parts["query"]) && strpos($result_parts["query"], "&") && nc_search::should('IndexerNormalizeLinks')) {
      $params = explode("&", $result_parts["query"]);
      sort($params);
      $result_parts["query"] = join("&", $params);
    }

    // IDN & non-latin paths
    $result_parts["host"] = nc_search_util::encode_host($result_parts["host"]);
    $result_parts["path"] = nc_search_util::encode_path($result_parts["path"]);

    // MySite.ORG == mysite.org
    $result_parts["host"] = strtolower($result_parts["host"]);
    
    $full_url = strtolower($result_parts["scheme"]) . "://" .
                $result_parts["host"] . 
                (isset($result_parts["port"]) ? ":$result_parts[port]" : "") . 
                $result_parts["path"] . 
                (isset($result_parts["query"]) ? "?$result_parts[query]" : "");
    
    return $full_url;
  }

  /**
   *
   * @return nc_search_indexer_link|null
   */
  public function get_next_link() {
    return $this->first('is_processed', false);
  }

  /**
   * For logging purposes
   * @return array
   */
  public function to_string() {
    $links = array();
    foreach (array_keys($this->items) as $url) {
      $links[] = nc_search_util::decode_url($url);
    }
    return $links;
  }

}
