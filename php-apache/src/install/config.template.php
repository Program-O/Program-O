<?php
/***************************************
 * http://www.program-o.com
 * PROGRAM O
 * Version: 2.6.11
 * FILE: config/global_config.php
 * AUTHOR: Elizabeth Perreau and Dave Morton
 * DATE: FEB 01 2016
 * DETAILS: this file is the ONLY configuration file for the bot and bot admin
 ***************************************/
//------------------------------------------------------------------------
// Paths - only set this manually if the below doesn't work
//------------------------------------------------------------------------

$path_separator = DIRECTORY_SEPARATOR;
$parentFolder = dirname(__DIR__) . $path_separator;
define('IS_WINDOWS',      ($path_separator === '/') ? false : true);

//------------------------------------------------------------------------
// Define paths for include files
//------------------------------------------------------------------------

define('_BASE_PATH_', $parentFolder);
define('_ADMIN_PATH_',    _BASE_PATH_  . "admin{$path_separator}");
define('_BOTCORE_PATH_',  _BASE_PATH_  . "chatbot{$path_separator}core{$path_separator}");
define('_LIB_PATH_',      _BASE_PATH_  . "library{$path_separator}");
define('_ADDONS_PATH_',   _BASE_PATH_  . "chatbot{$path_separator}addons{$path_separator}");
define('_CONF_PATH_',     _BASE_PATH_  . "config{$path_separator}");
define('_LOG_PATH_',      _BASE_PATH_  . "logs{$path_separator}");
define('_DEBUG_PATH_',    _BASE_PATH_  . "chatbot{$path_separator}debug{$path_separator}");
define('_INSTALL_PATH_',  _BASE_PATH_  . "install{$path_separator}");
define('_UPLOAD_PATH_',   _ADMIN_PATH_ . "uploads{$path_separator}");
define('_DOWNLOAD_PATH_', _ADMIN_PATH_ . "downloads{$path_separator}");
define('_SESSION_PATH_',  _ADMIN_PATH_ . "[session_dir]{$path_separator}");

//------------------------------------------------------------------------
// Define URL paths
//------------------------------------------------------------------------

$doc_root = $_SERVER['DOCUMENT_ROOT'];
$bu = str_replace($path_separator, '/', _BASE_PATH_);
$baseURL = str_replace($doc_root, '', $bu);
define ('_BASE_URL_', $baseURL);

define('_ADMIN_URL_',   _BASE_URL_ . 'admin/');
define('_LIB_URL_',     _BASE_URL_ . 'library/');
define('_LOG_URL_',     _BASE_URL_ . 'logs/');
define('_DEBUG_URL_',   _BASE_URL_ . 'chatbot/debug/');
define('_INSTALL_URL_', _BASE_URL_ . 'install/');

//------------------------------------------------------------------------
// Define constants for the current version of Program O, and for the OS name and version
//------------------------------------------------------------------------

define('VERSION', trim(file_get_contents(_BASE_PATH_ . 'version.txt'))); # Program O version
$os = php_uname('s');
$osv = php_uname('v');
header("x-server-os: $os - $osv");

//------------------------------------------------------------------------
// Error reporting
//------------------------------------------------------------------------

if (!(ini_get('allow_call_time_pass_reference')))
{
    ini_set('allow_call_time_pass_reference', 'true');
}

$e_all = defined('E_DEPRECATED') ? E_ALL & ~E_DEPRECATED : E_ALL;
error_reporting($e_all);
ini_set('log_errors', true);
ini_set('error_log', _LOG_PATH_ . 'error.log');
ini_set('html_errors', false);
ini_set('display_errors', false);

//------------------------------------------------------------------------
// Time zone settings
//------------------------------------------------------------------------
$time_zone_locale = '[time_zone_locale]'; // a full list can be found at http://uk.php.net/manual/en/timezones.php

//------------------------------------------------------------------------
// DB settings
//------------------------------------------------------------------------
# remote server location
$dbh     = '[dbh]';
# database port
$dbPort  = '[dbPort]';
# database name
$dbn     = '[dbn]';
# database username
$dbu     = '[dbu]';
# database password
$dbp     = '[dbp]';
//Login credentials for the admin page
$adm_dbu    = '[adm_dbu]';
$adm_dbp    = '[adm_dbp]';

//------------------------------------------------------------------------
// Default bot settings
// parent bot
// the parent bot is used to find aiml matches if no match is found for the current bot
// in the database the bot_parent_id is the id of the bot to use
// if no parent bot is used this is set to zero
// the actual parent bot is set later on in program o there is no need to edit this value
//------------------------------------------------------------------------
$bot_parent_id = 1;

//------------------------------------------------------------------------
// Define some bot option constants
//------------------------------------------------------------------------

/*
     It has been suggested that the "winning" response be the first category found with the highest score,
     rather than a random selection from all high scoring responses. It was also suggested that the most
     recent (e.g. the last) response should be chosen, with newer AIML categories superseding older ones.
     At some point this will be an option that will be placed in the admin pages on a per-bot basis, but
     for now it's just a random pick. That said, however, I'm going to start adding code for the other
     two options now. Feel free to change the value of $which_response to one of the three constants
     defined here.
*/

define('BOT_USE_RANDOM_RESPONSE', 0);
define('BOT_USE_FIRST_RESPONSE', 1);
define('BOT_USE_LAST_RESPONSE', 2);

$which_response = BOT_USE_RANDOM_RESPONSE;


//Used to populate the stack when first initialized
$stack_value = 'om';

//Default conversation id will be set to current session
$convo_id = session_id();

