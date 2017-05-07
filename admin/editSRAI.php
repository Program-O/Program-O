<?php

/***************************************
* http://www.program-o.com
* PROGRAM O
* Version: 2.6.5
* FILE: editSRAI.php
* AUTHOR: Elizabeth Perreau and Dave Morton
* DATE: 05-26-2014
* DETAILS: Search the AIML table of the DB for desired categories
***************************************/

$thisFile = __FILE__;
/** @noinspection PhpIncludeInspection */
require_once('../config/global_config.php');
/** @noinspection PhpIncludeInspection */
require_once(_LIB_PATH_ . 'PDO_functions.php');
/** @noinspection PhpIncludeInspection */
require_once(_LIB_PATH_ . 'error_functions.php');
/** @noinspection PhpIncludeInspection */
require_once(_LIB_PATH_ . 'misc_functions.php');

$e_all = defined('E_DEPRECATED') ? E_ALL & ~E_DEPRECATED : E_ALL;

error_reporting($e_all);
ini_set('log_errors', true);
ini_set('error_log', _LOG_PATH_ . 'editSRAI.error.log');
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
        exit (insertSRAI());
        break;
    case 'update':
        exit (updateSRAI());
        break;
    case 'del':
        exit (delSRAI($form_vars['id']));
        break;
    default:
        exit (runSearch());
}

/**
 * Function delSRAI
 *
 * * @param $id
 * @return string
 */
