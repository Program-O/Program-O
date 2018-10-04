<?php
/***************************************
 * http://www.program-o.com
 * PROGRAM O
 * Version: 2.6.11
 * FILE: download.php
 * AUTHOR: Elizabeth Perreau and Dave Morton
 * DATE: 12-08-2014
 * DETAILS: Provides the ability to download a chatbot's AIML filesm either in AIML or SQL format.
 ***************************************/

set_time_limit(0);
$content = '';
$status = '';
//assume ZipArchive is enabled by default
$ZIPenabled = class_exists('ZipArchive');
// $ZIPenabled = false; // debugging and testing - comment out when complete
$downloadLinks = ''; //download links for single files
$dlLinkTemplate = '<a class="dlLink" href="file.php?singlefile=[filename]" target="_blank">[filename]</a>';

/** @noinspection PhpUndefinedVariableInspection */
$bot_id = ($bot_id == 'new') ? 0 : $bot_id;
$referer = filter_input(INPUT_SERVER, 'HTTP_REFERER', FILTER_SANITIZE_URL);
$upperScripts = $template->getSection('UpperScripts');

$allowed_get_vars = $allowed_pages['download'];
$form_vars = clean_inputs($allowed_get_vars);

//trigger_error('Test error: form vars =' . print_r($form_vars, true), E_USER_NOTICE);

if (!empty($form_vars['type']) && !empty($form_vars['filenames']))
{
    $type = $form_vars['type'];

    /** @noinspection PhpUndefinedVariableInspection */
    $zipFilename = "$bot_name.$type.zip";

    if (!isset($form_vars['filenames']))
    {
        $msg .= 'No files were selected for download. Please select at least one file.';
    }
    else
    {
        $fileNames = $form_vars['filenames'];

        if($ZIPenabled)
        {
            // clear out old zip file if it exists, to prepare for the new one.
            if(file_exists(_DOWNLOAD_PATH_ . $zipFilename)) unlink(_DOWNLOAD_PATH_ . $zipFilename);
            $zip = new ZipArchive();
            $success = $zip->open(_DOWNLOAD_PATH_ . $zipFilename, ZipArchive::CREATE);

            if ($success === true)
            {
                foreach ($fileNames as $filename)
                {
                    $curZipContent = ($type == 'SQL') ? getSQLByFileName($filename) : getAIMLByFileName($filename);
                    $filename = ($type == 'SQL') ? str_replace('.aiml', '.sql', $filename) : $filename;
                    $zip->addFromString("{$bot_name}.{$filename}", $curZipContent);
                }

                $zip->close();
                $_SESSION['send_file'] = $zipFilename;
                $_SESSION['referer'] = $referer;

                header("Refresh: 5; url=file.php");

                $msg .= "The file $zipFilename is being processed. If the download doesn't start within a few seconds, please click <a href=\"file.php\">here</a>.\n";
            }
        }else{
            //lets download all selected files individually
            $msg .= 'The PHP ZipArchive class is not available on this server, so Zip files cannot be downloaded. However, individual AIML files can be downloaded. We apologise for the inconvenience. <br/><br/>';
            foreach ($fileNames as $filename)
            {
                $curFileContent = ($type == 'SQL') ? getSQLByFileName($filename) : getAIMLByFileName($filename);
                $filename = ($type == 'SQL') ? str_replace('.aiml', '.sql', $filename) : $filename;
                //delete old aiml files
                if(file_exists(_DOWNLOAD_PATH_ . $filename)) unlink(_DOWNLOAD_PATH_ . $filename);
                file_put_contents(_DOWNLOAD_PATH_ . $filename, $curFileContent);
                //get the download links
                $downloadLinks .= str_replace('[filename]', trim($filename), $dlLinkTemplate);
            }
            $fnList = implode(', ', $fileNames);
            $fnList = replace_last(', ', ' and ', $fnList);
            $msg .= "The file(s) <b> $fnList </b> have been processed. Click on the filename(s) below to download individually.<br/>$downloadLinks";
        }
    }
}

$content .= renderMain();
$showHelp = $template->getSection('DownloadShowHelp');
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
$pageTitle = "My-Program O - Download AIML files";

