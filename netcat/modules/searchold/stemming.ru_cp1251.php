<?php
/* $Id: stemming.ru_utf8.php 4028 2010-10-01 08:36:09Z denis $ */

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
   if (nc_preg_match_all('/'.$RVRE.'/', $word, $matches)) {
      $start = $matches[1][0];
      $RV = $matches[2][0];
   }
   if (empty($RV)) {
      return $word;
   }
   
   //Step 1
   if (nc_preg_match('/'.$PERFECTIVEGROUND.'/', $RV)) {
      $RV = nc_preg_replace('/'.$PERFECTIVEGROUND.'/', '', $RV);
   }
   else {
      $RV = nc_preg_replace('/'.$REFLEXIVE.'/', '', $RV);
      if (nc_preg_match('/'.$ADJECTIVE.'/', $RV)) {
         $RV = nc_preg_replace('/'.$ADJECTIVE.'/', '', $RV);
         $RV = nc_preg_replace('/'.$PARTICIPLE.'/', '', $RV);
      }
      else {
         if (!nc_preg_match('/'.$VERB.'/', $RV)) {
            $RV = nc_preg_replace('/'.$NOUN.'/', '', $RV);
         }
         else {
            $RV = nc_preg_replace('/'.$VERB.'/', '', $RV);
         }
      }
   }
   
   //Step 2
   $RV = nc_preg_replace('/и$/', '', $RV);
   
   //Step 3
   if (preg_match('/'.$DERIVATIONAL.'/', $RV)) {
      $RV = nc_preg_replace('/ость?$/', '', $RV);
   }
   
   //Step 4
   if (preg_match('/ь$/', $RV)) {
      $RV = nc_preg_replace('/ь$/', '', $RV);
   }
   else {
      $RV = nc_preg_replace('/ейше?/', '', $RV);
      $RV = nc_preg_replace('/нн$/', 'н', $RV);
   }
   
   return $start . $RV;
}

?>