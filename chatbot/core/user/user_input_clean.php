<?php

  /***************************************
  * www.program-o.com
  * PROGRAM O
  * Version: 2.2.1
  * FILE: chatbot/core/user/user_input_clean.php
  * AUTHOR: Elizabeth Perreau and Dave Morton
  * DATE: MAY 4TH 2011
  * DETAILS: this file contains the functions to clean a user input
  ***************************************/
  /**
  * function clean_for_aiml_match()
  * this function controls the calls to other functions to clean text for an aiml match
  * @param string $text
  * @return string text
  **/
  function clean_for_aiml_match($text)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Oh, my! how dirty! I just HAVE to clean this up!', 2);
    $otext = $text;
    $text = remove_all_punctuation($text);
    //was not all before
    $text = whitespace_clean($text);
    $text = capitalize($text);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "In: $otext Out:$text", 4);
    return $text;
  }

  /**
  * function whitespace_clean()
  * this function removes multiple whitespace
  * @param string $text
  * @return string text
  **/
  function whitespace_clean($text)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Wiping out all extra whitespace!', 2);
    $otext = $text;
    $text = preg_replace('/\s\s+/', ' ', $text);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "In: $otext Out:$text", 4);
    return trim($text);
  }

  /**
  * function remove_all_punctuation()
  * this function removes all puncutation
  * @param string $text
  * @return string text
  **/
  function remove_all_punctuation($text)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Im stripping out all punctuation if you dont mind', 2);
    $otext = $text;
    $text = preg_replace('/[[:punct:]]/uis', ' ', $text);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "In: $otext Out:$text", 4);
    return $text;
  }

  /**
  * function capitalize()
  * this function capitalizes a string
  * @param string $text
  * @return string text
  **/
  function capitalize($text)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'LET\'S JUST CAPITALIZE EVERYTHING, SHALL WE?', 2);
    $otext = $text;
    $text = mb_strtoupper($text);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "In: $otext Out:$text", 4);
    return $text;
  }

?>