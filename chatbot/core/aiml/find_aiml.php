<?php

  /***************************************
  * http://www.program-o.com
  * PROGRAM O
  * Version: 2.3.1
  * FILE: chatbot/core/aiml/find_aiml.php
  * AUTHOR: Elizabeth Perreau and Dave Morton
  * DATE: MAY 4TH 2011
  * DETAILS: this file contains the functions find and score
  *          the most likely AIML match from the database
  ***************************************/
  /**
  * function get_last_word()
  * This function gets the last word from a sentence
  * @param  string $sentence - the sentence to use
  * @return string $last_word
  **/
  function get_last_word($sentence)
  {
    $wordArr = explode(' ', $sentence);
    $last_word = trim($wordArr[count($wordArr) - 1]);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Sentence: $sentence. Last word:$last_word", 4);
    return $last_word;
  }

  /**
  * function get_first_word()
  * This function gets the last word from a sentence
  * @param  string $sentence - the sentence to use
  * @return string $last_word
  **/
  function get_first_word($sentence)
  {
    $wordArr = explode(' ', $sentence);
    $first_word = trim($wordArr[0]);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Sentence: $sentence. First word:$first_word", 4);
    return $first_word;
  }

  /**
  * function make_like_pattern()
  * This function gets an input sentence from the user and a aiml tag and creates an sql like pattern
  * @param  string $sentence - the user input to be turned into a like pattern
  * @param  string $field - the name of the aiml tag which we are going to use in our like pattern
  * @return string $sql_like_pattern - the like pattern
  **/
  function make_like_pattern($sentence, $field)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Making a like pattern to use in the sql", 4);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Transforming $field: " . print_r($sentence, true), 4);
    $sql_like_pattern = '';
    $i = 0;
    //if the sentence is contained in an array extract the actual text sentence
    if (is_array($sentence))
    {
      $sentence = implode_recursive(' ', $sentence, __FILE__, __FUNCTION__, __LINE__);
    }
    $words = explode(" ", $sentence);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "word list:\n" . print_r($words, true), 4);
    $count_words = count($words) - 1;
    $first_word = $words[0];
    $last_word = $words[$count_words];
    $tmpLike = '';
    $sql_like_pattern .= " `$field` like '$first_word %' OR";
    foreach ($words as $word)
    {
      if ($word == $first_word or $word == $last_word) continue;
      $sql_like_pattern .= " `$field` like '% $word %' OR";
    }

    $sql_like_pattern .= " `$field` like '% $last_word%' OR  `$field` like '$first_word % $last_word' OR `$field` like '$last_word'";
    runDebug(__FILE__, __FUNCTION__, __LINE__, "returning like pattern:\n$sql_like_pattern", 4);
    //return
    return $sql_like_pattern;
  }

  /**
  * function wordsCount_inSentence()
  * This function counts the words in a sentence
  * @param  string $sentence - the user input to be turned into a like pattern
  * @return int wordCount
  **/
  function wordsCount_inSentence($sentence)
  {
    $wordArr = explode(" ", $sentence);
    $wordCount = count($wordArr);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Sentence: $sentence numWords:$wordCount", 4);
    return $wordCount;
  }

  /**
  * function unset_all_bad_pattern_matches()
  * This function takes all the sql results and filters out the irrelevant ones
  * @param array $allrows - all the results
  * @param string $lookingfor - the user input
  * @param string $current_thatpattern - the current that pattern
  * @param string $current_topic - the current topic
  * @return array tmp_rows - the RELEVANT results
  **/
  function unset_all_bad_pattern_matches($allrows, $lookingfor, $current_thatpattern, $current_topic, $default_pattern)
  {
    global $error_response;
    $tmp_rows = array();
    $i = 0;
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Searching through " . count($allrows) . " rows to unset bad matches", 4);
    if (($allrows[0]['pattern'] == "no results") and (count($allrows) == 1))
    {
      $tmp_rows[0] = $allrows[0];
      $tmp_rows[0]['score'] = 1;
      runDebug(__FILE__, __FUNCTION__, __LINE__, "Returning error as no results where found", 1);
      return $tmp_rows;
    }
    //loop through the results array
    foreach ($allrows as $all => $subrow)
    {
      $aiml_pattern = $subrow['pattern'];
      //get the pattern
      $aiml_topic = $subrow['topic'];
      //get the topic
      $aiml_thatpattern = $subrow['thatpattern'];
      //get the that
      $aiml_pattern = (IS_MB_ENABLED) ? mb_strtolower($aiml_pattern) : strtolower($aiml_pattern);
      $default_pattern = (IS_MB_ENABLED) ? mb_strtolower($default_pattern) : strtolower($default_pattern);
      if ($aiml_pattern == $default_pattern)
      {
      //if it is a direct match with our default pattern then add to tmp_rows
        $tmp_rows[$i] = $subrow;
        $tmp_rows[$i]['score'] = 0;
        $tmp_rows[$i]['track_score'] = "za";
        $i++;
      }
      else
      {
        $aiml_pattern_matchme = match_wildcard_rows($aiml_pattern);
        //build an aiml_pattern with wild cards to check for a match
        if ($aiml_thatpattern != '')
        {
          $aiml_thatpattern_matchme = match_wildcard_rows($aiml_thatpattern);
          //build an aiml_thatpattern with wild cards to check for a match
          $that_match = preg_match($aiml_thatpattern_matchme, $current_thatpattern, $matches);
          //see if that patterns match
          $tmp_rows[$i]['track_score'] = "b";
        }
        else
        {
          $that_match = "$aiml_thatpattern == ''";
          $tmp_rows[$i]['track_score'] = "c";
        }
        if ($aiml_topic != '')
        {
          $topic_match = "$aiml_topic == $current_topic";
          $tmp_rows[$i]['track_score'] .= "d";
        }
        else
        {
          $topic_match = "$aiml_topic == ''";
          $tmp_rows[$i]['track_score'] .= "e";
        }
        //try to match the returned aiml pattern with the user input (lookingfor) and with the that's and topic's
        preg_match($aiml_pattern_matchme, $lookingfor, $matches);

        if (count($matches)>0)
        {
        //echo "<br/>TRUE1";
          if ($that_match)
          {
          //echo "<br/>TRUE2";
            if ($topic_match)
            {
              if ((isset ($subrow['pattern'])) && ($subrow['pattern'] != ''))
              {
              //	echo "<br/>TRUE3";
                $tmp_rows[$i] = $subrow;
                $tmp_rows[$i]['score'] = 0;
                $tmp_rows[$i]['track_score'] = "f";
                $i++;
              }
            }
          }
        }
        //echo "<br/>--------------";
      }
    }
    //	echo "<pre>";
    //	print_r($tmp_rows);
    //	echo "</pre>";
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Found '$i' relevant rows", 2);
    return $tmp_rows;
  }

  /**
  * function match_wildcard_rows()
  * This function takes a sentence and converts AIML wildcards to PHP wildcards
  * so that it can be matched in php
  * @param string $item
  * @return string matchme
  **/
  function match_wildcard_rows($item)
  {
    $item = trim($item);
    $item = str_replace("*", ")(.*)(", $item);
    $item = str_replace("_", ")(.*)(", $item);
    $item = str_replace("+", "\+", $item);
    $item = "(" . str_replace(" ", "\s", $item) . ")";
    $item = str_replace("()", '', $item);
    $matchme = "/^" . $item . "$/ui";
    return $matchme;
  }

  /**
  * function score_matches()
  * This function takes all the relevant sql results and scores them
  * to find the most likely match with the aiml
  * @param int $bot_parent_id - the id of the parent bot
  * @param array $allrows - all the results
  * @param string $lookingfor - the user input
  * @param string $current_thatpattern - the current that pattern
  * @param string $current_topic - the current topic
  * @return array allrows - the SCORED results
  **/
  function score_matches($convoArr, $bot_parent_id, $allrows, $lookingfor, $current_thatpattern, $current_topic, $default_pattern)
  {
    global $common_words_array;
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Scoring the matches. Topic = $current_topic", 4);
    //set the scores for each type of word or sentence to be used in this function
    $this_bot_match = 500;
    $underscore_points = 100;
    $that_pattern_match = 75;
    $topic_match = 50;
    $that_pattern_match_general = 9;
    $uncommon_word_points = 8;
    $common_word_points = 1;
    $direct_word_match_points = 1;
    $pattern_points = 2;
    $starscore_points = 1;
    $direct_match = 1;
    //even the worst match from the actual bot is better than the best match from the base bot
    //loop through all relevant results
    foreach ($allrows as $all => $subrow)
    {
    //get items
      if ((!isset ($subrow['pattern'])) || (trim($subrow['pattern']) == ''))
      {
        $aiml_pattern = '';
      }
      else
      {
        $aiml_pattern = trim($subrow['pattern']);
      }
      if ((!isset ($subrow['thatpattern'])) || (trim($subrow['thatpattern']) == ''))
      {
        $aiml_thatpattern = '';
      }
      else
      {
        $aiml_thatpattern = trim($subrow['thatpattern']);
      }
      if ((!isset ($subrow['topic'])) || (trim($subrow['topic']) == ''))
      {
        $aiml_topic = '';
      }
      else
      {
        $aiml_topic = trim($subrow['topic']);
      }
      if ($aiml_pattern == '')
      {
        continue;
      }
      //convert aiml wildcards to php wildcards
      if ($aiml_thatpattern != '')
      {
        $aiml_thatpattern_wildcards = match_wildcard_rows($aiml_thatpattern);
      }
      else
      {
        $aiml_thatpattern_wildcards = '';
      }
      //to debug the scoring...
      $allrows[$all]['track_score'] = '';
      //if the aiml is from the actual bot and not the base bot
      //any match in the current bot is better than the base bot
      if (($bot_parent_id != 0) && ($allrows[$all]['bot_id'] != $bot_parent_id))
      {
        $allrows[$all]['score'] += $this_bot_match;
        $allrows[$all]['track_score'] .= "a";
      }
      //if the result aiml pattern matches the user input increase score
      if ($aiml_pattern == $lookingfor)
      {
        $allrows[$all]['score'] += $direct_match;
        $allrows[$all]['track_score'] .= "b";
      }
      //if the result topic matches the user stored aiml topic increase score
      if (($aiml_topic == $current_topic) && ($aiml_topic != ''))
      {
        $allrows[$all]['score'] += $topic_match;
        $allrows[$all]['track_score'] .= "c";
      }
      //if the result that pattern matches the user stored that pattern increase score
      if (($aiml_thatpattern == $current_thatpattern) && ($aiml_thatpattern != '') && ($aiml_pattern != "*"))
      {
        $allrows[$all]['score'] += $that_pattern_match;
        $allrows[$all]['track_score'] .= "d";
      }
      elseif (($aiml_thatpattern_wildcards != '') && ($aiml_thatpattern != '') && ($aiml_pattern != "*") && (preg_match($aiml_thatpattern_wildcards, $current_thatpattern, $m)))
      {
        $allrows[$all]['score'] += $that_pattern_match;
        $allrows[$all]['track_score'] .= "e";
      }
      //if the that pattern is just a star we need to score it seperately as this is very general
      if (($aiml_pattern == "*") && ((substr($aiml_thatpattern, - 1) == "*") || (substr($aiml_thatpattern, 0, 1) == "*")))
      {
      //if the result that pattern matches the user stored that pattern increase score with a lower number
        if (($aiml_thatpattern == $current_thatpattern) && ($aiml_thatpattern != ''))
        {
          $allrows[$all]['score'] += $that_pattern_match_general;
          $allrows[$all]['track_score'] .= "f";
        }
        elseif (($aiml_thatpattern_wildcards != '') && ($aiml_thatpattern != '') && (preg_match($aiml_thatpattern_wildcards, $current_thatpattern, $m)))
        {
          $allrows[$all]['score'] += $that_pattern_match_general;
          $allrows[$all]['track_score'] .= "g";
        }
      }
      //if stored result == default pattern increase score
      $aiml_pattern = (IS_MB_ENABLED) ? mb_strtolower($aiml_pattern) : strtolower($aiml_pattern);
      $default_pattern = (IS_MB_ENABLED) ? mb_strtolower($default_pattern) : strtolower($default_pattern);
      if ($aiml_pattern == $default_pattern)
      {
        $allrows[$all]['score'] += $pattern_points;
        $allrows[$all]['track_score'] .= "h";
      }
      elseif ($aiml_pattern == "*")
      {
      //if stored result == * increase score
        $allrows[$all]['score'] += $starscore_points;
        $allrows[$all]['track_score'] .= "i";
      }
      elseif ($aiml_pattern == "_")
      {
      //if stored result == _ increase score
        $allrows[$all]['score'] += $underscore_points;
        $allrows[$all]['track_score'] .= "j";
      }
      else
      {
        //if stored result == none of the above BREAK INTO WORDS AND SCORE INDIVIDUAL WORDS
        $lc_lookingFor = (IS_MB_ENABLED) ? mb_strtolower($convoArr['user_say'][1]) : strtolower($convoArr['user_say'][1]);
        $lookingforArray = explode(' ', trim($lc_lookingFor));
        //save_file(_DEBUG_PATH_ . 'lfa.txt', print_r($lookingforArray, true));
        $wordsArr = explode(" ", $aiml_pattern);
        foreach ($wordsArr as $index => $word)
        {
          $word = (IS_MB_ENABLED) ? mb_strtolower($word) : strtolower($word);
          $word = trim($word);
          if (in_Array($word, $lookingforArray))
          {
          // if it is a direct word match increase with (lower) score
            $allrows[$all]['score'] += $direct_word_match_points;
            $allrows[$all]['track_score'] .= "x";
          }
          if (in_Array($word, $common_words_array))
          {
          // if it is a commonword increase with (lower) score
            $allrows[$all]['score'] += $common_word_points;
            $allrows[$all]['track_score'] .= "k";
          }
          elseif ($word == "*")
          {
            $allrows[$all]['score'] += $starscore_points;
            //if it is a star wildcard increase score
            $allrows[$all]['track_score'] .= "l";
          }
          elseif ($word == "_")
          {
            $allrows[$all]['score'] += $underscore_points;
            //if it is a underscore wildcard increase score
            $allrows[$all]['track_score'] .= "m";
          }
          else
          {
            $allrows[$all]['score'] += $uncommon_word_points;
            //else it must be an uncommon word so increase the score
            $allrows[$all]['track_score'] .= "n";
          }
        }
      }
    }
    //send off for debugging
    sort2DArray("show top scoring aiml matches", $allrows, "score", 1, 10);

    /*
    echo "<pre>";
    print_r($allrows);
    echo "</pre>";
    */
    return $allrows;
    //return the scored rows
  }

  /**
  * function sort2DArray()
  * Small helper function to sort a 2d array
  * @param string $opName - name of this operation i.e. show top scored aiml
  * @param array $thisArr - the array to sort
  * @param $sortByItem - the array field to sort by
  * @param $sortAsc - 1 = ascending order, 0 = descending
  * @param $limit - the number of results to return
  * @return void;
  **/
  function sort2DArray($opName, $thisArr, $sortByItem, $sortAsc = 1, $limit = 10)
  {
    $thisCount = count($thisArr);
    $showLimit = ($thisCount < $limit) ? $thisCount : $limit;
    runDebug(__FILE__, __FUNCTION__, __LINE__, "$opName - sorting $thisCount results by $sortByItem and getting the top $showLimit for debugging.", 4);
    runDebug(__FILE__, __FUNCTION__, __LINE__, print_r($thisArr, true), 4);
    $i = 0;
    $tmpSortArr = array();
    $resArr = array();
    $last_high_score = 0;
    //loop through the results and put in tmp array to sort
    foreach ($thisArr as $all => $subrow)
    {
      if (isset ($subrow[$sortByItem]))
      {
        $tmpSortArr[$subrow[$sortByItem]] = $subrow[$sortByItem];
      }
    }
    //sort the results
    if ($sortAsc == 1)
    {
    //ascending
      krsort($tmpSortArr);
    }
    else
    {
    //descending
      ksort($tmpSortArr);
    }
    //loop through scores
    foreach ($tmpSortArr as $sortedKey => $idValue)
    {
    //no match against orig res arr
      foreach ($thisArr as $i => $subArr)
      {
        if (isset ($subrow[$sortByItem]))
        {
          if (((string) $subArr[$sortByItem] == (string) $idValue))
          {
            $resArr[] = $subArr;
          }
        }
      }
    }
    //get the limited top results
    $outArr = array_slice($resArr, 0, $limit);
    //send to debug
    runDebug(__FILE__, __FUNCTION__, __LINE__, "$opName " . print_r($outArr, true), 3);
  }

  /**
  * function get_highest_score_rows()
  * This function takes all the relevant and scored aiml results
  * and saves the highest scoring rows
  * @param array $allrows - all the results
  * @param string $lookingfor - the user input
  * @return array bestResponseArr - best response and its parts (topic etc)
  **/
  function get_highest_score_rows($allrows, $lookingfor)
  {
    $bestResponse = array();
    $last_high_score = 0;
    $tmpArr = array();
    //loop through the results
    foreach ($allrows as $all => $subrow)
    {
      if (!isset ($subrow['score']))
      {
      }
      elseif ($subrow['score'] > $last_high_score)
      {
        $tmpArr = array();
      //if higher than last score then reset tmp array and store this result
        $tmpArr[] = $subrow;
        $last_high_score = $subrow['score'];
      }
      elseif ($subrow['score'] == $last_high_score)
      {
      //if same score as current high score add to array
        $tmpArr[] = $subrow;
      }
    }
    //there may be any number of results with the same score so pick any random one
    $bestResponse = (count($tmpArr) > 0) ? $tmpArr[array_rand($tmpArr)] : false;
    if (false !== $bestResponse) $bestResponse['template'] = get_winning_category($bestResponse['aiml_id']);
    $cRes = count($tmpArr);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Best Responses: " . print_r($tmpArr, true), 4);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Will use randomly picked best response chosen out of $cRes responses with same score: " . $bestResponse['aiml_id'] . " - " . $bestResponse['pattern'], 2);
    //return the best response
    return $bestResponse;
  }

  /**
    * function get_winning_category
    * Retrieves the AIML template from the selected DB entry
    * @param array  $id - the id number of the AIML category to get
    * @return string $template - the value of the `template` field from the chosen DB entry
    **/
    function get_winning_category($id)
    {
      runDebug(__FILE__, __FUNCTION__, __LINE__,"And the winner is... $id!", 2);
      global $con, $dbn, $error_response;
      $sql = "SELECT `template` from `$dbn`.`aiml` where `id` = $id limit 1;";
      $result = mysql_query($sql, $con) or trigger_error('Houston, we have a problem! Error: ' . mysql_error());
      if ($row = mysql_fetch_assoc($result))
      {
        $template = $row['template'];
      }
      else
      {
        $template = $error_response;
      }
      runDebug(__FILE__, __FUNCTION__, __LINE__,"Returning the AIML template for id# $id. Value:\n'$template'", 4);
      return $template;
    }


  /**
  * function get_convo_var()
  * This function takes fetches a variable from the conversation array
  * @param array $convoArr - conversation array
  * @param string $index_1 - convoArr[$index_1]
  * @param string $index_2 - convoArr[$index_1][$index_2]
  * @param int $index_3 - convoArr[$index_1][$index_2][$index_3]
  * @param int $index_4 - convoArr[$index_1][$index_2][$index_3][$index_4]
  * @return string $value - the value of the element
  *
  *
  * examples
  *
  * $convoArr['conversation']['bot_id'] = $convoArr['conversation']['bot_id']
  * $convoArr['that'][1][1] = get_convo_var($convoArr,'that','',1,1)
  **/
  function get_convo_var($convoArr, $index_1, $index_2 = '', $index_3 = '', $index_4 = '')
  {
    if ($index_2 == '')
      $index_2 = "~NULL~";
    if ($index_3 == '')
      $index_3 = 1;
    if ($index_4 == '')
      $index_4 = 1;
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Get from ConvoArr [$index_1][$index_2][$index_3][$index_4]", 4);
    if ((isset ($convoArr[$index_1])) && (!is_array($convoArr[$index_1])) && ($convoArr[$index_1] != ''))
    {
      $value = $convoArr[$index_1];
    }
    elseif ((isset ($convoArr[$index_1][$index_3])) && (!is_array($convoArr[$index_1][$index_3])) && ($convoArr[$index_1][$index_3] != ''))
    {
      $value = $convoArr[$index_1][$index_3];
    }
    elseif ((isset ($convoArr[$index_1][$index_3][$index_4])) && (!is_array($convoArr[$index_1][$index_3][$index_4])) && ($convoArr[$index_1][$index_3][$index_4] != ''))
    {
      $value = $convoArr[$index_1][$index_3][$index_4];
    }
    elseif ((isset ($convoArr[$index_1][$index_2])) && (!is_array($convoArr[$index_1][$index_2])) && ($convoArr[$index_1][$index_2] != ''))
    {
      $value = $convoArr[$index_1][$index_2];
    }
    elseif ((isset ($convoArr[$index_1][$index_2][$index_3])) && (!is_array($convoArr[$index_1][$index_2][$index_3])) && ($convoArr[$index_1][$index_2][$index_3] != ''))
    {
      $value = $convoArr[$index_1][$index_2][$index_3];
    }
    elseif ((isset ($convoArr[$index_1][$index_2][$index_3][$index_4])) && (!is_array($convoArr[$index_1][$index_2][$index_3][$index_4])) && ($convoArr[$index_1][$index_2][$index_3][$index_4] != ''))
    {
      $value = $convoArr[$index_1][$index_2][$index_3][$index_4];
    }
    else
    {
      $value = '';
    }
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Get from ConvoArr [$index_1][$index_2][$index_3][$index_4] FOUND: ConvoArr Value = '$value'", 4);
    return $value;
  }

  function get_client_property($convoArr, $name)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Rummaging through the DB and stuff for a client property.', 2);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Looking for client property '$name'", 2);
    global $con, $dbn;
    If (isset($convoArr['client_properties'][$name]))
    {
      $value = $convoArr['client_properties'][$name];
      runDebug(__FILE__, __FUNCTION__, __LINE__, "Found client property '$name' in the conversation array. Returning '$value'", 2);
      return $convoArr['client_properties'][$name];
    }
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Client property '$name' not found in the conversation array. Searching the DB.", 2);
    $user_id = $convoArr['conversation']['user_id'];
    $bot_id = $convoArr['conversation']['bot_id'];
    $sql = "select `value` from `$dbn`.`client_properties` where `user_id` = $user_id and `bot_id` = $bot_id and `name` = '$name' limit 1;";
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Querying the client_properties table for $name. SQL:\n$sql", 3);
    $result = db_query($sql, $con);
    $rowCount = mysql_num_rows($result);
    if ($rowCount != 0)
    {
      $row = mysql_fetch_assoc($result);
      $response = $row['value'];
      $convoArr['client_properties'][$name] = $response;
      runDebug(__FILE__, __FUNCTION__, __LINE__, "Found client property '$name' in the DB. Adding it to the conversation array and returning '$response'", 2);

    }
    else $response = 'undefined';
    mysql_free_result($result);
    return $response;
    }

  /**
  * function find_userdefined_aiml()
  * This function searches the user defined aiml patterns first
  * It will show an unmoderated response if the user_id's match
  * Or if you wish to approve a response to everyone set the user_id to -1
  * @param array $convoArr - conversation array
  * @return array allrows
  **/
  function find_userdefined_aiml($convoArr)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Looking for user defined responses', 4);
    global $dbn, $con;
    $i = 0;
    $allrows = array();
    $bot_id = $convoArr['conversation']['bot_id'];
    $user_id = $convoArr['conversation']['user_id'];
    $lookingfor = mysql_real_escape_string($convoArr['aiml']['lookingfor']);
    //build sql
    $sql = "SELECT * FROM `$dbn`.`aiml_userdefined` WHERE
		`bot_id` = '$bot_id' AND
		(`user_id` = '$user_id' OR `user_id` = '-1') AND
		`pattern` = '$lookingfor'";
    runDebug(__FILE__, __FUNCTION__, __LINE__, "User defined SQL: $sql", 3);
    $result = db_query($sql, $con);
    //if there is a result get it
    if (($result) && (mysql_num_rows($result) > 0))
    {
    //loop through results
      while ($row = mysql_fetch_assoc($result))
      {
        $allrows['pattern'] = $row['pattern'];
        $allrows['thatpattern'] = $row['thatpattern'];
        $allrows['template'] = $row['template'];
        $allrows['topic'] = $row['topic'];
        $i++;
      }
    }
    mysql_free_result($result);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "User defined rows found: '$i'", 2);
    //return rows
    return $allrows;
  }

  /**
  * function get_aiml_to_parse()
  * This function controls all the process to match the aiml in the db to the user input
  * @param array $convoArr - conversation array
  * @return array $convoArr
  **/
  function get_aiml_to_parse($convoArr)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Running all functions to get the correct aiml from the DB", 4);
    $lookingfor = $convoArr['aiml']['lookingfor'];
    $raw_that = (isset($convoArr['that'])) ? print_r($convoArr['that'], true) : '';
    $current_thatpattern = (isset($convoArr['that'][1][1])) ? $convoArr['that'][1][1] : '';
    $current_topic = get_topic($convoArr);
    $aiml_pattern = $convoArr['conversation']['default_aiml_pattern'];
    $bot_parent_id = $convoArr['conversation']['bot_parent_id'];
    $sendConvoArr = $convoArr;
    //check if match in user defined aiml
    $allrows = find_userdefined_aiml($convoArr);
    //if there is no match in the user defined aiml tbl
    if ((!isset ($allrows)) || (count($allrows) <= 0))
    {
    //look for a match in the normal aiml tbl
      $allrows = find_aiml_matches($convoArr);
      //unset all irrelvant matches
      $allrows = unset_all_bad_pattern_matches($allrows, $lookingfor, $current_thatpattern, $current_topic, $aiml_pattern);
      //score the relevant matches
      $allrows = score_matches($convoArr, $bot_parent_id, $allrows, $lookingfor, $current_thatpattern, $current_topic, $aiml_pattern);
      //get the highest
      $allrows = get_highest_score_rows($allrows, $lookingfor);
      //READY FOR v2.5 do not uncomment will not work
      //check if this is an unknown input and place in the unknown_inputs tbl if true
      //check_and_add_unknown_inputs($allrows,$convoArr);
    }
    //Now we have the results put into the conversation array
    $convoArr['aiml']['pattern'] = $allrows['pattern'];
    $convoArr['aiml']['thatpattern'] = $allrows['thatpattern'];
    $convoArr['aiml']['template'] = $allrows['template'];
    $convoArr['aiml']['html_template'] = '';
    $convoArr['aiml']['topic'] = $allrows['topic'];
    $convoArr['aiml']['score'] = $allrows['score'];
    $convoArr['aiml']['aiml_id'] = $allrows['aiml_id'];
    //return
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Will be parsing id:" . $allrows['aiml_id'] . " (" . $allrows['pattern'] . ")", 4);
    return $convoArr;
  }


  /**
  * function check_and_add_unknown_inputs()
  * READY FOR v2.5 
  * This function adds inputs without a response to the unknown_inputs table
  * @param array $allrows - the highest scoring return rows
  * @param array $convoArr - conversation array
  * @return void
  **/
  function check_and_add_unknown_inputs($allrows,$convoArr){
    if($allrows['pattern']==$convoArr['conversation']['default_aiml_pattern']){
        global $con, $dbn;
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Adding unknown input", 2);
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Pattern: ".$convoArr['aiml']['lookingfor'], 2);
        $pattern = trim(normalize_text($convoArr['aiml']['lookingfor']));
        $pattern = mysql_real_escape_string($pattern . " ");
        $u_id = $convoArr['conversation']['user_id'];
        $bot_id = $convoArr['conversation']['bot_id'];
        $sql = "INSERT INTO `$dbn`.`unknown_inputs`
            VALUES
            (NULL, '".mysql_real_escape_string($pattern)."','$bot_id','$u_id',NOW())";
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Unknown Input SQL: $sql", 3);
        $res = mysql_query($sql, $con);
    }
  }


  /**
  * function find_aiml_matches()
  * This function builds the sql to use to get a match from the tbl
  * @param array $convoArr - conversation array
  * @return array $convoArr
  **/
  function find_aiml_matches($convoArr)
  {
    global $con, $dbn, $error_response, $use_parent_bot;
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Finding the aiml matches from the DB", 4);
    $i = 0;
    //TODO convert to get_it
    $bot_id = $convoArr['conversation']['bot_id'];
    $bot_parent_id = $convoArr['conversation']['bot_parent_id'];
    $aiml_pattern = $convoArr['conversation']['default_aiml_pattern'];
    #$lookingfor = get_convo_var($convoArr,"aiml","lookingfor");
    $lookingfor = mysql_real_escape_string($convoArr['aiml']['lookingfor']);
    //get the first and last words of the cleaned user input
    $lastInputWord = get_last_word($lookingfor);
    $firstInputWord = get_first_word($lookingfor);
    //get the stored topic
    $storedtopic = (isset($convoArr['topic'][1])) ? mysql_real_escape_string($convoArr['topic'][1]) : '';
    if ($storedtopic == '') $storedtopic = get_topic($convoArr);
    //get the cleaned user input
    $lastthat =  (isset($convoArr['that'][1][1])) ? $convoArr['that'][1][1] : '';
    //build like patterns
    if ($lastthat != '')
    {
      $lastthat =  mysql_real_escape_string($lastthat);
      $thatPatternSQL = " OR " . make_like_pattern($lastthat, 'thatpattern');
    }
    else
    {
      $thatPattern = '';
      //$lastThatPattern = '';
      //$firstThatPattern = '';
      $thatPatternSQL = '';
      //$storedthatpattern ='';
    }
    //get the word count
    $word_count = wordsCount_inSentence($lookingfor);
    if ($bot_parent_id != 0)
    {
      $sql_bot_select = " (bot_id = '$bot_id' OR bot_id = '$bot_parent_id') ";
    }
    else
    {
      $sql_bot_select = " bot_id = '$bot_id' ";
    }
    if ($word_count == 1)
    {
    //if there is one word do this
      $sql = "SELECT `id`, `bot_id`, `pattern`, `thatpattern`, `topic` FROM `$dbn`.`aiml` WHERE
		$sql_bot_select AND (
		((`pattern` = '_') OR (`pattern` = '*') OR (`pattern` = '$lookingfor') OR (`pattern` = '$aiml_pattern' ) )
		AND	((`thatpattern` = '_') OR (`thatpattern` = '*') OR (`thatpattern` = '') OR (`thatpattern` = '$lastthat') $thatPatternSQL )
		AND ( (`topic`='') OR (`topic`='$storedtopic')))";
    }
    else
    {
    //otherwise do this
      $sql_add = make_like_pattern($lookingfor, 'pattern');
      $sql = "SELECT `id`, `bot_id`, `pattern`, `thatpattern`, `topic` FROM `$dbn`.`aiml` WHERE
		$sql_bot_select AND (
		((`pattern` = '_') OR
		 (`pattern` = '*') OR
		 (`pattern` like '$lookingfor') OR
		 ($sql_add) OR
		 (`pattern` = '$aiml_pattern' ))
		AND	((`thatpattern` = '_') OR (`thatpattern` = '*') OR (`thatpattern` = '') OR (`thatpattern` like '%') OR (`thatpattern` = '$lastthat') $thatPatternSQL )
		AND ((`topic`='') OR (`topic`='$storedtopic')));";
    }
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Match AIML sql: $sql", 3);
    $result = db_query($sql, $con);
    if (($result) && (mysql_num_rows($result) > 0))
    {
      $tmp_rows = number_format(mysql_num_rows($result));
      runDebug(__FILE__, __FUNCTION__, __LINE__, "FOUND: '" . mysql_num_rows($result) . "' potential AIML matches", 2);
      $tmp_content = date('H:i:s') . ": SQL:\n$sql\nRows = $tmp_rows\n\n";
      //loop through results
      while ($row = mysql_fetch_assoc($result))
      {
        $allrows[$i]['aiml_id'] = $row['id'];
        $allrows[$i]['bot_id'] = $row['bot_id'];
        $allrows[$i]['pattern'] = $row['pattern'];
        $allrows[$i]['thatpattern'] = $row['thatpattern'];
        $allrows[$i]['topic'] = $row['topic'];
        $i++;
        $mu = memory_get_usage(true);
        if ($mu >= MEM_TRIGGER)
        {
          runDebug(__FILE__, __FUNCTION__, __LINE__,'Current operation exceeds memory threshold. Aborting data retrieval.', 0);
          break;
        }
      }
    }
    else
    {
      runDebug(__FILE__, __FUNCTION__, __LINE__, "Error: FOUND NO AIML matches in DB", 1);
      $allrows[$i]['aiml_id'] = "-1";
      $allrows[$i]['bot_id'] = "-1";
      $allrows[$i]['pattern'] = "no results";
      $allrows[$i]['thatpattern'] = '';
      $allrows[$i]['topic'] = '';
    }
    mysql_free_result($result);
    return $allrows;
  }

  function get_topic($convoArr)
  {
    global $con,$dbn;
    $bot_id = $convoArr['conversation']['bot_id'];
    $user_id = $convoArr['conversation']['user_id'];
    $sql = "SELECT `value` FROM `client_properties` WHERE `user_id` = $user_id AND `bot_id` = $bot_id and `name` = 'topic';";
    $result = db_query($sql, $con);
    $num_rows = mysql_num_rows($result);
    if ($num_rows == 0) return '';
    $row = mysql_fetch_assoc($result);
    mysql_free_result($result);
    $retval = $row['value'];
    return $retval;
  }

?>
