
<?php

class NumToText
{
  var $Mant = array(); // �������� �������
  // � ������� (�����, �����, ������)
  // ��� (����, �����, ������)
  var $Expon = array(); // �������� ���������
  // � ������� (�������, �������, ������)

  function NumToText()
  {
  }

  // ��������� �������� �������
  function SetMant($mant)
  {
     $this->Mant = $mant;
  }

  // ��������� �������� ���������
  function SetExpon($expon)
  {
     $this->Expon = $expon;
  }

  // ������� ���������� ����������� ������ �������� �������
  // (�������, ��������, ���������) ��� ����� $ins
  // �������� ��� 29 �������� 2 (���������)
  // $ins �������� ��� �����
  function DescrIdx($ins)
  {
     if(intval($ins/10) == 1) // ����� 10 - 19: 10 ���������, 17 ���������
     return 2;
     else
     {
        // ��� ��������� �������� ������� �������
        $tmp = $ins%10;
        if($tmp == 1) // 1: 21 �������, 1 �������
        return 0;
        else if($tmp >= 2 && $tmp <= 4)
        return 1; // 2-4: 62 ��������
        else
        return 2; // 5-9 48 ���������
     }
  }

  // IN: $in - �����,
  // $raz - ������ ����� - 1, 1000, 1000000 � �.�.
  // ������ ������� ����� $in ��������
  // $ar_descr - ������ �������� ������� (�������, ��������, ���������) � �.�.
  // $fem - ������� �������� ���� ������� ����� (true ��� ������)
	function DescrSot(&$in, $raz, $ar_descr, $fem = false) {
		$ret = 0;

		$conv = intval($in / $raz);
		$in %= $raz;

		$descr = $ar_descr[ $this->DescrIdx($conv%100) ];

		if($conv >= 100) {
			$Sot = array("���", "������", "������", "���������", "�������", "��������", "�������", "���������", "���������");
			$ret = $Sot[intval($conv/100) - 1]." ";
			$conv %= 100;
		}

		if($conv >= 10) {
			$i = intval($conv / 10);
			if($i == 1)
			{
			   $DesEd = array("������", "�����������", "����������", "����������","������������","����������","�����������","����������","������������","������������");
			   $ret .= $DesEd[ $conv - 10 ]." ";
			   $ret .= $descr;
			   // ������������ �����
			   return $ret;
			}
			$Des = array("��������","��������","�����","���������","����������","���������","�����������","���������");
			$ret .= $Des[$i - 2]." ";
		}

		$i = $conv % 10;
		if($i > 0) {
			if( $fem && (($i==1) || ($i==2)) ) {
			   // ��� �������� ���� (��� ���� ������)
			   $Ed = array("����", "���");
			   $ret .= $Ed[$i - 1]." ";
			}
			else
			{
			   $Ed = array("����","���","���","������","����","�����","����","������","������");
			   $ret .= $Ed[$i - 1]." ";
			}
		}
		$ret .= $descr;
		return $ret;
	}

  // IN: $sum - �����, �������� 1256.18
  function Convert($sum)
  {
     $ret =0 ;

     // ����� ������ ��������� �������� �� ���������� ������
     // ����� ������ ������������� ������ �������� �����
     $Kop = 0;
     $Rub = 0;

     $sum = trim($sum);
     // ������ ������� ������ �����
     $sum = str_replace(" ", "", $sum);

     // ���� �������������� �����
     $sign = false;
     if($sum[0] == "-")
     {
        $sum = substr($sum, 1);
        $sign = true;
     }

     // ������� ������� �� �����, ���� ��� ����
     $sum = str_replace(",", ".", $sum);

     $Rub = intval($sum);
     $Kop = $sum*100 - $Rub*100;

     if($Rub)
     {
        // �������� $Rub ���������� ������ ������� DescrSot
        // ����� ��������: $Rub %= 1000000000 ��� ���������
        if($Rub >= 1000000000)
        $ret .= $this->DescrSot($Rub, 1000000000, array("��������", "���������", "����������")) ;
        if($Rub >= 1000000)
        $ret .= $this->DescrSot($Rub, 1000000, array("�������", "��������", "���������") ) ;
        if($Rub >= 1000)
        $ret .= $this->DescrSot($Rub, 1000, array("������", "������", "�����"), true)." ";

        $ret .= $this->DescrSot($Rub, 1, $this->Mant)." ";

        // ���� ���������� �������� ������� ������ �����
        $ret[0] = chr( ord($ret[0]) + ord(A) - ord(a) );
        // ��� ��������� �������������� ������ ����� ������� ������� ������
        // � ����������������� ��������� (��� �������� �������������)
        // $ret[0] = strtoupper($ret[0]);
     }
     if($Kop < 10)
     $ret .= 0;
     $ret .= $Kop .  $this->Expon[ $this->DescrIdx($Kop) ];

     // ���� ����� ���� ������������� ������� �����
     if($sign)
     $ret = "-" . $ret;
     return $ret;
  }
}

class ManyToText extends NumToText
{
  function ManyToText()
  {
     $this->SetMant( array("�����", "�����", "������") );
     $this->SetExpon( array("�������", "�������", "������") );
  }
}

class MetrToText extends NumToText
{
  function MetrToText()
  {
     $this->SetMant( array("����", "�����", "������") );
     $this->SetExpon( array("���������", "����������", "�����������") );
  }
}

?>
<HTML>
<HEAD>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=windows-1251">
</HEAD>
<BODY>
<?php
if( isset($_POST['num']) )
{
  $mt = new ManyToText();
  //$nt = new MetrToText();
  echo $mt->Convert($_POST['num']) . "<BR />";
 // echo $nt->Convert($HTTP_POST_VARS[num]) . "<BR />";
}
?>
<FORM ACTION ="<?php echo $HTTP_SERVER_VARS[PHP_SELF]; ?>" METHOD="post">
Input number:<BR />
<INPUT TYPE="text" NAME="num"><BR />
<INPUT TYPE="submit" VALUE=" GET ">
</FORM>
</BODY></HTML>

���� �� ������������ subclassing, �� ������ $mt = new ManyToText() �������� ��: 

$mt = new NumToText();
$mt->SetMant( array(�����, �����, ������) );
$mt->SetExpon( array(�������, �������, ������) );
echo $mt->Convert($HTTP_POST_VARS[num]);

