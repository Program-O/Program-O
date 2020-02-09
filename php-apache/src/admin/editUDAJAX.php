<?php

/* * *************************************
* http://www.program-o.com
* PROGRAM O
* Version: 2.6.11
* FILE: editUDAiml.php
* AUTHOR: Elizabeth Perreau and Dave Morton
* DATE: 05-26-2014
* DETAILS: Search the aiml_userdefined table of the DB for desired categories
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
ini_set('error_log', _LOG_PATH_ . 'editUDAJAX.error.log');
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
        $sql = "DELETE FROM `aiml_userdefined` WHERE `id` = :id LIMIT 1";
        $params = array(':id' => $id);
        $affectedRows = db_write($sql, $params, false, __FILE__, __FUNCTION__, __LINE__);

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
    global $bot_id, $form_vars, $group;
    save_file(_LOG_PATH_ . 'editUDajax.runSearch.form_vars.txt', print_r($form_vars, true));
    extract($form_vars);

    $search_fields = array('user_id', 'pattern', 'template', 'thatpattern');
    $searchTerms = array();
    $searchParams = array(':bot_id' => $bot_id);
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
    $countSQL = "SELECT count(id) FROM `aiml_userdefined` WHERE `bot_id` = :bot_id AND ($searchTerms);";
    $count = db_fetch($countSQL, $searchParams, __FILE__, __FUNCTION__, __LINE__);
    $total = $count['count(id)'];

    /** @noinspection SqlDialectInspection */
    /** @noinspection PhpUndefinedVariableInspection */
    $sql = "SELECT id, user_id, pattern, thatpattern, template FROM `aiml_userdefined` " . "WHERE `bot_id` = :bot_id AND ($searchTerms) order by $orderBy limit $start, $length;"; //
    $debugSQL = db_parseSQL($sql, $searchParams);
    file_put_contents(_LOG_PATH_ . 'editUDAJAX.sql.txt', $debugSQL);
    $result = db_fetchAll($sql, $searchParams, __FILE__, __FUNCTION__, __LINE__);
    save_file(_LOG_PATH_ . 'editUDajax.' . __FUNCTION__ . '.result.txt', print_r($result, true));

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
        $row['pattern'] = htmlentities($row['pattern'],ENT_NOQUOTES,'UTF-8');
        $row['user_id'] = htmlentities($row['user_id'],ENT_NOQUOTES,'UTF-8');
        $row['thatpattern'] = htmlentities($row['thatpattern'],ENT_NOQUOTES,'UTF-8');
        $row['template'] = htmlentities($row['template'],ENT_NOQUOTES,'UTF-8');
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
    global $form_vars;

    $template = trim($form_vars['template']);
    $pattern = _strtoupper(trim($form_vars['pattern']));
    $thatpattern = _strtoupper(trim($form_vars['thatpattern']));
    $user_id = _strtoupper(trim($form_vars['user_id']));
    $id = trim($form_vars['id']);

    if (($template == "") || ($pattern == "") || ($id == ""))
    {
        $msg = 'Please make sure you have entered a user input and bot response ';
    }
    else
    {
        /** @noinspection SqlDialectInspection */
        $sql = "UPDATE `aiml_userdefined` SET `pattern`=?,`thatpattern`=?,`template`=?,`user_id`=? WHERE `id`=? LIMIT 1";
        $affectedRows = db_write($sql, $params, false, __FILE__, __FUNCTION__, __LINE__);
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
    global $msg, $form_vars, $bot_id;

    $aiml = "<category><pattern>[pattern]</pattern>[thatpattern]<template>[template]</template></category>";
    $aimltemplate = trim($form_vars['template']);

    $pattern = trim($form_vars['pattern']);
    $pattern = _strtoupper($pattern);

    $thatpattern = trim($form_vars['thatpattern']);
    $thatpattern = _strtoupper($thatpattern);

    $aiml = str_replace('[pattern]', $pattern, $aiml);
    $aiml = (empty ($thatpattern)) ? str_replace('[thatpattern]', "<that>$thatpattern</that>", $aiml) : $aiml;
    $aiml = str_replace('[template]', $aimltemplate, $aiml);

    $user_id = trim($form_vars['user_id']);
    $user_id = _strtoupper($user_id);

    if (($pattern == "") || ($aimltemplate == ""))
    {
        $msg = 'You must enter a user input and bot response.';
    }
    else
    {
        /** @noinspection SqlDialectInspection */
        $sql = 'INSERT INTO `aiml_userdefined` (`id`,`bot_id`,`user_id`, `pattern`,`thatpattern`,`template`) VALUES (NULL, :bot_id, :user_id, :pattern, :thatpattern, :aimltemplate);';
        $params = array(
            ':bot_id' => $bot_id,
            ':user_id' => $user_id,
            ':pattern' => $pattern,
            ':thatpattern' => $thatpattern,
            ':template' => $template,
        );

        $affectedRows = db_write($sql, $params, false, __FILE__, __FUNCTION__, __LINE__);

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


