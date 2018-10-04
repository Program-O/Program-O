<?php
/***************************************
 * http://www.program-o.com
 * PROGRAM O
 * Version: 2.6.11
 * FILE: find_aiml.php
 * AUTHOR: Elizabeth Perreau and Dave Morton
 * DATE: FEB 01 2016
 * DETAILS: Contains functions that find and score the most likely AIML match from the database
 ***************************************/

/**
 * Takes all the sql results passed to the function and filters out the irrelevant ones
 *
 * @param array $convoArr
 * @param array $allrows
 * @param string $lookingfor
 * @internal param string $current_thatpattern
 * @internal param string $current_topic
 * @return array
 **/

function unset_all_bad_pattern_matches(&$convoArr, $allrows, $lookingfor)
{
    global $error_response;

    $lookingfor_lc = _strtolower($lookingfor);
    $current_topic = get_topic($convoArr);
    $current_topic_lc = _strtolower($current_topic);
    $current_thatpattern = (isset ($convoArr['that'][1][1])) ? $convoArr['that'][1][1] : '';

    //file_put_contents(_LOG_PATH_ . 'allrows.txt', print_r($allrows, true));
    if (!empty($current_thatpattern))
    {
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Current THAT = $current_thatpattern", 1);
    }

    $default_pattern = $convoArr['conversation']['default_aiml_pattern'];
    $default_pattern_lc = _strtolower($default_pattern);
    $tmp_rows = array();
    $relevantRows = array();
    //if default pattern keep
    //if direct pattern match keep
    //if wildcard or direct pattern match and direct or wildcard thatpattern match keep
    //if wildcard pattern matches found aiml keep
    //the end......
    $tmp_count = number_format(count($allrows));
    runDebug(__FILE__, __FUNCTION__, __LINE__, "NEW FUNC Searching through {$tmp_count} rows to unset bad matches", 4);
    //runDebug(__FILE__, __FUNCTION__, __LINE__, 'The allrows array:' . print_r($allrows, true), 4);

    // If no pattern was found, exit early
    if (($allrows[0]['pattern'] == "no results") && (count($allrows) == 1))
    {
        $tmp_rows[0] = $allrows[0];
        $tmp_rows[0]['score'] = 1;
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Returning error as no results where found", 1);

        return $tmp_rows;
    }

    //loop through the results array
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Blue 5 to Blue leader. Starting my run now! Looking for '$lookingfor'", 4);
    $i = 0;

    foreach ($allrows as $all => $subrow)
    {
        //get the pattern
        $aiml_pattern = _strtolower($subrow['pattern']);

        if (stripos($aiml_pattern, '<bot') !== false)
        {
            $startPos = stripos($aiml_pattern, '<bot');
            $endPos   = stripos($aiml_pattern, '>');
            $len = $endPos - $startPos + 1;
            $botTag = substr($aiml_pattern, $startPos, $len);
            $btReplace = parse_bot_tag($convoArr, new SimpleXMLElement($botTag));
            $aiml_pattern = str_ireplace($botTag, $btReplace, $aiml_pattern);
        }
        $aiml_pattern_wildcards = build_wildcard_RegEx($aiml_pattern);

        //get the that pattern
        $aiml_thatpattern = _strtolower($subrow['thatpattern']);
        $current_thatpattern = _strtolower($current_thatpattern);
        //get topic pattern
        $topicMatch = FALSE;
        $aiml_topic = _strtolower(trim($subrow['topic']));

        #Check for a matching topic
        $aiml_topic_wildcards = (!empty($aiml_topic)) ? build_wildcard_RegEx($aiml_topic) : '';

        if ($aiml_topic == '')
        {
            $topicMatch = TRUE;
        }
        elseif (($aiml_topic == $current_topic_lc))
        {
            $topicMatch = TRUE;
        }
        elseif (!empty($aiml_topic_wildcards))
        {
            preg_match($aiml_topic_wildcards, $current_topic_lc, $matches);
            $topicMatch = (count($matches) > 0) ? true : false;
        }
        else {
            $topicMatch = FALSE;
        }

        # check for a matching pattern
        //save_file(_LOG_PATH_ . 'aiml_pattern_wildcards.txt', print_r($aiml_pattern_wildcards, true) . "\n", true);
        set_error_handler('wildcard_handler', E_ALL);
        preg_match($aiml_pattern_wildcards, $lookingfor, $matches);
        restore_error_handler();
        $aiml_patternmatch = (count($matches) > 0) ? true : false;

        # look for a thatpattern match
        $aiml_thatpattern_wildcards = (!empty($aiml_thatpattern)) ? build_wildcard_RegEx($aiml_thatpattern) : '';
        $aiml_thatpattern_wc_matches = (!empty($aiml_thatpattern_wildcards)) ? preg_match_all($aiml_thatpattern_wildcards, $current_thatpattern, $matches) : 0;

        switch (true)
        {
            case ($aiml_thatpattern_wc_matches > 0):
            case ($current_thatpattern == $aiml_thatpattern):
                $aiml_thatpatternmatch = true;
                break;
            default:
                $aiml_thatpatternmatch = false;
        }

        // temporary debugging
        $tmp_rows[$i] = array();
        $tmp_rows[$i]['current_that'] = $current_thatpattern;

        if ($aiml_pattern == $default_pattern_lc)
        {
            //if it is a direct match with our default pattern then add to tmp_rows
            $tmp_rows[$i]['score'] = 1;
            $tmp_rows[$i]['track_score'] = "default pick up line ($aiml_pattern = $default_pattern) ";
        }
        elseif ((!$aiml_thatpattern_wildcards) && ($aiml_patternmatch)) // no thatpattern and a pattern match keep
        {
            $tmp_rows[$i]['score'] = 1;
            $tmp_rows[$i]['track_score'] = " no thatpattern in result and a pattern match";
        }
        elseif (($aiml_thatpattern_wildcards) && ($aiml_thatpatternmatch) && ($aiml_patternmatch)) //pattern match and a wildcard match on the thatpattern keep
        {
            $tmp_rows[$i]['score'] = 1;
            $tmp_rows[$i]['track_score'] = " thatpattern wildcard match and a pattern match";
        }
        elseif (($aiml_thatpatternmatch) && ($aiml_patternmatch)) //pattern match and a generic match on the thatpattern keep
        {
            $tmp_rows[$i]['score'] = 1;
            $tmp_rows[$i]['track_score'] = " thatpattern match and a pattern match";
        }
        elseif ($aiml_pattern == $lookingfor_lc) {
            $tmp_rows[$i]['score'] = 1;
            $tmp_rows[$i]['track_score'] = " direct pattern match";
        }
        else {
            $tmp_rows[$i]['score'] = -1;
            $tmp_rows[$i]['track_score'] = "dismissing nothing is matched";
        }

        if ($topicMatch === FALSE)
        {
            $tmp_rows[$i]['score'] = -1;
            $tmp_rows[$i]['track_score'] = "dismissing wrong topic";
        }

        if ($tmp_rows[$i]['score'] >= 0)
        {
            $relevantRows[] = $subrow;
        }
        $i++;
    }

    $rrCount = count($relevantRows);

    if ($rrCount == 0)
    {
        $i = 0;

        runDebug(__FILE__, __FUNCTION__, __LINE__, "Error: FOUND NO AIML matches in DB", 1);

        $relevantRows[$i]['aiml_id'] = "-1";
        $relevantRows[$i]['bot_id'] = "-1";
        $relevantRows[$i]['pattern'] = "no results";
        $relevantRows[$i]['thatpattern'] = '';
        $relevantRows[$i]['topic'] = '';
        $relevantRows[$i]['template'] = $error_response;
        $relevantRows[$i]['score'] = 0;
    }

    sort2DArray("show top scoring aiml matches", $relevantRows, "good matches", 1, 10);

    runDebug(__FILE__, __FUNCTION__, __LINE__, "Found " . count($relevantRows) . " relevant rows", 4);
    //file_put_contents(_LOG_PATH_ . 'relevantRow.txt', print_r($relevantRows, true));

    return $relevantRows;
}

