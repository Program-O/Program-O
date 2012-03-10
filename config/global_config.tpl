<?php
/***************************************
* http://www.program-o.com
* PROGRAM O
* Version: 2.0.1
* FILE: config/global_config.php
* AUTHOR: ELIZABETH PERREAU AND DAVE MORTON
* DATE: MAY 4TH 2011
* DETAILS: this file is the ONLY configuration file for the bot and bot admin
***************************************/
//------------------------------------------------------------------------
// Error reporting
//------------------------------------------------------------------------
  if(!(ini_get('allow_call_time_pass_reference'))){
    ini_set('allow_call_time_pass_reference', 'true');
  }

    $e_all = defined('E_DEPRECATED') ? E_ALL ^ E_DEPRECATED : E_ALL;
  error_reporting($e_all);

//------------------------------------------------------------------------
// Paths only set this manually if the below doesnt work
//------------------------------------------------------------------------

  chdir( dirname ( __FILE__ ) );
  $thisConfigFolder = dirname( realpath( __FILE__ ) ) . DIRECTORY_SEPARATOR;
  $thisConfigParentFolder = preg_replace( '~[/\\\\][^/\\\\]*[/\\\\]$~' , DIRECTORY_SEPARATOR , $thisConfigFolder);

  define("_BASE_DIR_", $thisConfigParentFolder);
  $path_separator = DIRECTORY_SEPARATOR;

//------------------------------------------------------------------------
// Define paths for include files
//------------------------------------------------------------------------

  define("_INC_PATH_",_BASE_DIR_.$path_separator);
  define("_ADMIN_PATH_",_BASE_DIR_."admin".$path_separator);
  define("_GLOBAL_PATH_",_BASE_DIR_."global".$path_separator);
  define("_BOTCORE_PATH_",_BASE_DIR_."chatbot".$path_separator."core".$path_separator);
  define("_AIMLPHP_PATH_",_BASE_DIR_."chatbot".$path_separator."aiml_to_php".$path_separator);
  define("_LIB_PATH_",_BASE_DIR_."library".$path_separator);
  define("_ADDONS_PATH_",_BASE_DIR_."chatbot".$path_separator."addons".$path_separator);
  define("_CONF_PATH_",_BASE_DIR_."config".$path_separator);
  define("_DEBUG_PATH_",_BASE_DIR_."chatbot".$path_separator."debug".$path_separator);
  define("_INSTALL_PATH_",_BASE_DIR_.$path_separator."install".$path_separator);

//------------------------------------------------------------------------
// server name
//------------------------------------------------------------------------
  $server = strtolower($_SERVER['HTTP_HOST']); //leave this to auto detect
  $dev_host = "localhost"; //the name of your dev server
  $alternate_local_server_name = "[alternate_local_server_name]"; // Use if you test on a local network - the network name of the server computer.
//------------------------------------------------------------------------
// parent bot
// the parent bot is used to find aiml matches if no match is found for the current bot
// in the database the bot_parent_id is the id of the bot to use
// if no parent bot is used this is set to zero
// the actual parent bot is set later on in program o there is no need to edit this value
//------------------------------------------------------------------------
  $bot_parent_id = 1;

