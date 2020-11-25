<?php
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
@include_once ($NETCAT_FOLDER."vars.inc.php");

class nc_cdmbank extends nc_payment  {

    protected $shop = null;
    protected $order_table_id = null;
    protected $db = null;
    protected $status_id = null;
    protected $type = null;
    protected $comment = null;
    protected $order_number = null;
    protected $payments_table_id = null;
    protected $secret_word = null;

    public function __construct($type) {
        parent::__construct($type);
		global $SKEY_CDM;
		//print_r($_GET);
		//print_r($_POST);

		//echo "nc_cdmbank.class.php";
		
		$answer=array();
		
		$body="";
		foreach ($_POST as $i => $value) {
			$body.=$i."=".$value."\n";
			$answer[$i]=$value;
		}
/*$answer['Function']="TR_1";
$answer['Result']="0";
$answer['RC']="00";
$answer['Amount']="3040";
$answer['Currency']="643";
$answer['Order']="035239";
$answer['TRType']="1";
$answer['RRN']="705285114131";
$answer['IntRef']="65CC870F6F15FB95";
$answer['AuthCode']="382570";
$answer['Fee']="0.00";
$answer['Time']="20170221151411";
$answer['P_Sign']="D423F707753E40CD1BA76C70D4E419AFFBACEE5B";

		print_r($answer);
		echo "<br>";*/

		$mailer = new CMIMEMail();
		
		// test mac
		$macdata=strlen($answer['Amount']).$answer['Amount'].
			strlen($answer['Currency']).$answer['Currency'].
			strlen($answer['Order']).$answer['Order'].
			strlen($answer['TRType']).$answer['TRType'].
			strlen($answer['Result']).$answer['Result'].
			strlen($answer['RC']).$answer['RC'].
			strlen($answer['AuthCode']).$answer['AuthCode'].
			strlen($answer['RRN']).$answer['RRN'].
			strlen($answer['IntRef']).$answer['IntRef'];
		$skey = $SKEY_CDM; //'466B3FE46B9D6030B322EEFAB03BE966';
		//echo $skey."<br>";
	//$res.= mb_detect_encoding($macdata);
	//$skey = '00112233445566778899AABBCCDDEEFF';
		$mac=hash_hmac('sha1',$macdata,hex2bin($skey)); 
		//echo $mac."<br>";
		if (strtoupper($mac)==$answer['P_Sign']) {
			// request is correct
			if ($answer['Result']==0) {
				// order is paid OK
				// get order id
				$order_id=intval($answer['Order']);
				$amount=floatval($answer['Amount']);
				// check if order is already paid
				$query="SELECT * FROM `Message{$this->order_table_id}` WHERE Message_ID=".$order_id;
				//echo $query."<br>";
				//$this->db->query($str);
				$rows = $this->db->get_results($query, ARRAY_A);
				//print_r($rows);
				//echo "<br>";
				foreach ((array) $rows as $row) {
					if ($row['paid']==0) {
						// save payment
						$qsave="UPDATE `Message{$this->order_table_id}` SET
							paid=1,
							paydate='".date("Y-m-d H:i:s")."',
							paysum={$amount}
						WHERE Message_ID={$order_id}";
						//echo $qsave."<br>";
						$this->db->query($qsave);
						
						// save payment in Netshop_CDMpayments
						$qsave="INSERT INTO Netshop_CDMpayments (created,order_id,amount,bankdate,info)
						VALUES ('".date("Y-m-d H:i:s")."',{$order_id},{$amount},'".date("Y-m-d H:i:s",strtotime($answer['Time']))."','{$body}')";
						$this->db->query($qsave);
						$body.="\n\n".$qsave;
					} else {
						// send letter about error
						$body.="\n\nUncorrect MAC!";
					}
					
				}
			}
		}
		$mailer->mailbody(strip_tags($body)); // plain/text email
		$mailer->send("elena@best-hosting.ru", "admin@russian-knife.ru", "admin@russian-knife.ru", "CDM bank payment", "RK");
		
		
/*		
AMOUNT, CURRENCY, ORDER, TRTYPE, RESULT, RC, AUTHCODE, RRN, INF_REF
		
Function=TR_1
Result=0
RC=00
Amount=3040
Currency=643
Order=035239
TRType=1
RRN=705285114131
IntRef=65CC870F6F15FB95
AuthCode=382570
Fee=0.00
Time=20170221151411
P_Sign=D423F707753E40CD1BA76C70D4E419AFFBACEE5B
*/
    }

    private function hash() {
        base64_encode(md5($_REQUEST['LMI_MERCHANT_ID'].";".$_REQUEST['LMI_PAYMENT_NO'].";".$_REQUEST['LMI_SYS_PAYMENT_ID'].";".$_REQUEST['LMI_SYS_PAYMENT_DATE'].";".$_REQUEST['LMI_PAYMENT_AMOUNT'].";".$_REQUEST['LMI_CURRENCY'].";".$_REQUEST['LMI_PAID_AMOUNT'].";".$_REQUEST['LMI_PAID_CURRENCY'].";".$_REQUEST['LMI_PAYMENT_SYSTEM'].";".$_REQUEST['LMI_SIM_MODE'].";".$this->secret_word, true));
    }

    public function check() {
        return !$_REQUEST['LMI_PREREQUEST'] && ($_REQUEST['LMI_HASH'] == $this->hash());
    }

    public function get_error_message() {
        echo 'LMI_HASH IS WRONG';
        exit;
    }

    public function update_order() {
        parent::update_order();
    }
}
?>
