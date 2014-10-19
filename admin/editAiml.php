<?php

/* * *************************************
 * http://www.program-o.com
 * PROGRAM O
 * Version: 2.4.3
 * FILE: editAiml.php
 * AUTHOR: Elizabeth Perreau and Dave Morton
 * DATE: 05-26-2014
 * DETAILS: Search the AIML table of the DB for desired categories
 * ************************************* */

$post_vars = filter_input_array(INPUT_POST);
$get_vars = filter_input_array(INPUT_GET);
$form_vars = array_merge((array) $post_vars, (array) $get_vars);

if (isset($get_vars['action'])) {
    //load shared files  
    $thisFile = __FILE__;
    require_once('../config/global_config.php');
    require_once(_LIB_PATH_ . 'PDO_functions.php');
    require_once(_LIB_PATH_ . 'error_functions.php');
    session_start();
    if (empty($_SESSION) || !isset($_SESSION['poadmin']['uid']) || $_SESSION['poadmin']['uid'] == "") {
        echo "No session found";
        exit();
    }
    // Open the DB
    $dbConn = db_open();
}

if ((isset($get_vars['action'])) && ($get_vars['action'] == "search")) {
    $group = (isset($get_vars['group'])) ? $get_vars['group'] : 1;
    echo json_encode(runSearch());
    exit();
} else if ((isset($get_vars['action'])) && ($get_vars['action'] == "add")) {
    echo insertAIML();
    exit();
} else if ((isset($get_vars['action'])) && ($get_vars['action'] == "update")) {
    echo updateAIML();
    exit();
} else if ((isset($get_vars['action'])) && ($get_vars['action'] == "del") && (isset($get_vars['id'])) && ($get_vars['id'] != "")) {
    echo delAIML($get_vars['id']);
    exit();
} else {
    $mainContent = $template->getSection('EditAimlPage');
}

$upperScripts = '<script type="text/javascript" src="scripts/tablesorter.min.js"></script>' . "\n";
$topNav = $template->getSection('TopNav');
$leftNav = $template->getSection('LeftNav');
$rightNav = $template->getSection('RightNav');
$main = $template->getSection('Main');

$navHeader = $template->getSection('NavHeader');

$FooterInfo = getFooter();
$errMsgClass = (!empty($msg)) ? "ShowError" : "HideError";
$errMsgStyle = $template->getSection($errMsgClass);
$noLeftNav = '';
$noTopNav = '';
$noRightNav = $template->getSection('NoRightNav');
$headerTitle = 'Actions:';
$pageTitle = 'My-Program O - Search/Edit AIML';
$mainTitle = 'Search/Edit AIML';

/**
 * Function delAIML
 *
 * * @param $id
 * @return string
 */
function delAIML($id) {
    global $dbConn;
    if ($id != "") {
        $sql = "DELETE FROM `aiml` WHERE `id` = '$id' LIMIT 1";
        $sth = $dbConn->prepare($sql);
        $sth->execute();
        $affectedRows = $sth->rowCount();

        if ($affectedRows == 0) {
            $msg = 'Error AIML couldn\'t be deleted - no changes made.</div>';
        } else {
            $msg = 'AIML has been deleted.';
        }
    } else {
        $msg = 'Error AIML couldn\'t be deleted - no changes made.';
    }
    return $msg;
}

/**
 * Function runSearch
 *
 *
 * @return mixed|string
 */
function runSearch() {
    global $bot_id, $bot_name, $form_vars, $dbConn, $group;
    $groupSize = 10;
    //exit("group = $group");
    $i = 0;
    $searchTermsTemplate = " like '%[value]%' or\n  ";
    $searchTerms = '';
    $search_fields = array('search_topic', 'search_filename', 'search_pattern', 'search_template', 'search_that');
    $qs = '';
    foreach ($search_fields as $index) {
        $$index = trim($form_vars[$index]);
        if (!empty($form_vars[$index])) {
            $ue = urlencode($form_vars[$index]);
            $qs .= "&amp;$index=$ue";
        }
    }
//    if (!empty($search_topic) or ! empty($search_filename) or ! empty($search_pattern) or ! empty($search_template) or ! empty($search_that)) {
    $limit = ($group - 1) * $groupSize;
    $limit = ($limit < 0) ? 0 : $limit;

    $searchTerms .= (!empty($search_topic)) ? '`topic`' . str_replace('[value]', $search_topic, $searchTermsTemplate) : '';
    $searchTerms .= (!empty($search_filename)) ? '`filename`' . str_replace('[value]', $search_filename, $searchTermsTemplate) : '';
    $searchTerms .= (!empty($search_pattern)) ? '`pattern`' . str_replace('[value]', $search_pattern, $searchTermsTemplate) : '';
    $searchTerms .= (!empty($search_template)) ? '`template`' . str_replace('[value]', $search_template, $searchTermsTemplate) : '';
    $searchTerms .= (!empty($search_that)) ? '`thatpattern`' . str_replace('[value]', $search_that, $searchTermsTemplate) : '';
    $searchTerms = rtrim($searchTerms, " or\n ");
    if ($searchTerms != "")
        $searchTerms = " AND (" . $searchTerms . ")";
    $countSQL = "SELECT count(id) FROM `aiml` WHERE `bot_id` = '$bot_id' [searchTerms]";
    $countSQL = str_replace('[searchTerms]', $searchTerms, $countSQL);
    $sth = $dbConn->prepare($countSQL);
    $sth->execute();
    $row = $sth->fetch();
    $total = $row['count(id)'];

    $limit = ($limit >= $row['count(id)']) ? $total - 1 - ($total - 1) % $groupSize : $limit;

    $sql = "SELECT id, topic, filename, pattern, template, thatpattern FROM `aiml` WHERE `bot_id` = '$bot_id' [searchTerms] order by id limit $limit, $groupSize;";
    $sql = str_replace('[searchTerms]', $searchTerms, $sql);
    //trigger_error("SQL = $sql");
    $sth = $dbConn->prepare($sql);
    $sth->execute();
    $result = $sth->fetchAll();
    return ["results" => $result,
        "total_records" => $row['count(id)'],
        "start_index" => 0,
        "page" => ($limit / $groupSize) + 1,
        "page_size" => $groupSize];
}