$mainContent = $content;
$mainTitle = "Download AIML files for the bot named  $bot_name [helpLink]";
$mainContent = str_replace('[showHelp]', $showHelp, $mainContent);
$mainContent = str_replace('[status]', $status, $mainContent);
$mainTitle = str_replace('[helpLink]', $template->getSection('HelpLink'), $mainTitle);

/**
 * Function getAIMLByFileName
 *
 * @param $filename
 * @return string
 */
function getAIMLByFileName($filename)
{
    if ($filename == 'null')
    {
        return "You need to select a file to download.";
    }

    global $botmaster_name, $charset, $bot_id;

    $bmnLen = 51 - strlen($botmaster_name);
    $bmnPadding = str_pad('', $bmnLen);
    $categoryTemplate = '<category><pattern>[pattern]</pattern>[that]<template>[template]</template></category>';

    $cleanedFilename = $filename;

    $topicArray = array();
    $curPath = dirname(__FILE__);
    chdir($curPath);

    $fileContent = file_get_contents(_ADMIN_PATH_ . 'AIML_Header.dat');
    $fileContent = str_replace('[year]', date('Y'), $fileContent);
    $fileContent = str_replace('[charset]', $charset, $fileContent);
    $fileContent = str_replace('[bm_name]', $botmaster_name . $bmnPadding, $fileContent);

    $pad_len = 60 - strlen($cleanedFilename);
//  NOTE: the value 60 in the previous line is the number of characters from the `[` to the first '-'
//  in the comment that contains the filename. This makes the comment the same width as the others
//  in the AIML file.
    $space_padding = str_pad('', $pad_len, ' ');
    $fileContent = str_replace('[fileName]', $cleanedFilename . $space_padding, $fileContent);

    $curDate = date('m-d-Y', time());
    $cdLen = strlen($curDate);
    $curDateSearch = str_pad('[curDate]', $cdLen);

    $fileContent = str_replace($curDateSearch, $curDate, $fileContent);

    /** @noinspection SqlDialectInspection */
    $sql = "SELECT DISTINCT topic FROM aiml WHERE filename LIKE :cleanedFilename and bot_id = :bot_id;";
        $params = array(
            ':cleanedFilename' => $cleanedFilename,
            ':bot_id' => $bot_id
        );
    $result = db_fetchAll($sql, $params, __FILE__, __FUNCTION__, __LINE__);

    foreach ($result as $row)
    {
        $topicArray[] = $row['topic'];
    }

    foreach ($topicArray as $topic)
    {
        if (!empty ($topic))
        {
            $fileContent .= "<topic name=\"$topic\">\n";
        }

        /** @noinspection SqlDialectInspection */
        $sql = "SELECT pattern, thatpattern, template FROM aiml WHERE topic LIKE :topic AND filename LIKE :cleanedFilename and bot_id = :bot_id;";
        $params = array(
            ':topic' => $topic,
            ':cleanedFilename' => $cleanedFilename,
            ':bot_id' => $bot_id
        );
        $result = db_fetchAll($sql, $params, __FILE__, __FUNCTION__, __LINE__);

        foreach ($result as $row)
        {
            $pattern = _strtoupper($row['pattern']);

            $template = str_replace("\r\n", '', $row['template']);
            $template = str_replace("\n", '', $row['template']);

            $newLine = str_replace('[pattern]', $pattern, $categoryTemplate);
            $newLine = str_replace('[template]', $template, $newLine);

            $that = (!empty ($row['thatpattern'])) ? '<that>' . $row['thatpattern'] .
                '</that>' : '';

            $newLine = str_replace('[that]', $that, $newLine);
            $fileContent .= "$newLine\n";
        }

        if (!empty ($topic))
        {
            $fileContent .= "</topic>\n";
        }
    }

    $fileContent .= "\r\n</aiml>\r\n";

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML(trim($fileContent));

    $fileContent = $dom->saveXML();

    $outFile = ltrim($fileContent, "\n\r\n");
    $outFile = (IS_MB_ENABLED) ? mb_convert_encoding($outFile, 'UTF-8') : $outFile;

    return $outFile;
}

/**
 * Function getSQLByFileName
 *
 * * @param $filename
 * @return string
 */
