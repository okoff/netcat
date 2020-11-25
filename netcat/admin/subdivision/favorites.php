<?php

/* $Id: favorites.php 8385 2012-11-09 10:45:10Z vadim $ */

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");
require ($ADMIN_FOLDER."catalogue/function.inc.php");
require ($ADMIN_FOLDER."subdivision/function.inc.php");


$Delimeter = " &gt ";
$main_section = "control";
$item_id = 3;
$Title1 = "<a href=\"".$ADMIN_PATH."subdivision/favorites.php\">".CONTROL_CONTENT_SUBDIVISION_FAVORITES_TITLE."</a>";
$Title2 = CONTROL_CONTENT_SUBDIVISION_FAVORITES_TITLE;
if (!$phase) $phase = 1;

$perm->ExitIfNotAccess(NC_PERM_FAVORITE, 0, 0, 0, 1);

if (in_array($phase, array(6))) {
    if (!$nc_core->token->verify()) {
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/favorites/");
        nc_print_status(NETCAT_TOKEN_INVALID, 'error');
        EndHtml();
        exit;
    }
}

switch ($phase) {
    case 1:
        # покажем список рубрик
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/favorites/");
        $UI_CONFIG = new ui_config_favorite('list');
        ShowFavorites();
        //echo "<a href=# onclick=\"window.open('".$ADMIN_PATH."subdivision/favorites.php?phase=4','LIST','top=50, left=100,directories=no,height=600,location=no,menubar=no,resizable=no,scrollbars=yes,status=yes,toolbar=no,width=400');return false;\"><b>".CONTROL_CONTENT_SUBDIVISION_FUNCS_ADDSECTION."</b></a>";
        break;

    case 4:
    case 2:
        // покажем список разделов всего сайта для добавления в избранное
        $structure = GetSubsForFavorites(0, "get_children", $catid);
        print "<html>
		 <title>".SECTION_CONTROL_CONTENT_FAVORITES."</title>
		 <head>
		  <link type='text/css' rel='Stylesheet' href='".$ADMIN_PATH."common/main.css'>
		  <link type='text/css' rel='Stylesheet' href='".$ADMIN_TEMPLATE."css/admin.css'>
		  <script type='text/javascript' src='".$ADMIN_PATH."common/main.js'></script>
		  <script>

		    function add_to_favorites(sub_id, phase)
                    {\n";

        print "opener.frames['mainViewIframe'].location.href='".$ADMIN_PATH."subdivision/favorites.php?phase='+phase+'&subid='+sub_id;";
        print "\n}

		  </script>
		 </head>
		 <body style='margin: 15px; overflow: scroll;'>";

        ShowCataloguesForFavorites($catid, $phase);
        echo "<div id='siteTreeContainer'>";
        ShowSubsForFavorites($structure, 0, $catid, $phase);
        echo "</div>";

        echo "</body></html>";
        break;

    case 3: //Добавление в избранное
        if ($perm->isSubdivisionAdmin($subid)) AddFavorites($subid);
        header("Location: http://".$HTTP_HOST."".$ADMIN_PATH."");
        break;

    case 5: //Добавление в избранное
        if ($perm->isSubdivisionAdmin($subid)) AddFavorites($subid);
        header("Location: http://".$HTTP_HOST."".$ADMIN_PATH."subdivision/favorites.php");
        break;
    case 6:
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/favorites/");
        $UI_CONFIG = new ui_config_favorite('list');
        nc_delete_from_favorite($Delete);
        ShowFavorites();
        break;
}

if ($phase != 2 && $phase != 4) EndHtml ();