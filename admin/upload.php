<?php

/***************************************
 * http://www.program-o.com
 * PROGRAM O
 * Version: 2.6.5
 * FILE: upload.php
 * AUTHOR: Elizabeth Perreau and Dave Morton
 * DATE: FEB 01 2016
 * DETAILS: Provides functionality to upload AIML files to a chatbot's database
 ***************************************/
ini_set('memory_limit', '128M');
ini_set('max_execution_time', '0');
ini_set('display_errors', false);
ini_set('log_errors', true);
chdir(_ADMIN_PATH_);

$validationStatus = '';
$msg = '';
libxml_use_internal_errors(true);
$_SESSION['failCount'] = 0;

/** @noinspection PhpUndefinedVariableInspection */
$bot_id = ($bot_id == 'new') ? 0 : $bot_id;
$msg = (array_key_exists('aimlfile', $_FILES)) ? processUpload() : '';
$upperScripts = <<<endScript

    <script type="text/javascript">
<!--
      function showMe() {
        var sh = document.getElementById('showHelp');
        var tf = document.getElementById('uploadForm');
        sh.style.display = 'block';
        tf.style.display = 'none';
      }
      function hideMe() {
        var sh = document.getElementById('showHelp');
        var tf = document.getElementById('uploadForm');
        sh.style.display = 'none';
        tf.style.display = 'block';
      }
      function showHide() {
        var display = document.getElementById('showHelp').style.display;
        switch (display) {
          case '':
          case 'none':
            return showMe();
            break;
          case 'block':
            return hideMe();
            break;
          default:
            alert('display = ' + display);
        }
      }
      function checkSize(){
        var file_upload = document.getElementById('aimlfile');
        if (!file_upload.files) return false;
        var fileSize = file_upload.files[0].size;//.fileSize;
        var fileName = file_upload.files[0].name;//.fileSize;
        if (fileSize > 2000000){
          showError("The file " + fileName + " exceeds the file size limit of 2MB. Please choose a different file.")
          file_upload.value = null;
        }
        var fileType = file_upload.files[0].type;//.fileSize;
        //showError('The file type is ' + file_upload.files[0].type);
        if (fileType != 'text/aiml' && fileType != 'application/x-zip-compressed') {
          //showError("The file " + fileName + " is neither an AIML file, nor a zip archive. Please select another file.")
          //file_upload.value = null;
        }
      }
      function showError(msg){
        var errorDiv = document.getElementById("errMsg");
        var closeButton = '<div class="closeButton" id="closeButton" onclick="closeStatus(\'errMsg\')" title="Click to hide">&nbsp;</div>';
          errorDiv.innerHTML = closeButton + msg;
          errorDiv.style.display = 'block';

      }
//-->
    </script>
endScript;

$post_vars = filter_input_array(INPUT_POST);
$XmlEntities = array('&amp;' => '&', '&lt;' => '<', '&gt;' => '>', '&apos;' => '\'', '&quot;' => '"',);
$g_tagName = null;

$aiml_sql = "";
$pattern_sql = "";
$that_sql = "";
$template_sql = "";
$insert_sql = "";
$file = "";
$full_path = "";

$cat_counter = 0;

$AIML_List = getAIML_List();
$all_bots = getBotList();
$uploadContent = $template->getSection('UploadAIMLForm');
$showHelp = $template->getSection('UploadShowHelp');
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
$pageTitle = 'My-Program O - Upload AIML';
$mainContent = $template->getSection('UploadMain');
$mainTitle = "Upload AIML to use for the bot named $bot_name [helpLink]";

#$msg = (empty($msg)) ? 'Test' : $msg;
$mainContent = str_replace('[bot_name]', $bot_name, $mainContent);
$mainContent = str_replace('[mainTitle]', $mainTitle, $mainContent);
$mainContent = str_replace('[upload_content]', $uploadContent, $mainContent);
$mainContent = str_replace('[showHelp]', $showHelp, $mainContent);
$mainContent = str_replace('[AIML_List]', $AIML_List, $mainContent);
$mainContent = str_replace('[all_bots]', $all_bots, $mainContent);

$mainTitle = str_replace('[helpLink]', $template->getSection('HelpLink'), $mainTitle);
$mainTitle = str_replace('[errMsg]', $msg, $mainTitle);

