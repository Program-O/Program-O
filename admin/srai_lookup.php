<?php
/***************************************
 * http://www.program-o.com
 * PROGRAM O
 * Version: 2.6.5
 * FILE: srai_lookup.php
 * AUTHOR: Elizabeth Perreau and Dave Morton
 * DATE: 05-26-2014
 * DETAILS: Manage entries in the srai_lookup table
 ***************************************/

$post_vars = filter_input_array(INPUT_POST);
$get_vars = filter_input_array(INPUT_GET);

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

/** @noinspection SqlDialectInspection */
$countSQL = 'SELECT COUNT(id) FROM srai_lookup WHERE bot_id = :bot_id;';
$countSTH = $dbConn->prepare($countSQL);
/** @noinspection PhpUndefinedVariableInspection */
$countSTH->bindValue(':bot_id', $bot_id, PDO::PARAM_INT);
$countSTH->execute();
$countRow = $countSTH->fetch();
$countSTH->closeCursor();
$row_count = number_format($countRow['COUNT(id)']);

$mainContent = str_replace('[row_count]', $row_count, $mainContent);
$mainContent = str_replace('[bot_name]', $bot_name, $mainContent);

/**
 * Function fillLookup
 *
 * @return string
 */
function fillLookup()
{
    global $dbConn;
    // <srai>XEDUCATELANG <star index="1"/> XSPLIT <star index="2"/> XSPLIT <star index="3"/>XSPLIT</srai>
    $starArray = array('~<star[ ]?/>~i', '~<star index="\d+"[ ]?\/>~');
    $msg = '';
    $timeStart = microtime(true);
    // Drop the index on the table srai_lookup to speed things up

    try
    {
        /** @noinspection SqlDialectInspection */
        $dropSQL = '
    ALTER TABLE srai_lookup DROP INDEX pattern;
    TRUNCATE TABLE `srai_lookup`;
    ALTER TABLE `aiml` ADD INDEX `srai_search` (`bot_id`, `pattern`(64));';
        $dropSTH = $dbConn->prepare($dropSQL);
        $dropSTH->execute();
        $dropSTH->closeCursor();
    }
    catch (Exception $e) { }

    $searchSQL = "select id, bot_id, template from aiml where template like '%<srai>%' order by id asc;";
    $es = microtime(true);
    $searchSTH = $dbConn->prepare($searchSQL);
    $searchSTH->execute();
    $searchResult = $searchSTH->fetchAll();
    $searchSTH->closeCursor();
    $rowCount = count($searchResult);
    $totalRows = number_format($rowCount);
    $msg .= ("Found $totalRows AIML categories that contain SRAI calls.<br>\n");
    //exit();
    $patterns = array(); // array to contain valid patterns, to prevent duplicates

    foreach ($searchResult as $row)
    {
        $bot_id = $row['bot_id'];

        if (!isset($patterns[$bot_id]))
        {
            $patterns[$bot_id] = array();
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
            $srai = trim(str_replace('<SRAI>', '', $srai));

            if (strstr($srai, '<') == false)
            {
                if (!in_array($srai, $patterns[$bot_id]))
                {
                    $patterns[$bot_id][] = $srai;
                }
            }
            $AIMLtemplate = substr($AIMLtemplate, $end);
        }
    }

    save_file(_LOG_PATH_ . 'srai_lookup.patterns.txt', print_r($patterns, true));

    /** @noinspection SqlDialectInspection */
    $patternSQL = 'SELECT id FROM aiml WHERE pattern = :pattern AND bot_id = :bot_id ORDER BY id limit 1;';
    $patternSTH = $dbConn->prepare($patternSQL);
    $lookups = array();

    foreach ($patterns as $id => $row)
    {
        foreach ($row as $pattern)
        {
            $patternSTH->bindValue(':pattern', $pattern);
            $patternSTH->bindValue(':bot_id', $id);
            $patternSTH->execute();

            $patternResult = $patternSTH->fetch();
            $patternSTH->closeCursor();
            $template_id = $patternResult['id'];
            $lookups[] = array(
                ':bot_id' => $id,
                ':pattern' => $pattern,
                ':template_id' => $template_id
            );
        }
    }
    /** @noinspection SqlDialectInspection */
    $insertSQL = "INSERT INTO srai_lookup (id, bot_id, pattern, template_id) VALUES (null, :bot_id, :pattern, :template_id);";
    $insertSTH = $dbConn->prepare($insertSQL);
    $insertCount = 0;

    foreach ($lookups as $params)
    {
        if (empty($params[':template_id'])) {
            continue;
        }

        $insertSTH->execute($params);
        $insertSTH->closeCursor();
        $insertCount++;
    }

    // Now put the index back
    /** @noinspection SqlDialectInspection */
    $indexSQL = 'ALTER TABLE `srai_lookup` ADD INDEX `pattern` (`bot_id`, `pattern`(64)); ALTER TABLE aiml DROP INDEX srai_search;';
    $indexSTH = $dbConn->prepare($indexSQL);
    $indexSTH->execute();
    $indexSTH->closeCursor();

    $insertCount = number_format($insertCount);
    $msg .= "Inserted $insertCount new entries into the SRAI lookup table!<br>\n";
    $timeEnd = microtime(true);
    $elapsed = $timeEnd - $timeStart;
    $elapsed = round($elapsed, 3);
    $msg .= "Elapsed time: $elapsed seconds.<br>\n";

    return $msg;
}