//------------------------------------------------------------------------
// Select which server we are on
// And set the default bot configuration
// And the database settings
//------------------------------------------------------------------------

  if($server==$dev_host or ((!empty($alternate_local_server_name) and  $server == $alternate_local_server_name)))
  {
    //------------------------------------------------------------------------
    // Development server settings
    //------------------------------------------------------------------------  
      $time_zone_locale = "[lsTZ]"; // a full list can be found at http://uk.php.net/manual/en/timezones.php
        $dbh    = "[local_dbh]";  # dev remote server location
        $dbPort = "[local_dbPort]";    # dev database name/prefix
        $dbn    = "[local_dbn]";    # dev database name/prefix
        $dbu    = "[local_dbu]";       # dev database username
        $dbp    = "[local_dbp]";  # dev database password
    
        //these are the admin DB settings in case you want make the admin a different db user with more privs
        $adm_dbh = "[local_dbh]";
        $adm_dbn = "[local_dbn]";
        $adm_dbu = "[adm_dbu]";
        $adm_dbp = "[adm_dbp]";
        
    //------------------------------------------------------------------------
    // Default bot settings
    //------------------------------------------------------------------------  
    
      //Used to populate the stack when first initialized
      $default_stack_value = "om";
      //Default conversation id will be set to current session
      $default_convo_id = session_id();
      
      //default bot config - this is the default bot most of this will be overwriten by the bot configuration in the db
        $default_bot_id = [default_bot_id];
        $default_format = "[default_format]";
        $default_pattern = "[default_pattern]";
        $default_use_aiml_code = '[default_use_aiml_code]';
        $default_update_aiml_code = '[default_update_aiml_code]';
        $default_conversation_lines = [default_conversation_lines];
        $default_remember_up_to = [default_remember_up_to];
        $default_debugemail = "[default_debugemail]";
        /* 
         * $default_debugshow - The level of messages to show the user
         * 0=none, 
         * 1=error+general, 
         * 2=error+general+sql,
         * 3=everything
         */
        $default_debugshow = [default_debugshow];
    
        /* 
         * $default_debugmode - How to show the debug data
         * 0 = source code view - show debugging in source code
         * 1 = file log - log debugging to a file
         * 2 = page view - display debugging on the webpage
         * 3 = email each conversation line (not recommended)
         */
        $default_debugmode = [default_debugmode];
        $default_save_state = "[default_save_state]";
        $error_response = "[error_response]";

    //------------------------------------------------------------------------
    // Default debug data
    //------------------------------------------------------------------------

        // Report all PHP errors
      $e_all = defined('E_DEPRECATED') ? E_ALL ^ E_DEPRECATED : E_ALL;
      error_reporting($e_all);

        //initially set here but overwriten by bot configuration in the admin panel
        $debuglevel = $default_debugshow;
         
         //for quick debug to override the bot config debug options
         //0 - Do not show anything 
         //1 - will print out to screen immediately
        $quickdebug = 0;
        
        //for quick debug
        //1 = will write debug data to file regardless of the bot config choice
        //it will write it as soon as it becomes available but this this will be finally
        //overwriten once if and when the conversation turn is complete
        //this will hammer the server if left on so dont leave it on... use in emergencies.
        $writetotemp = 0;

      //debug folders where txt files are stored
        $debugfolder = _DEBUG_PATH_;
        $debugfile = $debugfolder.$default_convo_id.".txt";        
  }
  else
  {
    //------------------------------------------------------------------------
    // LIVE server settings
    //------------------------------------------------------------------------  
      $time_zone_locale = "[rsTZ]"; // a full list can be found at http://uk.php.net/manual/en/timezones.php
      $dbh = "[remote_dbh]";
      $dbPort = "[remote_dbPort]";
      $dbn = "[remote_dbn]";
      $dbu = "[remote_dbu]";
      $dbp = "[remote_dbp]";


        //these are the admin DB settings in case you want make the admin a different db user with more privs
        $adm_dbh = "[remote_dbh]";
        $adm_dbn = "[remote_dbn]";
        $adm_dbu = "[adm_dbu]";
        $adm_dbp = "[adm_dbp]";
        
    //------------------------------------------------------------------------
    // Default bot settings
    //------------------------------------------------------------------------  
    
      //Used to populate the stack when first initialized
      $default_stack_value = "om";
      //Default conversation id will be set to current session
      $default_convo_id = session_id();
      
      //default bot config - this is the default bot most of this will be overwriten by the bot configuration in the db
        $default_bot_id = [default_bot_id];
        $default_format = "[default_format]";
        $default_pattern = "[default_pattern]";
        $default_use_aiml_code = '[default_use_aiml_code]';
        $default_update_aiml_code = '[default_update_aiml_code]';
        $default_conversation_lines = [default_conversation_lines];
        $default_remember_up_to = [default_remember_up_to];
        $default_debugemail = "[default_debugemail]";
        /* 
         * $default_debugshow - The level of messages to show the user 
         * 0=none, 
         * 1=error+general, 
         * 2=error+general+sql, 
         * 3=everything
         */
        $default_debugshow = [default_debugshow];
    
        /* 
         * $default_debugmode - How to show the debug data
         * 0 = source code view - show debugging in source code
         * 1 = file log - log debugging to a file
         * 2 = page view - display debugging on the webpage
         * 3 = email each conversation line (not recommended)
         */
        $default_debugmode = [default_debugmode];
        $default_save_state = "[default_save_state]";
        $error_response = "[error_response]";
                
    //------------------------------------------------------------------------
    // Default debug data
    //------------------------------------------------------------------------
      
        // Turn off all error reporting
      error_reporting(0);
        
        
        //initially set here but overwriten by bot configuration in the admin panel
        $debuglevel = $default_debugshow; 
         
         //for quick debug to override the bot config debug options
         //0 - Do not show anything 
         //1 - will print out to screen immediately
        $quickdebug = 0;
        
        //for quick debug
        //1 = will write debug data to file regardless of the bot config choice
        //it will write it as soon as it becomes available but this this will be finally overwriten once if and when the conversation turn is complete
        $writetotemp = 1;
      
      //debug folders where txt files are stored
        $debugfolder = _DEBUG_PATH_;
        $debugfile = $debugfolder.$default_convo_id.".txt";      
  }

