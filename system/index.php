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
	
	if (!empty($_GET)) {
		$qry = $_GET;
	} else if (!empty($_SERVER['REDIRECT_QUERY_STRING'])) {
		parse_str($_SERVER['REDIRECT_QUERY_STRING'],$qry);
	}

//	error_log('system index.php _qry='.var_export($qry,true));
	
	// utm parms +OPE
	if (!empty($qry)&&empty($_SESSION["UTM"])) {
		foreach ($qry as $key => $value) {
			if (!(strripos($key,"utm_")===false)) {
				$_SESSION["UTM"][$key] = mb_convert_encoding($value, 'windows-1251', 'auto');
			}
		}
		if (!empty($_SESSION["UTM"])) {
			$_SESSION["HREF"] = $_SERVER['HTTP_REFERER'];
		}
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