/**
 * Function parseAIML
 *
 * @param $fn
 * @param      $aimlContent
 * @param bool $from_zip
 * @return string
 */
function parseAIML($fn, $aimlContent, $from_zip = false)
{
    global $dbConn, $msg, $debugmode, $bot_id, $charset;

    $post_vars = filter_input_array(INPUT_POST);
    file_put_contents(_LOG_PATH_ . 'post_vars.txt', print_r($post_vars, true));

    if (empty ($aimlContent))
    {
        return "File $fn was empty!";
    }

    $skipVal = (isset($post_vars['skipVal'])) ? true : false;
    //trigger_error(() ? "true" : 'false');
    $fileName = basename($fn);
    $success = false;
    $topic = '';
    #Clear the database of the old entries

    if (isset ($post_vars['clearDB']))
    {
        /** @noinspection SqlDialectInspection */
        $sql = "DELETE FROM `aiml`  WHERE `filename` = :filename AND bot_id = :bot_id";
        $params = array(':filename' => $fileName, ':bot_id' => $bot_id);
        $affectedRows = db_write($sql, $params, false, __FILE__, __FUNCTION__, __LINE__);
    }
    $myBot_id = (isset ($post_vars['bot_id'])) ? $post_vars['bot_id'] : $bot_id;

    # Read new file into the XML parser
    /** @noinspection SqlDialectInspection */
    $sql = 'INSERT INTO `aiml` (`id`, `bot_id`, `aiml`, `pattern`, `thatpattern`, `template`, `topic`, `filename`) VALUES
    (NULL, :bot_id, :aiml, :pattern, :that, :template, :topic, :fileName);';

    # Validate the incoming document
    /*******************************************************/
    /*       Set up for validation from a common DTD       */
    /*       This will involve removing the XML and        */
    /*       AIML tags from the beginning of the file      */
    /*       and replacing them with our own tags          */
    /*******************************************************/
    $validAIMLHeader = '<?xml version="1.0" encoding="[charset]"?>
<aiml version="1.0.1" xmlns="http://www.alicebot.org/TR/2001/WD-aiml">';
    $validAIMLHeader = str_replace('[charset]', $charset, $validAIMLHeader);
    $aimlTagStart = stripos($aimlContent, '<aiml', 0);
    $aimlTagEnd = strpos($aimlContent, '>', $aimlTagStart) + 1;
    $aimlFile = $validAIMLHeader . substr($aimlContent, $aimlTagEnd);
    //file_put_contents(_LOG_PATH_ . 'upload.aiml.txt', print_r($aimlFile, true));
    $tmpDir = _UPLOAD_PATH_ . 'tmp' . DIRECTORY_SEPARATOR;
    if (!file_exists($tmpDir)) mkdir($tmpDir, 0755);
    save_file(_UPLOAD_PATH_ . 'tmp/' . $fileName, $aimlFile);
    $status = '';

    try
    {
        libxml_clear_errors();
        libxml_use_internal_errors(true);
        $xml = new DOMDocument('1.0', 'utf-8');

        if (!$xml->loadXML(trim($aimlFile))) // $aimlContent
        {
            $msg = "File $fileName is <strong>NOT</strong> valid!<br />\n";
            list($null, $status) = upload_libxml_display_errors($fileName);
            $msg .= "$status<br>\n<hr>\n";
            $msg = wordwrap($msg, 80, "<br>\n");
            $_SESSION['failCount']++;
        }
        elseif (!$skipVal && !$xml->schemaValidate('aiml.xsd'))
        {
            $msg = "<div class=\"center\"><b>A total of [count] error[plural] been found in the file $fileName.</b></div><br><br>\n";
            $xmlErrCount = 0;
            list($xmlErrCount, $status) = upload_libxml_display_errors($fileName);
            $plural = ($xmlErrCount !== 1) ? 's have' : ' has';
            $msg = str_replace('[count]', $xmlErrCount, $msg);
            $msg = str_replace('[plural]', $plural, $msg);
            $msg .= "$status\n<br>\n<hr>\n";
            $_SESSION['failCount']++;
        }
        else
        {
            $aiml = new SimpleXMLElement($xml->saveXML());
            $rowCount = 0;
            $params = array();

            if (!empty ($aiml->topic))
            {
                foreach ($aiml->topic as $topicXML)
                {
                    # handle any topic tag(s) in the file
                    $topicAttributes = $topicXML->attributes();
                    $topic = $topicAttributes['name'];

                    foreach ($topicXML->category as $category)
                    {
                        $fullCategory = $category->asXML();

                        $pattern = trim($category->pattern);
                        $pattern = str_replace("'", ' ', $pattern);
                        $pattern = _strtoupper($pattern);

                        $that = $category->that;
                        $that = _strtoupper($that);

                        $template = $category->template->asXML();
                        $template = str_replace('<template>', '', $template);
                        $template = str_replace('</template>', '', $template);
                        $template = trim($template);
                        # Strip CRLF and LF from category (Windows/mac/*nix)
                        $aiml_add = str_replace(array("\r\n", "\n"), '', $fullCategory);

                        $params[] = array(
                            ':bot_id' => $bot_id,
                            ':aiml' => $aiml_add,
                            ':pattern' => $pattern,
                            ':that' => $that,
                            ':template' => $template,
                            ':topic' => $topic,
                            ':fileName' => $fileName
                        );
                    }
                }
            }

            $topic = '';

            if (!empty ($aiml->category))
            {
                foreach ($aiml->category as $category)
                {
                    $fullCategory = $category->asXML();

                    $pattern = trim($category->pattern);
                    $pattern = str_replace("'", ' ', $pattern);
                    $pattern = _strtoupper($pattern);

                    $that = $category->that;
                    $template = $category->template->asXML();
                    //strip out the <template> tags, as they aren't needed
                    $template = substr($template, 10);
                    $tLen = strlen($template);
                    $template = substr($template, 0, $tLen - 11);
                    $template = trim($template);
                    # Strip CRLF and LF from category (Windows/mac/*nix)
                    $aiml_add = str_replace(array("\r\n", "\n"), '', $fullCategory);
                    $params[] = array(
                        ':bot_id' => $bot_id,
                        ':aiml' => $aiml_add,
                        ':pattern' => $pattern,
                        ':that' => $that,
                        ':template' => $template,
                        ':topic' => '',
                        ':fileName' => $fileName
                    );
                }
            }

            if (!empty($params))
            {
                $topic = "";
                $fullCategory = $category->asXML();

                $pattern = trim($category->pattern);
                $pattern = str_replace("'", ' ', $pattern);
                $pattern = _strtoupper($pattern);

                $that = $category->that;
                $template = $category->template->asXML();
                //strip out the <template> tags, as they aren't needed
                $template = substr($template, 10);
                $tLen = strlen($template);
                $template = substr($template, 0, $tLen - 11);
                $template = trim($template);
                # Strip CRLF and LF from category (Windows/mac/*nix)
                $aiml_add = str_replace(array("\r\n", "\n"), '', $fullCategory);
                $params[] = array(
                    ':bot_id' => $bot_id,
                    ':aiml' => $aiml_add,
                    ':pattern' => $pattern,
                    ':that' => $that,
                    ':template' => $template,
                    ':topic' => '',
                    ':fileName' => $fileName
                );

                $rowCount = db_write($sql, $params, true, __FILE__, __FUNCTION__, __LINE__);
                $success = ($rowCount !== false) ? true : false;
            }

            $msg = ($from_zip === true) ? '' : "Successfully added $fileName to the database.<br />\n";
        }
    }
    catch (Exception $e)
    {
        $trace = $e->getTraceAsString();
        //exit($e->getMessage() . ' at line ' . $e->getLine());
        $msg = $e->getMessage() . ' at line ' . $e->getLine() . "<br>\n";
        //trigger_error("Trace:\n$trace");
        error_log("Trace:\n$trace", 3, _LOG_PATH_ . "error.upload.$fileName.log");
        //file_put_contents(_LOG_PATH_ . 'error.trace.log', $trace . "\nEnd Trace\n\n", FILE_APPEND);
        $success = false;
        $_SESSION['failCount']++;
        $errMsg = "There was a problem adding file $fileName to the database. Please refer to the message below to correct the problem and try again.<br>\n" . $e->getMessage();
        list($null, $status) = upload_libxml_display_errors($fileName);
        $_SESSION['failCount']++;
        $msg .= $status;
    }
    /*
          $xml->loadXML($aimlFile);
          if (!validateAIML($xml)) $msg .= "File $fileName is not valid AIML. See errors, below.";
    */
    return $msg;
}

