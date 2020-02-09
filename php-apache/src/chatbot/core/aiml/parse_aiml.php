<?php

/***************************************
 * www.program-o.com
 * PROGRAM O
 * Version: 2.6.11
 * FILE: chatbot/core/aiml/parse_aiml.php
 * AUTHOR: Elizabeth Perreau and Dave Morton
 * DATE: MAY 17TH 2014
 * DETAILS: this file contains the functions used to convert aiml to php
 ***************************************/


/**
 * function buildVerbList()
 *
 * @param string $name
 * @param string $gender
 * @internal param array $convoArr
 */
function buildVerbList($name, $gender)
{
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Building the verb list. Name:$name. Gender:$gender", 4);

    // person transform arrays:
    $firstPersonPatterns = array();
    $firstPersonReplacements = array();
    $secondPersonKeyedPatterns = array();
    $secondPersonPatterns = array();
    $secondPersonReplacements = array();
    $thirdPersonReplacements = array();

    switch ($gender)
    {
        case "male" :
            $g3 = "he";
            $tpWord = 'third';
            break;
        case "female";
            $g3 = "she";
            $tpWord = 'third';
            break;
        default :
            $g3 = "they";
            $tpWord = 'second';
    }

    // Search and replacement templates - grouped in pairs/triplets
    // first to second/third
    $firstPersonSearchTemplate = '/\bi [word]\b/ui';
    $secondPersonKeyedReplaceTemplate = 'y%%ou [word]';
    $thirdPersonReplaceTemplate = "$g3 [word]";

    //second to first
    $secondPersonSearchTemplate = '/\byou [word]\b/ui';
    $firstPersonReplaceTemplate = 'I [word]';

    //second (reversed) to first
    $secondPersonSearchTemplateReversed = '/\b[word] you\b/ui';
    $firstPersonReplaceTemplateReversed = '[word] @II';
    $secondPersonKeyedSearchTemplate = '/\by%%ou [word]\b/ui';
    $secondPersonReplaceTemplate = 'you [word]';

    //the list of verbs is stored in the config folder
    $file = _CONF_PATH_ . "verbList.dat";
    $verbList = file($file, FILE_USE_INCLUDE_PATH | FILE_SKIP_EMPTY_LINES);

    //or exit("<br>Unable to open tr");
    sort($verbList);

    //  fill the arrays
    foreach ($verbList as $line)
    {
        $line = rtrim($line, "\r\n");
        #print "line = |$line|<br />\n";

        if (empty ($line)) {
            continue;
        }

        $firstChar = substr($line, 0, 1);

        if ($firstChar === '#') {
            continue;
        }

        if ($firstChar === '$')
        {
            $words = str_replace('$ ', '', $line);
            list($first, $third) = explode(', ', $words);
            $second = $first;
        }
        elseif ($firstChar === '~')
        {
            $words = str_replace('~ ', '', $line);
            $first = $words;
            $second = $first;
            $third = $first;
        }
        else {
            list($first, $second, $third) = explode(', ', $line);
        }

        // build first patterns to second (both keyed and non) patterns and replacements, and first patterns to third replacements
        $firstPersonPatterns[] = str_replace('[word]', $first, $firstPersonSearchTemplate);
        $secondPersonKeyedPatterns[] = str_replace('[word]', $second, $secondPersonKeyedSearchTemplate);
        $secondPersonReplacements[] = str_replace('[word]', $first, $secondPersonReplaceTemplate);
        $thirdPersonReplacements[] = str_replace('[word]', $tpWord, $thirdPersonReplaceTemplate);

        // build second patterns to first replacements - reversed (e.g. "would I") first
        $secondPersonPatterns[] = str_replace('[word]', $second, $secondPersonSearchTemplate);
        $firstPersonReplacements[] = str_replace('[word]', $first, $firstPersonReplaceTemplate);
        // build first patterns to third replacements
    }

    $_SESSION['verbList'] = true;
    // debugging - Let's see what the contents of the files are!
    $transformList = array('firstPersonPatterns' => $firstPersonPatterns, 'secondPersonKeyedPatterns' => $secondPersonKeyedPatterns, 'secondPersonReplacements' => $secondPersonReplacements, 'thirdPersonReplacements' => $thirdPersonReplacements, 'secondPersonPatterns' => $secondPersonPatterns, 'firstPersonReplacements' => $firstPersonReplacements);

    foreach ($transformList as $transform_index => $transform_value)
    {
        $_SESSION['transform_list'][$transform_index] = $transform_value;
    }
}

