<?php

  /***************************************
    * http://www.program-o.com
    * PROGRAM O
    * Version: 2.4.4
    * FILE: find_aiml.php
    * AUTHOR: Elizabeth Perreau and Dave Morton
    * DATE: 12-01-2014
    * DETAILS: Contains functions that find and score the most likely AIML match from the database
    ***************************************/
  /**
   * This file contains functions that find and score the most likely AIML match from the database
   *
   * @version 2.4.4
   * @filename find_aiml.php
   * @author Elizabeth Perreau and Dave Morton
   * @since MAY 17TH 2014
   **/

  /**
   * Gets the last word from a sentence
   *
   * @param  string $sentence
   * @return string
   **/
  function get_last_word($sentence)
  {
    $wordArr = explode(' ', $sentence);
    $last_word = trim($wordArr[count($wordArr) - 1]);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Sentence: $sentence. Last word:$last_word", 4);
    return $last_word;
  }

  /**
   * Gets the first word from a sentence
   * @param  string $sentence
   * @return string
   **/
  function get_first_word($sentence)
  {
    $wordArr = explode(' ', $sentence);
    $first_word = trim($wordArr[0]);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Sentence: $sentence. First word:$first_word", 4);
    return $first_word;
  }

  /**
   * Gets an input sentence from the user and a aiml tag and creates an sql like pattern
   * @param  string $sentence
   * @param  string $field
   * @return string
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
    return $sql_like_pattern;
  }

  /**
   * Counts the words in a sentence
   * @param  string $sentence
   * @return int
   **/
  function wordsCount_inSentence($sentence)
  {
    $wordArr = explode(" ", $sentence);
    $wordCount = count($wordArr);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Sentence: $sentence numWords:$wordCount", 4);
    return $wordCount;
  }

  /**
   * Takes all the sql results passed to the function and filters out the irrelevant ones
   *
   * @param array  $convoArr
   * @param array  $allrows
   * @param string $lookingfor
   * @internal param string $current_thatpattern
   * @internal param string $current_topic
   * @return array
   **/
  function unset_all_bad_pattern_matches($convoArr, $allrows, $lookingfor)
  {
    global $default_pattern;
    $current_topic = get_topic($convoArr);
    $current_thatpattern = (isset ($convoArr['that'][1][1])) ? $convoArr['that'][1][1] : '';
    $relevantRow = array();
    //if default pattern keep
    //if wildcard pattern matches found aiml keep
    //if wildcard pattern and wildard thatpattern keep
    //the end......
    runDebug(__FILE__, __FUNCTION__, __LINE__, "NEW FUNC Searching through " . count($allrows) . " rows to unset bad matches", 4);
    if (($allrows[0]['pattern'] == "no results") and (count($allrows) == 1))
    {
      $tmp_rows[0] = $allrows[0];
      $tmp_rows[0]['score'] = 1;
      runDebug(__FILE__, __FUNCTION__, __LINE__, "Returning error as no results where found", 1);
      return $tmp_rows;
    }
    $i = 0;
    $j = 0;
    $message = array();
    //loop through the results array
    foreach ($allrows as $all => $subrow)
    {
      $message[$j]['new turn looking for'] = "$lookingfor";
      $message[$j]['found pattern'] = $subrow['pattern'];
      $message[$j]['found thatpattern'] = $subrow['thatpattern'];
      $message[$j]['found topic'] = $subrow['topic'];
      $message[$j]['checking against'] = implode(",", $subrow);
      //get the pattern
      $aiml_pattern = $subrow['pattern'];
      $aiml_pattern = (IS_MB_ENABLED) ? mb_strtolower($aiml_pattern) : strtolower($aiml_pattern);
      $aiml_pattern_wildcards = match_wildcard_rows($aiml_pattern);
      //get the that pattern
      $aiml_thatpattern = $subrow['thatpattern'];
      $aiml_thatpattern = (IS_MB_ENABLED) ? mb_strtolower($aiml_thatpattern) : strtolower($aiml_thatpattern);
      //get topic pattern
      $topicMatch = FALSE;
      $aiml_topic = trim($subrow['topic']);
      $aiml_topic = (IS_MB_ENABLED) ? mb_strtolower($aiml_topic) : strtolower($aiml_topic);
      $current_topic_lc = (IS_MB_ENABLED) ? mb_strtolower($current_topic) : strtolower($current_topic);
      if ($aiml_topic == '')
      {
        $topicMatch = TRUE;
      }
      elseif (($aiml_topic == $current_topic_lc))
      {
        $topicMatch = TRUE;
        $message[$j]['topic match'] = "Found topic match $aiml_topic and $current_topic_lc";
      }
      else
      {
        $message[$j]['topic match'] = "NO topic match $aiml_topic and $current_topic_lc";
        $topicMatch = FALSE;
      }
      $message[$j]['have made the pattern wildcard to do reg exp'] = $aiml_pattern_wildcards;
      preg_match($aiml_pattern_wildcards, $lookingfor, $matches);
      if (count($matches) > 0)
      {
        $aiml_patternmatch = TRUE;
        $message[$j]['found some matches'] = $matches[0];
        $message[$j]['using'] = "$aiml_pattern_wildcards REGEXPIN $lookingfor";
      }
      else
      {
        $aiml_patternmatch = FALSE;
      }
      $message[$j]['found a match'] = $aiml_patternmatch;
      $message[$j]['what is the current thatpattern'] = $current_thatpattern;
      $message[$j]['do we have a thatpattern'] = $aiml_patternmatch;
      if ($aiml_thatpattern != '')
      {
        $aiml_thatpattern_wildcards = match_wildcard_rows($aiml_thatpattern);
        preg_match($aiml_thatpattern_wildcards, $current_thatpattern, $thatmatches);
        if (count($thatmatches) > 0)
        {
          $aiml_thatpatternmatch = TRUE;
          $message[$j]['there are thatpattern matches'] = "$aiml_thatpattern_wildcards in $current_thatpattern";
          $message[$j]['thatpattern matches are'] = print_r($thatmatches, true);
        }
        else
        {
          $aiml_thatpatternmatch = FALSE;
          $message[$j]['there arent any thatpattern matches'] = "$aiml_thatpattern_wildcards in $current_thatpattern";
        }
      }
      else
      {
        $aiml_thatpattern_wildcards = FALSE;
        $message[$j]['no thatpattern'] = $aiml_thatpattern;
      }
      //if default pattern keep
      if (($aiml_pattern == $default_pattern) || (strtolower($aiml_pattern) == strtolower($default_pattern)) || (strtoupper($aiml_pattern) == strtoupper($default_pattern)))
      {
      //if it is a direct match with our default pattern then add to tmp_rows
        $tmp_rows[$i]['score'] = 0;
        $tmp_rows[$i]['track_score'] = "default pick up line ($aiml_pattern = $default_pattern) ";
      }
      elseif ((!$aiml_thatpattern_wildcards) && ($aiml_patternmatch)) // no thatpattern and a pattern match keep
      {
        $tmp_rows[$i]['score'] = 1;
        $tmp_rows[$i]['track_score'] = " no thatpattern in result and a pattern match";
      }
      elseif (($aiml_thatpattern_wildcards) && ($aiml_thatpatternmatch) && ($aiml_patternmatch)) //pattern match and a wildcard match on the thatpattern keep
      {
        $tmp_rows[$i]['score'] = 2;
        $tmp_rows[$i]['track_score'] = " thatpattern match and a pattern match";
      }
      else
      {
        $tmp_rows[$i]['score'] = - 1;
        $tmp_rows[$i]['track_score'] = "dismissing nothing is matched";
      }
      if ($topicMatch === FALSE)
      {
        $tmp_rows[$i]['score'] = - 1;
        $tmp_rows[$i]['track_score'] = "dismissing wrong topic";
      }
      if ($tmp_rows[$i]['score'] >= 0)
      {
        $relevantRow[] = $subrow;
      }
      $message[$j]['score'] = $tmp_rows[$i]['score'];
      $message[$j]['track score'] = $tmp_rows[$i]['track_score'];
      $i++;
      $j++;
    }
    sort2DArray("show top scoring aiml matches", $relevantRow, "good matches", 1, 10);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Found " . count($relevantRow) . " relevant rows", 4);
    return $relevantRow;
  }

  /**
   * Takes a sentence and converts AIML wildcards to Regular Expression wildcards
   * so that it can be matched in php using pcre search functions
   *
   * @param string $item
   * @return string
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
   * Takes all the relevant sql results and scores them to find the most likely match with the aiml
   *
   * @param array  $convoArr
   * @param array  $allrows
   * @param string $lookingfor
   * @internal param int $bot_parent_id
   * @internal param string $current_thatpattern
   * @internal param string $current_topic
   * @return array
   **/
  function score_matches($convoArr, $allrows, $lookingfor)
  {
    global $common_words_array, $default_pattern;
    $current_thatpattern = (isset ($convoArr['that'][1][1])) ? $convoArr['that'][1][1] : '';
    $current_topic = get_topic($convoArr);
    $aiml_pattern = $convoArr['conversation']['default_aiml_pattern'];
    $bot_parent_id = $convoArr['conversation']['bot_parent_id'];
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
      if (($aiml_topic == $current_topic) && ($current_topic != ''))
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
      elseif (($aiml_pattern == "*") && ($aiml_thatpattern != ""))
      {
        if (($aiml_thatpattern_wildcards != '') && (preg_match($aiml_thatpattern_wildcards, $current_thatpattern, $m)))
        {
          $allrows[$all]['score'] += $that_pattern_match;
          $allrows[$all]['track_score'] = "general aiml that pattern match";
        }
      }
      //if stored result == default pattern increase score
      $aiml_pattern = (IS_MB_ENABLED) ? mb_strtolower($aiml_pattern) : strtolower($aiml_pattern);
      $default_pattern = (IS_MB_ENABLED) ? mb_strtolower($default_pattern) : strtolower($default_pattern);
      if ($aiml_pattern == $default_pattern)
      {
        $allrows[$all]['score'] += $pattern_points;
        $allrows[$all]['track_score'] .= "j";
      }
      elseif ($aiml_pattern == "*")
      {
      //if stored result == * increase score
        $allrows[$all]['score'] += $starscore_points;
        $allrows[$all]['track_score'] .= "k";
      }
      elseif ($aiml_pattern == "_")
      {
      //if stored result == _ increase score
        $allrows[$all]['score'] += $underscore_points;
        $allrows[$all]['track_score'] .= "l";
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
            $allrows[$all]['track_score'] .= "m";
          }
          if (in_Array($word, $common_words_array))
          {
          // if it is a commonword increase with (lower) score
            $allrows[$all]['score'] += $common_word_points;
            $allrows[$all]['track_score'] .= "n";
          }
          elseif ($word == "*")
          {
            $allrows[$all]['score'] += $starscore_points;
            //if it is a star wildcard increase score
            $allrows[$all]['track_score'] .= "o";
          }
          elseif ($word == "_")
          {
            $allrows[$all]['score'] += $underscore_points;
            //if it is a underscore wildcard increase score
            $allrows[$all]['track_score'] .= "p";
          }
          else
          {
            $allrows[$all]['score'] += $uncommon_word_points;
            //else it must be an uncommon word so increase the score
            $allrows[$all]['track_score'] .= "q";
          }
        }
      }
    }
    //send off for debugging
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Unsorted array \$allrows:\n" . print_r($allrows, true), 4);
    sort2DArray("show top scoring aiml matches", $allrows, "score", 1, 10);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Sorted array \$allrows:\n" . print_r($allrows, true), 4);
    return $allrows;
    //return the scored rows
  }

  /**
   * Small helper function to sort a 2d array
   *
   * @param string $opName
   * @param array $thisArr
   * @param $sortByItem
   * @param $sortAsc
   * @param $limit
   * @return void;
   **/
  function sort2DArray($opName, $thisArr, $sortByItem, $sortAsc = 1, $limit = 10)
  {
    $thisCount = count($thisArr);
    /** @noinspection PhpUnusedLocalVariableInspection */
    $showLimit = ($thisCount < $limit) ? $thisCount : $limit;
    $i = 0;
    $tmpSortArr = array();
    $resArr = array();
    /** @noinspection PhpUnusedLocalVariableInspection */
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
  }

  /**
  * function get_highest_scoring_row()
  * This function takes all the relevant and scored aiml results
  * and saves the highest scoring rows
  * @param array $convoArr - the conversation array
  * @param array $allrows - all the results
  * @param string $lookingfor - the user input
  * @return array bestResponseArr - best response and its parts (topic etc)
  **/
  function get_highest_scoring_row(& $convoArr, $allrows, $lookingfor)
  {
    $bestResponse = array();
    $last_high_score = 0;
    $tmpArr = array();
    //loop through the results
    foreach ($allrows as $all => $subrow)
    {
      if (!isset ($subrow['score']))
      {
        continue;
      }
      elseif ($subrow['score'] > $last_high_score)
      {
      //if higher than last score then reset tmp array and store this result
        $tmpArr = array($subrow);
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
    if (false !== $bestResponse) $bestResponse['template'] = get_winning_category($convoArr, $bestResponse['aiml_id']);
    $cRes = count($tmpArr);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Best Responses: " . print_r($tmpArr, true), 4);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Will use randomly picked best response chosen out of $cRes responses with same score: " . $bestResponse['aiml_id'] . " - " . $bestResponse['pattern'], 2);
    //return the best response
    return $bestResponse;
  }

  /**
   * function get_winning_category
   * Retrieves the AIML template from the selected DB entry
   *
   * @param       $convoArr
   * @param array $id - the id number of the AIML category to get
   * @return string $template - the value of the `template` field from the chosen DB entry
   */
  function get_winning_category(& $convoArr, $id)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, "And the winner is... $id!", 2);
    global $dbConn, $dbn, $error_response;
    $sql = "SELECT `template` from `$dbn`.`aiml` where `id` = $id limit 1;";
    $row = db_fetch($sql, null, __FILE__, __FUNCTION__, __LINE__);
