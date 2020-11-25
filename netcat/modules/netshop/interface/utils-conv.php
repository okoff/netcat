<?php
// 10/02/2018 Elen
function convstr($str) {
	return iconv("windows-1251//TRANSLIT","UTF-8",$str);
}
function convstrw($str) {
	return iconv("UTF-8","windows-1251//TRANSLIT",$str);
}
?>