/**
 * Function updateAIML
 *
 *
 * @return string
 */
function updateAIML() {
    global $post_vars, $dbConn;
    $template = trim($post_vars['template']);
    $filename = trim($post_vars['filename']);
    $pattern = (IS_MB_ENABLED) ? mb_strtoupper(trim($post_vars['pattern'])) : strtoupper(trim($post_vars['pattern']));
    $thatpattern = (IS_MB_ENABLED) ? mb_strtoupper(trim($post_vars['thatpattern'])) : strtoupper(trim($post_vars['thatpattern']));
    $topic = (IS_MB_ENABLED) ? mb_strtoupper(trim($post_vars['topic'])) : strtoupper(trim($post_vars['topic']));
    $id = trim($post_vars['id']);
    if (($template == "") || ($pattern == "") || ($id == "")) {
        $msg = 'Please make sure you have entered a user input and bot response ';
    } else {
        $sql = "UPDATE `aiml` SET `pattern` = '$pattern',`thatpattern`='$thatpattern',`template`='$template',`topic`='$topic',`filename`='$filename' WHERE `id`='$id' LIMIT 1";
        $sth = $dbConn->prepare($sql);
        $sth->execute();
        $affectedRows = $sth->rowCount();
        if ($affectedRows > 0) {
            $msg = 'AIML Updated.';
        } else {
            $msg = 'There was an error updating the AIML - no changes made.';
        }
    }
    return $msg;
}

/**
 * Function insertAIML
 *
 *
 * @return string
 */
function insertAIML() {
    //db globals
    global $template, $msg, $post_vars, $dbConn;
    $aiml = "<category><pattern>[pattern]</pattern>[thatpattern]<template>[template]</template></category>";
    $aimltemplate = trim($post_vars['template']);
    $pattern = trim($post_vars['pattern']);
    $pattern = (IS_MB_ENABLED) ? mb_strtoupper($pattern) : strtoupper($pattern);
    $thatpattern = trim($post_vars['thatpattern']);
    $thatpattern = (IS_MB_ENABLED) ? mb_strtoupper($thatpattern) : strtoupper($thatpattern);
    $aiml = str_replace('[pattern]', $pattern, $aiml);
    $aiml = (empty($thatpattern)) ? str_replace('[thatpattern]', "<that>$thatpattern</that>", $aiml) : $aiml;
    $aiml = str_replace('[template]', $aimltemplate, $aiml);
    $topic = trim($post_vars['topic']);
    $topic = (IS_MB_ENABLED) ? mb_strtoupper($topic) : strtoupper($topic);
    $bot_id = (isset($_SESSION['poadmin']['bot_id'])) ? $_SESSION['poadmin']['bot_id'] : 1;
    if (($pattern == "") || ($aimltemplate == "")) {
        $msg = 'You must enter a user input and bot response.';
    } else {
        $sql = "INSERT INTO `aiml` (`id`,`bot_id`, `aiml`, `pattern`,`thatpattern`,`template`,`topic`,`filename`) VALUES (NULL,'$bot_id', '$aiml','$pattern','$thatpattern','$aimltemplate','$topic','admin_added.aiml')";
        $sth = $dbConn->prepare($sql);
        $sth->execute();
        $affectedRows = $sth->rowCount();
        if ($affectedRows > 0) {
            $msg = "AIML added.";
        } else {
            $msg = "There was a problem adding the AIML - no changes made.";
        }
    }
    return $msg;
}