/*
    $sth = $dbConn->prepare($sql);
    $sth->execute();
    $row = $sth->fet ch();
*/
    if ($row)
    {
      $template = $row['template'];
      $convoArr['aiml']['template_id'] = $id;
    }
    else
    {
      $template = $error_response;
    }
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Returning the AIML template for id# $id. Value:\n'$template'", 4);
    return $template;
  }

  /**
   * function get_convo_var()
   * This function takes fetches a variable from the conversation array
   *
   * @param array      $convoArr - conversation array
   * @param string     $index_1  - convoArr[$index_1]
   * @param string     $index_2  - convoArr[$index_1][$index_2]
   * @param int|string $index_3  - convoArr[$index_1][$index_2][$index_3]
   * @param int|string $index_4  - convoArr[$index_1][$index_2][$index_3][$index_4]
   * @return string $value - the value of the element
   *
   *
   * examples
   *
   * $convoArr['conversation']['bot_id'] = $convoArr['conversation']['bot_id']
   * $convoArr['that'][1][1] = get_convo_var($convoArr,'that','',1,1)
   */
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

  /**
  * Function: get_client_property()
  * Summary: Extracts a value from the the client properties subarray within the main conversation array
  * @param Array $convoArr - the main conversation array
  * @param String $name - the key of the value to extract from client properties
  * @return String $response - the value of the client property
  **/
  function get_client_property($convoArr, $name)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Rummaging through the DB and stuff for a client property.', 2);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Looking for client property '$name'", 2);
    global $dbConn, $dbn;
    If (isset ($convoArr['client_properties'][$name]))
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
    $row = db_fetch($sql, null, __FILE__, __FUNCTION__, __LINE__);