//default bot config - this is the default bot. most of this will be overwriten by the bot configuration in the db
$bot_id = 1;
$format = '[format]';
$pattern = 'RANDOM PICKUP LINE';
$error_response = 'No AIML category found. This is a Default Response.';
$conversation_lines = '1';
$remember_up_to = 10;
$debugemail = '[debugemail]';

/*
 * $debug_level - The level of messages to show the user
 * 0 = none,
 * 1 = errors only
 * 2 = error + general,
 * 3 = error + general + sql,
 * 4 = everything
 */
$debug_level = '[debug_level]';

/*
 * $debug_mode - How to show the debug data
 * 0 = source code view - show debugging in source code
 * 1 = file log - log debugging to a file
 * 2 = page view - display debugging on the webpage
 * 3 = email each conversation line (not recommended)
 */
$debug_mode = '[debug_mode]';

/*
 * ERROR_DEBUGGING
 * Define a constant for the error handler to determine
 * whether a context file is generated for each error
 * WARNING! Setting this constant to true will generate a
 * potentially large number of BIG files.
 */
define('ERROR_DEBUGGING', false);

$save_state = '[save_state]';
$pattern = '[bot_default_aiml_pattern]';
$error_response = '[error_response]';
$unknown_user = 'Seeker';

//------------------------------------------------------------------------
// Default debug data
//------------------------------------------------------------------------

/*
 * for quick debug to override the bot config debug options
 * 0 - Do not show anything
 * 1 - will print out to screen immediately
 */
$quickdebug = 0;

/*
 * for quick debug
 * 1 = will write debug data to file regardless of the bot config choice
 * it will write it as soon as it becomes available but this this will be finally
 * overwriten once if and when the conversation turn is complete
 * this will hammer the server if left on so dont leave it on... use in emergencies.
 */
$writetotemp = 0;

//------------------------------------------------------------------------
// Set Misc Data
//------------------------------------------------------------------------
$botmaster_name = '[botmaster_name]';
$charset = 'UTF-8';

//------------------------------------------------------------------------
//
// THERE SHOULD BE NO NEED TO EDIT ANYTHING BELOW THIS LINE
//
//------------------------------------------------------------------------

//------------------------------------------------------------------------
// Session handling - Currently disabled until I can find a better way to implement this
//------------------------------------------------------------------------
/*
  $session_lifetime = 86400; // 24 hours, expressed in seconds
  $server_name = filter_input(INPUT_SERVER,'SERVER_NAME', FILTER_SANITIZE_STRING);
  $session_cookie_path = str_replace("http://$server_name", '', _ADMIN_URL_);
  session_set_cookie_params($session_lifetime, $session_cookie_path);
  if (is_writable(_SESSION_PATH_)) ini_set('session.save_path', _SESSION_PATH_);
*/

//------------------------------------------------------------------------
// Addon Configuration - Set as desired
//------------------------------------------------------------------------
define('USE_SPELL_CHECKER', true);
define('PARSE_BBCODE', true);
define('USE_WORD_CENSOR', true);
define('USE_CUSTOM_TAGS', true);

//------------------------------------------------------------------------
// Set timezone
//------------------------------------------------------------------------
if (function_exists('date_default_timezone_set') && function_exists('date_default_timezone_get'))
{
    @date_default_timezone_set(@date_default_timezone_get());
}
elseif (function_exists('date_default_timezone_set'))
{
    @date_default_timezone_set($time_zone_locale);
}

//------------------------------------------------------------------------
// Load words list and large arrays into session variables
//------------------------------------------------------------------------
if (empty($_SESSION['commonWords']))
{
    $_SESSION['commonWords'] = file(_CONF_PATH_ . 'commonWords.dat', FILE_IGNORE_NEW_LINES);
}
$common_words_array = $_SESSION['commonWords'];

//------------------------------------------------------------------------
// Set Program O globals
//------------------------------------------------------------------------
$srai_iterations = 1;
$rememLimit = 20;
$debugArr = array();

//------------------------------------------------------------------------
// Configure mbstring parameters
//------------------------------------------------------------------------
define('IS_MB_ENABLED', (extension_loaded('mbstring')) ? true : false);

if (IS_MB_ENABLED)
{
    mb_internal_encoding($charset);
    mb_http_input($charset);
    mb_http_output($charset);
    mb_detect_order($charset);
    mb_regex_encoding($charset);
}

//------------------------------------------------------------------------
// Set memory trigger for large data imports
//------------------------------------------------------------------------
$sys_mem_limit = ini_get('memory_limit');
$quantifier = preg_match('~\D~', $sys_mem_limit,$matches);
$quantifier = strtolower($matches[0]);
$mem_limit = str_replace($matches[0], '', $sys_mem_limit);
switch ($quantifier)
{
    /** @noinspection PhpMissingBreakStatementInspection */
    case 'g':
        $mem_limit *= 1024;
    /** @noinspection PhpMissingBreakStatementInspection */
    case 'm':
        $mem_limit *= 1024;
    /** @noinspection PhpMissingBreakStatementInspection */
    case 'k':
        $mem_limit *= 1024;
    default:
        $mem_limit *= 0.5; // half of the memory limit set by PHP
}

define('MEM_TRIGGER', $mem_limit);

//------------------------------------------------------------------------
// Set Program O Website URLs
//------------------------------------------------------------------------
define('DOCS_URL', 'https://github.com/Program-O/Program-O/wiki');
define('FORUM_URL', 'http://forum.program-o.com/');
define('BUGS_EMAIL', 'bugs@program-o.com');

//------------------------------------------------------------------------
// Set Security headers
//------------------------------------------------------------------------
header("X-Frame-Options:SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");

//------------------------------------------------------------------------
// Set Script Installation as completed
//------------------------------------------------------------------------
define('SCRIPT_INSTALLED', true);
