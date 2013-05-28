<?php

  /***************************************
  * http://www.program-o.com
  * PROGRAM O
  * Version: 2.2.1
  * FILE: chatbot/conversation_start.php
  * AUTHOR: Elizabeth Perreau and Dave Morton
  * DATE: 19 JUNE 2012
  * DETAILS: this file is the landing page for all calls to access the bots
  ***************************************/
 	$encode = 'UTF-8';
 	ini_set('default_charset', $encode);
	mb_internal_encoding($encode);
	mb_http_input($encode);
	mb_http_output($encode);
	mb_detect_order($encode);
	mb_regex_encoding($encode);

  $time_start = microtime(true);
  $last_timestamp = $time_start;
  $thisFile = __FILE__;
  require_once ("../config/global_config.php");
  //load shared files
  include_once (_LIB_PATH_ . "db_functions.php");
  include_once (_LIB_PATH_ . "error_functions.php");
  include_once(_LIB_PATH_ . 'misc_functions.php');

  //leave this first debug call in as it wipes any existing file for this session
  runDebug(__FILE__, __FUNCTION__, __LINE__, "Conversation Starting", 0);
  //load all the chatbot functions
  include_once (_BOTCORE_PATH_ . "aiml" . $path_separator . "load_aimlfunctions.php");
  //load all the user functions
  include_once (_BOTCORE_PATH_ . "conversation" . $path_separator . "load_convofunctions.php");
  //load all the user functions
  include_once (_BOTCORE_PATH_ . "user" . $path_separator . "load_userfunctions.php");
  //load all the user addons
  include_once (_ADDONS_PATH_ . "load_addons.php");
  runDebug(__FILE__, __FUNCTION__, __LINE__, "Loaded all Includes", 4);
  //------------------------------------------------------------------------
  // Error Handler
  //------------------------------------------------------------------------
  // set to the user defined error handler
  set_error_handler("myErrorHandler");
  //open db connection
  $con = db_open();
  //initialise globals
  $convoArr = array();
  $new_convo_id = false;
  $old_convo_id = false;
  $say = '';
  $display = "";

  $form_vars_post = filter_input_array(INPUT_POST);
  $form_vars_get = filter_input_array(INPUT_GET);

  $form_vars = ($form_vars_get !== null and $form_vars_post !== null)
    ? array_merge($form_vars_get, $form_vars_post)
    : ($form_vars_get !== null) ? $form_vars_get
    : $form_vars_post;
  #save_file(_LOG_PATH_ . 'Convo_start_form_vars.txt', print_r($form_vars, true));
  $say = (isset($say) and $say !== '') ? $say : trim($form_vars['say']);
  $session_name = 'PGOv2';
  session_name($session_name);
  session_start();
  #save_file(_LOG_PATH_ . 'session.txt', print_r($_SESSION, true));
  $debug_level = (isset($_SESSION['programo']['conversation']['debug_level'])) ? $_SESSION['programo']['conversation']['debug_level'] : $debug_level;
  if (isset($form_vars['convo_id'])) session_id($form_vars['convo_id']);
  $convo_id = session_id();
  #file_put_contents(_LOG_PATH_ . 'session_id.txt', session_id());
  //if the user has said something
  if (!empty($say))
  {
    // Chect to see if the user is clearing properties
    if (strtolower($say) == 'clear properties')
    {
      runDebug(__FILE__, __FUNCTION__, __LINE__, "Clearing client properties and starting over.", 4);
      $convoArr = read_from_session();
      $_SESSION = array();
      $user_id = (isset($convoArr['conversation']['user_id'])) ? $convoArr['conversation']['user_id'] : -1;
      $sql = "delete from `$dbn`.`client_properties` where `user_id` = $user_id;";
      $result = db_query($sql, $con);
      $numRows = mysql_affected_rows();
      $convoArr['client_properties'] = null;
      $convoArr['conversation'] = null;
      $convoArr['conversation']['user_id'] = $user_id;
      // Get old convo id, to use for later
      $old_convo_id = session_id();
      // Note: This will destroy the session, and not just the session data!
      // Finally, destroy the session.
      runDebug(__FILE__, __FUNCTION__, __LINE__, "Generating new session ID.", 4);
      session_regenerate_id(true);
      $new_convo_id = session_id();
      $params = session_get_cookie_params();
      setcookie($session_name, $new_convo_id, time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
      // Update the users table, and clear out any unused client properties as needed
      $sql = "update `$dbn`.`users` set `session_id` = '$new_convo_id' where `session_id` = '$old_convo_id';";
      runDebug(__FILE__, __FUNCTION__, __LINE__, "Update user - SQL:\n$sql", 3);
      $result = db_query($sql, $con);
      $confirm = mysql_affected_rows($con);
      // Get user id, so that we can clear the client properties
      $sql = "select `id` from `$dbn`.`users` where `session_id` = '$new_convo_id' limit 1;";
      $result = db_query($sql, $con) or trigger_error('Cannot obtain user ID. Error = ' . mysql_error());
      if ($result !== false)
      {
        $row = mysql_fetch_assoc($result);
        $user_id = $row['id'];
        $convoArr['conversation']['user_id'] = $user_id;
        $convoArr['conversation']['convo_id'] = $new_convo_id;
        runDebug(__FILE__, __FUNCTION__, __LINE__, "User ID = $user_id.", 4);
        $sql = "delete from `$dbn`.`client_properties` where `user_id` = $user_id;";
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Clear client properties from the DB - SQL:\n$sql", 4);
      }
      $say = "Hello";
    }
    //add any pre-processing addons


    $say = run_pre_input_addons($convoArr, $say);
    $bot_id = (isset($form_vars['bot_id'])) ? $form_vars['bot_id'] : $bot_id;
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Details:\nUser say: " . $say . "\nConvo id: " . $convo_id . "\nBot id: " . $bot_id . "\nFormat: " . $form_vars['format'], 2);
    //get the stored vars
    $convoArr = read_from_session();
    //now overwrite with the recieved data
    $convoArr = check_set_convo_id($convoArr);
    $convoArr = check_set_bot($convoArr);
    $convoArr = check_set_user($convoArr);
    if (!isset($convoArr['conversation']['user_id']) and isset($user_id)) $convoArr['conversation']['user_id'] = $user_id;
    $convoArr = check_set_format($convoArr);
    $convoArr = load_that($convoArr);
    $convoArr = buildNounList($convoArr);
    $convoArr['time_start'] = $time_start;
    $convoArr = load_bot_config($convoArr);
    //if totallines isn't set then this is new user
    runDebug(__FILE__, __FUNCTION__, __LINE__,"Debug level = $debug_level", 0);
    $debug_level = $convoArr['conversation']['debug_level'];
    runDebug(__FILE__, __FUNCTION__, __LINE__,"Debug level = $debug_level", 0);
    if (!isset ($convoArr['conversation']['totallines']))
    {
    //load the chatbot configuration for a new user
      $convoArr = intialise_convoArray($convoArr);
      //add the bot_id dependant vars
      $convoArr = add_firstturn_conversation_vars($convoArr);
      $convoArr['conversation']['totallines'] = 0;
      $convoArr = get_user_id($convoArr);
    }
    $convoArr['aiml'] = array();
    //add the latest thing the user said
    $convoArr = add_new_conversation_vars($say, $convoArr);
    //parse the aiml
    $convoArr = make_conversation($convoArr);
    $convoArr = run_mid_level_addons($convoArr);
    $convoArr = log_conversation($convoArr);
    #$convoArr = log_conversation_state($convoArr);
    $convoArr = write_to_session($convoArr);
    $convoArr = get_conversation($convoArr);
    $convoArr = run_post_response_useraddons($convoArr);
    //return the values to display
    $display = $convoArr['send_to_user'];
    $time_start = $convoArr['time_start'];
    $time_end = microtime(true);
    $time = round(($time_end - $time_start) * 1000,4);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Conversation Ending. Elapsed time: $time milliseconds.", 0);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "FINAL CONVO ARRAY",4);
    $final_convoArr = $convoArr;
    unset($final_convoArr['nounList']);
    runDebug(__FILE__, __FUNCTION__, __LINE__, print_r($final_convoArr,true), 4);
    unset($convoArr['nounList']);
  }
  else
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Conversation intialised waiting user", 2);
  }
  if ($display == '') $display = $convoArr['send_to_user'];
  runDebug(__FILE__, __FUNCTION__, __LINE__, "Closing Database", 2);
  db_close($con);
    #echo $display;
  $convoArr = handleDebug($convoArr); // Make sure this is the last line in the file, so that all debug entries are captured.
  display_conversation($display, $convoArr['conversation']['format']);