function delSRAI($id)
{
    if ($id != "")
    {
        /** @noinspection SqlDialectInspection */
        $sql = "DELETE FROM `srai_lookup` WHERE `id` = '$id' LIMIT 1";
        $affectedRows = db_write($sql, null, false, __FILE__, __FUNCTION__, __LINE__);

        if ($affectedRows == 0)
        {
            $msg = 'Error SRAI couldn\'t be deleted - no changes made.</div>';
        }
        else {
            $msg = 'Lookup entry has been deleted.';
        }
    }
    else {
        $msg = 'Error" Lookup entry couldn\'t be deleted - no changes made.';
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

    //file_put_contents(_LOG_PATH_ . "editSRAI.runSearch.form_vars.txt", print_r($form_vars, true));
    extract($form_vars);

    $search_fields = array('id', 'bot_id', 'pattern', 'template_id');
    $searchTerms = array();
    $searchParams = array($bot_id);
    $where = array();

    // parse column searches
    foreach ($columns as $index => $column)
    {
        if ($column['data'] == 'Delete')
        {
            $column['data'] = 'id';
        }

        if (!empty($column['search']['value']))
        {
            $tmpSearch = $column['search']['value'];
            $tmpSearch = str_replace('_', '\\_', $tmpSearch);
            $tmpSearch = str_replace('%', '\\$', $tmpSearch);
            $tmpName = $column['data'];
            $addWhere = "`$tmpName` like '%$tmpSearch%'";
            $where[] = $addWhere;
        }
    }

    $searchTerms = (!empty($where)) ? implode(' AND ', $where) : 'TRUE';

    // get search order
    $oBy = array();

    foreach ($order as $row)
    {
        $name = $columns[$row['column']]['data'];

        if ($name == 'Delete') {
            $name = 'id';
        }

        $dir = $row['dir'];
        $tmpOrder = "$name $dir";
        $oBy[] = $tmpOrder;
    }

    $orderBy = implode(', ', $oBy);

    if (empty($oBy))
    {
        $orderBy = 'id';
    }

    /** @noinspection SqlDialectInspection */
    $countSQL = "SELECT count(id) FROM `srai_lookup` WHERE `bot_id` = ? AND ($searchTerms);";

    $count = db_fetch($countSQL, $searchParams, __FILE__, __FUNCTION__, __LINE__);
    $total = $count['count(id)'];

    /** @noinspection SqlDialectInspection */
    $sql = "SELECT id, bot_id, pattern, template_id FROM `srai_lookup` " . "WHERE `bot_id` = $bot_id AND ($searchTerms) ORDER BY $orderBy limit $start, $length;";
    //file_put_contents(_LOG_PATH_ . "editSRAI.runSearch.sql.txt", print_r($sql, true));
    $result = db_fetchAll($sql, $searchParams, __FILE__, __FUNCTION__, __LINE__);

    $out = array(
        'draw' => $draw,
        'recordsTotal' => $total,
        'recordsFiltered' => $total,
        'data' => array()
    );
    //file_put_contents(_LOG_PATH_ . "editSRAI.runSearch.out1.txt", print_r($out, true));

    if (empty($result))
    {
        exit(json_encode($out));
    }

    foreach ($result as $index => $row)
    {
        $row['template_id'] = htmlentities($row['template_id']);
        $row['DT_RowId'] = $row['id'];
        $out['data'][] = $row;
    }
    //file_put_contents(_LOG_PATH_ . "editSRAI.runSearch.out.txt", print_r($out, true));
    return json_encode($out);
}

/**
 * Function updateSRAI
 *
 * @return string
 */
function updateSRAI()
{
    global $form_vars, $dbConn;

    $id = trim($form_vars['id']);
    $bot_id = trim($form_vars['bot_id']);
    $pattern = _strtoupper(trim($form_vars['pattern']));
    $template_id = trim($form_vars['template_id']);

    if (empty($bot_id) || empty($pattern) || empty($template_id))
    {
        $msg = 'Please make sure that no fields are empty.([fields])';
        $fArray = array();

        switch (true)
        {
            /** @noinspection PhpMissingBreakStatementInspection */
            case (empty($bot_id)):
                $fields[] = 'bot_id';
            /** @noinspection PhpMissingBreakStatementInspection */
            case (empty($pattern)):
                $fields[] = 'pattern';
            case (empty($template_id)):
                $fields[] = 'template_id';
        }

        $fields = implode(', ', $fArray);
        $msg = str_replace('[fields]', $fields, $msg);
    }
    else
    {
        $params = array(
            ':id' => $id,
            ':bot_id' => $bot_id,
            ':pattern' => $pattern,
            ':template_id' => $template_id
        );

        /** @noinspection SqlDialectInspection */
        $sql = "UPDATE `srai_lookup` SET `bot_id` = :bot_id, `pattern` = :pattern, `template_id` = :template_id WHERE `id` = :id;";
        $sth = $dbConn->prepare($sql);

        try {
            $sth->execute($params);
        }
        catch (Exception $e)
        {
            return 'Something went wrong! Error: ' . $e->getMessage();
        }

        $affectedRows = $sth->rowCount();

        if ($affectedRows > 0)
        {
            $msg = 'SRAI Updated.';
        }
        else {
            $msg = 'There was an error updating the SRAI - no changes made.';
        }
    }

    return $msg;
}

/**
 * Function insertSRAI
 *
 * @return string
 */
function insertSRAI()
{
    //db globals
    global $msg, $form_vars, $dbConn;

    $bot_id = trim($form_vars['bot_id']);
    $pattern = trim($form_vars['pattern']);
    $pattern = _strtoupper($pattern);
    $template_id = trim($form_vars['template_id']);

    /** @noinspection SqlDialectInspection */
    $sql = 'INSERT INTO `srai_lookup` (`id`, `bot_id`, `pattern`, `template_id`) VALUES (NULL, :bot_id, :pattern, :template_id);';
    $params = array(
        ':bot_id' => $bot_id,
        ':pattern' => $pattern,
        ':template_id' => $template_id
    );

    if ((empty($bot_id) || empty($pattern) || empty($template_id)))
    {
        $msg = 'No fields can be empty.';
    }
    else
    {
        //$sth = $dbConn->prepare('INSERT INTO `srai_lookup` (`id`,`bot_id` `pattern`,`template_id`) VALUES (NULL, :bot_id, :pattern, :template_id);');
        $sth = $dbConn->prepare($sql);

        try {
            $sth->execute($params);
        }
        catch (Exception $e) {
            return 'Something went wrong! Error: ' . $e->getMessage();
        }

        $affectedRows = $sth->rowCount();

        if ($affectedRows > 0)
        {
            $msg = "SRAI added.";
        }
        else {
            $msg = "SRAI not updated - no changes made.";
        }
    }

    return $msg;
}

