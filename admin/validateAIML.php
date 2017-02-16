<?php
/***************************************
 * http://www.program-o.com
 * PROGRAM O
 * Version: 2.6.4
 * FILE: validateAIML.php
 * AUTHOR: Elizabeth Perreau and Dave Morton
 * DATE: 06-02-2014
 * DETAILS: Validates uploaded AIML files
 ***************************************/

if (!file_exists('../config/global_config.php'))
{
    header('location: ../install/install_programo.php');
}

/** @noinspection PhpIncludeInspection */
require_once('../config/global_config.php');

chdir(__DIR__);
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('error_log', _LOG_PATH_ . 'validateAIML.error.log');

$status = '';
$displayAIML = '';
$ip = $_SERVER['REMOTE_ADDR'];
$ip = ($ip == '::1') ? 'localhost' : $ip;

if (!empty ($_FILES))
{
    $uploadDir = 'uploads/';
    //chdir($uploadDir);
    if (!file_exists("$uploadDir$ip"))
    {
        mkdir("$uploadDir$ip");
    }

    $tf = basename($_FILES['uploaded']['name']);
    $tf = str_replace(' ', '_', $tf);
    $target = $uploadDir . $ip . '/' . $tf;
    libxml_clear_errors();
    libxml_use_internal_errors(true);
    $tmpFile = str_replace('../', '', $_FILES['uploaded']['tmp_name']);

    if (move_uploaded_file($tmpFile, $target))
    {
        $aimlContent = trim(file_get_contents($target));
        $validAIMLHeader = '<?xml version="1.0" encoding="[charset]"?>
<aiml version="1.0.1" xmlns="http://www.alicebot.org/TR/2001/WD-aiml">';
        $validAIMLHeader = str_replace('[charset]', $charset, $validAIMLHeader);
        $aimlTagStart = stripos($aimlContent, '<aiml', 0);
        $aimlTagEnd = strpos($aimlContent, '>', $aimlTagStart) + 1;
        $aimlFile = $validAIMLHeader . substr($aimlContent, $aimlTagEnd);
        $fileName = basename($_FILES['uploaded']['name']);
        //$xml->preserveWhiteSpace = true;
        //$xml->formatOutput = true;
        $aimlArray = explode("\n", $aimlFile);
        array_unshift($aimlArray, null);
        unset($aimlArray[0]);

        foreach ($aimlArray as $line => $content)
        {
            $content = trim($content);
            $displayAIML .= "    <div class=\"source\" id=\"line$line\"><pre>$line | " . htmlentities($content) . "</pre></div>\n";
        }

        $xml = new DOMDocument('1.0', 'utf-8');

        if (!$xml->loadXML($aimlFile))
        {
            $status = "File $fileName is <strong>NOT</strong> valid!<br />\n";
            libxml_display_errors();
        }
        elseif (!$xml->schemaValidate('aiml.xsd'))
        {
            $status = '<b>A total of [count] error[plural] been found in this document.</b>';
            libxml_display_errors();
        }
        else {
            $status = "File $fileName is valid.<br />\n";
        }
    }
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

function libxml_display_errors()
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

?>
<!doctype html>
<html>
<head>
    <title>Program O AIML File Validator</title>
    <style type="text/css">
        .center {
            text-align: center;
        }
    </style>
</head>
<body>
<h2 style="text-align: center">Program O AIML File Validator</h2>
<p>
    This script will check to make sure that your AIML files are not only well formed, but also
    pass validation, based on the AIML 1.0.1 specification. This is important, because the
    Program O upload script in the admin pages requires the uploaded AIML files to be valid AIML
    in order for them to be added to your bot's database. This prevents <strong>some</strong> "bugs"
    from occurring that are actually problems with improperly created AIML files.
</p>
<p>
    Simply upload your AIML file, and the script will examine it. If it passes validation, then you'll
    get a simple notice on the page telling you so. If it fails, you'll get a detailed list of problems
    that the validator encountered.
</p>
<p>
    Please note that many standard HTML tags (e.g. &lt;b&gt;, &lt;u&gt;, &lt;i&gt; etc.) are not part of the
    AIML specification, and will therefor fail if encountered. That said, however, Program O will happily
    accept them, so if your AIML file fails validation for <b>ONLY</b> having HTML tags, you're still ok.
</p>
<div class="center">
    <form enctype="multipart/form-data" action="validateAIML.php" method="post">
        Please choose a file: <input name="uploaded" type="file" tabindex="1"/>&nbsp;&nbsp;
        <input type="submit" value="Validate"/><br>
    </form>
</div>
<hr/>
<?php echo $status ?>
<hr/>
<?php echo $displayAIML ?>
</body>
</html>