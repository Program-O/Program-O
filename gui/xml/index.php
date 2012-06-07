<?php
/***************************************
* www.program-o.com
* PROGRAM O
* Version: 2.0.1
* FILE: gui/xml/index.php
* AUTHOR: ELIZABETH PERREAU and DAVE MORTON
* DATE: Feb. 26th, 2012
* DETAILS: this file contains the chatbot's
    XML interface
***************************************/

  if (!file_exists('../config/global_config.php')) header('location: ../install/install_programo.php');
  require_once('../config/global_config.php');

$response = '';
session_start();
function get_response($path){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$path);
        curl_setopt($ch, CURLOPT_FAILONERROR,1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $retValue = curl_exec($ch);
        curl_close($ch);
        return $retValue;
}
$bot_id = 1;
$convo_id = session_id();
$format = "xml";
if(isset($_REQUEST['say'])) {
  if(isset($_REQUEST['say'])){
    $say = urlencode($_REQUEST['say']);
  }
  else{
    $say = "hi";
  }
  $responseTemplate = <<<endResponse
[user_name]: [usersay]<br />
[bot_name]: [botsay]<br />
endResponse;

  $thisFileURL = $_SERVER['SCRIPT_NAME'];
  $chatbotURLpath = str_replace('/gui/xml/index.php', '/chatbot',$thisFileURL);
  define("CHATBOT_URL_PATH",$chatbotURLpath);

  $send = "http://".$_SERVER['HTTP_HOST']. CHATBOT_URL_PATH . "/conversation_start.php?say=$say&convo_id=$convo_id&bot_id=$bot_id&format=$format";
  #$X = file_put_contents('URL.txt', "$send\r\n",FILE_APPEND);
#die();
  $sXML = trim(get_response($send));
  /*
  $response = htmlentities($sXML);
  $response = str_replace("\t\t", "\n", $response);
  $response = str_replace("\t", "\n", $response);
  */
  #file_put_contents('conversationXML.txt', $sXML);
  $xml = new SimpleXMLElement($sXML);
  #$xmlConversation = $xml->conversation;
  #$user_name = $xmlConversation->user_name;
  #$bot_name  = $xmlConversation->bot_name;
  $count = 0;
  foreach ($xml->children() as $child) {
    $childName = $child->getName();
    switch ($childName) {
      case 'user_name':
      $user_name = $child;
      break;
      case 'bot_name':
      $bot_name = $child;
      break;
      case 'usersay':
      $response .= "$user_name: " . $child . "<br />\n";
      break;
      case 'botsay':
      $response .= "$bot_name: " . $child . "<br />\n";
      default:
    }
/*
*/
  }
/*
*/
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
  <head>
    <link rel="icon" href="./favicon.ico" type="image/x-icon" />
    <link rel="shortcut icon" href="./favicon.ico" type="image/x-icon" />
      <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
    <title>Program O AIML Chatbot</title>
    <meta name="Description" content="A Free Open Source AIML PHP MySQL Chatbot called Program-O. Version2" />
    <meta name="keywords" content="Open Source, AIML, PHP, MySQL, Chatbot, Program-O, Version2" />
  </head>
  <body>
    <div id="response"><?php echo $response ?></div>
    <form method="post" action="./">
      <p>
        <label>Say:</label>
        <input type="text" name="say" id="say" />
        <input type="submit" name="submit" id="say" value="say" />
        <input type="hidden" name="convo_id" id="convo_id" value="<?php echo $convo_id;?>" />
        <input type="hidden" name="bot_id" id="bot_id" value="<?php echo $bot_id;?>" />
        <input type="hidden" name="format" id="format" value="<?php echo $format;?>" />
      </p>
    </form>
  </body>
</html>


