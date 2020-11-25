<?php
/* $Id: language.inc.php 3779 2010-05-05 09:52:01Z denis $ */

/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
function LoadAdminLanguage() {
  global $ADMIN_FOLDER,$AUTH_LANG,$PHP_AUTH_LANG, $ADMIN_LANGUAGE;

  if (preg_match("/^\w+$/", $AUTH_LANG)) {
    require_once($ADMIN_FOLDER."lang/".$AUTH_LANG.".php");
  }
  elseif (preg_match("/^\w+$/", $PHP_AUTH_LANG)) {
    require_once($ADMIN_FOLDER."lang/".$PHP_AUTH_LANG.".php");
  }
  elseif (preg_match("/^\w+$/", $_SESSION['User']['PHP_AUTH_LANG'])) {
    require_once($ADMIN_FOLDER."lang/".$_SESSION['User']['PHP_AUTH_LANG'].".php");
  }
  else {
    if (!$PHP_AUTH_LANG) {
      $PHP_AUTH_LANG = NetCat_language_detect();
    }
    if ($PHP_AUTH_LANG && preg_match("/^\w+$/", $PHP_AUTH_LANG) ) {
      require_once($ADMIN_FOLDER."lang/".$PHP_AUTH_LANG.".php");
    }
    else if ( preg_match("/^\w+$/", $ADMIN_LANGUAGE) )  {
      require_once($ADMIN_FOLDER."lang/".$ADMIN_LANGUAGE.".php");
    }
    else {
      die("Failed to determine language");
    }
}

}
?>