/**
 * Function processUpload
 *
 * @return string
 */
function processUpload()
{
    global $msg;
    // Validate the uploaded file
    if ($_FILES['aimlfile']['size'] === 0 || empty($_FILES['aimlfile']['tmp_name']))
    {
        $msg = 'No file was selected.';
    }
    elseif ($_FILES['aimlfile']['size'] > 2000000)
    {
        $msg = 'The file was too large.';
    }
    elseif ($_FILES['aimlfile']['error'] !== UPLOAD_ERR_OK) {
        // There was a PHP error
        $msg = 'There was an error uploading.';
    }
    else
    {
        // Move the file
        $file = _UPLOAD_PATH_ . $_FILES['aimlfile']['name'];

        if (move_uploaded_file($_FILES['aimlfile']['tmp_name'], $file))
        {
            #file_put_contents(_LOG_PATH_ . 'upload.type.txt', 'Type = ' . $_FILES['aimlfile']['type']);
            if ($_FILES['aimlfile']['type'] == 'application/zip' or $_FILES['aimlfile']['type'] == 'application/x-zip-compressed')
            {
                return processZip($file);
            }
            else
            {
                return parseAIML($file, file_get_contents($file));
            }
        }
        else {
            $msg = 'There was an error moving the file.';
        }
    }
    $_SESSION['errorMessage'] = $msg;

    return $msg;
}

