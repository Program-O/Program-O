<?php

  /***************************************
  * http://www.program-o.com
  * PROGRAM O
  * Version: 2.1.1
  * FILE: chatbot/core/aiml/make_aiml_to_php_code.php
  * AUTHOR: ELIZABETH PERREAU
  * DATE: MAY 4TH 2011
  * DETAILS: this file contains the functions generate php code from aiml
  ***************************************/
  /**
  * function aiml_to_phpfunctions()
  * This function performs a big find and replace on the aiml to convert it to php code
  * @param  array $convoArr - the existing conversation array
  * @return array $convoArr
  **/
  function aiml_to_phpfunctions($convoArr)
  {
  //TODO do we need this still?
    global $botsay, $srai_iterations, $error_response;
    //TODO read from bot vars
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Converting the AIML to PHP code", 4);
    $template = add_text_tags($convoArr['aiml']['template']);
    try
    {
      $aimlTemplate = new SimpleXMLElement($template);
    }
    catch (exception $e)
    {
      //exit ('Trouble in paradise! Check the logs!<br>' . $e->getMessage() . "<br>\n<pre>template = " . htmlentities($template));
      trigger_error("There was a problem parsing the template as XML. Template value:\n$template", E_USER_WARNING);
      $aimlTemplate = new SimpleXMLElement("<text>$error_response</text>");
    }
    //$x = file_put_contents(_DEBUG_PATH_ . 'template.txt', $template);
    $responseArray = parseTemplateRecursive($convoArr, $aimlTemplate);
    #$x = file_put_contents(_DEBUG_PATH_ . 'responseArray.txt', print_r($responseArray, true));
    $botsay = trim(implode_recursive(' ', $responseArray, __FILE__, __FUNCTION__, __LINE__));
    $botsay = str_replace(' .', '.', $botsay);
    $botsay = str_replace('  ', ' ', $botsay);
    #$x = file_put_contents(_DEBUG_PATH_ . 'botsay.txt', print_r($botsay, true));
    $convoArr['aiml']['parsed_template'] = $botsay;
    runDebug(__FILE__, __FUNCTION__, __LINE__, "The bot will say: $botsay", 2);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Client properties = " . print_r($convoArr['client_properties'], true), 2);
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Completed parsing the template.', 2);
    return $convoArr;
  }

  function add_text_tags($in)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Starting function and setting timestamp.', 2);
    // Since we're going to parse the template's contents as XML, we need to prepare it first
    // by transforming it into valid XML
    // First, wrap the template in TEMPLATE tags:
    $template = "<template>$in</template>";
    // SimpleXML can't deal with "mixed" content, so any "loose" text is wrapped in a <text> tag.
    // The process will sometimes add extra <text> tags, so part of the process below deals with that.
    $textTagsToRemove = array('<text></text>' => '', '<text> </text>' => '', '<say>' => '', '</say>' => '',
    //'<bigthink></bigthink>' => '',
    );
    $template = preg_replace('~>\s*?<~', '><', $template);
    $textTagSearch = array_keys($textTagsToRemove);
    $textTagReplace = array_values($textTagsToRemove);
    $template = str_replace("\n", '', $template);
    $template = preg_replace('~>(.*?)<~', "><text>$1</text><", $template);
    $template = str_replace($textTagSearch, $textTagReplace, $template);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Returning template:\n$template", 4);
    return $template;
  }

  function implode_recursive($glue, $in, $file = 'unknown', $function = 'unknown', $line = 'unknown')
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Starting function and setting timestamp.', 2);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "This function was called from $file, function $function at line $line.", 2);
    if (!is_array($in))
    {
      trigger_error('Input not array! Input = ' . print_r($in, true));
      return $in;
    }
    foreach ($in as $index => $element)
    {
      if (empty ($element))
        continue;
      if (is_array($element))
      {
        $in[$index] = implode_recursive($glue, $element, __FILE__, __FUNCTION__, __LINE__);
      }
    }
    $out = (is_array($in)) ? implode($glue, $in) : $in;
    return ltrim($out);
  }

  function parseTemplateRecursive($convoArr, SimpleXMLElement $element, $level = 0)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Starting function and setting timestamp.', 2);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Client properties = " . print_r($convoArr['client_properties'], true), 2);
    $HTML_tags = array('a','abbr','acronym','address','applet','area','b','bdo','big','blockquote','br','button','caption','center','cite','code','col','colgroup','dd','del','dfn','dir','div','dl','dt','em','fieldset','font','form','h1','h2','h3','h4','h5','h6','hr','i','iframe','img','input','ins','kbd','label','legend','ol','object','s','script','small','span','strike','strong','sub','sup','table','tbody','td','textarea','tfoot','th','thead','tr','tt','u','ul');
    $doNotParseChildren = array('li');
    $response = array();
    $parentName = strtolower($element->getName());
    $children = $element->children();
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Processing element $parentName at level $level. element XML = " . $element->asXML(), 2);
    $func = 'parse_' . $parentName . '_tag';
    if (in_array($parentName, $HTML_tags)) $func = 'parse_html_tag';
    if (function_exists($func))
    {
      if (!in_array(strtolower($parentName), $doNotParseChildren))
      {
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Passing element $parentName to the $func function", 2);
        $retVal = $func($convoArr, $element, $parentName, $level);
        $retVal = (is_array($retVal)) ? $retVal = implode_recursive(' ', $retVal, __FILE__, __FUNCTION__, __LINE__) : $retVal;
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Adding '$retVal' to the response array. tag name is $parentName", 2);
        $response[] = $retVal;
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Client properties = " . print_r($convoArr['client_properties'], true), 2);
        return $response;
      }
    }
    else
    {
      $retVal = $element;
    }
    $value = trim((string) $retVal);
    $tmpResponse = ($level <= 1 and ($parentName != 'think') and (!in_array($parentName, $doNotParseChildren))) ? $value : '';
    if (count($children) == 0 && !empty ($value))
    {
    }
    if (count($children) > 0 and is_object($retVal))
    {
      $childLabel = (count($children) == 1) ? ' child' : ' children';
      foreach ($children as $child)
      {
        $childName = $child->getName();
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Client properties = " . print_r($convoArr['client_properties'], true), 2);
        if (in_array(strtolower($childName), $doNotParseChildren))
          continue;
        $tmpResponse = parseTemplateRecursive($convoArr, $child, $level + 1);
        $tmpResponse = implode_recursive(' ', $tmpResponse, __FILE__, __FUNCTION__, __LINE__);
        $tmpResponse = ($childName == 'think') ? '' : $tmpResponse;
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Adding '$tmpResponse' to the response array. tag name is $parentName.", 2);
        $response[] = $tmpResponse;
      }
    }

    /*
    $response = array(
    'This is a test',
    'of the',
    'implode_recursive()',
    array('function.', 'This sentence','should make perfect'),
    'sense.'
    );
    */
    return $response;
  }

  function parse_text_tag($convoArr, $element, $parentName, $level)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Starting function and setting timestamp.', 2);
    return (string) $element;
  }

  function parse_star_tag($convoArr, $element, $parentName, $level)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Starting function and setting timestamp.', 2);
    $response = array();
    runDebug(__FILE__, __FUNCTION__, __LINE__, "parseStar called from the $parentName tag at level $level. element = " . $element->asXML(), 2);
    $attributes = $element->attributes();
    if (count($attributes) != 0)
    {
      $index = $element->attributes()->index;
    }
    else
      $index = 1;
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Star index = $index.", 2);
    $star = $convoArr['star'][(int) $index];
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Adding '$star' to the response array.", 2);
    $response[] = $star;
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Index value = $index, Star value = $star", 2);
    return $response;
  }

  function parse_br_tag($convoArr, $element, $parentName, $level)
  {
    return "<br />\n";
  }

  function parse_date_tag($convoArr, $element, $parentName, $level)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Starting function and setting timestamp.', 2);
    global $time_zone_locale;
    $isWindows = (DIRECTORY_SEPARATOR == '/') ? false : true;
    $now = time();
    $format = $element->attributes()->format;
    $locale = $element->attributes()->locale;
    $tz = $element->attributes()->timezone;
    $format = (string) $format;
    $locale = (string) $locale;
    $tz = (string) $tz;
    $tz = (empty ($tz)) ? $time_zone_locale : $tz;
    $hereNow = new DateTimeZone($tz);
    $ts = new DateTime("now", $hereNow);
    //exit("ts = " . print_r($ts->getTimestamp(), true));
    if (empty ($format))
    {
      $response = date($ts->getTimestamp());
    }
    else
    {
      if ($isWindows)
        $format = str_replace('%l', '%#I', $format);
      $response = strftime($format, $ts->getTimestamp());
    }
    return $response;
  }

  function parse_random_tag($convoArr, $element, $parentName, $level)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Starting function and setting timestamp.', 2);
    $random = (array) $element;
    $liArray = $random['li'];
    $pick = array_rand($liArray);
    //echo "picking option #$pick from random tag.\n";
    $li = $element->li->$pick;
    $li = $li->children();
    $liTxt = $li->asXML();
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Chose '$liTxt' for output.", 2);
    return $li;
  }

  function parse_get_tag($convoArr, $element, $parentName, $level)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Starting function and setting timestamp.', 2);
    global $con, $dbn;
    $response = '';
    $bot_id = $convoArr['conversation']['bot_id'];
    $user_id = $convoArr['conversation']['user_id'];
    $var_name = $element->attributes()->name;
    $var_name = ($var_name == '*') ? $convoArr['star'][1] : $var_name;
    if (empty ($var_name))
      $response = 'undefined';
    if (empty ($response))
    {
      $sql = "select `value` from `$dbn`.`client_properties` where `user_id` = $user_id and `bot_id` = $bot_id and `name` = '$var_name';";
      runDebug(__FILE__, __FUNCTION__, __LINE__, "Checking the DB for $var_name - sql:\n$sql", 2);
      $result = db_query($sql, $con);
      if (($result) and (mysql_num_rows($result) > 0))
      {
        $row = mysql_fetch_array($result);
        $response = $row['value'];
      }
      else
        $response = 'undefined';
    }
    runDebug(__FILE__, __FUNCTION__, __LINE__, "The value for $var_name is $response.", 2);
    return $response;
  }

  function parse_set_tag($convoArr, $element, $parentName, $level)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Starting function and setting timestamp.', 2);
    global $con, $dbn;
    $element = $element->set;
    $response = '';
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Processing element $parentName at level $level. element XML = " . $element->asXML(), 2);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Client properties = " . print_r($convoArr['client_properties'], true), 2);
    $bot_id = $convoArr['conversation']['bot_id'];
    $user_id = $convoArr['conversation']['user_id'];
    $var_name = $element->attributes()->name;
    $var_name = ($var_name == '*') ? $convoArr['star'][1] : $var_name;
    $vn_type = gettype($var_name);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "var_name = $var_name and is type: $vn_type", 2);
    $var_value = $element;
    switch (true)
    {
    case (is_object($var_value)) : $children = $var_value->children();
      $tmp_var_value = array();
      foreach ($children as $child)
      {
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Client properties = " . print_r($convoArr['client_properties'], true), 2);
        $tmp_var_value[] = parseTemplateRecursive($convoArr, $child, $level + 1);
      }
      $var_value = implode_recursive(' ', $tmp_var_value, __FILE__, __FUNCTION__, __LINE__);
      if ($var_name == 'name')
      {
        $convoArr['client_properties']['name'] = $var_value;
        $convoArr['conversation']['user_name'] = $var_value;
        $sql = "UPDATE `$dbn`.`users` set `name` = '$var_value' where `id` = $user_id;";
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Updating user name in the DB. SQL:\n$sql", 3);
        $result = db_query($sql, $con) or trigger_error('Error setting user name in ' . __FILE__ . ', function ' . __FUNCTION__ . ', line ' . __LINE__ . ' - Error message: ' . mysql_error());
        $numRows = mysql_affected_rows();
        $sql = "select `name` from `$dbn`.`users` where `id` = $user_id;";
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Checking the users table to see if the value has changed. - SQL:\n$sql", 2);
        $result = db_query($sql, $con) or trigger_error('Error looking up DB info in ' . __FILE__ . ', function ' . __FUNCTION__ . ', line ' . __LINE__ . ' - Error message: ' . mysql_error());
        $rowCount = mysql_num_rows($result);
        if ($rowCount != 0)
        {
          $rows = mysql_fetch_assoc($result);
          $tmp_name = $rows['name'];
          #$tmp_name = print_r($rows, true);
          runDebug(__FILE__, __FUNCTION__, __LINE__, "The value for the user's name is $tmp_name.", 2);
        }
      }
      break;
    case (is_array($var_value)) : $var_value = implode_recursive(' ', $tmp_var_value, __FILE__, __FUNCTION__, __LINE__);
      break;
    case ($var_name == '*') : $star = $convoArr['star'][1];
      runDebug(__FILE__, __FUNCTION__, __LINE__, "Transforming $var_name to $star.", 2);
      $var_name = $star;
      break;
      default :
    }
    $sql = "select `value` from `$dbn`.`client_properties` where `user_id` = $user_id and `bot_id` = $bot_id and `name` = '$var_name';";
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Checking the client_properties table for the value of $var_name. - SQL:\n$sql", 2);
    $result = db_query($sql, $con) or trigger_error('Error looking up DB info in ' . __FILE__ . ', function ' . __FUNCTION__ . ', line ' . __LINE__ . ' - Error message: ' . mysql_error());
    $rowCount = mysql_num_rows($result);
    if ($rowCount == 0)
    {
      $sql = "insert into `$dbn`.`client_properties` (`id`, `user_id`, `bot_id`, `name`, `value`)
      values (NULL, $user_id, $bot_id, '$var_name', '$var_value');";
      runDebug(__FILE__, __FUNCTION__, __LINE__, "No value found for $var_name. Inserting $var_value into the table.", 2);
    }
    else
    {
      $sql = "update `$dbn`.`client_properties` set `value` = '$var_value' where `user_id` = $user_id and `bot_id` = $bot_id and `name` = '$var_name';";
      runDebug(__FILE__, __FUNCTION__, __LINE__, "Value found for $var_name. Updating the table to  $var_value.", 2);
    }
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Saving to DB - SQL:\n$sql", 2);
    $result = db_query($sql, $con) or trigger_error('Error saving to db in ' . __FILE__ . ', function ' . __FUNCTION__ . ', line ' . __LINE__ . ' - Error message: ' . mysql_error());
    $rowCount = mysql_affected_rows();
    $response = $var_value;
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Client properties = " . print_r($convoArr['client_properties'], true), 2);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Value for $var_name has ben set. Returning $var_value.", 2);
    return $response;
  }

  function parse_think_tag($convoArr, $element, $parentName, $level)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Starting function and setting timestamp.', 2);
    $children = $element->children();
    if ($parentName == 'think')
      $element = $children;
    $parentName = strtolower($element->getName());
    $children = $element->children();
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Processing element $parentName at level $level. element XML = " . $element->asXML(), 2);
    $func = 'parse_' . $parentName . '_tag';
    if (function_exists($func))
    {
      runDebug(__FILE__, __FUNCTION__, __LINE__, "Passing element $parentName to the $func function. element XML = " . $element->asXML(), 2);
      $retVal = $func($convoArr, $element, $parentName, $level);
      $retVal = '';
      runDebug(__FILE__, __FUNCTION__, __LINE__, "Adding '$retVal' to the response array.", 2);
      $response[] = $retVal;
      return $response;
    }
    else
    {
      $retVal = $element;
    }
    if (is_string($retVal))
    {
      runDebug(__FILE__, __FUNCTION__, __LINE__, "Adding '$retVal' to the response array.", 2);
      $response[] = $retVal;
      return $response;
    }
    if (!empty ($children))
    {
      foreach ($children as $child)
      {
        $childName = $child->getName();
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Processing child $childName. element XML = " . $child->asXML(), 2);
        //$response[] = parseTemplateRecursive($convoArr, $child, $level + 1);
      }
    }
    return '';
  }

  function parse_bot_tag($convoArr, $element)
  {
    $attributeName = $element->attributes()->name;
    $attributeName = ($attributeName == '*') ? $convoArr['star'][1] : $attributeName;
    $response = (!empty ($convoArr['bot_properties'][$attributeName])) ? $convoArr['bot_properties'][$attributeName] : 'undefined';
    return $response;
  }

  function parse_id_tag($convoArr, $element, $parentName, $level)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Starting function and setting timestamp.', 2);
    return $convoArr['conversation']['convo_id'];
  }

  function parse_version_tag($convoArr, $element, $parentName, $level)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Starting function and setting timestamp.', 2);
    return 'Program O version ' . VERSION;
  }

  function parse_uppercase_tag($convoArr, $element, $parentName, $level)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Starting function and setting timestamp.', 2);
    $response = array();
    $children = $element->children();
    if (!empty ($children))
    {
      $response = parseTemplateRecursive($convoArr, $children, $level + 1);
    }
    else
    {
      $response[] = (string) $element;
    }
    $response_string = implode_recursive(' ', $response);
    return ltrim(strtoupper($response_string), ' ');
  }

  function parse_lowercase_tag($convoArr, $element, $parentName, $level)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Starting function and setting timestamp.', 2);
    $response = array();
    $children = $element->children();
    if (!empty ($children))
    {
      $response = parseTemplateRecursive($convoArr, $children, $level + 1);
    }
    else
    {
      $response[] = (string) $element;
    }
    $response_string = implode_recursive(' ', $response);
    return ltrim(strtolower($response_string), ' ');
  }

  function parse_sentence_tag($convoArr, $element, $parentName, $level)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Starting function and setting timestamp.', 2);
    $response = array();
    $children = $element->children();
    if (!empty ($children))
    {
      $response = parseTemplateRecursive($convoArr, $children, $level + 1);
    }
    else
    {
      $response[] = (string) $element;
    }
    $response_string = implode_recursive(' ', $response);
    $response = ucfirst(strtolower($response_string));
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Response string was: $response_string. Transformed to $response.", 2);
    return $response;
  }

  function parse_formal_tag($convoArr, $element, $parentName, $level)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Starting function and setting timestamp.', 2);
    $response = array();
    $children = $element->children();
    if (!empty ($children))
    {
      $response = parseTemplateRecursive($convoArr, $children, $level + 1);
    }
    else
    {
      $response[] = (string) $element;
    }
    $response_string = implode_recursive(' ', $response);
    $response = ucwords(strtolower($response_string));
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Response string was: $response_string. Transformed to $response.", 2);
    return $response;
  }

  function parse_srai_tag($convoArr, $element, $parentName, $level)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Starting function and setting timestamp.', 2);
    $response = array();
    $children = $element->children();
    if (!empty ($children))
    {
      $response = parseTemplateRecursive($convoArr, $children, $level + 1);
    }
    else
    {
      $response[] = (string) $element;
    }
    $response_string = implode_recursive(' ', $response);
    $response = run_srai($convoArr, $response_string);
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Finished parsing SRAI tag', 2);
    //exit("SRAI template = $response");
    return $response;
  }

  function parse_sr_tag($convoArr, $element, $parentName, $level)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Starting function and setting timestamp.', 2);
    $response = run_srai($convoArr, $convoArr['star'][1]);
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Finished parsing SRAI tag', 2);
    //exit("SRAI template = $response");
    return $response;
  }

  function parse_condition_tag($convoArr, $element, $parentName, $level)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Starting function and setting timestamp.', 2);
    $response = array();
    $attrName = $element['name'];
    if (!empty($attrName))
    {
      $attrValue = get_client_property($convoArr, (string) $attrName);
      return "attrName = $attrName. value = $attrValue";
      $attrName = ($attrName == '*') ? $convoArr['star'][1] : $attrName;
      $search = $convoArr['client_properties'][$attrName];
      $path = ($search != 'undefined') ? "//li[@value=\"$search\"]" : '//li[not@*]';
      $choice = $element->xpath($path);
      $choiceType = gettype($choice);
      exit("<pre>Choice type = $choiceType. Value = " . print_r($choice, true));
      $children = $choice[0]->children();
      if (!empty($children))
      {
        $response = parseTemplateRecursive($convoArr, $children, $level + 1);
      }
      else
      {
        $response[] = (string) $choice;
      }
      $response_string = implode_recursive(' ', $response);
      return $response_string;
    }
    else
    {
      //
    }
    #exit("<pre>Element value:<br />\n" . print_r($element, true));
    #exit("<pre>Element name:$parentName\nElement Attribute name: $attrName");
