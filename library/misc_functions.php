<?php
/***************************************
 * http://www.program-o.com
 * PROGRAM O
 * Version: 2.6.5
 * FILE: misc_functions.php
 * AUTHOR: Elizabeth Perreau and Dave Morton
 * DATE: 05-22-2013
 * DETAILS: Miscelaneous functions for Program O
 ***************************************/

/**
 * function get_cURL
 * Uses PHP's cURL functions to obtain data from "outside locations"
 *
 * @param (string) $url - The URL or IP address to access
 * @param array $options
 * @param array $params
 *
 * @return mixed|string (string) $out - The returned value from the curl_exec() call.
 */
function get_cURL($url, $options = array(), $params = array())
{
    $failed = 'Cannot process CURL call.'; // This will need to be changed, at some point.

    if (function_exists('curl_init'))
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (is_array($options) && count($options) > 0)
        {
            foreach ($options as $key => $value) {
                curl_setopt($ch, $key, $value);
            }
        }

        if (is_array($params) && count($params) > 0)
        {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }

        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }
    else {
        return $failed;
    }
}

/**
 * function _strtolower
 * Performs multibyte or standard lowercase conversion of a string,
 * based on configuration.
 *
 * @param string $text The string to convert.
 * @return string The input string converted to lower case.
 */
function _strtolower($text)
{
    return (IS_MB_ENABLED) ? mb_strtolower($text) : strtolower($text);
}

/**
 * function _strtoupper
 * Performs multibyte or standard uppercase conversion of a string,
 * based on configuration.
 *
 * @param string $text The string to convert.
 * @return string The string converted to UPPER CASE.
 */
function _strtoupper($text)
{
    return (IS_MB_ENABLED) ? mb_strtoupper($text) : strtoupper($text);
}

/**
 * function _title_case
 *
 * Performs multibyte or standard uppercase conversion of the first character of a string,
 * based on configuration.
 *
 * @param string $text The string to convert.
 * @return string The string converted to Title Case.
 */
function _title_case($text)
{
    global $charset;
    return (IS_MB_ENABLED) ? mb_convert_case($text, MB_CASE_TITLE, $charset) : ucwords($text);
}

/**
 * function _substr
 *
 * Gets the substring of either single byte or multi-byte strings by index and length, depending on configuration
 *
 * @param string $text
 * @param string $start
 * @param string $len
 * @param string $encoding
 * @return string
 */
function _substr($text, $start, $len = null, $encoding = null)
{
    if ($encoding === null) {
        $encoding = mb_internal_encoding();
    }

    return (IS_MB_ENABLED) ? mb_substr($text, $start, $len, $encoding) : substr($text, $start, $len);
}

/**
 * function normalize_text
 * Transforms text to uppercase, removes all punctuation, and strips extra whitespace
 *
 * @param string $text - The text to perform the transformations on
 * @param bool $convert_case - Flag for converting text to uppercase. Default = true
 * @return string $normalized_text - The completely transformed text
 */
function normalize_text($text, $convert_case = true)
{
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Begin normalization - text = '$text'", 4);

    $srch = array(
        '/(\d+)\s*-\s*(\d+)/',
        '/(\d+)\s*\+\s*(\d+)/',
        '/(\d+)\s*\*\s*(\d+)/',
        '/(\d+)\s*x\s*(\d+)/',
        '/(\d+)\s*\/\s*(\d+)/',
        '/[[:punct:]]/uis',
        '/\s\s+/',
    );
    $repl = array(
        '$1 MINUS $2',
        '$1 PLUS $2',
        '$1 TIMES $2',
        '$1 TIMES $2',
        '$1 DIVIDEDBY $2',
        ' ',
        ' ',
    );

    $normalized_text = preg_replace($srch, $repl, $text);
    $normalized_text = ($convert_case) ? _strtoupper($normalized_text) : $normalized_text;
    $normalized_text = trim($normalized_text);

    runDebug(__FILE__, __FUNCTION__, __LINE__, "Normalization complete. Text = '$normalized_text'", 4);

    return $normalized_text;
}

/**
 * Function getFooter
 *
 *
 * @return string
 */
function getFooter()
{
    $ip = $_SERVER['REMOTE_ADDR'];

    $name = (isset($_SESSION['poadmin']['name'])) ? $_SESSION['poadmin']['name'] : 'unknown';
    $lip = (isset($_SESSION['poadmin']['lip'])) ? $_SESSION['poadmin']['lip'] : 'unknown';
    $last = (isset($_SESSION['poadmin']['last_login'])) ? $_SESSION['poadmin']['last_login'] : 'unknown';
    $llast = (isset($_SESSION['poadmin']['prior_login'])) ? $_SESSION['poadmin']['prior_login'] : 'unknown';

    $admess = "You are logged in as: $name from $ip since: $last";
    $admess .= "<br />You last logged in from $lip on $llast";
    $today = date("Y");

    $out = <<<endFooter
    <p>&copy; $today My Program-O<br />$admess</p>
endFooter;

    return $out;
}

/*
 * function pgo_session_gc
 * Performs garbage collection on expired session files
 * @return void
 */


  function pgo_session_gc()
  {
    global $session_lifetime;
    $session_files = glob(_SESSION_PATH_ . 'sess_*');
    clearstatcache();
    foreach($session_files as $file)
    {
        $gcRand = mt_rand(0,10000); // random number from 0 to 10,000
        $lastAccessed = fileatime($file);
        if ($gcRand >= 10 && $lastAccessed < (time() - $session_lifetime)) unlink($file);
    }
  }

/**
 * function addUnknownInput
 * Adds previously unknown inputs to the database for later examination, and possible creation of new AIML categories
 * @param array $convoArr
 * @param string $input
 * @param int $bot_id
 * @param string $user_id
 * @return void
 */

function addUnknownInput($convoArr, $input, $bot_id, $user_id)
{
    global $dbConn, $dbn;

    $default_aiml_pattern = get_convo_var($convoArr, 'conversation', 'default_aiml_pattern');

    if ($input == $default_aiml_pattern) {
        return;
    }

    /** @noinspection SqlDialectInspection */
    $sql = "INSERT INTO `$dbn`.`unknown_inputs` (`id`, `bot_id`, `input`, `user_id`, `timestamp`) VALUES(null, :bot_id, :input, :user_id, CURRENT_TIMESTAMP);";

    $params = array(
        ':bot_id' => $bot_id,
        ':input' => $input,
        ':user_id' => $user_id,
    );
    $numRows = db_write($sql, $params, false, __FILE__, __FUNCTION__, __LINE__);
}

/**
 * function pretty_print_r
 * outputs a formatted text string from a print_r() statement
 * @param mixed $var
 * @return string $out
 */

function pretty_print_r($var)
{
    switch (true)
    {
        case (is_array($var)):
            $out = implode_recursive(",\n", $var, __FILE__, __FUNCTION__, __LINE__);
            break;
        default:
            $out = $var;
    }
    return trim($out, ",\n");
}

function clean_inputs($options = null)
{
    $referer = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : false;
    $host = (isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : false;
    if (false === $host || false === $referer) die ('CSRF failure!');
    $formVars = array_merge($_GET, $_POST);

    switch (true)
    {
        case (null === $options):
            $out = filter_var_array($formVars);
            break;
        case (!is_array($options)):
            if (!isset($formVars[$options])) return false;
            $vars = filter_var_array($formVars);
            $out = $vars[$options];
            break;
        default:
            $out = filter_var_array($formVars, $options, false);
    }
    return $out;
}

