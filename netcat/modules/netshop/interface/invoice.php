<?php
// 24.03.2014 Elen
// счет 
include_once ("../../../../vars.inc.php");
session_start();
include_once ("utils.php");
include_once ("utils-waybill.php");
$incoming = parse_incoming();
$con = mysql_connect($MYSQL_HOST,$MYSQL_USER,$MYSQL_PASSWORD);
if (!$con) {
	die('Could not connect: ' . mysql_error());
}

class NumToText
{
  var $Mant = array(); // описания мантисс
  // к примеру (рубль, рубля, рублей)
  // или (метр, метра, метров)
  var $Expon = array(); // описания экспонент
  // к примеру (копейка, копейки, копеек)

  function NumToText()
  {
  }

  // установка описания мантисс
  function SetMant($mant)
  {
     $this->Mant = $mant;
  }

  // установка описания экспонент
  function SetExpon($expon)
  {
     $this->Expon = $expon;
  }

  // функция возвращает необходимый индекс описаний разряда
  // (миллион, миллиона, миллионов) для числа $ins
  // например для 29 вернется 2 (миллионов)
  // $ins максимум два числа
  function DescrIdx($ins)
  {
     if(intval($ins/10) == 1) // числа 10 - 19: 10 миллионов, 17 миллионов
     return 2;
     else
     {
        // для остальных десятков возьмем единицу
        $tmp = $ins%10;
        if($tmp == 1) // 1: 21 миллион, 1 миллион
        return 0;
        else if($tmp >= 2 && $tmp <= 4)
        return 1; // 2-4: 62 миллиона
        else
        return 2; // 5-9 48 миллионов
     }
  }

  // IN: $in - число,
  // $raz - разряд числа - 1, 1000, 1000000 и т.д.
  // внутри функции число $in меняется
  // $ar_descr - массив описаний разряда (миллион, миллиона, миллионов) и т.д.
  // $fem - признак женского рода разряда числа (true для тысячи)
	function DescrSot(&$in, $raz, $ar_descr, $fem = false) {
		$ret = 0;

		$conv = intval($in / $raz);
		$in %= $raz;

		$descr = $ar_descr[ $this->DescrIdx($conv%100) ];

		if($conv >= 100) {
			$Sot = array("сто", "двести", "триста", "четыреста", "пятьсот", "шестьсот", "семьсот", "восемьсот", "девятьсот");
			$ret = $Sot[intval($conv/100) - 1]." ";
			$conv %= 100;
		}

		if($conv >= 10) {
			$i = intval($conv / 10);
			if($i == 1)
			{
			   $DesEd = array("десять", "одиннадцать", "двенадцать", "тринадцать","четырнадцать","пятнадцать","шестнадцать","семнадцать","восемнадцать","девятнадцать");
			   $ret .= $DesEd[ $conv - 10 ]." ";
			   $ret .= $descr;
			   // возвращаемся здесь
			   return $ret;
			}
			$Des = array("двадцать","тридцать","сорок","пятьдесят","шестьдесят","семьдесят","восемьдесят","девяносто");
			$ret .= $Des[$i - 2]." ";
		}

		$i = $conv % 10;
		if($i > 0) {
			if( $fem && (($i==1) || ($i==2)) ) {
			   // для женского рода (сто одна тысяча)
			   $Ed = array("одна", "две");
			   $ret .= $Ed[$i - 1]." ";
			}
			else
			{
			   $Ed = array("один","два","три","четыре","пять","шесть","семь","восемь","девять");
			   $ret .= $Ed[$i - 1]." ";
			}
		}
		$ret .= $descr;
		return $ret;
	}

  // IN: $sum - число, например 1256.18
  function Convert($sum)
  {
     $ret =0 ;

     // имена данных перменных остались от предыдущей версии
     // когда скрипт конвертировал только денежные суммы
     $Kop = 0;
     $Rub = 0;

     $sum = trim($sum);
     // удалим пробелы внутри числа
     $sum = str_replace(" ", "", $sum);

     // флаг отрицательного числа
     $sign = false;
     if($sum[0] == "-")
     {
        $sum = substr($sum, 1);
        $sign = true;
     }

     // заменим запятую на точку, если она есть
     $sum = str_replace(",", ".", $sum);

     $Rub = intval($sum);
     $Kop = $sum*100 - $Rub*100;

     if($Rub)
     {
        // значение $Rub изменяется внутри функции DescrSot
        // новое значение: $Rub %= 1000000000 для миллиарда
        if($Rub >= 1000000000)
        $ret .= $this->DescrSot($Rub, 1000000000, array("миллиард", "миллиарда", "миллиардов")) ;
        if($Rub >= 1000000)
        $ret .= $this->DescrSot($Rub, 1000000, array("миллион", "миллиона", "миллионов") ) ;
        if($Rub >= 1000)
        $ret .= $this->DescrSot($Rub, 1000, array("тысяча", "тысячи", "тысяч"), true)." ";

        $ret .= $this->DescrSot($Rub, 1, $this->Mant)." ";

        // если необходимо поднимем регистр первой буквы
        $ret[0] = chr( ord($ret[0]) + ord(A) - ord(a) );
        // для корректно локализованных систем можно закрыть верхнюю строку
        // и раскомментировать следующую (для легкости сопровождения)
        // $ret[0] = strtoupper($ret[0]);
     }
     if($Kop < 10)
     $ret .= 0;
     $ret .= $Kop ." ". $this->Expon[ $this->DescrIdx($Kop) ];

     // если число было отрицательным добавим минус
     if($sign)
     $ret = "-" . $ret;
     return $ret;
  }
}

