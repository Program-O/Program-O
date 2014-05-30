<?php

  /***************************************
  * www.program-o.com
  * PROGRAM O
  * Version: 2.4.1
  * FILE: chatbot/core/aiml/parse_aiml.php
  * AUTHOR: Elizabeth Perreau and Dave Morton
  * DATE: MAY 17TH 2014
  * DETAILS: this file contains the functions used to convert aiml to php
  ***************************************/


  /**
  * function buildVerbList()
  * @param array $convoArr
  * @param string $name
  * @param string $gender
  **/
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
    $secondPersonKeyedReplaceTemplate = 'y ou [word]';
    $thirdPersonReplaceTemplate = "$g3 [word]";
    //second to first
    $secondPersonSearchTemplate = '/\byou [word]\b/ui';
    $firstPersonReplaceTemplate = 'I [word]';
    //second (reversed) to first
    $secondPersonSearchTemplateReversed = '/\b[word] you\b/ui';
    $firstPersonReplaceTemplateReversed = '[word] @II';
    $secondPersonKeyedSearchTemplate = '/\by ou [word]\b/ui';
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
      if (empty ($line))
        continue;
      $firstChar = substr($line, 0, 1);
      if ($firstChar === '#')
        continue;
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
      else
      {
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
  * @return the tranformed string
  **/
  function swapPerson($convoArr, $person = 2, $input)
  {
  //2 = swap first with second poerson (e.g. I with you) // otherwise swap with third person
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Person:$person In:$input", 4);
    $name = $convoArr['client_properties']['name'];
    $gender = (isset($convoArr['client_properties']['gender'])) ? $convoArr['client_properties']['gender'] : 'unknown';
    $tmp = trim($input);
    if ((!isset ($_SESSION['transform_list'])) || ($_SESSION['transform_list'] == NULL))
    {
      buildVerbList($name, $gender);
    }
    switch ($gender)
    {
      case "male" :
        $g1 = "his";
        $g2 = "him";
        $g3 = "he";
        break;
      case "female";
      $g1 = "hers";
      $g2 = "her";
      $g3 = "she";
      break;
      default :
        $g1 = "theirs";
        $g2 = "them";
        $g3 = "they";
    }
    // the "simple" transform arrays - more for exceptions to the above rules than for anything "simple" :)
    $simpleFirstPersonPatterns = array('/(\bi am\b)/ui', '/(\bam i\b)/ui', '/(\bi\b)/ui', '/(\bmy\b)/ui', '/(\bmine\b)/ui', '/(\bmyself\b)/ui', '/(\bcan i\b)/ui');
    $simpleSecondPersonKeyedReplacements = array('you are', 'are you', 'you', 'your', 'yours', 'yourself', 'can you');
    $simpleFirstToThirdPersonPatterns = array('/(\bi am\b)/ui', '/(\bam i\b)/ui', '/(\bi\b)/ui', '/(\bmy\b)/ui', '/(\bmine\b)/ui', '/(\bmyself\b)/ui', '/(\bwill i\b)/ui', '/(\bshall i\b)/ui', '/(\bmay i\b)/ui', '/(\bmight i\b)/ui', '/(\bcan i\b)/ui', '/(\bcould i\b)/ui', '/(\bmust i\b)/ui', '/(\bshould i\b)/ui', '/(\bwould i\b)/ui', '/(\bneed i\b)/ui', '/(\bam i\b)/ui', '/(\bwas i\b)/ui',);
    $simpleThirdPersonReplacements = array("$g3 is", "is $g3", "$g3", "$g1", "$g1", "$g2" . 'self', 'will ' . $g3, 'shall ' . $g3, 'may ' . $g3, 'might ' . $g3, 'can ' . $g3, 'could ' . $g3, 'must ' . $g3, 'should ' . $g3, 'would ' . $g3, 'need ' . $g3, 'is ' . $g3, 'was ' . $g3,);
    $simpleSecondPersonPatterns = array('/(\bhelp you\b)/ui', '/(\bwill you\b)/ui', '/(\bshall you\b)/ui', '/(\bmay you\b)/ui', '/(\bmight you\b)/ui', '/(\bcan you\b)/ui', '/(\bcould you\b)/ui', '/(\bmust you\b)/ui', '/(\bshould you\b)/ui', '/(\bwould you\b)/ui', '/(\bneed you\b)/ui', '/(\bare you\b)/ui', '/(\bwere you\b)/ui', '/(\byour\b)/ui', '/(\byours\b)/ui', '/(\byourself\b)/ui', '/(\bthy\b)/ui');
    # will, shall, may, might, can, could, must, should, would, need
    $simpleFirstPersonReplacements = array('help m e', 'will @II', 'shall @II', 'may @II', 'might @II', 'can @II', 'could @II', 'must @II', 'should @II', 'would @II', 'need @II', 'am @II', 'was @II', 'my', 'mine', 'myself', 'my');
    if ($person == 2)
    {
      $tmp = preg_replace('/\bare you\b/ui', 'am @II', $tmp);
      // simple second to first transform
      $tmp = preg_replace('/\byou and i\b/ui', 'y ou and @II', $tmp);
      // fix the "Me and you" glitch
      $tmp = preg_replace($simpleSecondPersonPatterns, $simpleFirstPersonReplacements, $tmp);
      // "simple" second to keyed first transform
      $tmp = preg_replace($simpleFirstPersonPatterns, $simpleSecondPersonKeyedReplacements, $tmp);
      // simple first to keyed second transform
      $tmp = preg_replace($_SESSION['transform_list']['secondPersonPatterns'], $_SESSION['transform_list']['firstPersonReplacements'], $tmp);
      // second to first transform
      $tmp = preg_replace('/\bme\b/ui', 'you', $tmp);
      // simple second to first transform (me)
      #$tmp = preg_replace('/\bi\b/ui', 'y ou', $tmp);                                              // simple second to first transform (I)
      $tmp = preg_replace('/\byou\b/ui', 'me', $tmp);
      // simple second to first transform
      $tmp = str_replace('you', 'you', $tmp);
      // replace second person key (y ou) with non-keyed value (you)
      $tmp = str_replace(' me', ' me', $tmp);
      // replace first person key (m e) with non-keyed value (me)
      $tmp = str_replace(' my', ' my', $tmp);
      // replace first person key (m e) with non-keyed value (me)
      $tmp = str_replace('my ', 'my ', $tmp);
      // replace first person key (m e) with non-keyed value (me)
      $tmp = str_replace(' mine', ' mine', $tmp);
      // replace first person key (m e) with non-keyed value (me)
      $tmp = str_replace('mine ', 'mine ', $tmp);
      // replace first person key (m e) with non-keyed value (me)
      $tmp = str_replace(' @II ', ' I ', $tmp);
      // replace first person key (@I) with non-keyed value (I)
      $tmp = str_replace('@II ', 'I ', $tmp);
      // replace first person key (@I) with non-keyed value (I)
      $tmp = str_replace(' @II', ' I', $tmp);
      // replace first person key (@I) with non-keyed value (I)
      $tmp = str_replace('@you', 'I', $tmp);
      // replace first person key (@I) with non-keyed value (I)
      #$tmp = ucfirst(  $tmp);
    }
    elseif ($person == 3)
    {
      $tmp = preg_replace($_SESSION['transform_list']['firstPersonPatterns'], $_SESSION['transform_list']['thirdPersonReplacements'], $tmp);
      // first to third transform, but only when specifically needed
      $tmp = preg_replace('/(\byour gender\b)/ui', $g3, $tmp);
      $tmp = preg_replace('/(\bthey\b)/ui', $g3, $tmp);
      $tmp = preg_replace('/(\bi\b)/ui', $g3, $tmp);
      $tmp = preg_replace('/(\bme\b)/ui', $g3, $tmp);
    }
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
  * @return array $convoArr - the updated conversation array
  **/
  function parse_matched_aiml($convoArr, $type = "normal")
  {
  //which debug mode?
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Run the aiml parse in $type mode (normal or srai)", 3);
    $convoArr = set_wildcards($convoArr);
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
  * @param string $that - the string to clean
  * @return string $that - the cleaned string
  **/
  function clean_that($that, $file, $function, $line)
  {
    #runDebug(__FILE__, __FUNCTION__, __LINE__,"This was called from $file, function $function, line $line", 4);
    runDebug(__FILE__, __FUNCTION__, __LINE__,"Cleaning up the ~THAT~ tag: '$that'", 4);
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
  * @return array $convoArr - the updated conversation array
  **/
  function set_wildcards($convoArr)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Setting Wildcards", 2);
    //save_file(_LOG_PATH_ . 'convoarr.txt', print_r($convoArr, true));
    $aiml_pattern = $convoArr['aiml']['pattern'];
    $ap = trim($aiml_pattern);
    $ap = str_replace("+", "\+", $ap);
    $ap = str_replace(" * ", " (\S*) ", $ap);
    $ap = str_replace(" _ ", " (\S*) ", $ap);
    $ap = str_replace("* ", "(\S*) ", $ap);
    $ap = str_replace("_ ", "(\S*) ", $ap);
    $ap = str_replace(" *", " (.*)", $ap);
    $ap = str_replace(" _", " (.*)", $ap);
    $ap = str_replace("*", "(.*)", $ap);
    $ap = str_replace("_", "(.*)", $ap);
    $ap = str_replace("(\S(.*))", "(.*)", $ap);
    $ap = str_replace("(.(.*))", "(.*)", $ap);
    // Set pattern wildcards
    $pattern_wildcards = str_replace("_", "(.*)?", str_replace("*", "(.*)?", $aiml_pattern));
    if ($pattern_wildcards != $aiml_pattern)
    {
      runDebug(__FILE__, __FUNCTION__, __LINE__, "We have pattern stars to process!", 2);
      if (!isset ($convoArr['aiml']['user_raw']))
      {
        $checkagainst = $convoArr['aiml']['lookingfor'];
      }
      else
      {
        $checkagainst = $convoArr['aiml']['user_raw'];
      }
      runDebug(__FILE__, __FUNCTION__, __LINE__, "Checking '$ap' against '$checkagainst'.", 2);
      if (preg_match_all("~$ap~siu", $checkagainst, $matches))
      {
        runDebug(__FILE__, __FUNCTION__, __LINE__, print_r($matches, true), 2);
        for ($i = 1; $i < count($matches); $i++)
        {
          $curStar = trim($matches[$i][0]);
          $curStar = preg_replace('/[[:punct:]]/uis', ' ',$curStar);
          $curIndex = $i;
          runDebug(__FILE__, __FUNCTION__, __LINE__, "Adding $curStar to the star stack.", 2);
          $convoArr['star'][$i] = $curStar;
        }
      }
      else
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Something is not right here.", 2);
    }
    // Set that stars (match against just the first instance of that for now - need to work out something for all instances, though)
    $aiml_thatpattern = $convoArr['aiml']['thatpattern'];
    $tp = trim($aiml_thatpattern);
    $tp = str_replace("+", "\+", $tp);
/*
    $tp = str_replace("*", "(.*)", $tp);
    $tp = str_replace("_", "(.*)", $tp);
*/
    $tp = str_replace(" * ", " (\S*) ", $tp);
    $tp = str_replace(" _ ", " (\S*) ", $tp);
    $tp = str_replace("* ", "(\S*) ", $tp);
    $tp = str_replace("_ ", "(\S*) ", $tp);
    $tp = str_replace(" *", " (.*)", $tp);
    $tp = str_replace(" _", " (.*)", $tp);
    $tp = str_replace("*", "(.*)", $tp);
    $tp = str_replace("_", "(.*)", $tp);
    $thatpattern_wildcards = str_replace("_", "(.*)?", str_replace("*", "(.*)?", $aiml_thatpattern));
    if ($thatpattern_wildcards != $aiml_thatpattern)
    {
      runDebug(__FILE__, __FUNCTION__, __LINE__, "We have thatpattern stars to process!", 2);
      $that = $convoArr['that'][1];
      $checkagainst = implode_recursive(' ', $that, __FILE__, __FUNCTION__, __LINE__);
      runDebug(__FILE__, __FUNCTION__, __LINE__, "Checking '$tp' against '$checkagainst'.", 2);
      if (preg_match_all("~$tp~si", $checkagainst, $matches))
      #if (preg_match("~$tp~si", $checkagainst, $matches))
      {
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Current THAT matches:\n" . print_r($matches, true), 2);
        for ($i = 1; $i < count($matches); $i++)
        {
          $curStar = trim($matches[$i][0]);
          $curStar = preg_replace('/[[:punct:]]/uis', ' ',$curStar);
          $curIndex = $i;
          runDebug(__FILE__, __FUNCTION__, __LINE__, "Adding $curStar to the that_star stack.", 2);
          $convoArr['that_star'][$i] = $curStar;
        }
      }
      else
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Something is not right here.", 2);
    }

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
    global $srai_iterations, $error_response, $dbConn, $dbn;
    runDebug(__FILE__, __FUNCTION__, __LINE__,'Checking for entries in the srai_lookup table.', 2);
    $lookingfor = $convoArr['aiml']['lookingfor'];
    //$now_look_for_this = strtoupper($now_look_for_this);
    $sql = "select `template_id` from `$dbn`.`srai_lookup` where `pattern` like '$now_look_for_this';";
    runDebug(__FILE__, __FUNCTION__, __LINE__,"lookup SQL = $sql", 2);
    $sth = $dbConn->prepare($sql);
    $sth->execute();
    $row = $sth->fetch();
    $num_rows = count($row);
    runDebug(__FILE__, __FUNCTION__, __LINE__,"Found $num_rows rows in lookup table: " . print_r($row, true), 2);
    if ($num_rows > 0)
    {
      $template_id = $row['template_id'];
      runDebug(__FILE__, __FUNCTION__, __LINE__,"Found a matching entry in the lookup table. Using ID# $template_id.", 2);
      $sql = "select `template` from `$dbn`.`aiml` where `id` = '$template_id';";
      $sth = $dbConn->prepare($sql);
      $sth->execute();
      $row = $sth->fetch();
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
    if (!isset($tmp_convoArr['stack'])) $tmp_convoArr = load_blank_stack($tmp_convoArr);
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
    $tmp_convoArr = parse_matched_aiml($tmp_convoArr, "srai");
    $srai_parsed_template = $tmp_convoArr['aiml']['parsed_template'];
    runDebug(__FILE__, __FUNCTION__, __LINE__, "SRAI Found. Returning '$srai_parsed_template'", 2);
    $convoArr['client_properties'] = $tmp_convoArr['client_properties'];
    $convoArr['topic'] = $tmp_convoArr['topic'];
    $convoArr['stack'] = $tmp_convoArr['stack'];
    $srai_iterations--;
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Decrementing srai iterations to $srai_iterations", 4);
    return $srai_parsed_template . " ";
  }

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
    else
    {
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
    global $dbConn, $dbn;
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Making learn", 2);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Pattern:  $pattern", 2);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Template: $template", 2);
    $pattern = normalize_text($pattern);
    $aiml = "<learn> <category> <pattern> <eval>$pattern</eval> </pattern> <template> <eval>$template</eval> </template> </category> </learn>";
    $aiml = $aiml;
    $pattern = $pattern . " ";
    $template = $template . " ";
    $u_id = $convoArr['conversation']['user_id'];
    $bot_id = $convoArr['conversation']['bot_id'];
    $sql = "INSERT INTO `$dbn`.`aiml_userdefined`
        VALUES
        (NULL, '$aiml','$pattern','$template','$u_id','$bot_id',NOW())";
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Make learn SQL: $sql", 3);
    
    $sth = $dbConn->prepare($sql);
    $sth->execute();

    $numRows = $sth->rowCount();
  }

  /**
  * function math_functions()
  * This function runs the system math operations
  * @param char $operator - maths operator
  * @param int $num_1 - the first number
  * @param int $num_2 - the second number
  * @param int $output - the result of the math operation
  **/
  function math_functions($operator, $num_1, $num_2 = "")
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Running system tag math $num_1 $operator $num_2", 4);
    $operator = (IS_MB_ENABLED) ? mb_strtolower($operator) : strtolower($operator);
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
        else
        {
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

?>