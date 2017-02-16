<?php

/* * *************************************
* http://www.program-o.com
* PROGRAM O
* Version: 2.6.4
* FILE: editAiml.php
* AUTHOR: Elizabeth Perreau and Dave Morton
* DATE: 05-26-2014
* DETAILS: Search the AIML table of the DB for desired categories
* ************************************* */
$thisFile = __FILE__;

/** @noinspection PhpIncludeInspection */
require_once('../config/global_config.php');
/** @noinspection PhpIncludeInspection */
require_once(_LIB_PATH_ . 'PDO_functions.php');
/** @noinspection PhpIncludeInspection */
require_once(_LIB_PATH_ . 'error_functions.php');
/** @noinspection PhpIncludeInspection */
require_once(_LIB_PATH_ . 'misc_functions.php');

ini_set('log_errors', true);
ini_set('error_log', _LOG_PATH_ . 'editAJAX.error.log');
ini_set('html_errors', false);
ini_set('display_errors', false);

$session_name = 'PGO_Admin';
session_name($session_name);
session_start();

$form_vars = clean_inputs();
$bot_id = (isset ($_SESSION['poadmin']['bot_id'])) ? $_SESSION['poadmin']['bot_id'] : 1;

if (empty ($_SESSION) || !isset ($_SESSION['poadmin']['uid']) || $_SESSION['poadmin']['uid'] == "")
{
    error_log('Session vars: ' . print_r($_SESSION, true), 3, _LOG_PATH_ . 'session.txt');
    exit (json_encode(array('error' => "No session found")));
}

// Open the DB
$dbConn = db_open();
$action = (isset($form_vars['action'])) ? $form_vars['action'] : 'runSearch';

switch ($action)
{
    case 'add':
        exit (insertAIML());
        break;
    case 'update':
        exit (updateAIML());
        break;
    case 'del':
        exit (delAIML($form_vars['id']));
        break;
    default:
        exit (runSearch());
}

/**
 * Function delAIML
 *
 * @param $id
 * @return string
 */
function delAIML($id)
{
    if ($id != "")
    {
        /** @noinspection SqlDialectInspection */
        $sql = "DELETE FROM `aiml` WHERE `id` = '$id' LIMIT 1";
        $affectedRows = db_write($sql, null, false, __FILE__, __FUNCTION__, __LINE__);

        if ($affectedRows == 0)
        {
            $msg = 'Error AIML couldn\'t be deleted - no changes made.</div>';
        }
        else {
            $msg = 'AIML has been deleted.';
        }
    }
    else {
        $msg = 'Error AIML couldn\'t be deleted - no changes made.';
    }

    return $msg;
}

/**
 * Function runSearch
 *
 * @return mixed|string
 */
function runSearch()
{
    global $bot_id, $form_vars, $dbConn, $group;
    extract($form_vars);

    $search_fields = array('topic', 'filename', 'pattern', 'template', 'thatpattern');
    $searchTerms = array();
    $searchParams = array($bot_id);
    $where = array();

    // parse column searches
    /** @noinspection PhpUndefinedVariableInspection */
    foreach ($columns as $index => $column)
    {
        if ($column['data'] == 'Delete') {
            $column['data'] = 'id';
        }

        if (!empty($column['search']['value']))
        {
            $tmpSearch = $column['search']['value'];
            $tmpSearch = str_replace('_', '\\_', $tmpSearch);
            $tmpSearch = str_replace('%', '\\$', $tmpSearch);

            //if ($tmpSearch == '_') $tmpSearch = '\\_';
            $tmpName = $column['data'];
            $addWhere = "`$tmpName` like '%$tmpSearch%'";
            $where[] = $addWhere;
        }
    }

    $searchTerms = (!empty($where)) ? implode(' AND ', $where) : 'TRUE';

    // get search order
    $oBy = array();

    /** @noinspection PhpUndefinedVariableInspection */
    foreach ($order as $row)
    {
        $name = $columns[$row['column']]['data'];
        if ($name == 'Delete') $name = 'id';
        $dir = $row['dir'];
        $tmpOrder = "$name $dir";
        $oBy[] = $tmpOrder;
    }

    $orderBy = implode(', ', $oBy);

    if (empty($oBy)) {
        $orderBy = 'id';
    }

    /** @noinspection SqlDialectInspection */
    $countSQL = "SELECT count(id) FROM `aiml` WHERE `bot_id` = ? AND ($searchTerms);";
    $count = db_fetch($countSQL, $searchParams, __FILE__, __FUNCTION__, __LINE__);
    $total = $count['count(id)'];

    /** @noinspection SqlDialectInspection */
    /** @noinspection PhpUndefinedVariableInspection */
    $sql = "SELECT id, pattern, thatpattern, template, topic, filename FROM `aiml` " . "WHERE `bot_id` = $bot_id AND ($searchTerms) order by $orderBy limit $start, $length;";
    $result = db_fetchAll($sql, $searchParams, __FILE__, __FUNCTION__, __LINE__);

    /** @noinspection PhpUndefinedVariableInspection */
    $out = array(
        'draw' => $draw,
        'recordsTotal' => $total,
        'recordsFiltered' => $total,
        'data' => array()
    );

    if (empty($result))
    {
        exit(json_encode($out));
    }

    foreach ($result as $index => $row)
    {
        $row['template'] = htmlentities($row['template']);
        $row['DT_RowId'] = $row['id'];
        $out['data'][] = $row;
    }

    return json_encode($out);
}