/**
 * Takes a sentence and converts AIML wildcards to Regular Expression wildcards
 * so that it can be matched in php using pcre search functions
 *
 * @param string $item
 * @return string
 **/
function build_wildcard_RegEx($item)
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
 * Performs a pattern matching pass on an input string, checking
 * whether it matches a given pattern based on the AIML specification.
 *
 * @param string $pattern Pattern to match.
 * @param string $input Input string to check.
 * @return bool True if the string matches the pattern, false otherwise.
 **/
function aiml_pattern_match($pattern, $input)
{
    if (empty($input)) {
        return false;
    }

    $pattern_regex = build_wildcard_RegEx(_strtolower($pattern));

    return preg_match($pattern_regex, _strtolower($input)) === 1;
}

/**
 * Takes all the relevant sql results and scores them to find the most likely match with the aiml
 *
 * @param array $convoArr
 * @param array $allrows
 * @param string $pattern
 * @internal param int $bot_parent_id
 * @internal param string $current_thatpattern
 * @internal param string $current_topic
 * @return array $allrows
 **/
function score_matches(&$convoArr, $allrows, $pattern)
{
    global $common_words_array;

    runDebug(__FILE__, __FUNCTION__, __LINE__, "Scoring the matches.", 4);

    # obtain some values to test
    $topic = get_topic($convoArr);
    $that = (isset ($convoArr['that'][1][1])) ? $convoArr['that'][1][1] : '';
    $default_pattern = $convoArr['conversation']['default_aiml_pattern'];
    $bot_parent_id = $convoArr['conversation']['bot_parent_id'];
    $bot_id = $convoArr['conversation']['bot_id'];

    # set the scores for each type of word or sentence to be used in this function

    # full pattern match scores:
    $user_defined_match                = 300;
    $this_bot_match                    = 250;
    $underscore_match                  = 100;
    $topic_underscore_match            = 80;
    $topic_direct_match                = 50;
    $topic_star_match                  = 10;
    $thatpattern_underscore_match      = 45;
    $thatpattern_direct_match          = 15;
    $thatpattern_star_match            = 2;
    $direct_pattern_match              = 10;
    $pattern_direct_match              = 7;
    $pattern_star_match                = 1;
    $default_pattern_match             = 5;

    # individual word match scores:
    $uncommon_word_match               = 8;
    $common_word_match                 = 1;
    $direct_word_match                 = 2;
    $thatpattern_direct_word_match     = 2;
    $underscore_word_match             = 25;
    $thatpattern_underscore_word_match = 25;
    $star_word_match                   = 1;
    $thatpattern_star_word_match       = 1;
    $rejected                          = -1000;

    # loop through all relevant results
    foreach ($allrows as $all => $subrow)
    {
        $category_bot_id        = isset($subrow['bot_id']) ? $subrow['bot_id'] : 1;
        $category_topic         = $subrow['topic'];
        $category_thatpattern   = $subrow['thatpattern'];
        $category_pattern       = $subrow['pattern'];
        $check_pattern_words    = true;

        # make it all lower case, to make it easier to test, and do it using mbstring functions if possible
        $category_pattern_lc        = _strtolower($category_pattern);
        $category_thatpattern_lc    = _strtolower($category_thatpattern);
        $category_topic_lc          = _strtolower($category_topic);
        $default_pattern_lc         = _strtolower($default_pattern);
        $pattern_lc                 = _strtolower($pattern);
        $topic_lc                   = _strtolower($topic);
        $that_lc                    = _strtolower($that);
        $subrow['current_that'] = $that_lc;

        // Start scoring here
        $current_score = 0;
        $track_matches = '';

        # 1.) Check for current bot, rather than parent
        if ($category_bot_id == $bot_id)
        {
            $current_score += $this_bot_match;
            $track_matches .= "current bot ({$this_bot_match} points), ";
        }
        elseif ($category_bot_id == $bot_parent_id)
        {
            $current_score += 0;
            $track_matches .= "parent bot (0 points), ";
        }
        else # if it's not the current bot and not the parent bot, then reject it and log a debug message (this should never happen)
        {
            $current_score = $rejected;
            runDebug(__FILE__, __FUNCTION__, __LINE__, 'Found an error trying to identify the chatbot.', 1);

            unset($allrows[$all]);
            continue;
        }

        # 2.) Is this user defined AIML?
        if (isset($subrow['aiml_userdefined']))
        {
            $current_score += $user_defined_match;
            $track_matches .= "User Defined AIML ({$user_defined_match} points), ";
        }

        # 3.) test for a non-empty, current topic
        if (!empty($topic))
        {
            # 3a.) test for a non-empty topic in the current category
            if (empty($category_topic) || $category_topic == '*')
            {
                // take no action, as we're not looking for a topic here
                $track_matches .= "no topic to match (0 points), ";
            }
            else
            {
                # 3b.) create a RegEx to test for underscore matches
                if (strpos($category_topic, '_') !== false)
                {
                    $regEx = str_replace('_', '(.*)', $category_topic);
                    if ($regEx != $category_topic && preg_match("/$regEx/i", $topic) === 1)
                    {
                        $current_score += $topic_underscore_match;
                        $track_matches .= "topic match with underscore ({$topic_underscore_match} points), ";
                    }
                } # 3c.) Check for a direct topic match
                elseif ($topic == $category_topic)
                {
                    $current_score += $topic_direct_match;
                    $track_matches .= "direct topic match ({$topic_direct_match} points), ";
                } # 3d.) Check topic for a star wildcard match
                else
                {
                    $regEx = str_replace(array('*', '_'), '(.*)', $category_topic);

                    if (preg_match("/$regEx/i", $topic))
                    {
                        $current_score += $topic_star_match;
                        $track_matches .= "topic match with wildcards ({$topic_star_match} points), ";
                    }
                }
            }
        } # end topic testing

        # 4.) test for a category thatpattern
        if (empty($category_thatpattern) || $category_thatpattern == '*')
        {
            $current_score += 1;
            $track_matches .= "no thatpattern to match (1 point), ";
        }
        else
        {
            if (strpos($category_thatpattern, '_') !== false)
            {
                # 4a.) Create a RegEx to search for underscore wildcards
                $regEx = str_replace('_', '(.*)', $category_thatpattern);

                if ($regEx !== $category_thatpattern && preg_match("/$regEx/i", $that) === 1)
                {
                    $current_score += $thatpattern_underscore_match;
                    $track_matches .= "thatpattern match with underscore ({$thatpattern_underscore_match} points), ";
                }
            }
            # 4b.) direct thatpattern match
            elseif ($that_lc == $category_thatpattern_lc)
            {
                $current_score += $thatpattern_direct_match;
                $track_matches .= "direct thatpattern match ({$thatpattern_direct_match} points), ";
            }
            # 4c.) thatpattern star matches
            elseif (strstr($category_thatpattern_lc, '*') !== false)
            {
                $regEx = str_replace('*', '(.*)', $category_thatpattern);

                if (preg_match("/$regEx/i", $that))
                {
                    $current_score += $thatpattern_star_match;
                    $track_matches .= "thatpattern match with star ({$thatpattern_star_match} points), ";
                }
            }
            # 4d.)match thatpattern words
            elseif (!empty($category_thatpattern_lc))
            {
                $category_thatpattern_words = explode(" ", $category_thatpattern_lc);
                $thatpattern_words = explode(" ", $that_lc);
                foreach ($thatpattern_words as $word)
                {
                    continue;
                    $word = trim($word);
                    switch (true)
                    {
                        case ($word === '_'):
                            $current_score += $thatpattern_underscore_word_match;
                            $track_matches .= "thatpattern underscore word match ({$thatpattern_underscore_word_match} points), ";
                            break;
                        case ($word === '*'):
                            $current_score += $thatpattern_star_word_match;
                            $track_matches .= "thatpattern star word match ({$thatpattern_star_word_match} points), ";
                            break;
                        case (in_array($word, $category_thatpattern_words)):
                        //case (false):
                            $current_score += $thatpattern_direct_word_match;
                            $track_matches .= "thatpattern direct word match: {$word} ({$thatpattern_direct_word_match} points), ";
                            break;
                        case (in_array($word, $common_words_array)):
                            $current_score += $common_word_match;
                            $track_matches .= "thatpattern common word match: {$word} ({$common_word_match} points), ";
                            break;
                        default:
                            $current_score += $uncommon_word_match;
                            $track_matches .= "thatpattern uncommon word match: {$word} ({$uncommon_word_match} points), ";
                    }
                }
            }
            # 4e.) no match at all
            else
            {
                $current_score = $rejected;
                $track_matches .= "no thatpattern match at all ({$rejected} points), ";
                //runDebug(__FILE__, __FUNCTION__, __LINE__, "Matching '$that_lc' with '$category_thatpattern_lc' failed. Drat!'", 4);
            }
        } # end thatpattern testing

        # 5.) pattern testing

        # 5a.) Create a RegEx to search for underscore wildcards
        if (strpos($category_pattern, '_') !== false)
        {
            $regEx = str_replace('_', '(.*)', $category_pattern);
            //save_file(_LOG_PATH_ . 'regex.txt', "$regEx\n", true);
            if ($regEx != $category_pattern && preg_match("/$regEx/i", $pattern) === 1)
            {
                $current_score += $underscore_match;
                $track_matches .= "pattern match with underscore ({$underscore_match} points), ";
            }
        }
        # 5b.) direct pattern match
        elseif ($pattern == $category_pattern)
        {
            $current_score += $pattern_direct_match;
            $track_matches .= "direct pattern match ({$pattern_direct_match} points), ";
            //$check_pattern_words  = false;
        }
        # 5c.) pattern star matches
        else
        {
            $regEx = str_replace(array('*', '_'), '(.*?)', $category_pattern);
            if ($category_pattern == '*')
            {
                $current_score += $pattern_star_match;
                $track_matches .= "pattern star match ({$pattern_star_match} points), ";
                $check_pattern_words = false;
            }
            elseif ($regEx != $category_pattern && (($category_pattern != '*') || ($category_pattern != '_')) && preg_match("/$regEx/i", $pattern) != 0)
            { }
        } # end pattern testing

        # 5d.) See if the current category is the default category
        if ($category_pattern == $default_pattern_lc)
        {
            runDebug(__FILE__, __FUNCTION__, __LINE__, 'This category is the default pattern!', 4);

            $current_score += $default_pattern_match;
            $track_matches .= "default pattern match ({$default_pattern_match} points), ";
            $check_pattern_words = false;
        }

        #6.) check to see if we need to score word by word

        if ($check_pattern_words && $category_pattern_lc != $default_pattern_lc)
        {
            # 6a.) first, a little setup
            $pattern_lc = _strtolower($pattern);
            $category_pattern_lc = _strtolower($category_pattern);
            $pattern_words = explode(" ", $pattern_lc);

            # 6b.) break the input pattern into an array of individual words and iterate through the array
            $category_pattern_words = explode(" ", $category_pattern_lc);
            foreach ($category_pattern_words as $word)
            {
                $word = trim($word);
                switch (true)
                {
                    case ($word === '_'):
                        $current_score += $underscore_word_match;
                        $track_matches .= "underscore word match ({$underscore_word_match} points), ";
                        break;
                    case ($word === '*'):
                        $current_score += $star_word_match;
                        $track_matches .= "star word match ({$star_word_match} points), ";
                        break;
                    case (in_array($word, $pattern_words)):
                        $current_score += $direct_word_match;
                        $track_matches .= "direct word match: {$word} ({$direct_word_match} points), ";
                        break;
                    case (in_array($word, $common_words_array)):
                        $current_score += $common_word_match;
                        $track_matches .= "common word match: {$word} ({$common_word_match} points), ";
                        break;
                    default:
                        $current_score += $uncommon_word_match;
                        $track_matches .= "uncommon word match: {$word} ({$uncommon_word_match} points), ";
                }
            }
        }

        $allrows[$all]['score'] += $current_score;
        $allrows[$all]['track_score'] = rtrim($track_matches, ', ');
    }
    //runDebug(__FILE__, __FUNCTION__, __LINE__, "Unsorted array \$allrows:\n" . print_r($allrows, true), 4);
    $allrows = sort2DArray("show top scoring aiml matches", $allrows, "score", 1, 10);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Sorted array \$allrows:\n" . print_r($allrows, true), 4);

    return $allrows;
}

