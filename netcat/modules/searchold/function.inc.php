<?php
/* $Id: function.inc.php 4028 2010-10-01 08:36:09Z denis $ */

# обрезаем текст
function cuttext($text, $numchar)
{
	$text=strip_tags($text);
	$text=htmlspecialchars($text);

	if (nc_strlen($text)>$numchar)
	{
		$tmptext = strpos($text." "," ",$numchar);
		if ($tmptext != 0)
			$text=substr($text,0,$tmptext)."...";
	}
	return $text;
}


function istrpos($haystack,$needle,$offset = 0)
{
	setlocale (LC_ALL, array ('ru_RU.CP1251', 'rus_RUS.1251','russian'));
	return(strpos(strtolower($haystack),strtolower($needle),$offset));
}


function strpos_array($haystack, $needle)
{
  $kill = $offset = $i = 0;

  while ($kill === 0)
  {
    $i++;
    $result = strpos($haystack, $needle, $offset);

    if ($result === FALSE)
    {
      $kill = 1;
    }
    else
    {
      $array[$i] = $result;
      $offset = $result + 1;
    }
  }

  return $array;
}

function NC_CutWord($text, $first_or_last)
{
  $result = NULL;

  $tmp = explode(" ", $text);
  for ($i=0; $i<count($tmp); $i++)
  {
    if ($i == 0 && $first_or_last == 'first')
      continue;
    if ($i == (count($tmp) - 1) && $first_or_last == 'last')
      continue;
    $result .= $tmp[$i]." ";
  }

  return $result;
}

// Вывод куска текста с найденным словом
function SearchTextEcho($text, $word)
{
  $mytext = "";
	$text = str_replace("&amp;", "&", $text);

	if ($word)
	{
		$tmptext = istrpos($text,$word);
		$word = substr($text,$tmptext,nc_strlen($word));

		if ($tmptext !== FALSE)
		{
		  if ($tmptext >= 70)
		  {
		    $mytext = NC_CutWord(substr($text,$tmptext-70,70), 'first');
			  $mytext2 = NC_CutWord(substr($text,$tmptext+nc_strlen($word),nc_strlen($word)+70), 'last');
			  return "&#133; ".$mytext." <font style='background-color:#fbe99f;'>".$word."</font>".$mytext2." &#133;";
		  }
		  elseif ($tmptext < 70)
		  {
		    $mytext = substr($text,0,$tmptext);
			  $mytext2 = NC_CutWord(substr($text,$tmptext+nc_strlen($word),nc_strlen($word)+70), 'last');
			  return $mytext." <font style='background-color:#fbe99f;'>".$word."</font>".$mytext2." &#133;";
		  }
		}
	}

	return $mytext;
}


function FilterText ($text,$direction=1)
{
	if ($direction==1)
	{
		$text=addslashes($text);
		$text=str_replace('$',"\\\\\\$",$text);
	}
	else
	{
		$text=stripslashes($text);
		$text=str_replace('\$',"$",$text);
	}

	return $text;
}