/**
 * Function updateAIML
 * @return string
 * @throws Exception
 */
function updateAIML()
{
    global $form_vars, $dbConn;

    $template = trim($form_vars['template']);
    $filename = trim($form_vars['filename']);
    $pattern = _strtoupper(trim($form_vars['pattern']));
    $thatpattern = _strtoupper(trim($form_vars['thatpattern']));
    $topic = _strtoupper(trim($form_vars['topic']));
    $id = trim($form_vars['id']);

    if (($template == "") || ($pattern == "") || ($id == ""))
    {
        $msg = 'Please make sure you have entered a user input and bot response ';
    }
    else
    {
        /** @noinspection SqlDialectInspection */
        $sql = "UPDATE `aiml` SET `pattern`=?,`thatpattern`=?,`template`=?,`topic`=?,`filename`=? WHERE `id`=? LIMIT 1";
        $sth = $dbConn->prepare($sql);

        try
        {
            $sth->execute(array($pattern, $thatpattern, $template, $topic, $filename, $id));
        }
        catch (Exception $e)
        {
            header("HTTP/1.0 500 Internal Server Error");
            throw ($e);
        }

        $affectedRows = $sth->rowCount();

        if ($affectedRows > 0)
        {
            $msg = 'AIML Updated.';
        }
        else {
            $msg = 'There was an error updating the AIML - no changes made.';
        }
    }
    return $msg;
}

/**
 * Function insertAIML
 *
 * @throws Exception
 * @return string
 */
function insertAIML()
{
    //db globals
    global $msg, $form_vars, $dbConn, $bot_id;

    $aiml = "<category><pattern>[pattern]</pattern>[thatpattern]<template>[template]</template></category>";
    $aimltemplate = trim($form_vars['template']);

    $pattern = trim($form_vars['pattern']);
    $pattern = _strtoupper($pattern);

    $thatpattern = trim($form_vars['thatpattern']);
    $thatpattern = _strtoupper($thatpattern);

    $aiml = str_replace('[pattern]', $pattern, $aiml);
    $aiml = (empty ($thatpattern)) ? str_replace('[thatpattern]', "<that>$thatpattern</that>", $aiml) : $aiml;
    $aiml = str_replace('[template]', $aimltemplate, $aiml);

    $topic = trim($form_vars['topic']);
    $topic = _strtoupper($topic);

    if (($pattern == "") || ($aimltemplate == ""))
    {
        $msg = 'You must enter a user input and bot response.';
    }
    else
    {
        /** @noinspection SqlDialectInspection */
        $sth = $dbConn->prepare("INSERT INTO `aiml` (`id`,`bot_id`, `aiml`, `pattern`,`thatpattern`,`template`,`topic`,`filename`) " . "VALUES (NULL, ?, ?, ?, ?, ?, ?,'admin_added.aiml')");

        try {
            $sth->execute(array($bot_id, $aiml, $pattern, $thatpattern, $aimltemplate, $topic));
        }
        catch (Exception $e)
        {
            header("HTTP/1.0 500 Internal Server Error");
            throw ($e);
        }

        $affectedRows = $sth->rowCount();

        if ($affectedRows > 0)
        {
            $msg = "AIML added.";
        }
        else {
            $msg = "AIML not updated - no changes made.";
        }
    }

    return $msg;
}

function clean_inputs($options = null)
{
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