class ManyToText extends NumToText
{
  function ManyToText()
  {
     $this->SetMant( array("рубль", "рубля", "рублей") );
     $this->SetExpon( array("копейка", "копейки", "копеек") );
  }
}

class MetrToText extends NumToText
{
  function MetrToText()
  {
     $this->SetMant( array("метр", "метра", "метров") );
     $this->SetExpon( array("сантиметр", "сантиметра", "сантиметров") );
  }
}

mysql_select_db($MYSQL_DB_NAME, $con);
//mysql_set_charset("cp1251", $con);
mysql_set_charset("utf8", $con);

$sql="SELECT * FROM Netshop_Details";
if ($res=mysql_query($sql)) {
	$row=mysql_fetch_array($res);
}	

$sql="SELECT * FROM User_codetails INNER JOIN Message51 ON (Message51.User_ID=User_codetails.user_id) WHERE Message51.Message_ID=".intval($incoming['oid']);
if ($rs=mysql_query($sql)) {
	$row1=mysql_fetch_array($rs);
}	
?>
<!doctype html>
<html>
<head>
    <title>Счет на оплату</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        body { width: 210mm; margin-left: auto; margin-right: auto;font-size:12px;font-family:Tahoma;}
		td {text-align:left;}
		.brd {border-bottom:1px solid #000;}
        table.invoice_items { border: 1px solid; border-collapse: collapse;}
        table.invoice_items td, table.invoice_items th { border: 1px solid;}
    </style>
</head>
<body>
<div style="font-weight: bold; font-size: 16pt; padding-left:5px;">
    Счет № 0 от <?php echo date("d.m.Y"); ?></div>
<br/>
<table width="100%" cellpadding="1" cellspacing="0" border="0">
<tr><td style="width:10%">&nbsp;</td><td style="width:10%">&nbsp;</td><td style="width:10%">&nbsp;</td><td style="width:10%">&nbsp;</td><td style="width:10%">&nbsp;</td><td style="width:10%">&nbsp;</td><td style="width:10%">&nbsp;</td><td style="width:10%">&nbsp;</td><td style="width:10%">&nbsp;</td><td style="width:10%">&nbsp;</td></tr>
<tr><td>Поставщик</td><td style="font-size:16px;" colspan="9" class="brd"><?php echo $row['name']; ?></td></tr>
<tr><td>Адрес</td><td colspan="5" style="font-size:16px;"  class="brd"><?php echo $row['address_ur']; ?></td>
	<td style="text-align:right;">Телефон</td><td colspan="3" style="font-size:16px;" class="brd"><?php echo $row['phone']; ?></td></tr>
<tr><td colspan="4">Индентификационный номер поставщика (ИНН)</td><td style="font-size:16px;" colspan="6" class="brd"><?php echo $row['inn_kpp']; ?></td></tr>
<tr><td colspan="2">Расчетный счет</td><td style="font-size:16px;" class="brd" colspan="3"><?php echo $row['count_rasch']; ?></td>
	<td style="text-align:center;">в</td><td style="font-size:16px;" colspan="4" class="brd"><?php echo $row['bank']; ?></td></tr>
<tr><td>Город</td><td style="font-size:16px;" colspan="9" class="brd">г. Москва</td></tr>
<tr><td>БИК</td><td style="font-size:16px;" class="brd" colspan="4"><?php echo $row['bik']; ?></td>
	<td>Кор. счет</td><td style="font-size:16px;" colspan="4" class="brd"><?php echo $row['count_korr']; ?></td></tr>
<tr><td colspan="3">Грузоотправитель и его адрес</td><td style="font-size:16px;" colspan="7" class="brd"><?php echo $row['name']; ?></td></tr>
<tr><td colspan="3">Грузополучатель и его адрес</td><td style="font-size:16px;" colspan="7" class="brd">&nbsp;</td></tr>
<tr><td colspan="3">К платежно-расчетному документу №</td><td style="font-size:16px;" class="brd" colspan="2">&nbsp;</td>
	<td style="text-align:right;">от</td><td style="font-size:16px;" class="brd" colspan="4">&nbsp;</td></tr>
</table>
<br>
<table width="100%" cellpadding="1" cellspacing="0" border="0">
<tr><td style="width:10%">&nbsp;</td><td style="width:10%">&nbsp;</td><td style="width:10%">&nbsp;</td><td style="width:10%">&nbsp;</td><td style="width:10%">&nbsp;</td><td style="width:10%">&nbsp;</td><td style="width:10%">&nbsp;</td><td style="width:10%">&nbsp;</td><td style="width:10%">&nbsp;</td><td style="width:10%">&nbsp;</td></tr>
<tr><td>Получатель</td><td style="font-size:16px;" colspan="9" class="brd"><?php echo $row1['name']; ?></td></tr>
<tr><td>Адрес</td><td colspan="5" style="font-size:16px;"  class="brd"><?php echo $row1['address_ur']; ?></td>
	<td style="text-align:right;">Телефон</td><td colspan="3" style="font-size:16px;" class="brd"><?php echo $row1['phone']; ?></td></tr>
<tr><td colspan="4">Индентификационный номер получателя (ИНН)</td><td style="font-size:16px;" colspan="6" class="brd"><?php echo $row1['inn_kpp']; ?></td></tr>
<tr><td colspan="2">Расчетный счет</td><td style="font-size:16px;" class="brd" colspan="3"><?php echo $row1['count_rasch']; ?></td>
	<td style="text-align:center;">в</td><td style="font-size:16px;" colspan="4" class="brd"><?php echo $row1['bank']; ?></td></tr>
<tr><td>БИК</td><td style="font-size:16px;" class="brd" colspan="4"><?php echo $row1['bik']; ?></td>
	<td>Кор. счет</td><td style="font-size:16px;" colspan="4" class="brd"><?php echo $row1['count_korr']; ?></td></tr>
<tr><td colspan="7">Дополнение (условия оплаты по договору (контракту), способ отправления и т.п.</td><td colspan="3" class="brd">&nbsp;</td></tr>
<tr><td colspan="10" class="brd">&nbsp;</td></tr>
<tr><td colspan="10" class="brd">&nbsp;</td></tr>
</table>
<br><br>

<table width="100%" cellpadding="2" cellspacing="0" border="0" class="invoice_items">
    <thead>
    <tr>
        <th style="width:30%;">Наименование товара</th>
        <th style="width:10%;">Ед. измерения</th>
        <th style="width:10%;">Кол-во</th>
        <th style="width:10%;">Цена</th>
        <th style="width:10%;">Сумма</th>
        <th style="width:10%;">Ставка НДС</th>
        <th style="width:10%;">Сумма НДС</th>
        <th style="width:10%;">Всего с НДС</th>
    </tr>
    </thead>
    <tr><td style="text-align:center;">1</td><td style="text-align:center;">2</td><td style="text-align:center;">3</td><td style="text-align:center;">4</td><td style="text-align:center;">5</td><td style="text-align:center;">6</td><td style="text-align:center;">7</td><td style="text-align:center;">8</td></tr>

<?php
$sql="SELECT Netshop_OrderGoods.*,Message57.Name,Message57.ItemID FROM Netshop_OrderGoods 
	INNER JOIN Message57 ON (Message57.Message_ID=Netshop_OrderGoods.Item_ID)
	WHERE Order_ID=".intval($incoming["oid"]);
//echo $sql;
$rst=mysql_query($sql);
$count=0;
$fullprice=0;
while ($row2=mysql_fetch_array($rst)) {
?>
		<tr>
			<td><b>Арт. <?php echo $row2["ItemID"]; ?> <?php echo $row2["Name"]; ?></b></td>
			<td style="text-align:center;">шт.</td>
			<td style="text-align:center;"><?php echo $row2["Qty"]; ?></td>
			<td style="text-align:center;"><?php echo $row2["ItemPrice"]; ?></td>
			<td style="text-align:center;"><?php echo $row2["ItemPrice"]*$row2["Qty"]; ?></td>
			<td style="text-align:center;">&nbsp;</td>
			<td style="text-align:center;">&nbsp;</td>
			<td style="text-align:center;"><?php echo $row2["ItemPrice"]*$row2["Qty"]; ?></td>
		</tr>
<?php
	$count=$count+$row2["Qty"];
	$fullprice=$fullprice+$row2["ItemPrice"]*$row2["Qty"];
}	
?>
<tr>
	<td colspan="2"><b>Итого:</b></td>
	<td style="text-align:center;"><b><?php echo $count; ?></b></td>
	<td colspan="4">&nbsp;</td>
	<td style="text-align:center;"><b><?php echo $fullprice; ?></b></td>
</tr>
</table>
<br>
<p><b>Доставка: бесплатно</b></p>

<p>Сумма к оплате (прописью):<u>
<?php
	$mt = new ManyToText();
	echo $mt->Convert($fullprice);
?></u></p>
<p>НДС не облагается</p>
<br>
<br>
<table cellpadding="0" border="0" style="width:100%">
<tr>
<td style="width:60%">
<div>Руководитель предприятия<br><br>______________________ (Красников А.И.)</div>
<br>
<br>
ПОЛУЧИЛ
</td><td style="width:40%;">
<div>Главный бухгалтер<br><br>______________________ (Красников А.И.)</div>
<br>
<br>
ВЫДАЛ
</td></tr>
</table>


</body>
</html>
