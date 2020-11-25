<?php

/* $Id: cron.php 7302 2012-06-25 21:12:35Z alive $ */

$param = $_GET['param'];

// Укажите значение параметра, заданного в 'Управление задачами'
$check = "test";

if ($check != $param) {
    echo "Non-authorized access!";
    exit;
}

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ROOT_FOLDER."connect_io.php");



$MODULE_VARS = $nc_core->modules->get_module_vars();

include_once ($MODULE_FOLDER."subscriber/nc_subscriber_tools.class.php");
include_once ($MODULE_FOLDER."subscriber/nc_subscriber.class.php");
include_once ($MODULE_FOLDER."subscriber/nc_subscriber_send.class.php");
include_once ($MODULE_FOLDER."cache/function.inc.php");

require_once($MODULE_FOLDER."subscriber/ru.lang.php");

if ($nc_core->modules->get_by_keyword('auth')) {
    include_once ($MODULE_FOLDER."auth/function.inc.php");
}


if ($nc_core->modules->get_by_keyword('forum2', 0)) {
    include_once ($MODULE_FOLDER."forum2/nc_forum2.class.php");
    include_once ($MODULE_FOLDER."forum2/nc_forum2_parent.class.php");
    include_once ($MODULE_FOLDER."forum2/nc_forum2_forum.class.php");
    include_once ($MODULE_FOLDER."forum2/nc_forum2_topic.class.php");
    include_once ($MODULE_FOLDER."forum2/nc_forum2_reply.class.php");
}







try {
    $s = nc_subscriber_send::get_object();

    if ($s->is_blocked()) {
        die("Blocked");
    }

    $s->block();
    $nc_subscriber = nc_subscriber::get_object();

    $s->formation_service();

    $s->send();
    $s->send_periodical();
    $s->send_prepared();


    $s->update_stats();

    if (rand(0, 20) == 5) {
        $nc_subscriber->delete_expire();
    }
} catch (Exception $e) {
    echo $e->getMessage();
}

$s->unlock();
print "Done.";