function getSQLByFileName($filename)
{
    global $dbn, $botmaster_name, $dbh, $bot_id;

    $curPath = dirname(__FILE__);
    chdir($curPath);
    $dbFilename = $filename;
    $filename = str_ireplace('.aiml', '.sql', $filename);
    $newLine = "    ([bot_id],'[pattern]','[thatpattern]','[template]','[topic]','[filename]'),";
    $phpVer = phpversion();
    $cleanedFilename = $dbFilename;
    $topicArray = array();

    /** @noinspection SqlDialectInspection */
    $sql = "SELECT * FROM aiml WHERE filename LIKE :cleanedFilename and bot_id = :bot_id ORDER BY id ASC;";
    $params = array(':cleanedFilename' => $cleanedFilename, ':bot_id' => $bot_id);
    $fileContent = file_get_contents('SQL_Header.dat');

    $fileContent = str_replace('[botmaster_name]', $botmaster_name, $fileContent);
    $fileContent = str_replace('[host]', $dbh, $fileContent);
    $fileContent = str_replace('[dbn]', $dbn, $fileContent);
    $fileContent = str_replace('[sql]', $sql, $fileContent);
    $fileContent = str_replace('[phpVer]', $phpVer, $fileContent);

    $curDate = date('m-d-Y h:j:s A', time());

    $fileContent = str_replace('[curDate]', $curDate, $fileContent);
    $fileContent = str_replace('[fileName]', $cleanedFilename, $fileContent);

    $result = db_fetchAll($sql, $params, __FILE__, __FUNCTION__, __LINE__);

    foreach ($result as $row)
    {
        $template = str_replace("\r\n", '', $row['template']);
        $template = str_replace("\n", '', $template);

        //$newLine = str_replace('[id]', $row['id'], $categoryTemplate);
        $newLine = str_replace('[bot_id]', $row['bot_id'], $newLine);
        $newLine = str_replace('[pattern]', $row['pattern'], $newLine);
        $newLine = str_replace('[thatpattern]', $row['thatpattern'], $newLine);
        $newLine = str_replace('[template]', $template, $newLine);
        $newLine = str_replace('[topic]', $row['topic'], $newLine);
        $newLine = str_replace('[filename]', $row['filename'], $newLine);

        $fileContent .= "$newLine\r\n";
    }

    $fileContent = trim($fileContent, ",\r\n");
    $fileContent .= "\n";

    return $fileContent;
}

/**
 * Function getCheckboxes
 *
 * @return string
 */
function getCheckboxes()
{
    global $bot_id, $bot_name, $msg;

    /** @noinspection SqlDialectInspection */
    $sql = "SELECT DISTINCT filename FROM `aiml` WHERE `bot_id` = :bot_id ORDER BY `filename`;";
    $params = array(':bot_id' => $bot_id);
    $result = db_fetchAll($sql, $params, __FILE__, __FUNCTION__, __LINE__);

    if (count($result) == 0)
    {
        $msg = "The chatbot '$bot_name' has no AIML categories to download. Please select another bot.";
        return false;
    }

    $out = "";
    $checkboxTemplate = <<<endRow
                    <div class="cbCell">
                      <input id="[file_name_id]" name="filenames[]" type="checkbox" class="cbFiles" value="[file_name]">
                      <label for="[file_name_id]">&nbsp;[file_name]</label>
                    </div>
endRow;
    $rowCount = 0;

    foreach ($result as $row)
    {
        if (empty ($row['filename']))
        {
            $row['filename'] = 'unnamed_AIML.aiml';
        }

        $file_name = $row['filename'];
        $file_name_id = str_replace('.', '_', $file_name);
        $curCheckbox = str_replace('[file_name]', $file_name, $checkboxTemplate);
        $curCheckbox = str_replace('[file_name_id]', $file_name_id, $curCheckbox);
        $out .= $curCheckbox;
        $rowCount++;
    }

    return rtrim($out);
}

/**
 * Function renderMain
 *
 *
 * @return string
 */
function renderMain()
{
    global $msg, $template;

    $file_checkboxes = getCheckboxes();

    if ($file_checkboxes === false)
    {
        return "<div class=\"bold red center\">$msg</div><br>\n";
    }

    $content = $template->getSection('DownloadForm');
    $content = str_replace('[file_checkboxes]', $file_checkboxes, $content);

    return $content;
}

function replace_last($search, $replace, $subject)
{
    $pos = strripos($subject, $search);
    if(false !== $pos)
    {
        $sLen = strlen($search);
        $subject = substr_replace($subject, $replace, $pos, $sLen);
    }
    return $subject;
}