/**
 * function swapPerson()
 * @param array $convoArr
 * @param int $person
 * @param string $input
 * @return string $tmp
 **/
function swapPerson($convoArr, $person, $input)
{
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Person:$person In:$input", 4);

    $name = $convoArr['client_properties']['name'];
    $gender = (isset($convoArr['client_properties']['gender'])) ? $convoArr['client_properties']['gender'] : 'unknown';
    $tmp = trim($input);

    if ((!isset ($_SESSION['transform_list'])) || ($_SESSION['transform_list'] == NULL))
    {
        buildVerbList($name, $gender);
    }

    // <person2> = swap first with second person (e.g. I with you)
    // <person> swap with third person (e.g. I with he,she,it)
    switch ($gender)
    {
        case "male" :
            $g1 = "his";  //  Third person singular: masculine (Possessive pronoun)
            $g2 = "him";  // Third person singular: masculine (object)
            $g3 = "he";  // Third person singular: masculine (subject)
            $g4 = "his";  // Third person singular: neutral (Possessive adjective)
            break;
        case "female";
            $g1 = "hers";  //  Third person singular: feminine (Possessive pronoun)
            $g2 = "her";;  // Third person singular: feminine (object)
            $g3 = "she";  // Third person singular: feminine (subject)
            $g4 = "her";  // Third person singular: neutral (Possessive adjective)
            break;
        default :
            $g1 = "its";  // Third person singular: neutral (Possessive pronoun)
            $g2 = "it";  // Third person singular: neutral (object)
            $g3 = "it";  // Third person singular: neutral (subject)
            $g4 = "its";  // Third person singular: neutral (Possessive adjective)
    }

    # I am, am I, was I, I, my, mine, myself, me, ourselves, we, us, ours, our
    $simpleFirstPersonPatterns = array('/(\bi am\b)/ui', '/(\bam i\b)/ui', '/(\bwas i\b)/ui', '/(\bi\b)/ui', '/(\bmy\b)/ui', '/(\bmine\b)/ui', '/(\bmyself\b)/ui', '/(\byour\b)/ui', '/(\bme\b)/ui', '/(\bourselves\b)/ui', '/(\bwe\b)/ui', '/(\bus\b)/ui', '/(\bours\b)/ui', '/(\bour\b)/ui');
    $simpleSecondPersonKeyedReplacements = array('y%%ou are', 'are y%%ou', 'were y%%ou', 'y%%ou', 'yo%%ur', 'yo%%urs', 'y%%ourself', 'm%%y', 'y%%ou', 'y%%ourselves', 'y%%ou', 'y%%ou', 'y%%ours', 'y%%our');

    # you are, are you, were you, you, your, yours, yourself, thy, yourselves
    $simpleSecondPersonPatterns = array('/(\bare you\b)/ui', '/(\byou are\b)/ui', '/(\bwere you\b)/ui', '/(\byou\b)/ui', '/(\byour\b)/ui', '/(\byours\b)/ui', '/(\byourself\b)/ui', '/(\bthy\b)/ui', '/(\byourselves\b)/ui');
    $simpleFirstPersonKeyedReplacements = array('I%% am', 'am I%%', 'was I%%', 'I%%', 'm%%y', 'm%%ine', 'm%%yself', 'm%%y', 'o%%urselves');

    # I am, am I, will I, shall I, may I, might I, can I, could I, must I, should I, would I, need I, was I, ourselves, our, ours, I, me, my, mine, myself, we, us
    $simpleFirstToThirdPersonPatterns = array('/(\bi am\b)/ui', '/(\bam i\b)/ui', '/(\bwill i\b)/ui', '/(\bshall i\b)/ui', '/(\bmay i\b)/ui', '/(\bmight i\b)/ui', '/(\bcan i\b)/ui', '/(\bcould i\b)/ui', '/(\bmust i\b)/ui', '/(\bshould i\b)/ui', '/(\bwould i\b)/ui', '/(\bneed i\b)/ui', '/(\bwas i\b)/ui', '/(\bourselves\b)/ui', '/(\bour\b)/ui', '/(\bours\b)/ui', '/(\bme\b)/ui', '/(\bi\b)/ui', '/(\bmy\b)/ui', '/(\bmine\b)/ui', '/(\bmyself\b)/ui', '/(\bwe\b)/ui', '/(\bud\b)/ui');
    $simpleThirdPersonReplacements = array("$g3 is", "is $g3", 'will ' . $g3, 'shall ' . $g3, 'may ' . $g3, 'might ' . $g3, 'can ' . $g3, 'could ' . $g3, 'must ' . $g3, 'should ' . $g3, 'would ' . $g3, 'need ' . $g3, 'was ' . $g3, 'themselves', 'their', 'theirs', "$g2", "$g3", "$g4", "$g1", "$g2" . 'self', 'they', 'them');

    if ($person == 2)
    {
//      $tmp = preg_replace('/\byou and me\b/ui', 'you and IzI', $tmp);// fix the "Me and you" glitch
        $tmp = preg_replace($simpleFirstPersonPatterns, $simpleSecondPersonKeyedReplacements, $tmp);// simple first to keyed second transform
        $tmp = preg_replace($simpleSecondPersonPatterns, $simpleFirstPersonKeyedReplacements, $tmp);// "simple" second to keyed first transform
        $tmp = preg_replace($_SESSION['transform_list']['secondPersonPatterns'], $_SESSION['transform_list']['firstPersonReplacements'], $tmp);// second to first transform
    }
    elseif ($person == 3)
    {
        // first to third transform
        $tmp = preg_replace($simpleFirstToThirdPersonPatterns, $simpleThirdPersonReplacements, $tmp);// "simple" first to third transform
        $tmp = preg_replace($_SESSION['transform_list']['firstPersonPatterns'], $_SESSION['transform_list']['thirdPersonReplacements'], $tmp);
    }

    $tmp = str_replace('%%', '', $tmp);  // remove token
    //debug
    // if (RUN_DEBUG) runDebug(4, __FILE__, __FUNCTION__, __LINE__,"<br>\nTransformation complete. was: $input, is: $tmp");
    return $tmp;
    //return
}