/*
    $sth = $dbConn->prepare($sql);
    $sth->execute();
    $row = $sth->fet ch();
*/
    $rowCount = count($row);
    if ($rowCount != 0)
    {
      $response = trim($row['value']);
      $convoArr['client_properties'][$name] = $response;
      runDebug(__FILE__, __FUNCTION__, __LINE__, "Found client property '$name' in the DB. Adding it to the conversation array and returning '$response'", 2);
    }
    else $response = 'undefined';
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
    global $dbn, $dbConn;
    $i = 0;
    $allrows = array();
    $bot_id = $convoArr['conversation']['bot_id'];
    $user_id = $convoArr['conversation']['user_id'];
    $lookingfor = $convoArr['aiml']['lookingfor'];
    //build sql
    $sql = "SELECT * FROM `$dbn`.`aiml_userdefined` WHERE
    `bot_id` = '$bot_id' AND
    (`user_id` = '$user_id' OR `user_id` = '-1') AND
    `pattern` = '$lookingfor'";
    runDebug(__FILE__, __FUNCTION__, __LINE__, "User defined SQL: $sql", 3);
    $result = db_fetchAll($sql, null, __FILE__, __FUNCTION__, __LINE__);
/*
    $sth = $dbConn->prepare($sql);
    $sth->execute();
    $result = $sth->fet chAll();
*/
    $num_rows = count($result);
    //if there is a result get it
    if (($result) && ($num_rows > 0))
    {
    //loop through results
      foreach ($result as $row)
      {
        $allrows['pattern'] = $row['pattern'];
        $allrows['thatpattern'] = $row['thatpattern'];
        $allrows['template'] = $row['template'];
        $allrows['topic'] = $row['topic'];
        $i++;
      }
    }
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
    $current_thatpattern = (isset ($convoArr['that'][1][1])) ? $convoArr['that'][1][1] : '';
    $current_topic = get_topic($convoArr);
    $aiml_pattern = $convoArr['conversation']['default_aiml_pattern'];
    $bot_parent_id = $convoArr['conversation']['bot_parent_id'];
    $raw_that = (isset ($convoArr['that'])) ? print_r($convoArr['that'], true) : '';
    //check if match in user defined aiml
    $allrows = find_userdefined_aiml($convoArr);
    //if there is no match in the user defined aiml table
    if ((!isset ($allrows)) || (count($allrows) <= 0))
    {
      //look for a match in the normal aiml tbl
      $allrows = find_aiml_matches($convoArr);
      //unset all irrelvant matches
      $allrows = unset_all_bad_pattern_matches($convoArr, $allrows, $lookingfor);
      //score the relevant matches
      $allrows = score_matches($convoArr, $allrows, $lookingfor);
      //get the highest
      $allrows = get_highest_scoring_row($convoArr, $allrows, $lookingfor);
      //READY FOR v2.5 do not uncomment will not work
      //check if this is an unknown input and place in the unknown_inputs table if true
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
  function check_and_add_unknown_inputs($allrows, $convoArr)
  {
    if ($allrows['pattern'] == $convoArr['conversation']['default_aiml_pattern'])
    {
      global $dbConn, $dbn;
      runDebug(__FILE__, __FUNCTION__, __LINE__, "Adding unknown input", 2);
      runDebug(__FILE__, __FUNCTION__, __LINE__, "Pattern: " . $convoArr['aiml']['lookingfor'], 2);
      $pattern = trim(normalize_text($convoArr['aiml']['lookingfor']));
      $pattern = $pattern . " ";
      $u_id = $convoArr['conversation']['user_id'];
      $bot_id = $convoArr['conversation']['bot_id'];
      $sql = "INSERT INTO `$dbn`.`unknown_inputs`
            VALUES
            (NULL, '" . $pattern . "','$bot_id','$u_id',NOW())";
      runDebug(__FILE__, __FUNCTION__, __LINE__, "Unknown Input SQL: $sql", 3);
      $sth = $dbConn->prepare($sql);
      $sth->execute();
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
    global $dbConn, $dbn, $error_response, $use_parent_bot;
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Finding the aiml matches from the DB", 4);
    $i = 0;
    //TODO convert to get_it
    $bot_id = $convoArr['conversation']['bot_id'];
    $bot_parent_id = $convoArr['conversation']['bot_parent_id'];
    $aiml_pattern = $convoArr['conversation']['default_aiml_pattern'];
    #$lookingfor = get_convo_var($convoArr,"aiml","lookingfor");
    $convoArr['aiml']['lookingfor'] = str_replace('  ', ' ', $convoArr['aiml']['lookingfor']);
    $lookingfor = trim(strtoupper($convoArr['aiml']['lookingfor']));
    //get the first and last words of the cleaned user input
    $lastInputWord = get_last_word($lookingfor);
    $firstInputWord = get_first_word($lookingfor);
    //get the stored topic
    $storedtopic = get_topic($convoArr);
    //get the cleaned user input
    $lastthat = (isset ($convoArr['that'][1][1])) ? $convoArr['that'][1][1] : '';
    //build like patterns
    if ($lastthat != '')
    {
      $thatPatternSQL = " OR " . make_like_pattern($lastthat, 'thatpattern');
    }
    else
    {
      $thatPattern = '';
      $thatPatternSQL = '';
    }
    //get the word count
    $word_count = wordsCount_inSentence($lookingfor);
    if ($bot_parent_id != 0 and $bot_parent_id != $bot_id)
    {
      $sql_bot_select = " (bot_id = '$bot_id' OR bot_id = '$bot_parent_id') ";
    }
    else
    {
      $sql_bot_select = " bot_id = '$bot_id' ";
    }
    if ($storedtopic != '')
    {
      $topic_select = "AND ((`topic`='') OR (`topic`='$storedtopic'))";
    }
    else
      $topic_select = '';
    if ($word_count == 1)
    {
    //if there is one word do this
      $sql = "SELECT `id`, `bot_id`, `pattern`, `thatpattern`, `topic` FROM `$dbn`.`aiml` WHERE
    $sql_bot_select AND (
    ((`pattern` = '_') OR (`pattern` = '*') OR (`pattern` = '$lookingfor') OR (`pattern` = '$aiml_pattern' ) )
    $topic_select) order by `topic` desc, `id` desc, `pattern` asc;";
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
    $topic_select) order by `topic` desc, `id` desc, `pattern` asc;";
    }
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Match AIML sql: $sql", 3);
    $result = db_fetchAll($sql, null, __FILE__, __FUNCTION__, __LINE__);
/*
    $sth = $dbConn->prepare($sql);
    $sth->execute();
    $result = $sth->fet chAll();
*/
    $num_rows = count($result);
    if (($result) && ($num_rows > 0))
    {
      $tmp_rows = number_format($num_rows);
      runDebug(__FILE__, __FUNCTION__, __LINE__, "FOUND: ($num_rows) potential AIML matches", 2);
      $tmp_content = date('H:i:s') . ": SQL:\n$sql\nRows = $tmp_rows\n\n";
      //loop through results
      foreach ($result as $row)
      {
        $row['aiml_id'] = $row['id'];
        $row['score'] = 0;
        $row['track_score'] = '';
        $allrows[] = $row;
        $mu = memory_get_usage(true);
        if ($mu >= MEM_TRIGGER)
        {
          runDebug(__FILE__, __FUNCTION__, __LINE__, 'Current operation exceeds memory threshold. Aborting data retrieval.', 0);
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
    return $allrows;
  }

  /** get_topic()
   * Extracts the current topic directly from the database
   *
   * @param Array $convoArr - the conversation array
   *                        returns String $retval - the topic
   *                        *@return string
   */
  function get_topic($convoArr)
  {
    global $dbConn, $dbn, $bot_id;
    $bot_id = (!empty($convoArr['conversation']['bot_id'])) ? $convoArr['conversation']['bot_id'] : $bot_id;
    $user_id = $convoArr['conversation']['user_id'];
    $sql = "SELECT `value` FROM `client_properties` WHERE `user_id` = $user_id AND `bot_id` = $bot_id and `name` = 'topic';";
    $row = db_fetch($sql, null, __FILE__, __FUNCTION__, __LINE__);
    $num_rows = count($row);
    $retval = ($num_rows == 0) ? '' : $row['value'];
/*
    try
    {
      $sth = $dbConn->prepare($sql);
      $sth->execute();
      $row = $sth->fet ch();
      $num_rows = count($row);
      $retval = ($num_rows == 0) ? '' : $row['value'];
    }
    catch (Exception $e)
    {
      error_log("bad SQL encountered. SQL:\n$sql\n", 3, _LOG_PATH_ . 'badSQL.txt');
      $pdoError = print_r($dbConn->errorInfo(), true);
      $psError  = print_r($sth->errorInfo(), true);
      runDebug(__FILE__, __FUNCTION__, __LINE__, "Something went wrong!. SQL:\n$sql\nPDO error: $pdoError\nPDOStatement error: $psError", 0);
      $retval = '';
    }
*/
    return $retval;
  }

?>