/**
 * Small helper function to sort a 2d array
 *
 * @param string $opName
 * @param array $thisArr
 * @param $sortByItem
 * @param $sortAsc
 * @param $limit
 * @return array
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
            $tmpSortArr[] = $subrow[$sortByItem];
        }
    }

    //sort the results
    if ($sortAsc == 1)
    {
        //ascending
        arsort($tmpSortArr);
    }
    else
    {
        //descending
        asort($tmpSortArr);
    }

    //loop through scores
    foreach ($tmpSortArr as $sortedKey => $idValue)
    {
        $resArr[] = $thisArr[$sortedKey];
    }
    //get the limited top results
    $outArr = array_slice($resArr, 0, $limit);

    return $outArr;
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
    global $bot_id, $which_response, $error_response;

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
    $tmpArr = sortByID($tmpArr);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Final candidates:\n" . PHP_EOL . print_r($tmpArr, true) . "\n", 2);

/*
     It has been suggested that the "winning" response be the first category found with the highest score,
     rather than a random selection from all high scoring responses. It was also suggested that the most
     recent (e.g. the last) response should be chosen, with newer AIML categories superseding older ones.
     At some point this will be an option that will be placed in the admin pages on a per-bot basis, but
     for now it's just a random pick. That said, however, I'm going to start adding code for the other
     two options now.
*/
    if (!defined('BOT_USE_FIRST_RESPONSE')) $which_response = 0;
    $which_response = BOT_USE_LAST_RESPONSE;
    $resultCount = count($tmpArr);
    $use_message = 'No results found, so none to pick from.';
    $bestResponse = array();
    $defined_constants = get_defined_constants(true);
    $my_constants = $defined_constants['user'];
    //save_file(_LOG_PATH_ . 'constants.txt', print_r($my_constants, true));
    if ($resultCount > 0)
    {
        $first_match = $tmpArr[0];
        //runDebug(__FILE__, __FUNCTION__, __LINE__, "First match:\n" . print_r($first_match, true), 2);
        $random_match = $tmpArr[array_rand($tmpArr)];
        //runDebug(__FILE__, __FUNCTION__, __LINE__, "Random match:\n" . print_r($random_match, true), 2);
        $last_match = array_pop($tmpArr);
        //runDebug(__FILE__, __FUNCTION__, __LINE__, "Last match:\n" . print_r($last_match, true), 2);
        switch ($which_response)
        {
            case BOT_USE_FIRST_RESPONSE:
                $bestResponse = $first_match;
                break;
            case BOT_USE_LAST_RESPONSE:
                $bestResponse = $last_match;
                break;
            default:
                $bestResponse = $random_match;
        }
    }

    //$bestResponse = (count($tmpArr) > 0) ? $tmpArr[array_rand($tmpArr)] : false;
    if (empty($bestResponse))
    {
        $bestResponse = array(
            'id' => -1,
            'aiml_id' => -1,
            'bot_id' => $bot_id,
            'pattern' => 'no results',
            'thatpattern' => '',
            'topic' => '',
            'template' => $error_response,
            'score' => 0,
            'track_score' => 'No Match Found!',
        );
    }

    runDebug(__FILE__, __FUNCTION__, __LINE__, "Best Response: " . print_r($bestResponse, true), 4);
    runDebug(__FILE__, __FUNCTION__, __LINE__, $use_message, 2);

    // Add row data to the chatbot output
    $data = $bestResponse;
    $convoArr['conversation']['aimlData'] = $data;

    //return the best response
    return $bestResponse;
}

