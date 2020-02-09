<?php

/***************************************
 * http://www.program-o.com
 * PROGRAM O
 * Version: 2.6.11
 * FILE: logs.php
 * AUTHOR: Elizabeth Perreau and Dave Morton
 * DATE: 12-12-2014
 * DETAILS: Displays chat logs for the currently selected chatbot
 ***************************************/
    $e_all = defined('E_DEPRECATED') ? E_ALL & ~E_DEPRECATED : E_ALL;
    require_once('../config/global_config.php');
    require_once(_LIB_PATH_ . 'misc_functions.php');
    require_once(_LIB_PATH_ . 'error_functions.php');
    require_once(_LIB_PATH_ . 'PDO_functions.php');
    error_reporting($e_all);
    ini_set('log_errors', true);
    ini_set('error_log', _LOG_PATH_ . 'logs_php.error.log');
    ini_set('html_errors', false);
    ini_set('display_errors', false);
    if (session_id() == '')
    {
        $session_name = 'PGO_Admin';
        session_name($session_name);
        session_start();
    }

    $allowed_get_vars = array(
    // Make sure to put at least something in here, like this:
        'page' => FILTER_DEFAULT,
        'action' => FILTER_DEFAULT,
        'showing' => FILTER_DEFAULT,
        'id'   => FILTER_VALIDATE_INT,
    //see http://php.net/manual/en/filter.constants.php for available options
    );
    $get_vars = clean_inputs($allowed_get_vars);
    if (isset($get_vars['action']) && function_exists($get_vars['action'])) $get_vars['action']();

    $show = (isset ($get_vars['showing'])) ? $get_vars['showing'] : "last 20";

//showThis($show)
$show_this = showThis($show);
$convo = (isset ($get_vars['id'])) ? getuserConvo($get_vars['id'], $show) : "Please select a conversation from the side bar.";
$user_list = (isset ($get_vars['id'])) ? getUserList($get_vars['id'], $show) : getUserList($_SESSION['poadmin']['bot_id'], $show);
$bot_name = (isset ($_SESSION['poadmin']['bot_name'])) ? $_SESSION['poadmin']['bot_name'] : 'unknown';
$upperScripts = $template->getSection('UpperScripts');
$lowerScripts = $template->getSection('LogoLinkScript');
$lowerScripts .= $template->getSection('CLScript');

$topNav = $template->getSection('TopNav');
$leftNav = $template->getSection('LeftNav');
$main = $template->getSection('Main');
$FooterInfo = getFooter();
$errMsgClass = (!empty ($msg)) ? "ShowError" : "HideError";
$errMsgStyle = $template->getSection($errMsgClass);
$rightNav = $template->getSection('RightNav');
$navHeader = $template->getSection('NavHeader');

$noLeftNav = '';
$noTopNav = '';
$noRightNav = '';

$headerTitle = 'Actions:';
$pageTitle = 'My-Program O - Chat Logs';
$mainContent = $template->getSection('ConversationLogs1');
$mainTitle = 'Chat Logs';

$rightNav = str_replace('[rightNavLinks]', $show_this . $user_list, $rightNav);
$rightNav = str_replace('[navHeader]', $navHeader, $rightNav);
$rightNav = str_replace('[headerTitle]', 'Log Actions:', $rightNav);

$mainContent = str_replace('[show_this]', '', $mainContent);
$mainContent = str_replace('[convo]', $convo, $mainContent);
$mainContent = str_replace('[bot_name]', $bot_name, $mainContent);

/**
 * Function getUserNames
 *
 * @return array
 */
function getUserNames()
{
    $nameList = array();

    /** @noinspection SqlDialectInspection */
    $sql = "SELECT `id`, `user_name` FROM `users` WHERE 1 order by `id`;";
    $result = db_fetchAll($sql,null, __FILE__, __FUNCTION__, __LINE__);
    foreach ($result as $row) {
        $nameList[$row['id']] = $row['user_name'];
    }
    return $nameList;
}

/**
 * Function getUserList
 *
 * @param $bot_id
 * @param $showing
 * @return string
 */
