<?php

  /***************************************
  * www.program-o.com
  * PROGRAM O
  * Version: 2.1.5
  * FILE: chatbot/core/conversation/intialise_conversation.php
  * AUTHOR: Elizabeth Perreau and Dave Morton
  * DATE: MAY 4TH 2011
  * DETAILS: this file contains the functions intialise
  *          the conversation
  ***************************************/

  /**
  * function intialise_convoArray()
  * A function to intialise the conversation array
  * This is the array that is built throught the conversation
  * @param  string $convo_id - unique session id
  * @param  int $bot_id - the id of the bot
  * @param  string $format - the return format of the response (html,json,xml)
  * @param  array $convoArr - the current state of the conversation array
  * @return $convoArr (updated)
  **/
  function intialise_convoArray($convoArr)
  {
    //set the initial convoArr values
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Intialising conversation", 4);
    //load blank topics
    $convoArr = load_blank_convoArray('topic', "", $convoArr);
    //load blank thats
    $convoArr = load_blank_convoArray('that', "", $convoArr);
    //load blank stars
    $convoArr = load_blank_convoArray('star', "", $convoArr);
    //load blank stars
    $convoArr = load_blank_convoArray('input', "", $convoArr);
    //load blank stack
    $convoArr = load_blank_stack($convoArr);
    //load the new client defaults
    $convoArr = load_new_client_defaults($convoArr);
    return $convoArr;
  }

  /**
  * function load_blank_convoArray()
  * A function to intialise the conversation array values
  * @param  string $arrayIndex - the array element we are going to intialise
  * @param  string $defaultValue - the value which will be used to set the element
  * @param  array $convoArr - the current state of the conversation array
  * @return $convoArr (updated)
  **/
  function load_blank_convoArray($arrayIndex, $defaultValue, $convoArr)
  {
    global $default_remember_up_to;
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Loading blank $arrayIndex array", 4);
    //set in global config file
    $remember_up_to = (isset($convoArr['conversation']['remember_up_to'])) ? $convoArr['conversation']['remember_up_to'] : $default_remember_up_to;
    for ($i = 1; $i <= ($remember_up_to + 1); $i++)
    {
      $convoArr[$arrayIndex][$i] = $defaultValue;
    }
    return $convoArr;
  }

  /**
  * function load_blank_stack()
  * A function to intialise the conversation stack values
  * @param  string $arrayIndex - the array element we are going to intialise
  * @param  string $defaultValue - the value which will be used to set the element
  * @param  array $convoArr - the current state of the conversation array
  * @return $convoArr (updated)
  **/
  function load_blank_stack($convoArr)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Loading blank stack", 4);
    global $default_stack_value;
    //set in global config file
    $convoArr['stack']['top'] = $default_stack_value;
    $convoArr['stack']['second'] = $default_stack_value;
    $convoArr['stack']['third'] = $default_stack_value;
    $convoArr['stack']['fourth'] = $default_stack_value;
    $convoArr['stack']['fifth'] = $default_stack_value;
    $convoArr['stack']['sixth'] = $default_stack_value;
    $convoArr['stack']['seventh'] = $default_stack_value;
    $convoArr['stack']['last'] = $default_stack_value;
    return $convoArr;
  }

  /**
  * function load_default_bot_values()
  * A function to intialise the chatbot properties
  * @param  array $convoArr - the current state of the conversation array
  * @return $convoArr (updated)
  **/
  function load_default_bot_values($convoArr)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Loading db bot personality properties", 4);
    global $con, $dbn;
    //set in global config file
    $sql = "SELECT * FROM `$dbn`.`botpersonality` WHERE `bot_id` = '" . $convoArr['conversation']['bot_id'] . "'";
    runDebug(__FILE__, __FUNCTION__, __LINE__, "load db bot personality values SQL: $sql", 3);
    $result = db_query($sql, $con);
    while ($row = mysql_fetch_array($result))
    {
      $convoArr['bot_properties'][$row['name']] = $row['value'];
    }
    return $convoArr;
  }

  /**
  * function write_to_session()
  * A function to save the current conversation state to session for the next turn
  * @param  array $convoArr - the current state of the conversation array
  * @return $convoArr
  **/
  function write_to_session($convoArr)
  {
    // TODO: Reduce the convo array to only the barest info necessary before saving
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Saving to session", 4);
    $_SESSION['programo'] = $convoArr;
    return $convoArr;
  }

  /**
  * function read_from_session()
  * A function to read the current conversation state from session for this turn
  * @return $convoArr
  **/
  function read_from_session()
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Reading from session", 4);
    $convoArr = array();
    //initialise
    if (isset ($_SESSION['programo']))
    {
      $convoArr = $_SESSION['programo'];
    }
    return $convoArr;
  }

  /**
  * function add_new_conversation_vars()
  * A function add the new values from the user input into the conversation state
  * @param  string $say - the user input
  * @param  array $convoArr - the current state of the conversation array
  * @return $convoArr (updated)
  **/
  function add_new_conversation_vars($say, $convoArr)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, "New conversation vars", 4);
    //put what the user has said on the front of the 'user_say' and 'input' subarray with a minimum clean to prevent injection
    $convoArr = push_on_front_convoArr("user_say", strip_tags($say), $convoArr);
    $convoArr['aiml']['user_raw'] = strip_tags($say);
    $convoArr = push_on_front_convoArr('input', $convoArr['aiml']['user_raw'], $convoArr);
    return $convoArr;
  }

  /**
  * function add_firstturn_conversation_vars()
  * A function add the bot values to the conversation state if this is the first turn
  * @param  array $convoArr - the current state of the conversation array
  * @return $convoArr (updated)
  **/
  function add_firstturn_conversation_vars($convoArr)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, "First turn", 4);
    if (!isset ($convoArr['bot_properties']))
    {
      $convoArr = load_default_bot_values($convoArr);
    }
    return $convoArr;
  }

  /**
  * function push_on_front_convoArr()
  * A function to push items on the front of a subarray in convoArr
  * @param  array $arrayIndex - the subarray index to push to
  * @param  array $value - the value to push on teh subarray
  * @param  array $convoArr - the current state of the conversation array
  * @return $convoArr (updated)
  * TODO BETTER COMMENTING
  **/
  function push_on_front_convoArr($arrayIndex, $value, $convoArr)
  {
    global $rememLimit, $default_remember_up_to;
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Pushing '$value' to the front of the [$arrayIndex] array", 2);
    $remember_up_to = (isset($convoArr['conversation']['remember_up_to'])) ? $convoArr['conversation']['remember_up_to'] : $default_remember_up_to;
    //these subarray indexes are 2d
    $two_d_arrays = array("that", "that_raw");
    $arrayIndex = trim($arrayIndex);
    //mini clean
    $value = trim($value);
    $value = preg_replace('/\s\s+/', ' ', $value);
    $value = preg_replace('/\s\./', '.', $value);
    //there is a chance the subarray has not been set yet so check and if not set here
    if (!isset ($convoArr[$arrayIndex][1]))
    {
      $convoArr[$arrayIndex] = array();
      $convoArr = load_blank_convoArray($arrayIndex, "", $convoArr);
    }
    //if the subarray is itself an array check it here
    if (in_array($arrayIndex, $two_d_arrays))
    {
      $matches = preg_match_all("# ?(([^\.\?!]*)+(?:[\.\?!]|(?:<br ?/?>))*)#ui", $value, $sentences);
      $cmatch = 0;
      //do another check to make sure the array is not just full of blanks
      foreach ($sentences as $temp)
      {
        foreach ($temp as $chk)
        {
          if (trim($chk) != "")
          {
            $cmatch++;
          }
        }
      }
      //if there definately is something in the sentence array build the temp sentence array
      if (($cmatch > 0) && ($matches !== FALSE))
      {
        foreach ($sentences[1] as $index => $value)
        {
          if ($arrayIndex == "that")
          {
            $t = clean_that($value);
            if ($t != "")
            {
              $tmp_sentence[] = $t;
            }
          }
          else
          {
            $tmp_sentence[] = $value;
          }
        }
        //reverse the array and store
        $sentences = array();
        $sentences = array_reverse($tmp_sentence);
      }
      else
      {
        $sentences = array();
        if ($arrayIndex == "that")
        {
          $sentences[0] = clean_that($value);
        }
        else
        {
          $sentences[0] = $value;
        }
      }
      //make a space so that [0] is null (in accordance with the AIML array offset)
      array_unshift($sentences, NULL);
      unset ($sentences[0]);
      //push this onto the subarray and then clear [0] element (in accordance with the AIML array offset)
      array_unshift($convoArr[$arrayIndex], $sentences);
      array_unshift($convoArr[$arrayIndex], null);
      unset ($convoArr[$arrayIndex][0]);
    }
    else
    {
      array_unshift($convoArr[$arrayIndex], $value);
      array_unshift($convoArr[$arrayIndex], NULL);
    }
    if ((trim($arrayIndex) == 'star') || (trim($arrayIndex) == 'topic'))
    {
    //keep 5 times as many topics and stars as lines of conversation
      $rememLimit_tmp = $rememLimit;
    }
    else
    {
      $rememLimit_tmp = $remember_up_to;
    }
    for ($i = $rememLimit_tmp + 1; $i <= count($convoArr[$arrayIndex]); $i++)
    {
      if (isset ($convoArr[$arrayIndex][$i]))
      {
        unset ($convoArr[$arrayIndex][$i]);
      }
    }
    unset ($convoArr[$arrayIndex][0]);
    if ($arrayIndex == "topic")
    {
      push_stack($convoArr, $value);
    }
    return $convoArr;
  }

  /**
  * function load_bot_config()
  * A function to get the bot/convo configuration values out of the database
  * @param  array $convoArr - current state of the conversation
  * @return $convoArr (updated)
  **/
  function load_bot_config($convoArr)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Loading config data for the current bot.', 2);
    global $con, $dbn, $default_format, $default_pattern, $default_update_aiml_code, $default_conversation_lines, $default_remember_up_to, $default_debugemail, $default_debug_level, $default_debug_mode, $default_save_state, $error_response;
    //get the values from the db
    $sql = "SELECT * FROM `$dbn`.`bots` WHERE bot_id = '" . $convoArr['conversation']['bot_id'] . "'";
    runDebug(__FILE__, __FUNCTION__, __LINE__, "load bot config SQL: $sql", 3);
    if (($result = mysql_query($sql, $con)) === false) throw new Exception('You have a SQL error on line ' . __LINE__ . ' of ' . __FILE__ . '. Error message is: ' . mysql_error() . ".<br />\nSQL = $sql<br />\n");
    if (mysql_num_rows($result) > 0)
    {
      runDebug(__FILE__, __FUNCTION__, __LINE__, 'Loading bot details from the database.', 4);
      $row = mysql_fetch_assoc($result);
      $convoArr['conversation']['conversation_lines'] = $row['conversation_lines'];
      $convoArr['conversation']['remember_up_to'] = $row['remember_up_to'];
      $convoArr['conversation']['debugemail'] = $row['debugemail'];
      $convoArr['conversation']['debug_level'] = $row['debugshow'];
      $convoArr['conversation']['debugmode'] = $row['debugmode'];
      $convoArr['conversation']['save_state'] = $row['save_state'];
      $convoArr['conversation']['default_aiml_pattern'] = $row['default_aiml_pattern'];
      $convoArr['conversation']['bot_parent_id'] = $row['bot_parent_id'];
      $error_response = $row['error_response'];
    }
    else
    {
      runDebug(__FILE__, __FUNCTION__, __LINE__, 'Unable to load bot details from the database. Loading default values.', 4);
      $convoArr['conversation']['conversation_lines'] = $default_conversation_lines;
      $convoArr['conversation']['remember_up_to'] = $default_remember_up_to;
      $convoArr['conversation']['debugemail'] = $default_debugemail;
      $convoArr['conversation']['debug_level'] = $default_debug_level;
      $convoArr['conversation']['debugmode'] = $default_debug_mode;
      $convoArr['conversation']['save_state'] = $default_save_state;
      $convoArr['conversation']['default_aiml_pattern'] = $default_pattern;
      $convoArr['conversation']['bot_parent_id'] = 0;
    }
    //if return format is not html overide the debug type
    if ($convoArr['conversation']['format'] != "html")
    {
      $convoArr['conversation']['debugmode'] = 1;
    }
    return $convoArr;
  }

  /**
  * function log_conversation(()
  * A function to log the conversation
  * @param  array $convoArr - the current state of the conversation array
  * @return $convoArr (updated)
  **/
  function log_conversation($convoArr)
  {
    //db globals
    global $con, $dbn;
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Saving the conversation to the DB.', 2);
    //clean and set
    $usersay = mysql_real_escape_string($convoArr['aiml']['user_raw']);
    $botsay = mysql_real_escape_string($convoArr['aiml']['parsed_template']);
    $user_id = $convoArr['conversation']['user_id'];
    $convo_id = $convoArr['conversation']['convo_id'];
    $bot_id = $convoArr['conversation']['bot_id'];
    $sql = "INSERT INTO `$dbn`.`conversation_log` (
      `id` ,
      `input` ,
      `response` ,
      `user_id` ,
      `convo_id` ,
      `bot_id` ,
      `timestamp`
    )
    VALUES (
      NULL ,
      '$usersay',
      '$botsay',
      '$user_id',
      '$convo_id',
      '$bot_id',
      CURRENT_TIMESTAMP
    )";
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Saving conservation SQL: $sql", 3);
    db_query($sql, $con);
    return $convoArr;
  }

  /**
  * function log_conversation_state(()
  * A function to log the conversation
  * @param  array $convoArr - the current state of the conversation array
  * @return $convoArr (updated)
  **/
  function log_conversation_state($convoArr)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Logging the state of the conversation.', 2);
    global $con, $dbn, $user_name;
    //get undefined defaults from the db
    runDebug(__FILE__, __FUNCTION__, __LINE__, "logging state", 4);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "user name = $user_name. Stored user name = " . $convoArr['conversation']['user_name'], 4);
    $serialise_convo = mysql_real_escape_string(serialize(reduceConvoArr($convoArr)));
    $user_id = $convoArr['conversation']['user_id'];
    $bot_id = $convoArr['conversation']['bot_id'];
    $sql_addon = (!empty ($user_name)) ? "`user_name` = '" . mysql_real_escape_string($user_name) . "', " : '';
    $sql = "UPDATE `$dbn`.`users`
                SET
                `state` = '$serialise_convo',
                `last_update` = NOW(),
                $sql_addon
                `chatlines` = `chatlines`+1
                WHERE `id` = '$user_id' LIMIT 1";
    runDebug(__FILE__, __FUNCTION__, __LINE__, "updating conversation state SQL: $sql", 3);
    db_query($sql, $con);
    $confirm = mysql_affected_rows($con);
    return $convoArr;
  }

  /**
  * function get_conversation_state(()
  * A function to log the conversation
  * @param  array $convoArr - the current state of the conversation array
  * @return $convoArr (updated)
  **/
  function get_conversation_state($convoArr)
  {
    global $con, $dbn;
    runDebug(__FILE__, __FUNCTION__, __LINE__, "getting state", 4);
    //get converstation state from the db
    $serialise_convo = mysql_real_escape_string(serialize($convoArr));
    $user_id = $convoArr['conversation']['user_id'];
    $sql = "SELECT * FROM `$dbn`.`users` WHERE `id` = '$user_id' LIMIT 1";
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Getting conversation state SQL: $sql", 3);
    $result = db_query($sql, $con);
    if (($result) && (mysql_num_rows($result) > 0))
    {
      $row = mysql_fetch_assoc($result);
      $convoArr = unserialize($row['state']);
      $convoArr['conversation']['user_name'] = $row['user_name'];
      $convoArr['client_properties']['name'] = $row['user_name'];
    }
    return $convoArr;
  }

  /**
  * function check_set_bot(()
  * A function to check and set the bot id
  * @param  array $convoArr - the current state of the conversation array
  * @return $convoArr (updated)
  **/
  function check_set_bot($convoArr)
  {
    global  $form_vars;
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Checking and/or setting the current bot.', 2);
    global $con, $dbn, $default_bot_id, $error_response, $default_format;
    //check to see if bot_id has been passed if not load default
    if ((isset ($form_vars['bot_id'])) && (trim($form_vars['bot_id']) != ""))
    {
      $bot_id = trim($form_vars['bot_id']);
    }
    elseif (isset ($convoArr['conversation']['bot_id']))
    {
      $bot_id = $convoArr['conversation']['bot_id'];
    }
    else
    {
      $bot_id = $default_bot_id;
    }
    //get the values from the db
    $sql = "SELECT * FROM `$dbn`.`bots` WHERE bot_id = '$bot_id' and `bot_active`='1'";
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Checking bot exists SQL: $sql", 3);
    $result = db_query($sql, $con);
    if (($result) && (db_res_count($result) > 0))
    {
      $row = mysql_fetch_assoc($result);
      $bot_name = $row['bot_name'];
      $error_response = $row['error_response'];
      $convoArr['conversation']['bot_name'] = $bot_name;
      $convoArr['conversation']['bot_id'] = $bot_id;
      $convoArr['conversation']['format'] = $row['format'];
      runDebug(__FILE__, __FUNCTION__, __LINE__, "BOT ID: $bot_id", 2);
    }
    else
    {
      $convoArr['conversation']['format'] = $default_format;
      $convoArr['conversation']['bot_id'] = $bot_id;
      runDebug(__FILE__, __FUNCTION__, __LINE__, "ERROR - Cannot find bot id: $bot_id", 1);
    }
    return $convoArr;
  }

  /**
  * function check_set_convo_id(()
  * A function to check and set the convo id
  * @param  array $convoArr - the current state of the conversation array
  * @return $convoArr (updated)
  **/
  function check_set_convo_id($convoArr)
  {
    global $form_vars;
    //check to see if convo_id has been passed if not load default
    if (isset($form_vars['convo_id']))
    {
      $convo_id = $form_vars['convo_id'];
      runDebug(__FILE__, __FUNCTION__, __LINE__, "Obtaining the convo id from form vars. Valie: $convo_id", 4);
    }
    elseif (isset ($convoArr['conversation']['convo_id']))
    {
      $convo_id = $convoArr['conversation']['convo_id'];
      runDebug(__FILE__, __FUNCTION__, __LINE__, "CONVO ID already exists. Value: $convo_id", 2);
    }
    else
    {
      $convo_id = session_id();
      runDebug(__FILE__, __FUNCTION__, __LINE__, "Cannot find CONVO ID. Using default: $convo_id", 1);
    }
    $convoArr['conversation']['convo_id'] = $convo_id;
    return $convoArr;
  }

  /**
  * function check_set_user(()
  * A function to check and set the user's information
  * @param  array $convoArr - the current state of the conversation array
  * @return $convoArr (updated)
  **/
  function check_set_user($convoArr)
  {
    global $con, $dbn, $unknown_user;
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Checking and setting the user info, as needed.', 2);
    //check to see if user_name has been set if not set as default
    $convo_id = (isset ($convoArr['conversation']['convo_id'])) ? $convoArr['conversation']['convo_id'] : session_id();
    if (!isset ($convoArr['conversation']['convo_id']))
      $convoArr['conversation']['convo_id'] = $convo_id;
    $bot_id = $convoArr['conversation']['bot_id'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $convoArr['client_properties']['ip_address'] = $ip;
    $sql = "select `user_name`, `id` from `users` where `session_id` = '$convo_id' limit 1;";
    $result = mysql_query($sql, $con) or $msg = SQL_error(mysql_errno(), __FILE__, __FUNCTION__, __LINE__);
    $numRows = mysql_num_rows($result);
    if ($numRows == 0)
    {
      $convoArr = intisaliseUser($convoArr);
      $user_id = $convoArr['conversation']['user_id'];
    }
    else
    {
      $row = mysql_fetch_assoc($result);
      $user_id = (!empty ($row['id'])) ? $row['id'] : 0;
      $user_name = (!empty ($row['user_name'])) ? $row['user_name'] : $unknown_user;
    }
    $user_name = (!empty ($user_name)) ? $user_name : $unknown_user;
    $convoArr['conversation']['user_name'] = $user_name;
    $convoArr['conversation']['user_id'] = $user_id;
    $convoArr['client_properties']['name'] = $user_name;
    return $convoArr;
  }

  /**
  * function check_set_format(()
  * A function to check and set the conversation return type
  * @param  array $convoArr - the current state of the conversation array
  * @return $convoArr (updated)
  **/
  function check_set_format($convoArr)
  {
    global $default_format, $form_vars;
    $formatsArr = array('html', 'xml', 'json');
    //at thsi point we can overwrite the conversation format.
    if ((isset ($form_vars['format'])) && (trim($form_vars['format']) != ""))
    {
      $format = trim($form_vars['format']);
    }
    else
    {
      $format = $default_format;
    }
    $convoArr['conversation']['format'] = strtolower($format);
    if (!in_array($convoArr['conversation']['format'], $formatsArr))
    {
      $convoArr['debug']['intialisation_error'] = "Incompatible return type: $format";
      runDebug(__FILE__, __FUNCTION__, __LINE__, "ERROR - bad return type: $format", 1);
    }
    else
    {
      runDebug(__FILE__, __FUNCTION__, __LINE__, "Using format: $format", 4);
    }
    return $convoArr;
  }

  function load_that($convoArr)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Loading the THAT array.', 2);
    global $con, $dbn, $default_remember_up_to;
    $remember_up_to = (!empty ($convoArr['conversation']['remember_up_to'])) ? $convoArr['conversation']['remember_up_to'] : $default_remember_up_to;
    $user_id = $convoArr['conversation']['user_id'];
    $bot_id = $convoArr['conversation']['bot_id'];
    $limit = $remember_up_to;
    $sql = "select `response` from `$dbn`.`conversation_log` where `user_id` = $user_id and `bot_id` = $bot_id order by `id` asc limit $limit;"; // desc
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Getting conversation log entries for the current user. SQL:\n$sql", 3);
    $result = db_query($sql, $con);
    if ($result)
    {
      $tmpRows = array();
      $tmpThat = array();
      $puncuation = array(',', '?', ';', '!');
      while ($row = mysql_fetch_assoc($result))
      {
        $tmpRows[] = $row['response'];
      }
      runDebug(__FILE__, __FUNCTION__, __LINE__, 'Finished loading previous responses into the ~THAT~ array.', 4);
      array_reverse($tmpRows);
      foreach ($tmpRows as $row)
      {
        $row = str_replace($puncuation, '.', $row);
        $tmpThat[] = explode('.', $row);
      }
      array_unshift($tmpThat, NULL);
      unset ($tmpThat[0]);
      foreach ($tmpThat as $index => $value)
      {
        $value = implode_recursive(' ', $value, __FILE__, __FUNCTION__, __LINE__);
        $value = clean_that($value);
        $convoArr = push_on_front_convoArr('that', $value, $convoArr);
      }
    }
    return $convoArr;
  }

?>