/**
 * function get_convo_var()
 * This function takes fetches a variable from the conversation array
 *
 * @param array $convoArr - conversation array
 * @param string $index_1 - convoArr[$index_1]
 * @param string $index_2 - convoArr[$index_1][$index_2]
 * @param int|string $index_3 - convoArr[$index_1][$index_2][$index_3]
 * @param int|string $index_4 - convoArr[$index_1][$index_2][$index_3][$index_4]
 * @return string $value - the value of the element
 *
 *
 * examples
 *
 * $convoArr['conversation']['bot_id'] = $convoArr['conversation']['bot_id']
 * $convoArr['that'][1][1] = get_convo_var($convoArr,'that','',1,1)
 */
function get_convo_var(&$convoArr, $index_1, $index_2 = '~NULL~', $index_3 = 1, $index_4 = 1)
{
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
    else {
        $value = '';
    }

    runDebug(__FILE__, __FUNCTION__, __LINE__, "Get from ConvoArr [$index_1][$index_2][$index_3][$index_4] FOUND: ConvoArr Value = '$value'", 4);
    return $value;
}

/**
 * Function: get_client_property()
 * Summary: Extracts a value from the the client properties subarray within the main conversation array
 * @param array $convoArr - the main conversation array
 * @param string $name - the key of the value to extract from client properties
 * @return string $response - the value of the client property
 **/