function getUserList($bot_id, $showing)
{
    //db globals
    global $template, $get_vars;

    $nameList = getUserNames();
    $curUserid = (isset ($get_vars['id'])) ? $get_vars['id'] : -1;
    $bot_id = $_SESSION['poadmin']['bot_id'];
    $linkTag = $template->getSection('NavLink');
    $ts = '';

    $sql = "SELECT DISTINCT(`user_id`),COUNT(`user_id`) AS TOT FROM `conversation_log`  WHERE bot_id = :bot_id AND DATE(`timestamp`) >= [ts] GROUP BY `user_id`, `convo_id` ORDER BY ABS(`user_id`) ASC;";

    switch ($showing)
    {
        case "today" :
            $ts = 'DATE(NOW())';
            break;
        case "previous week" :
            $ts = 'DATE(NOW() - interval 1 week)';
            break;
        case "previous 2 weeks" :
            $ts = 'DATE(NOW() - interval 2 week)';
            break;
        case "previous month" :
            $ts = 'DATE(NOW() - interval 1 month)';
            break;
        case "previous 6 months" :
            $ts = 'DATE(NOW() - interval 6 month)';
            break;
        case "past 12 months" :
            $ts = 'DATE(NOW() - interval 1 year)';
            break;
        case "all time" :
            $ts = '0';
            break;
        case 'last 20':
            $sql = "SELECT DISTINCT(`user_id`),COUNT(`user_id`) AS TOT FROM `conversation_log`  WHERE  bot_id = '$bot_id' GROUP BY `user_id` ORDER BY ABS(`user_id`) ASC limit 20;";
            //$repl_date = time();
            $repl_date = false;
            break;
        default :
            /** @noinspection SqlDialectInspection */
            $sql = "SELECT DISTINCT(`user_id`),COUNT(`user_id`) AS TOT FROM `conversation_log`  WHERE  bot_id = :bot_id GROUP BY `user_id` ORDER BY ABS(`user_id`) ASC;";
    }

    $list = '<div class="userlist"><ul>';

    $sql = str_replace('[ts]', $ts, $sql);
    $params = array(
        ':bot_id' => $bot_id,
    );
    $debugSQL = db_parseSQL($sql, $params);
    //save_file(_LOG_PATH_ . __FUNCTION__ . '.sql.txt', $debugSQL);
    $rows = db_fetchAll($sql, $params, __FILE__, __FUNCTION__, __LINE__);
    $numRows = count($rows);

    if ($numRows == 0 || false === $rows)
    {
        $list .= '          <li>No chat log entries found</li>';
    }

    else {
        foreach ($rows as $row)
        {
            $user_id = $row['user_id'];
            $linkClass = ($user_id == $curUserid) ? 'selected' : 'noClass';
            $userName = (!empty($nameList[$user_id])) ? $nameList[$user_id] : 'Unknown';

            $TOT = $row['TOT'];

            $tmpLink = str_replace('[linkClass]', " class=\"$linkClass\"", $linkTag);
            $tmpLink = str_replace('[linkOnclick]', '', $tmpLink);
            $tmpLink = str_replace('[linkHref]', "href=\"index.php?page=logs&showing=$showing&id=$user_id#$user_id\" name=\"$user_id\"", $tmpLink);
            $tmpLink = str_replace('[linkTitle]', " title=\"Show entries for user $userName\"", $tmpLink);
            $tmpLink = str_replace('[linkLabel]', "USER ID #{$user_id}:$userName($TOT)", $tmpLink);

            $anchor = "            <a name=\"$user_id\" />\n";
            $anchor = '';

            $list .= "$tmpLink\n$anchor";
        }
    }

    $list .= "\n       </div>\n";

    return $list;
}

/**
 * Function showThis
 *
 * @param string $showing
 * @return string
 */
function showThis($showing = "last 20")
{
    $showarray = array("last 20", "today", "previous week", "previous 2 weeks", "previous month", "last 6 months", "past 12 months", "all time");
    $options = "";

    foreach ($showarray as $index => $value)
    {
        if ($value == $showing)
        {
            $sel = " SELECTED=SELECTED";
        }
        else {
            $sel = "";
        }

        $options .= "          <option value=\"$value\"$sel>$value</option>\n";
    }

    $form = <<<endForm
        <form name="showthis" method="post" action="index.php?page=logs">
          <select name="showing" id="showing">
$options
          </select>
        <input type="submit" id="submit" name="submit" value="show">
      </form>
endForm;

    return $form;
}

/**
 * Function getuserConvo
 *
 * @param $id
 * @param $showing
 * @return mixed|string
 */
