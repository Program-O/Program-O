<?php

  /***************************************
  * http://www.program-o.com
  * PROGRAM O
  * Version 2.2.2
  * FILE: gui/plain/index.php
  * AUTHOR: Elizabeth Perreau and Dave Morton
  * DATE: 11th May 2013
  * DETAILS: simple xml example gui
  ***************************************/
  $response = '';
  session_name('programo_XML_API');
  session_start();
  $convo_id = session_id();
  if (isset ($_REQUEST['bot_id']))
  {
    $bot_id = $_REQUEST['bot_id'];
  }
  else
  {
    $bot_id = 1;
  }
  $format = "xml";
  if ((isset ($_REQUEST['say'])) && ($_REQUEST['say'] != ''))
  {
    $say = urlencode($_REQUEST['say']);
    //make an xml request...
    #$request_url = "http://YOURSITE.COM/chatbot/conversation_start.php?say=$say&convo_id=$convo_id&bot_id=$bot_id&format=xml";
    $request_url = 'http://dmorton/Program-O/chatbot/conversation_start.php';
    $options = array(
               CURLOPT_USERAGENT => 'Program O XML API',
    );
    $form_vars_post = filter_input_array(INPUT_POST);
    $convo_xml = get_cURL($request_url, $options, $form_vars_post); //
    $conversation = simplexml_load_string($convo_xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    if (($conversation) && (count($conversation) > 0))
    {
      $botname = (string) $conversation->bot_name;
      $username = (string) $conversation->user_name;
      $botsay = (string) $conversation->botsay;
      $usersay = (string) $conversation->usersay;
      $response .= "<div id=\"user\"><b>$username:</b>$usersay</div>";
      $response .= "<div id=\"bot\"><b>$botname:</b>$botsay</div>";
      $response = str_ireplace('<![CDATA[', '', $response);
      $response = str_replace(']]>', '', $response);
    }
  }

  function get_cURL($url, $options = array(), $params = array())
  {
    $failed = 'Cannot process CURL call.'; // This will need to be changed, at some point.
    if (function_exists('curl_init'))
    {
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      if (is_array($options) and count($options) > 0)
      {
        foreach ($options as $key => $value)
        {
          curl_setopt($ch, $key, $value);
        }
      }
      if (is_array($params) and count($params) > 0)
      {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
      }
      $data = curl_exec($ch);
      curl_close($ch);
      return $data;
    }
    else
      return $failed;
  }
?>
<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <link rel="icon" href="./favicon.ico" type="image/x-icon" />
    <link rel="shortcut icon" href="./favicon.ico" type="image/x-icon" />
    <title>Program O AIML Chatbot</title>
    <meta name="Description" content="A Free Open Source AIML PHP MySQL Chatbot called Program-O. Version2" />
    <meta name="keywords" content="Open Source, AIML, PHP, MySQL, Chatbot, Program-O, Version2" />
  </head>
  <body>
    <p>Don't forget to change the $request_url in this file to connect the bot on your site. And to remove this message!</p>
    <div id="response"><?php echo $response ?></div>
    <form accept-charset="utf-8" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
      <p>
        <input type="text" name="say" id="say" />
        <input id="bot_id" type="hidden" name="bot_id" value="<?php echo $bot_id ?>">
        <input id="convo_id" type="hidden" name="convo_id" value="<?php echo $convo_id ?>">
        <input id="format" type="hidden" name="format" value="<?php echo $format ?>">
        <input type="submit" value="Chat" />
      </p>
    </form>
  </body>
</html>
