<?php
/* $Id: stemming.ru.php 3843 2010-06-28 11:52:24Z denis $ */

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
   if (nc_preg_match_all('/'.$RVRE.'/', $word, $matches)) {
      $start = $matches[1][0];
      $RV = $matches[2][0];
   }
   if (empty($RV)) {
      return $word;
   }
   
   //Step 1
   if (preg_match('/'.$PERFECTIVEGROUND.'/', $RV)) {
      $RV = nc_preg_replace('/'.$PERFECTIVEGROUND.'/', '', $RV);
   }
   else {
      $RV = nc_preg_replace('/'.$REFLEXIVE.'/', '', $RV);
      if (preg_match('/'.$ADJECTIVE.'/', $RV)) {
         $RV = nc_preg_replace('/'.$ADJECTIVE.'/', '', $RV);
         $RV = nc_preg_replace('/'.$PARTICIPLE.'/', '', $RV);
      }
      else {
         if (!preg_match('/'.$VERB.'/', $RV)) {
            $RV = nc_preg_replace('/'.$NOUN.'/', '', $RV);
         }
         else {
            $RV = nc_preg_replace('/'.$VERB.'/', '', $RV);
         }
      }
   }
   
   //Step 2
   $RV = nc_preg_replace('/�$/', '', $RV);
   
   //Step 3
   if (preg_match('/'.$DERIVATIONAL.'/', $RV)) {
      $RV = nc_preg_replace('/����?$/', '', $RV);
   }
   
   //Step 4
   if (preg_match('/�$/', $RV)) {
      $RV = nc_preg_replace('/�$/', '', $RV);
   }
   else {
      $RV = nc_preg_replace('/����?/', '', $RV);
      $RV = nc_preg_replace('/��$/', '�', $RV);
   }
   
   return $start . $RV;
}

?>