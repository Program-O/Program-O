<?php

  /***************************************
  * www.program-o.com
  * PROGRAM O
  * Version: 2.1.2
  * FILE: chatbot/core/aiml/parse_aiml.php
  * AUTHOR: Elizabeth Perreau and Dave Morton
  * DATE: MAY 4TH 2011
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
    $firstPersonSearchTemplate = '/\bi [word]\b/i';
    $secondPersonKeyedReplaceTemplate = 'y ou [word]';
    $thirdPersonReplaceTemplate = "$g3 [word]";
    //second to first
    $secondPersonSearchTemplate = '/\byou [word]\b/i';
    $firstPersonReplaceTemplate = 'I [word]';
    //second (reversed) to first
    $secondPersonSearchTemplateReversed = '/\b[word] you\b/i';
    $firstPersonReplaceTemplateReversed = '[word] @II';
    $secondPersonKeyedSearchTemplate = '/\by ou [word]\b/i';
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
  * @param string $in
  * @return the tranformed string
  **/
  function swapPerson($convoArr, $person = 2, $in)
  {
  //2 = swap first with second poerson (e.g. I with you) // otherwise swap with third person
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Person:$person In:$in", 4);
    $name = $convoArr['client_properties']['name'];
    $gender = $convoArr['client_properties']['gender'];
    $tmp = trim($in);
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
    $simpleFirstPersonPatterns = array('/(\bi am\b)/i', '/(\bam i\b)/i', '/(\bi\b)/i', '/(\bmy\b)/i', '/(\bmine\b)/i', '/(\bmyself\b)/i', '/(\bcan i\b)/i');
    $simpleSecondPersonKeyedReplacements = array('you are', 'are you', 'you', 'your', 'yours', 'yourself', 'can you');
    $simpleFirstToThirdPersonPatterns = array('/(\bi am\b)/i', '/(\bam i\b)/i', '/(\bi\b)/i', '/(\bmy\b)/i', '/(\bmine\b)/i', '/(\bmyself\b)/i', '/(\bwill i\b)/i', '/(\bshall i\b)/i', '/(\bmay i\b)/i', '/(\bmight i\b)/i', '/(\bcan i\b)/i', '/(\bcould i\b)/i', '/(\bmust i\b)/i', '/(\bshould i\b)/i', '/(\bwould i\b)/i', '/(\bneed i\b)/i', '/(\bam i\b)/i', '/(\bwas i\b)/i',);
    $simpleThirdPersonReplacements = array("$g3 is", "is $g3", "$g3", "$g1", "$g1", "$g2" . 'self', 'will ' . $g3, 'shall ' . $g3, 'may ' . $g3, 'might ' . $g3, 'can ' . $g3, 'could ' . $g3, 'must ' . $g3, 'should ' . $g3, 'would ' . $g3, 'need ' . $g3, 'is ' . $g3, 'was ' . $g3,);
    $simpleSecondPersonPatterns = array('/(\bhelp you\b)/i', '/(\bwill you\b)/i', '/(\bshall you\b)/i', '/(\bmay you\b)/i', '/(\bmight you\b)/i', '/(\bcan you\b)/i', '/(\bcould you\b)/i', '/(\bmust you\b)/i', '/(\bshould you\b)/i', '/(\bwould you\b)/i', '/(\bneed you\b)/i', '/(\bare you\b)/i', '/(\bwere you\b)/i', '/(\byour\b)/i', '/(\byours\b)/i', '/(\byourself\b)/i', '/(\bthy\b)/i');
    # will, shall, may, might, can, could, must, should, would, need
    $simpleFirstPersonReplacements = array('help m e', 'will @II', 'shall @II', 'may @II', 'might @II', 'can @II', 'could @II', 'must @II', 'should @II', 'would @II', 'need @II', 'am @II', 'was @II', 'my', 'mine', 'myself', 'my');
    if ($person == 2)
    {
      $tmp = preg_replace('/\bare you\b/i', 'am @II', $tmp);
      // simple second to first transform
      $tmp = preg_replace('/\byou and i\b/i', 'y ou and @II', $tmp);
      // fix the "Me and you" glitch
      $tmp = preg_replace($simpleSecondPersonPatterns, $simpleFirstPersonReplacements, $tmp);
      // "simple" second to keyed first transform
      $tmp = preg_replace($simpleFirstPersonPatterns, $simpleSecondPersonKeyedReplacements, $tmp);
      // simple first to keyed second transform
      $tmp = preg_replace($_SESSION['transform_list']['secondPersonPatterns'], $_SESSION['transform_list']['firstPersonReplacements'], $tmp);
      // second to first transform
      $tmp = preg_replace('/\bme\b/i', 'you', $tmp);
      // simple second to first transform (me)
      #$tmp = preg_replace('/\bi\b/i', 'y ou', $tmp);                                              // simple second to first transform (I)
      $tmp = preg_replace('/\byou\b/i', 'me', $tmp);
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
      $tmp = preg_replace('/(\byour gender\b)/i', $g3, $tmp);
      $tmp = preg_replace('/(\bthey\b)/i', $g3, $tmp);
      $tmp = preg_replace('/(\bi\b)/i', $g3, $tmp);
      $tmp = preg_replace('/(\bme\b)/i', $g3, $tmp);
    }
    //debug
    // if (RUN_DEBUG) runDebug(4, __FILE__, __FUNCTION__, __LINE__,"<br>\nTransformation complete. was: $in, is: $tmp");
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
  function clean_that($that)
  {
    $in = $that;
    $that = str_replace("<br/>", ".", $that);
    $that = strip_tags($that);
    $that = remove_all_punctuation($that);
    $that = whitespace_clean($that);
    $that = capitalize($that);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Cleaning the that - that: $in cleanthat:$that", 4);
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
    $aiml_pattern = $convoArr['aiml']['pattern'];
    $ap = trim($aiml_pattern);
    $ap = str_replace("+", "\+", $ap);
    $ap = str_replace("*", "(.*)", $ap);
    $ap = str_replace("_", "(.*)", $ap);
    $wildcards = str_replace("_", "(.*)?", str_replace("*", "(.*)?", $aiml_pattern));
    if ($wildcards != $aiml_pattern)
    {
      runDebug(__FILE__, __FUNCTION__, __LINE__, "We have stars to process!", 2);
      if (!isset ($convoArr['aiml']['user_raw']))
      {
        $checkagainst = $convoArr['aiml']['lookingfor'];
      }
      else
      {
        $checkagainst = $convoArr['aiml']['user_raw'];
      }
      runDebug(__FILE__, __FUNCTION__, __LINE__, "Checking '$ap' against '$checkagainst'.", 2);
      if (preg_match_all("~$ap~si", $checkagainst, $matches))
      {
        runDebug(__FILE__, __FUNCTION__, __LINE__, print_r($matches, true), 2);
        for ($i = 1; $i < count($matches); $i++)
        {
          $curStar = $matches[$i][0];
          $curStar = trim(remove_all_punctuation($curStar));
          $curIndex = $i;
          runDebug(__FILE__, __FUNCTION__, __LINE__, "Adding $curStar to the star stack.", 2);
          $convoArr['star'][$i] = $curStar;
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
  function run_srai(& $convoArr, $now_look_for_this)
  {
    global $srai_iterations, $offset, $error_response;
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
    $tmp_convoArr = array();
    $tmp_convoArr = $convoArr;
    $tmp_convoArr['aiml'] = array();
    $tmp_convoArr['that'][$offset][$offset] = "";
    //added
    $tmp_convoArr['aiml']['parsed_template'] = "";
    $tmp_convoArr['aiml']['lookingfor'] = $now_look_for_this;
    $tmp_convoArr = get_aiml_to_parse($tmp_convoArr);
    $tmp_convoArr = parse_matched_aiml($tmp_convoArr, "srai");
    $srai_parsed_template = $tmp_convoArr['aiml']['parsed_template'];
    runDebug(__FILE__, __FUNCTION__, __LINE__, "SRAI Found: '$srai_parsed_template'", 2);
    $convoArr['client_properties'] = $tmp_convoArr['client_properties'];
    $convoArr['topic'] = $tmp_convoArr['topic'][1];
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
    global $con, $dbn;
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Making learn", 2);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Pattern:  $pattern", 2);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Template: $template", 2);
    $pattern = clean_for_aiml_match($pattern);
    $aiml = "<learn> <category> <pattern> <eval>$pattern</eval> </pattern> <template> <eval>$template</eval> </template> </category> </learn>";
    $aiml = mysql_real_escape_string($aiml);
    $pattern = mysql_real_escape_string($pattern . " ");
    $template = mysql_real_escape_string($template . " ");
    $u_id = $convoArr['conversation']['user_id'];
    $bot_id = $convoArr['conversation']['bot_id'];
    $sql = "INSERT INTO `$dbn`.`aiml_userdefined`
        VALUES
        (NULL, '$aiml','$pattern','$template','$u_id','$bot_id',NOW())";
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Make learn SQL: $sql", 3);
    $res = mysql_query($sql, $con);
  }

  /**
  * function run_system()
  * This function runs the system math operations
  * @param char $operator - maths operator
  * @param int $num_1 - the first number
  * @param int $num_2 - the second number
  * @param int $output - the result of the math operation
  **/
  function run_system($operator, $num_1, $num_2 = "")
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Running system tag math $num_1 $operator $num_2", 4);
    switch (strtolower($operator))
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