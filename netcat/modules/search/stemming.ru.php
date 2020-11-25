<?php
/* $Id: stemming.ru.php 1265 2007-08-09 12:19:41Z chelout $ */

function nc_search_stem_word_ru($word) {
  
   $VOWEL = '���������';
   $PERFECTIVEGROUND = '((��|����|������|��|����|������)|((?<=[��])(�|���|�����)))$';
   $REFLEXIVE = '(�[��])$';
   $ADJECTIVE = '(��|��|��|��|���|���|��|��|��|��|��|��|��|��|���|���|���|���|��|��|��|��|��|��|��|��)$';
   $PARTICIPLE = '((���|���|���)|((?<=[��])(��|��|��|��|�)))$';
   $VERB = '((���|���|���|����|����|���|���|���|��|��|��|��|��|��|��|���|���|���|��|���|���|��|��|���|���|���|���|��|�)|((?<=[��])(��|��|���|���|��|�|�|��|�|��|��|��|��|��|��|���|���)))$';
   $NOUN = '(�|��|��|��|��|�|����|���|���|��|��|�|���|��|��|��|�|���|��|���|��|��|��|�|�|��|���|��|�|�|��|��|�|��|��|�)$';
   $RVRE = '^(.*?['.$VOWEL.'])(.*)$';
   $DERIVATIONAL = '[^'.$VOWEL.']['.$VOWEL.']+[^'.$VOWEL.']+['.$VOWEL.'].*(?<=�)���?$';
   
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
   $RV = preg_replace('/�$/', '', $RV);
   
   //Step 3
   if (preg_match('/'.$DERIVATIONAL.'/', $RV)) {
      $RV = preg_replace('/����?$/', '', $RV);
   }
   
   //Step 4
   if (preg_match('/�$/', $RV)) {
      $RV = preg_replace('/�$/', '', $RV);
   }
   else {
      $RV = preg_replace('/����?/', '', $RV);
      $RV = preg_replace('/��$/', '�', $RV);
   }
   
   return $start . $RV;
}

?>