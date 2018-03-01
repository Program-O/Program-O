<?php

/***************************************
 * http://www.program-o.com
 * PROGRAM O
 * Version: 2.6.*
 * FILE: gui/plain/index.php
 * AUTHOR: Elizabeth Perreau and Dave Morton
 * DATE: MAY 17TH 2014
 * DETAILS: simple example gui
 ***************************************/
$display = "";
$thisFile = __FILE__;

if (!file_exists('../../config/global_config.php'))
{
    header('Location: ../../install/install_programo.php');
}

/** @noinspection PhpIncludeInspection */
require_once('../../config/global_config.php');
session_name('ProgramO');
$debug_div = '';
$hideSP = '';
$resizeResponseDiv = '';
$clearButton = '';
$get_vars = (!empty($_GET)) ? filter_input_array(INPUT_GET) : array();
$post_vars = (!empty($_POST)) ? filter_input_array(INPUT_POST) : array();
$form_vars = array_merge($post_vars, $get_vars); // POST overrides and overwrites GET
if (!empty($form_vars)) require_once('../../chatbot/conversation_start.php');
  $bot_id = (!empty($form_vars['bot_id'])) ? $form_vars['bot_id'] : 1;
$say = (!empty($form_vars['say'])) ? $form_vars['say'] : '';
$convo_id = (isset($form_vars['convo_id'])) ? $form_vars['convo_id'] : md5(time());
$format = (!empty($form_vars['format'])) ? _strtolower($form_vars['format']) : 'html';

if (ERROR_DEBUGGING)
{
    $convo_id = (isset($form_vars['convo_id'])) ? $form_vars['convo_id'] : 'DEBUG'; // Hard-code the convo_id during debugging
    $debug_src = (!empty($form_vars) && file_exists(_DEBUG_PATH_ . "{$convo_id}.txt")) ? _DEBUG_URL_ . "reader.php?file={$convo_id}.txt" : '';
    $debug_div = <<<endDebugDiv

<iframe id="debugDiv" src="$debug_src" frameborder="0">
endDebugDiv;
    $hideSP = 'display: none;';
    $resizeResponseDiv = 'max-height: 200px;';
    $clearButton = <<<endClr
<input id="btnClear" name="" type="button" value="Clear Div" onclick="document.getElementById('responses').innerHTML = '';">
endClr;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Program O AIML PHP Chatbot</title>
    <link rel="icon" href="./favicon.ico" type="image/x-icon"/>
    <link rel="shortcut icon" href="./favicon.ico" type="image/x-icon"/>
    <meta name="Description" content="A Free Open Source AIML PHP MySQL Chatbot called Program-O. Version2"/>
    <meta name="keywords" content="Open Source, AIML, PHP, MySQL, Chatbot, Program-O, Version2"/>
    <style type="text/css">
        body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        #responses {
            width: 90%;
            min-width: 515px;
            height: auto;
            min-height: 150px;
            max-height: 500px;
            overflow: auto;
            border: 3px inset #666;
            margin-left: auto;
            margin-right: auto;
            margin-bottom: 1em;
            padding: 5px;
            box-sizing: border-box;
            <?php echo $resizeResponseDiv ?>
        }
        #debugDiv {
            width: 90%;
            min-height: 1.2em;
            height: 55vh;
            border: 3px inset #666;
            margin-left: auto;
            margin-right: auto;
            display: block;
        }

        #input {
            width: 90%;
            min-width: 535px;
            margin-bottom: 15px;
            margin-left: auto;
            margin-right: auto;
        }

        #shameless_plug {
            position: absolute;
            right: 10px;
            bottom: 10px;
            border: 1px solid red;
            box-sizing: border-box;
            -moz-box-sizing: border-box;
            box-shadow: 2px 2px 2px 0 #808080;
            padding: 5px;
            border-radius: 5px;
            <?php echo $hideSP ?>
        }


        #convo_id {
            position: absolute;
            top: 10px;
            right: 10px;
            border: 1px solid red;
            box-sizing: border-box;
            -moz-box-sizing: border-box;
            box-shadow: 2px 2px 2px 0 #808080;
            padding: 5px;
            border-radius: 5px;
        }

    </style>
</head>
<body onload="document.getElementById('say').focus()">
<h3>Program O Example GUI Page - HTML</h3>
<!-- The DIV below is for debugging purposes, and can be safely removed, if desired. -->
<div id="convo_id">Conversion ID: <?php echo $convo_id; ?></div>
<form name="chatform" method="post" action="index.php#end"
      onsubmit="if(document.getElementById('say').value == '') return false;">
    <div id="input">
        <label for="say">Say:</label>
        <input type="text" name="say" id="say" size="70"/>
        <input type="submit" name="submit" id="btn_say" value="say"/>
        <input type="hidden" name="convo_id" id="convo_id" value="<?php echo $convo_id; ?>"/>
        <input type="hidden" name="bot_id" id="bot_id" value="<?php echo $bot_id; ?>"/>
        <input type="hidden" name="format" id="format" value="<?php echo $format; ?>"/>
        <?php echo $clearButton ?>
    </div>
</form>
<div id="responses">
    <?php echo $display . '<div id="end">&nbsp;</div>' . PHP_EOL ?>
</div>
<?php echo $debug_div ?>
<div id="shameless_plug">
    To get your very own chatbot, visit <a href="http://www.program-o.com">program-o.com</a>!
</div>
</body>
</html>