//------------------------------------------------------------------------
// Set Misc Data
//------------------------------------------------------------------------
  $botmaster_name = "[botmaster_name]";

//------------------------------------------------------------------------
// Set Program O Website URLs
//------------------------------------------------------------------------

  define('FAQ_URL', 'http://www.program-o.com/ns/faq/');
  define('NEWS_URL', 'http://www.program-o.com/ns/feed/news/'); #This needs to be altered to reflect the correct URL
  define('RSS_URL', 'http://blog.program-o.com/feed/');
  define('SUP_URL', 'http://forum.program-o.com/syndication.php');
  define('BUGS_EMAIL', 'bugs@program-o.com');

//------------------------------------------------------------------------
//
// THERE SHOULD BE NO NEED TO EDIT ANYTHING BELOW THIS LINE
//
//------------------------------------------------------------------------






//------------------------------------------------------------------------
// Set timezone
//------------------------------------------------------------------------

  if(function_exists("date_default_timezone_set") && function_exists("date_default_timezone_get"))
  {
    @date_default_timezone_set(@date_default_timezone_get());
  }
  elseif(function_exists("date_default_timezone_set"))
  {
    @date_default_timezone_set($time_zone_locale);
  }


//------------------------------------------------------------------------
// Load words list and large arrays into session variables
//------------------------------------------------------------------------
  if (empty($_SESSION['commonWords']))
  {
    $_SESSION['commonWords'] = file(_CONF_PATH_.'commonWords.dat', FILE_IGNORE_NEW_LINES);
  }

  $commonwordsArr = $_SESSION['commonWords'];

  if (empty($_SESSION['allowedHtmlTags']))
  {
    $_SESSION['allowedHtmlTags'] = file(_CONF_PATH_.'allowedHtmlTags.dat', FILE_IGNORE_NEW_LINES);
  }
  $allowed_html_tags = $_SESSION['allowedHtmlTags'];


//------------------------------------------------------------------------
// Set Program O globals
// Do not edit
//------------------------------------------------------------------------
  $srai_iterations = '';
  $offset=1;
  $debugArr = array();

//------------------------------------------------------------------------
// Set Script Installation as completed
//------------------------------------------------------------------------

  define('SCRIPT_INSTALLED', true);
?>