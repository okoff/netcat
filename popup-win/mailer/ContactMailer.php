<?php

/**
 * Mailer: класс-хелпер, отправляет почту администратору
 */
class ContactMailer
{
	/**
     * E-mail отправителя
     * @var string
     */
    private static $emailFrom = 'admin@russian-knife.ru';
    /**
     * E-mail получателя
     * @var string
     */
    private static $emailTo = 'elena@best-hosting.ru';

    /**
     * Отправляет писмо, если письмо отправлено,
     * возвращает TRUE, в противном случае FALSE.
     * @param string $name
     * @param string $email
     * @param string $phone
     * @param string $message
     * @return boolean
     */
    public static function send($name, $email, $phone, $message, $itemid, $itemart, $itemname, $itemprice, $itemorprice, $order_id)
    {
	
		// Формируем тело письма
		$body = "Имя: " . $name . "\r\nE-mail: " . $email . "\r\nТелефон: " . $phone . "\r\nСообщение:" . $message."\r\nТовар: ".$itemart." - ".$itemname." ".$itemprice."руб.\r\n
		------------------------------------------------------
		\r\n\r\n";
		$body1="Здравствуйте, ".$name."! 
		\r\n\r\nНомер Вашего заказа: ".$order_id."
		\r\n\r\nВы заказали товар:\r\n ".$itemart." ".$itemname."  ".$itemprice."руб.
		\r\n\r\nВаш контактный телефон: " . $phone ;

		$headers='Content-type: text/plain; UTF-8'."\r\n".
			'From: Интернет-магазин Русские ножи <admin@russian-knife.ru>'."\r\n";
		
		$from="Интернет-магазин Русские ножи <admin@russian-knife.ru>";
		$subject="Быстрый заказ №".$order_id." в Интернет-магазине Русские ножи";
		
		$body1=$body1."
		\r\n\r\nС уважением,
		\r\nИнтернет-магазин \"Русские ножи\"
		\r\nТел.: +7 (495) 225-54-92,  +7 (495) 225-76-84\r\n";

		if ($email!="") {
			mail($email, $subject, iconv("utf-8","windows-1251",$body1), $headers);
		}
		mail("elena@best-hosting.ru", $subject, iconv("utf-8","windows-1251",$body.$body1), $headers);
		mail("admin@russian-knife.ru", $subject, iconv("utf-8","windows-1251",$body.$body1), $headers);




    	return true;
    }
}