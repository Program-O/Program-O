<?php
/***************************************
 * http://www.program-o.com
 * PROGRAM O
 * Version: 2.6.*
 * FILE: srai_lookup.php
 * AUTHOR: Elizabeth Perreau and Dave Morton
 * DATE: 05-26-2014
 * DETAILS: Manage entries in the srai_lookup table
 ***************************************/

$post_vars = filter_input_array(INPUT_POST);
$get_vars = filter_input_array(INPUT_GET);

$editScriptTemplate = '<script type="text/javascript" src="scripts/[editScript].js"></script>';
$editScript = 'srai_lookup';
$editScriptTag = str_replace('[editScript]', $editScript, $editScriptTemplate);

$form_vars = array_merge((array)$post_vars, (array)$get_vars);
//exit('Form vars:<pre>' . PHP_EOL . print_r($form_vars, true));
$action = (isset($form_vars['action'])) ? $form_vars['action'] : '';

$group = (isset($get_vars['group'])) ? $get_vars['group'] : 1;

$mainContent = $template->getSection('editSRAIPage');
$msg = (empty($action)) ? '' : $action();

$mainContent = str_replace('[group]', $group, $mainContent);

$topNav         = $template->getSection('TopNav');
$leftNav        = $template->getSection('LeftNav');
$rightNav       = $template->getSection('RightNav');
$main           = $template->getSection('Main');
$navHeader      = $template->getSection('NavHeader');
$FooterInfo     = getFooter();

$errMsgClass    = (!empty($msg)) ? "ShowError" : "HideError";
$errMsgStyle    = $template->getSection($errMsgClass);

$noLeftNav      = '';
$noTopNav       = '';

$noRightNav     = $template->getSection('NoRightNav');

$headerTitle    = 'Actions:';
$pageTitle      = 'My-Program O - SRAI Lookup';
$mainTitle      = 'SRAI Lookup ' . $template->getSection('HelpLink');

$showHelp = $template->getSection('editSRAIShowHelp');
$mainContent = str_replace('[showHelp]', $showHelp, $mainContent);

$countSQL = 'SELECT COUNT(id) FROM srai_lookup WHERE bot_id = :bot_id;';
$params = array(':bot_id' => $bot_id);
$countResult = db_fetch($countSQL, $params, __FILE__, __FUNCTION__, __LINE__);
$row_count = number_format($countResult['COUNT(id)']);

$mainContent = str_replace('[row_count]', $row_count, $mainContent);
$mainContent = str_replace('[bot_name]', $bot_name, $mainContent);

/**
 * Function fillLookup
 *
 * @return string
 */
