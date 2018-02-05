<?php
/***************************************
* http://www.program-o.com
* PROGRAM O
* Version: 2.6.8
* FILE: install_programo.php
* AUTHOR: Elizabeth Perreau and Dave Morton
* DATE: FEB 01 2016
* DETAILS: Program O's Automatic install script
***************************************/

session_name('PGO_install');
session_start();
require_once ('install_config.php');
require_once (_LIB_PATH_ . 'template.class.php');
require_once (_LIB_PATH_ . 'misc_functions.php');
require_once (_LIB_PATH_ . 'error_functions.php');

ini_set("display_errors", 0);
ini_set("log_errors", true);
ini_set("error_log", _LOG_PATH_ . "install.error.log");

define('PHP_SELF', $_SERVER['SCRIPT_NAME']); # This is more secure than $_SERVER['PHP_SELF'], and returns more or less the same thing
$input_vars = clean_inputs();
$dbConn = null;
$dbh    = 'localhost';
$dbn    = null;
$dbu    = null;
$dbp    = null;
$dbPort = 3306;



# Test for required version and extensions
$myPHP_Version = phpversion();
//$myPHP_Version = '5.2.9'; # debugging/testing - must be commented out for functionality.
$pdoSupport = (class_exists('PDO'));
$php_min_version = '5.3.0';
$version_compare = version_compare($myPHP_Version, $php_min_version);

$errorMessage = (!empty ($_SESSION['errorMessage'])) ? $_SESSION['errorMessage'] : '';

$pdoExtensionsArray = array(
    'PDO_CUBRID',
    'PDO_DBLIB',
    'PDO_FIREBIRD',
    'PDO_IBM',
    'PDO_INFORMIX',
    'PDO_MYSQL',
    'PDO_SQLSRV',
    'PDO_OCI',
    'PDO_ODBC',
    'PDO_PGSQL',
    'PDO_SQLITE',
    'PDO_4D'
);
$recommendedExtensionsArray = array(
    'curl',
    'zip',
    'mbstring',
);


$template = new Template('install.tpl.htm');

// check/set/create the sessions folder
$dirArray = glob(_ADMIN_PATH_ . "ses_*", GLOB_ONLYDIR);
$session_dir = (empty($dirArray)) ? create_session_dirname() : basename($dirArray[0]);
$dupPS = "{$path_separator}{$path_separator}";
$session_dir = str_replace($dupPS, $path_separator, $session_dir); // remove double path separators when necessary
$session_dir = rtrim($session_dir, PATH_SEPARATOR);
$full_session_path = _ADMIN_PATH_ . $session_dir;
clearstatcache();
$sd_exists = file_exists($full_session_path);
if (!$sd_exists)
{
    $md = mkdir($full_session_path, 0755);
    if (!$md)
    {
        $session_path_error = "The generated session path({$full_session_path}) could not be created. Default session path will be used instead.";
        error_log($session_path_error, 3, _LOG_PATH_ . 'install.error.log');
        $full_session_path = session_save_path();
        $errorMessage .= $session_path_error;
    }
}

define('_SESSION_PATH_', $full_session_path);

$myHost = $_SERVER['SERVER_NAME'];
chdir(dirname(realpath(__FILE__)));
$page = (isset ($input_vars['page'])) ? $input_vars['page'] : 0;
$action = (isset ($input_vars['action'])) ? $input_vars['action'] : '';
$message = '';

if (!empty ($action)) {
    $message = $action($page);
}

