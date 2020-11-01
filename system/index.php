<?php

/* $Id: index.php 6864 2012-05-04 12:43:59Z russuckoff $ */
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -3)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");

// запрет прямого обращения
if (!function_exists('nc_strlen')) die("Unable to load file.");

// include need classes
include_once ($SYSTEM_FOLDER."nc_system.class.php");
include_once ($SYSTEM_FOLDER."nc_exception.class.php");
include_once ($SYSTEM_FOLDER."nc_core.class.php");
include_once ($SYSTEM_FOLDER."nc_ezsqlcore.class.php");
include_once ($SYSTEM_FOLDER."nc_essence.class.php");

try {
    session_start();
	
	// utm parms +OPE 2020.10.23
	parse_str(parse_url($_SERVER['REQUEST_URI'],PHP_URL_QUERY),$qry);
	if (!empty($qry)&&empty($_SESSION["UTM"])) {
		foreach ($qry as $key => $value) {
			if (!(strripos($key,"utm_")===false)) {
				$_SESSION["UTM"][$key] = iconv('utf-8//IGNORE', 'windows-1251//IGNORE', $value);
				if (empty($_SESSION["UTM"][$key])) {
					$_SESSION["UTM"][$key] = $value;
				}
//				$_SESSION["UTM"][$key] = mb_convert_encoding($value, 'windows-1251', 'auto');
			}
		}
	}
	if (!empty($_SERVER['HTTP_REFERER'])&&empty($_SESSION["HREF"])) {
		$_SESSION["HREF"] = $_SERVER['HTTP_REFERER'];
	}
	
    // initialize superior system object
    $nc_core = nc_Core::get_object();
    // load default extensions
    $nc_core->load_default_extensions();
    $nc_core->load_files();

    if ($nc_core->use_gzip_compression && $nc_core->gzip->check())
            ob_start('ob_gzhandler');
    ob_start("nc_buffer");
    header("Content-Type: ".$nc_core->get_content_type());
    //global variables
    $LinkID = &$nc_core->db->dbh;
    $parsed_url = $nc_core->url->parse_url();
    $client_url = $nc_core->url->source_url();
    extract($nc_core->input->prepare_extract());
    $_cache = array();
} catch (Exception $e) {
    //$nc_core->errorMessage($e);
    die($e->getMessage());
}

function nc_buffer($str) {
    $nc_core = nc_Core::get_object();
    return $nc_core->replace_macrofunc($str);
}