/**
 * Function getAIML_List
 *
 * @return string
 */
function getAIML_List()
{
    global $dbConn, $dbn, $bot_id;
    $out = "                  <!-- Start List of Currently Stored AIML files -->\n";
    /** @noinspection SqlDialectInspection */
    $sql = "SELECT DISTINCT filename FROM `aiml` WHERE `bot_id` = $bot_id ORDER BY `filename`;";
    $result = db_fetchAll($sql, null, __FILE__, __FUNCTION__, __LINE__);

    foreach ($result as $row)
    {
        if (empty ($row['filename']))
        {
            $curOption = "                No Filename entry<br />\n";
        }
        else {
            $out .= $row['filename'] . "<br />\n";
        }
    }
    $out .= "                  <!-- End List of Currently Stored AIML files -->\n";

    return $out;
}

/**
 * Function getBotList
 *
 * @return string
 */
function getBotList()
{
    global $dbConn, $dbn, $bot_id;
    $botOptions = '';

    /** @noinspection SqlDialectInspection */
    $sql = 'SELECT `bot_name`, `bot_id` FROM `bots` ORDER BY `bot_id`;';
    $result = db_fetchAll($sql, null, __FILE__, __FUNCTION__, __LINE__);

    foreach ($result as $row)
    {
        $bn = $row['bot_name'];
        $bi = $row['bot_id'];
        $sel = ($bot_id == $bi) ? ' selected="selected"' : '';
        $botOptions .= "                    <option$sel value=\"$bi\">$bn</option>\n";
    }

    return $botOptions;
}

/**
 * Function upload_libxml_display_errors
 *
 * @param $fileName
 * @return string
 */
function upload_libxml_display_errors($fileName)
{
    $out = '';
    $errors = libxml_get_errors();
    $xmlErrCount = count($errors);
    //file_put_contents(_LOG_PATH_ . "$fileName.$xmlErrCount.errors.txt", print_r($errors, true));

    foreach ($errors as $error)
    {
        $out .= upload_libxml_display_error($error);
    }
    libxml_clear_errors();

    return array($xmlErrCount, $out);
}

/**
 * Function upload_libxml_display_error
 *
 * @param $error
 * @return string
 */
function upload_libxml_display_error($error)
{
    $out = "\n";

    switch ($error->level)
    {
        case LIBXML_ERR_WARNING :
            $out .= "<b>Warning {$error->code}</b>: ";
            break;
        case LIBXML_ERR_ERROR :
            $out .= "<b>Error {$error->code}</b>: ";
            break;
        case LIBXML_ERR_FATAL :
            $out .= "<b>Fatal Error {$error->code}</b>: ";
            break;
    }

    $m = $error->message;
    $m = str_replace('{http://www.alicebot.org/TR/2001/WD-aiml}', '', $m);
    $m = preg_replace("/Element '(.*?)'/", 'Element \'&lt;$1>\'', $m);
    $m = wordwrap($m, 80, "<br>\n");
    $l = number_format($error->line);
    $out .= "$m on line $l.</strong><br>\n";

    return "$out\n";
}

