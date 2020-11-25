<?

// Netshop POST request handler
// (a) Change Cart Contents
// (b) Process Payment Events
// (c) Output Documents (e.g. bill/sberbank reciept)

error_reporting(E_ALL ^ E_NOTICE);

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require_once ($INCLUDE_FOLDER."index.php");

$client_url = urldecode("http://".$HTTP_HOST.$REQUEST_URI);
$parsed_url = parse_url($client_url);
$current_catalogue = $nc_core->catalogue->get_by_host_name($parsed_url['host']);
$catalogue = $current_catalogue["Catalogue_ID"];
if (!$catalogue) $catalogue = 1; // first site
//LoadModuleEnv();
$MODULE_VARS = $nc_core->modules->get_module_vars();


// Process cart changes
if ($_POST["cart"]) {
    $shop->CartPut($_POST["cart"], $_POST["cart_mode"], $_POST['nc_cart_params']);
    $redirect_url = ($_POST["redirect_url"] ? $_POST["redirect_url"] : $HTTP_REFERER);

    if ($redirect_url) {
        ob_end_clean();

        if ($AUTHORIZATION_TYPE == "session" && ini_get("session.use_trans_sid")) {
            $netshop_sid = session_name();

            if (!strpos($redirect_url, "$netshop_sid=")) {
                $redirect_url .= ( strpos($redirect_url, "?") ? "&" : "?").
                        "$netshop_sid=$GLOBALS[$netshop_sid]";
            }
        }

        session_write_close();

        Header("Location: $redirect_url");
        die();
    }
}

// Payment documents (bills etc.)
if ($action == "print_bill") {
    $order_id = (int) $order_id;
    if ($order_id) {
        $shop->LoadOrder($order_id);
        $shop->Payment($system, $mode);
    }
} elseif ($_GET["system"]) {
    // Process replies from Payment Systems
    $stage = ($_GET["failed"] ? "failed" : "success");
    $shop->Payment($_GET["system"], $stage);
}
?>