<?php
  /***************************************
  * http://www.program-o.com
  * PROGRAM O
  * Version: 2.1.5
  * FILE: gui/plain/index.php
  * AUTHOR: Elizabeth Perreau and Dave Morton
  * DATE: 11th May 2013
  * DETAILS: simple xml example gui
  ***************************************/
?>
<!doctype html>
<head>
<meta charset="utf-8">
<?php
$convo_id = session_id();
$convo_id = substr($convo_id,0,10);

if(isset($_REQUEST['bot_id'])){
  $bot_id = $_REQUEST['bot_id'];
} else {
  $bot_id = 1;
}
  
$format = "xml";

if((isset($_REQUEST['say']))&&($_REQUEST['say']!='')) {

  $say = urlencode($_REQUEST['say']);
  //make an xml request...
  $request_url = "http://YOURSITE.COM/chatbot/conversation_start.php?say=$say&convo_id=$convo_id&bot_id=$bot_id&format=xml";
  $conversation = simplexml_load_file($request_url,"SimpleXmlElement");//,LIBXML_NOERROR+LIBXML_ERR_FATAL+LIBXML_ERR_NONE);
    
  if(($conversation)&&(count($conversation)>0)){
    $botname = (string)$conversation->bot_name;
    $username = (string)$conversation->user_name;
    $botsay = (string)$conversation->botsay;
    $usersay = (string)$conversation->usersay;

    $response .= "<div id=\"user\"><b>$username:</b>$usersay</div>";
    $response .= "<div id=\"bot\"><b>$botname:</b>$botsay</div>";
  }
}
?>
  <link rel="icon" href="./favicon.ico" type="image/x-icon" />
  <link rel="shortcut icon" href="./favicon.ico" type="image/x-icon" />
  <title>Program O AIML Chatbot</title>
  <meta name="Description" content="A Free Open Source AIML PHP MySQL Chatbot called Program-O. Version2" />
  <meta name="keywords" content="Open Source, AIML, PHP, MySQL, Chatbot, Program-O, Version2" />
</head>
<body>
  <p>Don't forget to change the $request_url in this file to connect the bot on your site. And to remove this message!</p>
  <div id="response"><?php echo $response ?></div>
  <form accept-charset="utf-8" method="post" action="./">
    <p>
      <input type="text" name="say" id="say" />
      <input type="submit" name="submit" id="say" value="say" />
    </p>
  </form>
</body>
</html>
