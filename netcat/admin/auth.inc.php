<?php
/* $Id: auth.inc.php 3826 2010-06-18 14:19:52Z denis $ */

$NETCAT_FOLDER = join( strstr(__FILE__, "/") ? "/" : "\\", array_slice( preg_split("/[\/\\\]+/", __FILE__), 0, -3 ) ).( strstr(__FILE__, "/") ? "/" : "\\" );
require_once ($NETCAT_FOLDER."vars.inc.php");
//require_once ($ADMIN_FOLDER.'consts.inc.php');
//require_once ($ROOT_FOLDER.'connect_io.php');
//require_once ($ADMIN_FOLDER.'permission.class.php');
/*require_once ($ADMIN_FOLDER.'personal.inc.php');*/

// load default essences
//$nc_core->load_default_extensions();

function GetCatalogueBySubdivision ($SubdivisionID) {
  global $nc_core;
  
  return $nc_core->subdivision->get_by_id($SubdivisionID, "Catalogue_ID");
}


function GetCatalogueNameByID ($CatalogueID) {
  global $nc_core;
  
  return $nc_core->catalogue->get_by_id($CatalogueID, "Catalogue_Name");
}


function GetSubdivisionNameByID ($SubdivisionID) {
  global $nc_core;
  
  return $nc_core->subdivision->get_by_id($SubdivisionID, "Subdivision_Name");
}


function GetCatalogueNameBySubdivisionID ($SubdivisionID) {
  global $db;

  return $db->get_var("SELECT c.`Catalogue_Name` FROM `Catalogue` AS c
    LEFT JOIN `Subdivision` AS s ON s.`Catalogue_ID` = c.`Catalogue_ID`
    WHERE s.`Subdivision_ID` = '".intval($SubdivisionID)."'");
}


function GetSubdivisionIDByMessageID ($MessageID, $ClassID) {
  global $db;

  return $db->get_var("SELECT `Subdivision_ID` FROM `Message".intval($ClassID)."`
    WHERE `Message_ID` = '".intval($MessageID)."'");
}


function DeleteAllPermission ($UserID) {
  global $db;

  $db->query("DELETE FROM `Permission` WHERE `User_ID` = '".intval($UserID)."'");

  return $db->rows_affected;
}


function DeleteInSubscribe ($UserID) {
  global $nc_core, $db;

  // is 'subscriber' module installed?
  if ( !$nc_core->modules->get_by_keyword('subscriber') ) return 1;

  $db->query("DELETE FROM `Subscriber` WHERE `User_ID` = '".intval($UserID)."'");
  
  return $db->rows_affected;
}


function GetEmailByUserID ($UserID) {
  global $nc_core;
  
  return $nc_core->user->get_by_id($UserID, "Email");
}


function SendEmail ($UserID, $Subject, $Message, $From) {
  global $db;

  $Email = GetEmailByUserID($UserID);
  
  mail($Email, $Subject, $Message, "From: ".$From."\nX-Mailer: PHP/" . phpversion()."\nContent-Type: text/plain\nContent-Transfer-Encoding: 8bit\n" );
}


function SendEmailFromEmail ($Email, $Subject, $Message, $From) {
  global $db;

  mail($Email, $Subject, $Message, "From: ".$From."\nX-Mailer: PHP/" . phpversion()."\nContent-Type: text/plain\nContent-Transfer-Encoding: 8bit\nReturn-Path: <".$From.">");
}

?>