/**
 * function parse_matched_aiml()
 * This function controls and triggers all the functions to parse the matched aiml
 * @param array $convoArr - the conversation array
 * @param string $type - normal or srai
 * @param string $aiml_pattern
 * @return array $convoArr - the updated conversation array
 */
function parse_matched_aiml($convoArr, $type = "normal", $aiml_pattern = '')
{
    //which debug mode?
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Run the aiml parse in $type mode (normal or srai)", 3);
    //save_file(_LOG_PATH_ . __FUNCTION__ . '.' . 'convo_array.txt', print_r($convoArr, true));

    $convoArr = set_wildcards($convoArr, $type);
    $car = $convoArr;
    $car['nounList'] = null;

    ksort($car);

    //file_put_contents(_LOG_PATH_ . 'car.txt', print_r($car, true));
    $convoArr = parse_aiml_as_XML($convoArr);

    if ($type != "srai")
    {
        runDebug(__FILE__, __FUNCTION__, __LINE__, "$type - Saving for next turn", 4);
        $convoArr = save_for_nextturn($convoArr);
    }
    else
    {
        runDebug(__FILE__, __FUNCTION__, __LINE__, "$type - Not saving for next turn", 4);
    }

    return $convoArr;
}

/**
 * function clean_that()
 * This function cleans the 'that' of html and other bits and bobs
 *
 * @param string $that - the string to clean
 * @param        $file
 * @param        $function
 * @param        $line
 * @return string $that - the cleaned string
 */
function clean_that($that, $file, $function, $line)
{
    #runDebug(__FILE__, __FUNCTION__, __LINE__,"This was called from $file, function $function, line $line", 4);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Cleaning up the ~THAT~ tag: '$that'", 4);

    $original_that = $that;
    $that = str_replace("<br/>", ".", $that);
    $that = strip_tags($that);
    $that = normalize_text($that);

    runDebug(__FILE__, __FUNCTION__, __LINE__, "Cleaning Complete. output = '$that'", 4);

    return $that;
}

