<?php
/***************************************
 * http://www.program-o.com
 * PROGRAM O
 * Version: 2.6.7
 * FILE: person.php
 * AUTHOR: Elizabeth Perreau and Dave Morton
 * DATE: 11-19-2017
 * DETAILS: Display/Edit person (1st to 2nd, 1st to 3rd) transformations
 ***************************************/


$upperScripts = '';

$topNav = $template->getSection('TopNav');
$leftNav = $template->getSection('LeftNav');
$main = $template->getSection('Main');
$navHeader = $template->getSection('NavHeader');
$FooterInfo = getFooter();
$errMsgClass = (!empty ($msg)) ? "ShowError" : "HideError";
$errMsgStyle = $template->getSection($errMsgClass);

$noLeftNav = '';
$noTopNav = '';

$noRightNav = $template->getSection('NoRightNav');
$headerTitle = 'Actions:';
$pageTitle = 'My-Program O - &lt;person&gt; Tag Handling';
$mainContent = $template->getSection('StatsPage');

$mainTitle = 'Search/Replace Pairs for the &lt;person&gt; Tag';



/**
 * Function getPairCount
 *
 * @param $interval
 * @return mixed
 */
function getPairCount()
{
    global $bot_id, $dbConn;
    $params = array();


    $sql = 'SELECT COUNT(DISTINCT(*)) AS TOT FROM `person`';
    $row = db_fetch($sql, null, __FILE__, __FUNCTION__, __LINE__);
    $res = $row['TOT'];

    return $res;
}

/**
 * Function getChatLines
 *
 * @param $i
 * @param $j
 * @return mixed
 */
function getPairs()
{
    global $bot_id, $dbConn;

    $sql = 'select `id`, `srch`, `repl` from `person`;';


    $row = db_fetch($sql, null, __FILE__, __FUNCTION__, __LINE__);
    $res = $row['TOT'];

    return $res;
}