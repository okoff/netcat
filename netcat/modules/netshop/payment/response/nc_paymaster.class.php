<?php

class nc_paymaster extends nc_payment {

    protected $order_number = null;
    protected $status_message = null;
    protected $status_id = null;
    protected $comment = null;
    protected $secret_word = null;

    public function __construct($type) {
        parent::__construct($type);
        $this->order_number = intval($_REQUEST["LMI_PAYMENT_NO"]);
        $this->status_message = 'PAID';
        $this->status_id = 3;
        $this->comment = NETCAT_MODULE_NETSHOP_TRANSACTION_NUMBER.' Paymaster '.intval($_REQUEST['LMI_PAYMENT_NO']);
        $this->secret_word = $this->shop->PaymasterWord;
		
		foreach ($_POST as $i => $value) {
			$body.=$i."=".$value."\n";
			$answer[$i]=$value;
		}

		$LMIPreRequest = $answer["LMI_PREREQUEST"];
		$LMIPayeePurse = $answer["LMI_PAYEE_PURSE"];
		$LMIPaymentAmount =  $answer["LMI_PAYMENT_AMOUNT"];
		$amount = floatval($answer["LMI_PAYMENT_AMOUNT"]);
		$order_id = intval($answer["LMI_PAYMENT_NO"]);
		$LMIMode = $answer["LMI_MODE"];
		$LMISysInvsNo = $answer["LMI_SYS_INVS_NO"];
		$LMISysTransNo = $answer["LMI_SYS_TRANS_NO"];
		$LMISysTransDate = $answer["LMI_SYS_TRANS_DATE"];
		$LMIPayerPurse = $answer["LMI_PAYER_PURSE"];
		$LMIPayerWM = $answer["LMI_PAYER_WM"];
		$LMISecretKey = "IUHLBN108ylKJHg12";
		$LMIHash = $answer["LMI_HASH"];

		$mailer = new CMIMEMail();
		
		$query="SELECT * FROM `Message{$this->order_table_id}` WHERE Message_ID=".$order_id;
		//$body.="\n\n".$query;
		//$this->db->query($str);
		$rows = $this->db->get_results($query, ARRAY_A);
		//print_r($rows);
		//echo "<br>";
		foreach ((array) $rows as $row) {
			//if ($row['paid']==0) {
				// save payment
				$qsave="UPDATE `Message{$this->order_table_id}` SET
					paid=1,
					paydate='".date("Y-m-d H:i:s")."',
					paysum={$amount}
				WHERE Message_ID={$order_id}";
				//$body.="\n\n".$qsave;
				$this->db->query($qsave);
				
				// save payment in Netshop_CDMpayments
				$qsave="INSERT INTO Netshop_ppayments (created,order_id,amount,bankdate,info)
				VALUES ('".date("Y-m-d H:i:s")."',{$order_id},{$amount},'".date("Y-m-d H:i:s",strtotime($answer['LMI_SYS_PAYMENT_DATE']))."','{$body}')";
				$this->db->query($qsave);
				//$body.="\n\n".$qsave;
			//} 			
		}
		$mailer->mailbody(strip_tags(iconv("utf-8","windows-1251",$body))); // plain/text email
		$mailer->send("elena@best-hosting.ru", "admin@russian-knife.ru", "admin@russian-knife.ru", "Paymaster payment", "RK");
		
		$bodym="Получена оплата по заказу {$order_id} на сумму {$amount} через систему Paymaster\n\n";
		$mailer->mailbody(strip_tags(iconv("utf-8","windows-1251",$bodym))); 
		$mailer->send("admin@russian-knife.ru", "admin@russian-knife.ru", "admin@russian-knife.ru", "Paymaster payment", "RK");
		$mailer->send("elena@best-hosting.ru", "admin@russian-knife.ru", "admin@russian-knife.ru", "Paymaster payment", "RK");
		
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