function fillLookup()
{
    set_time_limit(0);
    global $bot_id;
    // <srai>XEDUCATELANG <star index="1"/> XSPLIT <star index="2"/> XSPLIT <star index="3"/>XSPLIT</srai>
    $starArray = array('~<star[ ]?/>~i', '~<star index="\d+"[ ]?\/>~');
    $msg = '';
    $timeStart = microtime(true);
    // Drop the index on the table srai_lookup to speed things up

    try
    {
        // first, check to see if the index exists
        $testSQL = "SHOW INDEX FROM `srai_lookup` WHERE `Key_name` = 'pattern';";
        $testResult = db_fetch($testSQL, null, __FILE__, __FUNCTION__, __LINE__);

        // if the index exists then drop it. we'll recreate it later
        if (!empty($testResult))
        {
            $dropSQL = 'ALTER TABLE srai_lookup DROP INDEX pattern;';
            $dropResult = db_write($dropSQL, null, false, __FILE__, __FUNCTION__, __LINE__);
        }

        // now delete the rows for the current chatbot, but leave the others alone
        $deleteSQL = 'delete from `srai_lookup` where bot_id=:bot_id;';
        $deleteParams = array(':bot_id' => $bot_id);
        $deleteResult = db_write($deleteSQL, $deleteParams, false, __FILE__, __FUNCTION__, __LINE__);
        $deleteResult = number_format($deleteResult);

        // lastly, check if the aiml table has an index for srai search and if not, add it
        //
        $testSQL = "SHOW INDEX FROM `aiml` WHERE `Key_name` = 'srai_search';";
        $testResult = db_fetch($testSQL, null, __FILE__, __FUNCTION__, __LINE__);

        // if the index exists then drop it, then recreate it
        if (!empty($testResult))
        {
            $dropSQL = 'ALTER TABLE aiml DROP INDEX srai_search; ALTER TABLE `aiml` ADD INDEX `srai_search` (`bot_id`, `pattern`(64));';
            $dropResult = db_write($dropSQL, null, false, __FILE__, __FUNCTION__, __LINE__);
        }
        else
        {
            $dropSQL = 'ALTER TABLE `aiml` ADD INDEX `srai_search` (`bot_id`, `pattern`(64));';
            $dropResult = db_write($dropSQL, null, false, __FILE__, __FUNCTION__, __LINE__);
        }
        $let = $timeStart;
        $now = microtime(true);
        $cet = round($now - $let, 3);
        $let = $now;
        $msg .= "Removed {$deleteResult} existing rows from the srai lookup table, and altered the indexes.";
        $msg .= "Elapsed: {$cet} seconds.<br/>";
    }
    catch (Exception $e) { }

    $searchSQL = "select id, template from aiml where template like '%<srai>%' and bot_id = :bot_id order by id asc;";
    $searchParams = array(
        ':bot_id' => $bot_id
    );
    $searchResult = db_fetchAll($searchSQL, $searchParams, __FILE__, __FUNCTION__, __LINE__);
    $rowCount = count($searchResult);
    $totalRows = number_format($rowCount);
    $now = microtime(true);
    $cet = round($now - $let, 3);
    $let = $now;
    $msg .= ("Found $totalRows AIML categories that contain SRAI calls. Elapsed: $cet seconds.<br>\n");
    //exit();
    $patterns = array(); // array to contain valid patterns, to prevent duplicates

    foreach ($searchResult as $row)
    {
        $id = $row['id'];

        if (!isset($patterns[$id]))
        {
            $patterns[$id] = array();
        }

        $AIMLtemplate = trim($row['template']);

        while (stripos($AIMLtemplate, '<srai>', 0) !== false)
        {
            $start = stripos($AIMLtemplate, '<srai>', 0);
            $end = stripos($AIMLtemplate, '</srai>', $start);
            $len = $end - $start;

            $srai = substr($AIMLtemplate, $start, $len);
            $srai = preg_replace($starArray, '*', $srai); // replace references to <star> with the * wildcard
            $srai = _strtoupper($srai);
            $srai = trim(str_ireplace('<SRAI>', '', $srai));

            if (strstr($srai, '<') == false)
            {
                if (!in_array($srai, $patterns[$id]))
                {
                    $patterns[$id][] = $srai;
                }
            }
            $AIMLtemplate = substr($AIMLtemplate, $end);
        }
    }

    //save_file(_LOG_PATH_ . 'srai_lookup.patterns.txt', print_r($patterns, true));
    foreach ($patterns as $key => $value)
    {
        if (empty($value)) unset($patterns[$key]);
    }

    /** @noinspection SqlDialectInspection */
    $patternSQL = "SELECT id FROM aiml WHERE bot_id = :bot_id AND pattern like :pattern ORDER BY id limit 1;";
    $lookups = array();
    foreach ($patterns as $id => $row)
    {
        if (empty($row)) continue;
        foreach ($row as $index => $pattern)
        {
            $params = array(
                ':bot_id' => $bot_id,
                ':pattern' => $pattern
            );
            $patternResult = db_fetch($patternSQL, $params, __FILE__, __FUNCTION__, __LINE__);
            $template_id = $patternResult['id'];
            $lookups[] = array(
                'bot_id' => $bot_id,
                'pattern' => $pattern,
                'template_id' => $template_id
            );
/*
*/
        }
    }
    foreach ($lookups as $key => $value)
    {
        if (empty($value['template_id'])) {
            unset($lookups[$key]);
        }
    }
    $now = microtime(true);
    $cet = round($now - $let, 3);
    $let = $now;
    $lookupsCount = count($lookups);
    $lookupsCount = number_format($lookupsCount);
    $msg .= ("Loaded {$lookupsCount} AIML categories that need to be added to the lookup table. Elapsed: {$cet} seconds.<br>\n");
    //save_file(_LOG_PATH_ . 'lookups.txt', print_r($lookups, true));
    /** @noinspection SqlDialectInspection */
    // db_multi_insert($tableName, $data,  $file = 'unknown', $function = 'unknown', $line = 'unknown')
    $insertResult = db_multi_insert('srai_lookup', $lookups, true, __FILE__, __FUNCTION__, __LINE__);
    $insertCount = number_format($insertResult);

    // Now put the index back, and remove the temporary one
    /** @noinspection SqlDialectInspection */
    $indexSQL = 'ALTER TABLE `srai_lookup` ADD INDEX `pattern` (`bot_id`, `pattern`(64));ALTER TABLE aiml DROP INDEX srai_search;';
    $indexResult = db_write($indexSQL, null, false, __FILE__, __FUNCTION__, __LINE__);
    $now = microtime(true);
    $cet = round($now - $let, 3);
    $let = $now;
    $msg .= "Inserted $insertCount new entries into the SRAI lookup table. Elapsed: {$cet}<br>\n";
    $timeEnd = microtime(true);
    $elapsed = round($timeEnd - $timeStart, 3);
    $msg .= "Total elapsed time: $elapsed seconds.<br>\n";

    return $msg;
}

function foo($patterns, $lookups)
{
    foreach ($patterns as $id => $row)
    {
        foreach ($row as $pattern)
        {
            if (is_array($pattern))
            {
                $lookups = foo($pattern, $lookups);
            }
            $params = array(
                ':bot_id' => $bot_id,
                ':pattern' => $pattern
            );
            $patternResult = db_fetch($patternSQL, $params, __FILE__, __FUNCTION__, __LINE__);
            $template_id = $patternResult['id'];
            $lookups[] = array(
                ':bot_id' => $bot_id,
                ':pattern' => $pattern,
                ':template_id' => $template_id
            );
        }
    }
    return $lookups;
}