$content = $template->getSection('Header');
$content .= $template->getSection('Container');
$content .= $template->getSection('Footer');
$content .= $template->getSection("jQuery$page");
$notes = $template->getSection(ucwords("Page $page Notes"));
$submitButton = $template->getSection('SubmitButton');
switch ((int) $page)
{
    case 0:
        $main = $template->getSection('Checklist');
        $writeCheckArray = array('config' => _CONF_PATH_, 'debug' => _DEBUG_PATH_, 'logs' => _LOG_PATH_, 'session' => _SESSION_PATH_);
        $writeCheckKeys  = array('config' => '[cfw]', 'debug' => '[dfw]', 'logs' => '[lfw]', 'session' => '[sfw]');
        $writeCheckRepl  = array();
        $errFlag = false;
        $writableTemplate = '<span class="ext_[tf] floatRight">[pf]</span>';
        $writeErrorText = '';
        foreach ($writeCheckArray as $key => $folder)
        {
            $testFile = "{$folder}_test.txt";
            $searchTag = $writeCheckKeys[$key];
            $curSpan = $writableTemplate;
            $permissions = fileperms($folder);
            $txtPerms = showPerms($permissions);
            $writeErrorTemplate = "            <li style=\"color: black\">The {$key} folder ({$folder}) is not writable.<span class=\"floatRight\"> Permissions: {$txtPerms}</span></li>";
            $writeFlag = (is_writable($folder)) ? true : (chmod($folder, 0755));
            //$writeFlag = ($key !== 'debug') ? true : false; // Debugging/testing code. Comment out unless testing or debugging
            $writeErrorText .= ($writeFlag) ? '' : $writeErrorTemplate;
            $errFlag = (!$writeFlag) ? true: $errFlag;
            $curSpan = str_replace('[perms]', $txtPerms, $curSpan);
            $curSpan = str_replace('[tf]', ($writeFlag) ? 'true' : 'false', $curSpan);
            $curSpan = str_replace('[pf]', ($writeFlag) ? 'Pass' : 'Fail', $curSpan);
            $main = str_replace($searchTag, $curSpan, $main);
        }

        $additionalInfo = <<<endInfo
        <div class="m0a w50">
            <p>
                The following folders had permissions problems that PHP could not fix. This is usually an 'ownership' issue, and
                most often occurs with Linux-based systems. Please check owner and group settings for the following directories:
                <ul class="tal">
$writeErrorText
                </ul>
                <hr/>
                Owner and group for these folders should be the same as those of your web server software. If they are not, then you
                need to change that. If you have trouble with this, or have questions, please report the issue on
                <a href="https://github.com/Program-O/Program-O/issues">our GitHub page</a>.
            </p>
        </div>
endInfo;

        $reqs_not_met = '';
        if ($errFlag) {
            $reqs_not_met .= $additionalInfo;
        }

        $pvpf = ($version_compare >= 0) ? 'true' : 'false';
        $liTemplate = '                            <li class="[oe]">PDO [ext] extension enabled?: <span class="ext_[tf] floatRight">[pf]</span></li>' . PHP_EOL;
        $pdo_reqs = '';
        $oddEven = 0;
        foreach ($pdoExtensionsArray as $ext)
        {
            $oeClass = ($oddEven % 2 === 0) ? 'odd' : 'even';
            $tf = (extension_loaded($ext)) ? 'true' : 'false';
            $pf = ($tf === 'true') ? 'Pass' : 'Fail';
            $curLi = $liTemplate;
            $curLi = str_replace('[ext]', $ext, $curLi);
            $curLi = str_replace('[oe]', $oeClass, $curLi);
            $curLi = str_replace('[tf]', $tf, $curLi);
            $curLi = str_replace('[pf]', $pf, $curLi);
            if ($tf === 'false') $curLi = '';
            $pdo_reqs .= $curLi;
            if ($tf !== 'false') $oddEven++;
        }
        if (empty($pdo_reqs)) # || true # Again, debugging/testing code, to be commented out for actual use.
        {
            $pdo_reqs = $liTemplate;
            $pdo_reqs = str_replace('[oe]', 'even', $pdo_reqs);
            $pdo_reqs = str_replace('[ext]', '', $pdo_reqs);
            $pdo_reqs = str_replace('[tf]', 'false', $pdo_reqs);
            $pdo_reqs = str_replace('[pf]', 'Fail', $pdo_reqs);
            $reqs_not_met .= 'There are no PDO extensions available, so the install process cannot continue.<br>';
        }
        elseif ($pvpf == 'false')
        {
            $reqs_not_met .= "Your PHP version ({$myPHP_Version}) is older than the minimum required version of {$php_min_version}, so the install process cannot continue.<br>";
        }
        $reqs_met = empty($reqs_not_met);
        $main = str_replace('[pdo_reqs]', rtrim($pdo_reqs), $main);
        $rec_exts = '';
        $oddEven = 0;
        foreach ($recommendedExtensionsArray as $ext)
        {
            $oeClass = ($oddEven % 2 === 0) ? 'odd' : 'even';
            $curLi = $liTemplate;
            $tf = (extension_loaded($ext)) ? 'true' : 'false';
            $pf = ($tf === 'true') ? 'Pass' : 'Fail';
            $curLi = str_replace('[ext]', $ext, $curLi);
            $curLi = str_replace('[oe]', $oeClass, $curLi);
            $curLi = str_replace('[tf]', $tf, $curLi);
            $curLi = str_replace('[pf]', $pf, $curLi);
            $rec_exts .= $curLi;
            $oddEven++;
        }
        $main = str_replace('[rec_exts]', rtrim($rec_exts), $main);
        $main = str_replace('[pgo_version]', VERSION, $main);
        $main = str_replace('[pvpf]', $pvpf, $main);
        $main = str_replace('[version]', $myPHP_Version, $main);
        $continueLink = ($reqs_met) ? $template->getSection('Page0ContinueForm') :'<div class="center bold red">' .  $reqs_not_met . 'Please correct the items above in order to continue.</div>' . PHP_EOL;
        $main .= $continueLink;
        $main = str_replace('[blank]', '', $main);
        break;
    case 1:
        $main = $template->getSection('InstallForm');
        break;
    default: $main = $message;
}
$tmpSearchArray = array();
$content .= "\n    </body>\n</html>";

