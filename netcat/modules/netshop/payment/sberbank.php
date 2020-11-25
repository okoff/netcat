<?

class Payment_sberbank {

    private $shop = null;
    private $params = array();

    function __construct(&$shop) { // constructor
        $this->shop = &$shop;
    }

    function crc() {
        return md5($this->shop->secret_key.
                $this->shop->OrderID
        );
    }

    function create_bill($to_string = false) {
        global $SUB_FOLDER, $HTTP_ROOT_PATH;
        $form = "<br><br>
       <form id='fsberbank' action='".$SUB_FOLDER.$HTTP_ROOT_PATH."modules/netshop/post.php' method=get target=_blank>
        <input type=hidden name=action value=print_bill>
        <input type=hidden name=system value=sberbank>
        <input type=hidden name=mode value=print_bill>
        <input type=hidden name=order_id value=".$this->shop->OrderID.">
        <input type=hidden name=key value=".$this->crc().">";
        if (!$to_string) {
            echo $form;
            echo "<input type=submit value='����������� ���������'></form>";
            return true;
        } else {
            return $form.'</form>';
        }
    }

    // ������ ��������� ��� ������ ����� ��������
    function print_bill() {
        if ($_GET["key"] != $this->crc()) die(NETCAT_MODULE_NETSHOP_NO_RIGTHS);

        $RUR = $this->shop->CurrencyDetails["RUR"]["Currency"];
		//print_r($this->shop);
		//echo "<br><br>";
		//print_r($this->shop->CartDiscounts);
		//echo "<br><br>";
		
		$ordersum=$this->shop->Order["DeliveryCost"];
		foreach ($this->shop->CartContents as $item) {
			$idiscount=0;
			foreach($item['Discounts'] as $discount) {
				$idiscount=$idiscount+$discount['Sum'];
			}
			$ordersum=$ordersum+$item['OriginalPrice']*$item['Qty']-$idiscount;
			//echo $ordersum."<br>";
		}
		foreach ($this->shop->CartDiscounts as $cartdiscount) {
			$ordersum=$ordersum-$cartdiscount["Sum"];
		}
        $sum = $this->shop->ConvertCurrency($ordersum, $this->shop->DefaultCurrencyID, $RUR);
        $sum = $this->shop->FormatCurrency($ordersum, 'RUR');
?>
        <table CELLSPACING="0" BORDER="1" CELLPADDING="3" WIDTH="640" bordercolorlight="#000000" bordercolordark="#FFFFFF">
            <tr>
                <td ALIGN="left" WIDTH="240" VALIGN="middle">
                    &nbsp;&nbsp;<b>���������</b>
                    <br><br><br><br><br><br><br><br><br><br><br><br><br>
                    &nbsp;&nbsp;������<br>
                </td>
                <td ALIGN="right" WIDTH="400" VALIGN="middle">

                    <table CELLSPACING="0" BORDER="1" CELLPADDING="3" WIDTH="410" bordercolorlight="#000000" height=100% bordercolordark="#FFFFFF">
                        <tr>
                            <td colspan="3">
                                ���������� �������: <?= $this->shop->CompanyName
?><br>
                                ��� <?= $this->shop->INN
?><br>
                                �/c: <?= $this->shop->BankAccount
?>, <?= $this->shop->BankName
?><br>
                                ����.��.: <?= $this->shop->CorrespondentAccount
?><br>
                                ���: <?= $this->shop->KPP
?><br>
                                ���: <?= $this->shop->BIK
?></td>
                        </tr>
                        <tr>
                            <td COLSPAN="3">
                                <br><br>
                                <hr size="1" color="#000000">
                                <div align="center" style="font-family: sans-serif; font-size: xx-small">�������, �. �., �����</div>
                            </td>
                        </tr>
                        <tr>
                            <td ALIGN="center">��� �������</td>
                            <td ALIGN="center" width=15%>����</td>
                            <td ALIGN="center" width=15%>�����</td>
                        </tr>
                        <tr>
                            <td ALIGN="left"><?
        printf(NETCAT_MODULE_NETSHOP_PAYMENT_DESCRIPTION, $this->shop->OrderID, $this->shop->ShopName);
?></td>
                    <td valign="bottom">__________</td>
                    <td valign="bottom"><?= $sum
?></td>
                </tr>
                <tr>
                    <td ALIGN="left" ROWSPAN="2" colspan="3" valign="center">����������:</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td ALIGN="left" WIDTH="240" VALIGN="middle">
            &nbsp;&nbsp;<b>���������</b>
            <br><br><br><br><br><br><br><br><br><br><br><br><br>
            &nbsp;&nbsp;������<br>
            </span></td>
        <td ALIGN="right" VALIGN="middle">
            <table CELLSPACING="0" BORDER="1" CELLPADDING="3" WIDTH="410" height=100% bordercolorlight="#000000" bordercolordark="#FFFFFF">
                <tr>
                    <td colspan="3">
                        ���������� �������: <?= $this->shop->CompanyName
?><br>
                        ��� <?= $this->shop->INN
?><br>
                        �/c: <?= $this->shop->BankAccount
?>, <?= $this->shop->BankName
?><br>
                        ����.��.: <?= $this->shop->CorrespondentAccount
?><br>
                        ���: <?= $this->shop->KPP
?><br>
                        ���: <?= $this->shop->BIK
?></td>

                    </td>
                </tr>
                <tr><td COLSPAN="3"><br><br>
                        <hr size="1" color="#000000">
                        <div align="center" style="font-family: sans-serif; font-size: xx-small">�������, �. �., �����</div>
                    </td>
                </tr>
                <tr>
                    <td ALIGN="center">��� �������</td>
                    <td ALIGN="center">����</td>
                    <td ALIGN="center">�����</td>
                </tr>
                <tr>
                    <td ALIGN="left"><?
        printf(NETCAT_MODULE_NETSHOP_PAYMENT_DESCRIPTION, $this->shop->OrderID, $this->shop->ShopName);
?></td>
                            <td valign="bottom">__________</td>
                            <td valign="bottom"><?= $sum
?></td>
                </tr>
                <tr>
                    <td ALIGN="left" ROWSPAN="2" colspan="3" valign="center">����������:</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<p><b><sup>*</sup>��������� ����� ���������� ������ ����� ������������� ������ ����������!</b></p>
<p><b><sup>**</sup>����� ����������� �� 3 ������� ���.</b></p>
<?
}

}
?>