function getuserConvo($id, $showing)
{
    $bot_name = (isset ($_SESSION['poadmin']['bot_name'])) ? $_SESSION['poadmin']['bot_name'] : 'Bot';
    $bot_id = (isset ($_SESSION['poadmin']['bot_id'])) ? $_SESSION['poadmin']['bot_id'] : 0;
    $nameList = getUserNames();
    $user_name = (!empty($nameList[$id])) ? $nameList[$id] : 'Unknown';
    $ts = '';
    $ats = '';
    /** @noinspection SqlDialectInspection */
    $sql = 'SELECT *  FROM `conversation_log` WHERE `bot_id` = :bot_id AND `user_id` = :user_id  AND DATE(`timestamp`) >= [ts] ORDER BY `id` ASC;';

    switch ($showing)
    {
        case 'today' :
            $ts = 'DATE(NOW())';
            $title = "Today's Conversations for user: $user_name (ID #{$id})";
            break;
        case 'previous week' :
            $ts = 'DATE(NOW() - interval 1 week)';
            $title = "Conversations for user: $user_name (ID #{$id}) for the past week";
            break;
        case 'previous 2 weeks' :
            $ts = 'DATE(NOW() - interval 2 week)';
            $title = "Conversations for user: $user_name (ID #{$id}) for the past two weeks";
            break;
        case 'previous month' :
            $ts = 'DATE(NOW() - interval 1 month)';
            $title = "Conversations for user: $user_name (ID #{$id}) for the past month";
            break;
        case 'previous 6 months' :
            $ts = 'DATE(NOW() - interval 6 month)';
            $title = "Conversations for user: $user_name (ID #{$id}) for the past six months";
            break;
        case 'past 12 months' :
            $ts = 'DATE(NOW() - interval 1 year)';
            $title = "Conversations for user: $user_name (ID #{$id}) for the past year";
            break;
        case 'last 20':
            /** @noinspection SqlDialectInspection */
            $sql = 'SELECT *  FROM `conversation_log` WHERE `bot_id` = :bot_id AND `user_id` = :user_id ORDER BY `id` ASC limit 20;';
            $title = "Last 20 Conversation entries for user: $user_name (ID #{$id})";
            $ats = '0 limit 20';
            break;
        case 'all time' :
            $sql = 'SELECT *  FROM `conversation_log` WHERE `bot_id` = :bot_id AND `user_id` = :user_id ORDER BY `id` ASC;';
            $title = "All conversations for user: $user_name (ID #{$id})";
            $ats = 'foo';
            break;
        default :
            $title = "(ERROR! Unexpected value for showing: {$showing}!)";
    }

    //get undefined defaults from the db
    $params = array(
        ':bot_id'  => $bot_id,
        ':user_id' => $id,
    );
    $sql = str_replace('[ts]', $ts, $sql);
    $ts = (!empty($ats)) ? $ats : $ts;
    $clearLink = <<<endLink
 <a href="#" class="cl_del" data-sr="[sr]">Clear logs for this conversation</a>
endLink;
    $list = "<hr><br/><h4>{$title}:</h4>";
    $list .= '<div class="convolist">';
    $debugSQL = db_parseSQL($sql, $params);
    //save_file(_LOG_PATH_ . __FUNCTION__ . '.sql.txt', $debugSQL);

    $result = db_fetchAll($sql, $params, __FILE__, __FUNCTION__, __LINE__);
    $storedRows = array();
    $queries = array();
    $lasttimestamp = '';
    $i = 1;
    foreach ($result as $row)
    {
        extract($row);
        $response = stripslashes($response);
        $thisdate = date("Y-m-d", strtotime($timestamp));
        $storedRowsIndex =  "{$bot_id}_{$user_id}_{$thisdate}";
        if (!isset($storedRows[$storedRowsIndex])) $storedRows[$storedRowsIndex] = array();
        if (!isset($queries[$storedRowsIndex])) $queries[$storedRowsIndex] = array();
        $storedRows[$storedRowsIndex][] = $id;
        $queries[$storedRowsIndex][] = $sql;

        if ($thisdate != $lasttimestamp)
        {
            if ($i > 1 && $showing == 'last 20') {
                break;
            }
            $cl = str_replace('[sr]', $storedRowsIndex, $clearLink);
            $list .= "<hr><br/><h4>Conversation#$i $thisdate $cl</h4>";
            $i++;
        }

        $list .= "<br><span data-id=\"{$id}\" style=\"color:DARKBLUE;\">$user_name: {$input}</span>";
        $list .= "<br><span style=\"color:GREEN;\">$bot_name: {$response}</span>";
        $lasttimestamp = $thisdate;
    }
    $list .= "</div>";
    $list = str_ireplace('<script', '&lt;script', $list);
    $_SESSION['stored_rows'] = $storedRows;
    $_SESSION['cl_sql'] = 'Foo!';

    return $list;
}

function clearLogs()
{
    $out = array();
    $allowed_get_vars = array(
        'sr'      => FILTER_DEFAULT,
    );
    $get_vars = clean_inputs($allowed_get_vars);
    $out['get_vars'] = print_r($get_vars, true);
    extract($get_vars);
    $storedRows = $_SESSION['stored_rows'];

    $idRegEx =implode(' OR `id` = ', $storedRows[$sr]);
    list($bot_id, $user_id, $timestamp) = explode('_', $sr);
    $sql = "DELETE from `conversation_log` where id = $idRegEx;";
    //save_file(_LOG_PATH_ . 'clearLogs.sql.txt', $sql);
    $numRows = db_write($sql);
    If ($numRows > 0)
    {
        $out['message'] = "Conversation log altered. {$numRows} entries deleted.";
    }
    else
    {
        $out['message'] = 'Deletion of log entries failed. Please check the error logs.';
    }
    exit (json_encode($out));
}

