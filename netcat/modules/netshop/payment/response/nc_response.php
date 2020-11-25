<?php

$DOCUMENT_ROOT = rtrim(getenv("DOCUMENT_ROOT"), "/\\");
require_once ($DOCUMENT_ROOT."/vars.inc.php");
require_once ($ROOT_FOLDER."connect_io.php");
require_once ($INCLUDE_FOLDER."index.php");
require_once 'nc_response.class.php';
require_once 'nc_payment.class.php';

$response = new nc_response($systemtype);

if ($response->check()) {
    $response->update_order();
} else {
    echo $response->error();
}