/*
    $attributes = $element->attributes();
    $attArray = (array) $attributes;
    $attKeys = array_keys($attArray['@attributes']);
    $attName = $attKeys[0];
    $name = $attributes->getName();
    $attributeName = (string) $attributes[$name];
    $pick = (!empty ($convoArray['client_properties'][$attributeName])) ? $convoArray['client_properties'][$attributeName] : $this->undefined;
    #echo "attribute $attName = $attributeName, value = $pick<br>\n";
    $path = ($pick != $this->undefined) ? "//li[@value=\"$pick\"]" : '//li[not(@*)]';
    $choice = $element->xpath($path);
    $out = (!empty ($choice[0]->text)) ? (string) $choice[0]->text : (string) $choice;
    return $out;
*/
  }

  function parse_person_tag($convoArr, $element, $parentName, $level)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Starting function and setting timestamp.', 2);
    $response = array();
    $children = $element->children();
    if (!empty ($children))
    {
      $response = parseTemplateRecursive($convoArr, $children, $level + 1);
    }
    else
    {
      $response[] = $convoArr['star'][1];
    }
    $response_string = implode_recursive(' ', $response);
    $response = swapPerson($convoArr, 3, $response_string);
    return $response;
  }

  function parse_person2_tag($convoArr, $element, $parentName, $level)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Starting function and setting timestamp.', 2);
    $response = array();
    $children = $element->children();
    if (!empty ($children))
    {
      $response = parseTemplateRecursive($convoArr, $children, $level + 1);
    }
    else
    {
      $response[] = $convoArr['star'][1];
    }
    $response_string = implode_recursive(' ', $response);
    $response = swapPerson($convoArr, 2, $response_string);
    return $response;
  }

  function parse_that_tag($convoArr, $element, $parentName, $level)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Starting function and setting timestamp.', 2);
    $index = $element['index'];
    if (!empty($index))
    {
      $is2D = strstr($index,',');
    }
  }

  function parse_html_tag($convoArr, $element, $parentName, $level)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Starting function and setting timestamp.', 2);
    return (string) $element->asXML();
  }



?>