<?php
/* $Id: stemming.ru.php 1265 2007-08-09 12:19:41Z chelout $ */

function nc_search_stem_word_ru($word) {
  
   $VOWEL = 'аеиоуыэю€';
   $PERFECTIVEGROUND = '((ив|ивши|ившись|ыв|ывши|ывшись)|((?<=[а€])(в|вши|вшись)))$';
   $REFLEXIVE = '(с[€ь])$';
   $ADJECTIVE = '(ее|ие|ые|ое|ими|ыми|ей|ий|ый|ой|ем|им|ым|ом|его|ого|ему|ому|их|ых|ую|юю|а€|€€|ою|ею)$';
   $PARTICIPLE = '((ивш|ывш|ующ)|((?<=[а€])(ем|нн|вш|ющ|щ)))$';
   $VERB = '((ила|ыла|ена|ейте|уйте|ите|или|ыли|ей|уй|ил|ыл|им|ым|ен|ило|ыло|ено|€т|ует|уют|ит|ыт|ены|ить|ыть|ишь|ую|ю)|((?<=[а€])(ла|на|ете|йте|ли|й|л|ем|н|ло|но|ет|ют|ны|ть|ешь|нно)))$';
   $NOUN = '(а|ев|ов|ие|ье|е|и€ми|€ми|ами|еи|ии|и|ией|ей|ой|ий|й|и€м|€м|ием|ем|ам|ом|о|у|ах|и€х|€х|ы|ь|ию|ью|ю|и€|ь€|€)$';
   $RVRE = '^(.*?['.$VOWEL.'])(.*)$';
   $DERIVATIONAL = '[^'.$VOWEL.']['.$VOWEL.']+[^'.$VOWEL.']+['.$VOWEL.'].*(?<=о)сть?$';
   
   $matches = array();
   if (preg_match_all('/'.$RVRE.'/', $word, $matches)) {
      $start = $matches[1][0];
      $RV = $matches[2][0];
   }
   if (empty($RV)) {
      return $word;
   }
   
   //Step 1
   if (preg_match('/'.$PERFECTIVEGROUND.'/', $RV)) {
      $RV = preg_replace('/'.$PERFECTIVEGROUND.'/', '', $RV);
   }
   else {
      $RV = preg_replace('/'.$REFLEXIVE.'/', '', $RV);
      if (preg_match('/'.$ADJECTIVE.'/', $RV)) {
         $RV = preg_replace('/'.$ADJECTIVE.'/', '', $RV);
         $RV = preg_replace('/'.$PARTICIPLE.'/', '', $RV);
      }
      else {
         if (!preg_match('/'.$VERB.'/', $RV)) {
            $RV = preg_replace('/'.$NOUN.'/', '', $RV);
         }
         else {
            $RV = preg_replace('/'.$VERB.'/', '', $RV);
         }
      }
   }
   
   //Step 2
   $RV = preg_replace('/и$/', '', $RV);
   
   //Step 3
   if (preg_match('/'.$DERIVATIONAL.'/', $RV)) {
      $RV = preg_replace('/ость?$/', '', $RV);
   }
   
   //Step 4
   if (preg_match('/ь$/', $RV)) {
      $RV = preg_replace('/ь$/', '', $RV);
   }
   else {
      $RV = preg_replace('/ейше?/', '', $RV);
      $RV = preg_replace('/нн$/', 'н', $RV);
   }
   
   return $start . $RV;
}

?>