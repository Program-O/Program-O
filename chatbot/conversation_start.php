<?php

  /***************************************
  * http://www.program-o.com
  * PROGRAM O
  * Version: 2.1.2
  * FILE: chatbot/conversation_start.php
  * AUTHOR: Elizabeth Perreau and Dave Morton
  * DATE: 19 JUNE 2012
  * DETAILS: this file is the landing page for all calls to access the bots
  ***************************************/

  $time_start = microtime(true);
  $thisFile = __FILE__;
  require_once ("../config/global_config.php");
  //load shared files
  include_once (_LIB_PATH_ . "db_functions.php");
  include_once (_LIB_PATH_ . "error_functions.php");
  //leave this first debug call in as it wipes any existing file for this session
  runDebug(__FILE__, __FUNCTION__, __LINE__, "Conversation Starting", 1);
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
  $display = "";
  switch ($_SERVER['REQUEST_METHOD'])
  {
    case 'POST':
      $form_vars = filter_input_array(INPUT_POST);
      break;
    case 'GET':
      $form_vars = filter_input_array(INPUT_GET);
      break;
    default:
      $say = '';
  }
  $say = (isset($say)) ? $say : trim($form_vars['say']);
  $session_name = 'PGOv2';
  session_name($session_name);
  session_start();
  //if the user has said something
  if (!empty($say))
  {
    // Chect to see if the user is clearing properties
    if (strtolower($say) == 'clear properties')
    {
      runDebug(__FILE__, __FUNCTION__, __LINE__, "Clearing client properties and starting over.", 4);
      $_SESSION = array();
      $convoArr['client_properties'] = null;
      $tmpConvoArr = check_set_user($convoArr);
      $tmpUser_id = $tmpConvoArr['conversation']['user_id'];
      $sql = "delete from `$dbn`.`client_properties` where `user_id` = $tmpUser_id;";
      $result = db_query($sql, $con);
      $numRows = mysql_affected_rows();
/*
      // Get old convo id, to use for later
      $old_convo_id = (!empty($form_vars['convo_id'])) ? $form_vars['convo_id'] : '';
      // If it's desired to kill the session, also delete the session cookie.
      // Note: This will destroy the session, and not just the session data!
      if (ini_get("session.use_cookies"))
      {
        $params = session_get_cookie_params();
        setcookie($session_name, '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
      }
      // Finally, destroy the session.
      runDebug(__FILE__, __FUNCTION__, __LINE__, "Generating new session ID.", 4);
      session_destroy();
      session_start($session_name);
      session_regenerate_id();
      $new_convo_id = session_id();
      setcookie($session_name, $new_convo_id, time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
      $form_vars['convo_id'] = $new_convo_id;
      // Update the users table, and clear out any unused client properties as needed
      $sql = "update `$dbn`.`users` set `session_id` = '$new_convo_id' where `session_id` = '$old_convo_id';";
      runDebug(__FILE__, __FUNCTION__, __LINE__, "Update user - SQL:\n$sql", 4);
      $result = db_query($sql, $con);
      // Get user id, so that we can clear the client properties
      $sql = "select `id` from `$dbn`.`users` where `session_id` = '$new_convo_id' limit 1;";
      $result = db_query($sql, $con) or trigger_error('Cannot obtain user ID. Error = ' . mysql_error());
      if ($result !== false)
      {
        $row = mysql_fetch_assoc($result);
        $user_id = $row['id'];
        $convoArr['conversation']['user_id'] = $user_id;
        runDebug(__FILE__, __FUNCTION__, __LINE__, "User ID = $user_id.", 4);
        $sql = "delete from `$dbn`.`client_properties` where `user_id` = $user_id;";
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Clear client properties from the DB - SQL:\n$sql", 4);
        //$result = db_query($sql, $con);
      }
*/
      $say = "Hello";
    }
    //add any pre-processing addons
    $say = run_pre_input_addons($convoArr, $say);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Details:\nUser say: " . $say . "\nConvo id: " . $form_vars['convo_id'] . "\nBot id: " . $form_vars['bot_id'] . "\nFormat: " . $form_vars['format'], 2);
    //get the stored vars
    $convoArr = read_from_session();
    //now overwrite with the recieved data
    $convoArr = check_set_bot($convoArr);
    $convoArr = check_set_convo_id($convoArr);
    $convoArr = check_set_user($convoArr);
    $convoArr = check_set_format($convoArr);
    $convoArr = load_that($convoArr);
    $convoArr = buildNounList($convoArr);
    $convoArr['time_start'] = $time_start;
    //if totallines = 0 then this is new user
    if (isset ($convoArr['conversation']['totallines']))
    {
    //reset the debug level here
      $debuglevel = $convoArr['conversation']['debugshow'];
    }
    else
    {
    //load the chatbot configuration
      $convoArr = load_bot_config($convoArr);
      //reset the debug level here
      $debuglevel = $convoArr['conversation']['debugshow'];
      //insita
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
    $convoArr = log_conversation($convoArr);
    $convoArr = log_conversation_state($convoArr);
    $convoArr = write_to_session($convoArr);
    $convoArr = get_conversation($convoArr);
    $convoArr = run_post_response_useraddons($convoArr);
    //return the values to display
    $display = $convoArr['send_to_user'];
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Conversation Ending", 4);
    unset($convoArr['nounList']);
    $convoArr = handleDebug($convoArr);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Returning " . $convoArr['conversation']['format'], 4);
    if (strtolower($convoArr['conversation']['format']) == "html")
    {
    //TODO what if it is ajax call
      $time_start = $convoArr['time_start'];
      $time_end = microtime(true);
      $time = $time_end - $time_start;
      runDebug(__FILE__, __FUNCTION__, __LINE__, "Script took $time seconds", 2);
      return $convoArr['send_to_user'];
    }
    else
    {
      echo $convoArr['send_to_user'];
    }
  }
  else
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Conversation intialised waiting user", 2);
  }
  runDebug(__FILE__, __FUNCTION__, __LINE__, "Closing Database", 2);
  db_close($con);
  $time_end = microtime(true);
  $time = $time_end - $time_start;
  $convoArr['star'] = null;
  runDebug(__FILE__, __FUNCTION__, __LINE__, "Script took $time seconds", 2);

?>