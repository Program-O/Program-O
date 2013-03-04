<?php
/***************************************
* http://www.program-o.com
* PROGRAM O
* Version: 2.0.7
* FILE: config/global_config.php
* AUTHOR: ELIZABETH PERREAU AND DAVE MORTON
* DATE: January 16th, 2013
* DETAILS: this file is the ONLY configuration file for the bot and bot admin
***************************************/
    //------------------------------------------------------------------------
    // Paths - only set this manually if the below doesnt work
    //------------------------------------------------------------------------

    chdir( dirname ( __FILE__ ) );
    $thisConfigFolder = dirname( realpath( __FILE__ ) ) . DIRECTORY_SEPARATOR;
    $thisConfigParentFolder = preg_replace( '~[/\\\\][^/\\\\]*[/\\\\]$~' , DIRECTORY_SEPARATOR , $thisConfigFolder);
    $baseURL = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME'];
    $docRoot = $_SERVER['DOCUMENT_ROOT'];

    define('_BASE_DIR_', $thisConfigParentFolder);
    $path_separator = DIRECTORY_SEPARATOR;

    $thisFile = str_replace(_BASE_DIR_, '', $thisFile);
    $thisFile = str_replace($path_separator, '/', $thisFile);
    $baseURL = str_replace($thisFile, '', $baseURL);
    define('_BASE_URL_', $baseURL);

    //------------------------------------------------------------------------
    // Define paths for include files
    //------------------------------------------------------------------------

    define('_INC_PATH_',_BASE_DIR_.$path_separator);
    define('_ADMIN_PATH_',_BASE_DIR_.'admin'.$path_separator);
    define('_ADMIN_URL_',_BASE_URL_.'admin/');
    define('_BOTCORE_PATH_',_BASE_DIR_.'chatbot'.$path_separator.'core'.$path_separator);
    define('_LIB_PATH_',_BASE_DIR_.'library'.$path_separator);
    define('_ADDONS_PATH_',_BASE_DIR_.'chatbot'.$path_separator.'addons'.$path_separator);
    define('_CONF_PATH_',_BASE_DIR_.'config'.$path_separator);
    define('_UPLOAD_PATH_',_CONF_PATH_.'uploads'.$path_separator);
    define('_LOG_PATH_',_BASE_DIR_.'logs'.$path_separator);
    define('_LOG_URL_',_BASE_URL_.'logs/');
    define('_DEBUG_PATH_',_BASE_DIR_.'chatbot'.$path_separator.'debug'.$path_separator);
    define('_INSTALL_PATH_',_BASE_DIR_.$path_separator.'install'.$path_separator);
    define('_INSTALL_URL_',_BASE_URL_.'install/');

    //------------------------------------------------------------------------
    // Define constant for the current version
    //------------------------------------------------------------------------

    define ('VERSION', '2.1.4');

    //------------------------------------------------------------------------
    // Error reporting
    //------------------------------------------------------------------------
    if(!(ini_get('allow_call_time_pass_reference'))){
      ini_set('allow_call_time_pass_reference', 'true');
    }

    $e_all = defined('E_DEPRECATED') ? E_ALL ^ E_DEPRECATED : E_ALL;
    error_reporting($e_all);
    ini_set('log_errors', true);
    ini_set('error_log', _LOG_PATH_ . 'error.log');
    ini_set('html_errors', false);
    ini_set('display_errors', false);

//------------------------------------------------------------------------
// User Configuration Settings
//------------------------------------------------------------------------


    //------------------------------------------------------------------------
    // User Configuration Settings
    //------------------------------------------------------------------------
    // Here, I'm going to start migrating the configuration settings into an
    // array, so that it will be simpler to access configuration settings without
    // having to use a lot of "global $x" crap. the config array will be the first
    // item attached to the conversation array.
    //------------------------------------------------------------------------

    $config = array();

    //------------------------------------------------------------------------
    // parent bot
    // the parent bot is used to find aiml matches if no match is found for the current bot
    // in the database the bot_parent_id is the id of the bot to use
    // if no parent bot is used this is set to zero
    // the actual parent bot is set later on in program o there is no need to edit this value
    //------------------------------------------------------------------------
    $config['bot_parent_id'] = 1;

