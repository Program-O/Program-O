<?php

  /***************************************
  * http://www.program-o.com
  * PROGRAM O
  * Version: 2.0.1
  * FILE: chatbot/conversation_start.php
  * AUTHOR: ELIZABETH PERREAU
  * DATE: MAY 4TH 2011
  * DETAILS: this file is the landing page for all calls to access the bots
  ***************************************/
  session_start();
  $time_start = microtime(true);
  //chdir( dirname ( __FILE__ ) );
  $thisFolder = dirname(realpath(__FILE__)) . DIRECTORY_SEPARATOR;
  $thisParentFolder = preg_replace('~[/\\\\][^/\\\\]*[/\\\\]$~', DIRECTORY_SEPARATOR, $thisFolder);


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
  //------------------------------------------------------------------------
  // Error Handler
  //------------------------------------------------------------------------
  // set to the user defined error handler
  set_error_handler("myErrorHandler");
  //open db connection
  $con = db_open();
  //initialise globals
  $convoArr = array();
  $display = "";
  runDebug(__FILE__, __FUNCTION__, __LINE__, "Loaded all Includes", 3);
  //if the user has said something
  if ((isset ($_REQUEST['say'])) && (trim($_REQUEST['say']) != "")) {
    $say = trim($_REQUEST['say']);
    //add any pre-processing addons
    #$say = run_pre_input_addons($say);
    #die('say = ' . $say);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Details:\nUser say: " . $_REQUEST['say'] . "\nConvo id: " . $_REQUEST['convo_id'] . "\nBot id: " . $_REQUEST['bot_id'] . "\nFormat: " . $_REQUEST['format'], 1);
    if ($say == "clear properties") {
      //read the current convo from the session
      $convoArr = read_from_session();
      //put it into a temp array
      $convoArrTmp = $convoArr['conversation'];
      //wipe the existing array
      $convoArr = array();
      //regen the convo id
      session_regenerate_id();
      $convo_id = session_id();
      $convoArr['conversation']['convo_id'] = $convo_id;
      //TODO SORT THIS
      $convoArr = get_user_id($convoArr);
      $convoArr = check_set_bot($convoArr);
      $convoArr = check_set_convo_id($convoArr);
      $convoArr = check_set_format($convoArr);
      //load the chatbot configuration
      $convoArr = load_bot_config($convoArr);
      //reset the debug level here
      $debuglevel = get_convo_var($convoArr, 'conversation', 'debugshow', '', '');
      $convoArr = intialise_convoArray($convoArr);
      //add the bot_id dependant vars
      $convoArr = add_firstturn_conversation_vars($convoArr);
      $convoArr['conversation']['totallines'] = 0;
      //reset the user id
      $convoArr = get_user_id($convoArr);
      $convoArr = write_to_session($convoArr);
      //$convoArr = write_to_session($convoArr);
      $convoArr['send_to_user'] = get_conversation_to_display($convoArr);
      $convoArr['time_start'] = $time_start;
      runDebug(__FILE__, __FUNCTION__, __LINE__, "Conversation cleared", 1);
    }
    else {
      //get the stored vars
      $convoArr = read_from_session();
      //now overwrite with the recieved data
      $convoArr = check_set_bot($convoArr);
      $convoArr = check_set_convo_id($convoArr);
      $convoArr = check_set_user($convoArr);
      $convoArr = check_set_format($convoArr);
      $convoArr['time_start'] = $time_start;
      //if totallines = 0 then this is new user
      if (isset ($convoArr['conversation']['totallines'])) {
        //reset the debug level here
        $debuglevel = get_convo_var($convoArr, 'conversation', 'debugshow', '', '');
      }
      else {
        //load the chatbot configuration
        $convoArr = load_bot_config($convoArr);
        //reset the debug level here
        $debuglevel = get_convo_var($convoArr, 'conversation', 'debugshow', '', '');
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
    }
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Conversation Ending", 1);
    $convoArr = handleDebug($convoArr);
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Returning " . $convoArr['conversation']['format'], 1);
    if ($convoArr['conversation']['format'] == "html") {
      //TODO what if it is ajax call
      $time_start = $convoArr['time_start'];
      $time_end = microtime(true);
      $time = $time_end - $time_start;
      runDebug(__FILE__, __FUNCTION__, __LINE__, "Script took $time seconds", 1);
      return $convoArr['send_to_user'];
    }
    else {
      echo $convoArr['send_to_user'];
    }
  }
  else {
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Conversation intialised waiting user", 1);
  }
  runDebug(__FILE__, __FUNCTION__, __LINE__, "Closing Database", 1);
  $time_end = microtime(true);
  $time = $time_end - $time_start;
  runDebug(__FILE__, __FUNCTION__, __LINE__, "Script took $time seconds", 1);
?>