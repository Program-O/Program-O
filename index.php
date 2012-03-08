<?php
  ob_start();

  include ("./chatbot/conversation_start.php");

  #if (isset($_REQUEST['submit'])) die ("<pre>" . print_r($_REQUEST, true) . "</pre><br />\n");
  if (isset ($_REQUEST['bot_id'])) {
    $bot_id = $_REQUEST['bot_id'];
  }
  else {
    $bot_id = $default_bot_id;
  }
  if (isset ($_REQUEST['convo_id'])) {
    $convo_id = $_REQUEST['convo_id'];
  }
  else {
    $convo_id = $default_convo_id;
  }
  if (isset ($_REQUEST['format'])) {
    $format = $_REQUEST['format'];
  }
  else {
    $format = $default_format;
  }
  $output = (isset ($convoArr['send_to_user'])) ? $convoArr['send_to_user'] . ' <br /> <a name="new" />' : "Hi there! Please tell me your name.";
  $thisScript = $_SERVER['PHP_SELF'] . '#new';
  $content = <<<endPage
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
  <head>
    <link rel="icon" href="./favicon.ico" type="image/x-icon" />
    <link rel="shortcut icon" href="./favicon.ico" type="image/x-icon" />
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
    <link rel="stylesheet" type="text/css" href="style.css" />
    <title>Program O Test Bot</title>
    <meta name="Description" content="A Free Open Source AIML PHP MySQL Chatbot called Program-O. Version2" />
    <meta name="keywords" content="Open Source, AIML, PHP, MySQL, Chatbot, Program-O, Version2" />
  </head>
  <body onload="document.forms[0].say.focus();">
    <form method="post" action="$thisScript">
      <p>
        <label for="say">Say:</label>
        <input type="text" name="say" id="say" />
        <input type="submit" name="submit" id="submit" value="say" />
        <input type="hidden" name="convo_id" id="convo_id" value="$convo_id" />
        <input type="hidden" name="bot_id" id="bot_id" value="$bot_id" />
        <input type="hidden" name="format" id="format" value="$format" />
      </p>
    </form>
    <div id="output">$output&nbsp;</div>
  </body>
</html>
endPage;
print $content;
ob_end_flush();
?>