function get_client_property(&$convoArr, $name)
{
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Rummaging through various locations for client property '{$name}'", 2);
    global $dbn;

    if (isset ($convoArr['client_properties'][$name]))
    {
        $value = $convoArr['client_properties'][$name];
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Found client property '{$name}' in the conversation array. Returning '{$value}'", 2);

        return $convoArr['client_properties'][$name];
    }

    runDebug(__FILE__, __FUNCTION__, __LINE__, "Client property '{$name}' not found in the conversation array. Searching the DB.", 2);

    $user_id = $convoArr['conversation']['user_id'];
    $bot_id = $convoArr['conversation']['bot_id'];
    /** @noinspection SqlDialectInspection */
    $sql = "SELECT `value` FROM `$dbn`.`client_properties` WHERE `user_id` = :user_id AND `bot_id` = :bot_id AND `name` = '$name' limit 1;";
    $params = array(
        ':bot_id' => $bot_id,
        ':user_id' => $user_id,
    );
    $rdSQL = db_parseSQL($sql, $params);

    runDebug(__FILE__, __FUNCTION__, __LINE__, "Querying the client_properties table for {$name}. SQL:\n{$rdSQL}", 3);

    $row = db_fetch($sql, $params, __FILE__, __FUNCTION__, __LINE__);
    $rowCount = (false !== $row) ? count($row) : 0;

    if ($rowCount != 0)
    {
        $response = trim($row['value']);
        $convoArr['client_properties'][$name] = $response;
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Found client property '$name' in the DB. Adding it to the conversation array and returning '$response'", 2);
    }
    else {
        $response = 'undefined';
    }

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
function find_userdefined_aiml(&$convoArr)
{
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Looking for user defined responses', 4);
    global $dbn;

    $i = 0;
    $allrows = array();
    $bot_id = $convoArr['conversation']['bot_id'];
    $bot_parent_id = $convoArr['conversation']['bot_parent_id'];
    $c_id = $convoArr['conversation']['convo_id']; //$convoArr['conversation'][''];user_id
    $lookingfor = _strtoupper($convoArr['aiml']['lookingfor']);
    $params = array(
        ':user_id' => $c_id,
        ':bot_id'  => $bot_id
    );

    //build sql
    /** @noinspection SqlDialectInspection */
    $sql = <<<endSQL
SELECT `id`, `bot_id`, `pattern`, `thatpattern`, `template` FROM `$dbn`.`aiml_userdefined` WHERE
    `bot_id` = :bot_id AND
    `user_id` = :user_id
    AND ([pattern_like][thatpattern_like])
   ORDER BY `id` ASC, `pattern` ASC, `thatpattern` ASC;
endSQL;

    $rplTemplate = "'[search]' LIKE (REPLACE(REPLACE(`[field]`, '*', '%'), '_', '%'))";

    // Build the pattern search
    $pattern_like = "\n        " . str_replace('[search]', $lookingfor, $rplTemplate);
    $pattern_like = str_replace('[field]', 'pattern', $pattern_like);

    // Placeholder for thatpattern, in case it's empty
    $thatpattern_like = '';

    // Build the thatpattern search
    $lastthat = (isset ($convoArr['that'][1][1])) ? $convoArr['that'][1][1] : '';
    $lastthat = rtrim(_strtoupper($lastthat));
    if (!empty($lastthat))
    {
        $thatpattern_like = "\n        OR " . str_replace('[search]', $lastthat, $rplTemplate);
        $thatpattern_like = str_replace('[field]', 'thatpattern', $thatpattern_like);
    }


    // now let's replace stuff
    $sql = str_replace('[pattern_like]', $pattern_like, $sql);
    $sql = str_replace('[thatpattern_like]', $thatpattern_like, $sql);

    $debugSQL = db_parseSQL($sql, $params);

    runDebug(__FILE__, __FUNCTION__, __LINE__, "User defined SQL:\n$debugSQL", 3);

    $result = db_fetchAll($sql, $params, __FILE__, __FUNCTION__, __LINE__);
    $num_rows = count($result);

    //if there is a result get it
    if (($result) && ($num_rows > 0))
    {
        runDebug(__FILE__, __FUNCTION__, __LINE__, 'Results returned: ' . print_r($result, true), 3);

        //loop through results
        foreach ($result as $row)
        {
            $allrows[$i] = array();
            $allrows[$i]['id']               = $row['id'];
            $allrows[$i]['aiml_id']          = $row['id'];
            $allrows[$i]['aiml_userdefined'] = true;
            $allrows[$i]['score']            = 0; // This will be handled in score_matches()
            $allrows[$i]['pattern']          = $row['pattern'];
            $allrows[$i]['thatpattern']      = (isset($row['thatpattern'])) ? $row['thatpattern'] : '';
            $allrows[$i]['template']         = $row['template'];
            $allrows[$i]['topic']            = ''; // User defined AIML has no topic.
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
function get_aiml_to_parse(&$convoArr)
{
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Running all functions to get the correct aiml from the DB", 4);
    $allrows = array();

    $lookingfor = $convoArr['aiml']['lookingfor'];
    $current_thatpattern = (isset ($convoArr['that'][1][1])) ? $convoArr['that'][1][1] : '';
    $current_topic = get_topic($convoArr);
    $default_aiml_pattern = _strtolower($convoArr['conversation']['default_aiml_pattern']);
    $bot_parent_id = $convoArr['conversation']['bot_parent_id'];
    $raw_that = (isset ($convoArr['that'])) ? print_r($convoArr['that'], true) : '';

    //check if match in user defined aiml
    $allrows = find_userdefined_aiml($convoArr);

    //if there is no match in the user defined aiml table
    if ((empty($allrows)))
    {
        //look for a match in the normal aiml tbl
        $allrows = find_aiml_matches($convoArr);
    }
    //unset all irrelvant matches
    $allrows = unset_all_bad_pattern_matches($convoArr, $allrows, $lookingfor);
    //score the relevant matches
    $allrows = score_matches($convoArr, $allrows, $lookingfor);
    //get the highest scoring row
    $WinningCategory = get_highest_scoring_row($convoArr, $allrows, $lookingfor);
    $curPattern = _strtolower($WinningCategory['pattern']);

    // If the selected category's pattern is the default pickup category, store the input in the unknown inputs table
    //if (_strtolower($WinningCategory['pattern']) == $default_aiml_pattern && _strtolower($lookingfor) != $default_aiml_pattern)
    if (($curPattern == $default_aiml_pattern || $curPattern == '*') && _strtolower($lookingfor) != $default_aiml_pattern) //
    {
        $bot_id = $convoArr['conversation']['bot_id'];
        $user_id = $convoArr['conversation']['user_id'];
        $rawSay = $convoArr['conversation']['rawSay'];
        addUnknownInput($convoArr, $rawSay, $bot_id, $user_id);
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Added input '$lookingfor' to the unknown_inputs table.", 1);
    }

    //Now we have the results put into the conversation array
    if (isset($WinningCategory['id'])) {
        $convoArr['aiml']['category_id'] = $WinningCategory['id'];
    }

    $convoArr['aiml']['pattern'] = $WinningCategory['pattern'];
    $convoArr['aiml']['thatpattern'] = $WinningCategory['thatpattern'];
    $convoArr['aiml']['template'] = $WinningCategory['template'];
    $convoArr['aiml']['html_template'] = '';
    $convoArr['aiml']['topic'] = $WinningCategory['topic'];
    $convoArr['aiml']['score'] = (isset($WinningCategory['score'])) ? $WinningCategory['score'] : 0;
    $convoArr['aiml']['aiml_id'] = (isset($WinningCategory['aiml_id'])) ? $WinningCategory['aiml_id'] : -1;
    //return
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Will be parsing id:" . $WinningCategory['aiml_id'] . " (" . $WinningCategory['pattern'] . ")", 4);

    return $convoArr;
}

/**
 * function find_aiml_matches()
 * This function builds the sql to use to get a match from the tbl
 * @param array $convoArr - conversation array
 * @return array $convoArr
 **/
function find_aiml_matches(&$convoArr)
{
    global $dbn, $error_response;
    $user_id = $convoArr['conversation']['user_id'];
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Finding the aiml matches from the DB", 4);

    $i = 0;
    //TODO convert to get_it
    $bot_id = $convoArr['conversation']['bot_id'];
    $bot_parent_id = $convoArr['conversation']['bot_parent_id'];
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Bot ID = $bot_id. Bot Parent ID = $bot_parent_id.", 4);
    // get bot and bot parent ID's

    $default_aiml_pattern = $convoArr['conversation']['default_aiml_pattern'];

    #$lookingfor = get_convo_var($convoArr,"aiml","lookingfor");
    $convoArr['aiml']['lookingfor'] = str_replace('  ', ' ', $convoArr['aiml']['lookingfor']);
    $lookingfor = trim(_strtoupper($convoArr['aiml']['lookingfor']));

    //get the stored topic
    $storedtopic = trim(_strtoupper(get_topic($convoArr)));
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Stored topic = '$storedtopic'", 4);

    //get the cleaned previous bot response (that)
    $lastthat = (isset ($convoArr['that'][1][1])) ? $convoArr['that'][1][1] : '';
    $lastthat = rtrim(_strtoupper($lastthat));

    $params = array();
    if ($bot_parent_id != 0 && $bot_parent_id != $bot_id)
    {
        $sql_bot_like = "(bot_id = :bot_id OR bot_id = :bot_parent_id)";
        $params[':bot_id'] = $bot_id;
        $params[':bot_parent_id'] = $bot_parent_id;
    }
    else {
        $sql_bot_like = "bot_id = :bot_id";
        $params[':bot_id'] = $bot_id;
    }
    $rplTemplate = "'[search]' LIKE (REPLACE(REPLACE(`[field]`, '*', '%'), '_', '%'))";

    // Build the pattern search
    $pattern_like = "\n        " . str_replace('[search]', $lookingfor, $rplTemplate);
    $pattern_like = str_replace('[field]', 'pattern', $pattern_like);

    // Placeholders for thatpattern and topic, in case they're empty
    $thatpattern_like = '';
    $topic_like = '';

    // Build the thatpattern search
    if (!empty($lastthat))
    {
        $thatpattern_like = "\n        OR " . str_replace('[search]', $lastthat, $rplTemplate);
        $thatpattern_like = str_replace('[field]', 'thatpattern', $thatpattern_like);
    }

    // Build the topic search
    if (!empty($storedtopic))
    {
        $topic_like = "\n        OR " . str_replace('[search]', $storedtopic, $rplTemplate);
        $topic_like = str_replace('[field]', 'topic', $topic_like);
    }


    // The SQL template - There will ALWAYS be a pattern search, but not necessarily a thatpattern or topic.
    // There will also ALWAYS be a search for the default response category
    $sql = <<<endSQL
SELECT `id`, `bot_id`, `pattern`, `thatpattern`, `topic`, `filename`, `template` FROM `$dbn`.`aiml` WHERE
    [sql_bot_like] AND ([pattern_like][thatpattern_like][topic_like]
        OR `pattern` LIKE '$default_aiml_pattern'
    )
    # ORDER BY `id` ASC, `topic` DESC, `pattern` ASC, `thatpattern` ASC;
endSQL;


    // now let's replace stuff
    $sql = str_replace('[sql_bot_like]', $sql_bot_like, $sql);
    $sql = str_replace('[pattern_like]', $pattern_like, $sql);
    $sql = str_replace('[thatpattern_like]', $thatpattern_like, $sql);
    $sql = str_replace('[topic_like]', $topic_like, $sql);

    $debugSQL = db_parseSQL($sql, $params);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Core Match AIML sql:\n$debugSQL", 3);
    $result = db_fetchAll($sql, $params, __FILE__, __FUNCTION__, __LINE__);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Query Process time...", 3);
    $num_rows = count($result);

    if (($result) && ($num_rows > 0))
    {
        //sortByID($result);
        $tmp_rows = number_format($num_rows);
        runDebug(__FILE__, __FUNCTION__, __LINE__, "FOUND: ({$tmp_rows}) potential AIML matches", 2);
        //loop through results

        foreach ($result as $row)
        {
            $row['score'] = 0;
            $row['current_that'] = _strtolower($lastthat);
            $row['aiml_id'] = $row['id'];
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
        runDebug(__FILE__, __FUNCTION__, __LINE__, 'Error: FOUND NO AIML matches in DB.', 1);
        addUnknownInput($convoArr, $lookingfor, $bot_id, $user_id);
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Added input '{$lookingfor}' to the unknown_inputs table.", 1);

        $allrows[$i]['aiml_id'] = "-1";
        $allrows[$i]['bot_id'] = $bot_id;
        $allrows[$i]['pattern'] = "no results";
        $allrows[$i]['thatpattern'] = '';
        $allrows[$i]['template'] = $error_response;
        $allrows[$i]['topic'] = '';
    }

    return $allrows;
}

/** get_topic()
 * Extracts the current topic directly from the database
 *
 * @param array $convoArr - the conversation array
 * @return string $retVal - the topic
 */
function get_topic(&$convoArr)
{
    global $bot_id;

    $bot_id = (!empty($convoArr['conversation']['bot_id'])) ? $convoArr['conversation']['bot_id'] : $bot_id;
    $user_id = $convoArr['conversation']['user_id'];

    /** @noinspection SqlDialectInspection */
    $sql = "SELECT `value` FROM `client_properties` WHERE `user_id` = :user_id AND `bot_id` = :bot_id and `name` = 'topic';";
    $params = array(
        ':bot_id' => $bot_id,
        ':user_id' => $user_id,
    );
    $row = db_fetch($sql, $params, __FILE__, __FUNCTION__, __LINE__);
    $num_rows = empty($row) ? 0 : count($row);
    $retval = ($num_rows == 0) ? '' : $row['value'];

    return $retval;
}

function sortByID($array)
{
    usort($array, function($a, $b){return strcmp($a['id'], $b['id']);});
    return $array;
}

function buildSearchRegEx(&$convoArr)
{
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'building a new RegEx search array.', 4);
    $cwTmp = file(_CONF_PATH_ . 'commonWords.dat', FILE_IGNORE_NEW_LINES);
    $regExSearch = array();
    foreach ($cwTmp as $word)
    {
        $regExSearch[] = "~\b{$word}\b~i";
    }
    $convoArr['regExSearch'] = $regExSearch;
    save_file(_LOG_PATH_ . 'regex.txt', print_r($regExSearch, true));

}