//------------------------------------------------------------------------
// Set the default bot configuration And the database settings
//------------------------------------------------------------------------

    //------------------------------------------------------------------------
    // DB and time zone settings
    //------------------------------------------------------------------------

    $config['time_zone_locale'] = 'America/Los Angeles'; // a full list can be found at http://uk.php.net/manual/en/timezones.php
    $config['dbh']    = 'localhost';  # dev remote server location
    $config['dbPort'] = '3306';    # dev database name/prefix
    $config['dbn']    = 'pgo_v2_test_copy';    # dev database name/prefix
    $config['dbu']    = 'Dave';       # dev database username
    $config['dbp']    = '411693055';  # dev database password

    //these are the admin DB settings in case you want make the admin a different db user with more privs
    $config['adm_dbu'] = 'Dave';
    $config['adm_dbp'] = '411693055';

    //------------------------------------------------------------------------
    // Default bot settings
    //------------------------------------------------------------------------

    //Used to populate the stack when first initialized
    $config['default_stack_value'] = 'om';

    //default bot config - this is the default bot most of this will be overwriten by the bot configuration in the db
    $config['default_bot_id'] = 1;
    $config['default_format'] = 'html';
    $config['default_pattern'] = 'RANDOM PICKUP LINE';
    $config['default_error_response'] = 'No AIML category found. This is a Default Response.';
    $config['default_conversation_lines'] = '1';
    $config['default_remember_up_to'] = 10;
    $config['default_debugemail'] = 'dmorton@geekcavecreations.com';
    /*
     * $default_debug_level - The level of messages to show the user
     * 0=none,
     * 1=errors only
     * 1=error+general,
     * 2=error+general+sql,
     * 3=everything
     */
    $config['default_debug_level'] = '4';

    /*
     * $default_debug_mode - How to show the debug data
     * 0 = source code view - show debugging in source code
     * 1 = file log - log debugging to a file
     * 2 = page view - display debugging on the webpage
     * 3 = email each conversation line (not recommended)
     */
     $config['default_debug_mode'] = '1';
     $config['default_save_state'] = 'session';
     $config['error_response'] = '[error_response]';
     $config['unknown_user'] = 'Seeker';

    //------------------------------------------------------------------------
    // Default debug data
    //------------------------------------------------------------------------

    //for quick debug to override the bot config debug options
    //0 - Do not show anything
    //1 - will print out to screen immediately
    $config['quickdebug'] = 0;

    //for quick debug
    //1 = will write debug data to file regardless of the bot config choice
    //it will write it as soon as it becomes available but this this will be finally
    //overwriten once if and when the conversation turn is complete
    //this will hammer the server if left on so dont leave it on... use in emergencies.
    $config['writetotemp'] = 0;

    //------------------------------------------------------------------------
    // Set Misc Data
    //------------------------------------------------------------------------

    $config['botmaster_name'] = 'Dave Morton';
    $config['default_charset'] = 'UTF-8';

    //------------------------------------------------------------------------
    // Set Program O Website URLs
    //------------------------------------------------------------------------

    define('FAQ_URL', 'http://www.program-o.com/ns/faq/');
    define('NEWS_URL', 'http://www.program-o.com/ns/feed/news/'); #This needs to be altered to reflect the correct URL
    define('RSS_URL', 'http://blog.program-o.com/feed/');
    define('SUP_URL', 'http://forum.program-o.com/syndication.php');
    define('FORUM_URL', 'http://forum.program-o.com/');
    define('BUGS_EMAIL', 'bugs@program-o.com');

//------------------------------------------------------------------------
//
// THERE SHOULD BE NO NEED TO EDIT ANYTHING BELOW THIS LINE
//
//------------------------------------------------------------------------

    //------------------------------------------------------------------------
    // Set timezone
    //------------------------------------------------------------------------

    if(function_exists('date_default_timezone_set') && function_exists('date_default_timezone_get'))
    {
      @date_default_timezone_set(@date_default_timezone_get());
    }
    elseif(function_exists('date_default_timezone_set'))
    {
      @date_default_timezone_set($config['time_zone_locale']);
    }


    //------------------------------------------------------------------------
    // Load words list and large arrays into session variables
    //------------------------------------------------------------------------

    if (empty($_SESSION['commonWords']))
    {
      $_SESSION['commonWords'] = file(_CONF_PATH_.'commonWords.dat', FILE_IGNORE_NEW_LINES);
    }

    $config['common_words_array'] = $_SESSION['commonWords'];

    //------------------------------------------------------------------------
    // Set Program O globals
    // Do not edit
    //------------------------------------------------------------------------
    $config['srai_iterations'] = 1;
    $config['rememLimit'] = 20;
    $debugArr = array();

    //------------------------------------------------------------------------
    // Addon Configuration - Set as desired
    //------------------------------------------------------------------------

    define('USE_SPELL_CHECKER', false);
    define('PARSE_BBCODE', true);
    define('USE_WORD_CENSOR', true);
    define('USE_CUSTOM_TAGS', true);

    //------------------------------------------------------------------------
    // Set Script Installation as completed
    //------------------------------------------------------------------------

    define('SCRIPT_INSTALLED', true);
?>