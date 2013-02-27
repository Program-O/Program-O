<?php

  /***************************************
  * www.program-o.com
  * PROGRAM O
  * Version: 2.1.3
  * FILE: spell_checker/spell_checker.php
  * AUTHOR: Elizabeth Perreau and Dave Morton
  * DATE: MAY 4TH 2011
  * DETAILS: this file contains the addon library to spell check into before its matched in the database
  ***************************************/
  /**
  * function run_spellcheck_say()
  * A function to run the spellchecking of the userinput
  * @param  string $say - The user's input
  * @return $say (spellchecked)
  **/
  function run_spell_checker_say($say)
  {
    global $bot_id, $default_bot_id;
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Starting function and setting timestamp.', 2);
    $sentence = '';
    $bid = (!empty ($bot_id)) ? $bot_id : $default_bot_id;
    $wordArr = explode(' ', $say);
    foreach ($wordArr as $index => $word)
    {
      $sentence .= spell_check($word, $bid) . " ";
    }
    return trim($sentence);
  }

  /**
  * function spell_check()
  * A function query the db and get find mispelt words
  **/
  function spell_check($word, $bot_id)
  {
    global $con, $dbn;
    $corrected_word = $word;
  //set in global config file
    $sql = "SELECT `correction` FROM `$dbn`.`spellcheck` WHERE `missspelling` = '$word' LIMIT 1";
    $result = db_query($sql, $con);
    if (mysql_num_rows($result) > 0)
    {
      $row = mysql_fetch_assoc($result);
      $corrected_word = $row['correction'];
    }
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Corrected spelling. Old word = $word. New word = $corrected_word.", 2);
    return $corrected_word;
  }

?>