/**
 * function save_for_nextturn()
 * This function puts the bot results of an srai search into the main convoArr
 * @param array $convoArr - the conversation array
 * @return array $convoArr - the updated conversation array
 **/
function save_for_nextturn($convoArr)
{
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Saving that and that_raw for next turn", 4);

    $savethis = $convoArr['aiml']['parsed_template'];
    $convoArr = push_on_front_convoArr('that_raw', $savethis, $convoArr);
    $convoArr = push_on_front_convoArr('that', $savethis, $convoArr);

    return $convoArr;
}

/**
 * function set_wildcards()
 * This function extracts wildcards from a patterns and puts their values in their associated array
 * @param array $convoArr - the conversation array
 * @param $pattern
 * @param $type
 * @return array $convoArr - the updated conversation array
 */
function set_wildcards($convoArr, $type)
{
    if (!isset($convoArr['aiml']['srai_input'])) {
        $convoArr['aiml']['stars'] = array();
        $convoArr['aiml']['that_stars'] = array();
        $convoArr['aiml']['topic_stars'] = array();
    }
    // Set pattern wildcards
    $aiml_pattern = trim($convoArr['aiml']['pattern']);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Setting Wildcards. Pattern = '$aiml_pattern'", 2);
/* debugging code
    $aimlArr = $convoArr['aiml'];
    ksort($aimlArr,SORT_NATURAL | SORT_FLAG_CASE);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Current AIML array =" . print_r($aimlArr, true), 2);
*/
    $ap = str_replace(array("*", "_"), "(.+)", $aiml_pattern);
    if ($ap != $aiml_pattern)
    {
        runDebug(__FILE__, __FUNCTION__, __LINE__, "We have pattern stars to process!", 2);
        if (!isset ($convoArr['aiml']['user_raw'])) $convoArr['aiml']['user_raw'] = normalize_text($convoArr['aiml']['lookingfor'], false);
        $checkAgainst = ($type == 'normal') ? $convoArr['aiml']['user_raw'] : $convoArr['aiml']['lookingfor'];
        $regEx = "~{$ap}$~siuU";
        runDebug(__FILE__, __FUNCTION__, __LINE__, "RegEx string = {$regEx}: Searching {$checkAgainst} for a match.", 2);
        if (preg_match_all($regEx, $checkAgainst, $matches))
        {
            runDebug(__FILE__, __FUNCTION__, __LINE__, print_r($matches, true), 2);
            for ($i = 1; $i < count($matches); $i++)
            {
                $curStar = trim($matches[$i][0]);
                $curStar = preg_replace('/[[:punct:]]/uis', ' ', $curStar);
                runDebug(__FILE__, __FUNCTION__, __LINE__, "Adding '$curStar' to the stars stack.", 2);
                $convoArr['aiml']['stars'][$i] = $curStar;
                runDebug(__FILE__, __FUNCTION__, __LINE__, 'Star array = ' . print_r($convoArr['aiml']['stars'], true), 2);
            }
        }
        else {
            runDebug(__FILE__, __FUNCTION__, __LINE__, "Something is not right here.", 2);
        }
    }

    // Set that wildcards
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Checking for wildcards in pattern-side THAT', 4);
    $aiml_thatpattern = trim($convoArr['aiml']['thatpattern']);
    if (!empty($aiml_thatpattern))
    {
        $tp = str_replace(array("*", "_"), "(.+)", $aiml_thatpattern);
        if ($tp != $aiml_thatpattern)
        {
            runDebug(__FILE__, __FUNCTION__, __LINE__, "We have thatpattern stars to process!", 2);
            $that = $convoArr['that'][1];
            $checkAgainst = implode_recursive(' ', $that, __FILE__, __FUNCTION__, __LINE__);
            runDebug(__FILE__, __FUNCTION__, __LINE__, "Checking thatpattern '$tp' against '$checkAgainst'.", 2);
            if (preg_match_all("~{$tp}$~siuU", $checkAgainst, $matches))
            {
                //save_file(_LOG_PATH_ . 'that_star.matches.txt', print_r($matches, true));
                runDebug(__FILE__, __FUNCTION__, __LINE__, "Current THAT matches:\n" . print_r($matches, true), 2);

                for ($i = 1; $i < count($matches); $i++)
                {
                    $curStar = trim($matches[$i][0]);
                    $curStar = preg_replace('/[[:punct:]]/uis', ' ', $curStar);
                    runDebug(__FILE__, __FUNCTION__, __LINE__, "Adding $curStar to the that_stars stack.", 2);
                    $convoArr['aiml']['that_stars'][$i] = $curStar;
                }
            }
            else {
                runDebug(__FILE__, __FUNCTION__, __LINE__, "Something is not right here.", 2);
            }
        }
    }
    else $convoArr['aiml']['that_stars'][1] = '';

    // set topic wildcards
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Checking for wildcards in pattern-side TOPIC', 4);
    $aiml_topic = trim($convoArr['aiml']['topic']);
    if (!empty($aiml_topic))
    {
        $topp = str_replace(array("*", "_"), "(.+)", $aiml_topic);
        if ($topp != $aiml_topic)
        {
            runDebug(__FILE__, __FUNCTION__, __LINE__, "We have topic stars to process!", 2);
            $topic = $convoArr['client_properties']['topic'];
            $checkAgainst = implode_recursive(' ', $topic, __FILE__, __FUNCTION__, __LINE__);
            runDebug(__FILE__, __FUNCTION__, __LINE__, "Checking topic '$topp' against '$checkAgainst'.", 2);

            if (preg_match_all("~{$topp}$~siuU", $checkAgainst, $matches))
            {
                //save_file(_LOG_PATH_ . 'topic_star.matches.txt', print_r($matches, true));
                runDebug(__FILE__, __FUNCTION__, __LINE__, "Current TOPIC matches:\n" . print_r($matches, true), 2);

                for ($i = 1; $i < count($matches); $i++)
                {
                    $curStar = trim($matches[$i][0]);
                    $curStar = preg_replace('/[[:punct:]]/uis', ' ', $curStar);
                    runDebug(__FILE__, __FUNCTION__, __LINE__, "Adding $curStar to the topic_stars stack.", 2);
                    $convoArr['aiml']['topic_stars'][$i] = $curStar;
                }
            }
            else {
                $convoArr['aiml']['topic_stars'][1] = '';
                runDebug(__FILE__, __FUNCTION__, __LINE__, "Something is not right here.", 2);
            }
        }
    }
    $aimlArr = $convoArr['aiml'];
    ksort($aimlArr,SORT_NATURAL | SORT_FLAG_CASE);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "AIML array now = " . print_r($aimlArr, true), 2);
    return $convoArr;
}