/**
 * Function processZip
 *
 * @param $fileName
 * @return string
 */
function processZip($fileName)
{
    $out = '';
    $_SESSION['failCount'] = 0;
    $zipName = basename($fileName);
    $zip = new ZipArchive;
    $res = $zip->open($fileName);

    if ($res === TRUE)
    {
        $numFiles = $zip->numFiles;

        for ($loop = 0; $loop < $numFiles; $loop++)
        {
            $curName = $zip->getNameIndex($loop);

            if (strstr($curName, '/') !== false)
            {
                $endPos = strrpos($curName, '/') + 1;
                $curName = substr($curName, $endPos);
            }

            if (empty ($curName))
            {
                continue;
            }
            $fp = $zip->getStream($zip->getNameIndex($loop));

            if (!$fp)
            {
                $out .= "Processing for $curName failed.<br />\n";
                $bad_aiml_files = (!isset ($bad_aiml_files)) ? array() : $bad_aiml_files;
                $bad_aiml_files[] = $curName;
                $_SESSION['bad_aiml_files'] = $curName;
            }
            else
            {
                $curText = '';

                while (!feof($fp))
                {
                    $curText .= fread($fp, 8192);
                }

                fclose($fp);

                if (!stristr($curName, '.aiml'))
                {
                    continue;
                }
                $out .= parseAIML($curName, $curText, true);
            }
        }

        $zip->close();
        $failCount = $_SESSION['failCount'];
        $out .= "<br />\nUpload complete. $numFiles files were processed, and $failCount files encountered errors.<br />\n";

        if (isset ($_SESSION['bad_aiml_files']))
        {
            $out .= "<br />\nThe following AIML files encountered errors:<br />\n";

            foreach ($_SESSION['bad_aiml_files'] as $fn)
            {
                $out .= "$fn, ";
            }
            $out = rtrim($out, ', ') . "<br .>\nPlease test each of these files independently, to locate the errors within.";
            unset ($_SESSION['bad_aiml_files']);
        }
    }
    else
    {
        $out = "Upload failed. $fileName was either corrupted, or not a zip file.";
    }

    return $out;
}

function validateAIML($xml)
{
    file_put_contents(_LOG_PATH_ . 'upload.xml.txt', print_r($xml, true));
    global $validationStatus;
    $out = true;

    if (!$xml->schemaValidate('aiml.xsd'))
    {
        get_errors();
        $out = false;
    }

    return $out;
}

/**
 * Function libxml_display_error
 *
 * @param $error
 * @return string
 */
function libxml_display_error($error)
{
    global $aimlArray;
    $errorLine = $error->line;
    $errorXML = htmlentities(@$aimlArray[$errorLine]);
    $return = "<hr>\n";

    switch ($error->level)
    {
        case LIBXML_ERR_WARNING :
            $return .= "<b>Warning {$error->code}</b>: ";
            break;
        case LIBXML_ERR_ERROR :
            $return .= "<b>Error {$error->code}</b>: ";
            break;
        case LIBXML_ERR_FATAL :
            $return .= "<b>Fatal Error {$error->code}</b>: ";
            break;
    }
    $return .= trim($error->message);

    if ($error->file)
    {
        $return .= " in <b>{$error->file}</b>";
    }
    $return .= " on line <a href=\"#line$errorLine\">$errorLine</a>, column {$error->column}\n";
    $return .= "<br>$errorXML\n";

    return $return;
}

function get_errors()
{
    global $status;
    $errors = libxml_get_errors();
    $count = 0;

    foreach ($errors as $error)
    {
        $status .= libxml_display_error($error) . "<br />\n";
        $count++;
    }
    libxml_clear_errors();
    $plural = ($count > 1) ? 's have' : ' has';
    $status = str_replace('[count]', $count, $status);
    $status = str_replace('[plural]', $plural, $status);
}


