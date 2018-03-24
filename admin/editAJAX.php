<?php

/* * *************************************
* http://www.program-o.com
* PROGRAM O
* Version: 2.6.*
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
require_once(_ADMIN_PATH_ . 'allowedPages.php');

ini_set('log_errors', true);
ini_set('error_log', _LOG_PATH_ . 'editAJAX.error.log');
ini_set('html_errors', false);
ini_set('display_errors', false);
set_error_handler('handle_errors', E_ALL | E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE);

$session_name = 'PGO_Admin';
session_name($session_name);
session_start();
$allowedVars = $allowed_pages['editAJAX'];
$form_vars = clean_inputs($allowedVars);
$bot_id = (isset ($_SESSION['poadmin']['bot_id'])) ? $_SESSION['poadmin']['bot_id'] : 1;

if (empty ($_SESSION) || !isset ($_SESSION['poadmin']['uid']) || $_SESSION['poadmin']['uid'] == "")
{
    exit (json_encode(array('error' => "No session found")));
}

// Open the DB
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
        $sql = "DELETE FROM `aiml` WHERE `id` = :id LIMIT 1";
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
    extract($form_vars);
    $now = time();
    $search_fields = array('topic', 'filename', 'pattern', 'template', 'thatpattern');
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
            $srchValue = $column['search']['value'];
            $tmpSearch = htmlspecialchars_decode($srchValue, ENT_QUOTES);
            $sr = array(
                "'" => "\'",
                "<" => "\<",
                ">" => "\>",
            );
            if (strstr($tmpSearch, "'")) $tmpSearch = str_replace(array_keys($sr),array_values($sr), $tmpSearch);
            $tmpName = $column['data'];
            $tmpSearch = str_replace('_', '\\_', $tmpSearch);
            $tmpSearch = str_replace('%', '\\$', $tmpSearch);

            $addWhere = "`{$tmpName}` like :{$tmpName}";
            $searchParams[":{$tmpName}"] = "%{$tmpSearch}%";
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
    $countSQL = "SELECT count(id) FROM `aiml` WHERE `bot_id` = :bot_id AND ($searchTerms);";
    $count = db_fetch($countSQL, $searchParams, __FILE__, __FUNCTION__, __LINE__);
    $total = $count['count(id)'];

    /** @noinspection SqlDialectInspection */
    /** @noinspection PhpUndefinedVariableInspection */
    $sql = "SELECT id, pattern, thatpattern, template, topic, filename FROM `aiml` WHERE `bot_id` = :bot_id AND ($searchTerms) order by $orderBy limit $start, $length;";
    $debugSQL = db_parseSQL($sql, $searchParams);
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
        $row['pattern'] = htmlentities($row['pattern'],ENT_NOQUOTES,'UTF-8');
        $row['topic'] = htmlentities($row['topic'],ENT_NOQUOTES,'UTF-8');
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
    //if(ERROR_DEBUGGING) trigger_error('Form vars:' . print_r($form_vars, true));
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
        $sql = 'UPDATE `aiml` SET
            `pattern`= :pattern,
            `thatpattern`= :thatpattern,
            `template`= :template,
            `topic`= :topic,
            `filename`= :filename
             WHERE `id`= :id LIMIT 1;';

        $params = array(
            ':pattern' =>$pattern,
            ':thatpattern' =>$thatpattern,
            ':template' =>$template,
            ':topic' =>$topic,
            ':filename' =>$filename,
            ':id' =>$id,
        );

        $affectedRows = db_write($sql, $params, false, __FILE__, __FUNCTION__, __LINE__);

        if ($affectedRows > 0)
        {
            $msg = 'AIML Updated.';
        }
        else {
            $msg = 'Unable to update the AIML - no changes made.<br/>This is most likely because no changes were detected.';
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

    $aimltemplate = trim($form_vars['template']);

    $pattern = trim($form_vars['pattern']);
    $pattern = _strtoupper($pattern);

    $thatpattern = trim($form_vars['thatpattern']);
    $thatpattern = _strtoupper($thatpattern);

    $topic = trim($form_vars['topic']);
    $topic = _strtoupper($topic);

    $filename = trim($form_vars['filename']);
    if (empty($filename)) $filename = 'admin_added.aiml';

    if (($pattern == "") || ($aimltemplate == ""))
    {
        $msg = 'You must enter a user input and bot response.';
    }
    else
    {
        /** @noinspection SqlDialectInspection */
        $sql = "INSERT INTO `aiml` (`id`,`bot_id`, `pattern`, `thatpattern`, `template`, `topic`, `filename`)
                             values(NULL, :bot_id, :pattern,  :thatpattern,  :template,  :topic,  :filename)";
        $params = array(
            ':bot_id' =>$bot_id,
            ':pattern' =>$pattern,
            ':thatpattern' =>$thatpattern,
            ':template' =>$aimltemplate,
            ':topic' =>$topic,
            ':filename' =>$filename,
        );
        //trigger_error(db_parseSQL($sql, $params));
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