/**
 * function run_srai()
 * This function controls the SRAI recursion calls
 * @param array $convoArr - a reference to the existing conversation array
 * @param string $now_look_for_this - the text to search for
 * @return string $srai_parsed_template - the result of the search
 **/
function run_srai(&$convoArr, $now_look_for_this)
{
    global $srai_iterations, $error_response, $dbn;

    $currentStars = $convoArr['aiml']['stars'];
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Running SRAI. Pattern = '$now_look_for_this'.", 2);
    $bot_parent_id = $convoArr['conversation']['bot_parent_id'];
    $bot_id = $convoArr['conversation']['bot_id'];
    $params = array(
        ':bot_id' => $bot_id,
        ':now_look_for_this' => "%{$now_look_for_this}%",
    );

    $sql_bot_select = " bot_id = :bot_id ";
    if ($bot_parent_id != 0 && $bot_parent_id != $bot_id)
    {
        $sql_bot_select .= "OR bot_id = :bot_parent_id ";
        $params[':bot_parent_id'] = $bot_parent_id;
    }

        runDebug(__FILE__, __FUNCTION__, __LINE__,'Checking for entries in the srai_lookup table.', 2);
        runDebug(__FILE__, __FUNCTION__, __LINE__,"azimov bot_id = $bot_id", 2);
        $lookingfor = $convoArr['aiml']['lookingfor'];
        //$now_look_for_this = _strtoupper($now_look_for_this);
        $sql = "select `template_id` from `$dbn`.`srai_lookup` where `pattern` like :now_look_for_this and ({$sql_bot_select});";
        $debugSQL = db_parseSQL($sql, $params);
        runDebug(__FILE__, __FUNCTION__, __LINE__,"lookup SQL = {$debugSQL}", 2);
        $rows = db_fetchAll($sql, $params, __FILE__, __FUNCTION__, __LINE__);
        runDebug(__FILE__, __FUNCTION__, __LINE__, 'Result = ' . print_r($rows, true), 2);
        $num_rows = count($rows);
        //$num_rows = 0;
        if ($num_rows > 0)
        {
          runDebug(__FILE__, __FUNCTION__, __LINE__,"Found $num_rows rows in lookup table: " . print_r($rows, true), 2);
          $template_id = $rows[0]['template_id'];
          runDebug(__FILE__, __FUNCTION__, __LINE__,"Found a matching entry in the lookup table. Using ID# $template_id.", 2);
          $sql = "select `template` from `$dbn`.`aiml` where `id` = '$template_id';";
          $row = db_fetch($sql,null, __FILE__, __FUNCTION__, __LINE__);
          runDebug(__FILE__, __FUNCTION__, __LINE__,"Row found in AIML for ID $template_id: " . print_r($row, true), 2);
          if (!empty($row))
          {
            $template = add_text_tags($row['template']);
            try
            {
              $sraiTemplate = new SimpleXMLElement($template, LIBXML_NOCDATA);
            }
            catch (exception $e)
            {
              trigger_error("There was a problem parsing the SRAI template as XML. Template value:\n$template", E_USER_WARNING);
              $sraiTemplate = new SimpleXMLElement("<text>$error_response</text>", LIBXML_NOCDATA);
            }
            $responseArray = parseTemplateRecursive($convoArr, $sraiTemplate);
            $response = implode_recursive(' ', $responseArray, __FILE__, __FUNCTION__, __LINE__);
            runDebug(__FILE__, __FUNCTION__, __LINE__,"Returning results from stored srai lookup.", 2);
            return $response;
          }
        }
        else
        {
          runDebug(__FILE__, __FUNCTION__, __LINE__,'No match found in lookup table.', 2);
        }
          runDebug(__FILE__, __FUNCTION__, __LINE__,"Nothing found in the SRAI lookup table. Looking for a direct pattern match for '$now_look_for_this'.", 2);
/* disabling srai_lookup
*/

    /** @noinspection SqlDialectInspection */
    $sql = "SELECT `id`, `pattern`, `thatpattern`, `topic` FROM `$dbn`.`aiml` where `pattern` like :now_look_for_this and $sql_bot_select order by `id` asc;";
    $debugSQL = db_parseSQL($sql, $params);
    runDebug(__FILE__, __FUNCTION__, __LINE__,"lookup SQL = {$debugSQL}", 2);
    $result = db_fetchAll($sql, $params, __FILE__, __FUNCTION__, __LINE__);
    $num_rows = count($result);

    runDebug(__FILE__, __FUNCTION__, __LINE__, "Found $num_rows potential responses.", 2);
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Responses: ' . print_r($result, true), 2);

    $allrows = array();
    $i = 0;
    if ($num_rows > 0)
    {
        $tmp_rows = number_format($num_rows);
        runDebug(__FILE__, __FUNCTION__, __LINE__, "FOUND: ($tmp_rows) potential AIML matches", 2);

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

    //unset all irrelvant matches
    $allrows = unset_all_bad_pattern_matches($convoArr, $allrows, $now_look_for_this);
    $arCount = count($allrows);

    if ($arCount == 0)
    {
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Error: FOUND NO AIML matches in DB", 1);
        $allrows[$i]['aiml_id'] = "-1";
        $allrows[$i]['bot_id'] = "-1";
        $allrows[$i]['pattern'] = "no results";
        $allrows[$i]['thatpattern'] = '';
        $allrows[$i]['topic'] = '';
    }

    //score the relevant matches
    $allrows = score_matches($convoArr, $allrows, $now_look_for_this);
    //get the highest
    $allrows = get_highest_scoring_row($convoArr, $allrows, $now_look_for_this);

    if (isset($allrows['aiml_id']) && $allrows['aiml_id'] > 0)
    {
        $aiml_id = $allrows['aiml_id'];
        $pattern = $allrows['pattern'];

        /** @noinspection SqlDialectInspection */
        $sql = "SELECT `template` FROM `$dbn`.`aiml` WHERE `id` = :id limit 1;";
        $row = db_fetch($sql, array(':id' => $aiml_id), __FILE__, __FUNCTION__, __LINE__);
        $template = add_text_tags($row['template']);

        try
        {
            $sraiTemplate = new SimpleXMLElement($template, LIBXML_NOCDATA);
        }
        catch (exception $e)
        {
            trigger_error("There was a problem parsing the SRAI template as XML. Template value:\n$template", E_USER_WARNING);
            $sraiTemplate = new SimpleXMLElement("<text>$error_response</text>", LIBXML_NOCDATA);
        }

        $responseArray = parseTemplateRecursive($convoArr, $sraiTemplate);
        $response = implode_recursive(' ', $responseArray, __FILE__, __FUNCTION__, __LINE__);

        try
        {
            // code to try here
            /** @noinspection SqlDialectInspection */
            $sql = "INSERT INTO `$dbn`.`srai_lookup` (`id`, `bot_id`, `pattern`, `template_id`) VALUES(null, :bot_id, :pattern, :template_id);";
            $params = array(
                ':bot_id' => $bot_id,
                ':pattern' => $pattern,
                ':template_id' => $aiml_id,
            );
            $affectedRows = db_write($sql, $params, false, __FILE__, __FUNCTION__, __LINE__);
            if ($affectedRows > 0) runDebug(__FILE__, __FUNCTION__, __LINE__, "Successfully inserted entry for '$pattern'.", 1);
        }
        catch (Exception $e)
        {
            //something to handle the problem here, usually involving $e->getMessage()
            $err = $e->getMessage();
            runDebug(__FILE__, __FUNCTION__, __LINE__, "Unable to insert entry for '$pattern'! Error = $err.", 1);
            runDebug(__FILE__, __FUNCTION__, __LINE__, "SQL = $sql", 1);
        }
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Returning results from stored srai lookup.", 2);

        return $response;
    }

    runDebug(__FILE__, __FUNCTION__, __LINE__, "Running SRAI $srai_iterations on $now_look_for_this", 3);
    runDebug(__FILE__, __FUNCTION__, __LINE__, $convoArr['aiml']['html_template'], 4);

    //number of srai iterations - will stop recursion if it is over 10
    $srai_iterations++;
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Incrementing srai iterations to $srai_iterations", 4);

    if ($srai_iterations > 10)
    {
        runDebug(__FILE__, __FUNCTION__, __LINE__, "ERROR - Too much recursion breaking out", 1);
        $convoArr['aiml']['parsed_template'] = $error_response;

        return $error_response;
    }

    //$tmp_convoArr = array();
    $tmp_convoArr = $convoArr;

    if (!isset($tmp_convoArr['stack']))
    {
        $tmp_convoArr = load_blank_stack($tmp_convoArr);
    }

    if (!isset($tmp_convoArr['topic']))
    {
        $tmp_convoArr['topic'] = array();
        $tmp_convoArr['topic'][1] = '';
    }

    $tmp_convoArr['aiml'] = array();
    $tmp_convoArr['that'][1][1] = "";
    //added
    $tmp_convoArr['aiml']['parsed_template'] = "";
    $tmp_convoArr['aiml']['lookingfor'] = $now_look_for_this;
    $tmp_convoArr['aiml']['pattern'] = $now_look_for_this;
    $tmp_convoArr['aiml']['thatpattern'] = $convoArr['aiml']['thatpattern'];
    $tmp_convoArr = get_aiml_to_parse($tmp_convoArr);
    $tmp_convoArr = parse_matched_aiml($tmp_convoArr, "srai", $now_look_for_this);
    $srai_parsed_template = $tmp_convoArr['aiml']['parsed_template'];

    runDebug(__FILE__, __FUNCTION__, __LINE__, "SRAI Found. Returning '$srai_parsed_template'", 2);

    $convoArr['client_properties'] = $tmp_convoArr['client_properties'];
    $srai_iterations--;
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Decrementing srai iterations to $srai_iterations", 4);

    return $srai_parsed_template . " ";
}

/**
 * Function push_stack
 *
 * @param $convoArr
 * @param $item
 * @return mixed
 */
function push_stack(& $convoArr, $item)
{
    if ((trim($item)) != (trim($convoArr['stack']['top'])))
    {
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Pushing $item onto to the stack", 4);

        $convoArr['stack']['last'] = $convoArr['stack']['seventh'];
        $convoArr['stack']['seventh'] = $convoArr['stack']['sixth'];
        $convoArr['stack']['sixth'] = $convoArr['stack']['fifth'];
        $convoArr['stack']['fifth'] = $convoArr['stack']['fourth'];
        $convoArr['stack']['fourth'] = $convoArr['stack']['third'];
        $convoArr['stack']['third'] = $convoArr['stack']['second'];
        $convoArr['stack']['second'] = $convoArr['stack']['top'];
        $convoArr['stack']['top'] = $item;
    }
    else {
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Could not push empty item onto to the stack", 1);
    }

    return $item;
}

/**
 * function pop_stack()
 * This function pops an item off the stack
 * @param array $convoArr - conversation array
 * @return string $item - the popped item
 **/
function pop_stack(& $convoArr)
{
    $item = trim($convoArr['stack']['top']);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Popped $item off the stack", 4);

    $convoArr['stack']['top'] = $convoArr['stack']['second'];
    $convoArr['stack']['second'] = $convoArr['stack']['third'];
    $convoArr['stack']['third'] = $convoArr['stack']['fourth'];
    $convoArr['stack']['fourth'] = $convoArr['stack']['fifth'];
    $convoArr['stack']['fifth'] = $convoArr['stack']['sixth'];
    $convoArr['stack']['sixth'] = $convoArr['stack']['seventh'];
    $convoArr['stack']['seventh'] = $convoArr['stack']['last'];
    $convoArr['stack']['last'] = "om";

    return $item;
}

/**
 * function make_learn()
 * This function builds the sql insert a learnt aiml cateogry in to the db
 * @param array $convoArr - conversation array
 * @param string $pattern - the pattern we will insert
 * @param string $template - the template to insert
 **/
function make_learn($convoArr, $pattern, $template)
{
    global $dbn;

    runDebug(__FILE__, __FUNCTION__, __LINE__, "Making learn", 2);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Pattern:  $pattern", 2);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Template: $template", 2);

    $pattern = normalize_text($pattern);
    $aiml = "<learn> <category> <pattern> <eval>$pattern</eval> </pattern> <template> <eval>$template</eval> </template> </category> </learn>";
    /** @noinspection PhpSillyAssignmentInspection */
    $aiml = $aiml;
    $pattern = $pattern . " ";
    $template = $template . " ";
    $convo_id = $convoArr['conversation']['convo_id'];
    $bot_id = $convoArr['conversation']['bot_id'];

    /** @noinspection SqlDialectInspection */
    $sql = "INSERT INTO `$dbn`.`aiml_userdefined`
        VALUES
        (NULL, '$aiml','$pattern','$template','$convo_id','$bot_id',NOW())";

    runDebug(__FILE__, __FUNCTION__, __LINE__, "Make learn SQL: $sql", 3);

    $numRows = db_write($sql, null, false, __FILE__, __FUNCTION__, __LINE__);
}

/**
 * function math_functions()
 * This function runs the system math operations
 *
 * @param string $operator - maths operator
 * @param int $num_1 - the first number
 * @param int|string $num_2 - the second number
 * @internal param int $output - the result of the math operation
 *
 * @return float|int|number|string
 */
function math_functions($operator, $num_1, $num_2 = "")
{
    $num_1 = (is_numeric($num_1)) ? $num_1 : 0;
    $num_2 = (is_numeric($num_2)) ? $num_2 : 0;
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Running system tag math $num_1 $operator $num_2", 4);

    $operator = _strtolower($operator);

    switch ($operator)
    {
        case "add" :
            $output = $num_1 + $num_2;
            break;
        case "subtract" :
            $output = $num_1 - $num_2;
            break;
        case "multiply" :
            $output = $num_1 * $num_2;
            break;
        case "divide" :
            if ($num_2 == 0)
            {
                $output = "You can't divide by 0!";
            }
            else {
                $output = $num_1 / $num_2;
            }
            break;
        case "sqrt" :
            $output = sqrt($num_1);
            break;
        case "power" :
            $output = pow($num_1, $num_2);
            break;
        default :
            $output = $operator . "?";
    }

    return $output;
}