$content = str_replace('[mainPanel]', $main, $content);
$content = str_replace('[http_host]', $myHost, $content);
$content = str_replace('[bot_default_aiml_pattern]', $pattern, $content);
$content = str_replace('[error_response]', $error_response, $content);
$content = str_replace('[notes]', $notes, $content);
$content = str_replace('[PHP_SELF]', PHP_SELF, $content);
$content = str_replace('[errorMessage]', $errorMessage, $content);
$content = str_replace('[cr6]', "\n ", $content);
$content = str_replace('[cr4]', "\n ", $content);
$content = str_replace("\r\n", "\n", $content);
$content = str_replace("\n\n", "\n", $content);
$content = str_replace('[admin_url]', _ADMIN_URL_, $content);
$content = str_replace('[mainPanel]', $main, $content);
$content = str_replace('[blank]', '', $content);

exit($content);

/**
 * Function Save
 *
 * @return string
 */
function Save()
{
    global $template, $error_response, $session_dir, $dbConn, $dbh, $dbu, $dbp, $dbn, $dbPort;
    $errorMessage = '';

    // Do we want to start with a fresh, empty database?
    // initialize some variables and set some defaults
    $tagSearch = array();
    $varReplace = array();
    $conversation_lines = 1;
    $remember_up_to = 10;
    $error_response = 'No AIML category found. This is a Default Response.';
    $_SESSION['errorMessage'] = '';


    $configContents = file_get_contents(_INSTALL_PATH_ . 'config.template.php');
    $configContents = str_replace('[session_dir]', $session_dir, $configContents);
    clearstatcache();

    // First off, create the sessions folder and set permissions if it doesn't exist
    if (!file_exists(_SESSION_PATH_))
    {
        mkdir(_SESSION_PATH_, 0755);

        // Place an empty index file in the sessions folder to prevent direct access to the folder from a web browser
        file_put_contents(_SESSION_PATH_ . 'index.html', '');
    }

    // Write the config file from all of the posted form values

    // Get the posted values, sanitize them and put them into an array
    $myPostVars = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

    // Sort the array - not strictly necessary, but we're doing it anyway
    ksort($myPostVars);

    // Check to see if the user wants a 'fresh start'
    $clearDB = (isset($myPostVars['clearDB'])) ? true : false;

    // Create the SEARCH and REPLACE arrays
    foreach ($myPostVars as $key => $value)
    {
        $tagSearch[] = "[$key]";
        $varReplace[] = $value;
    }

    // Replace all [placeholder] tags with the posted values
    $configContents = str_replace($tagSearch, $varReplace, $configContents);

    // Write the new config file
    $saveFile = file_put_contents(_CONF_PATH_ . 'global_config.php', $configContents);

    // Now, update the data to the database, starting with making sure the tables are installed
    $dbh    = $myPostVars['dbh'];
    $dbn    = $myPostVars['dbn'];
    $dbu    = $myPostVars['dbu'];
    $dbp    = $myPostVars['dbp'];
    $dbPort = $myPostVars['dbPort'];

    // Open the database to begin storing stuff
    $dbConn = db_open();

    // Check to see if the database is empty, or if the user checked the "clear DB" option
    $row = db_fetch('show tables');
    if (empty ($row) || true === $clearDB)
    {
        $sqlArray = file('new.sql', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($sqlArray as $sql)
        {
            try {
                $insertSuccess = db_write($sql,null, false, __FILE__, __FUNCTION__, __LINE__, false);
                if (false === $insertSuccess){
                    throw new Exception('SQL operation failed!');
                }
            }
            catch(Exception $e)
            {
                $words = explode(' ', $sql);
                switch (strtoupper($words[0]))
                {
                    case 'DROP':
                        $table = trim($words[4], '`;');
                        break;
                    case 'CREATE':
                        $table = trim($words[5], '`');
                        break;
                    case 'ALTER':
                        $table = trim($words[2], '`');
                        break;
                    default:
                        $words[0] .= ' data into';
                        $table = trim($words[2], '`');
                }
                $errMsg = "Error while attempting to {$words[0]} the {$table} table. SQL:\n$sql\nError Code: {$e->getCode()}\nError message: {$e->getMessage()}\n-----------------------------------------------\n";
                error_log($errMsg, 3, _LOG_PATH_ . 'install.sql.error.log');
            }

        }
    }
    else
    {
        // Let's make sure that the srai lookup table exists
        try {
            /** @noinspection SqlNoDataSourceInspection */
            $sql = 'SELECT bot_id FROM srai_lookup;';
            $result = db_fetchAll($sql);
        }
        catch(Exception $e) {
            try {
                /** @noinspection SqlDialectInspection */
                /** @noinspection SqlNoDataSourceInspection */
                $sql = "DROP TABLE IF EXISTS `srai_lookup`; CREATE TABLE IF NOT EXISTS `srai_lookup` (`id` int(11) NOT NULL AUTO_INCREMENT, `bot_id` int(11) NOT NULL, `pattern` text NOT NULL, `template_id` int(11) NOT NULL, PRIMARY KEY (`id`), KEY `pattern` (`pattern`(64)) COMMENT 'Search against this for performance boost') ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Contains previously stored SRAI calls' AUTO_INCREMENT=1;";
                $affectedRows = db_write($sql,null, false, __FILE__, __FUNCTION__, __LINE__, false);
            }
            catch(Exception $e) {
              $errorMessage .= 'Could not add SRAI lookup table! Error is: ' . $e->getMessage();
            }
        }
    }

    /** @noinspection SqlDialectInspection */
    /** @noinspection SqlNoDataSourceInspection */
    $sql = 'SELECT `bot_id` FROM `bots`;';
    $result = db_fetchAll($sql);

    $bot_id               = 1;
    $bot_name             = $myPostVars['bot_name'];
    $bot_desc             = $myPostVars['bot_desc'];
    $bot_active           = $myPostVars['bot_active'];
    $bot_parent_id        = 1;
    $format               = $myPostVars['format'];
    $save_state           = $myPostVars['save_state'];
    $debugemail           = $myPostVars['debugemail'];
    $debugshow            = $myPostVars['debug_level'];
    $debugmode            = $myPostVars['debug_mode'];
    $error_response       = $myPostVars['error_response'];
    $default_aiml_pattern = 'RANDOM PICKUP LINE';

    $params = array(
        ':bot_id'               => $bot_id,
        ':bot_name'             => $bot_name,
        ':bot_desc'             => $bot_desc,
        ':bot_active'           => $bot_active,
        ':bot_parent_id'        => $bot_parent_id,
        ':format'               => $format,
        ':save_state'           => $save_state,
        ':conversation_lines'   => $conversation_lines,
        ':remember_up_to'       => $remember_up_to,
        ':debugemail'           => $debugemail,
        ':debugshow'            => $debugshow,
        ':debugmode'            => $debugmode,
        ':error_response'       => $error_response,
        ':default_aiml_pattern' => $default_aiml_pattern,
    );

    if (count($result) == 0)
    {
        /** @noinspection SqlDialectInspection */
        /** @noinspection SqlNoDataSourceInspection */
        $sql = 'insert ignore into `bots` (`bot_id`, `bot_name`, `bot_desc`, `bot_active`, `bot_parent_id`, `format`, `save_state`, `conversation_lines`, `remember_up_to`, `debugemail`, `debugshow`, `debugmode`, `error_response`, `default_aiml_pattern`)
    values ( :bot_id, :bot_name, :bot_desc, :bot_active, :bot_parent_id, :format, :save_state, :conversation_lines, :remember_up_to, :debugemail, :debugshow, :debugmode, :error_response, :default_aiml_pattern);';

    }
    else
    {
        /** @noinspection SqlDialectInspection */
        /** @noinspection SqlNoDataSourceInspection */
        $sql = 'update `bots` set
    `bot_name`             = :bot_name,
    `bot_desc`             = :bot_desc,
    `bot_active`           = :bot_active,
    `bot_parent_id`        = :bot_parent_id,
    `format`               = :format,
    `save_state`           = :save_state,
    `conversation_lines`   = :conversation_lines,
    `remember_up_to`       = :remember_up_to,
    `debugemail`           = :debugemail,
    `debugshow`            = :debugshow,
    `debugmode`            = :debugmode,
    `error_response`       = :error_response,
    `default_aiml_pattern` = :default_aiml_pattern
where `bot_id` = :bot_id;
    ';
    }

    try
    {
        $debugSQL = db_parseSQL($sql, $params);
        $affectedRows = db_write($sql, $params, false, __FILE__, __FUNCTION__, __LINE__, false);
        $errorMessage .= ($affectedRows > 0) ? '' : ' Could not create new bot!<br>';
        $errorMessage .= ($affectedRows > 0) ? '' : "SQL: {$debugSQL}";
    }
    catch(Exception $e) {
        $errorMessage .= $e->getMessage();
    }
    $cur_ip = $_SERVER['REMOTE_ADDR'];
    $encrypted_adm_dbp = md5($myPostVars['adm_dbp']);
    $adm_dbu = $myPostVars['adm_dbu'];

    /** @noinspection SqlDialectInspection */
    /** @noinspection SqlNoDataSourceInspection */
    $sql = "SELECT id FROM `myprogramo` WHERE `user_name` = '$adm_dbu' AND `password` = '$encrypted_adm_dbp';";
    $result = db_fetchAll($sql);

    if (count($result) == 0)
    {
        /** @noinspection SqlDialectInspection */
        /** @noinspection SqlNoDataSourceInspection */
        $sql = "INSERT ignore INTO `myprogramo` (`id`, `user_name`, `password`, `last_ip`) VALUES(null, :adm_dbu, :encrypted_adm_dbp, :cur_ip);";
        $params = array(
            ':adm_dbu' => $adm_dbu,
            ':encrypted_adm_dbp' => $encrypted_adm_dbp,
            ':cur_ip' => $cur_ip,
        );

        try {
            $affectedRows = db_write($sql, $params, false, __FILE__, __FUNCTION__, __LINE__, false);
            $errorMessage .= ($affectedRows > 0) ? '' : ' Could not create new Admin!';
        }
        catch(Exception $e) {
            $errorMessage .= $e->getMessage();
        }
    }

    if (empty($errorMessage)) {
        $out = $template->getSection('InstallComplete');
    }
    else {
        $out = $template->getSection('InstallError');
    }

    return $out . $errorMessage;
}

/*
 * function create_session_dirname
 * Creates a cryptographically secure, random folder name for storing session files
 * return (string) $out
 */

function create_session_dirname()
{
    global $path_separator;
    $randBytes = openssl_random_pseudo_bytes(12);
    $suffix = bin2hex($randBytes);
    $out = "ses_{$suffix}{$path_separator}";
    return $out;
}

function showPerms ($permissions)
{
    switch ($permissions & 0xF000) {
        case 0xC000: // socket
            $info = 's';
            break;
        case 0xA000: // symbolic link
            $info = 'l';
            break;
        case 0x8000: // regular
            $info = 'r';
            break;
        case 0x6000: // block special
            $info = 'b';
            break;
        case 0x4000: // directory
            $info = 'd';
            break;
        case 0x2000: // character special
            $info = 'c';
            break;
        case 0x1000: // FIFO pipe
            $info = 'p';
            break;
        default: // unknown
            $info = 'u';
    }

// Owner
    $info .= (($permissions & 0x0100) ? 'r' : '-');
    $info .= (($permissions & 0x0080) ? 'w' : '-');
    $info .= (($permissions & 0x0040) ?
                (($permissions & 0x0800) ? 's' : 'x' ) :
                (($permissions & 0x0800) ? 'S' : '-'));

// Group
    $info .= (($permissions & 0x0020) ? 'r' : '-');
    $info .= (($permissions & 0x0010) ? 'w' : '-');
    $info .= (($permissions & 0x0008) ?
                (($permissions & 0x0400) ? 's' : 'x' ) :
                (($permissions & 0x0400) ? 'S' : '-'));

// World
    $info .= (($permissions & 0x0004) ? 'r' : '-');
    $info .= (($permissions & 0x0002) ? 'w' : '-');
    $info .= (($permissions & 0x0001) ?
                (($permissions & 0x0200) ? 't' : 'x' ) :
                (($permissions & 0x0200) ? 'T' : '-'));

    return $info;
}


/**
 * function db_open()
 * Connect to the database
 *
 * @link     http://blog.program-o.com/?p=1340
 * @internal param string $host -  db host
 * @internal param string $user - db user
 * @internal param string $password - db password
 * @internal param string $database_name - db name
 * @return PDO $dbConn - the database connection resource
 */
function db_open()
{
    global $dbh, $dbu, $dbp, $dbn, $dbPort;

    try
    {
        $dbConn = new PDO("mysql:host=$dbh;port=$dbPort;dbname=$dbn;charset=utf8", $dbu, $dbp);
        $dbConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dbConn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $dbConn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    }
    catch (Exception $e) {
        //exit('Program O has encountered a problem with connecting to the database. With any luck, the following information will help: ' . $e->getMessage());
        $errMsg = <<<endMsg
Program O has encountered a problem with connecting to the database. With any luck, the following information will help:<br>
Error message: {$e->getMessage()}<br>
Host: {$dbh}<br>
Port: {$dbPort}<br>
User: {$dbu}<br>
Pass: {$dbp}<br>
endMsg;
        exit($errMsg);
    }

    return $dbConn;
}

/**
 * function db_close()
 * Close the connection to the database
 *
 * @link     http://blog.program-o.com/?p=1343
 * @internal param resource $dbConn - the open connection
 *
 * @return null
 */
function db_close($inPGO = true)
{
    if ($inPGO) runDebug(__FILE__, __FUNCTION__, __LINE__, 'This DB is now closed. You don\'t have to go home, but you can\'t stay here.', 2);
    return null;
}

/**
 * function db_fetch
 * Fetches a single row of data from the database
 *
 * @param string $sql - The SQL query to execute
 * @param mixed $params - either an array of placeholder/value pairs, or null, for no parameters
 * @param string $file - the path/filename of the file that the function call originated in
 * @param string $function - the name of the function that the function call originated in
 * @param string $line - the line number of the originating function call
 *
 * @return mixed $out - Either the row of data from the DB query, or false, if the query fails
 */
function db_fetch($sql, $params = null, $file = 'unknown', $function = 'unknown', $line = 'unknown', $inPGO = true)
{
    global $dbConn;
    //error_log(print_r($dbConn, true), 3, _LOG_PATH_ . 'dbConn.txt');
    try
    {
        $sth = $dbConn->prepare($sql);
        ($params === null) ? $sth->execute() : $sth->execute($params);
        $out = $sth->fetch();

        return $out;
    }
    catch (Exception $e)
    {
        //error_log("bad SQL encountered in file $file, line #$line. SQL:\n$sql\n", 3, _LOG_PATH_ . 'badSQL.txt');
        $pdoError = print_r($dbConn->errorInfo(), true);

        /** @noinspection PhpUndefinedVariableInspection */
        $psError = print_r($sth->errorInfo(), true);
        if ($inPGO) runDebug(__FILE__, __FUNCTION__, __LINE__, "An error was generated while extracting a row of data from the database in file $file at line $line, in the function $function - SQL:\n$sql\nPDO error: $pdoError\nPDOStatement error: $psError", 0);
        return false;
    }
}

/**
 * function db_fetchAll
 * Fetches rows of data from the database
 *
 * @param string $sql - The SQL query to execute
 * @param mixed $params - either an array of placeholder/value pairs, or null, for no parameters
 * @param string $file - the path/filename of the file that the function call originated in
 * @param string $function - the name of the function that the function call originated in
 * @param string $line - the line number of the originating function call
 *
 * @return mixed $out - Either an array of data from the DB query, or false, if the query fails
 */
function db_fetchAll($sql, $params = null, $file = 'unknown', $function = 'unknown', $line = 'unknown', $inPGO = true)
{
    global $dbConn;

    try {
        $sth = $dbConn->prepare($sql);
        ($params === null) ? $sth->execute() : $sth->execute($params);
        return $sth->fetchAll();
    }
    catch (Exception $e)
    {
        //error_log("bad SQL encountered in file $file, line #$line. SQL:\n$sql\n", 3, _LOG_PATH_ . 'badSQL.txt');
        $pdoError = print_r($dbConn->errorInfo(), true);

        /** @noinspection PhpUndefinedVariableInspection */
        $psError = print_r($sth->errorInfo(), true);
        $errSql = db_parseSQL($sql, $params);
        $dParams = (!is_null($params)) ? print_r($params, true) : 'null';
        $errMsg = <<<endMsg
An error was generated while extracting multiple rows of data from the database in file $file at line $line, in the function $function.
SQL: $errSql
Params: $dParams
PDO error: $pdoError
PDO_statement error: $psError

endMsg;
        if ($inPGO) runDebug(__FILE__, __FUNCTION__, __LINE__, $errMsg, 0);
        return false;
    }
}

/**
 * function db_write
 * write to the database
 *
 * @param string $sql - The SQL query to execute
 * @param mixed $params - either an array of placeholder/value pairs, or null, for no parameters
 * @param bool $multi - TODO add missing argument description
 * @param string $file - the path/filename of the file that the function call originated in
 * @param string $function - the name of the function that the function call originated in
 * @param string $line - the line number of the originating function call
 *
 * @return mixed $out - Either the number of rows affected by the DB query
 */
function db_write($sql, $params = null, $multi = false, $file = 'unknown', $function = 'unknown', $line = 'unknown', $inPGO = true)
{
    global $dbConn;
    $newLine = PHP_EOL;
    try
    {
        $sth = $dbConn->prepare($sql);

        switch (true)
        {
            case ($params === null):
                $sth->execute();
                break;

            case ($multi === true):
                foreach ($params as $row) {
                    $sth->execute($row);
                }
                break;

            default:
                $sth->execute($params);
        }

        return $sth->rowCount();
    }
    catch (Exception $e)
    {
        $errParams = ($multi) ? $row : $params;
        $paramsText = print_r($errParams, true);
        $pdoError = print_r($dbConn->errorInfo(), true);

        /** @noinspection PhpUndefinedVariableInspection */
        $psError = print_r($sth->errorInfo(), true);
        $eMessage = $e->getMessage();
        $errSQL = db_parseSQL($sql, $errParams);
        $errorMessage = <<<endMessage
Bad SQL encountered in file $file, line #$line. SQL:
$errSQL
PDO Error:
$pdoError
PDOStatement Error:
$psError
Exception Message:
$eMessage;
Parameters:
$paramsText
endMessage;
$file = str_replace(DIRECTORY_SEPARATOR, '/', $file);
$fpArray = explode('/', $file);
$fn = array_pop($fpArray);
$errLogPath = "{$fn}.{$function}.error.log";

        error_log($errorMessage, 3, _LOG_PATH_ . $errLogPath);

        $rdMessage = <<<endMessage
An error was generated while writing to the database in file $file at line $line, in the function $function.
SQL: $errSQL
PDO error: $pdoError
PDOStatement error: $psError
endMessage;

        if ($inPGO) runDebug(__FILE__, __FUNCTION__, __LINE__, $rdMessage, 0);
        return false;
    }
}


/**
 * function db_parseSQL
 *
 * Converts a prepared statment query into a human readable version
 *
 * @param string $sql
 * @param array $params
 *
 * @return string $out
 */

 function db_parseSQL ($sql, $params = null)
{
    $out = $sql;
    if (is_array($params))
    {
        foreach ($params as $key => $value)
        {
            if (is_numeric($key)) // deal with unnamed placeholders (?)
            {
                $limit = 1;
                $search = '/\?/';
                $value = (is_numeric($value)) ? $value : "'$value'"; // if $value is a string, encase it in quotes
                $out = preg_replace($search, $value, $out, $limit);
            }
            else // handle named parameters
            {
                $value = (is_numeric($value)) ? $value : "'$value'"; // if $value is a string, encase it in quotes
                $out = str_replace($key, $value, $out);
            }
        }
    }
    return $out;
}

function db_lastInsertId($name = null)
{
    global $dbConn;
    return $dbConn->lastInsertId($name);
}