function nc_search_highlight_words($document, $search_string, $text_size = 150, $hl_prefix = "<span style='background-color: #fbe99f;'>", $hl_suffix = "</span>") {

   $tokens = nc_preg_split('/\s+/', $search_string);

   $first_found_position = false;
   $text_is_cut = false;

   // Каждое слово в искомой фразе (фраза должна быть в виде BOOLEAN выражения)
   foreach ($tokens as $token) {

      // почистим слово
      $clean_token = str_replace(array('"', '*', '+', '>', '<', '-'), '', $token);
      if ( nc_strlen(trim($clean_token)) == 0 ) {
         continue;
      }

      // отметим его в документе: chr(1).$clean_token.chr(2)
      $new_document = nc_preg_replace('/([\s"\'!#$%&()*+,.-\/:;<=>?@\[\]{|}])('.preg_quote($clean_token, '/').'[^"\'\s0-9!#$%&()*+,.-\/:;<=>?@\[\]{|}]*)/i', '\1'.chr(1).'\2'.chr(2), ' '.$document);

      // если что-то было отмечено
      if ( $new_document != $document ) {

         // найдем первое вхождение...
         $first_found_position = nc_strpos($new_document, chr(1));

         // ... и если нашли, и текст мы еще не обрезали...
         if ( !$text_is_cut && $first_found_position !== false ) {
            // ...отмотаем назад половину указанной длины подсвечиваемого текста...
            $begin_offset = $first_found_position - floor($text_size / 2);
            if ($begin_offset < 0) {
               $begin_offset = 0;
            }
            $text_cut_length = $text_size + nc_strlen($clean_token) + 2;
            // Откуда 2? Это чтобы не выбрасывать откр. и закр. символы подсветки.
            // К сожалению не работает, если кол-во вхождений слова в текст больше 1
            // ...и отрежем текст
            $document = ( $begin_offset > 0 ? '... ' : '' ) . nc_substr($new_document, $begin_offset, $text_cut_length) . ( $begin_offset + $text_cut_length < nc_strlen($new_document) ? ' ...' : '');
            $text_is_cut = true;
         }
         else {
            $document = $new_document;
         }
      }

   }

   // если мы так ничего и не отрезали...
   if ( !$text_is_cut ) {
      // ...то отрежем хотя бы с начала
      $document = nc_substr($document, 0, $text_size) . ($text_size < nc_strlen($document) ? ' ...' : '');
   }
   // вырезаем все до первого пробела...
   $document = nc_preg_replace('/^\.\.\. [^\s]*\s+/U', '...', $document);
   // ...и все после последнего пробела
   $document = nc_preg_replace('/\s+[^\s]* \.\.\.$/U', '...', $document);

   // если текст мы обрезали между открывающим и закрывающим тэгами подсветки...
   if ( nc_strrpos($document, chr(1)) > nc_strrpos($document, chr(2)) ) {
      // ...то добавим закрывающий тэг
      $document .= chr(2);
   }

   // заменим условные символы настоящей разметкой
   $document = str_replace(chr(1), $hl_prefix, $document);
   $document = str_replace(chr(2), $hl_suffix, $document);

   return $document;
}



/**
 * Возвращает выражение, пригодное для использования в MATCH(...) AGAINST(...) в BOOLEAN режиме.
 *
 * @param string $search_string
 * @param string $language
 * @return string
 */
function nc_search_get_db_expression($search_string, $language = "ru") {

   global $MODULE_FOLDER;
   $search_module_path = $MODULE_FOLDER . 'searchold/';

   $nc_core = nc_Core::get_object();

   // мы вообще ищем хоть что-нибудь?
   if ( !$search_string ) {
      return "";
   }

   // а есть ли файл с функцией стемминга для указанного языка?
   if ( !file_exists($search_module_path . 'stemming.' . $language . '.php') ) {
      return $search_string;
   }
   if ( $language == 'ru') {
     $language .= $nc_core->NC_UNICODE ? "_utf8" : "_cp1251";
   }
   // и есть ли в этом файле функция стемминга для указанного языка?
   include_once($search_module_path . 'stemming.'.$language.'.php');
   $stem_function = 'nc_search_stem_word_'.$language;
   if ( !function_exists($stem_function) ) {
      return $search_string;
   }

   $tokens = nc_preg_split('/\s+/', $search_string);

   $result = "";
   $have_quote = false;
   foreach ($tokens as $token) {
      if ( $token{0} == '"' ) {
         $have_quote = true;
      }
      if ( nc_strlen($token) == 0 || $have_quote || preg_match('/[0-9!#$%&()*+,.-\/:;<=>?@\[\]{|}]+$/', $token) || $token{nc_strlen($token)-1} == '"' ) {
         $result .= $token . ' ';
      }
      else {
         $result .= $stem_function($token) . '* ';
      }
      if ( $token{nc_strlen($token)-1} == '"') {
         $have_quote = false;
      }
   }

   return $result;
}

?>