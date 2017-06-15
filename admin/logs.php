<?php

/***************************************
 * http://www.program-o.com
 * PROGRAM O
 * Version: 2.6.5
 * FILE: logs.php
 * AUTHOR: Elizabeth Perreau and Dave Morton
 * DATE: 12-12-2014
 * DETAILS: Displays chat logs for the currently selected chatbot
 ***************************************/
$allowed_get_vars = array(
    // Make sure to put at least something in here, like this:
    'page' => FILTER_DEFAULT,
    'showing' => FILTER_DEFAULT,
    'id'   => FILTER_VALIDATE_INT,
    //see http://php.net/manual/en/filter.constants.php for available options
);
$get_vars = clean_inputs($allowed_get_vars);

$show = (isset ($get_vars['showing'])) ? $get_vars['showing'] : "last 20";

//showThis($show)
$show_this = showThis($show);
$convo = (isset ($get_vars['id'])) ? getuserConvo($get_vars['id'], $show) : "Please select a conversation from the side bar.";
$user_list = (isset ($get_vars['id'])) ? getUserList($get_vars['id'], $show) : getUserList($_SESSION['poadmin']['bot_id'], $show);
$bot_name = (isset ($_SESSION['poadmin']['bot_name'])) ? $_SESSION['poadmin']['bot_name'] : 'unknown';
$upperScripts = $template->getSection('UpperScripts');

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
    global $dbConn;
    $nameList = array();

    /** @noinspection SqlDialectInspection */
    $sql = "SELECT `id`, `user_name` FROM `users` WHERE 1 order by `id`;";
    $result = db_fetchAll($sql, null, __FILE__, __FUNCTION__, __LINE__);
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
    global $template, $get_vars, $dbConn;

    $nameList = getUserNames();
    $curUserid = (isset ($get_vars['id'])) ? $get_vars['id'] : -1;
    $bot_id = $_SESSION['poadmin']['bot_id'];
    $linkTag = $template->getSection('NavLink');

    /** @noinspection SqlDialectInspection */
    $sql = "SELECT DISTINCT(`user_id`),COUNT(`user_id`) AS TOT FROM `conversation_log`  WHERE bot_id = :bot_id AND DATE(`timestamp`) >= ([repl_date]) GROUP BY `user_id`, `convo_id` ORDER BY ABS(`user_id`) ASC";
    $showarray = array("last 20", "previous week", "previous 2 weeks", "previous month", "last 6 months", "this year", "previous year", "all years");

    switch ($showing)
    {
        case "today" :
            $repl_date = strtotime(date("Y-m-d"));
            break;
        case "previous week" :
            //$repl_date = strtotime("-1 week");
            $repl_date = 'NOW() - interval 1 week';
            break;
        case "previous 2 weeks" :
            $repl_date = strtotime("-2 week");
            $repl_date = 'NOW() - interval 1 week';
            break;
        case "previous month" :
            $repl_date = strtotime("-1 month");
            $repl_date = 'NOW() - interval 1 week';
            break;
        case "previous 6 months" :
            $repl_date = strtotime("-6 month");
            $repl_date = 'NOW() - interval 1 week';
            break;
        case "past 12 months" :
            $repl_date = strtotime("-1 year");
            $repl_date = 'NOW() - interval 1 week';
            break;
        case "all time" :
            /** @noinspection SqlDialectInspection */
            $sql = "SELECT DISTINCT(`user_id`),COUNT(`user_id`) AS TOT FROM `conversation_log`  WHERE  bot_id = '$bot_id' GROUP BY `user_id` ORDER BY ABS(`user_id`) ASC;";
            //$repl_date = time();
            $repl_date = false;
            break;
        case 'last 20':
            $sql = "SELECT DISTINCT(`user_id`),COUNT(`user_id`) AS TOT FROM `conversation_log`  WHERE  bot_id = '$bot_id' GROUP BY `user_id` ORDER BY ABS(`user_id`) DESC limit 20;";
            //$repl_date = time();
            $repl_date = false;
            break;
        default :
            /** @noinspection SqlDialectInspection */
            $sql = "SELECT DISTINCT(`user_id`),COUNT(`user_id`) AS TOT FROM `conversation_log`  WHERE  bot_id = '$bot_id' GROUP BY `user_id` ORDER BY ABS(`user_id`) ASC;";
            //$repl_date = time();
            $repl_date = false;
    }

    $list = '<div class="userlist"><ul>';

    $sql = str_replace('[repl_date]', $repl_date, $sql);
    $params = array(
        ':bot_id' => $bot_id,
        ':repl_date' => $repl_date
    );
    if (false === $repl_date) unset($params[':repl_date']);
    $rows = db_fetchAll($sql, $params, __FILE__, __FUNCTION__, __LINE__);
    $numRows = count($rows);

    if ($numRows == 0 || false === $rows)
    {
        $list .= '          <li>No log entries found</li>';
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
            $tmpLink = str_replace('[linkLabel]', "USER:$userName($TOT)", $tmpLink);

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
    global $dbConn;

    $bot_name = (isset ($_SESSION['poadmin']['bot_name'])) ? $_SESSION['poadmin']['bot_name'] : 'Bot';
    $bot_id = (isset ($_SESSION['poadmin']['bot_id'])) ? $_SESSION['poadmin']['bot_id'] : 0;
    $nameList = getUserNames();
    $user_name = (!empty($nameList[$id])) ? $nameList[$id] : 'Unknown';
    $sqladd = '';

    switch ($showing)
    {
        case "today" :
            $sqladd = "AND DATE(`timestamp`) = '" . date('Y-m-d') . "'";
            $title = "Today's ";
            break;
        case "previous week" :
            $lastweek = strtotime("-1 week");
            $sqladd = "AND DATE(`timestamp`) >= '" . $lastweek . "'";
            $title = "Last week's ";
            break;
        case "previous 2 weeks" :
            $lasttwoweek = strtotime("-2 week");
            $sqladd = "AND DATE(`timestamp`) >= '" . $lasttwoweek . "'";
            $title = "Last two week's ";
            break;
        case "previous month" :
            $lastmonth = strtotime("-1 month");
            $sqladd = "AND DATE(`timestamp`) >= '" . $lastmonth . "'";
            $title = "Last month's ";
            break;
        case "previous 6 months" :
            $lastsixmonth = strtotime("-6 month");
            $sqladd = "AND DATE(`timestamp`) >= '" . $lastsixmonth . "'";
            $title = "Last six month's ";
            break;
        case "past 12 months" :
            $lastyear = strtotime("-1 year");
            $sqladd = "AND DATE(`timestamp`) >= '" . $lastyear . "'";
            $title = "Last twelve month's ";
            break;
        case "all time" :
            $sql = "";
            $title = "All ";
            break;
        default :
            $sqladd = "";
            $title = "Last ";
    }
    $lasttimestamp = "";
    $i = 1;

    //get undefined defaults from the db
    /** @noinspection SqlDialectInspection */
    $sql = "SELECT *  FROM `conversation_log` WHERE `bot_id` = '$bot_id' AND `user_id` = $id $sqladd ORDER BY `id` ASC";
    $list = "<hr><br/><h4>$title conversations for user: $id</h4>";
    $list .= "<div class=\"convolist\">";

    $result = db_fetchAll($sql, null, __FILE__, __FUNCTION__, __LINE__);

    foreach ($result as $row)
    {
        $thisdate = date("Y-m-d", strtotime($row['timestamp']));

        if ($thisdate != $lasttimestamp)
        {
            if ($i > 1 && $showing == 'last 20') {
                break;
            }

            $date = date("Y-m-d");
            $list .= "<hr><br/><h4>Conversation#$i $thisdate</h4>";
            $i++;
        }

        $list .= "<br><span style=\"color:DARKBLUE;\">$user_name: " . $row['input'] . "</span>";
        $list .= "<br><span style=\"color:GREEN;\">$bot_name: " . $row['response'] . "</span>";
        $lasttimestamp = $thisdate;
    }

    $list .= "</div>";
    $list = str_ireplace('<script', '&lt;script', $list);

    return $list;
}

